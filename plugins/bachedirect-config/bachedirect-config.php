<?php
/**
 * Plugin Name: Bâche Direct — configuration
 * Description: Configurateur (format, matière, finition, accessoires, quantité, délai), prix d'exemple et export XML fournisseur. Sans API fournisseur.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Author: Bâche Direct
 * Text Domain: bachedirect-config
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

define('BACHEDIRECT_CONFIG_FILE', __FILE__);
define('BACHEDIRECT_CONFIG_DIR', plugin_dir_path(__FILE__));

require_once BACHEDIRECT_CONFIG_DIR . 'includes/class-bd-catalog.php';
require_once BACHEDIRECT_CONFIG_DIR . 'includes/class-bd-pricing.php';
require_once BACHEDIRECT_CONFIG_DIR . 'includes/class-bd-xml.php';
require_once BACHEDIRECT_CONFIG_DIR . 'includes/class-bd-plugin.php';

/**
 * Compatibilité avec le stockage des commandes WooCommerce (HPOS).
 */
function bachedirect_declare_hpos(): void {
    if (!class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        return;
    }
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', BACHEDIRECT_CONFIG_FILE, true);
}

add_action('before_woocommerce_init', 'bachedirect_declare_hpos');
add_action('plugins_loaded', 'bachedirect_declare_hpos', 0);
add_action('plugins_loaded', ['BD_Plugin', 'boot']);

function bachedirect_is_configured(mixed $product): bool {
    return class_exists('BD_Plugin') && BD_Plugin::catalog_id($product) !== '';
}
