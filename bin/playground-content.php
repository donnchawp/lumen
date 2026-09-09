<?php
/**
 * Seed content for a Playground run.
 *
 * Loaded by bin/blueprint.json after WordPress has booted, with
 * LUMEN_PLAYGROUND_RESET defined. Every post here exists to exercise one branch
 * the templates have to get right: an untitled post, a post with no featured
 * image, a protected post that is also untitled, and enough photos to paginate.
 *
 * Development only. Nothing in the theme loads this, and it should not be
 * deployed: see readme.txt on excluding bin/, tests/ and docs/.
 *
 * @package Lumen
 */

// Nothing here is part of rendering a page. On a live install these files sit
// under wp-content/themes/, so without this a browser could run the suite.
//
// ABSPATH would be the wrong test here even though this file needs WordPress
// loaded: ABSPATH is defined in every web request, so it would guard against a
// direct hit on the URL and nothing else, and the wp_delete_post() loop below
// empties the site. Playground's runPHP step runs under the CLI SAPI.
if (PHP_SAPI !== 'cli') {
    exit;
}

// The SAPI check above closes the HTTP door and no more than that. WP-CLI also
// runs under the CLI SAPI, so on the live site
//
//   wp eval-file wp-content/themes/lumen/bin/playground-content.php
//
// would satisfy it and then delete every post and page. Nothing about that
// command reads as destructive, and someone poking at an unfamiliar theme
// directory could plausibly type it.
//
// So the caller has to say so as well. A constant rather than an environment
// variable: an exported variable can linger in a shell for the rest of the
// session, or be inherited by something that never meant to set it, whereas a
// constant has to be defined in this same PHP process by whoever includes this
// file. bin/blueprint.json defines it; there is no way to define it from
// eval-file's command line without writing a second file that says the same
// thing, which is the deliberate act this is asking for.
if (!defined('LUMEN_PLAYGROUND_RESET') || !LUMEN_PLAYGROUND_RESET) {
    fwrite(
        STDERR,
        "playground-content.php deletes every post and page before seeding.\n"
        . "Define LUMEN_PLAYGROUND_RESET before including it if that is what you want:\n"
        . "  php -r \"define('LUMEN_PLAYGROUND_RESET', true); require 'wp-load.php'; require 'bin/playground-content.php';\"\n"
    );
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * A generated JPEG, attached to a post and set as its featured image.
 *
 * Real files rather than placeholders, because the grid's sizes attribute and
 * srcset are built from the intermediate sizes WordPress generates from them.
 *
 * @param int    $post_id Post to attach to.
 * @param string $name    File name, without extension.
 * @param int    $width   Image width.
 * @param int    $height  Image height.
 * @return void
 */
function lumen_playground_attach_photo($post_id, $name, $width, $height) {
    $upload = wp_upload_dir();
    $file   = $upload['path'] . '/' . $name . '.jpg';

    $image = imagecreatetruecolor($width, $height);

    // A visible gradient, so a wrong image size is obvious on screen rather
    // than only in the markup.
    for ($x = 0; $x < $width; $x++) {
        $shade = (int) (255 * ($x / $width));
        imagefilledrectangle($image, $x, 0, $x, $height, imagecolorallocate($image, $shade, 80, 255 - $shade));
    }

    imagejpeg($image, $file, 85);
    imagedestroy($image);

    $attachment_id = wp_insert_attachment(
        array(
            'post_mime_type' => 'image/jpeg',
            'post_title'     => $name,
            'post_status'    => 'inherit',
        ),
        $file,
        $post_id
    );

    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', 'A generated test photograph.');
    set_post_thumbnail($post_id, $attachment_id);
}

// Hello world and the sample page ship with every install and would sit in the
// middle of the fixtures below.
foreach (get_posts(array('post_type' => array('post', 'page'), 'numberposts' => -1, 'post_status' => 'any')) as $existing) {
    wp_delete_post($existing->ID, true);
}

$photos = array(
    array('Clogher Beach', '2026-09-08 10:00:00', 1600, 1067),
    array('', '2026-09-07 10:00:00', 1200, 1600),
    array('Harbour Lights', '2026-09-06 10:00:00', 1400, 900),
    array('Fog on the Lee', '2026-09-05 10:00:00', 1000, 1000),
    array('Dawn, Ballycotton', '2026-09-04 10:00:00', 1800, 1200),
);

foreach ($photos as $index => $photo) {
    list($title, $date, $width, $height) = $photo;

    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => 'Shot on the club trip.',
        'post_status'  => 'publish',
        'post_date'    => $date,
        'post_category' => array(1),
    ));

    lumen_playground_attach_photo($post_id, 'photo-' . $index, $width, $height);
    wp_set_post_tags($post_id, array('seascape', 'kerry'));
}

// No featured image and no image in the content, so this belongs under
// "Notes & Writings" rather than in the grid.
wp_insert_post(array(
    'post_title'   => 'A note about lenses',
    'post_content' => 'Some words with no photograph attached to them at all.',
    'post_status'  => 'publish',
    'post_date'    => '2026-09-03 10:00:00',
));

// Protected and untitled at once: the grid must not show its photo, the notes
// list must call it "Protected: Untitled", and single.html must show the
// password form with nothing above it.
$protected_id = wp_insert_post(array(
    'post_title'    => '',
    'post_content'  => 'The photograph behind the password.',
    'post_status'   => 'publish',
    'post_password' => 'lumen',
    'post_date'     => '2026-09-02 10:00:00',
));
lumen_playground_attach_photo($protected_id, 'photo-protected', 1200, 800);

wp_insert_post(array(
    'post_title'   => 'About',
    'post_content' => 'A static page, to check page.html.',
    'post_type'    => 'page',
    'post_status'  => 'publish',
));

flush_rewrite_rules();
