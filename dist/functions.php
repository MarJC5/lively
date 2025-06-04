<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

// Constants
define('LIVELY_THEME_VERSION', '1.0.0');
define('LIVELY_THEME_DIR', __DIR__);
define('LIVELY_THEME_URL', get_stylesheet_directory_uri());
define('LIVELY_RESOURCES_DIR', __DIR__ . '/resources');
define('LIVELY_THEME_DOMAIN', 'lively');

// Include autoloader
require_once __DIR__ . '/includes/core/utils/Autoloader.php';

// Include scripts
require_once __DIR__ . '/config/enqueue.php';

class Lively
{
    /**
     * Initialize the plugin
     */
    public static function init()
    {
        /**
         * Initialize the autoloader
         */
        $autoloader = new \Lively\Core\Utils\Autoloader();
        $autoloader->register()->registerFrameworkNamespaces(LIVELY_THEME_DIR);

        /**
         * Initialize the Lively framework
         */
        \Lively\Core\Engine::init();

        /**
         * Initialize the SEO
         */
        \Lively\SEO\JsonLD::init();

        /**
         * Initialize the theme support
         */
        \Lively\Admin\ThemeSupport::init();

        /**
         * Initialize the media
         */
        \Lively\Media\Size::get_instance()->init();

        /**
         * Initialize the post types
         */
        \Lively\Admin\PostTypeRegistry::init();
    }
}

// Initialize early
add_action('after_setup_theme', [Lively::class, 'init']);