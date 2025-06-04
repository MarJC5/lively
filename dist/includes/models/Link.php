<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

class Link
{
    protected $id;
    protected $link;

    /**
     * Constructor to initialize the link object.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->link = get_bookmark($id);

        if (!$this->link) {
            throw new \InvalidArgumentException('Invalid link ID provided.');
        }
    }

    /**
     * Get the link ID.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the link name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->link->link_name;
    }

    /**
     * Get the link URL.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->link->link_url;
    }

    /**
     * Get the link description.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->link->link_description;
    }

    /**
     * Get the link notes.
     *
     * @return string
     */
    public function notes(): string
    {
        return $this->link->link_notes;
    }

    /**
     * Get the link rating.
     *
     * @return int
     */
    public function rating(): int
    {
        return (int) $this->link->link_rating;
    }

    /**
     * Get the link target.
     *
     * @return string
     */
    public function target(): string
    {
        return $this->link->link_target;
    }

    /**
     * Get the link image.
     *
     * @return string
     */
    public function image(): string
    {
        return $this->link->link_image;
    }

    /**
     * Get the link owner.
     *
     * @return int
     */
    public function owner(): int
    {
        return (int) $this->link->link_owner;
    }

    /**
     * Get the link category.
     *
     * @return int
     */
    public function category(): int
    {
        return (int) $this->link->link_category;
    }

    /**
     * Get the link visible status.
     *
     * @return string
     */
    public function visible(): string
    {
        return $this->link->link_visible;
    }

    /**
     * Get the link rel attribute.
     *
     * @return string
     */
    public function rel(): string
    {
        return $this->link->link_rel;
    }

    /**
     * Get the link rss feed URL.
     *
     * @return string
     */
    public function rss(): string
    {
        return $this->link->link_rss;
    }
} 