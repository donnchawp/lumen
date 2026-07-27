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

    // Block editor: wide and full alignments, previewed with a dedicated
    // stylesheet. style.css is not used here: its universal reset and
    // overflow-x:hidden on body would break the editing surface.
    add_theme_support('align-wide');
    add_editor_style('editor-style.css');

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
    $accent = sanitize_hex_color(get_theme_mod('lumen_accent_color', '#ffffff'));

    if ($accent) {
        $accent = lumen_ensure_contrast($accent, '#0a0a0a');

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
        'label'       => __('Accent Color', 'lumen'),
        'description' => __('Used for the site title, link hovers and focus outlines. Dark colours are lightened automatically so they stay readable on the dark background.', 'lumen'),
        'section'     => 'colors',
    )));
}
add_action('customize_register', 'lumen_customize_register');

/**
 * Display title for a post, falling back to "Untitled" when there is none.
 *
 * Photoblog posts are often untitled. Without a fallback the grid renders an
 * empty <h2> and a link with no accessible name.
 *
 * Emptiness is tested against the raw post_title, not get_the_title(), because
 * core prefixes protected and private posts ("Protected: %s"). Testing the
 * filtered title would see that prefix as content and emit "Protected: " with
 * a dangling colon. The prefix is reapplied to the fallback instead.
 *
 * No date is included: every call site already renders the date in a sibling
 * <time>, so putting it here made the link's accessible name say it twice.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return string Plain-text title, unescaped.
 */
function lumen_get_display_title($post = null) {
    $post_object = get_post($post);

    if (!$post_object) {
        return __('Untitled', 'lumen');
    }

    $raw = trim(wp_strip_all_tags(get_post_field('post_title', $post_object)));

    if ('' !== $raw) {
        return wp_strip_all_tags(get_the_title($post_object));
    }

    $fallback = __('Untitled', 'lumen');

    // Mirror core's get_the_title() prefixing so an untitled protected post
    // still reads "Protected: Untitled".
    if (post_password_required($post_object)) {
        $format = apply_filters('protected_title_format', __('Protected: %s'), $post_object);

        return wp_strip_all_tags(sprintf($format, $fallback));
    }

    if ('private' === get_post_status($post_object)) {
        $format = apply_filters('private_title_format', __('Private: %s'), $post_object);

        return wp_strip_all_tags(sprintf($format, $fallback));
    }

    return $fallback;
}

/**
 * Relative luminance of a hex colour, per WCAG 2.1.
 *
 * @param string $hex Three or six digit hex colour, with leading #.
 * @return float Luminance between 0 and 1.
 */
function lumen_relative_luminance($hex) {
    $hex = ltrim((string) $hex, '#');

    if (3 === strlen($hex)) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $channels = array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );

    $weights = array(0.2126, 0.7152, 0.0722);
    $luminance = 0.0;

    foreach ($channels as $index => $value) {
        $channel = $value / 255;
        $channel = ($channel <= 0.03928)
            ? $channel / 12.92
            : pow(($channel + 0.055) / 1.055, 2.4);

        $luminance += $channel * $weights[$index];
    }

    return $luminance;
}

/**
 * WCAG contrast ratio between two hex colours.
 *
 * @param string $one Hex colour.
 * @param string $two Hex colour.
 * @return float Ratio between 1 and 21.
 */
function lumen_contrast_ratio($one, $two) {
    $a = lumen_relative_luminance($one);
    $b = lumen_relative_luminance($two);

    $lighter = max($a, $b);
    $darker  = min($a, $b);

    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Lighten a colour until it meets a contrast ratio against a background.
 *
 * The accent colour drives the site title, link hovers, focus outlines and the
 * skip link. A dark accent picked in the Customizer would make all of those
 * unreadable on the dark background, and a colour picker cannot prevent it, so
 * the value is nudged toward white until it is legible.
 *
 * @param string $hex        Hex colour to adjust.
 * @param string $background Hex colour it will sit on.
 * @param float  $minimum    Target contrast ratio. Default 4.5 (WCAG AA).
 * @return string Hex colour meeting the ratio, or white if it cannot.
 */
function lumen_ensure_contrast($hex, $background, $minimum = 4.5) {
    if (lumen_contrast_ratio($hex, $background) >= $minimum) {
        return $hex;
    }

    $base = ltrim((string) $hex, '#');

    if (3 === strlen($base)) {
        $base = $base[0] . $base[0] . $base[1] . $base[1] . $base[2] . $base[2];
    }

    $red   = hexdec(substr($base, 0, 2));
    $green = hexdec(substr($base, 2, 2));
    $blue  = hexdec(substr($base, 4, 2));

    // Mix toward white in twentieths, keeping the hue as long as possible.
    for ($step = 1; $step <= 20; $step++) {
        $mix = $step / 20;

        $candidate = sprintf(
            '#%02x%02x%02x',
            (int) round($red + (255 - $red) * $mix),
            (int) round($green + (255 - $green) * $mix),
            (int) round($blue + (255 - $blue) * $mix)
        );

        if (lumen_contrast_ratio($candidate, $background) >= $minimum) {
            return $candidate;
        }
    }

    return '#ffffff';
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
