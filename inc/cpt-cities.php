<?php
/**
 * @package Storefront_Child
 * 
 * Регистрирует пользовательский тип записи "Cities"
 */

defined( 'ABSPATH' ) || exit;

add_action('init', 'storefront_child_register_cpt_cities');

function storefront_child_register_cpt_cities() {
    $labels = [
        'name'                  => __('Cities', 'storefront-child'),
        'singular_name'         => __('City', 'storefront-child'),
        'menu_name'             => __('Cities', 'storefront-child'),
        'name_admin_bar'        => __('City', 'storefront-child'),
        'add_new'               => __('Add New', 'storefront-child'),
        'add_new_item'          => __('Add New City', 'storefront-child'),
        'edit_item'             => __('Edit City', 'storefront-child'),
        'new_item'              => __('New City', 'storefront-child'),
        'view_item'             => __('View City', 'storefront-child'),
        'search_items'          => __('Search Cities', 'storefront-child'),
        'not_found'             => __('No cities found', 'storefront-child'),
        'not_found_in_trash'    => __('No cities found in Trash', 'storefront-child'),
        'all_items'             => __('All Cities', 'storefront-child'),
    ];    

    $args = [
        'label'               => 'Cities',
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'cities'],
        'menu_icon'           => 'dashicons-location-alt',
        'supports'            => ['title'],
        'show_in_rest'        => false,     // Отключено: REST API и Gutenberg не используются
        'hierarchical'        => false,
    ];

    register_post_type('cities', $args);
}
