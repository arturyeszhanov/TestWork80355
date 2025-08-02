<?php

/**
 * Обновляет кэш погоды для пакета городов.
 *
 * @param int $batch_size Количество городов в одном батче.
 * @param int $offset Смещение от начала списка.
 * @return array Статистика выполнения: смещение, обработано, осталось.
 * @return array{offset: int, processed: int, remaining: int}
 */
    function update_weather_cache_batch($batch_size = 20, $offset = 0) {
        // Проверка наличия API-ключа и функции получения городов
        if (!defined('OPENWEATHERMAP_API_KEY') || !function_exists('get_cached_cities_data')) {
            return;
    }

    $cities = get_cached_cities_data();
    $total = count($cities);

    // Получаем текущий пакет городов
    $batch = array_slice($cities, $offset, $batch_size);

    foreach ($batch as $city) {
        $lat = $city['lat'] ?? null;
        $lon = $city['lon'] ?? null;

        // Пропускаем, если координаты отсутствуют
        if (!$lat || !$lon) {
            continue;
        }

        $cache_key = get_weather_cache_key($lat, $lon);

        // Всегда обновляем данные, независимо от существующего кэша

        $weather_data = fetch_weather_data($lat, $lon);

        // Сохраняем в кэш, если данные получены
        if ($weather_data) {
            set_transient($cache_key, $weather_data, HOUR_IN_SECONDS);
        }

        // Пауза между запросами, чтобы не превышать лимиты API
        usleep(300000);
    }

    return [
        'offset'    => $offset,
        'processed' => count($batch),
        'remaining' => max(0, $total - ($offset + $batch_size)),
    ];
    
}

/**
 * Получает текущую температуру и описание погоды из кэша.
 *
 * @param float $lat Широта.
 * @param float $lon Долгота.
 * @return array|null Данные погоды или null, если в кэше нет.
 */
function get_temperature($lat, $lon) {
    $cache_key = get_weather_cache_key($lat, $lon);
    return get_transient($cache_key) ?: null;
}

/**
 * Генерирует ключ кэша погоды по координатам.
 *
 * @param float $lat
 * @param float $lon
 * @return string
 */
function get_weather_cache_key($lat, $lon) {
    return 'weather_temp_v7_' . md5("{$lat}_{$lon}");
}

/**
 * Получает данные погоды из OpenWeatherMap API.
 *
 * @param float $lat Широта.
 * @param float $lon Долгота.
 * @return array|null Ассоциативный массив с температурой, иконкой и описанием или null.
 */
function fetch_weather_data($lat, $lon) {
    $api_key = OPENWEATHERMAP_API_KEY;
    $url = sprintf(
        'https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&appid=%s&units=metric',
        $lat,
        $lon,
        $api_key
    );

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Проверяем наличие нужных данных
    if (!isset($data['main']['temp'], $data['weather'][0]['icon'], $data['weather'][0]['description'])) {
        return null;
    }

    return [
        'temp'        => round($data['main']['temp']),
        'icon'        => $data['weather'][0]['icon'],
        'description' => $data['weather'][0]['description'],
    ];
}
