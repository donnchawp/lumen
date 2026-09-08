<?php
/**
 * Test runner. Exits non-zero when anything failed, so CI or a git hook can
 * use it directly.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/bootstrap.php';

foreach (glob(__DIR__ . '/test-*.php') as $file) {
    require $file;
}

printf("\n%d assertions, %d failures\n", $GLOBALS['lumen_test_count'], $GLOBALS['lumen_test_failures']);

exit($GLOBALS['lumen_test_failures'] > 0 ? 1 : 0);
