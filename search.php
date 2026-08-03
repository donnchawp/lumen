<?php
/**
 * Search Results Template
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Needed for the result count below.
global $wp_query;
?>

<main id="primary" class="site-main">

<?php if (have_posts()) : ?>

    <header class="archive-header">
        <h1 class="archive-title">
            <?php
            printf(
                /* translators: %s: Search query. */
                esc_html__('Search results for %s', 'lumen'),
                '<span class="search-term">' . esc_html(get_search_query(false)) . '</span>'
            );
            ?>
        </h1>
        <p class="archive-description">
            <?php
            printf(
                esc_html(
                    /* translators: %s: Number of results. */
                    _n('%s result', '%s results', (int) $wp_query->found_posts, 'lumen')
                ),
                esc_html(number_format_i18n($wp_query->found_posts))
            );
            ?>
        </p>
    </header>

    <?php get_template_part('template-parts/loop', 'gallery'); ?>

<?php else : ?>

    <div class="no-posts">
        <h1>
            <?php
            printf(
                /* translators: %s: Search query. */
                esc_html__('No results for %s', 'lumen'),
                '<span class="search-term">' . esc_html(get_search_query(false)) . '</span>'
            );
            ?>
        </h1>
        <p><?php esc_html_e('Try a different word, or browse the gallery.', 'lumen'); ?></p>
        <?php get_search_form(); ?>
    </div>

<?php endif; ?>

</main>

<?php get_footer(); ?>
