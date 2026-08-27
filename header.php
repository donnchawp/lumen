<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'lumen'); ?></a>

<header class="site-header">
    <?php
    // An h1 only where the site title is the page's own heading. Everywhere else
    // the h1 belongs to the post or the archive, so this drops to a <p>. Only
    // the wrapper changes, so the link itself is written once.
    printf(
        '<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>',
        (is_front_page() && is_home()) ? 'h1' : 'p',
        esc_url(home_url('/')),
        esc_html(get_bloginfo('name'))
    );
    ?>

    <?php
    $lumen_description = get_bloginfo('description');
    if ($lumen_description || is_customize_preview()) :
    ?>
        <p class="site-description"><?php echo esc_html($lumen_description); ?></p>
    <?php endif; ?>

    <?php if (has_nav_menu('primary')) : ?>
        <nav class="site-nav" aria-label="<?php esc_attr_e('Primary menu', 'lumen'); ?>">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'depth'          => 1,
                'fallback_cb'    => false,
            ));
            ?>
        </nav>
    <?php endif; ?>
</header>
