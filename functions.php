<?php
/**
 * Storefront Child Theme Functions
 * 
 * - Подключение стилей и скриптов
 * - Регистрация хуков
 * - Обработка AJAX-запросов
 * - Подключение модулей (inc/*.php)
 */

// ================================
//  Подключение модулей из /inc
// ================================
require_once get_stylesheet_directory() . '/inc/cpt-cities.php';
require_once get_stylesheet_directory() . '/inc/taxonomy-countries.php';
require_once get_stylesheet_directory() . '/inc/ajax-cities.php';
require_once get_stylesheet_directory() . '/inc/metabox-city-coordinates.php';
require_once get_stylesheet_directory() . '/inc/weather-cache-batch-updater.php';
require_once get_stylesheet_directory() . '/inc/widget-weather.php';

// =======================================
//  Подключение стилей и скриптов темы
// =======================================
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_assets');

/**
 * Подключает стили и скрипты дочерней темы
 */
function storefront_child_enqueue_assets() {
    // Родительская тема
    wp_enqueue_style('storefront-style', get_template_directory_uri() . '/style.css');

    // Стили дочерней темы
    wp_enqueue_style('storefront-child-style', get_stylesheet_directory_uri() . '/style.css', ['storefront-style']);

    // Дополнительные стили для таблицы городов и виджета погоды
    wp_enqueue_style('cities-weather-style', get_stylesheet_directory_uri() . '/assets/css/cities-weather.css', ['storefront-child-style'], '1.0.0');

    // Скрипт для AJAX-поиска городов
    wp_enqueue_script(
        'cities-ajax',
        get_stylesheet_directory_uri() . '/assets/js/cities-ajax.js',
        ['jquery'],
        time(),
        true
    );

    // Данные для скрипта
    wp_localize_script('cities-ajax', 'cities_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('load_cities_nonce'),
    ]);
}

// ==================================
//  Вывод HTML перед/после таблицы
// ==================================
add_action('cities_table_before', 'cities_table_before_html');
add_action('cities_table_after', 'cities_table_after_html');

/**
 * Вывод HTML перед таблицей городов
 */
function cities_table_before_html() {
    ?>
    <div class="cities-table-header">
        <h2 class="cities-table-title">City Weather Table</h2>
        <p class="cities-table-subtitle">Search for a city and see current temperatures.</p>
    </div>

    <div class="cities-table-toolbar">
        <input type="text" id="city-search" class="city-search" placeholder="Search city..." />
        <div id="cities-count" class="cities-count">Total: 0 records</div>
    </div>
    <?php
}

/**
 * Вывод HTML после таблицы городов
 */
function cities_table_after_html() {
    echo '<div id="cities-pagination"></div>';
}

// =====================================================
//  Ручной запуск обновления погоды (cron-job.org и др.)
// =====================================================
add_action('init', 'handle_manual_weather_update');

/**
 * Обработка обновления погоды через URL
 * cron-job.org по секретному ключю
 */
function handle_manual_weather_update() {
    if (
        isset($_GET['force_weather_update']) &&
        isset($_GET['secret']) &&
        defined('WEATHER_UPDATE_SECRET') &&
        $_GET['secret'] === WEATHER_UPDATE_SECRET
    )
    {
        if (!function_exists('get_cached_cities_data')) {
            wp_die('Missing get_cached_cities_data()');
        }

        $batch_size = 20;
        $cities = get_cached_cities_data();
        $total = count($cities);
        $total_batches = ceil($total / $batch_size);

        $slot = floor(time() / 60) % $total_batches;
        $offset = $slot * $batch_size;

        $result = update_weather_cache_batch($batch_size, $offset);

        // Возврат результата (опционально)
        wp_die('Batch updated: ' . count($result) . ' cities');
    }
}
