<?php
/**
 * Виджет погоды для выбранного города
 *
 * Отображает текущую температуру, иконку и описание погоды
 * на основе координат, заданных у кастомного поста "Город".
 * В админке реализован выбор города: сначала показываются страны (таксономия 'countries') по алфавиту,
 * внутри каждой — города (post_type 'cities') также по алфавиту.
 *
 * @package WordPress
 */

class Weather_Widget extends WP_Widget {

    /**
     * Конструктор виджета
     */
    public function __construct() {
        parent::__construct(
            'weather_widget',
            'City Weather Widget',
            ['description' => 'Отображает текущую температуру для выбранного города.']
        );
    }

    /**
     * Вывод виджета на фронтенде
     *
     * @param array $args Аргументы вывода.
     * @param array $instance Настройки виджета.
     */
    public function widget($args, $instance) {
        $city_id = !empty($instance['city_id']) ? intval($instance['city_id']) : 0;

        echo $args['before_widget'];

        if ($city_id) {
            $city = get_post($city_id);
            $lat = get_post_meta($city_id, '_city_latitude', true);
            $lon = get_post_meta($city_id, '_city_longitude', true);

            echo '<div class="weather-widget-wrapper">';

            if (!empty($city->post_title)) {
                echo '<h2 class="weather-widget-title">' . esc_html($city->post_title) . '</h2>';
            }

            if (is_numeric($lat) && is_numeric($lon)) {
                $weather = get_temperature($lat, $lon);

                if (!empty($weather) && is_array($weather)) {
                    $temp = esc_html($weather['temp']);
                    $icon = esc_attr($weather['icon']);
                    $desc = esc_html(ucfirst($weather['description']));
                    $icon_url = esc_url("https://openweathermap.org/img/wn/{$icon}@2x.png");

                    echo '<div class="weather-widget">';
                    echo '  <img class="weather-icon" src="' . $icon_url . '" alt="' . $desc . '">';
                    echo '  <div class="weather-info">';
                    echo '      <p class="weather-temp">' . $temp . ' °C</p>';
                    echo '      <p class="weather-desc">' . $desc . '</p>';
                    echo '  </div>';
                    echo '</div>';
                } else {
                    echo '<p class="weather-error">Не удалось получить данные о погоде.</p>';
                }
            } else {
                echo '<p class="weather-error">Координаты отсутствуют.</p>';
            }

            echo '</div>';
        } else {
            echo '<p class="weather-error">Город не выбран.</p>';
        }

        echo $args['after_widget'];
    }

    /**
     * Форма настройки в админке
     *
     * Выводит список стран и вложенных городов по алфавиту
     *
     * @param array $instance Текущие настройки виджета.
     */
    public function form($instance) {
        $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';

        // Получаем список всех стран (таксономия 'countries'), отсортированных по имени
        $countries = get_terms([
            'taxonomy' => 'countries',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('city_id'); ?>">Выберите город:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('city_id'); ?>"
                    name="<?php echo $this->get_field_name('city_id'); ?>">
                <option value="">— Выбрать —</option>

                <?php foreach ($countries as $country): ?>
                    <?php
                    // Получаем города, относящиеся к стране
                    $cities = get_posts([
                        'post_type' => 'cities',
                        'numberposts' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'tax_query' => [[
                            'taxonomy' => 'countries',
                            'field' => 'term_id',
                            'terms' => $country->term_id,
                        ]],
                    ]);

                    if (empty($cities)) continue;
                    ?>
                    <optgroup label="<?php echo esc_attr($country->name); ?>">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo esc_attr($city->ID); ?>" <?php selected($city_id, $city->ID); ?>>
                                <?php echo esc_html($city->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </p>

        <?php
    }

    /**
     * Сохранение настроек виджета
     *
     * @param array $new_instance Новые значения.
     * @param array $old_instance Старые значения.
     * @return array Обновлённые значения.
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['city_id'] = !empty($new_instance['city_id']) ? intval($new_instance['city_id']) : '';
        return $instance;
    }
}

/**
 * Регистрация виджета
 */
add_action('widgets_init', function () {
    register_widget('Weather_Widget');
});
