<?php

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Admin\PostTypeRegistry;

/**
 * Register Product post type
 */
PostTypeRegistry::register('product', [
    'menu_icon' => 'dashicons-cart',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'products'],
], false)
->taxonomy('product_category', 'product', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'product-category'],
    'labels' => [
        'name' => __('Categories'),
        'singular_name' => __('Category'),
        'menu_name' => __('Categories'),
        'all_items' => __('All Categories'),
        'edit_item' => __('Edit Category'),
        'view_item' => __('View Category'),
        'update_item' => __('Update Category'),
        'add_new_item' => __('Add New Category'),
        'new_item_name' => __('New Category Name'),
        'search_items' => __('Search Categories'),
        'parent_item' => __('Parent Category'),
        'parent_item_colon' => __('Parent Category:'),
        'not_found' => __('No Categories found.'),
    ],
])
->taxonomy('product_tag', 'product', [
    'hierarchical' => false,
    'rewrite' => ['slug' => 'product-tag'],
    'labels' => [
        'name' => __('Tags'),
        'singular_name' => __('Tag'),
        'menu_name' => __('Tags'),
        'all_items' => __('All Tags'),
        'edit_item' => __('Edit Tag'),
        'view_item' => __('View Tag'),
        'update_item' => __('Update Tag'),
        'add_new_item' => __('Add New Tag'),
        'new_item_name' => __('New Tag Name'),
        'search_items' => __('Search Tags'),
        'not_found' => __('No Tags found.'),
    ],
]);