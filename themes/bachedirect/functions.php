<?php
/**
 * Thème Bâche Direct.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

require get_template_directory() . '/inc/art.php';

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 640,
        'single_image_width' => 800,
    ]);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('bachedirect', get_template_directory_uri() . '/assets/theme.css', [], '1.0.0');
    wp_enqueue_script('bachedirect', get_template_directory_uri() . '/assets/theme.js', [], '1.0.0', true);
});

add_filter('woocommerce_page_title', function (string $title): string {
    return function_exists('is_shop') && is_shop() ? 'Catalogue' : $title;
});

function bd_theme_shop_url(): string {
    if (function_exists('wc_get_page_id')) {
        $shop = wc_get_page_id('shop');
        if ($shop > 0) {
            $url = get_permalink($shop);
            if (is_string($url)) {
                return $url;
            }
        }
    }
    return home_url('/');
}

function bd_theme_cart_url(): string {
    return function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
}

function bd_theme_cart_count(): int {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }
    return (int) WC()->cart->get_cart_contents_count();
}

function bd_theme_family_url(string $slug): string {
    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term instanceof WP_Term) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) {
            return $link;
        }
    }
    return bd_theme_shop_url();
}

function bd_theme_product_family(WC_Product $product): string {
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (is_array($terms) && isset($terms[0]->name)) {
        return (string) $terms[0]->name;
    }
    return '';
}

/** @return array<int, array<string, string>> */
function bd_theme_families(): array {
    if (class_exists('BD_Catalog')) {
        $families = [];
        foreach (BD_Catalog::families() as $family) {
            $families[] = [
                'slug' => (string) ($family['slug'] ?? ''),
                'name' => (string) ($family['name'] ?? ''),
                'blurb' => (string) ($family['blurb'] ?? ''),
                'art' => (string) ($family['art'] ?? 'bache'),
            ];
        }
        return $families;
    }
    return [
        ['slug' => 'baches-vinyle', 'name' => 'Bâches vinyle', 'blurb' => 'Toile PVC pour façade, palissade ou fond de stand.', 'art' => 'bache'],
        ['slug' => 'baches-mesh', 'name' => 'Bâches mesh', 'blurb' => 'Toile micro-perforée pour les prises au vent.', 'art' => 'mesh'],
        ['slug' => 'baches-textile', 'name' => 'Bâches textile', 'blurb' => 'Polyester souple pour stand et intérieur.', 'art' => 'textile'],
        ['slug' => 'oriflammes', 'name' => 'Oriflammes', 'blurb' => 'Goutte, rectangle et plume, mât et pied compris.', 'art' => 'goutte'],
        ['slug' => 'roll-ups', 'name' => 'Roll-ups', 'blurb' => 'Cassettes à enrouleur, formats d\'atelier.', 'art' => 'rollup'],
        ['slug' => 'accessoires', 'name' => 'Accessoires', 'blurb' => 'Lest, sandows, crochets et housses.', 'art' => 'accessoire'],
    ];
}
