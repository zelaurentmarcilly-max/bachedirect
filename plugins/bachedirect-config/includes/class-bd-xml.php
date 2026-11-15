<?php
/**
 * Fichier fournisseur. Même idée que src/lib/xml.ts : un XML, aucun appel réseau.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || defined('BACHEDIRECT_STANDALONE') || exit;

final class BD_Xml {
    /**
     * @param array<string, mixed> $view
     */
    public static function render(array $view): string {
        $lines = [];
        $index = 0;
        foreach ($view['lines'] ?? [] as $line) {
            $index++;
            $lines[] = self::line($line, $index);
        }
        $payment = $view['payment'] ?? [];
        $totals = $view['totals'] ?? [];
        $client = $view['client'] ?? [];
        $delivery = $view['delivery'] ?? [];
        $vatRate = (float) ($totals['vat_rate'] ?? 0);

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<commande version="1" xmlns="https://bachedirect.fr/ns/commande/1">' . "\n"
            . "  <meta>\n"
            . '    ' . self::tag('boutique', 'bachedirect.fr') . "\n"
            . '    ' . self::tag('numero', (string) ($view['number'] ?? '')) . "\n"
            . '    ' . self::tag('creeLe', (string) ($view['created_at'] ?? '')) . "\n"
            . '    ' . self::tag('devise', (string) ($view['currency'] ?? 'EUR')) . "\n"
            . '    ' . self::tag('statut', (string) ($view['status'] ?? '')) . "\n"
            . '    <paiement mode="' . self::esc((string) ($payment['mode'] ?? '')) . '" encaisse="' . (!empty($payment['paid']) ? 'true' : 'false') . '">' . self::esc((string) ($payment['label'] ?? '')) . "</paiement>\n"
            . '    ' . self::tag('jeuDePrix', (string) ($view['pricing_version'] ?? '')) . "\n"
            . "  </meta>\n"
            . "  <client>\n"
            . '    ' . self::tag('nom', (string) ($client['name'] ?? '')) . "\n"
            . '    ' . self::tag('email', (string) ($client['email'] ?? '')) . "\n"
            . '    ' . self::tag('telephone', (string) ($client['phone'] ?? '')) . "\n"
            . '    ' . self::tag('societe', (string) ($client['company'] ?? '')) . "\n"
            . "  </client>\n"
            . "  <livraison>\n"
            . '    ' . self::tag('ligne1', (string) ($delivery['line1'] ?? '')) . "\n"
            . '    ' . self::tag('ligne2', (string) ($delivery['line2'] ?? '')) . "\n"
            . '    ' . self::tag('codePostal', (string) ($delivery['postal_code'] ?? '')) . "\n"
            . '    ' . self::tag('ville', (string) ($delivery['city'] ?? '')) . "\n"
            . '    ' . self::tag('pays', (string) ($delivery['country'] ?? '')) . "\n"
            . '    ' . self::tag('note', (string) ($delivery['note'] ?? '')) . "\n"
            . "  </livraison>\n"
            . "  <lignes>\n    " . implode("\n    ", $lines) . "\n  </lignes>\n"
            . "  <totaux>\n"
            . '    ' . self::tag('marchandisesHt', BD_Pricing::xml_decimal((int) ($totals['goods_cents'] ?? 0))) . "\n"
            . '    ' . self::tag('portHt', BD_Pricing::xml_decimal((int) ($totals['shipping_cents'] ?? 0))) . "\n"
            . '    <tva taux="' . self::esc(number_format($vatRate * 100, 1, '.', '')) . '">' . self::esc(BD_Pricing::xml_decimal((int) ($totals['vat_cents'] ?? 0))) . "</tva>\n"
            . '    ' . self::tag('totalTtc', BD_Pricing::xml_decimal((int) ($totals['total_cents'] ?? 0))) . "\n"
            . '    ' . self::tag('poidsG', (string) (int) ($totals['weight_grams'] ?? 0)) . "\n"
            . "  </totaux>\n"
            . "</commande>\n";

        return $body;
    }

    public static function from_wc_order(object $order): string {
        $lines = [];
        $goods = 0;
        $grams = 0;
        $version = '';
        foreach ($order->get_items() as $item) {
            $quote = $item->get_meta('_bd_quote', true);
            if (!is_array($quote)) {
                $raw = json_decode((string) $quote, true);
                $quote = is_array($raw) ? $raw : [];
            }
            if ($quote) {
                $goods += (int) ($quote['line_cents'] ?? 0);
                $grams += (int) ($quote['weight_grams'] ?? 0);
                if ($version === '' && !empty($quote['pricing_version'])) {
                    $version = (string) $quote['pricing_version'];
                }
                $lines[] = self::line_from_quote($quote);
                continue;
            }
            $cents = BD_Pricing::money_to_cents((string) $item->get_total());
            $goods += $cents;
            $lines[] = [
                'product_id' => (string) $item->get_product_id(),
                'product_label' => $item->get_name(),
                'family_id' => '',
                'width_cm' => 0,
                'height_cm' => 0,
                'area_m2' => 0,
                'material_id' => '',
                'material_label' => '',
                'finish_id' => '',
                'finish_label' => '',
                'accessories' => [],
                'quantity' => (int) $item->get_quantity(),
                'lead_time_id' => '',
                'lead_label' => '',
                'production_days' => 0,
                'ready_on' => '',
                'file_mode' => 'sans-objet',
                'file_reference' => '',
                'piece_cents' => $cents,
                'accessories_cents' => 0,
                'line_cents' => $cents,
                'weight_grams' => 0,
            ];
        }

        $shippingCents = BD_Pricing::money_to_cents((string) $order->get_shipping_total());
        $vatCents = BD_Pricing::money_to_cents((string) $order->get_total_tax());
        $totalCents = BD_Pricing::money_to_cents((string) $order->get_total());
        $vatRate = 0.0;
        $taxable = $goods + $shippingCents;
        if ($taxable > 0 && $vatCents > 0) {
            $vatRate = $vatCents / $taxable;
        }

        $created = $order->get_date_created();
        $createdAt = $created ? $created->setTimezone(new DateTimeZone('Europe/Paris'))->format('c') : '';
        $method = (string) $order->get_payment_method();
        $mode = $method === 'bacs' ? 'virement' : ($method !== '' ? $method : 'inconnu');

        $ship1 = (string) $order->get_shipping_address_1();
        $useShipping = $ship1 !== '';

        return self::render([
            'number' => (string) $order->get_order_number(),
            'created_at' => $createdAt,
            'currency' => (string) $order->get_currency(),
            'status' => (string) $order->get_status(),
            'pricing_version' => $version !== '' ? $version : (string) (BD_Pricing::table()['version'] ?? ''),
            'payment' => [
                'mode' => $mode,
                'label' => (string) $order->get_payment_method_title(),
                'paid' => (bool) $order->is_paid(),
            ],
            'client' => [
                'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'email' => (string) $order->get_billing_email(),
                'phone' => (string) $order->get_billing_phone(),
                'company' => (string) $order->get_billing_company(),
            ],
            'delivery' => [
                'line1' => $useShipping ? $ship1 : (string) $order->get_billing_address_1(),
                'line2' => $useShipping ? (string) $order->get_shipping_address_2() : (string) $order->get_billing_address_2(),
                'postal_code' => $useShipping ? (string) $order->get_shipping_postcode() : (string) $order->get_billing_postcode(),
                'city' => $useShipping ? (string) $order->get_shipping_city() : (string) $order->get_billing_city(),
                'country' => $useShipping ? (string) $order->get_shipping_country() : (string) $order->get_billing_country(),
                'note' => (string) $order->get_customer_note(),
            ],
            'lines' => $lines,
            'totals' => [
                'goods_cents' => $goods,
                'shipping_cents' => $shippingCents,
                'vat_rate' => $vatRate,
                'vat_cents' => $vatCents,
                'total_cents' => $totalCents,
                'weight_grams' => $grams,
            ],
        ]);
    }

    /** @param array<string, mixed> $quote
     * @return array<string, mixed>
     */
    private static function line_from_quote(array $quote): array {
        return $quote;
    }

    /** @param array<string, mixed> $line */
    private static function line(array $line, int $index): string {
        $accessories = $line['accessories'] ?? [];
        if (!$accessories) {
            $accXml = '<accessoires/>';
        } else {
            $parts = [];
            foreach ($accessories as $item) {
                $parts[] = '<accessoire code="' . self::esc((string) ($item['id'] ?? '')) . '" quantite="' . (int) ($item['quantity'] ?? 0) . '">' . self::esc((string) ($item['label'] ?? '')) . '</accessoire>';
            }
            $accXml = '<accessoires>' . implode('', $parts) . '</accessoires>';
        }
        $mode = (string) ($line['file_mode'] ?? 'sans-objet');
        if ($mode === 'reference') {
            $file = '<fichier mode="reference">' . self::esc((string) ($line['file_reference'] ?? '')) . '</fichier>';
        } else {
            $file = '<fichier mode="' . self::esc($mode) . '"/>';
        }
        $fileLabel = match ($mode) {
            'differe' => 'Dépôt après confirmation',
            'reference' => 'Référence déjà transmise',
            default => 'Pas de fichier',
        };
        $area = number_format((float) ($line['area_m2'] ?? 0), 4, '.', '');

        return '<ligne index="' . $index . '">' . "\n"
            . '      ' . self::tag('reference', (string) ($line['product_id'] ?? '')) . "\n"
            . '      ' . self::tag('designation', (string) ($line['product_label'] ?? '')) . "\n"
            . '      ' . self::tag('famille', (string) ($line['family_id'] ?? '')) . "\n"
            . '      ' . self::tag('largeurCm', (string) (int) ($line['width_cm'] ?? 0)) . "\n"
            . '      ' . self::tag('hauteurCm', (string) (int) ($line['height_cm'] ?? 0)) . "\n"
            . '      ' . self::tag('surfaceM2', $area) . "\n"
            . '      <matiere code="' . self::esc((string) ($line['material_id'] ?? '')) . '">' . self::esc((string) ($line['material_label'] ?? '')) . "</matiere>\n"
            . '      <finition code="' . self::esc((string) ($line['finish_id'] ?? '')) . '">' . self::esc((string) ($line['finish_label'] ?? '')) . "</finition>\n"
            . '      ' . $accXml . "\n"
            . '      ' . self::tag('quantite', (string) (int) ($line['quantity'] ?? 0)) . "\n"
            . '      <delai code="' . self::esc((string) ($line['lead_time_id'] ?? '')) . '" joursOuvres="' . (int) ($line['production_days'] ?? 0) . '">' . self::esc((string) ($line['lead_label'] ?? '')) . "</delai>\n"
            . '      ' . self::tag('sortieAtelier', (string) ($line['ready_on'] ?? '')) . "\n"
            . '      ' . $file . "\n"
            . '      ' . self::tag('fichierLibelle', $fileLabel) . "\n"
            . '      ' . self::tag('prixPieceHt', BD_Pricing::xml_decimal((int) ($line['piece_cents'] ?? 0))) . "\n"
            . '      ' . self::tag('accessoiresHt', BD_Pricing::xml_decimal((int) ($line['accessories_cents'] ?? 0))) . "\n"
            . '      ' . self::tag('totalLigneHt', BD_Pricing::xml_decimal((int) ($line['line_cents'] ?? 0))) . "\n"
            . '      ' . self::tag('poidsG', (string) (int) ($line['weight_grams'] ?? 0)) . "\n"
            . '    </ligne>';
    }

    private static function tag(string $name, string $value): string {
        return '<' . $name . '>' . self::esc($value) . '</' . $name . '>';
    }

    public static function esc(string $value): string {
        return str_replace(
            ['&', '<', '>', '"', "'"],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
            $value
        );
    }
}
