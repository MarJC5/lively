<?php

namespace Lively\Admin;

// Prevent direct access.
defined('ABSPATH') or exit;

class PostTypeRegistry
{
    /**
     * Initialize the post type registry
     */
    public static function init()
    {
        // Register post types early
        add_action('init', [self::class, 'register_post_types'], 0);
    }

    /**
     * Register all post types
     */
    public static function register_post_types()
    {
        // Load post type definitions
        require_once LIVELY_THEME_DIR . '/config/post-types.php';
    }

    /**
     * Register a new post type.
     *
     * @param string $postType
     * @param array $args
     * @return self
     */
    public static function register(string $postType, array $args = [], bool $register = true): self
    {
        $defaults = [
            'labels' => [],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-post',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'rewrite' => ['slug' => $postType],
            'show_in_rest' => true,
            'rest_base' => $postType,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];

        $args = array_merge($defaults, $args);
        $args['labels'] = self::getLabels($postType, $args['labels']);

        if ($register) {
            register_post_type($postType, $args);
        }

        return new self();
    }

    /**
     * Register a new taxonomy for a post type.
     *
     * @param string $taxonomy
     * @param string|array $postTypes
     * @param array $args
     * @return self
     */
    public function taxonomy(string $taxonomy, $postTypes, array $args = []): self
    {
        $defaults = [
            'labels' => [],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => $taxonomy],
            'show_in_rest' => true,
            'rest_base' => $taxonomy,
            'rest_controller_class' => 'WP_REST_Terms_Controller',
        ];

        $args = array_merge($defaults, $args);
        $args['labels'] = self::getTaxonomyLabels($taxonomy, $args['labels']);

        register_taxonomy($taxonomy, $postTypes, $args);
        return $this;
    }

    /**
     * Get labels for post type.
     *
     * @param string $postType
     * @param array $labels
     * @return array
     */
    protected static function getLabels(string $postType, array $labels = []): array
    {
        $singular = ucfirst($postType);
        $plural = $singular . 's';

        $defaults = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'name_admin_bar' => $singular,
            'add_new' => 'Add New',
            'add_new_item' => "Add New {$singular}",
            'new_item' => "New {$singular}",
            'edit_item' => "Edit {$singular}",
            'view_item' => "View {$singular}",
            'all_items' => "All {$plural}",
            'search_items' => "Search {$plural}",
            'parent_item_colon' => "Parent {$plural}:",
            'not_found' => "No {$plural} found.",
            'not_found_in_trash' => "No {$plural} found in Trash.",
            'featured_image' => "{$singular} Cover Image",
            'set_featured_image' => "Set cover image",
            'remove_featured_image' => "Remove cover image",
            'use_featured_image' => "Use as cover image",
            'archives' => "{$singular} Archives",
            'insert_into_item' => "Insert into {$singular}",
            'uploaded_to_this_item' => "Uploaded to this {$singular}",
            'filter_items_list' => "Filter {$plural} list",
            'items_list_navigation' => "{$plural} list navigation",
            'items_list' => "{$plural} list",
        ];

        return array_merge($defaults, $labels);
    }

    /**
     * Get labels for taxonomy.
     *
     * @param string $taxonomy
     * @param array $labels
     * @return array
     */
    protected static function getTaxonomyLabels(string $taxonomy, array $labels = []): array
    {
        $singular = ucfirst($taxonomy);
        $plural = $singular . 's';

        $defaults = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'all_items' => "All {$plural}",
            'edit_item' => "Edit {$singular}",
            'view_item' => "View {$singular}",
            'update_item' => "Update {$singular}",
            'add_new_item' => "Add New {$singular}",
            'new_item_name' => "New {$singular} Name",
            'parent_item' => "Parent {$singular}",
            'parent_item_colon' => "Parent {$singular}:",
            'search_items' => "Search {$plural}",
            'popular_items' => "Popular {$plural}",
            'separate_items_with_commas' => "Separate {$plural} with commas",
            'add_or_remove_items' => "Add or remove {$plural}",
            'choose_from_most_used' => "Choose from the most used {$plural}",
            'not_found' => "No {$plural} found.",
            'no_terms' => "No {$plural}",
            'filter_by_item' => "Filter by {$singular}",
            'items_list_navigation' => "{$plural} list navigation",
            'items_list' => "{$plural} list",
            'back_to_items' => "← Back to {$plural}",
            'item_link' => "{$singular} Link",
            'item_link_description' => "A link to a {$singular}",
        ];

        return array_merge($defaults, $labels);
    }
}