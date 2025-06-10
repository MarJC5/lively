<?php

namespace Lively;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Database\Query;
use Lively\Models\PostType;

get_header();

?>

<?php ly('App', ['children' => ly_html(function () { ?>
    <!-- Container -->
    <?php ly('Container', [
        'size' => 'lg',
        'children' => ly_html(function () { ?>
        <h1><?php echo __('Recherche', 'lively'); ?></h1>

        <?php if (isset($_GET['s']) && $_GET['s'] !== '') { ?>
            <p class="m-0"><?php echo __('Résultats pour', 'lively'); ?> <strong><?php echo $_GET['s']; ?></strong></p>
        <?php } ?>

        <!-- Form -->
        <?php ly('Form', [
                'class' => 'form--search my-4',
                'method' => 'get',
                'children' => ly_html(function () { ?>
            <?php ly('Input', ['type' => 'search', 'name' => 's', 'placeholder' => __('Rechercher', 'lively')]) ?>
        <?php })
            ]) ?>
    <?php })
    ]) ?>

    <?php
    // Get current page from URL parameter, default to 1
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = get_option('posts_per_page', 10); // Number of results per page

    $results = (new Query())
        ->table('posts')
        ->select(['ID'])
        ->where('post_status', 'publish')
        ->where('post_type', 'IN', ['page', 'post'])
        ->where('post_title', 'LIKE', '%' . $_GET['s'] . '%')
        ->orderBy('post_date', 'DESC')
        ->paginate($perPage, $currentPage);

    if (!empty($results['data'])) : ?>
        <!-- Container -->
        <?php ly('Container', [
            'size' => 'lg',
            'class' => 'search-results',
            'children' => ly_html(function () use ($results) { ?>
                <div class="grid">
                    <?php PostType::gets(array_column($results['data'], 'ID'), function ($post) { ?>
                        <!-- Card -->
                        <a href="<?php echo $post->url(); ?>">
                            <?php ly('Card', [
                                'id' => 'result-' . $post->id(),
                                'header' => ly_html(function () use ($post) { ?>
                                    <h3><?php echo $post->title(); ?></h3>
                            <?php }),
                                'children' => ly_html(function () use ($post) { ?>
                                <p class="excerpt"><?php echo $post->excerpt(); ?></p>
                            <?php }),
                                'footer' => ly_html(function () use ($post) { ?>
                                <div class="meta">
                                    <time datetime="<?php echo $post->date(); ?>">
                                        <?php echo $post->date(); ?>
                                    </time>
                                </div>
                            <?php })
                            ]) ?>
                        </a>
                    <?php }); ?>
                </div>

                <!-- Pagination -->
                <?php
                ly('Pagination', [
                    'currentPage' => $results['current_page'],
                    'lastPage' => $results['last_page'],
                    'total' => $results['total'],
                    'perPage' => $results['per_page'],
                    'queryParams' => ['s' => $_GET['s']]
                ]);
                ?>
            <?php })
        ]) ?>
        <?php else : ?>
            <!-- Container -->
            <?php ly('Container', ['size' => 'lg', 'children' => ly_html(function () { ?>
                <div class="no-results">
                    <p class="m-0"><?php echo __('No results found.', 'lively'); ?></p>
                </div>
            <?php })]) ?>
        <?php endif; ?>
    <?php })]) ?>

    <?php get_footer(); ?>