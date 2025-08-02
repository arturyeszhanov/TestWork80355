<?php
/**
 * Template Name: Cities Weather Table
 *
 * Шаблон страницы для отображения таблицы городов с температурой.
 */

get_header();

/**
 * Хук перед таблицей.
 * Выведена строка поиска, количество записей.
 */
do_action('cities_table_before');
?>

<div class="cities-table-container">
    <!-- Таблица с данными -->
    <table id="cities-table" class="cities-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Country</th>
                <th>City</th>
                <th>Temperature (°C)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4"></td>
            </tr>
        </tbody>
    </table>
</div>

<?php
/**
 * Хук после таблицы.
 * Используется для вывода пагинации.
 */
do_action('cities_table_after');

get_footer();
