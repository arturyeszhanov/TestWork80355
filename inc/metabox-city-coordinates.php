<?php
/**
 * @package Storefront_Child
 * 
 * Регистрирует метабокс "Coordinates" для CPT "Cities"
 */

defined( 'ABSPATH' ) || exit;

function storefront_child_register_city_coordinates_metabox() {
    add_meta_box(
        'city_coordinates',
        __('Coordinates', 'storefront-child'),
        'storefront_child_render_city_coordinates_metabox',
        'cities',
        'normal',
        'default'
    );
}

add_action('add_meta_boxes', 'storefront_child_register_city_coordinates_metabox');

/**
 * Выводит HTML-форму метабокса
 *
 * @param WP_Post $post
 */
function storefront_child_render_city_coordinates_metabox($post) {
    $latitude = get_post_meta($post->ID, '_city_latitude', true);
    $longitude = get_post_meta($post->ID, '_city_longitude', true);

    wp_nonce_field('save_city_coordinates_action', 'city_coordinates_nonce');
    ?>
    <fieldset>
        <legend><?php esc_html_e('Enter the geographical coordinates of the city.', 'storefront-child'); ?></legend>

        <p>
            <label for="city_latitude"><?php esc_html_e('Latitude', 'storefront-child'); ?>:</label><br>
            <input 
                type="number" 
                step="any" 
                name="city_latitude" 
                id="city_latitude" 
                value="<?php echo esc_attr($latitude); ?>" 
                size="25" 
                placeholder="e.g. 51.5074">
        </p>

        <p>
            <label for="city_longitude"><?php esc_html_e('Longitude', 'storefront-child'); ?>:</label><br>
            <input 
                type="number" 
                step="any" 
                name="city_longitude" 
                id="city_longitude" 
                value="<?php echo esc_attr($longitude); ?>" 
                size="25" 
                placeholder="e.g. -0.1278">
        </p>
    </fieldset>
    <?php
}

/**
 * Сохраняет координаты при сохранении записи
 *
 * @param int $post_id
 */
function storefront_child_save_city_coordinates_meta($post_id) {
    // Проверка nonce
    if (
        !isset($_POST['city_coordinates_nonce']) ||
        !wp_verify_nonce($_POST['city_coordinates_nonce'], 'save_city_coordinates_action')
    ) {
        return;
    }

    // Пропускаем автосохранения
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Пропускаем, если это не CPT "cities"
    if (get_post_type($post_id) !== 'cities') {
        return;
    }

    // Проверка прав
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Сохраняем широту
    if (isset($_POST['city_latitude'])) {
        update_post_meta($post_id, '_city_latitude', floatval($_POST['city_latitude']));
    }

    // Сохраняем долготу
    if (isset($_POST['city_longitude'])) {
        update_post_meta($post_id, '_city_longitude', floatval($_POST['city_longitude']));
    }
}

add_action('save_post', 'storefront_child_save_city_coordinates_meta');