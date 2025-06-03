<?php

namespace Lively\Cli;

abstract class Command
{
    protected string $name;
    protected string $description;

    /**
     * Get the command name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the command description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Display an error message
     * 
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        echo "\033[31mError: {$message}\033[0m\n";
    }

    /**
     * Display a success message
     * 
     * @param string $message
     * @return void
     */
    protected function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    /**
     * Display an info message
     * 
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        echo "\033[36m{$message}\033[0m\n";
    }

    abstract public function handle(array $args = []): void;
} 