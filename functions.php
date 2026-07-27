<?php
/**
 * Lumen Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function lumen_setup() {
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
    
    // Theme JS (empty by default, ready for future use)
    wp_enqueue_script(
        'lumen-script',
        get_template_directory_uri() . '/js/lumen.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'lumen_scripts');

/**
 * Custom Excerpt Length
 */
function lumen_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'lumen_excerpt_length', 999);

/**
 * Add custom class to text-only posts in the loop
 */
function lumen_post_classes($classes) {
    if (!has_post_thumbnail()) {
        $classes[] = 'text-only-post';
    }
    return $classes;
}
add_filter('post_class', 'lumen_post_classes');

/**
 * Customizer: Add theme options
 */
function lumen_customize_register($wp_customize) {
    // Site Title Color (already handled by CSS but good to expose)
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
 * Add async/defer to scripts
 */
function lumen_script_loader_tag($tag, $handle) {
    if ('lumen-script' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'lumen_script_loader_tag', 10, 2);
