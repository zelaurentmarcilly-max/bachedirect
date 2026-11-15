<?php
/**
 * Visuels originaux, en SVG. Aucun fichier de marque repris ailleurs.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

function bd_theme_art(string $art, array $opts = []): void {
    static $n = 0;
    $n++;
    $id = 'bdart' . $n;
    $class = 'bd-art';
    if (!empty($opts['grommets'])) {
        $class .= ' is-grommets';
    }
    if (!empty($opts['sleeve'])) {
        $class .= ' is-sleeve';
    }
    echo '<svg class="' . esc_attr($class) . '" viewBox="0 0 360 240" role="img" aria-hidden="true">';
    echo '<rect width="360" height="240" fill="#f4f1ea"/>';
    switch ($art) {
        case 'mesh':
            bd_theme_art_banner($id, 'mesh');
            break;
        case 'textile':
            bd_theme_art_banner($id, 'textile');
            break;
        case 'goutte':
            bd_theme_art_drop();
            break;
        case 'rectangle':
            bd_theme_art_rect_flag();
            break;
        case 'plume':
            bd_theme_art_plume();
            break;
        case 'rollup':
            bd_theme_art_rollup();
            break;
        case 'rail':
            bd_theme_art_rail();
            break;
        case 'cadre':
            bd_theme_art_frame();
            break;
        case 'accessoire':
            bd_theme_art_kit();
            break;
        default:
            bd_theme_art_banner($id, 'vinyle');
            break;
    }
    echo '</svg>';
}

function bd_theme_art_for_product(WC_Product $product): void {
    if ($product->get_image_id()) {
        echo $product->get_image('woocommerce_single');
        return;
    }
    $art = (string) $product->get_meta('_bd_art');
    $key = (string) $product->get_meta('_bd_product_id');
    if ($key === '') {
        $key = (string) $product->get_sku();
    }
    if ($art === '' && class_exists('BD_Catalog')) {
        $row = BD_Catalog::product($key);
        $art = (string) ($row['art'] ?? 'bache');
    }
    $finish = '';
    if (class_exists('BD_Pricing') && $key !== '') {
        $finish = (string) (BD_Pricing::default_input($key)['finishId'] ?? '');
    }
    bd_theme_art($art !== '' ? $art : 'bache', [
        'grommets' => str_contains($finish, 'oeillet'),
        'sleeve' => str_contains($finish, 'fourreau'),
    ]);
}

function bd_theme_art_banner(string $id, string $kind): void {
    if ($kind === 'mesh') {
        echo '<pattern id="' . esc_attr($id) . '" width="12" height="12" patternUnits="userSpaceOnUse">';
        echo '<circle cx="2" cy="2" r="1" fill="#17191c" opacity="0.4"/>';
        echo '</pattern>';
    }
    echo '<rect x="58" y="46" width="244" height="140" fill="' . ($kind === 'mesh' ? 'url(#' . esc_attr($id) . ')' : '#fbfaf6') . '" stroke="#17191c" stroke-width="1.75"/>';
    if ($kind === 'textile') {
        echo '<g stroke="#1e3a6e" stroke-width="1" opacity="0.55">';
        foreach ([70, 92, 114, 136, 158] as $y) {
            echo '<path d="M70 ' . $y . 'h220" fill="none"/>';
        }
        echo '</g>';
    }
    echo '<path d="M58 32v14M44 46h14M302 32v14M316 46h-14M58 200v14M44 186h14M302 200v14M316 186h-14" fill="none" stroke="#1e3a6e" stroke-width="1.25"/>';
    echo '<g class="bd-sleeve" stroke="#d85a2a" stroke-width="5"><path d="M58 52h244M58 180h244"/></g>';
    echo '<g class="bd-grommets" fill="none" stroke="#d85a2a" stroke-width="1.4">';
    foreach ([86, 140, 194, 248] as $x) {
        echo '<circle cx="' . $x . '" cy="62" r="4"/>';
        echo '<circle cx="' . $x . '" cy="170" r="4"/>';
    }
    echo '</g>';
}

function bd_theme_art_drop(): void {
    echo '<path d="M178 34c40 30 62 74 62 112 0 34-26 54-62 54s-62-20-62-54c0-38 22-82 62-112z" fill="#fbfaf6" stroke="#17191c" stroke-width="1.75"/>';
    echo '<path d="M178 42v150" stroke="#1e3a6e" stroke-width="3"/>';
    echo '<path d="M132 196h92M150 196v16M206 196v16M138 212h80" fill="none" stroke="#17191c" stroke-width="1.75"/>';
}

function bd_theme_art_rect_flag(): void {
    echo '<path d="M150 36h92v150h-92z" fill="#fbfaf6" stroke="#17191c" stroke-width="1.75"/>';
    echo '<path d="M150 36v168" stroke="#1e3a6e" stroke-width="3"/>';
    echo '<path d="M118 204h96" stroke="#17191c" stroke-width="1.75"/>';
}

function bd_theme_art_plume(): void {
    echo '<path d="M188 28c26 18 34 58 34 108 0 42-12 72-34 84-8-28-12-62-12-96 0-40 4-72 12-96z" fill="#fbfaf6" stroke="#17191c" stroke-width="1.75"/>';
    echo '<path d="M176 36v156" stroke="#1e3a6e" stroke-width="3"/>';
    echo '<path d="M140 204h88" stroke="#17191c" stroke-width="1.75"/>';
}

function bd_theme_art_rollup(): void {
    echo '<rect x="118" y="40" width="124" height="132" fill="#fbfaf6" stroke="#17191c" stroke-width="1.75"/>';
    echo '<rect x="104" y="168" width="152" height="22" rx="2" fill="#17191c"/>';
    echo '<path d="M132 190v16M228 190v16" stroke="#1e3a6e" stroke-width="3"/>';
    echo '<path d="M118 52h124" stroke="#d85a2a" stroke-width="1.5"/>';
}

function bd_theme_art_rail(): void {
    echo '<rect x="48" y="78" width="264" height="14" fill="#17191c"/>';
    foreach ([78, 132, 186, 240, 286] as $x) {
        echo '<rect x="' . $x . '" y="92" width="10" height="16" fill="#1e3a6e"/>';
    }
    echo '<path d="M70 150h220" stroke="#17191c" stroke-width="1.25" stroke-dasharray="4 6"/>';
}

function bd_theme_art_frame(): void {
    echo '<rect x="96" y="36" width="168" height="150" fill="none" stroke="#17191c" stroke-width="8"/>';
    echo '<path d="M112 186v22M248 186v22M96 208h168" fill="none" stroke="#1e3a6e" stroke-width="2"/>';
}

function bd_theme_art_kit(): void {
    echo '<g fill="none" stroke="#17191c" stroke-width="1.75">';
    echo '<circle cx="110" cy="120" r="28"/>';
    echo '<circle cx="180" cy="120" r="28"/>';
    echo '<circle cx="250" cy="120" r="28"/>';
    echo '</g>';
    echo '<path d="M250 92c18-16 36-8 36 10" fill="none" stroke="#1e3a6e" stroke-width="2"/>';
}
