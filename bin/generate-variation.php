<?php
/**
 * Generate a style variation from a background and an accent colour.
 *
 * Lumen used to derive its palette on every request. It now derives it here,
 * once, and ships the result. The maths is unchanged: what moved is when it
 * runs. Regenerate with:
 *
 *   php bin/generate-variation.php Dark  '#0a0a0a' '#ffffff' > styles/dark.json
 *   php bin/generate-variation.php Light '#ffffff' '#ffffff' > styles/light.json
 *
 * Not shipped with the theme. It is a development tool, and it is the reason
 * a third variation costs one command rather than a hand re-tune.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once dirname(__DIR__) . '/inc/palette.php';

/**
 * Human-readable names for the palette's custom properties.
 *
 * The editor shows these in its colour picker, so they say what the tone is
 * for rather than repeating the custom property name at the reader.
 */
const LUMEN_PALETTE_LABELS = array(
    '--bg-primary'     => 'Background',
    '--bg-secondary'   => 'Background, raised',
    '--bg-tertiary'    => 'Background, panel',
    '--border'         => 'Border',
    '--border-hover'   => 'Border, hover',
    '--text-primary'   => 'Text',
    '--text-secondary' => 'Text, secondary',
    '--text-muted'     => 'Text, muted',
    '--accent'         => 'Accent',
    '--accent-overlay' => 'Accent, on photo',
    '--text-overlay'   => 'Text, on photo',
);

/**
 * Every text tone checked against the surface it is actually painted on.
 *
 * LUMEN_TEXT_REFERENCE names that surface per tone, and it is not always the
 * page background: --text-muted is painted on --bg-secondary, so checking it
 * against --bg-primary would pass a tone that fails where it is really used.
 *
 * @param array<string,string> $palette Custom property name to hex colour.
 * @return string[] Failure descriptions, empty when the palette passes.
 */
function lumen_assert_variation_contrast(array $palette) {
    $failures = array();

    foreach (LUMEN_TEXT_REFERENCE as $property => $reference) {
        $surface = $reference[1];
        $ratio   = lumen_contrast_ratio($palette[$property], $palette[$surface]);

        if ($ratio < LUMEN_MIN_CONTRAST) {
            $failures[] = sprintf(
                '%s on %s is %.2f:1, below %.1f:1',
                $property,
                $surface,
                $ratio,
                LUMEN_MIN_CONTRAST
            );
        }
    }

    return $failures;
}

/**
 * Build one style variation.
 *
 * A variation is its palette and nothing else. settings.color.palette is what
 * the editor's colour pickers read, and WordPress emits one
 * --wp--preset--color--<slug> from each entry; theme.json aliases those onto the
 * --bg-primary names style.css is written against, once, so a variation has no
 * second copy of its own colours to keep in step.
 *
 * The earlier version of this function wrote a styles.css block as well, on the
 * grounds that rewriting 450 lines of style.css to say
 * --wp--preset--color--bg-primary would gain nothing. That was true, and it was
 * the wrong pair to choose between: aliasing in one place costs eleven lines and
 * means a colour edited in the Site Editor actually paints, which the duplicated
 * block prevented — the theme's own styles.css always won.
 *
 * @param string $title      Variation name shown in Browse styles.
 * @param string $background Hex colour.
 * @param string $accent     Hex colour.
 * @return array Decoded theme.json variation.
 */
function lumen_build_variation($title, $background, $accent) {
    $GLOBALS['lumen_stub_mods'] = array(
        'lumen_background_color' => $background,
        'lumen_accent_color'     => $accent,
    );

    $presets = array();

    foreach (lumen_palette() as $property => $hex) {
        $slug = ltrim($property, '-');

        $presets[] = array(
            'slug'  => $slug,
            'name'  => isset(LUMEN_PALETTE_LABELS[$property]) ? LUMEN_PALETTE_LABELS[$property] : $slug,
            'color' => $hex,
        );
    }

    return array(
        '$schema'  => 'https://schemas.wp.org/wp/6.6/theme.json',
        'version'  => 3,
        'title'    => $title,
        'slug'     => strtolower($title),
        'settings' => array(
            'color' => array(
                'palette' => $presets,
            ),
        ),
    );
}

// CLI entry point. Skipped when the file is required by the test runner, which
// supplies its own stubs and calls lumen_build_variation() directly.
if (PHP_SAPI === 'cli' && isset($argv) && basename($argv[0]) === 'generate-variation.php') {
    if (!function_exists('sanitize_hex_color')) {
        require __DIR__ . '/palette-stubs.php';
    }

    if ($argc !== 4) {
        fwrite(STDERR, "Usage: generate-variation.php <Title> <#background> <#accent>\n");
        exit(1);
    }

    // lumen_palette() sanitises these the same way, but silently falls back to
    // the defaults when a value fails: correct for a live site, which must
    // never break on bad Customizer data, but wrong here. A typo an operator
    // never sees produces a "Warm" variation built from the dark default and
    // checks it in as if it were real.
    if (!sanitize_hex_color($argv[2])) {
        fwrite(STDERR, "generate-variation.php: '{$argv[2]}' is not a valid hex colour (background)\n");
        exit(1);
    }

    if (!sanitize_hex_color($argv[3])) {
        fwrite(STDERR, "generate-variation.php: '{$argv[3]}' is not a valid hex colour (accent)\n");
        exit(1);
    }

    $variation = lumen_build_variation($argv[1], $argv[2], $argv[3]);
    $failures  = lumen_assert_variation_contrast(lumen_palette());

    if ($failures) {
        fwrite(STDERR, "Refusing to write a variation that fails WCAG AA:\n");
        foreach ($failures as $failure) {
            fwrite(STDERR, '  ' . $failure . "\n");
        }
        exit(1);
    }

    echo json_encode($variation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
}
