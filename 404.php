<?php
/**
 * 404 Template
 *
 * Without this file a missing URL falls through to index.php and tells an
 * established site "No posts yet".
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

    <div class="no-posts">
        <h1><?php esc_html_e('Page not found', 'lumen'); ?></h1>
        <p><?php esc_html_e('That link is broken or the page has moved. Try a search instead.', 'lumen'); ?></p>
        <?php get_search_form(); ?>
        <p class="no-posts-back">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to the gallery', 'lumen'); ?></a>
        </p>
    </div>

</main>

<?php get_footer(); ?>
