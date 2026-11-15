<?php
/**
 * Vérifie le barème (mêmes cas que src/lib/pricing.test.ts) et le CSV d'import.
 * php woocommerce/bachedirect-config/bin/verify-pricing.php
 */

define('BACHEDIRECT_STANDALONE', true);

require dirname(__DIR__) . '/includes/class-bd-catalog.php';
require dirname(__DIR__) . '/includes/class-bd-pricing.php';
require dirname(__DIR__) . '/includes/class-bd-xml.php';

$failures = 0;

function check(bool $ok, string $message): void {
    global $failures;
    if ($ok) {
        return;
    }
    $failures++;
    fwrite(STDERR, "ÉCHEC  $message\n");
}

function quote_ok(array $input, string $today = '2026-11-14'): array {
    $result = BD_Pricing::quote($input, $today);
    check($result['ok'] === true, 'devis refusé pour ' . ($input['productId'] ?? '') . ' ' . implode(' ', $result['errors'] ?? []));
    return $result['ok'] ? $result['quote'] : [];
}

check(BD_Pricing::add_business_days('2026-11-14', 5) === '2026-11-20', '5 jours ouvrés depuis un samedi');
check(BD_Pricing::add_business_days('2026-11-14', 2) === '2026-11-17', '2 jours ouvrés');
check(BD_Pricing::add_business_days('2026-11-13', 1) === '2026-11-16', 'vendredi + 1 jour ouvré');

$banner = [
    'productId' => 'bache-vinyle',
    'widthCm' => 200,
    'heightCm' => 100,
    'materialId' => 'vinyle-510',
    'finishId' => 'oeillets-50',
    'accessories' => [],
    'quantity' => 1,
    'leadTimeId' => 'standard',
];

$base = quote_ok($banner);
check(($base['area_m2'] ?? null) === 2.0, 'surface 2 m²');
check(($base['piece_cents'] ?? null) === 6930, 'pièce 6930, obtenu ' . ($base['piece_cents'] ?? ''));
check(($base['line_cents'] ?? null) === 6930, 'ligne 6930');
check(($base['weight_grams'] ?? null) === 1020, 'poids 1020 g');
check(($base['ready_on'] ?? null) === '2026-11-20', 'sortie atelier');

$floor = quote_ok(array_merge($banner, ['widthCm' => 40, 'heightCm' => 40]));
check(($floor['piece_cents'] ?? null) === 4798, 'plancher 4798, obtenu ' . ($floor['piece_cents'] ?? ''));

$volume = quote_ok(array_merge($banner, ['quantity' => 10]));
check(($volume['piece_cents'] ?? null) === 6098, 'remise 10 : 6098, obtenu ' . ($volume['piece_cents'] ?? ''));
check(($volume['line_cents'] ?? null) === 60980, 'ligne volume');

$express = quote_ok(array_merge($banner, ['leadTimeId' => 'express']));
check(($express['piece_cents'] ?? null) === 9148, 'express 9148, obtenu ' . ($express['piece_cents'] ?? ''));
check(($express['ready_on'] ?? null) === '2026-11-17', 'express prêt le 17');

$both = quote_ok(array_merge($banner, ['quantity' => 10, 'leadTimeId' => 'express']));
check(($both['piece_cents'] ?? null) === 8050, 'express et remise 8050, obtenu ' . ($both['piece_cents'] ?? ''));

$withAcc = quote_ok(array_merge($banner, [
    'quantity' => 2,
    'accessories' => [['id' => 'sandow-10', 'quantity' => 1]],
]));
check(($withAcc['piece_cents'] ?? null) === 6930, 'pièce inchangée avec accessoire');
check(($withAcc['accessories_cents'] ?? null) === 1150, 'accessoire 1150');
check(($withAcc['line_cents'] ?? null) === 15010, 'ligne 15010');
check(($withAcc['weight_grams'] ?? null) === 2220, 'poids 2220');

$mesh = BD_Pricing::quote([
    'productId' => 'bache-mesh',
    'widthCm' => 300,
    'heightCm' => 150,
    'materialId' => 'mesh-270',
    'finishId' => 'fourreau',
    'accessories' => [],
    'quantity' => 1,
    'leadTimeId' => 'standard',
]);
check($mesh['ok'] === false && isset($mesh['errors'][0]) && stripos($mesh['errors'][0], 'fourreau') !== false, 'le mesh refuse le fourreau');

$totals = BD_Pricing::sum_quotes([$withAcc]);
check(($totals['goods_cents'] ?? null) === 15010, 'marchandises');
check(($totals['shipping_cents'] ?? null) === 1445, 'port 1445, obtenu ' . ($totals['shipping_cents'] ?? ''));
check(($totals['vat_cents'] ?? null) === 3291, 'tva 3291, obtenu ' . ($totals['vat_cents'] ?? ''));
check(($totals['total_cents'] ?? null) === 19746, 'ttc');
check(($totals['free_shipping'] ?? null) === false, 'port non offert');

$free = BD_Pricing::sum_quotes([['line_cents' => 28000, 'weight_grams' => 1000]]);
check(($free['shipping_cents'] ?? null) === 0 && ($free['free_shipping'] ?? false) === true, 'port offert au seuil');

$rail = quote_ok([
    'productId' => 'systeme-rail',
    'widthCm' => 200,
    'heightCm' => 100,
    'presetId' => '',
    'materialId' => 'rail-alu',
    'finishId' => 'coulisseaux',
    'accessories' => [],
    'quantity' => 1,
    'leadTimeId' => 'standard',
]);
check(($rail['piece_cents'] ?? null) === 3600, 'rail 36 €, obtenu ' . ($rail['piece_cents'] ?? ''));

$flag = quote_ok(BD_Pricing::default_input('oriflamme-goutte'), '2026-11-14');
check(($flag['piece_cents'] ?? null) === 7600, 'oriflamme goutte 76 €');

$jsonPath = dirname(__DIR__, 3) . '/data/catalog.json';
$json = json_decode((string) file_get_contents($jsonPath), true);
$jsonIds = array_column($json['products'], 'id');
$catalogIds = array_column(BD_Catalog::products(), 'id');
check($jsonIds === $catalogIds, 'le catalogue PHP reprend les identifiants du JSON, dans l\'ordre');
check(count($catalogIds) === 13, '13 articles, pas un catalogue inventé');

foreach ($json['products'] as $index => $product) {
    $row = BD_Catalog::products()[$index];
    check($row['name'] === $product['name'], 'nom ' . $product['id']);
    check($row['summary'] === $product['summary'], 'résumé ' . $product['id']);
    check($row['description'] === $product['description'], 'description ' . $product['id']);
    $result = BD_Pricing::quote(BD_Pricing::default_input($product['id']));
    check($result['ok'] === true && $result['quote']['piece_cents'] > 0, 'tarif par défaut ' . $product['id']);
}

$expectedFamily = [
    'bache-vinyle' => 'baches-vinyle',
    'bache-mesh' => 'baches-mesh',
    'bache-textile' => 'baches-textile',
    'oriflamme-goutte' => 'oriflammes',
    'oriflamme-rectangle' => 'oriflammes',
    'oriflamme-plume' => 'oriflammes',
    'rollup' => 'roll-ups',
    'systeme-rail' => 'systemes',
    'systeme-cadre' => 'systemes',
    'accessoire-sandow' => 'accessoires',
    'accessoire-crochet' => 'accessoires',
    'accessoire-lest' => 'accessoires',
    'accessoire-housse' => 'accessoires',
];
foreach (BD_Catalog::products() as $product) {
    check(($expectedFamily[$product['id']] ?? '') === $product['family'], 'famille de ' . $product['id']);
}

$csvPath = dirname(__DIR__, 2) . '/import/bachedirect-produits.csv';
check(is_file($csvPath), 'CSV présent');
$handle = fopen($csvPath, 'rb');
$header = $handle ? fgetcsv($handle) : false;
$rows = [];
if ($handle && is_array($header)) {
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) === count($header)) {
            $rows[] = array_combine($header, $row);
        }
    }
    fclose($handle);
}
check(count($rows) === 13, 'le CSV a 13 lignes, obtenu ' . count($rows));
$csvSkus = array_column($rows, 'SKU');
check($csvSkus === $catalogIds, 'les UGS du CSV sont exactement celles du catalogue');

foreach ($rows as $row) {
    $product = BD_Catalog::product($row['SKU']);
    $family = $product ? BD_Catalog::family((string) $product['family']) : null;
    $result = BD_Pricing::quote(BD_Pricing::default_input($row['SKU']));
    $price = $result['ok'] ? number_format($result['quote']['piece_cents'] / 100, 2, '.', '') : '';
    check($product !== null, 'UGS inconnue ' . $row['SKU']);
    check(($row['Name'] ?? '') === ($product['name'] ?? ''), 'nom CSV ' . $row['SKU']);
    check(($row['Short description'] ?? '') === ($product['summary'] ?? ''), 'résumé CSV ' . $row['SKU']);
    check(($row['Description'] ?? '') === ($product['description'] ?? ''), 'description CSV ' . $row['SKU']);
    check(($row['Categories'] ?? '') === ($family['name'] ?? ''), 'famille CSV ' . $row['SKU'] . ' obtenu ' . ($row['Categories'] ?? ''));
    check(($row['Regular price'] ?? '') === $price, 'prix CSV ' . $row['SKU'] . ' attendu ' . $price . ' obtenu ' . ($row['Regular price'] ?? ''));
    check(($row['Meta: _bd_product_id'] ?? '') === $row['SKU'], 'meta id ' . $row['SKU']);
    check(($row['Type'] ?? '') === 'simple', 'type simple');
}

$xml = BD_Xml::render([
    'number' => 'BD-1',
    'created_at' => '2026-11-14T10:00:00+01:00',
    'currency' => 'EUR',
    'status' => 'on-hold',
    'pricing_version' => 'exemple-2026-11',
    'payment' => ['mode' => 'virement', 'label' => 'Virement bancaire', 'paid' => false],
    'client' => ['name' => 'Atelier & Co', 'email' => 'a@exemple.fr', 'phone' => '', 'company' => ''],
    'delivery' => ['line1' => '1 rue <Test>', 'line2' => '', 'postal_code' => '69001', 'city' => 'Lyon', 'country' => 'FR', 'note' => ''],
    'lines' => [[
        'product_id' => 'bache-vinyle',
        'product_label' => 'Bâche vinyle',
        'family_id' => 'baches-vinyle',
        'width_cm' => 200,
        'height_cm' => 100,
        'area_m2' => 2,
        'material_id' => 'vinyle-510',
        'material_label' => 'Vinyle 510 g/m²',
        'finish_id' => 'oeillets-50',
        'finish_label' => 'Œillets tous les 50 cm',
        'accessories' => [],
        'quantity' => 1,
        'lead_time_id' => 'standard',
        'lead_label' => 'Standard',
        'production_days' => 5,
        'ready_on' => '2026-11-20',
        'file_mode' => 'differe',
        'file_reference' => '',
        'piece_cents' => 6930,
        'accessories_cents' => 0,
        'line_cents' => 6930,
        'weight_grams' => 1020,
    ]],
    'totals' => [
        'goods_cents' => 6930,
        'shipping_cents' => 1445,
        'vat_rate' => 0.2,
        'vat_cents' => 1675,
        'total_cents' => 10050,
        'weight_grams' => 1020,
    ],
]);
check(str_contains($xml, 'xmlns="https://bachedirect.fr/ns/commande/1"'), 'espace de noms');
check(str_contains($xml, 'Atelier &amp; Co'), 'échappement esperluette');
check(str_contains($xml, '1 rue &lt;Test&gt;'), 'échappement chevron');
check(str_contains($xml, '<prixPieceHt>69.30</prixPieceHt>'), 'montant XML');
check(str_contains($xml, 'encaisse="false"'), 'paiement non encaissé');
check(str_contains($xml, '<famille>baches-vinyle</famille>'), 'famille');

if ($failures > 0) {
    fwrite(STDERR, $failures . " échec(s)\n");
    exit(1);
}

fwrite(STDOUT, "OK\n");
