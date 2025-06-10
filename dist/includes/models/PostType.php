<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Traits\Meta;

abstract class PostType
{
    use Meta;

    protected $id;
    protected $post;

    /**
     * Get the post type.
     * This method must be implemented by child classes.
     *
     * @return string
     */
    abstract protected function getPostType(): string;

    /**
     * Constructor to initialize the post type object.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->post = get_post($id);

        if (!$this->post) {
            throw new \InvalidArgumentException('Invalid post ID provided.');
        }

        if ($this->post->post_type !== $this->getPostType()) {
            throw new \InvalidArgumentException('Invalid post type.');
        }
    }

    /**
     * Get the post ID.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the post title.
     *
     * @return string
     */
    public function title(): string
    {
        return get_the_title($this->id);
    }

    /**
     * Get the post content.
     *
     * @return string
     */
    public function content(): string
    {
        return get_the_content(null, false, $this->id);
    }

    /**
     * Get the post excerpt.
     *
     * @return string
     */
    public function excerpt(): string
    {
        return get_the_excerpt($this->id);
    }

    /**
     * Get the post permalink.
     *
     * @return string
     */
    public function url(): string
    {
        return get_permalink($this->id);
    }

    /**
     * Get the post date.
     *
     * @param string $format
     * @return string
     */
    public function date(string $format = 'Y-m-d'): string
    {
        return get_the_date($format, $this->id);
    }

    /**
     * Get the post modified date.
     *
     * @param string $format
     * @return string
     */
    public function modifiedDate(string $format = 'Y-m-d'): string
    {
        return get_the_modified_date($format, $this->id);
    }

    /**
     * Get the post author.
     *
     * @return int
     */
    public function author(): int
    {
        return $this->post->post_author;
    }

    /**
     * Get the post status.
     *
     * @return string
     */
    public function status(): string
    {
        return $this->post->post_status;
    }

    /**
     * Get the post type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->post->post_type;
    }

    /**
     * Get the featured image.
     *
     * @return Media|null
     */
    public function featuredImage(): ?Media
    {
        $thumbnail_id = get_post_thumbnail_id($this->id);
        return $thumbnail_id ? new Media($thumbnail_id) : null;
    }

    /**
     * Get post terms.
     *
     * @param string $taxonomy
     * @return array
     */
    public function terms(string $taxonomy): array
    {
        return wp_get_post_terms($this->id, $taxonomy);
    }

    /**
     * Get the meta type for this model.
     *
     * @return string
     */
    protected function getMetaType(): string
    {
        return 'post';
    }

    /**
     * Get the meta function for this model.
     *
     * @return callable
     */
    protected function getMetaFunction(): callable
    {
        return 'get_post_meta';
    }

    /**
     * Get the update meta function for this model.
     *
     * @return callable
     */
    protected function updateMetaFunction(): callable
    {
        return 'update_post_meta';
    }

    /**
     * Get the delete meta function for this model.
     *
     * @return callable
     */
    protected function deleteMetaFunction(): callable
    {
        return 'delete_post_meta';
    }

    /**
     * Get the post type.
     * This method is used to get a post type object from an ID.
     *
     * @param int $id
     * @param string|null $postType
     * @return PostType
     */
    public static function get(int $id, ?string $postType = null)
    {
        $postType = $postType ?? get_post_type($id);
        $modelClass = "\\Lively\\Models\\" . ucfirst($postType);
        return new $modelClass($id);
    }

    /**
     * Get the post type.
     * This method is used to get a post type object from an array of IDs.
     *
     * @param array $ids
     * @param callable|null $callback
     * @return array
     */
    public static function gets(array $ids, ?callable $callback = null)
    {
        $models = [];
        foreach ($ids as $id) {
            $postType = get_post_type($id);
            $modelClass = "\\Lively\\Models\\" . ucfirst($postType);
            $models[] = $callback ? $callback(new $modelClass($id)) : new $modelClass($id);
        }

        return $models;
    }
} 