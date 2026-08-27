=== Lumen ===
Contributors: donncha
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4.0
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
A dark, gallery-first WordPress theme designed for photoblogs. Features a responsive CSS Grid layout with hover overlays, featured image support, and a dedicated "Notes & Writings" section at the bottom of archive pages for text-only posts.

== Installation ==
1. Upload the theme files to `/wp-content/themes/lumen`
2. Activate the theme through the 'Appearance > Themes' menu in WordPress
3. Run Regenerate Thumbnails if you already had photos in the Media Library (see Recommended Plugins below)
4. Set a featured image on each photo post
5. Posts without featured images will automatically appear in the text-only section

== Features ==
- Dark, minimal design that puts photos first
- Responsive CSS Grid gallery (auto-adjusts columns)
- Portrait-aware image sizing (tall photos get a portrait crop and a taller grid cell)
- Overlay with title and date, shown on hover, on keyboard focus, and always on touch devices
- Text-only posts section on archive pages
- Customizer support for background color, accent color and photo grid column width
- Accessible, semantic HTML5 markup
- No dependencies, no jQuery, no JavaScript of its own

== Notes ==

= Posts without a title =
Photoblog posts are often untitled. Those fall back to "Untitled" so grid links still have an accessible name. The date is not repeated in the fallback because it is already rendered beside the title.

= Password-protected posts =
The featured image of a password-protected post is not shown until the password is supplied, and the post is listed under "Notes & Writings" rather than as a browsable grid tile. On a photoblog the image is the thing being protected.

= How the gallery and notes split works =
Posts are partitioned after the main query runs: those with a featured image go to the grid, the rest to "Notes & Writings". Because the split happens after pagination, the notes section on any given page lists only the text posts that fall on that page.

== Recommended Plugins ==
- Regenerate Thumbnails

The theme registers its own image sizes on activation. Photos already in your Media Library will not have those sizes, so WordPress falls back to serving the full-size original in the grid, which can mean multi-megabyte images in a 300px cell. Regenerate Thumbnails creates the missing sizes. New uploads are unaffected.

== Copyright ==

Lumen WordPress Theme, (C) 2026 Donncha O Caoimh
Lumen is distributed under the terms of the GNU GPL version 2 or later.

This theme bundles no third-party assets, fonts, images, or libraries. It uses the system font stack via CSS and ships no JavaScript of its own.

== Changelog ==

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
