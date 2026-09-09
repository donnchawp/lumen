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
 * This is the grid's minimum column width, not a column count. The grid fits as
 * many columns of at least this width as the container holds and then stretches
 * them to fill it, so a larger value means fewer and bigger photos, and the
 * count still falls on its own as the viewport narrows.
 *
 * It used to be a Customizer setting with a 200-600 range, clamped on read.
 * The Customizer went with the classic theme, so there is one value now and
 * nothing to sanitise. Much above 650 the grid is a single column stretched
 * across the full width at every viewport, which is a different layout rather
 * than a denser one, which is why the range it used to offer stopped at 600.
 */
const LUMEN_GRID_MIN_WIDTH_DEFAULT = 450;

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

// Still required at runtime even though no colour is derived per request any
// more: LUMEN_OVERLAY_ALPHA lives here, lumen_scripts() emits it, and the tone
// the overlay's date is contrast-checked against is worked out from the same
// constant. bin/generate-variation.php and tests/ load this file themselves.
require_once get_template_directory() . '/inc/palette.php';

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

    // Wide and full alignments. theme.json's layout.wideSize already implies
    // this on a block theme; it is declared anyway so the support does not
    // depend on which of the two WordPress consults.
    //
    // There is no editor stylesheet any more. editor-style.css existed because
    // style.css cannot safely be loaded onto the editing surface — its
    // universal reset and its overflow-x:hidden on body would break it — and
    // the palette still had to reach the canvas. theme.json carries the palette
    // now, and the block styling with it. What no longer previews in the editor
    // is the post-content typography .single-content applies on the front end.
    //
    // register_nav_menus() went at the same time. A block theme has no Menus
    // screen for a registered location to appear on, and core/navigation finds
    // an existing classic menu through the nav_menu_locations theme mod, which
    // is stored whether or not the location is registered.
    add_theme_support('align-wide');
}
add_action('after_setup_theme', 'lumen_setup');

/**
 * Register Blocks
 */
function lumen_register_blocks() {
    // Registered from metadata so block.json stays the single source of truth
    // for the attribute default the renderer reads.
    register_block_type(get_template_directory() . '/blocks/photo-grid');
}
add_action('init', 'lumen_register_blocks');

/**
 * The two paragraphs in the templates that a static file cannot hold.
 *
 * footer.php built its copyright line from wp_date('Y') and the site name, and
 * 404.php linked home through home_url(). A template is static markup, so
 * writing those values into parts/footer.html and templates/404.html would
 * leave the year wrong every January, the site name wrong the first time the
 * site is renamed, and the "back to the gallery" link pointing at the domain
 * root on any install in a subdirectory. It would also drop the only two
 * translated strings the templates have.
 *
 * A binding keeps each paragraph in its template, where the Site Editor can
 * still move and style it, and the string here. What is written into the
 * template file is only what the editor and a stale render show; these are the
 * values that ship. Core passes a bound paragraph through wp_kses_post(), so
 * the anchor below survives.
 */
function lumen_register_bindings() {
    register_block_bindings_source('lumen/copyright', array(
        'label'              => __('Copyright line', 'lumen'),
        'get_value_callback' => 'lumen_get_copyright_line',
    ));

    register_block_bindings_source('lumen/home-link', array(
        'label'              => __('Link home', 'lumen'),
        'get_value_callback' => 'lumen_get_home_link',
    ));
}
add_action('init', 'lumen_register_bindings');

/**
 * @return string The copyright line, as plain text.
 */
function lumen_get_copyright_line() {
    return sprintf(
        /* translators: 1: Current year. 2: Site name. */
        __('© %1$s %2$s. All rights reserved.', 'lumen'),
        wp_date('Y'),
        get_bloginfo('name')
    );
}

/**
 * @return string An anchor to the site's front page.
 */
function lumen_get_home_link() {
    return sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url(home_url('/')),
        esc_html__('Back to the gallery', 'lumen')
    );
}

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

    // The four measurements PHP owns and the stylesheet needs. style.css
    // carries the same values, so the page still renders if this block never
    // arrives, but these are the authoritative copies and the ones the sizes
    // attribute is computed from.
    //
    // No colours. lumen_palette() used to be merged in here, which put the dark
    // palette on every response, after the global stylesheet and at the same
    // specificity — so selecting the Light variation in the Site Editor changed
    // nothing. Colour comes from theme.json and from whichever variation under
    // styles/ is selected; lumen_palette() is what generates those, offline,
    // through bin/generate-variation.php.
    $properties = array(
        '--photo-grid-min' => lumen_get_grid_min_width() . 'px',
        '--site-max-width' => LUMEN_SITE_MAX_WIDTH . 'px',
        '--reading-width'  => LUMEN_CONTENT_WIDTH . 'px',
        '--overlay-alpha'  => LUMEN_OVERLAY_ALPHA,
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
 * The photo grid column width, in pixels.
 *
 * Kept as a function rather than folded into its two callers, because the sizes
 * attribute and --photo-grid-min have to agree on it and a single reader is how
 * that stays true.
 *
 * The three theme mods this and lumen_palette() used to read are deliberately
 * left in the database. Nothing on the request path reads theme_mods_lumen any
 * more — lumen_palette() still would, but only bin/generate-variation.php calls
 * it, against its own stubs — so lumen_background_color, lumen_accent_color and
 * lumen_grid_min_width all survive a switch back to classic Lumen intact.
 *
 * nav_menu_locations, in that same option, does not, and the cause is dropping
 * register_nav_menus(): switch_theme() stashes the locations, then
 * wp_map_nav_menu_locations() intersects them against get_registered_nav_menus(),
 * which is now empty, and _wp_menus_changed() writes the empty result back. So
 * leaving this theme and returning to it loses the menu assignment that both
 * classic Lumen and core/navigation's classic-menu import read. Do the import in
 * the Site Editor before switching themes for any reason.
 *
 * @return int
 */
function lumen_get_grid_min_width() {
    return LUMEN_GRID_MIN_WIDTH_DEFAULT;
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
 * Supply a title for untitled posts.
 *
 * lumen_get_display_title() used to be called from the templates directly.
 * core/post-title offers no fallback hook, so the value is filtered instead.
 * This reaches slightly further than the old function did, because a filter
 * cannot see which template asked: an untitled post now reads "Untitled"
 * wherever core prints a title, including the admin lists and the feeds.
 *
 * Emptiness is tested against the raw post_title rather than $title for the
 * same reason lumen_get_display_title() does. core applies this filter after
 * it has prefixed protected and private posts, so an untitled protected post
 * arrives here as "Protected: ", which is not empty. Testing $title would
 * therefore never fire the fallback for exactly the posts whose prefixing
 * lumen_get_display_title() goes to the trouble of reproducing.
 *
 * No recursion: lumen_get_display_title() only calls get_the_title(), and so
 * only re-enters this filter, on its non-empty branch, which is the branch
 * this function has already returned on.
 *
 * @param string $title The post title, already prefixed by core.
 * @param int    $id    The post ID. 0 when a caller applies the filter without one.
 * @return string
 */
function lumen_filter_empty_title($title, $id = 0) {
    // Without an id there is no raw title to consult and no post to build a
    // fallback from, so the title is passed through untouched.
    if (!$id) {
        return $title;
    }

    if ('' !== trim(wp_strip_all_tags(get_post_field('post_title', $id)))) {
        return $title;
    }

    return lumen_get_display_title($id);
}
add_filter('the_title', 'lumen_filter_empty_title', 10, 2);

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
 * Never render a protected post's featured image.
 *
 * On a photoblog the featured image is the content being protected, so it must
 * not sit above the password form. lumen_is_photo_post() enforced this in
 * single.php and page.php; core/post-featured-image renders whenever a
 * thumbnail exists and takes no such predicate, so the rule moves to the value
 * every caller derives from. Returning an empty string is enough to suppress
 * the whole figure, because the block bails on empty markup.
 *
 * The post id core passes is used rather than the ambient global $post, so
 * that a card rendered outside the loop is judged on its own post, the way
 * lumen_is_photo_post() is.
 *
 * @param string $html    The featured image markup.
 * @param int    $post_id The post the image belongs to.
 * @return string
 */
function lumen_hide_protected_thumbnail($html, $post_id) {
    return post_password_required($post_id) ? '' : $html;
}
add_filter('post_thumbnail_html', 'lumen_hide_protected_thumbnail', 10, 2);

/**
 * Keep the comment reply title at h2.
 *
 * comments.php passed these two strings to comment_form() itself.
 * core/post-comments-form exposes no heading level, so without this the title
 * reverts to core's h3, and that h3 skips a level: core/comments-title renders
 * nothing until a post has its first comment, so on an uncommented post the
 * reply title would follow the h1 post title with no h2 between them.
 *
 * @param array $defaults The comment form defaults.
 * @return array
 */
function lumen_comment_form_heading($defaults) {
    $defaults['title_reply_before'] = '<h2 id="reply-title" class="comment-reply-title">';
    $defaults['title_reply_after']  = '</h2>';

    return $defaults;
}
add_filter('comment_form_defaults', 'lumen_comment_form_heading');

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
