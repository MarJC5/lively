<?php

namespace Lively\Core\Cli;

class Cli
{
    protected static $instance;
    protected $commands = [];
    protected $themeRoot;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get theme root directory (one level up from lively directory)
        $this->themeRoot = dirname(dirname(dirname(__DIR__)));
    }

    /**
     * Get the singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a command
     * 
     * @param string $name
     * @param string $class
     * @return self
     */
    public function registerCommand(string $name, string $class): self
    {
        $this->commands[$name] = $class;
        return $this;
    }

    /**
     * Get a command
     * 
     * @param string $name
     * @return string|null
     */
    public function getCommand(string $name): ?string
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * Get the resource path
     * 
     * @return string
     */
    public function getResourcePath(): string
    {
        return $this->themeRoot . '/resources';
    }

    /**
     * Run a command
     * 
     * @param array $args
     * @return void
     */
    public function run(array $args): void
    {
        $command = $args[0] ?? null;
        $commandArgs = array_slice($args, 1);

        if (!$command) {
            $this->showHelp();
            return;
        }

        $commandClass = $this->getCommand($command);
        if (!$commandClass) {
            echo "Unknown command: {$command}\n";
            return;
        }

        $commandInstance = new $commandClass();
        $commandInstance->handle($commandArgs);
    }

    /**
     * Show the help
     * 
     * @return void
     */
    protected function showHelp(): void
    {
        echo "Usage: php ly <command> [arguments]\n";
        echo "Available commands:\n";
        foreach ($this->commands as $name => $class) {
            $instance = new $class();
            echo "  {$name}  {$instance->getDescription()}\n";
        }
    }
}