<?php
/**
 * Lumen Theme Functions
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function lumen_setup() {
    global $content_width;

    // Width of the reading column, which is also what oEmbed asks providers for.
    // Embeds render in the column rather than at the wider image breakout, so
    // this stays at the column width. Set here rather than at file scope so a
    // child theme can override it on the same hook.
    if (!isset($content_width)) {
        $content_width = 700;
    }

    // Translations. No .mo files ship with the theme; this lets a translation
    // dropped into wp-content/languages/themes, or into a /languages directory
    // added later, be picked up.
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
    //
    // Both supports are required. add_editor_style() declares 'editor-style'
    // (singular), which is the classic TinyMCE feature; the block editor loads
    // theme styles only when 'editor-styles' (plural) is declared. Without the
    // plural one the stylesheet below is never loaded and this whole block is
    // inert. dark-editor-style tells the editor its canvas is dark so it
    // adjusts its own UI accordingly.
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_theme_support('dark-editor-style');
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
        $accent = lumen_ensure_contrast($accent, lumen_accent_background());

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
 * The background the accent colour is checked for contrast against.
 *
 * The accent is painted on two backgrounds: --bg-primary (#0a0a0a) for the site
 * title, links and focus outlines, and the lighter --bg-secondary (#111111)
 * behind the current pagination item, the focused skip link, and note rows on
 * hover. Checking against the lighter of the two satisfies both, since a light
 * accent only gains contrast on a darker background. Checking against #0a0a0a
 * instead would let a colour land at 4.5:1 there and 4.29:1 on #111111.
 *
 * These values also live in style.css as custom properties. Kept in a function
 * so the duplication has one obvious place to update, because nothing errors if
 * the two drift apart, the contrast maths just quietly stops matching what is
 * rendered.
 *
 * @return string Hex colour, matching --bg-secondary in style.css.
 */
function lumen_accent_background() {
    return '#111111';
}

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
    //
    // The conditions match core exactly, including is_admin(): core prefixes on
    // whether the post HAS a password, not on whether the visitor has entered
    // it, and it does not prefix at all in the admin. Testing
    // post_password_required() instead would drop the prefix the moment the
    // password was accepted, so on a page listing two protected posts sharing
    // one password the titled one would still read "Protected: Sunset" while
    // the untitled one fell back to a bare "Untitled".
    if (!is_admin() && !empty($post_object->post_password)) {
        $format = apply_filters('protected_title_format', __('Protected: %s'), $post_object);

        return wp_strip_all_tags(sprintf($format, $fallback));
    }

    if (!is_admin() && 'private' === get_post_status($post_object)) {
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
 * Lighten a colour until it meets a contrast ratio against a dark background.
 *
 * The accent colour drives the site title, link hovers, focus outlines and the
 * skip link. A dark accent picked in the Customizer would make all of those
 * unreadable on the dark background, and a colour picker cannot prevent it, so
 * the value is nudged toward white until it is legible.
 *
 * Only ever lightens, so $background must be dark. Against a light background
 * every step makes contrast worse and the return value is white, which is the
 * least readable answer available rather than a safe fallback. The theme calls
 * this with lumen_accent_background() and nothing else.
 *
 * @param string $hex        Hex colour to adjust.
 * @param string $background Hex colour it will sit on. Must be dark.
 * @param float  $minimum    Target contrast ratio. Default 4.5 (WCAG AA).
 * @return string Hex colour meeting the ratio against a dark background.
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

    // Not reached against a dark background: the last step mixes to exactly
    // white, which clears any ratio up to 21. Kept so the function still returns
    // a colour rather than null if it is ever called with a light one.
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

/**
 * Which registered image size a post's featured image should use in the grid.
 *
 * Portrait photos get a portrait crop and a taller cell; everything else gets
 * the landscape crop. Deciding that is data logic rather than markup, and it
 * belongs beside lumen_is_photo_post() which answers the related question of
 * whether a post reaches the grid at all.
 *
 * The 1.2 threshold, rather than a plain height > width, keeps nearly square
 * photos in the landscape cell where they crop better.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return array {
 *     @type string $0 Registered image size name.
 *     @type string $1 Card class, including the portrait modifier when it applies.
 * }
 */
function lumen_get_grid_image_size($post = null) {
    $metadata = wp_get_attachment_metadata(get_post_thumbnail_id($post));

    $is_portrait = !empty($metadata['height'])
        && !empty($metadata['width'])
        && ($metadata['height'] / $metadata['width']) > 1.2;

    if ($is_portrait) {
        return array('lumen-grid-portrait', 'photo-card photo-card--portrait');
    }

    return array('lumen-grid', 'photo-card');
}
