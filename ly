#!/usr/bin/env php
<?php

// Initialize autoloader
require_once __DIR__ . '/dist/includes/core/utils/Autoloader.php';
$autoloader = \Lively\Core\Utils\Autoloader::getInstance();
$autoloader->register()->registerFrameworkNamespaces(__DIR__ . '/dist');

// Register CLI namespace
$autoloader->registerNamespace('Lively\\Cli', __DIR__ . '/cli');

// Initialize CLI
$cli = \Lively\Cli\Cli::getInstance();

// Register commands
$cli->registerCommand('make:composant', \Lively\Cli\Commands\MakeComposantCommand::class);
$cli->registerCommand('clear:memory', \Lively\Cli\Commands\ClearMemoryCommand::class);

// Run the command
$cli->run(array_slice($argv, 1)); 