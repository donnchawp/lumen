<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <?php if (is_front_page() && is_home()) : ?>
        <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
    <?php else : ?>
        <p class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></p>
    <?php endif; ?>

    <?php
    $description = get_bloginfo('description', 'display');
    if ($description || is_customize_preview()) :
    ?>
        <p class="site-description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <?php if (has_nav_menu('primary')) : ?>
        <nav class="site-nav">
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
