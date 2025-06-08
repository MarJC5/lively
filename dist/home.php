<?php

// Prevent direct access.
defined('ABSPATH') or exit;

get_header();

?>

<?php ly('App', [ 'children' => ly_html(function() { ?>
<?php })]) ?>

<?php get_footer(); ?>