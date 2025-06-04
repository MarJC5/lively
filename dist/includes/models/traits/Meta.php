<?php

namespace Lively\Models\Traits;

// Prevent direct access.
defined('ABSPATH') or exit;

trait Meta
{
    /**
     * Get meta value.
     *
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public function getMeta(string $key, bool $single = true)
    {
        return $this->getMetaFunction()($this->id(), $key, $single);
    }

    /**
     * Set meta value.
     *
     * @param string $key
     * @param mixed $value
     * @return int|bool
     */
    public function setMeta(string $key, $value)
    {
        return $this->updateMetaFunction()($this->id(), $key, $value);
    }

    /**
     * Delete meta value.
     *
     * @param string $key
     * @return bool
     */
    public function deleteMeta(string $key)
    {
        return $this->deleteMetaFunction()($this->id(), $key);
    }

    /**
     * Check if meta exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return metadata_exists($this->getMetaType(), $this->id(), $key);
    }

    /**
     * Get all meta values.
     *
     * @return array
     */
    public function getAllMeta(): array
    {
        return $this->getMetaFunction()($this->id());
    }

    /**
     * Get meta value with default.
     *
     * @param string $key
     * @param mixed $default
     * @param bool $single
     * @return mixed
     */
    public function getMetaWithDefault(string $key, $default = null, bool $single = true)
    {
        $value = $this->getMeta($key, $single);
        return $value !== false && $value !== '' ? $value : $default;
    }

    /**
     * Get meta value as array.
     *
     * @param string $key
     * @return array
     */
    public function getMetaArray(string $key): array
    {
        $value = $this->getMeta($key, false);
        return is_array($value) ? $value : [];
    }

    /**
     * Get meta value as string.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getMetaString(string $key, string $default = ''): string
    {
        $value = $this->getMeta($key);
        return is_string($value) ? $value : $default;
    }

    /**
     * Get meta value as integer.
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    public function getMetaInt(string $key, int $default = 0): int
    {
        $value = $this->getMeta($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get meta value as float.
     *
     * @param string $key
     * @param float $default
     * @return float
     */
    public function getMetaFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getMeta($key);
        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Get meta value as boolean.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getMetaBool(string $key, bool $default = false): bool
    {
        $value = $this->getMeta($key);
        return is_bool($value) ? $value : $default;
    }

    /**
     * Get meta value as JSON.
     *
     * @param string $key
     * @param array $default
     * @return array
     */
    public function getMetaJson(string $key, array $default = []): array
    {
        $value = $this->getMetaString($key);
        if (empty($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Set meta value as JSON.
     *
     * @param string $key
     * @param array $value
     * @return int|bool
     */
    public function setMetaJson(string $key, array $value)
    {
        return $this->setMeta($key, json_encode($value));
    }

    /**
     * Get the meta type for this model.
     * Must be implemented by the using class.
     *
     * @return string
     */
    abstract protected function getMetaType(): string;

    /**
     * Get the meta function for this model.
     * Must be implemented by the using class.
     *
     * @return callable
     */
    abstract protected function getMetaFunction(): callable;

    /**
     * Get the update meta function for this model.
     * Must be implemented by the using class.
     *
     * @return callable
     */
    abstract protected function updateMetaFunction(): callable;

    /**
     * Get the delete meta function for this model.
     * Must be implemented by the using class.
     *
     * @return callable
     */
    abstract protected function deleteMetaFunction(): callable;
} 