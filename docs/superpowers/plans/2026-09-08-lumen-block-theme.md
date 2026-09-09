# Lumen 2.0 Block Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert Lumen from a classic PHP theme to a block theme, keeping the photo grid's rendering behaviour and the palette's WCAG guarantees intact.

**Architecture:** The palette maths moves out of the request path into a CLI generator that emits two checked-in style variations. The photo grid keeps its PHP behind a dynamic block, because its partition, image sizing and LCP hints have no block-native equivalent. Everything else becomes block markup in `templates/` and `parts/`, with theme.json taking the styling it expresses well.

**Tech Stack:** WordPress 6.6+, theme.json v3, plain PHP 7.4+ (no Composer, no build step), a hand-rolled PHP assertion harness for tests.

**Spec:** `docs/superpowers/specs/2026-09-08-lumen-block-theme-design.md`

## Global Constraints

- WordPress floor: 6.6. `style.css` header reads `Requires at least: 6.6`.
- PHP floor: 7.4. No Composer, no npm, no build step. The theme ships as source.
- Theme version: 2.0.0.
- Text domain: `lumen`. Every user-facing string is translated.
- `LUMEN_MIN_CONTRAST` is 4.5 (WCAG AA, normal text). No generated tone may sit below it on its own surface.
- Indentation: 4 spaces, PHP; no closing `?>` in PHP-only files.
- Theme files start with `if (!defined('ABSPATH')) { exit; }`. CLI files — everything
  under `bin/` and `tests/` — start with `if (PHP_SAPI !== 'cli') { exit; }` instead.
  The ABSPATH guard cannot work there: `tests/bootstrap.php` defines ABSPATH itself, so
  the guard would exit before the definition. Both express the same rule — nothing in
  the theme runs from a browser that is not part of rendering a page.
- Comments explain *why*, not *what*. The existing codebase sets this bar; match it.
- Commit after every task. Never commit a failing test.

## Discovered during planning

Two facts the spec does not account for. Both are handled below.

1. **There are two theme mods, not one.** `lumen_palette()` reads
   `lumen_background_color` *and* `lumen_accent_color` (default `#ffffff`,
   `functions.php:371-372`). The generator takes both.
2. **`functions.php` cannot be required from the CLI.** It carries the standard
   `if (!defined('ABSPATH')) { exit; }` guard, so requiring it from a script exits
   silently with no output. Task 1 extracts the maths into `inc/palette.php` and
   has the generator define `ABSPATH` before requiring it. This is a better
   structure than the spec's "move the functions to `bin/`": one copy of the
   maths, required by both the theme and the generator.

## Known-good values

Task 4 asserts against these. They were produced by running the current
`lumen_palette()` against each background with the accent left at its `#ffffff`
default. The dark column reproduces the hand-tuned values in `style.css` exactly,
which is the calibration claim in `functions.php:70-73`.

| Property | dark (`#0a0a0a`) | light (`#ffffff`) |
|---|---|---|
| `--bg-primary` | `#0a0a0a` | `#ffffff` |
| `--bg-secondary` | `#111111` | `#f8f8f8` |
| `--bg-tertiary` | `#1a1a1a` | `#eeeeee` |
| `--border` | `#1a1a1a` | `#eeeeee` |
| `--border-hover` | `#333333` | `#d4d4d4` |
| `--text-primary` | `#e5e5e5` | `#212121` |
| `--text-secondary` | `#888888` | `#676767` |
| `--text-muted` | `#7c7c7c` | `#727272` |
| `--accent` | `#ffffff` | `#666666` |
| `--accent-overlay` | `#ffffff` | `#ffffff` |
| `--text-overlay` | `#888888` | `#888888` |

`--accent-overlay` and `--text-overlay` are identical in both because they are
checked against the photo scrim, which stays dark however light the page gets.
That is the intended behaviour, not a bug to fix.

---

### Task 1: Test harness and the extracted palette module

**Files:**
- Create: `tests/bootstrap.php`
- Create: `tests/run.php`
- Create: `tests/test-palette.php`
- Create: `inc/palette.php`
- Modify: `functions.php` — remove the palette functions, require `inc/palette.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `inc/palette.php` defining `lumen_palette(): array<string,string>`,
  `lumen_contrast_ratio(string $one, string $two): float`,
  `lumen_surfaces(string $background): array<string,string>`,
  `lumen_ensure_contrast(string $hex, string $background, float $minimum): string`,
  and the constants `LUMEN_MIN_CONTRAST`, `LUMEN_BG_DEFAULT`,
  `LUMEN_SURFACE_MIX`, `LUMEN_TEXT_REFERENCE`, `LUMEN_OVERLAY_ALPHA`.
  `tests/bootstrap.php` defining `lumen_assert_same($expected, $actual, string $message): void`
  and `lumen_assert_true(bool $condition, string $message): void`.

- [ ] **Step 1: Write the test harness**

`tests/bootstrap.php`:

```php
<?php
/**
 * Test bootstrap: WordPress stubs and assertions.
 *
 * The palette maths touches only three WordPress functions, so a full test
 * suite would cost more than it proves. These stubs are the whole dependency.
 *
 * @package Lumen
 */

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
```

`tests/run.php`:

```php
<?php
/**
 * Test runner. Exits non-zero when anything failed, so CI or a git hook can
 * use it directly.
 *
 * @package Lumen
 */

require __DIR__ . '/bootstrap.php';

foreach (glob(__DIR__ . '/test-*.php') as $file) {
    require $file;
}

printf("\n%d assertions, %d failures\n", $GLOBALS['lumen_test_count'], $GLOBALS['lumen_test_failures']);

exit($GLOBALS['lumen_test_failures'] > 0 ? 1 : 0);
```

- [ ] **Step 2: Write the failing test**

`tests/test-palette.php`:

```php
<?php
/**
 * The palette maths, exercised without WordPress.
 *
 * @package Lumen
 */

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
```

- [ ] **Step 3: Run it and watch it fail**

Run: `php tests/run.php`
Expected: fatal error, `inc/palette.php` does not exist.

- [ ] **Step 4: Create `inc/palette.php`**

Move these from `functions.php` verbatim, keeping every comment:

- Constants `LUMEN_MIN_CONTRAST`, `LUMEN_BG_DEFAULT`, `LUMEN_SURFACE_MIX`, `LUMEN_TEXT_REFERENCE`, `LUMEN_OVERLAY_ALPHA`
- `lumen_overlay_background()`, `lumen_get_background_color()`, `lumen_palette()`,
  `lumen_relative_luminance()`, `lumen_contrast_ratio()`, `lumen_is_light()`,
  `lumen_contrast_pole()`, `lumen_mix()`, `lumen_hex_to_rgb()`,
  `lumen_ensure_contrast()`, `lumen_muted_toward()`, `lumen_surfaces()`,
  `lumen_background_supports_palette()`, `lumen_usable_background()`

Head the file:

```php
<?php
/**
 * Colour derivation.
 *
 * Kept apart from functions.php so bin/generate-variation.php can require it
 * from the CLI. functions.php exits when ABSPATH is undefined, which is
 * correct for a theme file and useless for a generator.
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}
```

- [ ] **Step 5: Require it from `functions.php`**

Replace the removed block with:

```php
require_once get_template_directory() . '/inc/palette.php';
```

- [ ] **Step 6: Run the tests**

Run: `php tests/run.php`
Expected: `6 assertions, 0 failures`

- [ ] **Step 7: Lint and commit**

```bash
php -l inc/palette.php && php -l functions.php
git add tests inc/palette.php functions.php
git commit -m "Extract the palette maths so it can run outside WordPress

bin/generate-variation.php needs to call this code from the CLI, and
functions.php exits when ABSPATH is undefined. One copy, required by both."
```

---

### Task 2: The variation generator

**Files:**
- Create: `bin/generate-variation.php`
- Modify: `tests/test-palette.php` — append the generator's shape assertions

**Interfaces:**
- Consumes: `lumen_palette()`, `lumen_contrast_ratio()`, `lumen_surfaces()` from Task 1.
- Produces: `lumen_build_variation(string $title, string $background, string $accent): array`
  returning the decoded theme.json variation structure. Defined in
  `bin/generate-variation.php`, guarded so requiring the file runs no CLI code.

- [ ] **Step 1: Write the failing test**

Append to `tests/test-palette.php`:

```php
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
```

- [ ] **Step 2: Run it and watch it fail**

Run: `php tests/run.php`
Expected: fatal error, `bin/generate-variation.php` does not exist.

- [ ] **Step 3: Write the generator**

`bin/generate-variation.php`:

```php
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

    $presets     = array();
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
```

Note the `$GLOBALS['lumen_stub_mods']` assignment. `inc/palette.php` reads its
inputs through `get_theme_mod()`, so the generator has to set them where the stub
looks. `bin/palette-stubs.php` and `tests/bootstrap.php` must agree on this global
name; they are the only two places it appears.

- [ ] **Step 4: Add the CLI entry point**

Append to `bin/generate-variation.php`:

```php
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

    echo json_encode(
        lumen_build_variation($argv[1], $argv[2], $argv[3]),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ), "\n";
}
```

Create `bin/palette-stubs.php` with the same three stubs the test bootstrap
defines — `sanitize_hex_color()`, `get_theme_mod()`, `apply_filters()` — copied
verbatim from `tests/bootstrap.php` Steps 1. They are duplicated rather than
shared because the test bootstrap also defines assertions and exit codes, which a
generator must not inherit.

- [ ] **Step 5: Run the tests**

Run: `php tests/run.php`
Expected: `12 assertions, 0 failures`

- [ ] **Step 6: Commit**

```bash
php -l bin/generate-variation.php && php -l bin/palette-stubs.php
git add bin tests/test-palette.php
git commit -m "Add the style variation generator

Emits both the preset palette the editor reads and the raw custom
properties style.css reads, because the two must not disagree."
```

---

### Task 3: Refuse to emit a failing palette

**Files:**
- Modify: `bin/generate-variation.php` — add the assertion
- Modify: `tests/test-palette.php` — append the assertion's tests

**Interfaces:**
- Consumes: `lumen_build_variation()` from Task 2.
- Produces: `lumen_assert_variation_contrast(array $palette): array` returning a
  list of human-readable failure strings, empty when the palette passes.

- [ ] **Step 1: Write the failing test**

Append to `tests/test-palette.php`:

```php
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
```

- [ ] **Step 2: Run it and watch it fail**

Run: `php tests/run.php`
Expected: FAIL, `lumen_assert_variation_contrast()` undefined.

- [ ] **Step 3: Implement the check**

Add to `bin/generate-variation.php`, above `lumen_build_variation()`:

```php
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
```

- [ ] **Step 4: Wire it into the CLI**

In the CLI block, between building and printing:

```php
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
```

- [ ] **Step 5: Run the tests**

Run: `php tests/run.php`
Expected: `14 assertions, 0 failures`

- [ ] **Step 6: Commit**

```bash
git add bin/generate-variation.php tests/test-palette.php
git commit -m "Refuse to generate a variation that fails WCAG AA

The request used to re-check contrast on every page load. Generating
ahead of time moves that duty to the generator or loses it entirely."
```

---

### Task 4: Generate the two variations

**Files:**
- Create: `styles/dark.json`
- Create: `styles/light.json`
- Modify: `tests/test-palette.php` — assert the checked-in files match the maths

**Interfaces:**
- Consumes: the generator from Tasks 2 and 3.
- Produces: `styles/dark.json` and `styles/light.json`, checked in.

- [ ] **Step 1: Confirm the live values first**

Run against the site before generating, because `dark.json` has to reproduce
what inphotos.org renders today:

```
wp theme mod get lumen_background_color --url=https://inphotos.org
wp theme mod get lumen_accent_color --url=https://inphotos.org
```

Blank means unset, so the defaults `#0a0a0a` and `#ffffff` apply and the commands
below are correct as written. Any other value replaces the corresponding argument.

- [ ] **Step 2: Generate both files**

```bash
mkdir -p styles
php bin/generate-variation.php Dark  '#0a0a0a' '#ffffff' > styles/dark.json
php bin/generate-variation.php Light '#ffffff' '#ffffff' > styles/light.json
```

- [ ] **Step 3: Write the drift test**

Append to `tests/test-palette.php`:

```php
// The checked-in files are generated, so they can silently fall behind a change
// to the maths. This fails the moment they do.
foreach (array('dark' => '#0a0a0a', 'light' => '#ffffff') as $name => $background) {
    $path  = dirname(__DIR__) . '/styles/' . $name . '.json';
    $onDisk = json_decode(file_get_contents($path), true);
    $fresh  = lumen_build_variation($onDisk['title'], $background, '#ffffff');

    lumen_assert_same($fresh, $onDisk, $name . '.json matches the generator');
}
```

- [ ] **Step 4: Run the tests**

Run: `php tests/run.php`
Expected: `16 assertions, 0 failures`

- [ ] **Step 5: Spot-check the values**

Run: `grep -o '\-\-text-muted:[^;]*' styles/dark.json`
Expected: `--text-muted:#7c7c7c` — the value `style.css` ships today.

- [ ] **Step 6: Commit**

```bash
git add styles tests/test-palette.php
git commit -m "Generate the dark and light style variations

dark.json reproduces the values style.css ships today, so inphotos.org
looks unchanged across the conversion."
```

---

### Task 5: theme.json

**Files:**
- Create: `theme.json`
- Modify: `style.css` — bump to 2.0.0 and raise the floor to 6.6

**Interfaces:**
- Consumes: the palette slugs from Task 4's variations.
- Produces: `theme.json` v3 declaring settings and block styles. Later tasks
  assume `contentSize` is `var(--reading-width)`'s value and `wideSize` is
  `var(--site-max-width)`'s.

- [ ] **Step 1: Write `theme.json`**

`contentSize` is `700px` and `wideSize` is `1400px`, from `LUMEN_CONTENT_WIDTH`
and `LUMEN_SITE_MAX_WIDTH` (`functions.php:50-51`). `wp_add_inline_style()` emits
the same two numbers as `--reading-width` and `--site-max-width`, so they are
duplicated and must move together. Task 8 Step 4 adds a comment beside the
constants saying so.

```json
{
    "$schema": "https://schemas.wp.org/wp/6.6/theme.json",
    "version": 3,
    "settings": {
        "appearanceTools": true,
        "useRootPaddingAwareAlignments": true,
        "color": {
            "custom": false,
            "customDuotone": false,
            "defaultPalette": false,
            "defaultGradients": false
        },
        "spacing": {
            "units": ["px", "rem", "vh", "vw"]
        },
        "layout": {
            "contentSize": "700px",
            "wideSize": "1400px"
        },
        "typography": {
            "customFontSize": false,
            "fluid": true
        }
    },
    "styles": {
        "color": {
            "background": "var(--bg-primary)",
            "text": "var(--text-primary)"
        },
        "elements": {
            "link": {
                "color": { "text": "var(--accent)" }
            }
        }
    }
}
```

`"custom": false` and `"defaultPalette": false` are deliberate. The palette is
generated and contrast-checked; letting a picker introduce an unchecked colour
would quietly undo the guarantee Task 3 exists to enforce.

- [ ] **Step 2: Bump the theme header**

In `style.css`, change `Version:` to `2.0.0` and `Requires at least:` to `6.6`.

- [ ] **Step 3: Verify WordPress parses it**

Run: `php -r 'json_decode(file_get_contents("theme.json"), true); echo json_last_error_msg(), "\n";'`
Expected: `No error`

- [ ] **Step 4: Commit**

```bash
git add theme.json style.css
git commit -m "Add theme.json and raise the floor to WordPress 6.6

Colour picking is locked to the generated palette: an unchecked custom
colour would undo the contrast guarantee the generator enforces."
```

---

### Task 5b: Give theme.json a base palette the editor can see

Added during execution (controller ruling R7). Task 5's review found that
`theme.json:32` sets link colour to `var(--accent)`, but `--accent` is declared
only in `style.css`'s front-end `:root` and in `lumen_scripts()`'s inline style,
which runs on a front-end-only hook. `editor-style.css` declares seven of the
eleven palette properties and omits that one. Worse, Task 8 deletes
`editor-style.css` outright, after which nothing supplies the palette to the
editing canvas at all — a near-black gallery theme would open white.

theme.json's own `styles.css` is injected into both the editor canvas and the
front end, so putting the palette there fixes both contexts at once.

**Files:**
- Modify: `theme.json` — add `styles.css`
- Modify: `tests/test-palette.php` — append the drift assertion

**Interfaces:**
- Consumes: `styles/dark.json` from Task 4.
- Produces: nothing later tasks call.

- [ ] **Step 1: Write the failing test**

Append to `tests/test-palette.php`:

```php
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
```

- [ ] **Step 2: Run it and watch it fail**

Run: `php tests/run.php`
Expected: FAIL — `theme.json` has no `styles.css` key, so the comparison sees null.

- [ ] **Step 3: Copy the palette across programmatically**

Do not retype the eleven values. Read the string out of `styles/dark.json` and
write it into `theme.json`'s `styles.css`, preserving the file's existing
`styles.color` and `styles.elements` keys:

```bash
php -r '
$theme = json_decode(file_get_contents("theme.json"), true);
$dark  = json_decode(file_get_contents("styles/dark.json"), true);
$theme["styles"]["css"] = $dark["styles"]["css"];
file_put_contents("theme.json", json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
'
```

- [ ] **Step 4: Run the tests**

Run: `php tests/run.php`
Expected: `17 assertions, 0 failures`

- [ ] **Step 5: Commit**

```bash
git add theme.json tests/test-palette.php
git commit -m "Give theme.json a base palette the editor can see

theme.json set link colour to var(--accent), which no stylesheet the
editor loads ever declared. Its own styles.css reaches both the canvas
and the front end, so the palette belongs there."
```

---

### Task 6: The photo-grid block

**Files:**
- Create: `blocks/photo-grid/block.json`
- Create: `blocks/photo-grid/render.php`
- Modify: `functions.php` — register the block
- Delete: `template-parts/loop-gallery.php` (in Task 8, once nothing calls it)

**Interfaces:**
- Consumes: `lumen_is_photo_post()`, `lumen_get_grid_image_size()`,
  `lumen_get_grid_sizes_attr()`, `lumen_get_display_title()` — all already in
  `functions.php`.
- Produces: the block type `lumen/photo-grid` with one attribute,
  `notesHeading` (string, default `Notes & Writings`).

- [ ] **Step 1: Write `block.json`**

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "lumen/photo-grid",
    "title": "Photo Grid",
    "category": "theme",
    "icon": "grid-view",
    "description": "The photo grid and notes list, partitioned from one query.",
    "textdomain": "lumen",
    "usesContext": ["queryId", "query"],
    "attributes": {
        "notesHeading": {
            "type": "string",
            "default": "Notes & Writings"
        }
    },
    "supports": {
        "html": false,
        "align": ["wide", "full"]
    },
    "render": "file:./render.php"
}
```

- [ ] **Step 2: Port the renderer**

`blocks/photo-grid/render.php` is `template-parts/loop-gallery.php` copied
verbatim, with three changes and nothing else:

1. The docblock's `@package` line stays; the "Shared by index.php, archive.php
   and search.php" sentence becomes "Rendered by the lumen/photo-grid block
   inside an inheriting Query block."
2. `esc_html_e('Notes & Writings', 'lumen')` becomes
   `echo esc_html($attributes['notesHeading'])`.
3. The `the_posts_pagination()` call at the end is removed. `core/query-pagination`
   in the template renders it now.

Everything else moves across untouched, including every explanatory comment. The
partition, `update_post_thumbnail_cache()`, `wp_omit_loading_attr_threshold()`,
the per-post size, the computed `sizes`, and the `loading` and `fetchpriority`
handling are the reason this file exists and none of their reasoning changes.

Add at the top, after the ABSPATH guard:

```php
/*
 * Inherited queries only. Every template in this theme uses one, and building a
 * WP_Query from block context would duplicate core for no gain. Rendering
 * nothing is the better failure: a grid built from the wrong query would look
 * plausible.
 */
if (!empty($block->context['query']) && empty($block->context['query']['inherit'])) {
    return;
}
```

- [ ] **Step 3: Register the block**

In `functions.php`, inside `lumen_setup()`:

```php
    // Registered from metadata so block.json stays the single source of truth
    // for the attribute default the renderer reads.
    register_block_type(get_template_directory() . '/blocks/photo-grid');
```

- [ ] **Step 4: Lint**

Run: `php -l blocks/photo-grid/render.php && php -l functions.php`
Expected: no syntax errors in either.

- [ ] **Step 5: Commit**

```bash
git add blocks functions.php
git commit -m "Move the gallery loop into a lumen/photo-grid block

Query Loop cannot express the partition: whether a post is a photo post
is computed at render time, including the first-content-image fallback,
so there is no meta key to filter on."
```

---

### Task 7: Templates and parts

**Files:**
- Create: `templates/index.html`, `templates/archive.html`, `templates/single.html`,
  `templates/page.html`, `templates/search.html`, `templates/404.html`
- Create: `parts/header.html`, `parts/footer.html`
- Modify: `functions.php` — the two behaviour-preserving filters

**Interfaces:**
- Consumes: `lumen/photo-grid` from Task 6.
- Produces: nothing later tasks depend on.

- [ ] **Step 1: Write `parts/header.html`**

```html
<!-- wp:html -->
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>
<!-- /wp:html -->
<!-- wp:group {"tagName":"header","className":"site-header","layout":{"type":"constrained"}} -->
<header class="wp-block-group site-header">
    <!-- wp:site-title {"level":0,"className":"site-title"} /-->
    <!-- wp:site-tagline {"className":"site-description"} /-->
    <!-- wp:navigation {"overlayMenu":"never","className":"site-nav"} /-->
</header>
<!-- /wp:group -->
```

`{"level":0}` renders the site title as `<p>`, which is what `header.php` does on
every view except the front page.

**Behaviour change, the fifth.** `header.php:21-28` promotes the site title to
`<h1>` when `is_front_page() && is_home()`, because on that one view no post title
owns the heading. A template part is static and cannot make that choice.

Resolve it with a second part rather than losing the heading: create
`parts/header-home.html`, identical but with `{"level":1}`, and reference it from
`templates/index.html` alone. Every other template keeps `header.html`. Two small
files beat a front page with no `<h1>`.

Add both to `theme.json` so the Site Editor names them properly:

```json
    "templateParts": [
        { "name": "header", "title": "Header", "area": "header" },
        { "name": "header-home", "title": "Header (home)", "area": "header" },
        { "name": "footer", "title": "Footer", "area": "footer" }
    ]
```

- [ ] **Step 2: Write `parts/footer.html`**

Port `footer.php`. It is twelve lines; keep its copy verbatim and wrap it in a
`core/group` with `tagName` `footer` and `className` `site-footer`, so the
existing `.site-footer` rules in `style.css` still reach it.

Note that `templates/index.html` in Step 3 below must reference `header-home`
rather than `header`.

- [ ] **Step 3: Write `templates/index.html`**

```html
<!-- wp:template-part {"slug":"header-home","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:query {"queryId":0,"query":{"inherit":true}} -->
    <div class="wp-block-query">
        <!-- wp:lumen/photo-grid /-->
        <!-- wp:query-pagination -->
        <!-- wp:query-pagination-previous {"label":"← Previous"} /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next {"label":"Next →"} /-->
        <!-- /wp:query-pagination -->
        <!-- wp:query-no-results -->
        <!-- wp:heading -->
        <h2 class="wp-block-heading">No posts yet</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>Start publishing to see your photos here.</p>
        <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 4: Write the remaining templates**

`archive.html` is `index.html` with `core/query-title` and `core/term-description`
above the query, and the no-results copy from `archive.php:31-32`. `search.html`
is the same shape with the copy from `search.php`. `single.html` carries
`core/post-featured-image`, `core/post-title`, `core/post-date`, `core/post-terms`
for both categories and tags, `core/post-content`, `core/post-navigation-link`
both ways, and `core/comments`. `page.html` is `single.html` without the date,
terms, navigation or comments. `404.html` ports `404.php`.

- [ ] **Step 5: Add the two behaviour-preserving filters**

In `functions.php`:

```php
/**
 * Supply a title for untitled posts.
 *
 * lumen_get_display_title() used to do this at six call sites. core/post-title
 * offers no fallback hook, so the value is filtered instead. This reaches
 * slightly further than the old function did, because a filter cannot see which
 * template asked.
 *
 * @param string $title The post title.
 * @param int    $id    The post ID.
 * @return string
 */
function lumen_filter_empty_title($title, $id = 0) {
    if ('' !== trim(wp_strip_all_tags($title))) {
        return $title;
    }

    return lumen_get_display_title($id);
}
add_filter('the_title', 'lumen_filter_empty_title', 10, 2);

/**
 * Never render a protected post's featured image.
 *
 * On a photoblog the featured image is the content being protected, so it must
 * not sit above the password form. lumen_is_photo_post() enforced this in the
 * template; core/post-featured-image renders whenever a thumbnail exists, so
 * the rule moves to the value every caller derives from.
 *
 * @param string $html The featured image markup.
 * @return string
 */
function lumen_hide_protected_thumbnail($html) {
    return post_password_required() ? '' : $html;
}
add_filter('post_thumbnail_html', 'lumen_hide_protected_thumbnail');
```

- [ ] **Step 6: Verify in WP Playground**

```bash
npx @wp-playground/cli@latest server \
  --wp=6.6 --mount=.:/wordpress/wp-content/themes/lumen --blueprint=bin/blueprint.json
```

Create `bin/blueprint.json` activating the theme and importing a handful of posts,
at least one with no featured image and one with no title. Check by hand:

- The front page has exactly one `<h1>`, and it is the site title.
- The grid renders photo posts, and untitled ones show the fallback title.
- Posts with no featured image appear under "Notes & Writings", not in the grid.
- Pagination works across more than one page.
- `curl -s localhost:9400 | grep -o 'fetchpriority="high"' | wc -l` returns `1`.
- A password-protected post shows its form with no photo above it.

- [ ] **Step 7: Commit**

```bash
git add templates parts theme.json functions.php bin/blueprint.json
git commit -m "Convert the templates to block markup

The untitled-title fallback and the protected-image rule move to filters,
because core/post-title and core/post-featured-image expose no hook for
either."
```

---

### Task 8: Split the CSS and remove the classic theme

**Files:**
- Modify: `style.css` — remove what block markup no longer emits
- Modify: `theme.json` — absorb the block-level styling
- Delete: `index.php`, `archive.php`, `single.php`, `page.php`, `search.php`,
  `404.php`, `header.php`, `footer.php`, `comments.php`, `searchform.php`,
  `template-parts/loop-gallery.php`, `editor-style.css`
- Modify: `functions.php` — drop `lumen_customize_register()`,
  `lumen_sanitize_grid_min_width()`, `add_editor_style()`, `register_nav_menus()`

**Interfaces:**
- Consumes: everything above.
- Produces: the finished theme.

- [ ] **Step 1: Move block-level styling into `theme.json`**

Under `styles.blocks`, port the rules for `core/site-title`, `core/navigation`,
`core/post-title`, `core/post-date`, `core/comments` and `core/query-pagination`
from the corresponding `style.css` sections — `HEADER`, `SINGLE POST`,
`NAVIGATION`, `PAGINATION` and `COMMENTS`.

- [ ] **Step 2: Delete the orphaned CSS**

Remove from `style.css` every rule whose selector no longer matches: `.site-header`,
`.site-description`, `.single-header`, `.single-title`, `.single-meta`,
`.archive-header`, `.archive-title`, `.post-navigation`, `.nav-label`,
`.nav-title`, `.pagination`, `.comments-area` and their descendants.

Keep: the reset and base, `PHOTO GRID`, `TEXT-ONLY POSTS SECTION`, `POST TAGS`,
`ACCESSIBILITY HELPERS`, `WORDPRESS CORE CLASSES`, `POST CONTENT TYPOGRAPHY`,
`REDUCED MOTION`, and `RESPONSIVE`.

- [ ] **Step 3: Delete the classic files**

`editor-style.css` is safe to delete only because Task 5b moved the palette into
`theme.json`'s `styles.css`, which the editor canvas loads. Confirm that key is
present before deleting, and drop `add_theme_support('editor-styles')` and
`add_editor_style()` in Step 4 as planned.

```bash
git rm index.php archive.php single.php page.php search.php 404.php \
       header.php footer.php comments.php searchform.php \
       template-parts/loop-gallery.php editor-style.css
```

- [ ] **Step 4: Strip the Customizer**

Remove `lumen_customize_register()`, `lumen_sanitize_grid_min_width()`, their
`add_action`, `add_editor_style()` and `register_nav_menus()` from
`functions.php`. Keep `lumen_get_grid_min_width()`: the `sizes` attribute and
`--photo-grid-min` still read it, now from its default alone.

- [ ] **Step 5: Run everything**

```bash
php tests/run.php
for f in functions.php inc/palette.php blocks/photo-grid/render.php; do php -l $f; done
```

Expected: `16 assertions, 0 failures`, and no syntax errors.

- [ ] **Step 6: Re-run the Playground checks from Task 7 Step 6**

All of them must still pass with the classic templates gone. Then install
ActivityPub and confirm a post's likes and reposts render with no theme-side code,
which is the problem that prompted the conversion.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Remove the classic theme

The Customizer goes with it: colour now comes from the generated
variations, which are contrast-checked, and the grid width from its
default."
```

---

## Deployment

Not a task, because it happens on the server rather than in the repo.

1. Back up the inphotos.org database. The navigation menu import is one-way.
2. Deploy the theme.
3. Open the Site Editor once and accept the classic menu import.
4. Confirm Site Editor → Styles → Browse styles offers Dark and Light, and that
   Dark is what the site already looked like.

The old `theme_mods_lumen` option is left in place untouched, so switching back to
classic Lumen restores the previous appearance intact.
