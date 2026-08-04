<?php
/**
 * Static Page Template
 *
 * Without this file every Page falls through to index.php and renders as a
 * photo card with its content discarded.
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main" tabindex="-1">

<?php while (have_posts()) : the_post(); ?>

    <header class="single-header">
        <h1 class="single-title"><?php echo esc_html(lumen_get_display_title()); ?></h1>
    </header>

    <?php if (has_post_thumbnail() && !post_password_required()) : ?>
        <div class="single-featured-image">
            <?php the_post_thumbnail('lumen-single'); ?>
        </div>
    <?php endif; ?>

    <div class="single-content">
        <?php the_content(); ?>

        <?php
        wp_link_pages(array(
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'lumen'),
            'after'  => '</div>',
        ));
        ?>
    </div>

    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    ?>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
