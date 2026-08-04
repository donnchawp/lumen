<?php
/**
 * Archive Template
 *
 * Same layout as index.php, with an archive title on top.
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main" tabindex="-1">

<header class="archive-header">
    <?php the_archive_title('<h1 class="archive-title">', '</h1>'); ?>
    <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
</header>

<?php if (have_posts()) : ?>

    <?php get_template_part('template-parts/loop', 'gallery'); ?>

<?php else : ?>

    <div class="no-posts">
        <h2><?php esc_html_e('Nothing found', 'lumen'); ?></h2>
        <p><?php esc_html_e('No posts match this archive.', 'lumen'); ?></p>
    </div>

<?php endif; ?>

</main>

<?php get_footer(); ?>
