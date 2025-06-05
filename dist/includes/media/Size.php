<?php

namespace Lively\Media;

// Prevent direct access.
defined('ABSPATH') or exit;

class Size
{
    /**
     * Constants - Modern approach with better organization
     */
    private const CACHE_GROUP = 'lively_media_size';
    private const WEBP_QUALITY = 85;
    private const UNSUPPORTED_FORMATS = ['svg'];
    
    // Modern responsive sizes with container query support
    public const FULL_SIZE = 99999;
    public const SIZES = [
        "image-xs" => [360, self::FULL_SIZE, false],
        "image-sm" => [640, self::FULL_SIZE, false], 
        "image-md" => [768, self::FULL_SIZE, false],
        "image-lg" => [1024, self::FULL_SIZE, false],
        "image-xl" => [1280, self::FULL_SIZE, false],
        "image-2xl" => [1536, self::FULL_SIZE, false],
        
        // Retina versions
        "image-xs-2x" => [720, self::FULL_SIZE, false],
        "image-sm-2x" => [1280, self::FULL_SIZE, false],
        "image-md-2x" => [1536, self::FULL_SIZE, false],
        "image-lg-2x" => [2048, self::FULL_SIZE, false],
        "image-xl-2x" => [2560, self::FULL_SIZE, false],
        "image-2xl-2x" => [3072, self::FULL_SIZE, false],
        
        // Modern aspect ratios
        "square-sm" => [300, 300, true],
        "square-lg" => [600, 600, true],
        "wide-banner" => [1200, 300, true],
        "portrait" => [400, 600, true],
    ];

    // Properties with modern typing (PHP 7.4+)
    private static ?self $_instance = null;
    private array $_image_sizes = [];
    private string $_fly_dir = "";
    private string $_upload_url = "";
    private string $_capability = "manage_options";
    private bool $_webp_support = false;
    private bool $_avif_support = false;

    /**
     * Singleton pattern with type safety
     */
    public static function get_instance(): self
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct()
    {
        // Initialize properties
        $upload_dir = wp_upload_dir();
        $this->_upload_url = $upload_dir['baseurl'];
        $this->_webp_support = function_exists('imagewebp');
        $this->_avif_support = function_exists('imageavif') && wp_image_editor_supports(['mime_type' => 'image/avif']);
    }

    /**
     * Initialize with modern WordPress hooks
     */
    public function init(): void
    {
        $this->_fly_dir = apply_filters("fly_dir_path", $this->get_fly_dir());
        $this->_capability = apply_filters("fly_images_user_capability", $this->_capability);

        $this->check_fly_dir();
        $this->register_hooks();
        $this->register_image_sizes();
        $this->setup_modern_features();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void
    {
        add_action('switch_blog', [$this, 'blog_switched']);
        add_action('delete_attachment', [$this, 'cleanup_attachment_images']);
        add_filter("image_resize_dimensions", ["\\Lively\\Media\\Upscale", "resize"], 10, 6);
        add_filter("image_size_names_choose", fn($sizes) => array_merge($sizes, $this->get_size_names()));
        
        // Modern REST API integration
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Performance optimization
        add_action('wp_enqueue_scripts', [$this, 'enqueue_modern_scripts']);
    }

    /**
     * Register all image sizes
     */
    private function register_image_sizes(): void
    {
        foreach (self::SIZES as $name => $config) {
            $this->add_image_size($name, $config[0], $config[1], $config[2]);
        }
    }

    /**
     * Setup modern features (WebP, AVIF, lazy loading)
     */
    private function setup_modern_features(): void
    {
        if ($this->_webp_support) {
            add_filter('wp_generate_attachment_metadata', [$this, 'generate_modern_formats'], 10, 2);
        }
        
        // Add modern image attributes
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_modern_attributes'], 10, 3);
    }

    /**
     * Static method to add image size (keeping original API)
     */
    public static function add(string $size_name = "", int $width = 0, int $height = 0, bool $crop = false): bool
    {
        return self::get_instance()->add_image_size($size_name, $width, $height, $crop);
    }

    /**
     * Static method to get image src (keeping original API)
     */
    public static function src(int $attachment_id = 0, $size = "", ?bool $crop = null): array
    {
        return self::get_instance()->get_attachment_image_src($attachment_id, $size, $crop);
    }

    /**
     * Get image src in specific format (for manual fallback control)
     */
    public static function src_format(int $attachment_id = 0, $size = "", string $format = 'best', ?bool $crop = null): array
    {
        return self::get_instance()->get_attachment_image_src_format($attachment_id, $size, $format, $crop);
    }

    /**
     * Get image source in specified format
     */
    public function get_attachment_image_src_format(int $attachment_id, $size, string $format = 'best', ?bool $crop = null): array
    {
        if ($format === 'best') {
            return $this->get_attachment_image_src($attachment_id, $size, $crop);
        }

        $all_formats = $this->get_all_image_formats($attachment_id, $size, $crop);
        
        // Return specific format if available
        if (isset($all_formats[$format])) {
            return $all_formats[$format];
        }

        // Fallback logic
        $fallback_order = [
            'avif' => ['webp', 'original'],
            'webp' => ['original'],
            'original' => []
        ];

        if (isset($fallback_order[$format])) {
            foreach ($fallback_order[$format] as $fallback) {
                if (isset($all_formats[$fallback])) {
                    return $all_formats[$fallback];
                }
            }
        }

        return [];
    }

    /**
     * Get optimized image source with modern format support
     */
    public function get_attachment_image_src(int $attachment_id = 0, $size = "", ?bool $crop = null): array
    {
        if ($attachment_id < 1 || empty($size)) {
            return [];
        }

        // Handle full size
        if ($size === 'full') {
            return wp_get_attachment_image_src($attachment_id, 'full') ?: [];
        }

        // Check cache first
        $cache_key = $this->get_cache_key($attachment_id, $size, $crop);
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        if ($cached !== false) {
            return $cached;
        }

        $result = $this->generate_optimized_image($attachment_id, $size, $crop);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $result, self::CACHE_GROUP, HOUR_IN_SECONDS);
        
        return $result;
    }

    /**
     * Generate optimized image with modern format support
     */
    private function generate_optimized_image(int $attachment_id, $size, ?bool $crop): array
    {
        // Check if extension is supported
        $image_path = get_attached_file($attachment_id);
        if (!$image_path || !$this->is_supported_format($image_path)) {
            return wp_get_attachment_image_src($attachment_id, 'full') ?: [];
        }

        // Get image metadata
        $image_meta = wp_get_attachment_metadata($attachment_id);
        if (!$image_meta) {
            return [];
        }

        // Parse dimensions
        [$width, $height, $crop] = $this->parse_size_params($size, $crop);
        if (!$width || !$height) {
            return [];
        }

        // Generate file paths for different formats
        $base_filename = $this->get_fly_file_name(basename($image_meta['file']), $width, $height, $crop);
        $paths = $this->get_format_paths($attachment_id, $base_filename);

        // Check existing files (prioritize modern formats)
        foreach (['avif', 'webp', 'original'] as $format) {
            if (isset($paths[$format]) && file_exists($paths[$format]['path'])) {
                $image_size = getimagesize($paths[$format]['path']);
                if ($image_size) {
                    return [
                        'src' => $paths[$format]['url'],
                        'width' => $image_size[0],
                        'height' => $image_size[1],
                        'format' => $format
                    ];
                }
            }
        }

        // Generate new images if none exist
        return $this->create_optimized_images($attachment_id, $image_path, $paths, $width, $height, $crop);
    }

    /**
     * Create optimized images in multiple formats
     */
    private function create_optimized_images(int $attachment_id, string $source_path, array $paths, int $width, int $height, bool $crop): array
    {
        if (!$this->fly_dir_writable()) {
            return [];
        }

        try {
            // Ensure directory exists
            $this->check_fly_dir();
            wp_mkdir_p(dirname($paths['original']['path']));

            // Get image editor
            $editor = wp_get_image_editor($source_path);
            if (is_wp_error($editor)) {
                return [];
            }

            // Resize
            $resize_result = $editor->resize($width, $height, $crop);
            if (is_wp_error($resize_result)) {
                return [];
            }

            $result = [];
            $formats_to_create = ['original'];
            
            // Add modern formats if supported
            if ($this->_webp_support) $formats_to_create[] = 'webp';
            if ($this->_avif_support) $formats_to_create[] = 'avif';

            // Create images in all supported formats
            foreach ($formats_to_create as $format) {
                if (!isset($paths[$format])) continue;

                $save_args = [];
                if ($format === 'webp') {
                    $save_args = ['quality' => self::WEBP_QUALITY];
                } elseif ($format === 'avif') {
                    $save_args = ['quality' => 80];
                }

                $mime_type = $format === 'original' ? null : "image/{$format}";
                $save_result = $editor->save($paths[$format]['path'], $mime_type, $save_args);

                if (!is_wp_error($save_result) && file_exists($paths[$format]['path'])) {
                    $image_dimensions = $editor->get_size();
                    $result = [
                        'src' => $paths[$format]['url'],
                        'width' => $image_dimensions['width'],
                        'height' => $image_dimensions['height'],
                        'format' => $format
                    ];
                }
            }

            // Fire action for additional processing
            do_action('fly_image_created', $attachment_id, $paths, $result);

            return $result;

        } catch (\Exception $e) {
            error_log("Size class error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse size parameters with better error handling
     */
    private function parse_size_params($size, ?bool $crop): array
    {
        if (is_string($size)) {
            $image_size = $this->get_image_size($size);
            if (empty($image_size)) {
                // Try WordPress native sizes
                $wp_sizes = wp_get_additional_image_sizes();
                if (isset($wp_sizes[$size])) {
                    $wp_size = $wp_sizes[$size];
                    return [$wp_size['width'], $wp_size['height'], $crop ?? $wp_size['crop']];
                }
                return [0, 0, false];
            }
            return [$image_size['size'][0], $image_size['size'][1], $crop ?? $image_size['crop']];
        }

        if (is_array($size) && count($size) >= 2) {
            return [$size[0], $size[1], $crop ?? false];
        }

        return [0, 0, false];
    }

    /**
     * Get paths for different image formats
     */
    private function get_format_paths(int $attachment_id, string $base_filename): array
    {
        $fly_dir = $this->get_fly_dir($attachment_id);
        $paths = [];

        // Original format
        $original_path = $fly_dir . DIRECTORY_SEPARATOR . $base_filename;
        $paths['original'] = [
            'path' => $original_path,
            'url' => $this->get_fly_path($original_path)
        ];

        // WebP format
        if ($this->_webp_support) {
            $webp_filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $base_filename);
            $webp_path = $fly_dir . DIRECTORY_SEPARATOR . $webp_filename;
            $paths['webp'] = [
                'path' => $webp_path,
                'url' => $this->get_fly_path($webp_path)
            ];
        }

        // AVIF format
        if ($this->_avif_support) {
            $avif_filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.avif', $base_filename);
            $avif_path = $fly_dir . DIRECTORY_SEPARATOR . $avif_filename;
            $paths['avif'] = [
                'path' => $avif_path,
                'url' => $this->get_fly_path($avif_path)
            ];
        }

        return $paths;
    }

    /**
     * Check if image format is supported
     */
    private function is_supported_format(string $file_path): bool
    {
        $extension = strtolower($this->get_image_extension($file_path));
        return !in_array($extension, self::UNSUPPORTED_FORMATS);
    }

    /**
     * Generate cache key
     */
    private function get_cache_key(int $attachment_id, $size, ?bool $crop): string
    {
        $size_string = is_array($size) ? implode('x', $size) : $size;
        $crop_string = $crop ? 'crop' : 'no-crop';
        return "img_src_{$attachment_id}_{$size_string}_{$crop_string}";
    }

    /**
     * Get size names for WordPress admin
     */
    private function get_size_names(): array
    {
        $names = [];
        foreach (array_keys(self::SIZES) as $size_name) {
            $names[$size_name] = ucwords(str_replace(['-', '_'], ' ', $size_name));
        }
        return $names;
    }

    /**
     * Add modern attributes to images (lazy loading, etc.)
     */
    public function add_modern_attributes(array $attr, \WP_Post $attachment, $size): array
    {
        // Add lazy loading for modern browsers
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }

        // Add decoding hint
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }

        return $attr;
    }

    /**
     * Generate modern format versions
     */
    public function generate_modern_formats(array $metadata, int $attachment_id): array
    {
        if (empty($metadata['sizes'])) {
            return $metadata;
        }

        $original_path = get_attached_file($attachment_id);
        $upload_dir = dirname($original_path);

        foreach ($metadata['sizes'] as $size_name => $size_data) {
            $image_path = $upload_dir . '/' . $size_data['file'];
            
            // Generate WebP
            if ($this->_webp_support) {
                $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);
                if (!file_exists($webp_path)) {
                    $this->convert_to_format($image_path, $webp_path, 'webp');
                }
            }

            // Generate AVIF
            if ($this->_avif_support) {
                $avif_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.avif', $image_path);
                if (!file_exists($avif_path)) {
                    $this->convert_to_format($image_path, $avif_path, 'avif');
                }
            }
        }

        return $metadata;
    }

    /**
     * Convert image to specified format
     */
    private function convert_to_format(string $source_path, string $target_path, string $format): bool
    {
        if (!file_exists($source_path)) {
            return false;
        }

        $editor = wp_get_image_editor($source_path);
        if (is_wp_error($editor) || !$editor->supports_mime_type("image/{$format}")) {
            return false;
        }

        $quality = $format === 'webp' ? self::WEBP_QUALITY : 80;
        $result = $editor->save($target_path, "image/{$format}", ['quality' => $quality]);

        return !is_wp_error($result);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void
    {
        register_rest_route('lively/v1', '/media/resize/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_resized_image'],
            'permission_callback' => '__return_true',
            'args' => [
                'size' => ['required' => true, 'type' => 'string'],
                'format' => ['type' => 'string', 'enum' => ['webp', 'avif', 'original']]
            ]
        ]);
    }

    /**
     * REST endpoint for getting resized images
     */
    public function rest_get_resized_image(\WP_REST_Request $request): \WP_REST_Response
    {
        $attachment_id = (int) $request->get_param('id');
        $size = $request->get_param('size');

        $image_data = $this->get_attachment_image_src($attachment_id, $size);
        
        if (empty($image_data)) {
            return new \WP_REST_Response(['error' => 'Image not found'], 404);
        }

        return new \WP_REST_Response($image_data);
    }

    /**
     * Cleanup attachment images when deleted
     */
    public function cleanup_attachment_images(int $attachment_id): void
    {
        $this->delete_attachment_fly_images($attachment_id);
        
        // Clear cache
        wp_cache_flush_group(self::CACHE_GROUP);
    }

    /**
     * Enqueue modern scripts for frontend optimization
     */
    public function enqueue_modern_scripts(): void
    {
        // Add intersection observer for lazy loading fallback
        wp_add_inline_script('jquery', $this->get_lazy_loading_script());
    }

    /**
     * Get lazy loading fallback script
     */
    private function get_lazy_loading_script(): string
    {
        return "
        // Lazy loading fallback for older browsers
        if ('loading' in HTMLImageElement.prototype === false) {
            const images = document.querySelectorAll('img[loading=\"lazy\"]');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            img.removeAttribute('loading');
                            observer.unobserve(img);
                        }
                    });
                });
                images.forEach(img => observer.observe(img));
            }
        }";
    }

    // Keep all original methods for backward compatibility
    public function get_fly_dir(string $path = ""): string
    {
        if (empty($this->_fly_dir)) {
            $wp_upload_dir = wp_upload_dir();
            return $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'fly-images' . 
                   ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
        }

        return $this->_fly_dir . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function check_fly_dir(): void
    {
        if (!is_dir($this->_fly_dir)) {
            wp_mkdir_p($this->_fly_dir);
        }
    }

    public function fly_dir_writable(): bool
    {
        return is_dir($this->_fly_dir) && wp_is_writable($this->_fly_dir);
    }

    public function delete_attachment_fly_images(int $attachment_id = 0): bool
    {
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem->rmdir($this->get_fly_dir($attachment_id), true);
    }

    public function delete_all_fly_images(): bool
    {
        WP_Filesystem();
        global $wp_filesystem;

        if ($wp_filesystem->rmdir($this->get_fly_dir(), true)) {
            $this->check_fly_dir();
            return true;
        }

        return false;
    }

    public function add_image_size(string $size_name, int $width = 0, int $height = 0, bool $crop = false): bool
    {
        if (empty($size_name) || !$width || !$height) {
            return false;
        }

        $this->_image_sizes[$size_name] = [
            'size' => [$width, $height],
            'crop' => $crop,
        ];

        return true;
    }

    public function get_image_size(string $size_name = ""): array
    {
        if (empty($size_name) || !isset($this->_image_sizes[$size_name])) {
            return [];
        }

        return $this->_image_sizes[$size_name];
    }

    public function get_image_extension(string $file_name = ""): string
    {
        return pathinfo($file_name, PATHINFO_EXTENSION);
    }

    public function get_all_image_sizes(): array
    {
        return $this->_image_sizes;
    }

    public function get_attachment_image(int $attachment_id = 0, $size = "", ?bool $crop = null, array $attr = []): string
    {
        if ($attachment_id < 1 || empty($size)) {
            return '';
        }

        if ($size === 'full') {
            return wp_get_attachment_image($attachment_id, $size, false, $attr);
        }

        // Get all available formats for this image
        $all_formats = $this->get_all_image_formats($attachment_id, $size, $crop);
        if (empty($all_formats)) {
            return '';
        }

        $size_class = is_array($size) ? implode('x', $size) : $size;
        $attachment = get_post($attachment_id);
        
        // Get image dimensions from the best available format
        $primary_image = reset($all_formats);
        $hwstring = image_hwstring($primary_image['width'], $primary_image['height']);
        
        // Default attributes
        $default_attr = [
            'class' => "attachment-{$size_class}",
            'alt' => trim(strip_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))),
            'loading' => 'lazy',
            'decoding' => 'async'
        ];

        // Fallback alt text
        if (empty($default_attr['alt'])) {
            $default_attr['alt'] = trim(strip_tags($attachment->post_excerpt));
        }
        if (empty($default_attr['alt'])) {
            $default_attr['alt'] = trim(strip_tags($attachment->post_title));
        }

        $attr = wp_parse_args($attr, $default_attr);
        $attr = apply_filters('fly_get_attachment_image_attributes', $attr, $attachment, $size);
        
        // Generate modern <picture> element with fallbacks
        if (count($all_formats) > 1) {
            return $this->generate_picture_element($all_formats, $attr, $hwstring);
        }
        
        // Single format fallback (original <img> behavior)
        $attr['src'] = $primary_image['src'];
        $attr = array_map('esc_attr', $attr);
        
        $html = rtrim("<img {$hwstring}");
        foreach ($attr as $name => $value) {
            $html .= " {$name}=\"{$value}\"";
        }
        $html .= ' />';

        return $html;
    }

    /**
     * Generate modern <picture> element with format fallbacks
     */
    private function generate_picture_element(array $formats, array $attr, string $hwstring): string
    {
        $html = '<picture>';
        
        // Add source elements for modern formats (AVIF, WebP)
        foreach (['avif', 'webp'] as $format) {
            if (isset($formats[$format])) {
                $html .= sprintf(
                    '<source srcset="%s" type="image/%s">',
                    esc_url($formats[$format]['src']),
                    $format
                );
            }
        }
        
        // Fallback <img> with original format
        $fallback_format = $formats['original'] ?? reset($formats);
        $attr['src'] = $fallback_format['src'];
        $attr = array_map('esc_attr', $attr);
        
        $html .= rtrim("<img {$hwstring}");
        foreach ($attr as $name => $value) {
            $html .= " {$name}=\"{$value}\"";
        }
        $html .= ' />';
        
        $html .= '</picture>';
        
        return $html;
    }

    /**
     * Get all available image formats for an attachment
     */
    public function get_all_image_formats(int $attachment_id, $size, ?bool $crop): array
    {
        // Check if extension is supported
        $image_path = get_attached_file($attachment_id);
        if (!$image_path || !$this->is_supported_format($image_path)) {
            return [];
        }

        // Get image metadata
        $image_meta = wp_get_attachment_metadata($attachment_id);
        if (!$image_meta) {
            return [];
        }

        // Parse dimensions
        [$width, $height, $crop] = $this->parse_size_params($size, $crop);
        if (!$width || !$height) {
            return [];
        }

        // Generate file paths for different formats
        $base_filename = $this->get_fly_file_name(basename($image_meta['file']), $width, $height, $crop);
        $paths = $this->get_format_paths($attachment_id, $base_filename);

        $available_formats = [];

        // Check existing files and collect all available formats
        foreach (['avif', 'webp', 'original'] as $format) {
            if (isset($paths[$format]) && file_exists($paths[$format]['path'])) {
                $image_size = getimagesize($paths[$format]['path']);
                if ($image_size) {
                    $available_formats[$format] = [
                        'src' => $paths[$format]['url'],
                        'width' => $image_size[0],
                        'height' => $image_size[1],
                        'format' => $format
                    ];
                }
            }
        }

        // Generate missing formats if none exist
        if (empty($available_formats)) {
            $generated = $this->create_optimized_images($attachment_id, $image_path, $paths, $width, $height, $crop);
            if ($generated) {
                // Re-check all formats after generation
                foreach (['avif', 'webp', 'original'] as $format) {
                    if (isset($paths[$format]) && file_exists($paths[$format]['path'])) {
                        $image_size = getimagesize($paths[$format]['path']);
                        if ($image_size) {
                            $available_formats[$format] = [
                                'src' => $paths[$format]['url'],
                                'width' => $image_size[0],
                                'height' => $image_size[1],
                                'format' => $format
                            ];
                        }
                    }
                }
            }
        }

        return $available_formats;
    }

    public function get_fly_file_name(string $file_name, int $width, int $height, bool $crop, bool $webp = false): string
    {
        $file_name_only = pathinfo($file_name, PATHINFO_FILENAME);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        $crop_extension = $crop ? '-c' : '';
        if (is_array($crop)) {
            $crop_extension = '-' . implode('', array_map(fn($pos) => $pos[0], $crop));
        }

        if ($webp) {
            $file_extension = 'webp';
        }

        return sprintf('%s-%dx%d%s.%s', $file_name_only, $width, $height, $crop_extension, $file_extension);
    }

    public function get_fly_path(string $absolute_path = ""): string
    {
        $wp_upload_dir = wp_upload_dir();
        $path = $wp_upload_dir['baseurl'] . str_replace($wp_upload_dir['basedir'], '', $absolute_path);
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    public function get_fly_absolute_path(string $path = ""): string
    {
        $wp_upload_dir = wp_upload_dir();
        return $wp_upload_dir['basedir'] . str_replace($wp_upload_dir['baseurl'], '', $path);
    }

    public function blog_switched(): void
    {
        $this->_fly_dir = '';
        $this->_fly_dir = apply_filters('fly_dir_path', $this->get_fly_dir());
    }
}