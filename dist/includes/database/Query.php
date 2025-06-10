<?php

namespace Lively\Database;

use Lively\Core\Utils\Logger;

// Prevent direct access.
defined('ABSPATH') or exit;

class Query {
    protected $wpdb;
    protected $table;
    protected $select = ['*'];
    protected $where = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;
    protected $joins = [];
    protected $params = [];
    protected $prefix = true;   

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Set the table to query
     * 
     * @param string $table
     * @return self
     */
    public function table(string $table): self {
        $this->table = $this->prefix ? $this->wpdb->prefix . $table : $table;
        return $this;
    }

    /**
     * Disable automatic table prefix
     * 
     * @return self
     */
    public function withoutPrefix(): self {
        $this->prefix = false;
        return $this;
    }

    /**
     * Enable automatic table prefix
     * 
     * @return self
     */
    public function withPrefix(): self {
        $this->prefix = true;
        return $this;
    }

    /**
     * Set columns to select
     * 
     * @param array|string $columns
     * @return self
     */
    public function select($columns = ['*']): self {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a where clause
     * 
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function where(string $column, string $operator = '=', $value = null): self {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add an OR where clause
     * 
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere(string $column, string $operator = '=', $value = null): self {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];

        return $this;
    }

    /**
     * Add a join clause
     * 
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return self
     */
    public function join(string $table, string $first, string $operator = '=', ?string $second = null, string $type = 'INNER'): self {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = [
            'table' => $this->prefix ? $this->wpdb->prefix . $table : $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    /**
     * Add an order by clause
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    /**
     * Set the limit
     * 
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset
     * 
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get the first result
     * 
     * @return object|null
     */
    public function first(): ?object {
        $this->limit(1);
        $results = $this->get();
        
        // Log whether we found a result
        Logger::debug('First Result Query', [
            'found' => !empty($results),
            'last_error' => $this->wpdb->last_error,
            'last_query' => $this->wpdb->last_query
        ]);
        
        return $results[0] ?? null;
    }

    /**
     * Get the last result
     * 
     * @return object|null
     */
    public function last(): ?object {
        // Store original order by
        $originalOrderBy = $this->orderBy;
        
        // Clear existing order by
        $this->orderBy = [];
        
        // Add DESC order by for the first column in select
        $firstColumn = $this->select[0] === '*' ? 'id' : $this->select[0];
        $this->orderBy($firstColumn, 'DESC');
        
        // Get first result with reversed ordering
        $result = $this->first();
        
        // Restore original order by
        $this->orderBy = $originalOrderBy;
        
        // Log the query
        Logger::debug('Last Result Query', [
            'found' => !empty($result),
            'last_error' => $this->wpdb->last_error,
            'last_query' => $this->wpdb->last_query
        ]);
        
        return $result;
    }

    /**
     * Paginate the results
     * 
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $perPage = 10, int $page = 1): array {
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countQuery = clone $this;
        $countQuery->select(['COUNT(*) as total']);
        $total = (int) $countQuery->first()->total;
        
        // Get paginated results
        $this->limit($perPage);
        $this->offset($offset);
        $results = $this->get();
        
        // Calculate last page
        $lastPage = max(1, ceil($total / $perPage));
        
        // Log pagination info
        Logger::debug('Pagination Query', [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'results_count' => count($results),
            'last_error' => $this->wpdb->last_error,
            'last_query' => $this->wpdb->last_query
        ]);
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage
        ];
    }

    /**
     * Get all results
     * 
     * @return array
     */
    public function get(): array {
        $sql = $this->toSql();
        $results = $this->wpdb->get_results($sql);
        
        // Log the results count
        Logger::debug('Query Results', [
            'count' => count($results),
            'last_error' => $this->wpdb->last_error,
            'last_query' => $this->wpdb->last_query
        ]);

        return $results;
    }

    /**
     * Get the SQL query
     * 
     * @return string
     */
    protected function toSql(): string {
        $sql = [];

        // SELECT
        $sql[] = 'SELECT ' . implode(', ', $this->select);

        // FROM
        $sql[] = 'FROM ' . $this->table;

        // JOIN
        foreach ($this->joins as $join) {
            $sql[] = sprintf(
                '%s JOIN %s ON %s %s %s',
                $join['type'],
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }

        // WHERE
        if (!empty($this->where)) {
            $where = [];
            foreach ($this->where as $index => $condition) {
                $boolean = $index === 0 ? 'WHERE' : $condition['boolean'];
                $column = $condition['column'];
                $operator = strtoupper($condition['operator']);
                $value = $condition['value'];

                if ($operator === 'IN' && is_array($value)) {
                    $placeholders = implode(', ', array_fill(0, count($value), "'%s'"));
                    $where[] = sprintf(
                        "%s %s IN ($placeholders)",
                        $boolean,
                        $column,
                        ...$value
                    );
                } else {
                    $where[] = sprintf(
                        '%s %s %s %s',
                        $boolean,
                        $column,
                        $operator,
                        $this->wpdb->prepare('%s', $value)
                    );
                }
            }
            $sql[] = implode(' ', $where);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = sprintf('%s %s', $order['column'], $order['direction']);
            }
            $sql[] = 'ORDER BY ' . implode(', ', $orders);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql[] = 'LIMIT ' . $this->limit;
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql[] = 'OFFSET ' . $this->offset;
        }

        $finalSql = implode(' ', $sql);
        
        // Log the query and its parameters
        Logger::debug('Executing SQL Query', [
            'sql' => $finalSql,
            'table' => $this->table,
            'select' => $this->select,
            'where' => $this->where,
            'joins' => $this->joins,
            'orderBy' => $this->orderBy,
            'limit' => $this->limit,
            'offset' => $this->offset
        ]);

        return $finalSql;
    }

    /**
     * Get URL parameters and apply them to the query
     * 
     * @param array $mapping Optional mapping of URL parameters to database columns
     *                      e.g. ['search' => 'post_title', 'author' => 'post_author']
     * @return self
     */
    public function urlParams(array $mapping = []): self {
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                // If mapping is provided, only use mapped parameters
                if (!empty($mapping) && !isset($mapping[$key])) {
                    continue;
                }

                // Get the column name from mapping or use the key directly
                $column = $mapping[$key] ?? $key;

                // Skip empty values
                if (empty($value)) {
                    continue;
                }

                // Sanitize the value
                $value = sanitize_text_field($value);

                // Handle special operators in the value
                if (is_string($value) && strpos($value, ':') !== false) {
                    list($operator, $value) = explode(':', $value, 2);
                    $operator = sanitize_text_field($operator);
                    $value = sanitize_text_field($value);
                    $this->where($column, $operator, $value);
                } else {
                    $this->where($column, $value);
                }
            }
        }
        return $this;
    }

    /**
     * Debug the current query by showing all matching posts regardless of status
     * 
     * @return array
     */
    public function debug(): array {
        // Store original where conditions
        $originalWhere = $this->where;
        
        // Clear where conditions
        $this->where = [];
        
        // Get all posts
        $allPosts = $this->get();
        
        // Restore original where conditions
        $this->where = $originalWhere;
        
        // Sanitize sample posts for logging
        $samplePosts = array_slice($allPosts, 0, 5, true);
        $sanitizedSample = array_map(function($post) {
            if (is_object($post)) {
                $post = (array)$post;
            }
            return array_map('sanitize_text_field', $post);
        }, $samplePosts);
        
        // Log debug information
        Logger::debug('Query Debug Information', [
            'total_posts' => count($allPosts),
            'sample_posts' => $sanitizedSample,
            'current_where_conditions' => $this->where,
            'table' => $this->table
        ]);
        
        return $allPosts;
    }
}