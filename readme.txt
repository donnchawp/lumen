=== Lumen ===
Contributors: donncha
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
A dark, gallery-first WordPress block theme designed for photoblogs. Features a responsive CSS Grid layout with hover overlays, featured image support, and a dedicated "Notes & Writings" section at the bottom of archive pages for text-only posts.

Since 2.0.0 this is a block theme. Every template is edited under Appearance > Editor rather than by editing PHP, and the palette is chosen there too. There is no Customizer screen any more; see the notes below.

== Installation ==
1. Upload the theme files to `/wp-content/themes/lumen`
2. Activate the theme through the 'Appearance > Themes' menu in WordPress
3. Run Regenerate Thumbnails if you already had photos in the Media Library (see Recommended Plugins below)
4. Set a featured image on each photo post
5. Posts without featured images will automatically appear in the text-only section
6. If you are coming from Lumen 1.x, read "Upgrading from Lumen 1.x" below before you do anything else

== Features ==
- Dark, minimal design that puts photos first
- Responsive CSS Grid gallery (auto-adjusts columns)
- Photos shown whole in the grid, at their own shape, never cropped to fit a cell
- Overlay with title and date, shown on hover, on keyboard focus, and always on touch devices
- Text-only posts section on archive pages
- Two style variations, Dark and Light, switched in the Site Editor
- Templates and template parts editable in the Site Editor
- Accessible, semantic HTML5 markup
- No dependencies and no jQuery. The theme ships no front-end JavaScript; the one script it does register loads only in the block editor

== Notes ==

= Posts without a title =
Photoblog posts are often untitled. Those fall back to "Untitled" so grid links still have an accessible name. The date is not repeated in the fallback because it is already rendered beside the title.

= Password-protected posts =
The featured image of a password-protected post is not shown until the password is supplied, and the post is listed under "Notes & Writings" rather than as a browsable grid tile. On a photoblog the image is the thing being protected.

= How the gallery and notes split works =
Posts are partitioned after the main query runs: those with a featured image go to the grid, the rest to "Notes & Writings". Because the split happens after pagination, the notes section on any given page lists only the text posts that fall on that page.

The grid is a block, Photo Grid, and it is what the index, archive and search templates place inside their Query Loop. It has one setting, the heading above the notes list, in the block's sidebar. It renders nothing inside a Query Loop that has been switched to its own custom query, because such a query is not the one it partitions.

= The two style variations =
Dark and Light. Dark is what the theme paints if you never choose anything.

To switch: Appearance > Editor > Styles, then the "Browse styles" list, then Dark or Light, then Save.

Neither palette is hand-picked. Both are generated from one background colour and one accent colour by the same derivation the 1.4.0 Customizer used at request time, so every text tone in both is measured against the surface it is really painted on and none of them ships below WCAG AA. That is why there are two ready-made variations rather than a colour picker: the picker is now a generator, run once, and its output is checked in as styles/dark.json and styles/light.json.

= The Customizer is gone =
Appearance > Customize no longer offers background colour, accent colour or photo grid column width. Block themes have no Customizer, and two of the three settings are answered by the style variations above. The column width is a constant in functions.php, LUMEN_GRID_MIN_WIDTH_DEFAULT, at the same 450px default 1.3.0 shipped.

Your saved values are not deleted. lumen_background_color, lumen_accent_color and lumen_grid_min_width all stay in the database, so switching back to Lumen 1.x finds them intact.

= Upgrading from Lumen 1.x =
Do the Site Editor classic-menu import **before** switching themes for any reason. register_nav_menus() is gone, so wp_map_nav_menu_locations() intersects the stored locations against an empty registry on after_switch_theme and writes the empty result back, discarding the stored `primary` assignment. The menu object itself survives, and WP_Navigation_Fallback will still find it: with the location gone it looks for a menu whose slug is `primary`, and failing that takes the most recently created menu, empty or not. If that newest menu happens to be empty, core does not try an older one — it falls back to a list of your pages instead. So the import is worth doing while the location assignment is still there to be read.

In practice: activate Lumen 2.0.0, open Appearance > Editor, and accept the offer to import your existing menu into the navigation block. Do that first, before switching to any other theme and back.

Back up the database before upgrading, as with any theme that changes this much.

== Development ==

bin/, tests/ and docs/ are development directories. They are in the repository because there is no build step to strip them, and nothing in the theme loads any of them, but they should be excluded when deploying to a live site:

- bin/ contains the style variation generator and a WordPress Playground fixture script. The fixture script deletes every post and page before seeding its own. It refuses to run unless the constant LUMEN_PLAYGROUND_RESET is defined by whoever includes it, which is a deliberate acknowledgement and not something you would type by accident, but the file has no business being on a live server either way.
- tests/ contains the palette test suite, run with `php tests/run.php`.
- docs/ contains the design specification.

If you deploy with rsync, exclude them; if you build a zip, leave them out of it.

== Recommended Plugins ==
- Regenerate Thumbnails

The theme registers its own image sizes on activation. Photos already in your Media Library will not have those sizes, so WordPress falls back to serving the full-size original in the grid, which can mean multi-megabyte images in a 300px cell. Regenerate Thumbnails creates the missing sizes. New uploads are unaffected.

== Copyright ==

Lumen WordPress Theme, (C) 2026 Donncha O Caoimh
Lumen is distributed under the terms of the GNU GPL version 2 or later.

This theme bundles no third-party assets, fonts, images, or libraries. It uses the system font stack via CSS. The only JavaScript it ships is blocks/photo-grid/editor.js, which registers one block with the block editor and never loads on the site itself.

== Changelog ==

= 2.0.0 =
* Lumen is a block theme. The six PHP templates, the header, the footer, comments.php and searchform.php are replaced by templates/*.html and parts/*.html, built out of core blocks, and every one of them is now editable under Appearance > Editor. Requires at least is 6.6, up from 6.0, which is what block themes of this shape need.
* The photo grid is a block, lumen/photo-grid, rather than a template part included by three templates. The partition, the thumbnail cache priming, the computed sizes attribute and the explicit fetchpriority on the first image all moved with it unchanged. The heading above the notes list is a block setting now instead of a hardcoded string.
* Two style variations, Dark and Light, replace the Customizer's colour settings. Dark renders identically to 1.6.5, hex for hex. Both are generated by bin/generate-variation.php from the same contrast maths that used to run on every request, and it refuses to write a variation whose text fails WCAG AA, so the palettes are still proven rather than eyeballed.
* The Customizer is gone entirely, including the photo grid column width, which is now a constant at the same 450px default. Your stored values are left in the database untouched.
* register_nav_menus() is gone with it, and that has a cost on a theme switch. Read "Upgrading from Lumen 1.x" above before you activate this.
* Things the block templates cannot do that the PHP ones did, all small and all deliberate: paginated posts using <!--nextpage--> render only their first page, since core/post-content has no wp_link_pages(); the search results page no longer prints a result count; author and post-type archives no longer print their description, only taxonomy terms do; the primary menu is no longer limited to one level; a site with no menu assigned now gets a list of its pages in the header rather than nothing; and on a static-front setup the posts page shows the site title as its heading rather than its own.
* The strings in the templates are hardcoded English. A template file cannot call a translation function, and core does not translate template content, so the pagination labels, the empty-state messages and the search placeholder are no longer translatable. What is left in PHP still is. The translation-ready tag has been dropped from style.css to match.
* The skip link is core's now, which builds it in JavaScript. With JavaScript off there is no skip link, where before there was one that needed nothing.
* editor-style.css is deleted. theme.json carries the palette and the block styling into the editor, which is what that file existed to do.

= 1.6.5 =
* Tested up to 7.1, which is the version it has been running on for a while. No code changes. Requires at least stays at 6.0, which is correct: the newest core function the theme calls is wp_omit_loading_attr_threshold(), and that has been in WordPress since 5.9.

= 1.6.4 =
* Housekeeping, with nothing to see on the site. The page frame is now one number in one place instead of the same number written out at a dozen selectors: the site width, the reading column and the page gutter are custom properties, and the widths derived from them, like the 1304px images in post content break out to, are written as the subtraction rather than the answer. Widening the frame used to mean editing five rules and a PHP constant and hoping you found them all.
* The form fields and the two buttons were three near-copies of one look, and had already drifted apart on padding. They share a rule now. So had the block editor preview: inline code was 4px rounded there and 3px on the site, which is fixed, so the preview matches the post again.
* Dropped two functions nothing called, one of which carried a long comment explaining a decision that is actually made somewhere else. That reasoning moved to where the decision is. The hex parser existed twice, the accent colour was the one derived colour not checking the theme's own contrast constant, and the overlay's worst-case surface was a hand-computed copy of what the scrim's opacity implies rather than being worked out from it. Softening the scrim now re-checks the text against the lighter surface it just created.
* The two breakout rules for wide images sat in separate media queries 200 lines apart, agreeing on 1140px by way of a comment saying they agreed. They are one block now. The full-bleed treatment on a phone likewise stopped being written twice, once for the grid and once for the featured image.

= 1.6.3 =
* The featured image on a single post runs edge to edge on a phone as well. 1.6.2 did this for the grid and stopped there, and the single post was losing more than the grid ever did: .single-featured-image carries its own 1.5rem of padding on top of .site-main's, so on a 523px phone the photo was capped at 427px of a 523px screen. The title and the text below it keep their padding.

= 1.6.2 =
* The photo column width setting applies on phones too. Below 769px the grid capped its minimum column at 250px, which was right when the width was hardcoded at 300 and became wrong the moment it turned into a setting. A site asking for 450px columns got two 254px photos on a 523px phone rather than one 523px photo, and 1.6.1's full bleed made that worse by handing the grid exactly the width it needed to fit the second column. The cap is gone, so the number in the Customizer means the same thing at every width.

= 1.6.1 =
* Photos run edge to edge on any phone, not just a narrow one. 1.6.0 gated the full-bleed grid at 480px, which looks like a phone boundary and is not one: Android's display size setting scales the density, so the same handset reports 412px at its default and 523px a couple of notches down. A Galaxy S23 Ultra at 523px sat just outside it and kept the padding. Full bleed is tied to the narrow layout breakpoint now, so it holds however wide a phone decides it is, and the two-column tablet range gets it too.

= 1.6.0 =
* Photos run edge to edge on a phone. The 1.5rem of padding on .site-main is a fixed 48px whatever the screen is, so a 320px phone was spending 16% of its viewport on margin and the photo was losing 41% of its area to it. Below 481px the grid cancels that padding out. The notes list and the pagination keep theirs, because text running into the edge of the screen reads badly.
* Card corners are square at full bleed. A 4px radius on a photo touching both edges reads as a rendering fault rather than a detail.
* The page padding is a custom property, --page-padding. The grid's breakout has to cancel exactly what .site-main applies, and two hardcoded 1.5rems would drift apart eventually.

= 1.5.0 =
* Grid photos are shown whole rather than cropped to fit the cell. A card took a fixed 4:3 shape, or 3:4 for a tall photo, and object-fit cut the frame down to it. Both orientations are wider than their card, so the cut always came off the sides: a photo with a border painted into it lost its left and right edges and kept top and bottom, which is why the borders looked lopsided. Cards take the shape of their photo now and all four edges show.
* Rows are ragged, and slightly less ragged than they were. At a 664px column a landscape card used to be 498px tall beside an 885px portrait. At their own shapes they are 448px and 826px.
* The grid image sizes are not cropped any more, and there is no separate portrait size, because an uncropped width bound already fits either orientation. lumen-grid and lumen-grid-large replace the four sizes 1.3.0 registered. Run Regenerate Thumbnails: anything already cut is a hard crop and keeps its cropped edges until you do.
* A comment describing the grid sizes ended up stranded above the palette constants in 1.4.0, documenting the wrong thing. It is back where it belongs.

= 1.4.0 =
* The page background is set under Appearance > Customize > Colors, and every other colour is worked out from it. Pick something light and the text, panels and borders go dark to match instead of leaving dark-on-dark. The default is unchanged and renders identically to 1.3.1, down to the hex value of every token.
* Text tones are derived from the contrast ratios the palette already had rather than from fixed hex values, and each is measured against the surface it is really painted on. --text-muted was tuned at 4.74:1 against the page background, but the note row date and the search placeholder paint it on a panel, where it was only 4.52:1 with nothing spare for the background moving.
* The photo card overlay has its own title and date colours now. It is a dark scrim sitting on the photo rather than on the page, so a light background would have put dark text on black. The accent still shows on the title there, lightened for the scrim instead of darkened for the page.
* A background too close to mid grey to carry legible text is nudged away from the text until it can. Contrast against the nearer of black and white bottoms out around 4.58:1 in that band, and the panels sit a step closer to the text again, so nothing painted on one could reach AA. This is the same bargain the theme already makes with a dark accent colour.

= 1.3.1 =
* The navigation menu is a horizontal row again rather than a bulleted list. The theme styled the links but never the ul that wp_nav_menu wraps them in, so the items fell back to browser defaults and stacked one per line, each with a disc hanging outside the list because the reset zeroes list padding. The spacing is unchanged, and the menu now wraps to a second row on a narrow phone instead of running off the side.

= 1.3.0 =
* The photo grid's column width is set under Appearance > Customize > Photo Grid. It is the narrowest a column may be, from 200px to 600px, not a column count: the grid still fits as many columns as will fit and still drops to fewer as the window narrows, so a larger number means fewer and bigger photos. 300 gives four across on a wide screen, 350 gives three, 450 gives two.
* The default is now 450px, which is two across on a wide screen where it used to be four. Set it back to 300 in the Customizer if you preferred the denser grid.
* Grid photos are cut at 1400x1050 as well as 800x600, and both are offered to the browser together. A column wider than about 390px outgrew the single 800px crop, and the new default reaches 923px, so run Regenerate Thumbnails after upgrading. Until you do, photos keep their existing 800px crop and look soft at the larger sizes rather than being served the full-size original in its place.
* The sizes hint is worked out from the column width rather than hardcoded. It named a single width for every viewport above 768px, which was already a compromise at the old fixed four columns and would have been wrong by half at the wider settings, where the grid holds one column across the whole laptop range.

= 1.2.4 =
* Images in posts really do render at their own width now. 1.2.3 only matched an image sitting in a bare figure, and the editor wraps an aligned image in a div around that figure, which is the shape almost every photo on a photoblog has. The rule matches both containers.
* Wide alignment works on a wrapped image too. The fix in 1.2.1 named the element to win a specificity tie and only covered the figure form, so the div form still lost. The figure margin rule is wrapped in :where() instead, dropping it to no specificity so any alignment class beats it whatever element it lands on.

= 1.2.3 =
* Images in posts written with the classic editor render at their own width again. 1.2.1 narrowed the rule to the block editor's own figure classes, and the classic editor writes figure class="aligncenter size-full" with no block class, so every one of those images was pushed back into the reading column. The rule now matches a figure that directly wraps an image, linked or not, which is what actually tells an image apart from the table, embed, pullquote, audio, video and gallery blocks that core also wraps in a figure.

= 1.2.2 =
* Grid photos ask for the right file size between 481px and 563px wide. The sizes hint switched to half-width at 481px, but the grid does not fit a second column until 564px, so in that window the browser fetched a 240px file for a 433px slot.
* The date on a card overlay meets WCAG AA over any photo. Over a white frame the gradient left it at 4.27:1; it is 4.96:1 now.
* A Customizer accent colour is checked against the lighter of the two backgrounds it is painted on. Checking only the darker one let a colour pass at 4.5:1 there and fail at 4.29:1 behind the current pagination item, the focused skip link and note rows on hover.
* Untitled protected and private posts keep their prefix once the password is entered, matching what core does for titled ones. Before, entering the password turned "Protected: Untitled" into a bare "Untitled" while a titled post beside it still read "Protected: Sunset".
* Comment navigation has its own styling instead of picking up the post navigation's, which was right-aligning "Newer comments" for no reason.
* The reply heading is an h2. On a post with no comments it was an h3 following the post h1, skipping a level.
* The skip link moves focus in Safari as well. Its target was not focusable, so the page jumped but focus stayed in the header.
* main is outside the loop in single.php and page.php, as it already was in the other five templates, and no longer opens and closes per iteration.
* Orientation logic moved out of the gallery template into lumen_get_grid_image_size().
* $content_width is set on after_setup_theme so a child theme can override it.
* Removed two declarations from the post navigation that had nothing to distribute, and a comment crediting one of them with an effect flex:1 was producing.

= 1.2.1 =
* The image breakout added in 1.2.0 was matching any figure, so unaligned tables, embeds, pullquotes, audio, video and galleries were stretched out of the reading column too. It now applies to image figures and captions only.
* Images in post content line up with the featured image above them. They were capped 96px wider than it, which ate the page gutter.
* Wide and full alignments work on images for the first time. The block editor writes a wide image as figure class="wp-block-image alignwide", which collided with the theme's own figure margin rule at equal specificity and lost, so the alignment was silently dropped.
* Full-width content no longer scrolls the page sideways by a few pixels. It is sized in vw, which counts the scrollbar, and the clipping meant to hide that was not taking effect.
* The first photo on a grid page is no longer lazy-loaded and now carries fetchpriority="high". Because the grid renders outside the loop, WordPress could not tell which images were on screen and lazy-loaded all of them, including the largest one above the fold.
* Grid pages prime the thumbnail cache in one pass. Query count was scaling with the number of photos, about two extra queries per card.
* editor-style.css is loaded in the block editor at last. add_editor_style() only declares the classic editor feature; the block editor needs the editor-styles theme support, which was missing, so the file shipped in 1.0.2 had never been used.

= 1.2.0 =
* Featured images are no longer stretched to fill the page. The old rule forced every one of them to the full 1304px content box, so a portrait frame served at 607px was upscaled by 115% and older narrow files fared worse. They now render at their own size, centred.
* Images in post content render at their own width instead of being shrunk to the 700px reading column, centred on the page and capped at the same width the featured image uses. Text stays in the column. Floated images and explicit wide/full alignments are unchanged, and below 1140px nothing breaks out.

= 1.1.0 =
* Single posts now list their tags below the content, as linked pills. Posts with no tags render nothing.

= 1.0.2 =
* Portrait cards no longer stretch their landscape neighbours. Grid items stretch by default and stretch overrides aspect-ratio, so one portrait photo was forcing every landscape photo in the same row to a 3:4 crop.
* The keyboard focus ring on gallery links is visible again. It was drawn outside the card, which clips overflow.
* Dark accent colours picked in the Customizer are lightened until they reach 4.5:1 against the background, instead of rendering the site title and focus outlines unreadable.
* Added editor-style.css so wide and full alignments preview correctly in the block editor.
* Added layout for [gallery] shortcodes, which had none because html5 gallery support suppresses core's inline styles.
* Untitled posts read "Untitled" rather than "Untitled, <date>", which was making the link's accessible name announce the date twice. Untitled protected posts read "Protected: Untitled" instead of "Protected: " with a dangling colon.
* Post navigation renders from the adjacent posts already fetched, halving the queries, and now honours the untitled fallback.
* wp_link_pages() output has its own class instead of inheriting the pager's bordered boxes.
* An empty archive has an h1 again.
* Floated images in post content are cleared.

= 1.0.1 =
* Added page.php. Static Pages previously fell through to index.php and rendered as a photo tile with their content discarded.
* Added 404.php, search.php, searchform.php and comments.php.
* Featured images of password-protected posts are no longer exposed.
* Wired the Customizer accent colour to the stylesheet. It previously had no effect.
* Removed an enqueue for a js/lumen.js file that does not exist, which 404'd on every page load.
* Portrait photos now render in a portrait cell instead of being cropped back to 4:3.
* Added .screen-reader-text, so pagination no longer reads "Page 1 Page 2 Page 3".
* Added the required core classes for alignment, captions, sticky posts and post authors.
* Restored heading, list, blockquote, code and table spacing inside post content.
* Card overlays now appear on keyboard focus and are always visible on touch devices.
* Raised muted text contrast from 2.64:1 to 4.74:1 to meet WCAG AA.
* Added a skip link, an h1 on the blog index, and labelled nav landmarks.
* Escaped author and date output in archive titles.
* Deduplicated the loop shared by index.php, archive.php and search.php.

= 1.0.0 =
* Initial release.
