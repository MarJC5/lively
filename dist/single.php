<?php

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Models\Post;

get_header();

?>

<?php Post::current(function(Post $post) { ?>

<?php }); ?>

<?php get_footer(); ?>