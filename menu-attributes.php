<?php
/**
 * Модуль: Атрибуты в меню
 * Описание: Добавление атрибутов товаров в меню навигации для фильтрации
 * Зависимости: нет
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавление атрибутов товаров в меню
 * ИСПРАВЛЕНО: Убрана проверка на конкретное меню
 */
add_filter('wp_nav_menu_objects', 'parusweb_add_attributes_to_menu', 10, 2);
function parusweb_add_attributes_to_menu($items, $args) {
    
    // Работаем со всеми меню (убрали проверку theme_location)
    
    foreach ($items as $item) {
        // Ищем пункты меню для категорий товаров
        if ($item->object === 'product_cat') {
            $category_id = $item->object_id;
            
            // Получаем атрибуты для этой категории
            $attributes = parusweb_get_category_attributes($category_id);
            
            if (!empty($attributes)) {
                // Добавляем атрибуты как классы для стилизации
                $item->classes[] = 'has-attributes';
                $item->classes[] = 'attribute-count-' . count($attributes);
                
                // Сохраняем атрибуты в объекте для дальнейшего использования
                $item->attributes_data = $attributes;
            }
        }
    }
    
    return $items;
}

/**
 * Получение атрибутов для категории
 */
function parusweb_get_category_attributes($category_id) {
    // Кешируем результат
    $cache_key = 'parusweb_cat_attrs_' . $category_id;
    $cached = wp_cache_get($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Получаем товары категории (ограничиваем количество для производительности)
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 50, // Ограничиваем для скорости
        'fields' => 'ids', // Только ID
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
    );
    
    $product_ids = get_posts($args);
    
    if (empty($product_ids)) {
        wp_cache_set($cache_key, array(), '', 3600);
        return array();
    }
    
    $attributes = array();
    
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            continue;
        }
        
        // Получаем атрибуты товара
        $product_attributes = $product->get_attributes();
        
        foreach ($product_attributes as $attribute_name => $attribute) {
            if (!isset($attributes[$attribute_name])) {
                $attributes[$attribute_name] = array(
                    'name' => wc_attribute_label($attribute_name),
                    'values' => array(),
                );
            }
            
            // Получаем значения атрибута
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product_id, $attribute->get_name());
                foreach ($terms as $term) {
                    $attributes[$attribute_name]['values'][$term->slug] = $term->name;
                }
            } else {
                $values = $attribute->get_options();
                foreach ($values as $value) {
                    $slug = sanitize_title($value);
                    $attributes[$attribute_name]['values'][$slug] = $value;
                }
            }
        }
    }
    
    // Кешируем на 1 час
    wp_cache_set($cache_key, $attributes, '', 3600);
    
    return $attributes;
}

/**
 * Вывод атрибутов в виде фильтров в сайдбаре/меню
 */
add_action('woocommerce_before_shop_loop', 'parusweb_display_attribute_filters', 15);
function parusweb_display_attribute_filters() {
    
    if (!is_product_category()) {
        return;
    }
    
    $category = get_queried_object();
    
    if (!$category) {
        return;
    }
    
    $attributes = parusweb_get_category_attributes($category->term_id);
    
    if (empty($attributes)) {
        return;
    }
    
    echo '<div class="product-attributes-filter">';
    echo '<h3>Фильтры</h3>';
    
    foreach ($attributes as $attr_name => $attr_data) {
        // Пропускаем атрибуты без значений
        if (empty($attr_data['values'])) {
            continue;
        }
        
        echo '<div class="attribute-filter">';
        echo '<h4>' . esc_html($attr_data['name']) . '</h4>';
        echo '<ul>';
        
        foreach ($attr_data['values'] as $slug => $name) {
            $is_active = isset($_GET['filter_' . $attr_name]) && $_GET['filter_' . $attr_name] === $slug;
            $class = $is_active ? 'active' : '';
            
            $url = add_query_arg('filter_' . $attr_name, $slug);
            
            if ($is_active) {
                $url = remove_query_arg('filter_' . $attr_name);
            }
            
            echo '<li class="' . $class . '">';
            echo '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    // Кнопка сброса фильтров
    $has_filters = false;
    foreach ($_GET as $key => $value) {
        if (strpos($key, 'filter_') === 0) {
            $has_filters = true;
            break;
        }
    }
    
    if ($has_filters) {
        $reset_url = strtok($_SERVER['REQUEST_URI'], '?');
        echo '<div class="filter-reset">';
        echo '<a href="' . esc_url($reset_url) . '" class="button">Сбросить фильтры</a>';
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Применение фильтров к запросу товаров
 */
add_action('pre_get_posts', 'parusweb_apply_attribute_filters');
function parusweb_apply_attribute_filters($query) {
    
    if (is_admin() || !$query->is_main_query() || !is_product_category()) {
        return;
    }
    
    $meta_query = array();
    $tax_query = $query->get('tax_query') ?: array();
    
    foreach ($_GET as $key => $value) {
        if (strpos($key, 'filter_') !== 0) {
            continue;
        }
        
        $attribute = str_replace('filter_', '', $key);
        
        // Проверяем является ли это таксономией
        if (taxonomy_exists('pa_' . $attribute)) {
            $tax_query[] = array(
                'taxonomy' => 'pa_' . $attribute,
                'field' => 'slug',
                'terms' => sanitize_text_field($value),
            );
        } else {
            // Для кастомных атрибутов используем meta_query
            $meta_query[] = array(
                'key' => 'attribute_' . $attribute,
                'value' => sanitize_text_field($value),
                'compare' => 'LIKE',
            );
        }
    }
    
    if (!empty($meta_query)) {
        $meta_query['relation'] = 'AND';
        $existing_meta = $query->get('meta_query') ?: array();
        $query->set('meta_query', array_merge($existing_meta, $meta_query));
    }
    
    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }
}

/**
 * CSS стили для фильтров атрибутов
 */
add_action('wp_head', 'parusweb_attribute_filter_styles');
function parusweb_attribute_filter_styles() {
    ?>
    <style>
    .product-attributes-filter {
        background: #f5f5f5;
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 8px;
    }
    
    .product-attributes-filter h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 20px;
    }
    
    .attribute-filter {
        margin-bottom: 20px;
    }
    
    .attribute-filter h4 {
        margin-bottom: 10px;
        font-size: 16px;
        color: #333;
    }
    
    .attribute-filter ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .attribute-filter li {
        margin-bottom: 8px;
    }
    
    .attribute-filter li a {
        display: block;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
    }
    
    .attribute-filter li a:hover {
        background: #e0e0e0;
        border-color: #999;
    }
    
    .attribute-filter li.active a {
        background: #0073aa;
        color: #fff;
        border-color: #0073aa;
    }
    
    .filter-reset {
        margin-top: 20px;
        text-align: center;
    }
    
    .filter-reset .button {
        display: inline-block;
        padding: 10px 20px;
        background: #dc3232;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        transition: background 0.3s;
    }
    
    .filter-reset .button:hover {
        background: #a02020;
    }
    
    @media (max-width: 768px) {
        .product-attributes-filter {
            padding: 15px;
        }
        
        .attribute-filter li a {
            padding: 6px 10px;
            font-size: 14px;
        }
    }
    </style>
    <?php
}

/**
 * Очистка кеша атрибутов при изменении товара
 */
add_action('save_post_product', 'parusweb_clear_attribute_cache', 10, 1);
function parusweb_clear_attribute_cache($post_id) {
    $categories = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'ids'));
    
    foreach ($categories as $cat_id) {
        wp_cache_delete('parusweb_cat_attrs_' . $cat_id);
    }
}