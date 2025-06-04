<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

class Product extends PostType
{
    /**
     * Get the post type.
     *
     * @return string
     */
    protected function getPostType(): string
    {
        return 'product';
    }

    /**
     * Get the product price.
     *
     * @return float
     */
    public function price(): float
    {
        return (float) $this->getMetaFloat('price', 0.0);
    }

    /**
     * Get the product SKU.
     *
     * @return string
     */
    public function sku(): string
    {
        return $this->getMetaString('sku', '');
    }

    /**
     * Get the product stock quantity.
     *
     * @return int
     */
    public function stock(): int
    {
        return $this->getMetaInt('stock', 0);
    }

    /**
     * Check if product is in stock.
     *
     * @return bool
     */
    public function inStock(): bool
    {
        return $this->stock() > 0;
    }

    /**
     * Get product categories.
     *
     * @return array
     */
    public function categories(): array
    {
        return array_map(function($term) {
            return new Term($term->term_id, 'product_category');
        }, $this->terms('product_category'));
    }

    /**
     * Get product tags.
     *
     * @return array
     */
    public function tags(): array
    {
        return array_map(function($term) {
            return new Term($term->term_id, 'product_tag');
        }, $this->terms('product_tag'));
    }

    /**
     * Get product gallery images.
     *
     * @return array
     */
    public function gallery(): array
    {
        $gallery_ids = $this->getMetaArray('gallery');
        return array_map(function($id) {
            return new Media($id);
        }, $gallery_ids);
    }

    /**
     * Get product attributes.
     *
     * @return array
     */
    public function attributes(): array
    {
        return $this->getMetaJson('attributes', []);
    }

    /**
     * Get product variations.
     *
     * @return array
     */
    public function variations(): array
    {
        $variations = $this->getMetaJson('variations', []);
        return array_map(function($variation) {
            return new Product($variation['id']);
        }, $variations);
    }

    /**
     * Get related products.
     *
     * @param int $limit
     * @return array
     */
    public function related(int $limit = 4): array
    {
        $related_ids = $this->getMetaArray('related_products');
        if (empty($related_ids)) {
            return [];
        }

        $related_ids = array_slice($related_ids, 0, $limit);
        return array_map(function($id) {
            return new Product($id);
        }, $related_ids);
    }

    /**
     * Get product reviews.
     *
     * @return array
     */
    public function reviews(): array
    {
        $comments = get_comments([
            'post_id' => $this->id(),
            'status' => 'approve',
            'type' => 'review'
        ]);

        return array_map(function($comment) {
            return new Comment($comment->comment_ID);
        }, $comments);
    }

    /**
     * Get average rating.
     *
     * @return float
     */
    public function rating(): float
    {
        return (float) $this->getMetaFloat('rating', 0.0);
    }

    /**
     * Get review count.
     *
     * @return int
     */
    public function reviewCount(): int
    {
        return $this->getMetaInt('review_count', 0);
    }
} 