<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

get_header(); 

?>

<?php ly('App', ['children' => ly_html(function() { ?>
    <h1><?= __('Recherche', 'lively') ?></h1>
    <?php ly('Form', ['class' => 'form--search', 'method' => 'get', 'children' => ly_html(function() { ?>
        <?php ly('Input', ['type' => 'search', 'name' => 's', 'placeholder' => __('Rechercher', 'lively')]) ?>
    <?php })]) ?>
<?php })]) ?>

<?php get_footer(); ?>
