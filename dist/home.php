<?php 

defined('ABSPATH') || exit;

get_header() 

?>

<div class="container">
    <?php lively('Counter', ['initialValue' => 10, 'label' => 'Counter']); ?>
</div>

<?php get_footer() ?>