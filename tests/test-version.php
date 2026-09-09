<?php
/**
 * The version, checked across the three places that state it.
 *
 * style.css's header is the one WordPress reads. readme.txt repeats it as
 * Stable tag, and the changelog below that has to have something to say about
 * it. None of the three knows about the other two.
 *
 * This is the mildest of the copies the suite pins, and the easiest to leave
 * behind: a bump is the last thing done before a release, usually in a hurry,
 * and nothing on a running site looks wrong when readme.txt still names the
 * version before. What it costs is the deploy afterwards, where the readme is
 * the only record of what shipped and it is describing a different release.
 *
 * The changelog assertion is the useful half. Comparing the two headers only
 * catches half a bump; requiring the newest entry to name the same version
 * catches a bump nobody wrote down, which is the one that actually happens.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
if (PHP_SAPI !== 'cli') {
    exit;
}

$lumen_version_root = dirname(__DIR__);

/**
 * One `Field: value` line from a file header.
 *
 * Only the first 40 lines are searched. Both headers sit at the top of their
 * file, and a changelog entry further down is free to quote a field name
 * without being mistaken for one.
 *
 * @param string $path  File to read.
 * @param string $field Field name, without the colon.
 * @return string|null
 */
function lumen_test_read_header($path, $field) {
    $head = implode("\n", array_slice(explode("\n", file_get_contents($path)), 0, 40));

    if (preg_match('/^\s*' . preg_quote($field, '/') . ':\s*(\S+)\s*$/m', $head, $matches)) {
        return $matches[1];
    }

    return null;
}

$lumen_style_version = lumen_test_read_header($lumen_version_root . '/style.css', 'Version');
$lumen_readme_stable = lumen_test_read_header($lumen_version_root . '/readme.txt', 'Stable tag');

lumen_assert_true(
    null !== $lumen_style_version,
    'style.css declares a Version'
);

lumen_assert_same(
    $lumen_style_version,
    $lumen_readme_stable,
    'readme.txt Stable tag matches style.css Version'
);

// The newest changelog entry, which is the first one under the heading. Read
// from there rather than from the whole file so the Upgrading section above it,
// which names old versions in prose, cannot answer for the changelog.
$lumen_readme = file_get_contents($lumen_version_root . '/readme.txt');

$lumen_newest_entry = null;

if (preg_match('/^== Changelog ==$(.*)/ms', $lumen_readme, $lumen_changelog)) {
    if (preg_match('/^=\s*(\S+)\s*=$/m', $lumen_changelog[1], $lumen_matches)) {
        $lumen_newest_entry = $lumen_matches[1];
    }
}

lumen_assert_same(
    $lumen_style_version,
    $lumen_newest_entry,
    'the newest changelog entry is for the current version'
);
