<?php
/**
 * Brancher le configurateur, le panier et l'export XML sur WooCommerce.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

final class BD_Plugin {
    /** @var array<int, array<string, mixed>> */
    private static array $pending = [];

    public static function boot(): void {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [self::class, 'need_woocommerce']);
            return;
        }

        add_action('wp_enqueue_scripts', [self::class, 'assets']);
        add_action('wp_ajax_bachedirect_quote', [self::class, 'ajax']);
        add_action('wp_ajax_nopriv_bachedirect_quote', [self::class, 'ajax']);
        add_action('woocommerce_before_add_to_cart_button', [self::class, 'fields']);
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'validate'], 10, 3);
        add_filter('woocommerce_add_to_cart_quantity', [self::class, 'quantity'], 10, 2);
        add_filter('woocommerce_add_cart_item_data', [self::class, 'cart_data'], 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', [self::class, 'cart_session'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [self::class, 'prices'], 20);
        add_action('woocommerce_cart_calculate_fees', [self::class, 'fees']);
        add_filter('woocommerce_get_item_data', [self::class, 'item_data'], 10, 2);
        add_filter('woocommerce_cart_item_quantity', [self::class, 'lock_qty'], 10, 3);
        add_action('woocommerce_after_cart_item_quantity_update', [self::class, 'keep_qty'], 10, 4);
        add_action('woocommerce_checkout_create_order_line_item', [self::class, 'order_item'], 10, 4);
        add_filter('woocommerce_get_price_html', [self::class, 'price_html'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [self::class, 'button_text'], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [self::class, 'loop_link'], 10, 2);
        add_filter('woocommerce_product_supports', [self::class, 'supports'], 10, 3);
        add_filter('woocommerce_hidden_order_itemmeta', [self::class, 'hidden_meta']);
        add_filter('woocommerce_order_actions', [self::class, 'order_actions']);
        add_action('woocommerce_order_action_bachedirect_export_xml', [self::class, 'action_export']);
        add_action('woocommerce_admin_order_data_after_order_details', [self::class, 'order_panel']);
        add_action('admin_post_bachedirect_export_xml', [self::class, 'download_xml']);
        add_action('admin_menu', [self::class, 'menu']);
        add_action('woocommerce_shipping_init', [self::class, 'shipping_init']);
        add_filter('woocommerce_shipping_methods', [self::class, 'shipping_methods']);
    }

    public static function need_woocommerce(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-warning"><p>Bâche Direct — configuration a besoin de WooCommerce.</p></div>';
    }

    public static function catalog_id(mixed $product): string {
        if (is_numeric($product)) {
            $product = wc_get_product((int) $product);
        }
        if (!$product instanceof WC_Product) {
            return '';
        }
        $meta = (string) $product->get_meta('_bd_product_id');
        $sku = (string) $product->get_sku();
        $table = BD_Pricing::table()['products'] ?? [];
        if ($meta !== '' && isset($table[$meta])) {
            return $meta;
        }
        if ($sku !== '' && isset($table[$sku])) {
            return $sku;
        }
        return '';
    }

    public static function assets(): void {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        wp_enqueue_style(
            'bachedirect-config',
            plugins_url('assets/configurator.css', BACHEDIRECT_CONFIG_FILE),
            [],
            '1.0.0'
        );
        wp_enqueue_script(
            'bachedirect-config',
            plugins_url('assets/configurator.js', BACHEDIRECT_CONFIG_FILE),
            [],
            '1.0.0',
            true
        );
        wp_localize_script('bachedirect-config', 'bachedirectConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bachedirect_quote'),
        ]);
    }

    public static function ajax(): void {
        check_ajax_referer('bachedirect_quote', 'nonce');
        $id = isset($_POST['bd_product_id']) ? sanitize_key(wp_unslash($_POST['bd_product_id'])) : '';
        if ($id === '' || !isset(BD_Pricing::table()['products'][$id])) {
            wp_send_json(['ok' => false, 'errors' => ['Article inconnu.'], 'lines' => [], 'total' => '', 'ready' => '', 'note' => '']);
        }
        $result = BD_Pricing::quote(self::input_from_post($id));
        wp_send_json(self::payload($result));
    }

    public static function fields(): void {
        global $product;
        $id = self::catalog_id($product);
        if ($id === '') {
            return;
        }
        $rule = BD_Pricing::table()['products'][$id];
        $catalog = BD_Catalog::product($id);
        $input = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['bd_product_id']))
            ? self::input_from_post($id)
            : BD_Pricing::default_input($id);
        $result = BD_Pricing::quote($input);
        $payload = self::payload($result);
        $mode = (string) ($rule['mode'] ?? '');
        echo '<div class="bd-config">';
        echo '<input type="hidden" name="bd_product_id" value="' . esc_attr($id) . '">';

        if ($mode === 'surface' || $mode === 'length') {
            $widthLabel = $mode === 'length' ? 'Longueur (cm)' : 'Largeur (cm)';
            $heightLabel = $mode === 'length' ? 'Hauteur de toile, indicative (cm)' : 'Hauteur (cm)';
            echo '<div class="bd-grid-2">';
            self::number_field('bd_width_cm', $widthLabel, (int) $input['widthCm'], (int) ($rule['minWidthCm'] ?? 1), (int) ($rule['maxWidthCm'] ?? 1000));
            self::number_field('bd_height_cm', $heightLabel, (int) $input['heightCm'], (int) ($rule['minHeightCm'] ?? 1), (int) ($rule['maxHeightCm'] ?? 1000));
            echo '</div>';
            echo '<p class="bd-hint">Entre ' . (int) $rule['minWidthCm'] . ' et ' . (int) $rule['maxWidthCm'] . ' cm de large, '
                . (int) $rule['minHeightCm'] . ' et ' . (int) $rule['maxHeightCm'] . ' cm de haut.</p>';
            if ($mode === 'length') {
                echo '<p class="bd-hint">La hauteur est notée pour l\'atelier. Elle n\'entre pas dans le prix.</p>';
            }
        } elseif ($mode === 'preset') {
            echo '<label class="bd-field"><span>Format</span><select name="bd_preset_id">';
            foreach ($rule['presets'] ?? [] as $presetId => $preset) {
                echo '<option value="' . esc_attr((string) $presetId) . '"' . selected($input['presetId'], $presetId, false) . '>'
                    . esc_html((string) $preset['label']) . '</option>';
            }
            echo '</select></label>';
        } else {
            echo '<p class="bd-hint">Article au lot, sans format à saisir.</p>';
        }

        self::choice_field('bd_material_id', 'Matière', $input['materialId'], $rule['materials'] ?? [], BD_Pricing::table()['materials'] ?? []);
        self::choice_field('bd_finish_id', 'Finition', $input['finishId'], $rule['finishes'] ?? [], BD_Pricing::table()['finishes'] ?? []);

        $allowed = $rule['accessories'] ?? [];
        if ($allowed) {
            echo '<fieldset class="bd-field"><legend class="bd-legend">Accessoires</legend>';
            foreach ($allowed as $accId) {
                $acc = BD_Pricing::table()['accessories'][$accId] ?? null;
                if (!is_array($acc)) {
                    continue;
                }
                $current = 0;
                foreach ($input['accessories'] as $row) {
                    if (($row['id'] ?? '') === $accId) {
                        $current = (int) $row['quantity'];
                    }
                }
                $price = BD_Pricing::format_euro(BD_Pricing::money_to_cents((string) $acc['unitHt']));
                echo '<label class="bd-acc"><span>' . esc_html((string) $acc['label']) . ' <small>' . esc_html($price) . ' HT</small></span>';
                echo '<input type="number" name="bd_acc[' . esc_attr((string) $accId) . ']" min="0" max="20" step="1" inputmode="numeric" value="' . esc_attr((string) $current) . '">';
                echo '</label>';
            }
            echo '</fieldset>';
        }

        self::number_field('bd_quantity', 'Quantité', (int) $input['quantity'], 1, 99);

        echo '<fieldset class="bd-field"><legend class="bd-legend">Délai</legend><div class="bd-choices">';
        foreach (BD_Pricing::table()['leadTimes'] ?? [] as $leadId => $lead) {
            echo '<label><input type="radio" name="bd_lead_time_id" value="' . esc_attr((string) $leadId) . '"'
                . checked($input['leadTimeId'], $leadId, false) . '> <span><strong>' . esc_html((string) $lead['label'])
                . '</strong><small>' . (int) $lead['businessDays'] . ' jours ouvrés</small></span></label>';
        }
        echo '</div></fieldset>';

        if (!empty($catalog['file'])) {
            $mode = sanitize_key(self::post_string('bd_file_mode', 'differe'));
            if (!in_array($mode, ['differe', 'reference'], true)) {
                $mode = 'differe';
            }
            $ref = esc_attr(self::clean(self::post_string('bd_file_reference'), 80));
            echo '<fieldset class="bd-field"><legend class="bd-legend">Fichier d\'impression</legend><div class="bd-choices">';
            echo '<label><input type="radio" name="bd_file_mode" value="differe"' . checked($mode, 'differe', false) . '> <span>Dépôt après confirmation</span></label>';
            echo '<label><input type="radio" name="bd_file_mode" value="reference"' . checked($mode, 'reference', false) . '> <span>Référence déjà transmise</span></label>';
            echo '</div></fieldset>';
            echo '<label class="bd-field bd-file-ref"' . ($mode === 'reference' ? '' : ' hidden') . '><span>Référence du fichier</span>';
            echo '<input type="text" name="bd_file_reference" maxlength="80" value="' . $ref . '"></label>';
        }

        echo '<div class="bd-quote" aria-live="polite">';
        echo '<p class="bd-quote-kicker">Prix d\'exemple, hors taxes</p>';
        echo '<p class="bd-quote-total" data-bd-total>' . esc_html($payload['total'] !== '' ? $payload['total'] : '—') . '</p>';
        echo '<div class="bd-lines" data-bd-lines>';
        foreach ($payload['lines'] as $line) {
            echo '<div><span>' . esc_html($line['label']) . '</span><span>' . esc_html($line['amount']) . '</span></div>';
        }
        echo '</div>';
        echo '<p data-bd-ready>' . esc_html($payload['ready']) . '</p>';
        echo '<p class="bd-note" data-bd-note>' . esc_html($payload['note']) . '</p>';
        echo '<p class="bd-error" data-bd-error role="alert">' . esc_html(implode(' ', $payload['errors'])) . '</p>';
        echo '</div></div>';
    }

    public static function validate(bool $passed, int $productId, int $qty): bool {
        $id = self::catalog_id($productId);
        if ($id === '') {
            return $passed;
        }
        $input = self::input_from_post($id);
        $result = BD_Pricing::quote($input);
        $errors = $result['ok'] ? [] : $result['errors'];
        $catalog = BD_Catalog::product($id);
        $file = self::file_from_post($catalog);
        if (!empty($catalog['file']) && $file['mode'] === 'reference' && $file['reference'] === '') {
            $errors[] = 'Indiquez la référence du fichier déjà transmis.';
        }
        if ($errors) {
            wc_add_notice(implode(' ', $errors), 'error');
            return false;
        }
        self::$pending[$productId] = self::decorate($result['quote'], $catalog, $file);
        return $passed;
    }

    public static function quantity(int $quantity, int $productId): int {
        if (self::catalog_id($productId) === '' || !isset($_POST['bd_quantity']) || is_array($_POST['bd_quantity'])) {
            return $quantity;
        }
        $n = BD_Pricing::as_int(self::post_string('bd_quantity'));
        if ($n === null) {
            return $quantity;
        }
        return max(1, min(99, $n));
    }

    /** @param array<string, mixed> $data */
    public static function cart_data(array $data, int $productId): array {
        $quote = self::$pending[$productId] ?? null;
        if (!is_array($quote)) {
            $id = self::catalog_id($productId);
            if ($id === '') {
                return $data;
            }
            $result = BD_Pricing::quote(self::input_from_post($id));
            if (!$result['ok']) {
                return $data;
            }
            $quote = self::decorate($result['quote'], BD_Catalog::product($id), self::file_from_post(BD_Catalog::product($id)));
        }
        $data['bd_quote'] = $quote;
        $data['bd_key'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('bd', true);
        return $data;
    }

    /** @param array<string, mixed> $item
     * @param array<string, mixed> $values
     */
    public static function cart_session(array $item, array $values): array {
        if (isset($values['bd_quote'])) {
            $item['bd_quote'] = $values['bd_quote'];
        }
        if (isset($values['bd_key'])) {
            $item['bd_key'] = $values['bd_key'];
        }
        return $item;
    }

    public static function prices(WC_Cart $cart): void {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        static $running = false;
        if ($running) {
            return;
        }
        $running = true;
        foreach ($cart->get_cart() as $item) {
            if (empty($item['bd_quote']['piece_cents']) || !$item['data'] instanceof WC_Product) {
                continue;
            }
            $quote = $item['bd_quote'];
            $item['data']->set_price(((int) $quote['piece_cents']) / 100);
            $qty = max(1, (int) $quote['quantity']);
            $item['data']->set_weight(wc_format_decimal(((int) $quote['weight_grams']) / $qty / 1000, 3));
        }
        $running = false;
    }

    public static function fees(WC_Cart $cart): void {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        $cents = 0;
        foreach ($cart->get_cart() as $item) {
            $cents += (int) ($item['bd_quote']['accessories_cents'] ?? 0);
        }
        if ($cents > 0) {
            $cart->add_fee('Accessoires', $cents / 100, true);
        }
    }

    /** @param array<int, array<string, string>> $data
     * @param array<string, mixed> $item
     */
    public static function item_data(array $data, array $item): array {
        if (empty($item['bd_quote']) || !is_array($item['bd_quote'])) {
            return $data;
        }
        foreach (self::visible_meta($item['bd_quote']) as $row) {
            $data[] = ['key' => $row['key'], 'value' => $row['value']];
        }
        return $data;
    }

    public static function lock_qty(string $html, string $key, array $item): string {
        if (empty($item['bd_quote']['quantity'])) {
            return $html;
        }
        $n = (int) $item['bd_quote']['quantity'];
        return '<span class="bd-qty-lock">' . esc_html((string) $n) . '</span>'
            . '<input type="hidden" name="cart[' . esc_attr($key) . '][qty]" value="' . esc_attr((string) $n) . '">';
    }

    public static function keep_qty(string $key, int $qty, int $old, WC_Cart $cart): void {
        $item = $cart->get_cart_item($key);
        if (empty($item['bd_quote']['quantity'])) {
            return;
        }
        $expected = (int) $item['bd_quote']['quantity'];
        if ($qty !== $expected) {
            $cart->set_quantity($key, $expected, false);
        }
    }

    public static function order_item(WC_Order_Item_Product $item, string $key, array $values, WC_Order $order): void {
        if (empty($values['bd_quote']) || !is_array($values['bd_quote'])) {
            return;
        }
        $quote = $values['bd_quote'];
        $item->add_meta_data('_bd_quote', $quote, true);
        foreach (self::visible_meta($quote) as $row) {
            $item->add_meta_data($row['key'], $row['value'], true);
        }
        unset($order);
    }

    public static function price_html(string $html, WC_Product $product): string {
        $id = self::catalog_id($product);
        if ($id === '') {
            return $html;
        }
        $result = BD_Pricing::quote(BD_Pricing::default_input($id));
        if (!$result['ok']) {
            return $html;
        }
        return '<span class="bd-from">dès ' . esc_html(BD_Pricing::format_euro((int) $result['quote']['piece_cents'])) . ' HT</span>';
    }

    public static function button_text(string $text, WC_Product $product): string {
        return self::catalog_id($product) !== '' ? 'Ajouter au panier' : $text;
    }

    public static function loop_link(string $html, WC_Product $product): string {
        if (self::catalog_id($product) === '') {
            return $html;
        }
        return '<a class="button" href="' . esc_url($product->get_permalink()) . '">Configurer</a>';
    }

    public static function supports(bool $supports, string $feature, WC_Product $product): bool {
        if ($feature === 'ajax_add_to_cart' && self::catalog_id($product) !== '') {
            return false;
        }
        return $supports;
    }

    /** @param array<int, string> $keys */
    public static function hidden_meta(array $keys): array {
        $keys[] = '_bd_quote';
        return $keys;
    }

    /** @param array<string, string> $actions */
    public static function order_actions(array $actions): array {
        $actions['bachedirect_export_xml'] = 'Exporter le XML fournisseur';
        return $actions;
    }

    public static function action_export(WC_Order $order): void {
        wp_safe_redirect(self::export_url($order->get_id()));
        exit;
    }

    public static function order_panel(WC_Order $order): void {
        echo '<p class="form-field form-field-wide"><a class="button button-primary" href="' . esc_url(self::export_url($order->get_id())) . '">Télécharger le XML fournisseur</a></p>';
        echo '<p>Fichier atelier, sur le modèle de la boutique. Rien n\'est envoyé à un fournisseur.</p>';
    }

    public static function download_xml(): void {
        $orderId = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if ($orderId < 1 || !current_user_can('edit_shop_order', $orderId)) {
            wp_die(esc_html('Commande introuvable.'), 403);
        }
        check_admin_referer('bachedirect_export_' . $orderId);
        $order = wc_get_order($orderId);
        if (!$order instanceof WC_Order) {
            wp_die(esc_html('Commande introuvable.'), 404);
        }
        $xml = BD_Xml::from_wc_order($order);
        $order->add_order_note('XML fournisseur téléchargé.');
        $order->save();
        $number = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $order->get_order_number()) ?? 'commande';
        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bachedirect-' . $number . '.xml"');
        echo $xml;
        exit;
    }

    public static function menu(): void {
        add_submenu_page(
            'woocommerce',
            'Bâche Direct',
            'Bâche Direct',
            'manage_woocommerce',
            'bachedirect',
            [self::class, 'admin_page']
        );
    }

    public static function admin_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        echo '<div class="wrap"><h1>Bâche Direct</h1>';
        echo '<p>Le barème d\'exemple est dans <code>wp-content/plugins/bachedirect-config/price-table.php</code> (version '
            . esc_html((string) (BD_Pricing::table()['version'] ?? '')) . '). Modifiez les nombres, gardez les clés. Aucun appel fournisseur.</p>';
        echo '<p>Les produits s\'importent depuis <strong>Produits → Tous les produits → Importer</strong>, avec le fichier <code>bachedirect-produits.csv</code>. '
            . 'Treize articles du catalogue, pas davantage. Le pas à pas est dans <code>woocommerce/README.md</code>.</p>';
        echo '<p>Sur une commande, le bouton « Télécharger le XML fournisseur » écrit le fichier atelier. Le paiement reste celui de WooCommerce (le virement suffit).</p>';
        echo '</div>';
    }

    public static function shipping_init(): void {
        require_once BACHEDIRECT_CONFIG_DIR . 'includes/class-bd-shipping.php';
    }

    /** @param array<string, string> $methods */
    public static function shipping_methods(array $methods): array {
        $methods['bachedirect_port'] = 'BD_Shipping_Method';
        return $methods;
    }

    /** @return array<string, mixed> */
    private static function input_from_post(string $productId): array {
        $defaults = BD_Pricing::default_input($productId);
        $width = BD_Pricing::as_int(self::post_string('bd_width_cm', (string) $defaults['widthCm']));
        $height = BD_Pricing::as_int(self::post_string('bd_height_cm', (string) $defaults['heightCm']));
        $quantity = BD_Pricing::as_int(self::post_string('bd_quantity', '1'));
        $preset = sanitize_key(self::post_string('bd_preset_id', (string) $defaults['presetId']));
        $material = sanitize_key(self::post_string('bd_material_id', (string) $defaults['materialId']));
        $finish = sanitize_key(self::post_string('bd_finish_id', (string) $defaults['finishId']));
        $lead = sanitize_key(self::post_string('bd_lead_time_id', (string) $defaults['leadTimeId']));
        $accessories = [];
        if (isset($_POST['bd_acc']) && is_array($_POST['bd_acc'])) {
            foreach (wp_unslash($_POST['bd_acc']) as $accId => $accQty) {
                $parsed = BD_Pricing::as_int($accQty);
                $accessories[] = [
                    'id' => sanitize_key((string) $accId),
                    'quantity' => $parsed ?? -1,
                ];
            }
        }
        return [
            'productId' => $productId,
            'widthCm' => $width ?? -1,
            'heightCm' => $height ?? -1,
            'presetId' => $preset,
            'materialId' => $material,
            'finishId' => $finish,
            'accessories' => $accessories,
            'quantity' => $quantity ?? -1,
            'leadTimeId' => $lead,
        ];
    }

    /**
     * @param array<string, mixed>|null $catalog
     * @return array{mode: string, reference: string}
     */
    private static function file_from_post(?array $catalog): array {
        if (empty($catalog['file'])) {
            return ['mode' => 'sans-objet', 'reference' => ''];
        }
        $mode = sanitize_key(self::post_string('bd_file_mode', 'differe'));
        if (!in_array($mode, ['differe', 'reference'], true)) {
            $mode = 'differe';
        }
        $ref = self::clean(self::post_string('bd_file_reference'), 80);
        return ['mode' => $mode, 'reference' => $mode === 'reference' ? $ref : ''];
    }

    /**
     * @param array<string, mixed> $quote
     * @param array<string, mixed>|null $catalog
     * @param array{mode: string, reference: string} $file
     * @return array<string, mixed>
     */
    private static function decorate(array $quote, ?array $catalog, array $file): array {
        $quote['family_id'] = (string) ($catalog['family'] ?? '');
        $quote['file_mode'] = $file['mode'];
        $quote['file_reference'] = $file['reference'];
        return $quote;
    }

    /** @param array{ok: bool, quote?: array<string, mixed>, errors?: array<int, string>} $result
     * @return array<string, mixed>
     */
    private static function payload(array $result): array {
        if (!$result['ok']) {
            return [
                'ok' => false,
                'errors' => $result['errors'] ?? ['Prix indisponible.'],
                'lines' => [],
                'total' => '',
                'ready' => '',
                'note' => '',
            ];
        }
        $quote = $result['quote'];
        $lines = [];
        foreach ($quote['breakdown'] as $line) {
            $lines[] = [
                'label' => (string) $line['label'],
                'amount' => BD_Pricing::format_euro((int) $line['cents']),
            ];
        }
        $note = ((int) $quote['accessories_cents']) > 0
            ? 'Les accessoires de cette ligne sont ajoutés au panier à part, pour le même montant.'
            : '';
        return [
            'ok' => true,
            'errors' => [],
            'lines' => $lines,
            'total' => BD_Pricing::format_euro((int) $quote['line_cents']),
            'ready' => 'Sortie d\'atelier ' . BD_Pricing::format_date((string) $quote['ready_on']),
            'note' => $note,
        ];
    }

    /** @param array<string, mixed> $quote
     * @return array<int, array{key: string, value: string}>
     */
    private static function visible_meta(array $quote): array {
        $rows = [];
        if (!empty($quote['preset_label'])) {
            $rows[] = ['key' => 'Format', 'value' => (string) $quote['preset_label']];
        } elseif ((int) ($quote['width_cm'] ?? 0) > 0 || (int) ($quote['height_cm'] ?? 0) > 0) {
            $rows[] = ['key' => 'Format', 'value' => (int) $quote['width_cm'] . ' × ' . (int) $quote['height_cm'] . ' cm'];
        }
        $rows[] = ['key' => 'Matière', 'value' => (string) ($quote['material_label'] ?? '')];
        $rows[] = ['key' => 'Finition', 'value' => (string) ($quote['finish_label'] ?? '')];
        if (!empty($quote['accessories'])) {
            $parts = [];
            foreach ($quote['accessories'] as $acc) {
                $parts[] = $acc['label'] . ' × ' . (int) $acc['quantity'];
            }
            $rows[] = ['key' => 'Accessoires', 'value' => implode(', ', $parts)];
        }
        $rows[] = ['key' => 'Quantité', 'value' => (string) (int) ($quote['quantity'] ?? 1)];
        $rows[] = ['key' => 'Délai', 'value' => (string) ($quote['lead_label'] ?? '') . ' (' . (int) ($quote['production_days'] ?? 0) . ' jours ouvrés)'];
        $rows[] = ['key' => 'Sortie d\'atelier', 'value' => BD_Pricing::format_date((string) ($quote['ready_on'] ?? ''))];
        $mode = (string) ($quote['file_mode'] ?? '');
        if ($mode === 'reference') {
            $rows[] = ['key' => 'Fichier', 'value' => 'Référence déjà transmise — ' . (string) ($quote['file_reference'] ?? '')];
        } elseif ($mode === 'differe') {
            $rows[] = ['key' => 'Fichier', 'value' => 'Dépôt après confirmation'];
        }
        return $rows;
    }

    /** @param array<int, string> $ids
     * @param array<string, array<string, mixed>> $source
     */
    private static function choice_field(string $name, string $legend, string $current, array $ids, array $source): void {
        if (count($ids) <= 1) {
            $only = (string) ($ids[0] ?? $current);
            $label = (string) ($source[$only]['label'] ?? $only);
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($only) . '">';
            echo '<p class="bd-hint">' . esc_html($legend) . ' : ' . esc_html($label) . '</p>';
            return;
        }
        echo '<fieldset class="bd-field"><legend class="bd-legend">' . esc_html($legend) . '</legend><div class="bd-choices">';
        foreach ($ids as $optionId) {
            $option = $source[$optionId] ?? null;
            if (!is_array($option)) {
                continue;
            }
            echo '<label><input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr((string) $optionId) . '"'
                . checked($current, $optionId, false) . '> <span><strong>' . esc_html((string) $option['label']) . '</strong>';
            if (!empty($option['note'])) {
                echo '<small>' . esc_html((string) $option['note']) . '</small>';
            }
            echo '</span></label>';
        }
        echo '</div></fieldset>';
    }

    private static function number_field(string $name, string $label, int $value, int $min, int $max): void {
        echo '<label class="bd-field"><span>' . esc_html($label) . '</span>';
        echo '<input type="number" name="' . esc_attr($name) . '" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max)
            . '" step="1" inputmode="numeric" required value="' . esc_attr((string) $value) . '"></label>';
    }

    private static function post_string(string $key, string $default = ''): string {
        if (!isset($_POST[$key]) || is_array($_POST[$key])) {
            return $default;
        }
        return (string) wp_unslash($_POST[$key]);
    }

    private static function clean(string $value, int $max): string {
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
        $value = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }
        return substr($value, 0, $max);
    }

    private static function export_url(int $orderId): string {
        return wp_nonce_url(
            admin_url('admin-post.php?action=bachedirect_export_xml&order_id=' . $orderId),
            'bachedirect_export_' . $orderId
        );
    }
}
