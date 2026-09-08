<?php
/**
 * Colour derivation.
 *
 * Kept apart from functions.php so bin/generate-variation.php can require it
 * from the CLI. functions.php exits when ABSPATH is undefined, which is
 * correct for a theme file and useless for a generator.
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

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
