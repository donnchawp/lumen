=== Lumen ===
Contributors: donncha
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
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
- Customizer support for accent color
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
