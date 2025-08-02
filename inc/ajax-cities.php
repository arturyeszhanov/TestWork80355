<?php
/**
 * AJAX-обработчик для загрузки и фильтрации списка городов с погодой
 *
 * Этот файл реализует серверную часть для AJAX-запросов:
 * - Получение списка городов из базы данных (тип поста: 'cities')
 * - Фильтрация по названию города
 * - Пагинация
 * - Получение погоды по координатам (lat/lon)
 * - Кэширование списка городов через Transient API
 * - Автоматический сброс кэша при изменении записей
 *
 * Поддерживает как авторизованных, так и неавторизованных пользователей.
 *
 * Хуки:
 * - wp_ajax_load_cities
 * - wp_ajax_nopriv_load_cities
 * - save_post_cities
 * - deleted_post
 * - trashed_post
 * - clear_cities_cache_event
 *
 * Безопасность:
 * - Используется check_ajax_referer для верификации nonce
 * - Все входные и выходные данные проходят очистку/экранирование
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Регистрируем AJAX-хендлеры (для авторизованных и неавторизованных пользователей)
add_action('wp_ajax_load_cities', 'ajax_load_cities');
add_action('wp_ajax_nopriv_load_cities', 'ajax_load_cities');

/**
 * Основной AJAX-обработчик загрузки городов
 *
 * Получает список городов, применяет фильтрацию, сортировку и пагинацию.
 * Также добавляет информацию о погоде по координатам.
 */
function ajax_load_cities() {
    check_ajax_referer('load_cities_nonce', 'security');

    // Получаем и очищаем входные параметры
    $search = sanitize_text_field($_POST['search'] ?? '');
    $page = max(1, intval($_POST['page'] ?? 1));
    $per_page = max(1, intval($_POST['per_page'] ?? 10));

    // Получаем закэшированные данные
    $all_cities = get_cached_cities_data();

    // Фильтрация по названию города
    if (!empty($search)) {
        $filtered = array_filter($all_cities, function ($city) use ($search) {
            return stripos($city['city'], $search) !== false;
        });
    } else {
        $filtered = $all_cities;
    }

    // Сортировка по алфавиту (по названию города)
    usort($filtered, fn($a, $b) => strcmp($a['city'], $b['city']));

    // Пагинация
    $total_items = count($filtered);
    $offset = ($page - 1) * $per_page;
    $paged_data = array_slice($filtered, $offset, $per_page);

    $data = [];

    foreach ($paged_data as $row) {
        $lat = $row['lat'];
        $lon = $row['lon'];

        // Получение погоды по координатам
        $weather = ($lat && $lon) ? get_temperature($lat, $lon) : null;

        if ($weather && isset($weather['temp'], $weather['icon'], $weather['description'])) {
            // Генерация HTML-блока для отображения температуры и иконки
            $temperature_html = '<div style="display: flex; align-items: center; gap: 10px; font-weight: 600;">';
            $temperature_html .= '<img src="https://openweathermap.org/img/wn/' . esc_attr($weather['icon']) . '@2x.png" ';
            $temperature_html .= 'alt="' . esc_attr($weather['description']) . '" ';
            $temperature_html .= 'title="' . esc_attr($weather['description']) . '" ';
            $temperature_html .= 'style="width: 60px; height: 60px; flex-shrink: 0;" />';
            $temperature_html .= '<div style="display: flex; flex-direction: column;">';
            $temperature_html .= '<span style="font-weight: bold; font-size: 16px;">' . esc_html($weather['temp']) . ' °C</span>';
            $temperature_html .= '<span style="color: #666; font-size: 14px;">' . esc_html(ucfirst($weather['description'])) . '</span>';
            $temperature_html .= '</div>';
            $temperature_html .= '</div>';
        } else {
            $temperature_html = '—';
        }

        // Подготовка строки данных
        $data[] = [
            'country'     => esc_html($row['country'] ?? '—'),
            'city'        => esc_html($row['city'] ?? '—'),
            'temperature' => $temperature_html,
        ];
    }

    // Отправка ответа клиенту
    wp_send_json_success([
        'cities'      => $data,
        'total_items' => $total_items,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => ceil($total_items / $per_page),
    ]);
}

/**
 * Получение и кэширование всех городов из базы данных
 *
 * Кэш хранится 1 неделю через Transient API
 *
 * @return array
 */
function get_cached_cities_data() {
    $transient_key = 'cached_all_cities';
    $cities = get_transient($transient_key);

    if ($cities === false) {
        global $wpdb;

        // SQL-запрос к базе данных WordPress
        $query = "
            SELECT 
                p.ID, 
                p.post_title AS city, 
                t.name AS country,
                pm_lat.meta_value AS lat,
                pm_lon.meta_value AS lon
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}term_relationships tr 
                ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->prefix}term_taxonomy tt 
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->prefix}terms t 
                ON tt.term_id = t.term_id AND tt.taxonomy = 'countries'
            LEFT JOIN {$wpdb->prefix}postmeta pm_lat 
                ON pm_lat.post_id = p.ID AND pm_lat.meta_key = '_city_latitude'
            LEFT JOIN {$wpdb->prefix}postmeta pm_lon 
                ON pm_lon.post_id = p.ID AND pm_lon.meta_key = '_city_longitude'
            WHERE p.post_type = 'cities' 
            AND p.post_status = 'publish'
        ";

        $results = $wpdb->get_results($query);

        $cities = [];

        // Преобразуем результаты в массив
        foreach ($results as $row) {
            $cities[] = [
                'id'      => $row->ID,
                'city'    => $row->city,
                'country' => $row->country,
                'lat'     => $row->lat,
                'lon'     => $row->lon,
            ];
        }

        // Сохраняем в кэш на 1 неделю
        set_transient($transient_key, $cities, WEEK_IN_SECONDS);
    }

    return $cities;
}

/**
 * Планирует событие сброса кэша через 15 минут
 *
 * Используется при изменении, удалении или перемещении поста типа 'cities'
 */
function reschedule_cities_cache_invalidation() {
    // Отменить ранее запланированное событие (если есть)
    wp_clear_scheduled_hook('clear_cities_cache_event');

    // Запланировать новое через 15 минут
    wp_schedule_single_event(time() + 15 * MINUTE_IN_SECONDS, 'clear_cities_cache_event');
}

/**
 * Хук: сброс транзиента 'cached_all_cities'
 */
add_action('clear_cities_cache_event', function () {
    delete_transient('cached_all_cities');
    error_log('[CRON] Транзиент cached_all_cities сброшен.');
});

/**
 * Хук: при сохранении поста типа 'cities' — планируем сброс кэша
 */
add_action('save_post_cities', 'reschedule_cities_cache_invalidation');

/**
 * Хук: при удалении поста — проверка типа и планирование сброса кэша
 */
add_action('deleted_post', function ($post_id) {
    if (get_post_type($post_id) === 'cities') {
        reschedule_cities_cache_invalidation();
    }
});

/**
 * Хук: при перемещении поста в корзину — проверка типа и планирование сброса кэша
 */
add_action('trashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'cities') {
        reschedule_cities_cache_invalidation();
    }
});
