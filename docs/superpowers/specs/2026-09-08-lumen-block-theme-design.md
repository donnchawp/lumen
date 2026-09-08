# Convert Lumen to a block theme

Date: 2026-09-08
Status: Approved, ready for implementation

## Problem

Lumen is a classic theme. Every template is PHP, the palette lives behind a
Customizer control, and the Site Editor can edit nothing. Two plugins already in
use on inphotos.org — ActivityPub and ATmosphere — render their reactions through
blocks hooked to `core/post-content`, a hook that never fires in a classic theme.
Both plugins therefore store fediverse likes and reposts and show the reader none
of them. Neither ships a shortcode fallback, so a classic theme has to hand-render
each one.

Converting also settles the wider problem those two plugins illustrate. Block
hooks are where the ecosystem has landed. Staying classic means writing bespoke
rendering for every plugin that adopts them.

## Scope

Lumen 2.0 replaces classic Lumen in place: same directory, same slug, classic
templates deleted. The floor rises from WordPress 6.0 to 6.6, which is what
theme.json v3, block hooks and the current Query Loop behaviour require.

The photo grid keeps its own PHP behind a dynamic block. The colour derivation
keeps its WCAG contrast maths. Neither is rewritten, only rehomed.

Out of scope: redesigning the theme. Every visual decision the theme makes today
survives the conversion, and any that cannot is listed under Behaviour changes
below.

## Design

### File layout

```
lumen/
  style.css              2.0.0, Requires at least: 6.6 — slimmed to ~450 lines
  theme.json             v3
  functions.php          palette bridge, block registration, image sizes, migration
  templates/             index, archive, single, page, search, 404 (.html)
  parts/                 header.html, footer.html
  blocks/photo-grid/     block.json, render.php, editor.js
```

These are deleted: `index.php`, `archive.php`, `single.php`, `page.php`,
`search.php`, `404.php`, `header.php`, `footer.php`, `comments.php`,
`searchform.php`, `template-parts/loop-gallery.php`, and `editor-style.css`.

WordPress enqueues `style.css` in block themes as it does in classic ones, so the
grid CSS keeps working untouched. `editor-style.css` goes because theme.json and
`style.css` together cover the editor canvas, which is what `add_editor_style()`
existed to do.

### The colour bridge

The palette functions in `functions.php:367-760` are kept verbatim. Only their
input changes. Today `lumen_get_background_color()` reads a theme mod; in 2.0 it
reads the background the user set in Site Editor → Styles.

```
Site Editor → Styles → Background
        ↓
wp_global_styles CPT
        ↓
lumen_source_background()      reads user data, static-cached
        ↓
lumen_palette()                existing contrast maths, unchanged
        ↓
wp_theme_json_data_theme       injects settings.color.palette
        ↓
editor pickers + front end
```

The filter runs during `WP_Theme_JSON_Resolver::get_theme_data()`. Reading user
data from inside it is the one genuinely uncertain step in this design, because
`get_user_data()` sits on a neighbouring resolver path and a re-entrant call would
recurse. **Prove this with a throwaway probe before building anything on it.** If
it recurses, read the `wp_global_styles` post content directly through a small
helper and cache it in a static, which touches no resolver at all.

Colour is the only thing the bridge carries. `--photo-grid-min`,
`--site-max-width`, `--reading-width` and `--overlay-alpha` stay on
`wp_add_inline_style()` where they are today. They are layout inputs that the grid
CSS and the `sizes` attribute both read, not palette entries, and theme.json has
nowhere honest to put them.

### The photo-grid block

`blocks/photo-grid/render.php` is `template-parts/loop-gallery.php` almost
verbatim: the partition into photo and text posts, `update_post_thumbnail_cache()`,
`wp_omit_loading_attr_threshold()`, the per-post size choice, the computed `sizes`
attribute, and the explicit `loading` and `fetchpriority` on leading images. The
comments explaining why each of those is needed move with the code. They are the
reason the file is hard to reconstruct, and none of the reasoning changes.

`block.json` declares `apiVersion` 3, `usesContext: ["queryId", "query"]`, and no
`save`.

The block supports inherited queries only. Every template here uses one, and
building a `WP_Query` from block context would duplicate core badly for no gain.
Dropped into a custom Query Loop it renders nothing, which is the better failure:
a wrong grid would look plausible.

One attribute, `notesHeading`, defaults to "Notes & Writings" so the label is
editable without a code change. The editor preview uses `ServerSideRender`.

### Templates

| Classic | Block |
|---|---|
| `index.php` | `templates/index.html` |
| `archive.php` | `templates/archive.html`, with `core/query-title` and `core/term-description` |
| `single.php` | `templates/single.html`, comments through `core/comments` |
| `page.php` | `templates/page.html` |
| `search.php` | `templates/search.html` |
| `404.php` | `templates/404.html` |
| `header.php` | `parts/header.html` |
| `footer.php` | `parts/footer.html` |

`index.html` wraps the grid in an inheriting Query block:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
  <!-- wp:query {"queryId":0,"query":{"inherit":true}} -->
    <!-- wp:lumen/photo-grid /-->
    <!-- wp:query-pagination /-->
  <!-- /wp:query -->
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

### Styles

theme.json owns the palette, the typography scale, spacing, layout widths, and
per-block styles for `core/site-title`, `core/navigation`, `core/post-title`,
`core/comments` and `core/query-pagination`.

`style.css` keeps what theme.json cannot express: the CSS Grid photo layout, the
card hover overlay and its gradient, the "Notes & Writings" list, post content
typography, the `prefers-reduced-motion` rules, and core's alignment and gallery
classes. It should land near 450 lines, down from 1176.

The reduction is not a saving we choose to make. The rules that style the header,
single post, navigation, footer, pagination, archive headers, search form and
comments stop matching anything the moment those templates become blocks, because
block markup emits `.wp-block-*` rather than `.site-header`, `.single-title` or
`.comments-area`. Most of that set moves into theme.json under `styles.blocks`;
the rest is deleted. The grid CSS is exempt because our own render callback still
emits `.photo-grid` and `.photo-card`.

## Behaviour changes

Three behaviours cannot be ported directly. Each needs a deliberate replacement,
and each one changes something.

**Untitled post fallback.** `lumen_get_display_title()` supplies a title for
untitled posts in six places. `core/post-title` offers no fallback hook, so this
becomes a `the_title` filter applied to empty titles in the loop. That reaches
slightly further than the current function does, because a filter cannot see which
template called it.

**Password-protected featured images.** `lumen_is_photo_post()` gates the single
featured image so that a protected post's photo never sits above its password form
(`functions.php:755-757`). `core/post-featured-image` renders whenever a thumbnail
exists. Replace it with a `post_thumbnail_html` filter returning `''` when
`post_password_required()` is true, which preserves the intent at the value every
caller derives from.

**Navigation menus.** `register_nav_menus('primary')` gives way to
`core/navigation`. WordPress offers to import the existing classic menu the first
time the Site Editor loads. That is one manual step on inphotos.org, not an
automatic migration.

## Migration

A version-stamped routine on `after_switch_theme`, guarded by an option so it runs
once, reads `get_theme_mod('lumen_background_color')` and writes it into the user
Global Styles background.

Without it inphotos.org loses its background colour the moment the theme updates,
and every derived tone with it. No widget areas are registered, so nothing else
needs carrying across.

This step does not reverse. Switching back to classic Lumen will not restore the
theme mod from Global Styles. Take a database backup of inphotos.org before the
update lands; a git tag covers the theme but not the site.

## Verification

The theme ships no test framework, so this is verification rather than automated
testing.

- `php -l` on every PHP file.
- Theme Check against the wp.org block theme rules.
- A WP Playground blueprint mounting the theme against a sample import, checking
  the photo and text partition, pagination across more than one page, and the
  `loading` and `fetchpriority` attributes in the rendered HTML.
- A chosen background still produces tones that pass WCAG AA, which is the whole
  point of keeping the derivation.
- ActivityPub reactions appear on a single post without any theme-side rendering
  code, which is the problem that prompted the conversion.

## Build order

Riskiest first, so a failure changes the design instead of wasting a rewrite.

1. Palette bridge probe, then theme.json and the migration routine
2. The `photo-grid` block, porting `loop-gallery.php`
3. Templates and parts
4. The CSS split
5. Delete the classic templates, bump the version, run Theme Check
