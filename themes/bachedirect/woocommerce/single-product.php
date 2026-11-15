<?php
/**
 * Fiche produit.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    global $product;
    if (!$product instanceof WC_Product) {
        $loaded = wc_get_product(get_the_ID());
        if ($loaded instanceof WC_Product) {
            $product = $loaded;
        }
    }
    if (!$product instanceof WC_Product) {
        continue;
    }
    ?>
    <article <?php wc_product_class('bd-product', $product); ?>>
        <div class="bd-wrap">
            <?php do_action('woocommerce_before_single_product'); ?>
            <div class="bd-product-layout">
                <div class="bd-product-art">
                    <?php bd_theme_art_for_product($product); ?>
                </div>
                <div class="bd-product-summary">
                    <p class="bd-kicker"><?php echo esc_html(bd_theme_product_family($product)); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <?php if ($product->get_short_description()) : ?>
                        <p class="bd-lede"><?php echo esc_html(wp_strip_all_tags($product->get_short_description())); ?></p>
                    <?php endif; ?>
                    <?php woocommerce_template_single_add_to_cart(); ?>
                    <div class="bd-prose"><?php the_content(); ?></div>
                </div>
            </div>
        </div>
    </article>
    <?php
endwhile;

get_footer();
