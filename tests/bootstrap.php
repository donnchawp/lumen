<?php
/**
 * Test bootstrap: WordPress stubs and assertions.
 *
 * The palette maths touches only three WordPress functions, so a full test
 * suite would cost more than it proves. These stubs are the whole dependency.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

define('ABSPATH', __DIR__ . '/');

$GLOBALS['lumen_stub_mods']     = array();
$GLOBALS['lumen_test_failures'] = 0;
$GLOBALS['lumen_test_count']    = 0;

function sanitize_hex_color($color) {
    return preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', (string) $color) ? $color : null;
}

function get_theme_mod($name, $default = false) {
    return array_key_exists($name, $GLOBALS['lumen_stub_mods'])
        ? $GLOBALS['lumen_stub_mods'][$name]
        : $default;
}

function apply_filters($tag, $value) {
    return $value;
}

function lumen_test_set_mods(array $mods) {
    $GLOBALS['lumen_stub_mods'] = $mods;
}

function lumen_assert_same($expected, $actual, $message) {
    $GLOBALS['lumen_test_count']++;

    if ($expected === $actual) {
        return;
    }

    $GLOBALS['lumen_test_failures']++;
    printf(
        "FAIL %s\n  expected: %s\n  actual:   %s\n",
        $message,
        var_export($expected, true),
        var_export($actual, true)
    );
}

function lumen_assert_true($condition, $message) {
    lumen_assert_same(true, (bool) $condition, $message);
}
