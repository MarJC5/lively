<?php

// Prevent direct access.
defined('ABSPATH') or exit;

get_header()

?>

<div class="container">
    <?php lively('Badge', ['label' => 'New', 'icon' => ['name' => 'tick']]); ?>
</div>

<?php get_footer() ?>