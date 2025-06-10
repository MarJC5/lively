<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

get_header();

?>

<?php ly('App', ['children' => ly_html(function () { ?>
    <h1><?= __('Oups !', 'lively') ?></h1>
    <p><?= __('La page que vous recherchez n\'existe pas', 'lively') ?></p>
    <a href="<?= home_url() ?>" class="btn"><?= __('Revenir sur la page d\'accueil', 'lively') ?></a>
<?php })]) ?>

<?php get_footer(); ?>