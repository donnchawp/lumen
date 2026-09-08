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
