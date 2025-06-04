<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

class Page extends PostType
{
    /**
     * Get the post type.
     *
     * @return string
     */
    protected function getPostType(): string
    {
        return 'page';
    }

    /**
     * Get parent page.
     *
     * @return Page|null
     */
    public function parent(): ?Page
    {
        if (!$this->post->post_parent) {
            return null;
        }
        return new Page($this->post->post_parent);
    }

    /**
     * Get child pages.
     *
     * @return array
     */
    public function children(): array
    {
        $children = get_posts([
            'post_type' => 'page',
            'post_parent' => $this->id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        return array_map(function($child) {
            return new Page($child->ID);
        }, $children);
    }

    /**
     * Get page template.
     *
     * @return string
     */
    public function template(): string
    {
        return get_page_template_slug($this->id);
    }
} 