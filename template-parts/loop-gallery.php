<?php
/**
 * The gallery loop: photo grid, notes list, pagination.
 *
 * Shared by index.php, archive.php and search.php. The caller is responsible
 * for the surrounding have_posts() check and for the empty-results message,
 * because that wording differs per context.
 *
 * Posts are partitioned after the main query has run, so pagination, post
 * counts and $wp_query->max_num_pages are all left untouched.
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

// The foreach loops below reassign $post so that the_permalink(), the_ID() and
// friends resolve against each card's post. load_template() already globalizes
// $post, but declare it explicitly so that is obvious.
global $post;

$lumen_photo_posts = array();
$lumen_text_posts  = array();

while (have_posts()) {
    the_post();

    if (lumen_is_photo_post()) {
        $lumen_photo_posts[] = $post;
    } else {
        $lumen_text_posts[] = $post;
    }
}
?>

<?php if (!empty($lumen_photo_posts)) : ?>
    <div class="photo-grid">
        <?php foreach ($lumen_photo_posts as $post) : setup_postdata($post); ?>
            <?php
            // Choose orientation-based image size.
            $lumen_metadata = wp_get_attachment_metadata(get_post_thumbnail_id());
            $lumen_size     = 'lumen-grid';

            if (!empty($lumen_metadata['height']) && !empty($lumen_metadata['width'])) {
                if (($lumen_metadata['height'] / $lumen_metadata['width']) > 1.2) {
                    $lumen_size = 'lumen-grid-portrait';
                }
            }

            $lumen_card_class = 'photo-card';

            if ('lumen-grid-portrait' === $lumen_size) {
                $lumen_card_class .= ' photo-card--portrait';
            }
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class($lumen_card_class); ?>>
                <a href="<?php the_permalink(); ?>">
                    <?php
                    // No alt is passed: core uses the alt text set in the Media
                    // Library, which is more descriptive than the post title.
                    the_post_thumbnail($lumen_size, array(
                        'sizes' => '(max-width: 480px) 100vw, (max-width: 768px) 50vw, 400px',
                    ));
                    ?>
                    <div class="photo-card-overlay">
                        <h2 class="photo-card-title"><?php echo esc_html(lumen_get_display_title()); ?></h2>
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

<?php if (!empty($lumen_text_posts)) : ?>
    <section class="text-posts-section">
        <h2 class="text-posts-heading"><?php esc_html_e('Notes & Writings', 'lumen'); ?></h2>
        <div class="text-posts-list">
            <?php foreach ($lumen_text_posts as $post) : setup_postdata($post); ?>
                <a href="<?php the_permalink(); ?>" class="text-post-item">
                    <span class="text-post-title"><?php echo esc_html(lumen_get_display_title()); ?></span>
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
    'prev_text' => esc_html__('← Previous', 'lumen'),
    'next_text' => esc_html__('Next →', 'lumen'),
));
