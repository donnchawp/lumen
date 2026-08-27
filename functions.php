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
 * Photo grid column width, in pixels.
 *
 * The Customizer setting is the grid's minimum column width, not a column
 * count. The grid fits as many columns of at least this width as the container
 * holds and then stretches them to fill it, so a larger value means fewer and
 * bigger photos, and the count still falls on its own as the viewport narrows.
 *
 * 600 is the ceiling because two 600px columns plus the 24px gap need 1224px
 * and .site-main tops out at a 1352px content box. Anything above roughly 650
 * would leave a single column stretched across the full width at every
 * viewport, which is a different layout rather than a denser one.
 */
const LUMEN_GRID_MIN_WIDTH_DEFAULT = 450;
const LUMEN_GRID_MIN_WIDTH_LOWER   = 200;
const LUMEN_GRID_MIN_WIDTH_UPPER   = 600;

/**
 * Grid geometry, mirroring style.css.
 *
 * lumen_get_grid_bands() works out where the grid changes column count so the
 * sizes attribute can describe it. That calculation is only as true as these
 * numbers are, so changing the grid rules in style.css means changing these to
 * match.
 */
const LUMEN_GRID_MAX_CONTENT  = 1352; // .site-main max-width 1400 less its 2 x 1.5rem padding.
const LUMEN_GRID_PAGE_PADDING = 48;   // 2 x 1.5rem.
const LUMEN_GRID_GAP          = 24;   // 1.5rem, above 768px.
const LUMEN_GRID_GAP_NARROW   = 16;   // 1rem, at 768px and below.
const LUMEN_GRID_NARROW_MIN   = 250;  // Cap on the minmax() floor at 768px and below.
const LUMEN_GRID_NARROW_BP    = 768;
const LUMEN_GRID_ONE_COL_BP   = 480;  // At and below this the grid is forced to one column.

/**
 * The two grid crops.
 *
 * Both are 4:3 (and their portrait counterparts 3:4), which is what puts them
 * in the same srcset: core only offers alternatives whose aspect ratio matches
 * the size being rendered. The pair therefore gives the browser a real choice
 * rather than the single candidate a lone crop leaves it with.
 */
const LUMEN_GRID_CROP_WIDTH        = 800;
const LUMEN_GRID_CROP_HEIGHT       = 600;
const LUMEN_GRID_CROP_LARGE_WIDTH  = 1400;
const LUMEN_GRID_CROP_LARGE_HEIGHT = 1050;

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

    // Grid crops, in landscape and portrait, at two widths. Which one is asked
    // for depends on how wide the configured columns can get; see
    // lumen_get_grid_image_size(). Both widths stay registered whichever is in
    // use, because they are also each other's srcset candidates.
    add_image_size('lumen-grid', LUMEN_GRID_CROP_WIDTH, LUMEN_GRID_CROP_HEIGHT, true);
    add_image_size('lumen-grid-portrait', LUMEN_GRID_CROP_HEIGHT, LUMEN_GRID_CROP_WIDTH, true);
    add_image_size('lumen-grid-large', LUMEN_GRID_CROP_LARGE_WIDTH, LUMEN_GRID_CROP_LARGE_HEIGHT, true);
    add_image_size('lumen-grid-portrait-large', LUMEN_GRID_CROP_LARGE_HEIGHT, LUMEN_GRID_CROP_LARGE_WIDTH, true);
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

    // Customizer values that reach the stylesheet as custom property overrides.
    $properties = array(
        '--photo-grid-min' => lumen_get_grid_min_width() . 'px',
    );

    $accent = sanitize_hex_color(get_theme_mod('lumen_accent_color', '#ffffff'));

    if ($accent) {
        $properties['--accent'] = lumen_ensure_contrast($accent, lumen_accent_background());
    }

    $declarations = '';

    foreach ($properties as $property => $value) {
        $declarations .= $property . ':' . $value . ';';
    }

    wp_add_inline_style('lumen-style', ':root{' . $declarations . '}');

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

    $wp_customize->add_section('lumen_photo_grid', array(
        'title'    => __('Photo Grid', 'lumen'),
        'priority' => 40,
    ));

    $wp_customize->add_setting('lumen_grid_min_width', array(
        'default'           => LUMEN_GRID_MIN_WIDTH_DEFAULT,
        'sanitize_callback' => 'lumen_sanitize_grid_min_width',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('lumen_grid_min_width', array(
        'label'       => __('Column width', 'lumen'),
        'description' => __('The narrowest a photo column may be, in pixels. The grid fits as many columns as will fit and stretches them to fill the row, so a larger number means fewer, bigger photos. 300 gives four across on a wide screen, 350 gives three and 450 gives two.', 'lumen'),
        'section'     => 'lumen_photo_grid',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => LUMEN_GRID_MIN_WIDTH_LOWER,
            'max'  => LUMEN_GRID_MIN_WIDTH_UPPER,
            'step' => 10,
        ),
    ));
}

/**
 * Clamp a photo grid column width to the range the layout and crops support.
 *
 * Used as the setting's sanitize_callback, and again on read, because a value
 * stored before the bounds moved would otherwise escape them.
 *
 * @param mixed $value Raw setting value.
 * @return int Column width in pixels.
 */
function lumen_sanitize_grid_min_width($value) {
    return min(LUMEN_GRID_MIN_WIDTH_UPPER, max(LUMEN_GRID_MIN_WIDTH_LOWER, (int) $value));
}

/**
 * The configured photo grid column width, in pixels.
 *
 * @return int
 */
function lumen_get_grid_min_width() {
    return lumen_sanitize_grid_min_width(
        get_theme_mod('lumen_grid_min_width', LUMEN_GRID_MIN_WIDTH_DEFAULT)
    );
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

    // Which crop to name as the src. Both crops are in the srcset either way,
    // so this only decides the fallback a browser without srcset support gets,
    // and where the browser starts from. Above a 800px column the small crop
    // would be upscaled, so hand over the large one.
    $large = lumen_get_grid_widest_column() > LUMEN_GRID_CROP_WIDTH;
    $size  = $is_portrait ? 'lumen-grid-portrait' : 'lumen-grid';

    // The large crops only exist for photos uploaded or regenerated since they
    // were registered. Asking for one that was never cut makes core fall back
    // to the full-size original, which on a photoblog is several megabytes, so
    // check the attachment actually has it and keep the small crop if not. A
    // soft photo is the better failure.
    if ($large && !empty($metadata['sizes'][$size . '-large'])) {
        $size .= '-large';
    }

    return array($size, $is_portrait ? 'photo-card photo-card--portrait' : 'photo-card');
}

/**
 * The viewport bands the photo grid holds a steady column count across.
 *
 * auto-fill adds a column the moment one more fits, so the rendered column
 * width sawtooths as the viewport grows: it climbs while a count holds, then
 * drops when the next column appears. A single number in the sizes attribute
 * cannot describe that, and gets further from the truth the wider the columns
 * are configured to be. At 600px, for instance, the grid is a single column
 * from 769px to 1271px and two columns above that, so a value taken from the
 * widest viewport would understate the column by half across every laptop.
 *
 * Bands come back in ascending viewport order. 'slot' is the widest the column
 * gets within the band, which is at the top of it.
 *
 * @return array<int, array{min_vw:int, max_vw:?int, columns:int, gap:int, slot:float}>
 */
function lumen_get_grid_bands() {
    static $cache = array();

    $min_width = lumen_get_grid_min_width();

    if (isset($cache[$min_width])) {
        return $cache[$min_width];
    }

    $pad = LUMEN_GRID_PAGE_PADDING;

    // style.css forces one column at 480px and below whatever the setting is.
    $bands = array(
        array(
            'min_vw'  => 0,
            'max_vw'  => LUMEN_GRID_ONE_COL_BP,
            'columns' => 1,
            'gap'     => 0,
            'slot'    => LUMEN_GRID_ONE_COL_BP - $pad,
        ),
    );

    // The two regimes above it, as [first viewport, last viewport or null,
    // minmax() floor, gap]. The narrow one caps the floor and tightens the gap.
    $regimes = array(
        array(
            LUMEN_GRID_ONE_COL_BP + 1,
            LUMEN_GRID_NARROW_BP,
            min($min_width, LUMEN_GRID_NARROW_MIN),
            LUMEN_GRID_GAP_NARROW,
        ),
        array(
            LUMEN_GRID_NARROW_BP + 1,
            null,
            $min_width,
            LUMEN_GRID_GAP,
        ),
    );

    foreach ($regimes as $regime) {
        list($from_vw, $to_vw, $floor, $gap) = $regime;

        for ($vw = $from_vw; ; ) {
            $content = min($vw - $pad, LUMEN_GRID_MAX_CONTENT);
            $columns = max(1, (int) floor(($content + $gap) / ($floor + $gap)));

            // Where one more column first fits. Null once .site-main has stopped
            // growing, because the count can no longer change after that.
            $next_content = ($columns + 1) * $floor + $columns * $gap;
            $max_vw       = $next_content > LUMEN_GRID_MAX_CONTENT
                ? null
                : $next_content + $pad - 1;

            if (null !== $to_vw && (null === $max_vw || $max_vw > $to_vw)) {
                $max_vw = $to_vw;
            }

            $top_content = null === $max_vw
                ? LUMEN_GRID_MAX_CONTENT
                : min($max_vw - $pad, LUMEN_GRID_MAX_CONTENT);

            $bands[] = array(
                'min_vw'  => $vw,
                'max_vw'  => $max_vw,
                'columns' => $columns,
                'gap'     => $gap,
                'slot'    => ($top_content - ($columns - 1) * $gap) / $columns,
            );

            if (null === $max_vw || (null !== $to_vw && $max_vw >= $to_vw)) {
                break;
            }

            $vw = $max_vw + 1;
        }
    }

    $cache[$min_width] = $bands;

    return $bands;
}

/**
 * The widest a photo grid column ever gets above the narrow breakpoint.
 *
 * Narrow viewports are excluded because their widest column belongs to a phone
 * held at a width where the grid has collapsed to one, and sizing every crop
 * for that would hand a large file to every desktop visitor as well.
 *
 * @return float Width in CSS pixels.
 */
function lumen_get_grid_widest_column() {
    $widest = 0;

    foreach (lumen_get_grid_bands() as $band) {
        if ($band['min_vw'] > LUMEN_GRID_NARROW_BP) {
            $widest = max($widest, $band['slot']);
        }
    }

    return $widest;
}

/**
 * The sizes attribute for photo grid images.
 *
 * One clause per band from lumen_get_grid_bands(), narrowest first, which is
 * the order the attribute is evaluated in. Bands below the container cap are
 * expressed as a calc() off the viewport so they stay exact as it grows; the
 * last band is a fixed width, because .site-main has stopped growing by then.
 *
 * @return string
 */
function lumen_get_grid_sizes_attr() {
    $clauses  = array();
    $previous = null;

    foreach (lumen_get_grid_bands() as $band) {
        if (null === $band['max_vw']) {
            $value = sprintf('%dpx', (int) ceil($band['slot']));
        } elseif (1 === $band['columns']) {
            // The page padding is left out rather than subtracted. It is under
            // 10% of a single column and erring wide is the safe direction.
            $value = '100vw';
        } else {
            $value = sprintf(
                'calc((100vw - %dpx) / %d)',
                LUMEN_GRID_PAGE_PADDING + ($band['columns'] - 1) * $band['gap'],
                $band['columns']
            );
        }

        // Neighbouring bands can land on the same value, most often the forced
        // single column below 481px and a single column band just above it.
        // Widen the clause already there rather than repeating it.
        if ($value === $previous) {
            array_pop($clauses);
        }

        $clauses[] = null === $band['max_vw']
            ? $value
            : sprintf('(max-width: %dpx) %s', $band['max_vw'], $value);

        $previous = $value;
    }

    return implode(', ', $clauses);
}
