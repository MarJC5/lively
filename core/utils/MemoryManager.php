<?php

namespace Lively\Core\Utils;

use Lively\Core\View\Renderer;
use Lively\Core\Utils\Logger;

/**
 * Memory management utility for Lively framework
 * Provides tools for monitoring and optimizing memory usage
 */
class MemoryManager {
    /** @var MemoryManager Singleton instance */
    protected static $instance = null;
    
    /** @var int Threshold percentage for automatic cleanup (0-100) */
    protected $autoCleanupThreshold = 70;
    
    /** @var int Time interval in seconds between automatic cleanups */
    protected $cleanupInterval = 300; // 5 minutes
    
    /** @var int Timestamp of last cleanup */
    protected $lastCleanupTime = 0;
    
    /** @var bool Whether automatic cleanup is enabled */
    protected $autoCleanupEnabled = true;
    
    /** @var array Memory usage statistics */
    protected $statistics = [
        'peak_usage' => 0,
        'cleanups_performed' => 0,
        'components_removed' => 0,
        'last_usage_percentage' => 0
    ];
    
    /** @var array Activity scores for components */
    protected $componentActivityScores = [];
    
    /** @var array Last interaction timestamps for components */
    protected $componentLastInteraction = [];
    
    /** @var int Maximum inactivity time in seconds before aggressively cleaning up (default: 30 minutes) */
    protected $maxInactivityTime = 1800;
    
    /** @var int Minimum activity score for component retention during aggressive cleanup */
    protected $minActivityScore = 3;
    
    /** @var bool Whether aggressive cleanup is enabled */
    protected $aggressiveCleanupEnabled = true;
    
    /** @var int Interval between aggressive cleanups in seconds (default: 10 minutes) */
    protected $aggressiveCleanupInterval = 600;
    
    /** @var int Timestamp of last aggressive cleanup */
    protected $lastAggressiveCleanupTime = 0;
    
    /**
     * Private constructor for singleton
     */
    private function __construct() {
        $this->lastCleanupTime = time();
        
        // Register shutdown function to log memory statistics
        register_shutdown_function([$this, 'logMemoryStatistics']);
    }
    
    /**
     * Get singleton instance
     * 
     * @return MemoryManager
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the memory manager
     * Sets up WordPress hooks if in WordPress environment
     * 
     * @return void
     */
    public static function initialize(): void {
        $instance = self::getInstance();
        
        // If in WordPress environment, hook into WordPress actions
        if (function_exists('add_action')) {
            // Monitor memory on each page load
            add_action('init', [$instance, 'monitorMemory']);
            
            // Monitor memory before rendering components
            add_action('lively_before_render', [$instance, 'monitorMemory']);
            
            // Monitor memory after processing AJAX requests
            add_action('wp_ajax_lively_component_action', [$instance, 'monitorMemory'], 5);
            add_action('wp_ajax_nopriv_lively_component_action', [$instance, 'monitorMemory'], 5);
            
            // Register component activity hooks
            add_action('lively_component_interaction', [$instance, 'recordComponentActivity'], 10, 2);
            
            // Auto-cleanup on shutdown
            add_action('shutdown', [$instance, 'performAggressiveCleanupIfNeeded'], 0);
        }
    }
    
    /**
     * Check if cleanup should be performed based on memory usage or time interval
     * 
     * @return bool Whether cleanup is needed
     */
    public function shouldPerformCleanup(): bool {
        if (!$this->autoCleanupEnabled) {
            return false;
        }
        
        // Check if enough time has passed since last cleanup
        $timeSinceLastCleanup = time() - $this->lastCleanupTime;
        if ($timeSinceLastCleanup >= $this->cleanupInterval) {
            return true;
        }
        
        // Check memory usage
        $currentUsage = $this->getMemoryUsagePercentage();
        $this->statistics['last_usage_percentage'] = $currentUsage;
        
        return $currentUsage >= $this->autoCleanupThreshold;
    }
    
    /**
     * Monitor memory usage and perform cleanup if necessary
     * 
     * @return bool Whether cleanup was performed
     */
    public function monitorMemory(): bool {
        // Update peak memory usage
        $peakUsage = memory_get_peak_usage(true);
        if ($peakUsage > $this->statistics['peak_usage']) {
            $this->statistics['peak_usage'] = $peakUsage;
        }
        
        // Check if regular cleanup should be performed
        $performedRegular = false;
        if ($this->shouldPerformCleanup()) {
            $performedRegular = $this->performCleanup();
        }
        
        // Check if aggressive cleanup should be performed
        $performedAggressive = false;
        if ($this->shouldPerformAggressiveCleanup()) {
            $performedAggressive = $this->performAggressiveCleanup();
        }
        
        return $performedRegular || $performedAggressive;
    }
    
    /**
     * Perform component cleanup to free memory
     * 
     * @param int $maxAge Maximum age in seconds for components to be considered stale
     * @return bool Whether cleanup was performed
     */
    public function performCleanup(int $maxAge = 1800): bool {
        $renderer = Renderer::getInstance();
        $componentsRemoved = $renderer->cleanupComponents($maxAge);
        
        if ($componentsRemoved > 0) {
            $this->statistics['cleanups_performed']++;
            $this->statistics['components_removed'] += $componentsRemoved;
            
            Logger::info("Memory cleanup performed", [
                'components_removed' => $componentsRemoved,
                'memory_before' => $this->formatBytes(memory_get_usage(true)),
                'memory_after' => $this->formatBytes(memory_get_usage(true))
            ]);
            
            // Force garbage collection if available
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        // Update last cleanup time
        $this->lastCleanupTime = time();
        
        return $componentsRemoved > 0;
    }
    
    /**
     * Get current memory usage as a percentage of limit
     * 
     * @return float Percentage of memory used (0-100)
     */
    public function getMemoryUsagePercentage(): float {
        $memoryLimit = $this->getMemoryLimitBytes();
        $currentUsage = memory_get_usage(true);
        
        return ($currentUsage / $memoryLimit) * 100;
    }
    
    /**
     * Get memory limit in bytes
     * 
     * @return int Memory limit in bytes
     */
    public function getMemoryLimitBytes(): int {
        $limit = ini_get('memory_limit');
        
        // Convert to bytes
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string
     */
    private function formatBytes($bytes, $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Log memory statistics on shutdown
     * 
     * @return void
     */
    public function logMemoryStatistics(): void {
        // Calculate final memory usage
        $finalUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        
        // Only log if cleanups were performed
        if ($this->statistics['cleanups_performed'] > 0) {
            Logger::info("Memory usage statistics", [
                'peak_usage' => $this->formatBytes($peakUsage),
                'final_usage' => $this->formatBytes($finalUsage),
                'cleanups_performed' => $this->statistics['cleanups_performed'],
                'components_removed' => $this->statistics['components_removed'],
                'last_usage_percentage' => round($this->statistics['last_usage_percentage'], 2) . '%'
            ]);
        }
    }
    
    /**
     * Set the auto cleanup threshold percentage
     * 
     * @param int $percentage Percentage threshold (0-100)
     * @return self
     */
    public function setAutoCleanupThreshold(int $percentage): self {
        $this->autoCleanupThreshold = max(0, min(100, $percentage));
        return $this;
    }
    
    /**
     * Set the auto cleanup interval in seconds
     * 
     * @param int $seconds Interval in seconds
     * @return self
     */
    public function setCleanupInterval(int $seconds): self {
        $this->cleanupInterval = max(60, $seconds); // Minimum 1 minute
        return $this;
    }
    
    /**
     * Enable or disable automatic cleanup
     * 
     * @param bool $enabled Whether automatic cleanup is enabled
     * @return self
     */
    public function setAutoCleanupEnabled(bool $enabled): self {
        $this->autoCleanupEnabled = $enabled;
        return $this;
    }
    
    /**
     * Detect circular references in component tree
     * 
     * @param bool $breakCircularReferences Whether to break detected circular references
     * @return array Information about detected circular references
     */
    public function detectCircularReferences(bool $breakCircularReferences = false): array {
        $renderer = Renderer::getInstance();
        $components = $renderer->getAllComponents();
        $circularRefs = [];
        
        foreach ($components as $component) {
            $visited = [];
            $path = [];
            $this->detectCircularInComponent($component, $visited, $path, $circularRefs);
        }
        
        // Break circular references if requested
        if ($breakCircularReferences && !empty($circularRefs)) {
            foreach ($circularRefs as $ref) {
                $this->breakCircularReference($ref['component'], $ref['path']);
            }
            
            Logger::info("Broke " . count($circularRefs) . " circular references");
        }
        
        return $circularRefs;
    }
    
    /**
     * Detect circular references in a component
     * 
     * @param object $component Component to check
     * @param array $visited Already visited components (IDs)
     * @param array $path Current path in the traversal
     * @param array $circularRefs Output array to store found circular references
     * @return void
     */
    private function detectCircularInComponent($component, array &$visited, array $path, array &$circularRefs): void {
        // Skip non-components or null values
        if (!is_object($component) || !method_exists($component, 'getId')) {
            return;
        }
        
        $id = $component->getId();
        
        // If we've seen this component before in this path, we have a circular reference
        if (in_array($id, $visited)) {
            // Find where in the path this component was first seen
            $startIndex = array_search($id, array_column($path, 'id'));
            
            // Extract the circular path
            $circularPath = array_slice($path, $startIndex);
            $circularPath[] = ['id' => $id, 'class' => get_class($component)];
            
            // Add to the found circular references
            $circularRefs[] = [
                'component' => $component,
                'path' => $circularPath
            ];
            
            Logger::warn("Detected circular reference", [
                'component' => get_class($component),
                'id' => $id,
                'path_length' => count($circularPath)
            ]);
            
            return;
        }
        
        // Add to visited and path
        $visited[] = $id;
        $path[] = ['id' => $id, 'class' => get_class($component)];
        
        // Check children
        if (method_exists($component, 'getAllChildren')) {
            foreach ($component->getAllChildren() as $slot => $children) {
                foreach ($children as $child) {
                    $this->detectCircularInComponent($child, $visited, $path, $circularRefs);
                }
            }
        }
        
        // Check any referenced components
        if (method_exists($component, 'getComponents')) {
            foreach ($component->getComponents() as $refComponent) {
                $this->detectCircularInComponent($refComponent, $visited, $path, $circularRefs);
            }
        }
        
        // If component has other properties that might contain components
        // This is a more aggressive approach but can find hidden circular references
        $refObj = new \ReflectionObject($component);
        foreach ($refObj->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($component);
            
            // If the property is an object and different from the component itself
            if (is_object($value) && $value !== $component) {
                // Only check components, not other objects
                if (method_exists($value, 'getId')) {
                    $this->detectCircularInComponent($value, $visited, $path, $circularRefs);
                }
            }
            
            // Check arrays that might contain components
            if (is_array($value)) {
                foreach ($value as $arrItem) {
                    if (is_object($arrItem) && method_exists($arrItem, 'getId')) {
                        $this->detectCircularInComponent($arrItem, $visited, $path, $circularRefs);
                    }
                }
            }
        }
        
        // Remove from visited and path as we backtrack
        array_pop($path);
        array_pop($visited);
    }
    
    /**
     * Break a circular reference
     * 
     * @param object $component Component with circular reference
     * @param array $path Path of the circular reference
     * @return bool Whether the circular reference was broken
     */
    private function breakCircularReference($component, array $path): bool {
        // Get the last component in the path that creates the circular reference
        $lastInPath = end($path);
        
        // Try to find the reference to break
        $refObj = new \ReflectionObject($component);
        foreach ($refObj->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($component);
            
            // If the property is an object that matches the ID of the first component in the circle
            if (is_object($value) && method_exists($value, 'getId') && $value->getId() === $path[0]['id']) {
                // Break the reference by setting to null
                $property->setValue($component, null);
                
                Logger::info("Broke circular reference", [
                    'component' => get_class($component),
                    'property' => $property->getName(),
                    'referenced' => $path[0]['class']
                ]);
                
                return true;
            }
            
            // Check arrays that might contain the reference
            if (is_array($value)) {
                foreach ($value as $key => $arrItem) {
                    if (is_object($arrItem) && method_exists($arrItem, 'getId') && $arrItem->getId() === $path[0]['id']) {
                        // Break the reference by removing from array
                        unset($value[$key]);
                        $property->setValue($component, $value);
                        
                        Logger::info("Broke circular reference in array", [
                            'component' => get_class($component),
                            'property' => $property->getName(),
                            'array_key' => $key,
                            'referenced' => $path[0]['class']
                        ]);
                        
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Record component activity
     * Used to track which components are actively being used
     * 
     * @param string $componentId The component ID
     * @param string $action The action performed (render, update, etc.)
     * @return void
     */
    public function recordComponentActivity($componentId, $action = 'interaction'): void {
        $now = time();
        
        // Initialize if not exists
        if (!isset($this->componentActivityScores[$componentId])) {
            $this->componentActivityScores[$componentId] = 0;
        }
        
        // Update activity score based on action type
        switch ($action) {
            case 'render':
                $this->componentActivityScores[$componentId] += 1;
                break;
                
            case 'update':
                $this->componentActivityScores[$componentId] += 2;
                break;
                
            case 'interaction':
                $this->componentActivityScores[$componentId] += 3;
                break;
                
            default:
                $this->componentActivityScores[$componentId] += 1;
        }
        
        // Cap the score at a maximum value to prevent unbounded growth
        $this->componentActivityScores[$componentId] = min(100, $this->componentActivityScores[$componentId]);
        
        // Update last interaction time
        $this->componentLastInteraction[$componentId] = $now;
        
        Logger::debug("Component activity recorded", [
            'component_id' => $componentId,
            'action' => $action,
            'score' => $this->componentActivityScores[$componentId]
        ]);
    }
    
    /**
     * Check if aggressive cleanup should be performed
     * Based on time interval and memory pressure
     * 
     * @return bool Whether aggressive cleanup is needed
     */
    public function shouldPerformAggressiveCleanup(): bool {
        if (!$this->aggressiveCleanupEnabled) {
            return false;
        }
        
        $now = time();
        
        // Always perform aggressive cleanup under high memory pressure
        $currentUsage = $this->getMemoryUsagePercentage();
        if ($currentUsage >= 85) { // Higher threshold than regular cleanup
            Logger::warn("High memory pressure detected ($currentUsage%), triggering aggressive cleanup");
            return true;
        }
        
        // Otherwise, check the time interval
        $timeSinceLastCleanup = $now - $this->lastAggressiveCleanupTime;
        return $timeSinceLastCleanup >= $this->aggressiveCleanupInterval;
    }
    
    /**
     * Perform aggressive component cleanup
     * Targets inactive components even if they're technically still "active"
     * 
     * @return bool Whether cleanup was performed
     */
    public function performAggressiveCleanup(): bool {
        $renderer = Renderer::getInstance();
        $now = time();
        $componentsRemoved = 0;
        $componentsPruned = 0;
        
        // Get all active components
        $components = $renderer->getAllComponents();
        
        foreach ($components as $id => $component) {
            // Skip components without activity metrics
            if (!isset($this->componentLastInteraction[$id])) {
                // For components without activity records, consider them as freshly created
                // So we'll give them a chance to be used before cleanup
                $this->componentLastInteraction[$id] = $now;
                $this->componentActivityScores[$id] = 5; // Moderate starting score
                continue;
            }
            
            $lastInteraction = $this->componentLastInteraction[$id];
            $activityScore = $this->componentActivityScores[$id] ?? 0;
            $inactiveTime = $now - $lastInteraction;
            
            // Decay activity score based on inactivity
            if ($inactiveTime > 300) { // 5 minutes inactivity
                // Decay formula: reduce score by 1 for every 5 minutes of inactivity
                $decayFactor = floor($inactiveTime / 300);
                $newScore = max(0, $activityScore - $decayFactor);
                
                if ($newScore !== $activityScore) {
                    $this->componentActivityScores[$id] = $newScore;
                    Logger::debug("Component activity score decayed", [
                        'component_id' => $id,
                        'previous_score' => $activityScore,
                        'new_score' => $newScore,
                        'inactive_time' => $inactiveTime
                    ]);
                }
                
                $activityScore = $newScore;
            }
            
            // Check if component should be removed based on inactivity and score
            if (($inactiveTime > $this->maxInactivityTime && $activityScore < $this->minActivityScore) || 
                ($inactiveTime > $this->maxInactivityTime * 3)) { // Force cleanup after extended inactivity
                
                // Call destroy method if it exists
                if (method_exists($component, 'destroy')) {
                    $component->destroy();
                }
                
                // Clean up activity tracking
                unset($this->componentActivityScores[$id]);
                unset($this->componentLastInteraction[$id]);
                
                $componentsRemoved++;
            } else if ($activityScore < 10 && $inactiveTime > 600) {
                // For less active components, try to prune internal state to reduce memory footprint
                if (method_exists($component, 'prune')) {
                    $component->prune();
                    $componentsPruned++;
                }
            }
        }
        
        // Update last aggressive cleanup time
        $this->lastAggressiveCleanupTime = $now;
        
        if ($componentsRemoved > 0 || $componentsPruned > 0) {
            $this->statistics['cleanups_performed']++;
            $this->statistics['components_removed'] += $componentsRemoved;
            
            Logger::info("Aggressive memory cleanup performed", [
                'components_removed' => $componentsRemoved,
                'components_pruned' => $componentsPruned,
                'memory_before' => $this->formatBytes(memory_get_usage(true)),
                'memory_after' => $this->formatBytes(memory_get_usage(true))
            ]);
            
            // Force garbage collection if available
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Perform aggressive cleanup if needed (called on shutdown)
     * 
     * @return void
     */
    public function performAggressiveCleanupIfNeeded(): void {
        // Only perform cleanup if enabled and enough time has passed
        if ($this->shouldPerformAggressiveCleanup()) {
            $this->performAggressiveCleanup();
        }
    }
    
    /**
     * Configure aggressive cleanup settings
     * 
     * @param bool $enabled Whether aggressive cleanup is enabled
     * @param int $interval Interval between aggressive cleanups in seconds
     * @param int $maxInactivity Maximum inactivity time before cleanup
     * @param int $minScore Minimum activity score to retain components
     * @return self
     */
    public function configureAggressiveCleanup(bool $enabled = true, int $interval = 600, int $maxInactivity = 1800, int $minScore = 3): self {
        $this->aggressiveCleanupEnabled = $enabled;
        $this->aggressiveCleanupInterval = max(60, $interval); // At least 1 minute
        $this->maxInactivityTime = max(300, $maxInactivity); // At least 5 minutes
        $this->minActivityScore = max(0, $minScore);
        
        return $this;
    }
} 