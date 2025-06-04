<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Traits\Meta;

class User
{
    use Meta;

    protected $id;
    protected $user;

    /**
     * Constructor to initialize the user object.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->user = get_userdata($id);

        if (!$this->user) {
            throw new \InvalidArgumentException('Invalid user ID provided.');
        }
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get the user login.
     *
     * @return string
     */
    public function login(): string
    {
        return $this->user->user_login;
    }

    /**
     * Get the user email.
     *
     * @return string
     */
    public function email(): string
    {
        return $this->user->user_email;
    }

    /**
     * Get the user display name.
     *
     * @return string
     */
    public function displayName(): string
    {
        return $this->user->display_name;
    }

    /**
     * Get the user first name.
     *
     * @return string
     */
    public function firstName(): string
    {
        return $this->user->first_name;
    }

    /**
     * Get the user last name.
     *
     * @return string
     */
    public function lastName(): string
    {
        return $this->user->last_name;
    }

    /**
     * Get the user full name.
     *
     * @return string
     */
    public function fullName(): string
    {
        return trim($this->firstName() . ' ' . $this->lastName());
    }

    /**
     * Get the user nickname.
     *
     * @return string
     */
    public function nickname(): string
    {
        return $this->user->nickname;
    }

    /**
     * Get the user URL.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->user->user_url;
    }

    /**
     * Get the user description.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->user->description;
    }

    /**
     * Get the user registration date.
     *
     * @param string $format
     * @return string
     */
    public function registered(string $format = 'Y-m-d'): string
    {
        return date($format, strtotime($this->user->user_registered));
    }

    /**
     * Get the user roles.
     *
     * @return array
     */
    public function roles(): array
    {
        return $this->user->roles;
    }

    /**
     * Check if user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles());
    }

    /**
     * Check if user is an administrator.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }

    /**
     * Get user posts.
     *
     * @param array $args Additional query arguments
     * @return array
     */
    public function posts(array $args = []): array
    {
        $defaults = [
            'author' => $this->id,
            'post_type' => 'any',
            'posts_per_page' => -1,
            'post_status' => 'publish'
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
     * Get user comments.
     *
     * @param array $args Additional query arguments
     * @return array
     */
    public function comments(array $args = []): array
    {
        $defaults = [
            'user_id' => $this->id,
            'status' => 'approve',
            'number' => 0
        ];

        $comments = get_comments(array_merge($defaults, $args));
        return array_map(function($comment) {
            return new Comment($comment->comment_ID);
        }, $comments);
    }

    /**
     * Get user avatar URL.
     *
     * @param int $size
     * @return string
     */
    public function avatar(int $size = 96): string
    {
        return get_avatar_url($this->id, ['size' => $size]);
    }

    /**
     * Get user capabilities.
     *
     * @return array
     */
    public function capabilities(): array
    {
        return $this->user->allcaps;
    }

    /**
     * Check if user has a specific capability.
     *
     * @param string $capability
     * @return bool
     */
    public function can(string $capability): bool
    {
        return user_can($this->id, $capability);
    }

    /**
     * Get user locale.
     *
     * @return string
     */
    public function locale(): string
    {
        return get_user_locale($this->id);
    }

    /**
     * Get user timezone.
     *
     * @return string
     */
    public function timezone(): string
    {
        return get_user_meta($this->id, 'timezone_string', true);
    }

    /**
     * Get the meta type for this model.
     *
     * @return string
     */
    protected function getMetaType(): string
    {
        return 'user';
    }

    /**
     * Get the meta function for this model.
     *
     * @return callable
     */
    protected function getMetaFunction(): callable
    {
        return 'get_user_meta';
    }

    /**
     * Get the update meta function for this model.
     *
     * @return callable
     */
    protected function updateMetaFunction(): callable
    {
        return 'update_user_meta';
    }

    /**
     * Get the delete meta function for this model.
     *
     * @return callable
     */
    protected function deleteMetaFunction(): callable
    {
        return 'delete_user_meta';
    }
} 