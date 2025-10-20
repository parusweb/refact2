<?php
/**
 * Модуль: Отображение калькуляторов
 * Описание: HTML разметка и вывод калькуляторов на странице товара
 * Зависимости: category-helpers, product-calculations
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Главная функция вывода калькуляторов
 */
add_action('woocommerce_before_add_to_cart_button', 'display_product_calculators', 5);
function display_product_calculators() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    
    // Проверяем нужен ли калькулятор
    if (!function_exists('is_in_target_categories') || !is_in_target_categories($product_id)) {
        return;
    }
    
    $calculator_type = function_exists('get_calculator_type') ? get_calculator_type($product_id) : 'none';
    
    if ($calculator_type === 'none') {
        return;
    }
    
    // Получаем данные товара
    $base_price = $product->get_price();
    $title = $product->get_title();
    $area_data = function_exists('extract_area_with_qty') ? extract_area_with_qty($title, $product_id) : null;
    $multiplier = function_exists('get_final_multiplier') ? get_final_multiplier($product_id) : 1.0;
    
    // Применяем множитель к цене
    $price_with_multiplier = $base_price * $multiplier;
    
    // Выводим нужный калькулятор
    switch ($calculator_type) {
        case 'square_meter':
            display_square_meter_calculator($product_id, $price_with_multiplier, $area_data);
            break;
            
        case 'running_meter':
            display_running_meter_calculator($product_id, $price_with_multiplier);
            break;
            
        case 'falsebalk':
            display_falsebalk_calculator($product_id, $price_with_multiplier);
            break;
            
        case 'dimensions':
            display_dimensions_calculator($product_id, $price_with_multiplier, $area_data);
            break;
    }
    
    // Добавляем блок услуг покраски если нужно
    if (function_exists('is_in_painting_categories') && is_in_painting_categories($product_id)) {
        display_painting_services($product_id);
    }
}

/**
 * Калькулятор площади (квадратные метры)
 */
function display_square_meter_calculator($product_id, $price, $area_data) {
    ?>
    <div id="square-meter-calculator" class="parusweb-calculator" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
        <h4 style="margin-top: 0;">Калькулятор по площади</h4>
        
        <?php if ($area_data): ?>
            <div style="margin-bottom: 15px; padding: 10px; background: #e8f4f8; border-radius: 4px;">
                <strong>Площадь в упаковке:</strong> <?php echo number_format($area_data, 2, ',', ' '); ?> м²
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ширина (м):</label>
            <input type="number" id="custom_sq_width" name="custom_sq_width" 
                   step="0.01" min="0" placeholder="0.00"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Длина (м):</label>
            <input type="number" id="custom_sq_length" name="custom_sq_length" 
                   step="0.01" min="0" placeholder="0.00"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div id="sq_calc_result" style="padding: 15px; background: #fff; border: 2px solid #4CAF50; border-radius: 6px; display: none;">
            <div style="margin-bottom: 10px;">
                <strong>Площадь:</strong> <span id="sq_total_area">0</span> м²
            </div>
            <?php if ($area_data): ?>
            <div style="margin-bottom: 10px;">
                <strong>Количество упаковок:</strong> <span id="sq_packs_needed">0</span>
            </div>
            <?php endif; ?>
            <div style="font-size: 18px; color: #4CAF50; font-weight: 700;">
                <strong>Итого:</strong> <span id="sq_total_price">0 ₽</span>
            </div>
        </div>
        
        <input type="hidden" id="custom_sq_total_price" name="custom_sq_total_price" value="0">
        <input type="hidden" id="custom_sq_quantity" name="custom_sq_quantity" value="1">
        <input type="hidden" id="custom_sq_pack_area" value="<?php echo esc_attr($area_data); ?>">
        <input type="hidden" id="custom_sq_base_price" value="<?php echo esc_attr($price); ?>">
    </div>
    <?php
}

/**
 * Калькулятор погонных метров
 */
function display_running_meter_calculator($product_id, $price) {
    ?>
    <div id="running-meter-calculator" class="parusweb-calculator" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
        <h4 style="margin-top: 0;">Калькулятор погонных метров</h4>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Длина (м.п.):</label>
            <input type="number" id="custom_rm_length" name="custom_rm_length" 
                   step="0.1" min="0.1" placeholder="0.0"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div id="rm_calc_result" style="padding: 15px; background: #fff; border: 2px solid #4CAF50; border-radius: 6px; display: none;">
            <div style="margin-bottom: 10px;">
                <strong>Длина:</strong> <span id="rm_total_length">0</span> м.п.
            </div>
            <div style="margin-bottom: 10px;">
                <strong>Цена за м.п.:</strong> <?php echo wc_price($price); ?>
            </div>
            <div style="font-size: 18px; color: #4CAF50; font-weight: 700;">
                <strong>Итого:</strong> <span id="rm_total_price">0 ₽</span>
            </div>
        </div>
        
        <input type="hidden" id="custom_rm_total_price" name="custom_rm_total_price" value="0">
        <input type="hidden" id="custom_rm_quantity" name="custom_rm_quantity" value="1">
        <input type="hidden" id="custom_rm_base_price" value="<?php echo esc_attr($price); ?>">
    </div>
    <?php
}

/**
 * Калькулятор фальшбалок
 */
function display_falsebalk_calculator($product_id, $price) {
    $shapes_data = get_post_meta($product_id, '_falsebalk_shapes_data', true);
    
    if (!is_array($shapes_data) || empty($shapes_data)) {
        return;
    }
    
    $shape_labels = array(
        'g' => 'Г-образная',
        'p' => 'П-образная',
        'o' => 'О-образная'
    );
    
    ?>
    <div id="falsebalk-calculator" class="parusweb-calculator" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
        <h4 style="margin-top: 0;">Калькулятор фальшбалок</h4>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Форма сечения:</label>
            <select id="falsebalk_shape" name="falsebalk_shape" 
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Выберите форму</option>
                <?php foreach ($shapes_data as $shape_key => $shape_info): ?>
                    <?php if (!empty($shape_info['enabled'])): ?>
                        <option value="<?php echo esc_attr($shape_key); ?>"
                                data-config='<?php echo esc_attr(json_encode($shape_info)); ?>'>
                            <?php echo esc_html($shape_labels[$shape_key]); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="falsebalk_dimensions" style="display: none;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ширина (мм):</label>
                <select id="falsebalk_width" name="falsebalk_width"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Высота (мм):</label>
                <select id="falsebalk_height" name="falsebalk_height"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Длина (м):</label>
                <input type="number" id="falsebalk_length" name="falsebalk_length"
                       step="0.1" min="0.1" placeholder="0.0"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
        
        <div id="falsebalk_result" style="padding: 15px; background: #fff; border: 2px solid #4CAF50; border-radius: 6px; display: none;">
            <div style="margin-bottom: 10px;">
                <strong>Длина:</strong> <span id="falsebalk_total_length">0</span> м.п.
            </div>
            <div style="font-size: 18px; color: #4CAF50; font-weight: 700;">
                <strong>Итого:</strong> <span id="falsebalk_total_price">0 ₽</span>
            </div>
        </div>
        
        <input type="hidden" id="custom_rm_base_price" value="<?php echo esc_attr($price); ?>">
        <input type="hidden" name="falsebalk_shape_label" id="falsebalk_shape_label" value="">
    </div>
    <?php
}

/**
 * Калькулятор размеров
 */
function display_dimensions_calculator($product_id, $price, $area_data) {
    ?>
    <div id="dimensions-calculator" class="parusweb-calculator" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
        <h4 style="margin-top: 0;">Калькулятор по размерам</h4>
        
        <?php if ($area_data): ?>
            <div style="margin-bottom: 15px; padding: 10px; background: #e8f4f8; border-radius: 4px;">
                <strong>Площадь упаковки:</strong> <?php echo number_format($area_data, 2, ',', ' '); ?> м²
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ширина (м):</label>
            <input type="number" id="custom_mult_width" name="custom_mult_width" 
                   step="0.01" min="0" placeholder="0.00"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Длина (м):</label>
            <input type="number" id="custom_mult_length" name="custom_mult_length" 
                   step="0.01" min="0" placeholder="0.00"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div id="mult_calc_result" style="padding: 15px; background: #fff; border: 2px solid #4CAF50; border-radius: 6px; display: none;">
            <div style="margin-bottom: 10px;">
                <strong>Площадь:</strong> <span id="mult_total_area">0</span> м²
            </div>
            <?php if ($area_data): ?>
            <div style="margin-bottom: 10px;">
                <strong>Количество упаковок:</strong> <span id="mult_packs_needed">0</span>
            </div>
            <?php endif; ?>
            <div style="font-size: 18px; color: #4CAF50; font-weight: 700;">
                <strong>Итого:</strong> <span id="mult_total_price">0 ₽</span>
            </div>
        </div>
        
        <input type="hidden" id="custom_mult_total_price" name="custom_mult_total_price" value="0">
        <input type="hidden" id="custom_mult_quantity" name="custom_mult_quantity" value="1">
        <input type="hidden" id="custom_mult_pack_area" value="<?php echo esc_attr($area_data); ?>">
        <input type="hidden" id="custom_mult_base_price" value="<?php echo esc_attr($price); ?>">
    </div>
    <?php
}

/**
 * Блок услуг покраски
 */
function display_painting_services($product_id) {
    if (!function_exists('get_acf_painting_services')) {
        return;
    }
    
    $services = get_acf_painting_services($product_id);
    
    if (empty($services)) {
        return;
    }
    
    ?>
    <div id="painting-services-block" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background: #fff9e6;">
        <h4 style="margin-top: 0;">Услуги покраски</h4>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Выберите услугу:</label>
            <select id="painting_service_select" name="painting_service_key"
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Без покраски</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr($service['id']); ?>"
                            data-price="<?php echo esc_attr($service['price']); ?>">
                        <?php echo esc_html($service['title']); ?> 
                        (+<?php echo wc_price($service['price']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="painting_price_display" style="display: none; padding: 10px; background: #e8f4f8; border-radius: 4px; margin-top: 10px;">
            <strong>Стоимость покраски:</strong> <span id="painting_price_value">0 ₽</span>
        </div>
    </div>
    <?php
}