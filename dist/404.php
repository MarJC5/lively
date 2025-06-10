<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

get_header();

?>

<?php ly('App', ['children' => ly_html(function () { ?>
    <!-- Container -->
    <?php ly('Container', [
        'size' => 'lg',
        'class' => 'page-404-wrapper',
        'children' => ly_html(function () { ?>
        <h1><?php echo __('Oups !', 'lively'); ?></h1>
        <p><?php echo __('La page que vous recherchez n\'existe pas', 'lively'); ?></p>
        <a href="<?php echo home_url(); ?>" class="btn"><?php echo __('Revenir sur la page d\'accueil', 'lively'); ?></a>
    <?php })]) ?>
<?php })]) ?>

<?php get_footer(); ?>