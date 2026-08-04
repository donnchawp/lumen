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

// Rendering the cards below happens outside the loop, which silently opts this
// file out of two core behaviours that are keyed on in_the_loop():
//
//   1. get_the_post_thumbnail() primes the whole page's attachment posts and
//      metadata in one pass, but only when in_the_loop() is true. Without this
//      call each card pays an uncached get_post() on its attachment plus an
//      uncached _wp_attachment_metadata read, so query count scales with the
//      number of photos rather than staying flat.
//   2. wp_get_loading_optimization_attributes() decides whether an image is
//      likely in the viewport using in_the_loop() && is_main_query(), with a
//      fallback on $wp_query->before_loop. Both are false once the partition
//      loop above has exhausted the query, so every image would default to
//      loading="lazy" with no fetchpriority, including the LCP image. The
//      attributes are therefore set explicitly at the the_post_thumbnail()
//      call below.
//
// Anything else added here that relies on loop state needs the same treatment.
update_post_thumbnail_cache();
?>

<?php if (!empty($lumen_photo_posts)) : ?>
    <?php
    // How many leading images core would exclude from lazy-loading in a normal
    // loop. Read from core rather than hardcoded, so a site filtering the
    // threshold still gets what it asked for.
    $lumen_eager_count = wp_omit_loading_attr_threshold();
    ?>
    <div class="photo-grid">
        <?php foreach ($lumen_photo_posts as $lumen_index => $post) : setup_postdata($post); ?>
            <?php
            list($lumen_size, $lumen_card_class) = lumen_get_grid_image_size();

            // Breakpoints follow where the grid actually changes column count,
            // which is not where the CSS breakpoints are. Below 564px the grid
            // is one column: auto-fill with minmax(250px, 1fr) and a 1rem gap
            // needs 516px of content box for a second column, and the container
            // is the viewport less 3rem of padding. Advertising 50vw from 481px,
            // as this did, told the browser to fetch a ~240px file for a slot
            // 433px wide.
            $lumen_image_attr = array(
                'sizes' => '(max-width: 564px) 100vw, (max-width: 768px) 50vw, 400px',
            );

            // Core's viewport heuristic cannot run here (see the note above the
            // grid), so state the answer directly. loading => false omits the
            // attribute rather than emitting loading="", which is what core
            // does for leading images; anything other than 'lazy' also marks
            // the image as in-viewport for the rest of its calculation.
            if ($lumen_index < $lumen_eager_count) {
                $lumen_image_attr['loading'] = false;

                if (0 === $lumen_index) {
                    $lumen_image_attr['fetchpriority'] = 'high';
                }
            }
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class($lumen_card_class); ?>>
                <a href="<?php the_permalink(); ?>">
                    <?php
                    // No alt is passed: core uses the alt text set in the Media
                    // Library, which is more descriptive than the post title.
                    the_post_thumbnail($lumen_size, $lumen_image_attr);
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
