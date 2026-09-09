<?php
/**
 * The page frame, checked across the three files that state it.
 *
 * theme.json owns the two widths. functions.php falls back to them when there is
 * no resolved theme.json to read, and style.css falls back again when the inline
 * block those produce never arrives. Neither fallback is a copy anybody consults
 * in normal running, but both are values a reader would see if the one above
 * them failed, so both have to mean what theme.json means.
 *
 * Nothing at runtime compares them, which is exactly the shape the palette was
 * in before it was tested: three files agreeing by hand until one of them
 * quietly stopped. The failure here is worse than a wrong colour, because it is
 * invisible — lumen_get_grid_bands() computes the sizes attribute from the
 * frame, so a disagreement hands the browser a layout description that does not
 * match the page and it picks the wrong srcset candidate for every photo, with
 * nothing on screen to say so.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

$lumen_root = dirname(__DIR__);

// functions.php cannot be required: it exits without ABSPATH, and defining that
// only gets as far as the first add_action(). The constants are read out of the
// source instead, the way test-palette.php reads style.css.
$lumen_functions = file_get_contents($lumen_root . '/functions.php');

/**
 * One `const NAME = <int>;` from functions.php.
 *
 * @param string $source Contents of functions.php.
 * @param string $name   Constant name.
 * @return int|null
 */
function lumen_test_read_const($source, $name) {
    if (preg_match('/^\s*const\s+' . preg_quote($name, '/') . '\s*=\s*(\d+)\s*;/m', $source, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

/**
 * One `--name: <int>px;` from style.css's :root block.
 *
 * Read from that block rather than the whole file, so a value in a comment or a
 * media query cannot satisfy it. The :where(:root) palette block above it is a
 * different selector and does not match.
 *
 * @param string $source Contents of style.css.
 * @param string $name   Custom property name, without the leading dashes.
 * @return int|null
 */
function lumen_test_read_css_px($source, $name) {
    if (!preg_match('/\n:root\s*\{(.*?)\n\}/s', $source, $block)) {
        return null;
    }

    $declarations = preg_replace('!/\*.*?\*/!s', '', $block[1]);

    if (preg_match('/--' . preg_quote($name, '/') . '\s*:\s*(\d+)px\s*;/', $declarations, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

$lumen_theme_json_layout = json_decode(
    file_get_contents($lumen_root . '/theme.json'),
    true
)['settings']['layout'];

$lumen_style_css = file_get_contents($lumen_root . '/style.css');

// theme.json is the source, so it is the expected value in every pair below
// rather than one more thing being compared to the others.
$lumen_frame = array(
    // [theme.json key, functions.php constant, style.css custom property]
    array('wideSize', 'LUMEN_SITE_MAX_WIDTH', 'site-max-width'),
    array('contentSize', 'LUMEN_CONTENT_WIDTH', 'reading-width'),
);

foreach ($lumen_frame as $lumen_pair) {
    list($lumen_key, $lumen_const, $lumen_property) = $lumen_pair;

    $lumen_expected = (int) rtrim($lumen_theme_json_layout[$lumen_key], 'px');

    lumen_assert_same(
        $lumen_expected,
        lumen_test_read_const($lumen_functions, $lumen_const),
        $lumen_const . ' matches theme.json ' . $lumen_key
    );

    lumen_assert_same(
        $lumen_expected,
        lumen_test_read_css_px($lumen_style_css, $lumen_property),
        '--' . $lumen_property . ' matches theme.json ' . $lumen_key
    );
}

// The band maths is integer pixel arithmetic, so a width theme.json states in
// any other unit would be silently unusable. Stated as a test rather than left
// to lumen_get_layout_width()'s fallback, because falling back correctly is not
// the same as the theme meaning what it says.
foreach (array('wideSize', 'contentSize') as $lumen_key) {
    lumen_assert_true(
        (bool) preg_match('/^\d+px$/', $lumen_theme_json_layout[$lumen_key]),
        'theme.json ' . $lumen_key . ' is a plain pixel value'
    );
}
