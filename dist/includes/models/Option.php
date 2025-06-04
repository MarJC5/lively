<?php

namespace Lively\Models;

// Prevent direct access.
defined('ABSPATH') or exit;

class Option
{
    protected $name;
    protected $value;
    protected $autoload;

    /**
     * Constructor to initialize the option object.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->load();
    }

    /**
     * Load the option value from the database.
     */
    protected function load(): void
    {
        global $wpdb;
        
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT option_value, autoload FROM $wpdb->options WHERE option_name = %s",
            $this->name
        ));

        if ($row) {
            $this->value = maybe_unserialize($row->option_value);
            $this->autoload = $row->autoload;
        } else {
            $this->value = null;
            $this->autoload = 'yes';
        }
    }

    /**
     * Get the option name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the option value.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Set the option value.
     *
     * @param mixed $value
     * @param bool $autoload
     * @return bool
     */
    public function set($value, bool $autoload = true): bool
    {
        $this->value = $value;
        $this->autoload = $autoload ? 'yes' : 'no';
        
        return update_option($this->name, $value, $autoload);
    }

    /**
     * Delete the option.
     *
     * @return bool
     */
    public function delete(): bool
    {
        return delete_option($this->name);
    }

    /**
     * Check if the option exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->value !== null;
    }

    /**
     * Check if the option is autoloaded.
     *
     * @return bool
     */
    public function isAutoloaded(): bool
    {
        return $this->autoload === 'yes';
    }

    /**
     * Get all options with a specific prefix.
     *
     * @param string $prefix
     * @return array
     */
    public static function getAllWithPrefix(string $prefix): array
    {
        global $wpdb;
        
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value, autoload 
            FROM $wpdb->options 
            WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%'
        ));

        $result = [];
        foreach ($options as $option) {
            $result[$option->option_name] = [
                'value' => maybe_unserialize($option->option_value),
                'autoload' => $option->autoload
            ];
        }

        return $result;
    }

    /**
     * Delete all options with a specific prefix.
     *
     * @param string $prefix
     * @return int Number of deleted options
     */
    public static function deleteAllWithPrefix(string $prefix): int
    {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%'
        ));

        return $deleted;
    }
} 