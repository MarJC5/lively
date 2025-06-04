<?php 

// Prevent direct access.
defined('ABSPATH') or exit;

get_header() 

?>

<div class="container">
    <?php lively('Image', ['media' => 6]); ?>
</div>

<?php get_footer() ?>