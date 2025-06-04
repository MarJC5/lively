<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Traits\Meta;

class Comment
{
    use Meta;

    protected $id;
    protected $comment;

    /**
     * Constructor to initialize the comment object.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->comment = get_comment($id);

        if (!$this->comment) {
            throw new \InvalidArgumentException('Invalid comment ID provided.');
        }
    }

    /**
     * Get the comment ID.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the comment content.
     *
     * @return string
     */
    public function content(): string
    {
        return get_comment_text($this->id);
    }

    /**
     * Get the comment author.
     *
     * @return string
     */
    public function author(): string
    {
        return get_comment_author($this->id);
    }

    /**
     * Get the comment author email.
     *
     * @return string
     */
    public function email(): string
    {
        return get_comment_author_email($this->id);
    }

    /**
     * Get the comment author URL.
     *
     * @return string
     */
    public function url(): string
    {
        return get_comment_author_url($this->id);
    }

    /**
     * Get the comment date.
     *
     * @param string $format
     * @return string
     */
    public function date(string $format = 'Y-m-d'): string
    {
        return get_comment_date($format, $this->id);
    }

    /**
     * Get the comment time.
     *
     * @param string $format
     * @return string
     */
    public function time(string $format = 'H:i:s'): string
    {
        return get_comment_time($format, false, true, $this->id);
    }

    /**
     * Get the comment status.
     *
     * @return string
     */
    public function status(): string
    {
        return wp_get_comment_status($this->id);
    }

    /**
     * Get the comment type.
     *
     * @return string
     */
    public function type(): string
    {
        return get_comment_type($this->id);
    }

    /**
     * Get the parent comment.
     *
     * @return Comment|null
     */
    public function parent(): ?Comment
    {
        if (!$this->comment->comment_parent) {
            return null;
        }
        return new Comment($this->comment->comment_parent);
    }

    /**
     * Get child comments.
     *
     * @return array
     */
    public function children(): array
    {
        $children = get_comments([
            'parent' => $this->id,
            'status' => 'approve'
        ]);

        return array_map(function($child) {
            return new Comment($child->comment_ID);
        }, $children);
    }

    /**
     * Get the associated post.
     *
     * @return Post|null
     */
    public function post(): ?Post
    {
        $post = get_post($this->comment->comment_post_ID);
        return $post ? new Post($post->ID) : null;
    }

    /**
     * Get the meta type for this model.
     *
     * @return string
     */
    protected function getMetaType(): string
    {
        return 'comment';
    }

    /**
     * Get the meta function for this model.
     *
     * @return callable
     */
    protected function getMetaFunction(): callable
    {
        return 'get_comment_meta';
    }

    /**
     * Get the update meta function for this model.
     *
     * @return callable
     */
    protected function updateMetaFunction(): callable
    {
        return 'update_comment_meta';
    }

    /**
     * Get the delete meta function for this model.
     *
     * @return callable
     */
    protected function deleteMetaFunction(): callable
    {
        return 'delete_comment_meta';
    }
} 