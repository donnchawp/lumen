<?php
/**
 * Single Post Template
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<?php while (have_posts()) : the_post(); ?>

<main id="primary" class="site-main">

    <header class="single-header">
        <h1 class="single-title"><?php echo esc_html(lumen_get_display_title()); ?></h1>
        <div class="single-meta">
            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                <?php echo esc_html(get_the_date()); ?>
            </time>
            <?php if (has_category()) : ?>
                <span> &mdash; <?php the_category(', '); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <?php
    // The featured image is the protected content on a photoblog, so it stays
    // hidden until the password is supplied.
    if (has_post_thumbnail() && !post_password_required()) :
    ?>
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
    $lumen_prev = get_previous_post();
    $lumen_next = get_next_post();

    if ($lumen_prev || $lumen_next) :
    ?>
        <nav class="post-navigation" aria-label="<?php esc_attr_e('Post navigation', 'lumen'); ?>">
            <?php
            // Rendered from the objects already fetched above. Using
            // previous_post_link()/next_post_link() here would re-run both
            // adjacent-post queries, and their %title token bypasses the
            // untitled fallback.
            ?>
            <?php if ($lumen_prev) : ?>
                <div class="nav-previous">
                    <span class="nav-label"><?php esc_html_e('Previous', 'lumen'); ?></span>
                    <div class="nav-title">
                        <a href="<?php echo esc_url(get_permalink($lumen_prev)); ?>" rel="prev">
                            <?php echo esc_html(lumen_get_display_title($lumen_prev)); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($lumen_next) : ?>
                <div class="nav-next">
                    <span class="nav-label"><?php esc_html_e('Next', 'lumen'); ?></span>
                    <div class="nav-title">
                        <a href="<?php echo esc_url(get_permalink($lumen_next)); ?>" rel="next">
                            <?php echo esc_html(lumen_get_display_title($lumen_next)); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    ?>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
