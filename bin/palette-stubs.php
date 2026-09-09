<?php
/**
 * WordPress stubs for running the generator outside WordPress.
 *
 * Duplicates three functions from tests/bootstrap.php rather than sharing it,
 * because that bootstrap also defines assertions and an exit code, which a
 * generator must not inherit.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

$GLOBALS['lumen_stub_mods'] = array();

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
