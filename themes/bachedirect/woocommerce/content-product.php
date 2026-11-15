<?php
/**
 * Carte catalogue.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

global $product;
if (!$product instanceof WC_Product || !$product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('bd-card', $product); ?>>
    <a href="<?php echo esc_url($product->get_permalink()); ?>">
        <div class="bd-card-art"><?php bd_theme_art_for_product($product); ?></div>
        <div class="bd-card-body">
            <p class="bd-kicker"><?php echo esc_html(bd_theme_product_family($product)); ?></p>
            <h2><?php echo esc_html($product->get_name()); ?></h2>
            <p><?php echo esc_html(wp_strip_all_tags($product->get_short_description())); ?></p>
            <p class="bd-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
        </div>
    </a>
</li>
