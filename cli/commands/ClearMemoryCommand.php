<?php

namespace Lively\Cli\Commands;

use Lively\Cli\Command;
use Lively\Core\View\Renderer;

class ClearMemoryCommand extends Command
{
    protected string $name = 'clear:memory';
    protected string $description = 'Clear memory by cleaning up unused components';
    protected string $help = 'This command cleans up unused components from memory to free up resources.';

    /**
     * Handle the command
     * 
     * @param array $args
     */
    public function handle(array $args = []): void
    {
        // Get the renderer instance
        $renderer = Renderer::getInstance();
        
        // Get current memory usage before cleanup
        $beforeUsage = memory_get_usage(true);
        $beforeUsageFormatted = $this->formatBytes($beforeUsage);
        
        // Perform cleanup with a 30-minute timeout (1800 seconds)
        $removed = $renderer->cleanupComponents(1800);
        
        // Get memory usage after cleanup
        $afterUsage = memory_get_usage(true);
        $afterUsageFormatted = $this->formatBytes($afterUsage);
        
        // Calculate memory freed
        $freed = $beforeUsage - $afterUsage;
        $freedFormatted = $this->formatBytes($freed);
        
        // Output results
        $this->info("Memory cleanup completed:");
        $this->info("- Components removed: $removed");
        $this->info("- Memory before: $beforeUsageFormatted");
        $this->info("- Memory after: $afterUsageFormatted");
        $this->info("- Memory freed: $freedFormatted");
    }
    
    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 