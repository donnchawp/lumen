# Display tags on single posts

Date: 2026-08-03
Status: Approved, ready for implementation

## Problem

Lumen renders no tags anywhere. A reader who finishes a post cannot reach related
posts through the tag archives WordPress already generates. Categories appear in
`single.php:25-26`; the `post_tag` taxonomy appears nowhere.

## Scope

Tags render on the single post view only. The photo grid and the "Notes & Writings"
list stay unchanged, because the grid overlay carries a title and a date over the
image and a third element would crowd it. Pages keep no tag row: `page.php` renders
Pages, and Pages carry no tags.

## Design

### Markup

`single.php` gains a block between `.single-content` and `.post-navigation`:

```php
<?php if (has_tag()) : ?>
    <nav class="post-tags" aria-label="<?php esc_attr_e('Tags', 'lumen'); ?>">
        <?php the_tags('', ' ', ''); ?>
    </nav>
<?php endif; ?>
```

Core's `the_tags()` emits one `<a href="…" rel="tag">name</a>` per tag and escapes
each name. The separator is a single space. Flex `gap` governs the real spacing,
and a whitespace-only anonymous flex item never renders, so the space costs nothing
visually while keeping the names apart when CSS fails to load.

Passing `''` as the first argument matters: `the_tags()` substitutes a translated
`Tags: ` prefix when `$before` is `null`.

The `has_tag()` guard suppresses the whole container on untagged posts. Without it
an empty `<nav>` would still consume its bottom margin.

`<nav aria-label>` follows the pattern `.site-nav` and `.post-navigation` already
set in this theme.

### Styles

`style.css` gains one block after `.post-navigation`:

```css
.post-tags {
    max-width: 700px;
    margin: 0 auto 3rem;
    padding: 0 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.post-tags a {
    border: 1px solid var(--border);
    border-radius: 4px;
    color: var(--text-muted);
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    padding: 0.35rem 0.75rem;
    transition: border-color var(--transition), color var(--transition);
}

.post-tags a:hover,
.post-tags a:focus-visible {
    border-color: var(--border-hover);
    color: var(--accent);
}
```

Every value reuses an existing custom property. The rules introduce no new token.

`.post-tags` sits as a sibling of `.single-content` and repeats `max-width` and
`padding`, exactly as `.post-navigation` does. Three duplicated lines buy a clean
separation between the post body and its metadata.

`:focus-visible` shares the hover treatment. Release 1.0.2 restored the keyboard
focus ring on gallery links; a hover-only affordance here would undo that work.

`--text-muted` measures 4.74:1 against `--bg-primary`, so the pills meet WCAG AA.

### Versioning

Adding a feature bumps the minor version:

- `style.css` — `Version: 1.1.0`
- `readme.txt` — `Stable tag: 1.1.0`, plus a `= 1.1.0 =` changelog entry

## Alternatives rejected

**A `lumen_the_tags()` helper in `functions.php`.** The theme's existing helpers,
`lumen_get_display_title()` and `lumen_is_photo_post()`, each serve several call
sites. A tag helper would serve one.

**A hand-rolled `get_the_terms()` loop.** Per-link classes are its only advantage,
and the `.post-tags a` descendant selector already delivers the styling. Writing the
escaping by hand would add risk that core removes.

**Tags in the grid overlay or the notes list.** Rejected under Scope above.

## Verification

The theme ships no test harness, so verification runs by hand:

1. A tagged post renders the pill row below the content and above the post nav.
2. An untagged post renders no container and no extra vertical space.
3. A post carrying roughly fifteen tags wraps onto multiple rows.
4. Keyboard tabbing reaches each pill and shows a visible focus ring.
5. `php -l single.php` passes.
