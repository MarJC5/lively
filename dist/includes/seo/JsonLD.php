<?php

namespace Lively\SEO;

// Prevent direct access.
defined('ABSPATH') or exit;

class JsonLD
{
    protected static $data = [];

    /**
     * Initialize the JSON-LD auto-detection and rendering.
     */
    public static function init()
    {
        // Hook into `wp` to determine the page type and add JSON-LD
        add_action('wp', [self::class, 'autoDetectAndAdd']);

        // Hook into `wp_head` to render the JSON-LD
        add_action('wp_head', [self::class, 'render']);
    }

    /**
     * Automatically detect the type of page being viewed and add the appropriate JSON-LD schema.
     *
     * Supports:
     * - Single posts
     * - Pages
     * - Products
     * - Archives
     * - Homepage
     * - Search results
     * - 404 pages
     */
    public static function autoDetectAndAdd()
    {
        if (is_singular('post')) {
            self::addPostSchema();
        } elseif (is_page()) {
            self::addPageSchema();
        } elseif (is_singular('product')) {
            self::addProductSchema();
        } elseif (is_archive()) {
            self::addArchiveSchema();
        } elseif (is_front_page() || is_home()) {
            self::addHomePageSchema();
        } elseif (is_search()) {
            self::addSearchSchema();
        } elseif (is_404()) {
            self::add404Schema();
        }
    }

    /**
     * Add JSON-LD structured data for single posts.
     *
     * Includes metadata such as the post title, publication date, author, and featured image.
     */
    protected static function addPostSchema()
    {
        $post = get_post();
        if (!$post) return;

        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'Article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => get_permalink($post),
            ],
            'headline' => get_the_title($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => [
                '@type' => 'Person',
                'name'  => get_the_author_meta('display_name', $post->post_author),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => self::getCustomLogoUrl(),
                ],
            ],
            'description' => get_the_excerpt($post),
            'image' => get_the_post_thumbnail_url($post, 'full'),
        ]);
    }

    /**
     * Add JSON-LD structured data for static pages.
     *
     * Includes metadata such as the page title, URL, and description.
     */
    protected static function addPageSchema()
    {
        $page = get_post();
        if (!$page) return;

        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'WebPage',
            'name'     => get_the_title($page),
            'url'      => get_permalink($page),
            'description' => get_the_excerpt($page),
        ]);
    }

    /**
     * Add JSON-LD structured data for WooCommerce products.
     *
     * Includes metadata such as the product name, price, SKU, and availability.
     */
    protected static function addProductSchema()
    {

        if (!function_exists('wc_get_product') || !function_exists('get_woocommerce_currency')) {
            return;
        }

        $product = wc_get_product(get_the_ID()); // WooCommerce helper
        if (!$product) return;

        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => $product->get_name(),
            'image'    => wp_get_attachment_url($product->get_image_id()),
            'description' => $product->get_description(),
            'sku'      => $product->get_sku(),
            'offers'   => [
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($product->get_id()),
            ],
        ]);
    }

    /**
     * Add JSON-LD structured data for archive pages.
     *
     * Includes metadata such as the archive name and description.
     */
    protected static function addArchiveSchema()
    {
        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'CollectionPage',
            'name'     => single_cat_title('', false),
            'url'      => get_term_link(get_queried_object_id()),
            'description' => category_description(),
        ]);
    }

    /**
     * Add JSON-LD structured data for the homepage.
     *
     * Includes metadata such as the site name, URL, and description.
     */
    protected static function addHomePageSchema()
    {
        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => get_bloginfo('name'),
            'url'      => home_url(),
            'description' => get_bloginfo('description'),
        ]);
    }

    /**
     * Add JSON-LD structured data for search results pages.
     *
     * Includes metadata such as the search query and URL.
     */
    protected static function addSearchSchema()
    {
        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'SearchResultsPage',
            'name'     => __('Search Results', LIVELY_THEME_DOMAIN),
            'url'      => home_url(add_query_arg(null, null)),
        ]);
    }

    /**
     * Add JSON-LD structured data for 404 error pages.
     *
     * Includes metadata such as the URL and a name indicating the error.
     */
    protected static function add404Schema()
    {
        self::add([
            '@context' => 'https://schema.org',
            '@type'    => 'WebPage',
            'name'     => __('404 Page Not Found', LIVELY_THEME_DOMAIN),
            'url'      => home_url($_SERVER['REQUEST_URI']),
        ]);
    }

    /**
     * Add a JSON-LD structured data block to the global output.
     *
     * @param array $context The structured data to add.
     */
    public static function add(array $context)
    {
        self::$data[] = $context;
    }

    /**
     * Render the JSON-LD structured data as a `<script>` tag in the head section.
     *
     * Outputs all collected JSON-LD data blocks in a single script.
     */
    public static function render()
    {
        if (!empty(self::$data)) {
            echo '<!-- JsonLD START -->';
            echo '<script type="application/ld+json">' . json_encode(self::$data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
            echo '<!-- JsonLD END -->';
        }
    }

    /**
     * Helper method to retrieve the URL of the custom logo.
     *
     * @return string|null The custom logo URL, or null if not set.
     */
    protected static function getCustomLogoUrl()
    {
        $custom_logo_id = get_theme_mod('custom_logo');
        return $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'full') : null;
    }
}