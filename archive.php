<?php
/**
 * Archive Template
 * Uses the same layout as index.php but shows archive title
 */
get_header();
?>

<main class="site-main">

<?php if (is_category() || is_tag() || is_date() || is_author()) : ?>
    <header style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 1.2rem; text-transform: uppercase; letter-spacing: 0.15em; color: #666; font-weight: 400;">
            <?php
            if (is_category()) {
                single_cat_title();
            } elseif (is_tag()) {
                single_tag_title();
            } elseif (is_author()) {
                printf(__('Posts by %s', 'lumen'), get_the_author());
            } elseif (is_date()) {
                if (is_day()) {
                    printf(__('Posts from %s', 'lumen'), get_the_date());
                } elseif (is_month()) {
                    printf(__('Posts from %s', 'lumen'), get_the_date('F Y'));
                } elseif (is_year()) {
                    printf(__('Posts from %s', 'lumen'), get_the_date('Y'));
                }
            }
            ?>
        </h1>
    </header>
<?php endif; ?>

<?php if (have_posts()) : ?>

    <?php
    $photo_posts = array();
    $text_posts  = array();

    while (have_posts()) {
        the_post();
        if (has_post_thumbnail()) {
            $photo_posts[] = $post;
        } else {
            $text_posts[] = $post;
        }
    }
    ?>

    <?php if (!empty($photo_posts)) : ?>
        <div class="photo-grid">
            <?php foreach ($photo_posts as $post) : setup_postdata($post); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('photo-card'); ?>>
                    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                        <?php
                        $thumbnail_id = get_post_thumbnail_id();
                        $metadata = wp_get_attachment_metadata($thumbnail_id);
                        $size = 'lumen-grid';
                        
                        if (!empty($metadata['height']) && !empty($metadata['width'])) {
                            $ratio = $metadata['height'] / $metadata['width'];
                            if ($ratio > 1.2) {
                                $size = 'lumen-grid-portrait';
                            }
                        }
                        
                        the_post_thumbnail($size, array('alt' => the_title_attribute(array('echo' => false))));
                        ?>
                        <div class="photo-card-overlay">
                            <h2 class="photo-card-title"><?php the_title(); ?></h2>
                            <time class="photo-card-date" datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                                <?php echo esc_html(get_the_date()); ?>
                            </time>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($text_posts)) : ?>
        <section class="text-posts-section">
            <h2 class="text-posts-heading"><?php esc_html_e('Notes & Writings', 'lumen'); ?></h2>
            <div class="text-posts-list">
                <?php foreach ($text_posts as $post) : setup_postdata($post); ?>
                    <a href="<?php the_permalink(); ?>" class="text-post-item">
                        <span class="text-post-title"><?php the_title(); ?></span>
                        <time class="text-post-date" datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                            <?php echo esc_html(get_the_date()); ?>
                        </time>
                    </a>
                <?php endforeach; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    the_posts_pagination(array(
        'mid_size'  => 2,
        'prev_text' => __('&larr; Previous', 'lumen'),
        'next_text' => __('Next &rarr;', 'lumen'),
    ));
    ?>

<?php else : ?>

    <div class="no-posts">
        <h2><?php esc_html_e('Nothing found', 'lumen'); ?></h2>
        <p><?php esc_html_e('No posts match this archive.', 'lumen'); ?></p>
    </div>

<?php endif; ?>

</main>

<?php get_footer(); ?>
