<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

class Post extends PostType
{
    /**
     * Get the post type.
     *
     * @return string
     */
    protected function getPostType(): string
    {
        return 'post';
    }

    /**
     * Get post categories.
     *
     * @return array
     */
    public function categories(): array
    {
        $terms = $this->terms('category');
        return array_map(function($term) {
            return new Term($term->term_id, 'category');
        }, $terms);
    }

    /**
     * Get post tags.
     *
     * @return array
     */
    public function tags(): array
    {
        $terms = $this->terms('post_tag');
        return array_map(function($term) {
            return new Term($term->term_id, 'post_tag');
        }, $terms);
    }

    /**
     * Get terms from a specific taxonomy.
     *
     * @param string $taxonomy
     * @return array
     */
    public function getTerms(string $taxonomy): array
    {
        $terms = $this->terms($taxonomy);
        return array_map(function($term) use ($taxonomy) {
            return new Term($term->term_id, $taxonomy);
        }, $terms);
    }

    /**
     * Get all taxonomies associated with this post.
     *
     * @return array
     */
    public function getTaxonomies(): array
    {
        return get_object_taxonomies($this->post);
    }

    /**
     * Check if post has a specific term.
     *
     * @param string $taxonomy
     * @param string|int $term Term ID or slug
     * @return bool
     */
    public function hasTerm(string $taxonomy, $term): bool
    {
        return has_term($term, $taxonomy, $this->id);
    }

    /**
     * Get previous post.
     *
     * @return Post|null
     */
    public function previous(): ?Post
    {
        $previous = get_previous_post();
        return $previous ? new Post($previous->ID) : null;
    }

    /**
     * Get next post.
     *
     * @return Post|null
     */
    public function next(): ?Post
    {
        $next = get_next_post();
        return $next ? new Post($next->ID) : null;
    }
} 