<?php

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Page;

get_header();

?>

<?php Page::current(function(Page $page) { ?>

<?php }); ?>

<?php get_footer(); ?>