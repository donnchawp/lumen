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
stops running at request time and becomes a build-time generator: Lumen ships two
style variations, dark and light, whose tones the existing contrast maths produces.
Neither body of code is rewritten, only rehomed.

Lumen runs one site, inphotos.org, and is not distributed. Arbitrary background
colours therefore serve a choice made once, which is what justifies retiring them.

Out of scope: redesigning the theme. Every visual decision the theme makes today
survives the conversion, and any that cannot is listed under Behaviour changes
below.

## Design

### File layout

```
lumen/
  style.css              2.0.0, Requires at least: 6.6 — slimmed to ~450 lines
  theme.json             v3
  functions.php          block registration, image sizes, title and thumbnail filters
  styles/                dark.json, light.json — generated, checked in
  templates/             index, archive, single, page, search, 404 (.html)
  parts/                 header.html, footer.html
  blocks/photo-grid/     block.json, render.php, editor.js
  bin/generate-variation.php   dev tool; excluded when deploying, see readme.txt
```

These are deleted: `index.php`, `archive.php`, `single.php`, `page.php`,
`search.php`, `404.php`, `header.php`, `footer.php`, `comments.php`,
`searchform.php`, `template-parts/loop-gallery.php`, and `editor-style.css`.

WordPress enqueues `style.css` in block themes as it does in classic ones, so the
grid CSS keeps working untouched. `editor-style.css` goes because theme.json and
`style.css` together cover the editor canvas, which is what `add_editor_style()`
existed to do.

### Style variations

The palette functions in `functions.php:367-760` are not deleted. They move to
`bin/generate-variation.php`, a development tool that stubs the three WordPress
functions the maths actually touches — `sanitize_hex_color()`, `get_theme_mod()`
and `apply_filters()` — and prints a style variation for a given background:

```
php bin/generate-variation.php '#0a0a0a' > styles/dark.json
php bin/generate-variation.php '#ffffff' > styles/light.json
```

The generated files are checked in. WordPress reads them as style variations, and
the reader switches between them in Site Editor → Styles → Browse styles. Nothing
derives colour at request time any more.

This keeps the property that made the palette worth having. Both variations still
come out of `lumen_ensure_contrast()`, so every tone's WCAG AA compliance is
proven by the same code that proved it before, rather than eyeballed. Hand-tuning
two palettes would have thrown that away, and is the version of this simplification
worth avoiding.

It also stays extensible. A third variation later is one command and a checked-in
file, not a re-tune.

`--photo-grid-min`, `--site-max-width`, `--reading-width` and `--overlay-alpha`
stay on `wp_add_inline_style()` where they are today. They are layout inputs that
the grid CSS and the `sizes` attribute both read, not palette entries, and
theme.json has nowhere honest to put them.

The generator must assert before writing. A variation whose tones fail
`LUMEN_MIN_CONTRAST` should abort rather than emit a file, because a generated
palette that nobody re-checks is worse than a runtime one that checks itself.

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
editable without a code change, through a `TextControl` in the block's inspector.

`editor.js` registers the block on the client. Without it the editor knows nothing
about the block and every template using it shows an unsupported-block warning
instead. It is plain ES5 against the `wp.*` globals, because there is no build
step, and `block.json` names the handle `functions.php` registers rather than a
file, because `file:./editor.js` makes core look for a generated `editor.asset.php`
beside it.

The edit function is a static placeholder, not the `ServerSideRender` this spec
originally called for. `render.php` partitions the posts the surrounding query has
already run; rendering it through `/wp/v2/block-renderer/` gives it a REST request
whose main query holds no posts, so the preview would read "Block rendered as
empty." on every template. A placeholder that says what the block does is worth
more than a preview that is always blank.

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

Five behaviours were known in advance not to port directly. Each needs a
deliberate replacement, and each one changes something.

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

**The front page heading.** `header.php:21-28` promotes the site title to `<h1>`
when `is_front_page() && is_home()` and drops it to `<p>` everywhere else, because
on the front page no post title owns the heading. A template part is static and
cannot make that choice. Two header parts resolve it: `header.html` at level 0 and
`header-home.html` at level 1, the latter referenced only by `templates/index.html`.

**Arbitrary background colours.** The Customizer control and the runtime
derivation both go. The reader chooses between two generated variations instead of
any hex. This is a feature removal, taken deliberately: the theme runs one site, so
the derivation served a decision made once, and it was the only part of the
conversion whose feasibility was unproven.

The eight below were not foreseen. They were found while doing the work, each one
a place where a core block covers less ground than the classic template call it
replaced. None was worth stopping for, and all eight are losses rather than
trades, so they are written down here instead.

**Paginated posts.** `single.php:39-44` and `page.php:34-39` called
`wp_link_pages()`. `core/post-content` renders the current page of the content and
offers no page links, and core ships no block that does. A post using
`<!--nextpage-->` therefore renders page one with no way to reach the rest.
Nothing replaces it: no post on inphotos.org is paginated, and a second theme
block whose only job is to reproduce `wp_link_pages()` is more theme than that
case is worth.

**The search result count.** `search.php:32-42` rendered
`_n('%s result', '%s results', $wp_query->found_posts)` under the query title. No
core block exposes `found_posts`. `core/query-title` still names what was searched
for and the pagination still shows how far the results run, so what is gone is the
total at a glance.

**The posts-page heading.** `index.php:19-27` rendered the posts page's own title
in the `is_home() && !is_front_page()` case, which is what a static front page
produces. That page falls to `templates/index.html`, there being no `home.html`,
and `index.html` uses `header-home.html` — so the `<h1>` is the site title and the
page's own title is not shown at all. inphotos.org lists posts on its front page
and never reaches this branch. Setting a static front page would.

**Archive descriptions.** `archive.php:21` called `the_archive_description()`,
which covers term descriptions, author biographies and post-type archive
descriptions. `core/term-description` is the taxonomy third of that, so author and
post-type archives now render no description at all. inphotos.org has one author
and no custom post types, so the loss only becomes visible if an author biography
is filled in later.

**Menu depth.** `header.php:41` passed `'depth' => 1` to `wp_nav_menu()`, so a
menu with children rendered only its top level and the header stayed one line.
`core/navigation` has no depth control. Submenus now render, as a flyout or an
inline list depending on the block's settings. The menu on inphotos.org is flat,
so nothing changes until a child item is added — at which point the header changes
shape without anyone asking it to.

**A site with no menu.** `header.php:39` guarded on `has_nav_menu('primary')` and
passed `'fallback_cb' => false`, so a site with nothing assigned rendered no
navigation whatsoever. `core/navigation` falls back instead:
`block_core_navigation_get_fallback_blocks()` takes the most recent `wp_navigation`
post if one exists and a `core/page-list` block if none does. An unconfigured site
now gets a list of its pages in the header rather than an empty space.

**Template strings.** Every string in `templates/*.html` and `parts/*.html` is
hardcoded English: the pagination labels, the four empty-state messages, the search
placeholder, the comment pager. The classic templates passed each through
`esc_html_e()`. A template file is static markup with no way to call a translation
function, and core does not translate template content. What survives in PHP — the
untitled fallback, the copyright line, the link home, the block's editor labels —
is still translated, and `load_theme_textdomain()` stays for it. Because most of
the visible chrome is no longer among them, `style.css` drops the
`translation-ready` tag. The theme is now partly internationalised, and a tag
claiming otherwise is exactly the sort of claim someone would act on before
finding out.

**The skip link.** All five classic templates opened
`<main id="primary" class="site-main" tabindex="-1">`, and `header.php` printed a
skip link ahead of it in the markup. Block themes get core's
`wp_enqueue_block_template_skip_link()` instead, which builds the link in
JavaScript from `document.querySelector('main')` and injects it before
`.wp-site-blocks`. Two things follow. With JavaScript off there is no skip link at
all, where the theme previously shipped one that needed nothing. And core does not
put `tabindex="-1"` on the target, so where focus lands after following `#primary`
is left to the browser rather than settled by the markup, which is the whole
reason the attribute was there. `core/group` cannot carry `tabindex`, so restoring
it would mean a second `render_block` filter; it is recorded rather than fixed.

## Migration

Read the live value before generating anything. inphotos.org is one site on a
multisite network, and theme mods are per-site, so `--url` is required: without it
WP-CLI reads the main site's mods and answers the wrong question without erroring.

```
wp theme mod get lumen_background_color --url=https://inphotos.org
```

Lumen is active on inphotos.org alone, so this is the only site to check.

If it is unset or `#0a0a0a`, `styles/dark.json` reproduces what inphotos.org
renders today and there is nothing to migrate. If it is some other colour, that
colour is the one to generate `styles/dark.json` from, so the site looks unchanged
across the update.

Nothing is written to the database. The theme mod is simply left behind, which
means switching back to classic Lumen restores the current appearance intact. No
widget areas are registered, so nothing else needs carrying across.

Take a database backup of inphotos.org before the update lands anyway. The
navigation menu import is a one-way step, and a git tag covers the theme but not
the site.

## Verification

The theme ships no test framework, so this is verification rather than automated
testing.

- `php -l` on every PHP file.
- Theme Check against the wp.org block theme rules.
- A WP Playground blueprint mounting the theme against a sample import, checking
  the photo and text partition, pagination across more than one page, and the
  `loading` and `fetchpriority` attributes in the rendered HTML.
- Both generated variations pass WCAG AA, asserted by the generator rather than
  checked by eye.
- The dark variation renders inphotos.org indistinguishably from classic Lumen.
- ActivityPub reactions appear on a single post without any theme-side rendering
  code, which is the problem that prompted the conversion.

## Build order

No step here is speculative. An earlier draft of this design kept the derivation
at request time and injected it into theme.json through a filter, which put an
unproven question about WordPress's theme.json resolver at the head of the work.
Generating the variations ahead of time removes that question entirely: the maths
is known to run standalone, touching only `sanitize_hex_color()`,
`get_theme_mod()` and `apply_filters()`.

1. `bin/generate-variation.php`, then `styles/dark.json` and `styles/light.json`
2. theme.json
3. The `photo-grid` block, porting `loop-gallery.php`
4. Templates and parts
5. The CSS split
6. Delete the classic templates and the Customizer section, bump the version, run
   Theme Check
