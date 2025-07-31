<?php
/**
 * @package Storefront_Child
 * 
 * Регистрирует пользовательскую таксономию 'Countries' для типа записи 'Cities'.
 */
 
defined( 'ABSPATH' ) || exit;

function storefront_child_register_taxonomy_countries() {
    $labels = [
        'name'              => __('Countries', 'storefront-child'),
        'singular_name'     => __('Country', 'storefront-child'),
        'search_items'      => __('Search Countries', 'storefront-child'),
        'all_items'         => __('All Countries', 'storefront-child'),
        'parent_item'       => __('Parent Country', 'storefront-child'),
        'parent_item_colon' => __('Parent Country:', 'storefront-child'),
        'edit_item'         => __('Edit Country', 'storefront-child'),
        'update_item'       => __('Update Country', 'storefront-child'),
        'add_new_item'      => __('Add New Country', 'storefront-child'),
        'new_item_name'     => __('New Country Name', 'storefront-child'),
        'menu_name'         => __('Countries', 'storefront-child'),
    ];    

    $args = [
        'labels'            => $labels,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'countries'],
        'show_in_rest'      => false,   // Отключено: REST API и Gutenberg не используются
    ];

    register_taxonomy('countries', ['cities'], $args);
}

add_action('init', 'storefront_child_register_taxonomy_countries');