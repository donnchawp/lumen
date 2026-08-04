<?php
/**
 * Main Template
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main" tabindex="-1">

<?php
$lumen_posts_page = (int) get_option('page_for_posts');

if (is_home() && !is_front_page() && $lumen_posts_page) :
?>
    <header class="archive-header">
        <h1 class="archive-title"><?php echo esc_html(get_the_title($lumen_posts_page)); ?></h1>
    </header>
<?php endif; ?>

<?php if (have_posts()) : ?>

    <?php get_template_part('template-parts/loop', 'gallery'); ?>

<?php else : ?>

    <div class="no-posts">
        <h2><?php esc_html_e('No posts yet', 'lumen'); ?></h2>
        <p><?php esc_html_e('Start publishing to see your photos here.', 'lumen'); ?></p>
    </div>

<?php endif; ?>

</main>

<?php get_footer(); ?>
