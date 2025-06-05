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

/**
 * Register Project post type
 */
PostTypeRegistry::register('project', [
    'menu_icon' => 'dashicons-feedback',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'projects'],
], false)
->taxonomy('project_category', 'project', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'project-category'],
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
]);

/**
 * Register Team post type
 */
PostTypeRegistry::register('team', [
    'menu_icon' => 'dashicons-groups',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'teams'],
], false)
->taxonomy('team_category', 'team', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'team-category'],
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
]);

/**
 * Register Testimonial post type
 */
PostTypeRegistry::register('testimonial', [
    'menu_icon' => 'dashicons-testimonial',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'testimonials'],
], false)
->taxonomy('testimonial_category', 'testimonial', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'testimonial-category'],
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
]);

/**
 * Register Service post type
 */
PostTypeRegistry::register('service', [
    'menu_icon' => 'dashicons-hammer',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'services'],
], false)
->taxonomy('service_category', 'service', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'service-category'],
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
]);

/**
 * Register FAQ post type
 */
PostTypeRegistry::register('faq', [
    'menu_icon' => 'dashicons-help',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'faqs'],
], false)
->taxonomy('faq_category', 'faq', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'faq-category'],
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
]);

/**
 * Register Gallery post type
 */
PostTypeRegistry::register('gallery', [
    'menu_icon' => 'dashicons-gallery',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'galleries'],
], false)
->taxonomy('gallery_category', 'gallery', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'gallery-category'],
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
]);

/**
 * Register Resources post type
 */
PostTypeRegistry::register('resource', [
    'menu_icon' => 'dashicons-document',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    'rewrite' => ['slug' => 'resources'],
], false)
->taxonomy('resource_category', 'resource', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'resource-category'],
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
]);