<?php

namespace Lively\Admin;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Core\Utils\Environment;

class ThemeSupport
{
    /**
     * Initialize theme support features.
     *
     * Hooks into WordPress actions and filters to set up theme support and customization.
     *
     * @return void
     */
    public static function init()
    {
        add_action('init', [__CLASS__, 'registerThemeSupports']);
        add_action('init', [__CLASS__, 'hideWPVersion']);
        add_action('init', [__CLASS__, 'disableAutoUpdate']);
        add_action('admin_menu', [__CLASS__, 'customizeAdmin']);
        
        // Add block editor supports
        add_filter('block_type_metadata', [__CLASS__, 'addBlockSupports']);
        add_filter('block_editor_settings_all', [__CLASS__, 'addEditorSettings']);
        add_filter('register_block_type_args', [__CLASS__, 'addBlockTypeArgs'], 10, 2);

        // Allow custom uploads
        add_action('init', [__CLASS__, 'allowCustomUploads']);

        // Set the maximum upload size for the site from app.config.php
        add_action('init', [__CLASS__, 'maxUploadSize']);
    }
    
    /**
     * Set the maximum upload size for the site from app.config.php
     *
     * @return int The maximum upload size in bytes.
     */
    public static function maxUploadSize() {
        add_filter('upload_size_limit', function($size) use ($maxSize) {
            return Environment::get('upload.max_size');
        }, 20);
    }

    /**
     * Customize the WordPress admin area.
     *
     * Removes unnecessary admin bar elements and modifies the admin interface.
     *
     * @return void
     */
    public static function customizeAdmin() {
        add_action('admin_bar_menu', function ($wp_admin_bar) {
            $wp_admin_bar->remove_node('wp-logo');
        }, 999);
    }

    /**
     * Hide the WordPress version from the head and RSS feeds.
     *
     * Prevents the WordPress version from being exposed for security purposes.
     *
     * @return void
     */
    public static function hideWPVersion()
    {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_false');
    }

    /**
     * Disable automatic updates for plugins and themes.
     *
     * Ensures that automatic updates do not interfere with manual configurations.
     *
     * @return void
     */
    public static function disableAutoUpdate()
    {
        // disable auto-updates if mainwp is installed
        if (defined('MAINWP_CHILD_DIR')) {
            return;
        }

        // disable auto-updates for plugins
        add_filter('auto_update_plugin', '__return_false');
    }

    /**
     * Register theme support features.
     *
     * Adds support for various WordPress features such as post thumbnails, custom headers,
     * custom backgrounds, and block editor tools.
     *
     * @return void
     */
    public static function registerThemeSupports()
    {
        // Existing theme supports
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo', [
            'height'      => 60,
            'width'       => 200,
            'flex-height' => true,
            'flex-width'  => true,
        ]);
        add_theme_support('html5', [
            'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
        ]);
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('custom-background');

        // Editor and block supports
        add_theme_support('editor-styles');
        add_editor_style('assets/css/editor-style.css');
        add_theme_support('align-wide');
        add_theme_support('responsive-embeds');
        
        // New block editor supports
        add_theme_support('custom-spacing');
        add_theme_support('custom-units', ['px', 'em', 'rem', '%', 'vw', 'vh']);
        add_theme_support('custom-line-height');
        add_theme_support('appearance-tools');
        add_theme_support('link-color');
        add_theme_support('border');
        add_theme_support('typography');

        // Existing supports
        add_theme_support('post-formats', ['aside', 'gallery', 'quote', 'image', 'video']);
        if (class_exists('WooCommerce') && function_exists('WC')) {
            add_theme_support('woocommerce');

            // Register woocommerce ajax actions.
            add_action('wp_ajax_get_cart_count', function () {
                $count = WC()->cart->get_cart_contents_count();
                return wp_send_json(['count' => $count]);
            });
            
            add_action('wp_ajax_nopriv_get_cart_count', function () {
                $count = WC()->cart->get_cart_contents_count();
                return wp_send_json(['count' => $count]);
            });
            
            add_action('wp_enqueue_scripts', function () {
                wp_dequeue_script('wc-cart-fragments');
            }, 11);
        }
        add_theme_support('custom-header', [
            'width'       => 1200,
            'height'      => 600,
            'flex-width'  => true,
            'flex-height' => true,
            'default-text-color' => '000',
            'uploads' => true,
        ]);
    }   

    /**
     * Add support for block type metadata.
     *
     * Configures block type settings for spacing, margin, padding, and block gaps.
     *
     * @param array $metadata The metadata for the block type.
     * @return array The updated metadata with block supports added.
     */
    public static function addBlockSupports($metadata) 
    {
        if (!isset($metadata['supports'])) {
            $metadata['supports'] = [];
        }
        
        if (!isset($metadata['supports']['spacing'])) {
            $metadata['supports']['spacing'] = [
                'margin' => true,
                'padding' => true,
                'blockGap' => true
            ];
        }
        
        return $metadata;
    }

    /**
     * Add editor settings for the block editor.
     *
     * Configures editor settings for spacing units, presets, and additional tools.
     *
     * @param array $settings The current editor settings.
     * @return array The updated editor settings.
     */
    public static function addEditorSettings($settings) 
    {
        // Add spacing settings
        $settings['spacing'] = [
            'units' => ['px', 'em', 'rem', '%', 'vw', 'vh'],
            'padding' => true,
            'margin' => true,
        ];
        
        // Add spacing presets
        $settings['spacingSizes'] = [
            [
                'name' => 'Small',
                'slug' => 'small',
                'size' => '1rem',
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'size' => '2rem',
            ],
            [
                'name' => 'Large',
                'slug' => 'large',
                'size' => '4rem',
            ],
            [
                'name' => 'Extra Large',
                'slug' => 'x-large',
                'size' => '8rem',
            ],
        ];
        
        return $settings;
    }

    /**
     * Add block type arguments for enhanced block support.
     *
     * Configures block type arguments for color, typography, and spacing features.
     *
     * @param array $args The current block type arguments.
     * @param string $block_name The name of the block being registered.
     * @return array The updated block type arguments.
     */
    public static function addBlockTypeArgs($args, $block_name) 
    {
        if (!isset($args['supports'])) {
            $args['supports'] = [];
        }

        // Add spacing support
        if (!isset($args['supports']['spacing'])) {
            $args['supports']['spacing'] = [
                'margin' => true,
                'padding' => true,
                'blockGap' => true
            ];
        }

        // Add color support
        if (!isset($args['supports']['color'])) {
            $args['supports']['color'] = [
                'background' => true,
                'text' => true,
                'link' => true,
                'gradients' => true,
            ];
        }

        // Add typography support
        if (!isset($args['supports']['typography'])) {
            $args['supports']['typography'] = [
                'fontSize' => true,
                'lineHeight' => true,
            ];
        }

        return $args;
    }

    /**
     * Allow custom uploads
     */
    public static function allowCustomUploads()
    {
        add_filter("upload_mimes", function ($mimes) {
            $mimes["svg"] = "image/svg+xml";
            return $mimes;
        });
    }
}