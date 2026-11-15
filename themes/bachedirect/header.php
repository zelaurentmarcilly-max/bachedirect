<?php
/**
 * En-tête.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="bd-skip" href="#contenu">Aller au contenu</a>
<header class="bd-header">
    <div class="bd-wrap bd-header-row">
        <a class="bd-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <svg class="bd-mark" viewBox="0 0 32 32" aria-hidden="true">
                <rect x="1.5" y="1.5" width="29" height="29" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path d="M19 1.5V11H30" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path d="M7 22h12" stroke="#1e3a6e" stroke-width="1.6"/>
            </svg>
            <span>
                <strong>Bâche Direct</strong>
                <em>atelier grand format</em>
            </span>
        </a>
        <button class="bd-nav-toggle" type="button" aria-expanded="false" aria-controls="bd-nav">Menu</button>
        <nav id="bd-nav" class="bd-nav" aria-label="Navigation">
            <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
            <a href="<?php echo esc_url(bd_theme_shop_url()); ?>">Catalogue</a>
        </nav>
        <a class="bd-cart" href="<?php echo esc_url(bd_theme_cart_url()); ?>">
            Panier
            <span class="bd-count"><?php echo esc_html((string) bd_theme_cart_count()); ?></span>
        </a>
    </div>
</header>
<main id="contenu">
