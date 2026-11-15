<?php
/**
 * Formulaire d'ajout. La quantité d'atelier est celle du configurateur.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

global $product;

if (!$product instanceof WC_Product || !$product->is_purchasable()) {
    return;
}

echo wc_get_stock_html($product);

if (!$product->is_in_stock()) {
    return;
}

$configured = function_exists('bachedirect_is_configured') && bachedirect_is_configured($product);
?>
<form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data">
    <?php do_action('woocommerce_before_add_to_cart_button'); ?>
    <?php
    if (!$configured) {
        woocommerce_quantity_input([
            'min_value' => $product->get_min_purchase_quantity(),
            'max_value' => $product->get_max_purchase_quantity(),
            'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(),
        ]);
    }
    ?>
    <button type="submit" name="add-to-cart" value="<?php echo esc_attr((string) $product->get_id()); ?>" class="single_add_to_cart_button button alt bd-btn">
        <?php echo esc_html($product->single_add_to_cart_text()); ?>
    </button>
    <?php do_action('woocommerce_after_add_to_cart_button'); ?>
</form>
