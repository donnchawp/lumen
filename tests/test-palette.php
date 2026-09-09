<?php
/**
 * The palette maths, exercised without WordPress.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

require_once dirname(__DIR__) . '/inc/palette.php';

// The default background must still reproduce the hand-tuned scheme exactly.
// This is the calibration the whole derivation is built around; if it drifts,
// every other background has drifted with it.
lumen_test_set_mods(array());
$dark = lumen_palette();

lumen_assert_same('#0a0a0a', $dark['--bg-primary'], 'dark --bg-primary');
lumen_assert_same('#111111', $dark['--bg-secondary'], 'dark --bg-secondary');
lumen_assert_same('#1a1a1a', $dark['--bg-tertiary'], 'dark --bg-tertiary');
lumen_assert_same('#333333', $dark['--border-hover'], 'dark --border-hover');
lumen_assert_same('#e5e5e5', $dark['--text-primary'], 'dark --text-primary');
lumen_assert_same('#7c7c7c', $dark['--text-muted'], 'dark --text-muted');

require_once dirname(__DIR__) . '/bin/generate-variation.php';

$variation = lumen_build_variation('Dark', '#0a0a0a', '#ffffff');

lumen_assert_same(3, $variation['version'], 'variation is theme.json v3');
lumen_assert_same('Dark', $variation['title'], 'variation title');

// The editor reads settings.color.palette to draw its pickers; style.css reads
// the raw custom properties. A variation has to carry both or the two disagree.
$slugs = array_column($variation['settings']['color']['palette'], 'slug');
lumen_assert_true(in_array('bg-primary', $slugs, true), 'palette exposes bg-primary');
lumen_assert_true(in_array('text-primary', $slugs, true), 'palette exposes text-primary');

lumen_assert_true(
    strpos($variation['styles']['css'], '--bg-primary:#0a0a0a') !== false,
    'variation css carries --bg-primary'
);
lumen_assert_true(
    strpos($variation['styles']['css'], '--text-muted:#7c7c7c') !== false,
    'variation css carries --text-muted'
);

// A generated palette nobody re-checks is worse than a runtime one that checks
// itself, so the generator has to do the checking the request used to do.
lumen_test_set_mods(array());
lumen_assert_same(
    array(),
    lumen_assert_variation_contrast(lumen_palette()),
    'the default palette passes its own contrast check'
);

// A palette the maths never produces, to prove the check can actually fail.
$broken = lumen_palette();
$broken['--text-primary'] = '#1c1c1c';
lumen_assert_true(
    count(lumen_assert_variation_contrast($broken)) > 0,
    'a low-contrast tone is reported'
);

// The checked-in files are generated, so they can silently fall behind a change
// to the maths. This fails the moment they do.
foreach (array('dark' => '#0a0a0a', 'light' => '#ffffff') as $name => $background) {
    $path  = dirname(__DIR__) . '/styles/' . $name . '.json';
    $onDisk = json_decode(file_get_contents($path), true);
    $fresh  = lumen_build_variation($onDisk['title'], $background, '#ffffff');

    lumen_assert_same($fresh, $onDisk, $name . '.json matches the generator');
}

// theme.json's base styles must carry the same custom properties as the dark
// variation. Without them the editor canvas has no palette at all once
// editor-style.css is deleted, and theme.json's own var(--accent) link colour
// resolves to nothing. Compared as strings because both come from the same
// generator output — any divergence means one was hand-edited.
$lumen_theme_json = json_decode(file_get_contents(dirname(__DIR__) . '/theme.json'), true);
$lumen_dark       = json_decode(file_get_contents(dirname(__DIR__) . '/styles/dark.json'), true);

lumen_assert_same(
    $lumen_dark['styles']['css'],
    $lumen_theme_json['styles']['css'],
    'theme.json base palette matches styles/dark.json'
);

// And the same for the presets. settings.color sets defaultPalette and custom
// both to false, so without a palette of its own theme.json leaves the editor's
// colour UI completely empty until the reader opens Browse styles and picks a
// variation — which is not a state anyone would choose deliberately. Copied
// from styles/dark.json rather than typed, the way styles.css above is, so this
// assertion is the only thing keeping the two in step.
lumen_assert_same(
    $lumen_dark['settings']['color']['palette'],
    $lumen_theme_json['settings']['color']['palette'],
    'theme.json colour presets match styles/dark.json'
);

// The third copy of the same eleven values, and until now the untested one:
// style.css declares them at zero specificity as what paints when no global
// stylesheet arrives. Nothing at runtime compares it with the other two, so it
// can drift silently and only be noticed by whoever loads the site with
// WordPress's global styles failing — which is exactly when it matters.
//
// Read as a block rather than by searching the whole file, so a stray
// --bg-primary in a comment or a media query cannot satisfy it. Comments are
// stripped first because the ones inside this block quote hex values.
$lumen_style_css = file_get_contents(dirname(__DIR__) . '/style.css');

preg_match('/:where\(:root\)\s*\{(.*?)\}/s', $lumen_style_css, $lumen_root_block);

$lumen_declared = array();

preg_match_all(
    '/(--[a-z-]+)\s*:\s*(#[0-9a-f]{3,6})\s*;/i',
    preg_replace('!/\*.*?\*/!s', '', isset($lumen_root_block[1]) ? $lumen_root_block[1] : ''),
    $lumen_matches,
    PREG_SET_ORDER
);

foreach ($lumen_matches as $lumen_match) {
    $lumen_declared[$lumen_match[1]] = strtolower($lumen_match[2]);
}

lumen_test_set_mods(array());
$lumen_generated = lumen_palette();

ksort($lumen_generated);
ksort($lumen_declared);

lumen_assert_same(
    $lumen_generated,
    $lumen_declared,
    'style.css :where(:root) matches the generated dark palette'
);
