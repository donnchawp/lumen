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
 * Build one style variation.
 *
 * The variation carries the palette twice, deliberately. settings.color.palette
 * is what the editor's colour pickers read; styles.css is what style.css reads,
 * because the grid and card rules are written against --bg-primary rather than
 * --wp--preset--color--bg-primary and rewriting 450 lines to gain nothing would
 * be a poor trade.
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

    $palette = lumen_palette();

    $presets      = array();
    $declarations = '';

    foreach ($palette as $property => $hex) {
        $slug = ltrim($property, '-');

        $presets[] = array(
            'slug'  => $slug,
            'name'  => isset(LUMEN_PALETTE_LABELS[$property]) ? LUMEN_PALETTE_LABELS[$property] : $slug,
            'color' => $hex,
        );

        $declarations .= $property . ':' . $hex . ';';
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
        'styles'   => array(
            'css' => ':root{' . $declarations . '}',
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

    echo json_encode(
        lumen_build_variation($argv[1], $argv[2], $argv[3]),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ), "\n";
}
