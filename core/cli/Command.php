<?php

namespace Lively\Core\Cli;

abstract class Command
{
    protected string $name;
    protected string $description;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function error(string $message): void
    {
        echo "\033[31mError: {$message}\033[0m\n";
    }

    protected function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    protected function info(string $message): void
    {
        echo "\033[36m{$message}\033[0m\n";
    }

    abstract public function handle(array $args = []): void;
} 