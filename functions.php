<?php
/**
 * Lumen Theme Functions
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($content_width)) {
    $content_width = 700;
}

/**
 * Theme Setup
 */
function lumen_setup() {
    // Translations. Bundled .mo files live in /languages.
    load_theme_textdomain('lumen', get_template_directory() . '/languages');

    // Add theme support for featured images
    add_theme_support('post-thumbnails');

    // Set default featured image size
    set_post_thumbnail_size(600, 450, true);

    // Add custom image size for grid
    add_image_size('lumen-grid', 800, 600, true);
    add_image_size('lumen-grid-portrait', 600, 800, true);
    add_image_size('lumen-single', 1400, 900, false);

    // Add theme support for title tag
    add_theme_support('title-tag');

    // RSS feed links in <head>
    add_theme_support('automatic-feed-links');

    // HTML5 support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'script',
        'style',
    ));

    // Add theme support for responsive embeds
    add_theme_support('responsive-embeds');

    // Block editor: wide and full alignments.
    // No add_editor_style() here on purpose. style.css carries a universal
    // reset and overflow-x:hidden on body, which are not safe to load into the
    // editor. Matching editor styles need their own scoped stylesheet.
    add_theme_support('align-wide');

    // Register navigation menu
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'lumen'),
    ));
}
add_action('after_setup_theme', 'lumen_setup');

/**
 * Enqueue Scripts and Styles
 */
function lumen_scripts() {
    // Theme stylesheet
    wp_enqueue_style(
        'lumen-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );

    // Accent colour from the Customizer, applied as a custom property override.
    $accent = get_theme_mod('lumen_accent_color', '#ffffff');
    $accent = sanitize_hex_color($accent);

    if ($accent) {
        wp_add_inline_style(
            'lumen-style',
            ':root{--accent:' . $accent . ';}'
        );
    }

    // Threaded comment replies
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'lumen_scripts');

/**
 * Customizer: Add theme options
 */
function lumen_customize_register($wp_customize) {
    $wp_customize->add_setting('lumen_accent_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'lumen_accent_color', array(
        'label'   => __('Accent Color', 'lumen'),
        'section' => 'colors',
    )));
}
add_action('customize_register', 'lumen_customize_register');

/**
 * Display title for a post, falling back to the date when the title is empty.
 *
 * Photoblog posts are often untitled. Without a fallback the grid renders an
 * empty <h2> and a link with no accessible name.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return string Plain-text title, unescaped.
 */
function lumen_get_display_title($post = null) {
    $title = wp_strip_all_tags(get_the_title($post));

    if ('' !== trim($title)) {
        return $title;
    }

    $date = get_the_date('', $post);

    if ($date) {
        /* translators: %s: Post publication date. */
        return sprintf(__('Untitled, %s', 'lumen'), $date);
    }

    return __('Untitled', 'lumen');
}

/**
 * Whether a post should appear in the photo grid.
 *
 * Password-protected posts are excluded: on a photoblog the featured image is
 * the content being protected, so it must not be browsable in the grid.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return bool
 */
function lumen_is_photo_post($post = null) {
    return has_post_thumbnail($post) && !post_password_required($post);
}
