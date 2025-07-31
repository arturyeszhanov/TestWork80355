<?php
/**
 * Регистрирует пользовательский тип записи "Cities"
 */

add_action('init', 'register_cpt_cities');

function register_cpt_cities() {
    $labels = [
        'name'               => 'Cities',
        'singular_name'      => 'City',
        'menu_name'          => 'Cities',
        'name_admin_bar'     => 'City',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New City',
        'edit_item'          => 'Edit City',
        'new_item'           => 'New City',
        'view_item'          => 'View City',
        'search_items'       => 'Search Cities',
        'not_found'          => 'No cities found',
        'not_found_in_trash' => 'No cities found in Trash',
        'all_items'          => 'All Cities'
    ];

    $args = [
        'label'               => 'Cities',
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'cities'],
        'menu_icon'           => 'dashicons-location-alt',
        'supports'            => ['title'],
        'show_in_rest'        => false,
        'hierarchical'        => false,
    ];

    register_post_type('cities', $args);
}
