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
 * The page frame, mirroring style.css.
 *
 * lumen_get_grid_bands() works out where the grid changes column count so the
 * sizes attribute can describe it. That calculation is only as true as these
 * numbers are, so changing the frame in style.css means changing these to
 * match.
 *
 * Most of it is not mirrored at all any more. LUMEN_SITE_MAX_WIDTH and
 * LUMEN_CONTENT_WIDTH are emitted as --site-max-width and --reading-width by
 * lumen_scripts(), so style.css consumes them rather than repeating them, and
 * the widths that used to be written out at a dozen selectors are now derived
 * from those two.
 *
 * What genuinely has to agree is the padding and the two breakpoints.
 * --page-padding stays in style.css because it is a rem value, and turning it
 * into pixels here would break for anyone running a larger default font size;
 * LUMEN_GRID_PAGE_PADDING is its pixel equivalent at the default root size,
 * which is the assumption the band maths already rests on. A media query cannot
 * read a custom property at all, so the breakpoints stay written out in both.
 */
const LUMEN_SITE_MAX_WIDTH    = 1400; // --site-max-width.
const LUMEN_CONTENT_WIDTH     = 700;  // --reading-width.
const LUMEN_GRID_PAGE_PADDING = 48;   // 2 x --page-padding.
const LUMEN_GRID_GAP          = 24;   // 1.5rem, above 768px.
const LUMEN_GRID_GAP_NARROW   = 16;   // 1rem, at 768px and below.
const LUMEN_GRID_NARROW_BP    = 768;  // At and below this the grid runs full bleed.
const LUMEN_GRID_ONE_COL_BP   = 480;  // At and below this the grid is forced to one column.

// What .site-main leaves for the grid once it has paid its own padding.
const LUMEN_GRID_MAX_CONTENT  = LUMEN_SITE_MAX_WIDTH - LUMEN_GRID_PAGE_PADDING;

/**
 * The palette the theme was designed around.
 *
 * Every colour below is derived from the Customizer's background colour rather
 * than hardcoded, but these values are what that derivation is calibrated
 * against: at the default background it reproduces them exactly, and at any
 * other background it transposes the same relationships. Changing one here
 * changes the whole scheme, at every background, which is the point of keeping
 * them in one place.
 *
 * The surface fractions say how far each surface sits from the background
 * toward the opposite pole. 0.029 of the way from #0a0a0a to white is #111111,
 * 0.065 is #1a1a1a and 0.167 is #333333, which is the dark palette exactly.
 */
const LUMEN_BG_DEFAULT = '#0a0a0a';

const LUMEN_SURFACE_MIX = array(
    '--bg-secondary' => 0.029,
    '--bg-tertiary'  => 0.065,
    '--border'       => 0.065,
    '--border-hover' => 0.167,
);

/**
 * Each text tone, and the surface it has to survive on.
 *
 * The surface is the darkest one the tone is actually painted on in style.css,
 * not the page background: --text-primary reaches --bg-tertiary on the search
 * and comment submit buttons, and --text-muted reaches --bg-secondary on the
 * note row date and the search placeholder. Deriving a tone against the page
 * background alone is what let the shipped --text-muted sit at 4.74:1 there
 * and only 4.52:1 where it is really used, with no margin left for the
 * background moving.
 */
const LUMEN_TEXT_REFERENCE = array(
    '--text-primary'   => array('#e5e5e5', '--bg-tertiary'),
    '--text-secondary' => array('#888888', '--bg-primary'),
    '--text-muted'     => array('#7c7c7c', '--bg-secondary'),
);

/** WCAG AA for normal text. No tone is allowed below this on its own surface. */
const LUMEN_MIN_CONTRAST = 4.5;

/**
 * How opaque the photo card overlay's scrim is at its foot.
 *
 * Emitted as --overlay-alpha and used by the gradient in style.css, so this is
 * the value itself rather than a copy of it. lumen_overlay_background() then
 * derives the surface that alpha implies, which is what overlay text is
 * checked against. Softening the scrim therefore re-checks the text against
 * the lighter surface it just created, instead of leaving the two to disagree.
 */
const LUMEN_OVERLAY_ALPHA = 0.9;

/**
 * The lightest the photo card overlay's scrim can composite to.
 *
 * The overlay is a black gradient over arbitrary photo data, so the worst case
 * for anything painted on it is the lightest photo: whatever is left of white
 * once the scrim is laid over it. Overlay text is checked against that, and
 * never against the page background, because the scrim stays dark however
 * light the page gets.
 *
 * @return string Hex colour.
 */
function lumen_overlay_background() {
    return lumen_mix('#ffffff', '#000000', LUMEN_OVERLAY_ALPHA);
}

/**
 * The two grid image sizes.
 *
 * Neither is cropped. A card takes the shape of its own photo, so the only
 * thing a size has to guarantee is width: the height follows from the photo and
 * the card follows from the height. The height bound is set high enough never
 * to bind, which is how you ask WordPress for "this wide, whatever tall".
 *
 * Being uncropped is also what keeps them in one srcset. Core only offers
 * alternatives whose aspect ratio matches the size being rendered, and an
 * uncropped size always carries the original's ratio, so both of these and the
 * original itself are candidates for the same image. That matters more than it
 * looks: an original too small to make the large size still ends up offered at
 * its own width, which is usually wider than the small size.
 */
const LUMEN_GRID_WIDTH       = 800;
const LUMEN_GRID_LARGE_WIDTH = 1400;
const LUMEN_GRID_ANY_HEIGHT  = 9999;

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
        $content_width = LUMEN_CONTENT_WIDTH;
    }

    // Translations. No .mo files ship with the theme; this lets a translation
    // dropped into wp-content/languages/themes, or into a /languages directory
    // added later, be picked up.
    load_theme_textdomain('lumen', get_template_directory() . '/languages');

    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(600, 450, true);

    // Grid images at two widths, uncropped. Which one is asked for depends on
    // how wide the configured columns can get; see lumen_get_grid_image_size().
    // Both stay registered whichever is in use, because they are also each
    // other's srcset candidates. There is no separate portrait size any more:
    // an uncropped width bound already fits either orientation.
    add_image_size('lumen-grid', LUMEN_GRID_WIDTH, LUMEN_GRID_ANY_HEIGHT, false);
    add_image_size('lumen-grid-large', LUMEN_GRID_LARGE_WIDTH, LUMEN_GRID_ANY_HEIGHT, false);
    // The featured image on a single post. Unlike the grid sizes above this one
    // still binds on height, so a tall portrait is generated narrower than the
    // slot it lands in. Widening it means regenerating every attachment, so it
    // is left alone here rather than changed in passing.
    add_image_size('lumen-single', LUMEN_SITE_MAX_WIDTH, 900, false);

    add_theme_support('title-tag');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'script',
        'style',
    ));

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

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'lumen'),
    ));
}
add_action('after_setup_theme', 'lumen_setup');

/**
 * Enqueue Scripts and Styles
 */
function lumen_scripts() {
    wp_enqueue_style(
        'lumen-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );

    // Everything PHP owns that the stylesheet needs, as custom property
    // overrides. style.css carries the same values as var() fallbacks, so the
    // page still renders if this block never arrives, but these are the
    // authoritative copies and the ones the sizes attribute is computed from.
    //
    // The whole colour palette is derived rather than only the accent, because
    // every tone in it is relative to the background the visitor picked.
    $properties = array_merge(
        array(
            '--photo-grid-min' => lumen_get_grid_min_width() . 'px',
            '--site-max-width' => LUMEN_SITE_MAX_WIDTH . 'px',
            '--reading-width'  => LUMEN_CONTENT_WIDTH . 'px',
            '--overlay-alpha'  => LUMEN_OVERLAY_ALPHA,
        ),
        lumen_palette()
    );

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

    $wp_customize->add_setting('lumen_background_color', array(
        'default'           => LUMEN_BG_DEFAULT,
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'lumen_background_color', array(
        'label'       => __('Background Color', 'lumen'),
        'description' => __('The page background. Every other colour in the theme is worked out from it, so a light background gives dark text, borders and panels to match, all held to WCAG AA. The overlay on a photo stays dark either way, because it sits on the photo rather than on the page.', 'lumen'),
        'section'     => 'colors',
        'priority'    => 5,
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
 * The configured background colour.
 *
 * @return string Hex colour.
 */
function lumen_get_background_color() {
    $background = sanitize_hex_color(get_theme_mod('lumen_background_color', LUMEN_BG_DEFAULT));

    return $background ? $background : LUMEN_BG_DEFAULT;
}

/**
 * Every colour custom property, derived from the background colour.
 *
 * The theme is one scheme rather than a light one and a dark one. The surfaces
 * are fixed fractions of the way from the background toward the opposite pole,
 * and the text tones are whatever hits the contrast ratios the original palette
 * had. Both are calibrated so the default background reproduces the hand-tuned
 * values in style.css exactly; a lighter background transposes the same scheme
 * rather than switching to a second one.
 *
 * The overlay is the exception. It is a dark scrim sitting on a photo, not on
 * the page, so its text keeps its own tones and stays light however light the
 * page gets. Without that, a light background would paint dark text on black.
 *
 * @return array<string, string> Custom property name to hex colour.
 */
function lumen_palette() {
    static $cache = array();

    $background = lumen_get_background_color();
    $accent     = sanitize_hex_color(get_theme_mod('lumen_accent_color', '#ffffff'));
    $accent     = $accent ? $accent : '#ffffff';
    $key        = $background . $accent;

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $background = lumen_usable_background($background);
    $palette    = lumen_surfaces($background);

    // The ratio each tone has in the designed palette, measured where it is
    // actually painted, is the thing carried over to the new background. Never
    // below AA, which only bites if a reference tone was itself borderline.
    $reference_surfaces = lumen_surfaces(LUMEN_BG_DEFAULT);

    foreach (LUMEN_TEXT_REFERENCE as $property => $reference) {
        list($reference_hex, $surface) = $reference;

        $palette[$property] = lumen_muted_toward(
            $background,
            $palette[$surface],
            max(
                LUMEN_MIN_CONTRAST,
                lumen_contrast_ratio($reference_hex, $reference_surfaces[$surface])
            )
        );
    }

    // Checked against --bg-secondary, not the page. The accent is painted on
    // both: --bg-primary for the site title, links and focus outlines, and
    // --bg-secondary behind the current pagination item, the focused skip link
    // and note rows on hover. --bg-secondary is always the one shifted a step
    // toward the text, so it is always the worse of the two for anything
    // painted in the text's direction, whichever way round the scheme is.
    // Checking --bg-primary instead used to let a colour land at 4.5:1 there
    // and 4.29:1 where it really sat.
    $palette['--accent'] = lumen_ensure_contrast($accent, $palette['--bg-secondary']);

    // The scrim is dark whatever the page is doing, so the overlay's accent is
    // checked against the scrim and ends up lightened where the page's is
    // darkened. Same colour picked, pushed the other way.
    $palette['--accent-overlay'] = lumen_ensure_contrast($accent, lumen_overlay_background());

    // The date under the overlay title. Fixed rather than derived:
    // LUMEN_OVERLAY_ALPHA was chosen to put exactly this tone at 4.9:1 over the
    // lightest photo the scrim can composite against.
    $palette['--text-overlay'] = LUMEN_TEXT_REFERENCE['--text-secondary'][0];

    $cache[$key] = $palette;

    return $palette;
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
    $format = null;

    if (!is_admin()) {
        if (!empty($post_object->post_password)) {
            $format = apply_filters('protected_title_format', __('Protected: %s'), $post_object);
        } elseif ('private' === get_post_status($post_object)) {
            $format = apply_filters('private_title_format', __('Private: %s'), $post_object);
        }
    }

    // Tested against null rather than truthiness, so a filter that returns an
    // empty format still produces an empty title the way core would.
    return null === $format
        ? $fallback
        : wp_strip_all_tags(sprintf($format, $fallback));
}

/**
 * Relative luminance of a hex colour, per WCAG 2.1.
 *
 * @param string $hex Three or six digit hex colour, with leading #.
 * @return float Luminance between 0 and 1.
 */
function lumen_relative_luminance($hex) {
    $channels  = lumen_hex_to_rgb($hex);
    $weights   = array(0.2126, 0.7152, 0.0722);
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
 * Whether a colour reads as light, meaning dark text belongs on it.
 *
 * Decided by which pole it contrasts with better rather than by a luminance
 * threshold, so there is no cutoff to argue about: the answer is always the one
 * that leaves more contrast to work with.
 *
 * @param string $hex Hex colour.
 * @return bool
 */
function lumen_is_light($hex) {
    return lumen_contrast_ratio($hex, '#000000') > lumen_contrast_ratio($hex, '#ffffff');
}

/**
 * The pole a colour sitting on $background should be pushed toward.
 *
 * @param string $background Hex colour.
 * @return string '#000000' on a light background, '#ffffff' on a dark one.
 */
function lumen_contrast_pole($background) {
    return lumen_is_light($background) ? '#000000' : '#ffffff';
}

/**
 * Mix two colours.
 *
 * @param string $from   Hex colour at $amount 0.
 * @param string $to     Hex colour at $amount 1.
 * @param float  $amount Position between them, 0 to 1.
 * @return string Hex colour.
 */
function lumen_mix($from, $to, $amount) {
    $a = lumen_hex_to_rgb($from);
    $b = lumen_hex_to_rgb($to);

    return sprintf(
        '#%02x%02x%02x',
        (int) round($a[0] + ($b[0] - $a[0]) * $amount),
        (int) round($a[1] + ($b[1] - $a[1]) * $amount),
        (int) round($a[2] + ($b[2] - $a[2]) * $amount)
    );
}

/**
 * Split a hex colour into its channels.
 *
 * @param string $hex Three or six digit hex colour, with or without a leading #.
 * @return array{0: int, 1: int, 2: int} Red, green and blue, 0 to 255.
 */
function lumen_hex_to_rgb($hex) {
    $hex = ltrim((string) $hex, '#');

    if (3 === strlen($hex)) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return array(
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    );
}

/**
 * Push a colour toward whichever pole its background calls for, until it is legible.
 *
 * The accent colour drives the site title, link hovers, focus outlines and the
 * skip link. A colour picker cannot stop someone choosing one that vanishes
 * into the background, so the value is nudged until it is readable. Which way
 * it gets nudged follows the background: toward white on a dark one, toward
 * black on a light one.
 *
 * The hue survives as long as it can, because each step only moves part of the
 * way to the pole and the first step that clears the ratio wins.
 *
 * @param string $hex        Hex colour to adjust.
 * @param string $background Hex colour it will sit on.
 * @param float  $minimum    Target contrast ratio. Default LUMEN_MIN_CONTRAST.
 * @return string Hex colour meeting the ratio, or the pole if the background
 *                is mid-toned enough that nothing else does.
 */
function lumen_ensure_contrast($hex, $background, $minimum = LUMEN_MIN_CONTRAST) {
    if (lumen_contrast_ratio($hex, $background) >= $minimum) {
        return $hex;
    }

    $pole = lumen_contrast_pole($background);

    // Twentieths, keeping the hue as long as possible.
    for ($step = 1; $step <= 20; $step++) {
        $candidate = lumen_mix($hex, $pole, $step / 20);

        if (lumen_contrast_ratio($candidate, $background) >= $minimum) {
            return $candidate;
        }
    }

    // Only reached on a background mid-toned enough that even the pole falls
    // short, which bottoms out around 4.58:1 at the crossover between the two.
    // The pole is the most readable answer available.
    return $pole;
}

/**
 * The most muted colour that still clears a contrast ratio on a given surface.
 *
 * Walks from the pole back toward the page background and keeps the last value
 * that passes, so a tone asked for a low ratio comes out subdued and one asked
 * for a high ratio comes out bright. The ratio, rather than the colour, is what
 * the palette pins down, which is what lets the same three tones be rebuilt
 * against any background.
 *
 * $background and $surface differ because a tone is mixed toward the page but
 * measured where it is painted. Mixing toward the page tints the result with
 * the page's own hue, so a blue-grey site gets blue-grey text rather than
 * neutral grey laid on top of it; measuring against the surface is what keeps
 * it legible on the panel it actually lands on.
 *
 * @param string $background Page background, the direction to mix toward.
 * @param string $surface    Surface the tone is painted on, what it is measured against.
 * @param float  $ratio      Target contrast ratio.
 * @return string Hex colour, or the pole if the target is out of reach.
 */
function lumen_muted_toward($background, $surface, $ratio) {
    $pole = lumen_contrast_pole($background);

    if (lumen_contrast_ratio($pole, $surface) < $ratio) {
        return $pole;
    }

    $best = $pole;

    // 255 steps, so a grey background lands on exact channel values and the
    // default palette comes back out unchanged rather than one step off.
    for ($step = 1; $step <= 255; $step++) {
        $candidate = lumen_mix($pole, $background, $step / 255);

        if (lumen_contrast_ratio($candidate, $surface) < $ratio) {
            break;
        }

        $best = $candidate;
    }

    return $best;
}

/**
 * The surfaces a background implies.
 *
 * @param string $background Hex colour.
 * @return array<string, string> Custom property name to hex colour, including
 *                               --bg-primary itself.
 */
function lumen_surfaces($background) {
    $pole     = lumen_contrast_pole($background);
    $surfaces = array('--bg-primary' => $background);

    foreach (LUMEN_SURFACE_MIX as $property => $amount) {
        $surfaces[$property] = lumen_mix($background, $pole, $amount);
    }

    return $surfaces;
}

/**
 * Whether a background leaves room for a legible palette.
 *
 * Fails only for mid-tones. Contrast against the better pole bottoms out around
 * 4.58:1 where black and white are equally far away, and the surfaces are a
 * step further toward the text again, so there is a band either side of that
 * crossover where nothing painted on a panel can reach AA.
 *
 * @param string $background Hex colour.
 * @return bool
 */
function lumen_background_supports_palette($background) {
    $surfaces = lumen_surfaces($background);
    $pole     = lumen_contrast_pole($background);

    foreach (LUMEN_TEXT_REFERENCE as $reference) {
        if (lumen_contrast_ratio($pole, $surfaces[$reference[1]]) < LUMEN_MIN_CONTRAST) {
            return false;
        }
    }

    return true;
}

/**
 * Move a background out of the mid-tone band, if it is in it.
 *
 * A picked colour that cannot carry legible text is pushed away from the text,
 * deeper into whichever scheme it already leans toward, until it can. The theme
 * already does this to a dark accent colour rather than rendering it
 * unreadable; this is the same bargain applied to the background.
 *
 * The push never crosses the crossover, because it moves away from the text
 * pole rather than toward it, so a background that reads as light stays light.
 *
 * @param string $background Hex colour.
 * @return string Hex colour that supports a legible palette.
 */
function lumen_usable_background($background) {
    if (lumen_background_supports_palette($background)) {
        return $background;
    }

    $away = lumen_is_light($background) ? '#ffffff' : '#000000';

    for ($step = 1; $step <= 255; $step++) {
        $candidate = lumen_mix($background, $away, $step / 255);

        if (lumen_background_supports_palette($candidate)) {
            return $candidate;
        }
    }

    return $away;
}

/**
 * Whether a post's photo should be shown.
 *
 * This is the theme's definition of "this post is a photo": the grid uses it to
 * decide what gets a card, and the single and page templates use it to decide
 * whether to render the featured image. One predicate rather than three, so a
 * post cannot appear as a card in the grid and then render without its photo
 * when you click it.
 *
 * Password-protected posts are excluded: on a photoblog the featured image is
 * the content being protected, so it must not be browsable in the grid or sit
 * above the password form.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return bool
 */
function lumen_is_photo_post($post = null) {
    return has_post_thumbnail($post) && !post_password_required($post);
}

/**
 * The first media library image in a post's content, for posts that have no
 * featured image of their own.
 *
 * Someone who inserts photos into a post without also setting a featured image
 * gets an empty grid and every post in the notes list, because the whole theme
 * keys on has_post_thumbnail(). Rather than teach the grid, the single template
 * and the size chooser each to look somewhere else, this hooks the one value
 * they all derive from: core builds has_post_thumbnail() out of
 * get_post_thumbnail_id(), so filtering the id reaches every one of them, and
 * update_post_thumbnail_cache() primes whatever it returns for free.
 *
 * The featured image always wins. This only runs for a post showing nothing in
 * the grid today, so nothing that renders now changes.
 *
 * @param int              $thumbnail_id The post thumbnail id, 0 when there is none.
 * @param int|WP_Post|null $post         Post id or object, already resolved by core.
 * @return int Attachment id, or 0 to leave the post without a photo.
 */
function lumen_fallback_thumbnail_id($thumbnail_id, $post) {
    if ($thumbnail_id) {
        return $thumbnail_id;
    }

    $post = get_post($post);

    if (!$post) {
        return $thumbnail_id;
    }

    // The same post is asked for its thumbnail several times per card - once to
    // partition it, once to choose a size, once to render - and every one of
    // those would otherwise rescan the content.
    static $cache = array();

    if (isset($cache[$post->ID])) {
        return $cache[$post->ID];
    }

    // Deliberately the stored content rather than the_content(). Running every
    // content filter for each post in a loop just to find an id is far too much
    // work for what it buys, and on this site an mu-plugin strips the first
    // image out of the filtered output, so the raw column is also the more
    // truthful place to look.
    //
    // The pattern is core's own, from wp_filter_content_tags() in media.php.
    // Both editors write the class whenever an image comes from the library:
    // the block editor as wp-image-${id}, the classic editor in
    // get_image_send_to_editor(). An externally hosted image carries no id and
    // is passed over, which is what we want - there is nothing to build a
    // srcset from.
    if (!preg_match('/wp-image-([0-9]+)/i', $post->post_content, $matches)) {
        $cache[$post->ID] = 0;

        return 0;
    }

    $attachment_id = (int) $matches[1];

    // The class outlives the attachment: delete an image from the library and
    // the markup keeps its id. Rendering that would put a broken card in the
    // grid, which is a worse outcome than the note the post is today.
    $cache[$post->ID] = wp_attachment_is_image($attachment_id) ? $attachment_id : 0;

    return $cache[$post->ID];
}
add_filter('post_thumbnail_id', 'lumen_fallback_thumbnail_id', 10, 2);

/**
 * Which registered image size a post's featured image should use in the grid.
 *
 * Orientation used to be decided here, because a portrait photo was given a
 * portrait crop and a taller cell. Nothing is cropped now and a card takes the
 * shape of its photo, so both orientations want the same size and there is
 * nothing left to choose but width.
 *
 * @param int|WP_Post|null $post Optional. Post ID or object. Default global $post.
 * @return string Registered image size name.
 */
function lumen_get_grid_image_size($post = null) {
    $metadata = wp_get_attachment_metadata(get_post_thumbnail_id($post));

    // Both sizes are in the srcset either way, so this only decides the
    // fallback a browser without srcset support gets, and where the browser
    // starts from. Above an 800px column the smaller one would be upscaled, so
    // hand over the large one.
    $large = lumen_get_grid_widest_column() > LUMEN_GRID_WIDTH;

    // The large size only exists for photos big enough to make it that have
    // been regenerated since it was registered. Naming one that was never made
    // sends core to the full-size original in its place, which on a photoblog
    // is several megabytes, so check the attachment actually has it. Falling
    // back to the smaller file, and letting the srcset offer the original where
    // it fits, is the better failure.
    if ($large && !empty($metadata['sizes']['lumen-grid-large'])) {
        return 'lumen-grid-large';
    }

    return 'lumen-grid';
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

    // style.css forces one column at 480px and below whatever the setting is,
    // and the grid runs full bleed there, so the column is the whole viewport.
    $bands = array(
        array(
            'min_vw'  => 0,
            'max_vw'  => LUMEN_GRID_ONE_COL_BP,
            'columns' => 1,
            'gap'     => 0,
            'pad'     => 0,
            'slot'    => LUMEN_GRID_ONE_COL_BP,
        ),
    );

    // The two regimes above it, as [first viewport, last viewport or null, gap,
    // page padding]. The narrow one tightens the gap and spends no padding,
    // because the grid cancels it to reach both edges.
    //
    // The minmax() floor is not among them: it is the configured width in both.
    // Capping it on small screens was overriding the setting on the screens
    // where a big column matters most, so $min_width now governs throughout.
    $regimes = array(
        array(LUMEN_GRID_ONE_COL_BP + 1, LUMEN_GRID_NARROW_BP, LUMEN_GRID_GAP_NARROW, 0),
        array(LUMEN_GRID_NARROW_BP + 1, null, LUMEN_GRID_GAP, LUMEN_GRID_PAGE_PADDING),
    );

    foreach ($regimes as $regime) {
        list($from_vw, $to_vw, $gap, $pad) = $regime;

        for ($vw = $from_vw; ; ) {
            $content = min($vw - $pad, LUMEN_GRID_MAX_CONTENT);
            $columns = max(1, (int) floor(($content + $gap) / ($min_width + $gap)));

            // Where one more column first fits. Null once .site-main has stopped
            // growing, because the count can no longer change after that.
            $next_content = ($columns + 1) * $min_width + $columns * $gap;
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
                'pad'     => $pad,
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
    static $widest = null;

    if (null !== $widest) {
        return $widest;
    }

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
            // Exact where the grid is full bleed, since the column is then the
            // viewport. Where it is not, the page padding is left out rather
            // than subtracted: it is under 10% of a single column, and erring
            // wide is the safe direction.
            $value = '100vw';
        } else {
            $value = sprintf(
                'calc((100vw - %dpx) / %d)',
                $band['pad'] + ($band['columns'] - 1) * $band['gap'],
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
