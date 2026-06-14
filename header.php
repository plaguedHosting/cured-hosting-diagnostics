<?php
/**
 * Theme Header Template
 *
 * Minimal, accessible header for WordPress themes.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header id="site-header" role="banner">
    <div class="site-branding">
        <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?>
        <?php else : ?>
            <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                <h1><?php bloginfo( 'name' ); ?></h1>
            </a>
            <p class="site-description"><?php bloginfo( 'description' ); ?></p>
        <?php endif; ?>
    </div>

    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'textdomain' ); ?>">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'menu-1',
            'menu_id'        => 'primary-menu',
        ) );
        ?>
    </nav>
</header>

<main id="site-content" role="main">
