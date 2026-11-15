<?php
/**
 * Port d'exemple, lu dans le barème. À activer dans une zone France.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

class BD_Shipping_Method extends WC_Shipping_Method {
    public function __construct($instance_id = 0) {
        $this->id = 'bachedirect_port';
        $this->instance_id = absint($instance_id);
        $this->method_title = 'Port atelier (exemple)';
        $this->method_description = '12,90 € HT + 0,70 €/kg, offert dès 280 € HT. Montants d\'exemple, dans le barème du plugin.';
        $this->supports = ['shipping-zones', 'instance-settings'];
        $this->enabled = 'yes';
        $this->title = 'Port atelier (exemple)';
        $this->init();
    }

    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title', $this->title);
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void {
        $this->instance_form_fields = [
            'title' => [
                'title' => 'Titre affiché',
                'type' => 'text',
                'default' => 'Port atelier (exemple)',
            ],
        ];
    }

    public function calculate_shipping($package = []): void {
        $goods = 0;
        $grams = 0;
        foreach ($package['contents'] ?? [] as $item) {
            if (!empty($item['bd_quote']) && is_array($item['bd_quote'])) {
                $goods += (int) ($item['bd_quote']['line_cents'] ?? 0);
                $grams += (int) ($item['bd_quote']['weight_grams'] ?? 0);
                continue;
            }
            $goods += (int) round(((float) ($item['line_total'] ?? 0)) * 100);
            $product = $item['data'] ?? null;
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($product instanceof WC_Product) {
                $grams += (int) round(((float) $product->get_weight()) * 1000 * $qty);
            }
        }
        $cents = BD_Pricing::shipping_cents($goods, $grams);
        $this->add_rate([
            'id' => $this->get_rate_id(),
            'label' => $cents === 0 ? 'Port offert (exemple)' : $this->title,
            'cost' => $cents / 100,
            'package' => $package,
        ]);
    }
}
