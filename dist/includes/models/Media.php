<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Media\Size;

class Media
{
    protected $id;
    protected $attachment;

    /**
     * Constructor to initialize the media object.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->attachment = get_post($id);

        if (!$this->attachment || $this->attachment->post_type !== 'attachment') {
            throw new \InvalidArgumentException('Invalid media ID provided.');
        }
    }

    /**
     * Get the media url for a size.
     * Return The original media's url if size didn't exist.
     * @param string $size The size ID
     *
     * @return string
     */
    public function src($size = "thumbnail"): string|bool
    {
        $data = Size::src($this->id(), $size);

        if ($size !== "full" and !$data) {
            return $this->src("full");
        }

        if (!$data) {
            return false;
        }

        if (isset($data[0])) {
            return $data[0];
        }

        return $data["src"];
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
        return get_post_meta($this->id(), "_wp_attachment_image_alt", true) ?? $this->title();
    }

    /**
     * Get the media caption.
     *
     * @return string
     */
    public function caption(): string
    {
        return wp_get_attachment_caption($this->id());
    }

    /**
     * Get the filename of the media.
     */
    public function filename(): string
    {
        return basename(get_attached_file($this->id()));
    }

    /**
     * Get the media title.
     *
     * @return string
     */
    public function title(): string
    {
        return get_the_title($this->id);
    }

    /**
     * Get the media URL.
     *
     * @return string|null
     */
    public function url(): string
    {
        return wp_get_attachment_url($this->id);
    }

    /**
     * Get the physical file path of the media.
     *
     * @return string|null
     */
    public function path(): string
    {
        $uploadDir = wp_upload_dir();
        $filePath = get_post_meta($this->id, '_wp_attached_file', true);

        return $filePath ? $uploadDir['basedir'] . '/' . $filePath : null;
    }

    /**
     * Get the media metadata.
     *
     * @return array|null
     */
    public function metadata(): array
    {
        return wp_get_attachment_metadata($this->id);
    }

    /**
     * Check if the media is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        $mimeType = get_post_mime_type($this->id);
        return strpos($mimeType, 'image') === 0;
    }

    /**
     * Check if the media is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        $mimeType = get_post_mime_type($this->id);
        return strpos($mimeType, 'video') === 0;
    }
}