/**
 * ParusWeb Calculators JavaScript
 * Обработчики для всех калькуляторов
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('ParusWeb Calculators loaded');
    
    // ============================================
    // КАЛЬКУЛЯТОР КВАДРАТНЫХ МЕТРОВ
    // ============================================
    
    $('#custom_sq_width, #custom_sq_length').on('input', function() {
        calculateSquareMeters();
    });
    
    function calculateSquareMeters() {
        const width = parseFloat($('#custom_sq_width').val()) || 0;
        const length = parseFloat($('#custom_sq_length').val()) || 0;
        
        if (width <= 0 || length <= 0) {
            $('#sq_calc_result').hide();
            return;
        }
        
        const area = width * length;
        const basePrice = parseFloat($('#custom_sq_base_price').val()) || 0;
        const packArea = parseFloat($('#custom_sq_pack_area').val()) || 0;
        
        let totalPrice = area * basePrice;
        let packs = 1;
        
        if (packArea > 0) {
            packs = Math.ceil(area / packArea);
            totalPrice = packs * packArea * basePrice;
        }
        
        // Добавляем покраску если выбрана
        const paintingPrice = parseFloat($('#painting_service_select option:selected').data('price')) || 0;
        if (paintingPrice > 0) {
            totalPrice += (paintingPrice * area);
        }
        
        // Обновляем отображение
        $('#sq_total_area').text(area.toFixed(2));
        if (packArea > 0) {
            $('#sq_packs_needed').text(packs);
        }
        $('#sq_total_price').text(formatPrice(totalPrice));
        $('#custom_sq_total_price').val(totalPrice);
        $('#custom_sq_quantity').val(packs);
        
        $('#sq_calc_result').show();
        
        // Обновляем количество WooCommerce
        $('input.qty').val(packs).prop('readonly', true);
    }
    
    // ============================================
    // КАЛЬКУЛЯТОР ПОГОННЫХ МЕТРОВ
    // ============================================
    
    $('#custom_rm_length').on('input', function() {
        calculateRunningMeters();
    });
    
    function calculateRunningMeters() {
        const length = parseFloat($('#custom_rm_length').val()) || 0;
        
        if (length <= 0) {
            $('#rm_calc_result').hide();
            return;
        }
        
        const basePrice = parseFloat($('#custom_rm_base_price').val()) || 0;
        let totalPrice = length * basePrice;
        
        // Добавляем покраску
        const paintingPrice = parseFloat($('#painting_service_select option:selected').data('price')) || 0;
        if (paintingPrice > 0) {
            totalPrice += (paintingPrice * length);
        }
        
        $('#rm_total_length').text(length.toFixed(2));
        $('#rm_total_price').text(formatPrice(totalPrice));
        $('#custom_rm_total_price').val(totalPrice);
        $('#custom_rm_quantity').val(length);
        
        $('#rm_calc_result').show();
        
        $('input.qty').val(length.toFixed(2)).prop('readonly', true);
    }
    
    // ============================================
    // КАЛЬКУЛЯТОР ФАЛЬШБАЛОК
    // ============================================
    
    $('#falsebalk_shape').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const config = selectedOption.data('config');
        
        if (!config) {
            $('#falsebalk_dimensions').hide();
            $('#falsebalk_result').hide();
            return;
        }
        
        // Заполняем ширину
        const $widthSelect = $('#falsebalk_width').empty();
        for (let w = config.width_min; w <= config.width_max; w += config.width_step) {
            $widthSelect.append(`<option value="${w}">${w} мм</option>`);
        }
        
        // Заполняем высоту
        const $heightSelect = $('#falsebalk_height').empty();
        for (let h = config.height_min; h <= config.height_max; h += config.height_step) {
            $heightSelect.append(`<option value="${h}">${h} мм</option>`);
        }
        
        $('#falsebalk_shape_label').val(selectedOption.text());
        $('#falsebalk_dimensions').show();
    });
    
    $('#falsebalk_width, #falsebalk_height, #falsebalk_length').on('change input', function() {
        calculateFalsebalk();
    });
    
    function calculateFalsebalk() {
        const length = parseFloat($('#falsebalk_length').val()) || 0;
        
        if (length <= 0) {
            $('#falsebalk_result').hide();
            return;
        }
        
        const basePrice = parseFloat($('#custom_rm_base_price').val()) || 0;
        let totalPrice = length * basePrice;
        
        // Добавляем покраску
        const paintingPrice = parseFloat($('#painting_service_select option:selected').data('price')) || 0;
        if (paintingPrice > 0) {
            totalPrice += (paintingPrice * length);
        }
        
        $('#falsebalk_total_length').text(length.toFixed(2));
        $('#falsebalk_total_price').text(formatPrice(totalPrice));
        $('#custom_rm_total_price').val(totalPrice);
        $('#custom_rm_length').val(length);
        $('#custom_rm_quantity').val(length);
        
        $('#falsebalk_result').show();
        
        $('input.qty').val(length.toFixed(2)).prop('readonly', true);
    }
    
    // ============================================
    // КАЛЬКУЛЯТОР РАЗМЕРОВ (с множителем)
    // ============================================
    
    $('#custom_mult_width, #custom_mult_length').on('input', function() {
        calculateMultiplier();
    });
    
    function calculateMultiplier() {
        const width = parseFloat($('#custom_mult_width').val()) || 0;
        const length = parseFloat($('#custom_mult_length').val()) || 0;
        
        if (width <= 0 || length <= 0) {
            $('#mult_calc_result').hide();
            return;
        }
        
        const area = width * length;
        const basePrice = parseFloat($('#custom_mult_base_price').val()) || 0;
        const packArea = parseFloat($('#custom_mult_pack_area').val()) || 0;
        
        let totalPrice = area * basePrice;
        let packs = 1;
        
        if (packArea > 0) {
            packs = Math.ceil(area / packArea);
            totalPrice = packs * packArea * basePrice;
        }
        
        // Добавляем покраску
        const paintingPrice = parseFloat($('#painting_service_select option:selected').data('price')) || 0;
        if (paintingPrice > 0) {
            totalPrice += (paintingPrice * area);
        }
        
        $('#mult_total_area').text(area.toFixed(2));
        if (packArea > 0) {
            $('#mult_packs_needed').text(packs);
        }
        $('#mult_total_price').text(formatPrice(totalPrice));
        $('#custom_mult_total_price').val(totalPrice);
        $('#custom_mult_quantity').val(packs);
        
        $('#mult_calc_result').show();
        
        $('input.qty').val(packs).prop('readonly', true);
    }
    
    // ============================================
    // УСЛУГИ ПОКРАСКИ
    // ============================================
    
    $('#painting_service_select').on('change', function() {
        const price = parseFloat($(this).find('option:selected').data('price')) || 0;
        
        if (price > 0) {
            $('#painting_price_value').text(formatPrice(price));
            $('#painting_price_display').show();
        } else {
            $('#painting_price_display').hide();
        }
        
        // Пересчитываем активный калькулятор
        if ($('#sq_calc_result').is(':visible')) {
            calculateSquareMeters();
        } else if ($('#rm_calc_result').is(':visible')) {
            calculateRunningMeters();
        } else if ($('#falsebalk_result').is(':visible')) {
            calculateFalsebalk();
        } else if ($('#mult_calc_result').is(':visible')) {
            calculateMultiplier();
        }
    });
    
    // ============================================
    // ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // ============================================
    
    function formatPrice(price) {
        return Math.round(price).toLocaleString('ru-RU') + ' ₽';
    }
    
    // Валидация формы перед отправкой
    $('form.cart').on('submit', function(e) {
        let hasCalcData = false;
        
        // Проверяем заполнен ли хоть один калькулятор
        if ($('#sq_calc_result').is(':visible') && $('#custom_sq_total_price').val() > 0) {
            hasCalcData = true;
        }
        if ($('#rm_calc_result').is(':visible') && $('#custom_rm_total_price').val() > 0) {
            hasCalcData = true;
        }
        if ($('#falsebalk_result').is(':visible') && $('#custom_rm_total_price').val() > 0) {
            hasCalcData = true;
        }
        if ($('#mult_calc_result').is(':visible') && $('#custom_mult_total_price').val() > 0) {
            hasCalcData = true;
        }
        
        // Если есть калькулятор но данных нет - останавливаем
        if ($('.parusweb-calculator').length > 0 && !hasCalcData) {
            e.preventDefault();
            alert('Пожалуйста, заполните калькулятор');
            return false;
        }
    });
    
    console.log('ParusWeb Calculators initialized');
});