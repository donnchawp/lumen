<?php
/**
 * Single Post Template
 *
 * @package Lumen
 */

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
            'before' => '<div class="pagination">' . esc_html__('Pages:', 'lumen'),
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
            previous_post_link(
                '<div class="nav-previous"><span class="nav-label">' . esc_html__('Previous', 'lumen') . '</span><div class="nav-title">%link</div></div>',
                '%title'
            );
            next_post_link(
                '<div class="nav-next"><span class="nav-label">' . esc_html__('Next', 'lumen') . '</span><div class="nav-title">%link</div></div>',
                '%title'
            );
            ?>
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
