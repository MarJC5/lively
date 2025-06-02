<?php

namespace Lively\Core\Cli;

interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function handle(array $args = []): void;
} 