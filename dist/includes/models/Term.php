<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Traits\Meta;

class Term
{
    use Meta;

    protected $id;
    protected $term;
    protected $taxonomy;

    /**
     * Constructor to initialize the term object.
     *
     * @param int $id
     * @param string $taxonomy
     */
    public function __construct($id, string $taxonomy)
    {
        $this->id = $id;
        $this->taxonomy = $taxonomy;
        $this->term = get_term($id, $taxonomy);

        if (!$this->term || is_wp_error($this->term)) {
            throw new \InvalidArgumentException('Invalid term ID or taxonomy provided.');
        }
    }

    /**
     * Get the term ID.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the term name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->term->name;
    }

    /**
     * Get the term slug.
     *
     * @return string
     */
    public function slug(): string
    {
        return $this->term->slug;
    }

    /**
     * Get the term description.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->term->description;
    }

    /**
     * Get the term taxonomy.
     *
     * @return string
     */
    public function taxonomy(): string
    {
        return $this->taxonomy;
    }

    /**
     * Get the term parent.
     *
     * @return Term|null
     */
    public function parent(): ?Term
    {
        if (!$this->term->parent) {
            return null;
        }
        return new Term($this->term->parent, $this->taxonomy);
    }

    /**
     * Get child terms.
     *
     * @return array
     */
    public function children(): array
    {
        $children = get_terms([
            'taxonomy' => $this->taxonomy,
            'parent' => $this->id,
            'hide_empty' => false
        ]);

        if (is_wp_error($children)) {
            return [];
        }

        return array_map(function($child) {
            return new Term($child->term_id, $this->taxonomy);
        }, $children);
    }

    /**
     * Get the term count.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->term->count;
    }

    /**
     * Get the term URL.
     *
     * @return string
     */
    public function url(): string
    {
        return get_term_link($this->term);
    }

    /**
     * Get posts associated with this term.
     *
     * @param array $args Additional query arguments
     * @return array
     */
    public function posts(array $args = []): array
    {
        $defaults = [
            'post_type' => 'any',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field' => 'term_id',
                    'terms' => $this->id
                ]
            ]
        ];

        $query = new \WP_Query(array_merge($defaults, $args));
        $posts = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = new Post(get_the_ID());
            }
        }

        wp_reset_postdata();
        return $posts;
    }

    /**
     * Get the meta type for this model.
     *
     * @return string
     */
    protected function getMetaType(): string
    {
        return 'term';
    }

    /**
     * Get the meta function for this model.
     *
     * @return callable
     */
    protected function getMetaFunction(): callable
    {
        return 'get_term_meta';
    }

    /**
     * Get the update meta function for this model.
     *
     * @return callable
     */
    protected function updateMetaFunction(): callable
    {
        return 'update_term_meta';
    }

    /**
     * Get the delete meta function for this model.
     *
     * @return callable
     */
    protected function deleteMetaFunction(): callable
    {
        return 'delete_term_meta';
    }
} 