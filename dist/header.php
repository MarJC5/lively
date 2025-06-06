<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

?>
<!doctype html>
<html class="no-js" <?php language_attributes(); ?>>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Lively CSRF Token -->
    <?php ly_csrf(); ?>
    <!-- WordPress head -->
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

