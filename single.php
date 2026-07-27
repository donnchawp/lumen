<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>

<main class="site-main">

    <header class="single-header">
        <h1 class="single-title"><?php the_title(); ?></h1>
        <div class="single-meta">
            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                <?php echo esc_html(get_the_date()); ?>
            </time>
            <?php if (has_category()) : ?>
                <span> &mdash; <?php the_category(', '); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <?php if (has_post_thumbnail()) : ?>
        <div class="single-featured-image">
            <?php the_post_thumbnail('lumen-single', array('alt' => the_title_attribute(array('echo' => false)))); ?>
        </div>
    <?php endif; ?>

    <div class="single-content">
        <?php the_content(); ?>
        
        <?php
        wp_link_pages(array(
            'before' => '<div class="pagination">' . __('Pages:', 'lumen'),
            'after'  => '</div>',
        ));
        ?>
    </div>

    <nav class="post-navigation">
        <?php
        previous_post_link(
            '<div class="nav-previous"><span class="nav-label">' . __('Previous', 'lumen') . '</span><div class="nav-title">%link</div></div>',
            '%title'
        );
        next_post_link(
            '<div class="nav-next"><span class="nav-label">' . __('Next', 'lumen') . '</span><div class="nav-title">%link</div></div>',
            '%title'
        );
        ?>
    </nav>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
