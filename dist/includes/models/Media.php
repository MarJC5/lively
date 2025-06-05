<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Media\Size;

class Media
{
    protected int $id;
    protected ?\WP_Post $attachment;

    /**
     * Constructor to initialize the media object.
     * 
     * @param int $id
     * @throws \InvalidArgumentException
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->attachment = get_post($id);
        
        if (!$this->attachment || $this->attachment->post_type !== 'attachment') {
            throw new \InvalidArgumentException("Invalid media ID provided: {$id}");
        }
    }

    /**
     * Get the media url for a size.
     * Returns the original media's url if size doesn't exist.
     * 
     * @param string $size The size ID
     * @return string|false
     */
    public function src(string $size = "thumbnail"): string|false
    {
        $data = Size::src($this->id(), $size);
        
        if ($size !== "full" && empty($data)) {
            return $this->src("full");
        }
        
        if (empty($data)) {
            return false;
        }

        // Handle both old array format [0 => url] and new format ['src' => url]
        if (isset($data['src'])) {
            return $data['src'];
        }
        
        if (isset($data[0])) {
            return $data[0];
        }

        return false;
    }

    /**
     * Get the media url for a specific format
     * 
     * @param string $size The size ID
     * @param string $format The desired format ('avif', 'webp', 'original', 'best')
     * @return string|false
     */
    public function srcFormat(string $size = "thumbnail", string $format = 'best'): string|false
    {
        $data = Size::src_format($this->id(), $size, $format);
        
        if ($size !== "full" && empty($data)) {
            return $this->srcFormat("full", $format);
        }
        
        if (empty($data)) {
            return false;
        }

        return $data['src'] ?? false;
    }

    /**
     * Get all available formats for a size
     * 
     * @param string $size
     * @return array Array of format => data
     */
    public function getAllFormats(string $size = "thumbnail"): array
    {
        $sizeInstance = Size::get_instance();
        return $sizeInstance->get_all_image_formats($this->id(), $size, null);
    }

    /**
     * Get responsive image data with all formats and sizes
     * 
     * @param array $sizes Array of size names to include
     * @return array
     */
    public function getResponsiveData(array $sizes = []): array
    {
        if (empty($sizes)) {
            $sizes = array_keys(Size::SIZES);
        }

        $responsive = [];
        foreach ($sizes as $sizeName) {
            $allFormats = $this->getAllFormats($sizeName);
            if (!empty($allFormats)) {
                $responsive[$sizeName] = $allFormats;
            }
        }

        return $responsive;
    }

    /**
     * Generate complete picture element HTML
     * 
     * @param string $size Primary size to use
     * @param array $attr Additional HTML attributes
     * @return string
     */
    public function picture(string $size = "large", array $attr = []): string
    {
        $sizeInstance = Size::get_instance();
        return $sizeInstance->get_attachment_image($this->id(), $size, null, $attr);
    }

    /**
     * Get srcset string for responsive images
     * 
     * @param array $sizes Array of [size_name => width] pairs
     * @param string $format Specific format or 'best' for automatic
     * @return string
     */
    public function srcset(array $sizes, string $format = 'best'): string
    {
        $srcset = [];
        
        foreach ($sizes as $sizeName => $width) {
            $imageData = $format === 'best' 
                ? Size::src($this->id(), $sizeName)
                : Size::src_format($this->id(), $sizeName, $format);
                
            if (!empty($imageData['src'])) {
                $srcset[] = $imageData['src'] . ' ' . $width . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Check if a specific format is available for this media
     * 
     * @param string $format
     * @return bool
     */
    public function hasFormat(string $format): bool
    {
        if (!$this->isImage()) {
            return false;
        }

        $sizeInstance = Size::get_instance();
        
        // Check format support based on file type and server capabilities
        switch ($format) {
            case 'avif':
                return $sizeInstance->_avif_support ?? false;
            case 'webp':
                return $sizeInstance->_webp_support ?? false;
            case 'original':
                return true;
            default:
                return false;
        }
    }

    /**
     * Get the best available format for this media
     * 
     * @return string
     */
    public function getBestFormat(): string
    {
        if ($this->hasFormat('avif')) {
            return 'avif';
        }
        
        if ($this->hasFormat('webp')) {
            return 'webp';
        }
        
        return 'original';
    }

    /**
     * Get image dimensions for a specific size
     * 
     * @param string $size
     * @return array|false [width, height] or false
     */
    public function dimensions(string $size = "thumbnail"): array|false
    {
        $data = Size::src($this->id(), $size);
        
        if (empty($data)) {
            return false;
        }

        return [
            'width' => $data['width'] ?? $data[1] ?? 0,
            'height' => $data['height'] ?? $data[2] ?? 0
        ];
    }

    /**
     * Get the media ID.
     * 
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the media alt description.
     * 
     * @return string
     */
    public function alt(): string
    {
        $alt = get_post_meta($this->id(), "_wp_attachment_image_alt", true);
        return $alt ?: $this->title();
    }

    /**
     * Get the media caption.
     * 
     * @return string
     */
    public function caption(): string
    {
        return wp_get_attachment_caption($this->id()) ?: '';
    }

    /**
     * Get the filename of the media.
     * 
     * @return string
     */
    public function filename(): string
    {
        $filepath = get_attached_file($this->id());
        return $filepath ? basename($filepath) : '';
    }

    /**
     * Get the media title.
     * 
     * @return string
     */
    public function title(): string
    {
        return get_the_title($this->id()) ?: '';
    }

    /**
     * Get the media URL (original file).
     * 
     * @return string
     */
    public function url(): string
    {
        return wp_get_attachment_url($this->id()) ?: '';
    }

    /**
     * Get the physical file path of the media.
     * 
     * @return string|null
     */
    public function path(): ?string
    {
        $filepath = get_attached_file($this->id());
        return $filepath ?: null;
    }

    /**
     * Get the media metadata.
     * 
     * @return array
     */
    public function metadata(): array
    {
        return wp_get_attachment_metadata($this->id()) ?: [];
    }

    /**
     * Check if the media is an image.
     * 
     * @return bool
     */
    public function isImage(): bool
    {
        $mimeType = get_post_mime_type($this->id());
        return $mimeType && strpos($mimeType, 'image') === 0;
    }

    /**
     * Check if the media is a video.
     * 
     * @return bool
     */
    public function isVideo(): bool
    {
        $mimeType = get_post_mime_type($this->id());
        return $mimeType && strpos($mimeType, 'video') === 0;
    }

    /**
     * Check if the media is an audio file.
     * 
     * @return bool
     */
    public function isAudio(): bool
    {
        $mimeType = get_post_mime_type($this->id());
        return $mimeType && strpos($mimeType, 'audio') === 0;
    }

    /**
     * Get the file size in bytes
     * 
     * @return int
     */
    public function fileSize(): int
    {
        $path = $this->path();
        return $path && file_exists($path) ? filesize($path) : 0;
    }

    /**
     * Get human readable file size
     * 
     * @return string
     */
    public function fileSizeFormatted(): string
    {
        return size_format($this->fileSize());
    }

    /**
     * Get the MIME type
     * 
     * @return string
     */
    public function mimeType(): string
    {
        return get_post_mime_type($this->id()) ?: '';
    }

    /**
     * Get the file extension
     * 
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->filename(), PATHINFO_EXTENSION);
    }

    /**
     * Get debug information about this media
     * 
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'id' => $this->id(),
            'title' => $this->title(),
            'filename' => $this->filename(),
            'mime_type' => $this->mimeType(),
            'file_size' => $this->fileSizeFormatted(),
            'is_image' => $this->isImage(),
            'available_formats' => $this->isImage() ? [
                'avif' => $this->hasFormat('avif'),
                'webp' => $this->hasFormat('webp'),
                'original' => true
            ] : [],
            'best_format' => $this->isImage() ? $this->getBestFormat() : null,
            'url' => $this->url(),
            'path' => $this->path()
        ];
    }

    /**
     * Magic method to get properties
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return match($name) {
            'id' => $this->id(),
            'title' => $this->title(),
            'alt' => $this->alt(),
            'caption' => $this->caption(),
            'filename' => $this->filename(),
            'url' => $this->url(),
            'path' => $this->path(),
            'metadata' => $this->metadata(),
            default => null
        };
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'title' => $this->title(),
            'alt' => $this->alt(),
            'caption' => $this->caption(),
            'filename' => $this->filename(),
            'url' => $this->url(),
            'mime_type' => $this->mimeType(),
            'file_size' => $this->fileSize(),
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->isAudio(),
            'metadata' => $this->metadata()
        ];
    }
}