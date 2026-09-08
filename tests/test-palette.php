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
