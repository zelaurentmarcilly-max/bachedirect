<?php
/**
 * Régénère woocommerce/import/bachedirect-produits.csv
 * à partir du catalogue et du barème. À lancer depuis la racine du dépôt :
 * php woocommerce/bachedirect-config/bin/build-import-csv.php
 */

define('BACHEDIRECT_STANDALONE', true);

require dirname(__DIR__) . '/includes/class-bd-catalog.php';
require dirname(__DIR__) . '/includes/class-bd-pricing.php';

$featured = array_flip(BD_Catalog::featured_ids());
$path = dirname(__DIR__, 2) . '/import/bachedirect-produits.csv';
if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0775, true) && !is_dir(dirname($path))) {
    fwrite(STDERR, "Dossier d'import introuvable.\n");
    exit(1);
}

$handle = fopen($path, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Écriture impossible.\n");
    exit(1);
}

$header = [
    'Type',
    'SKU',
    'Name',
    'Published',
    'Is featured?',
    'Visibility in catalog',
    'Short description',
    'Description',
    'Tax status',
    'In stock?',
    'Weight (kg)',
    'Regular price',
    'Categories',
    'Position',
    'Sold individually?',
    'Allow customer reviews?',
    'Meta: _bd_product_id',
    'Meta: _bd_art',
];
fputcsv($handle, $header);

$position = 0;
foreach (BD_Catalog::products() as $product) {
    $position++;
    $family = BD_Catalog::family((string) $product['family']);
    $result = BD_Pricing::quote(BD_Pricing::default_input((string) $product['id']));
    if (!$result['ok']) {
        fwrite(STDERR, $product['id'] . ': ' . implode(' ', $result['errors']) . "\n");
        exit(1);
    }
    $quote = $result['quote'];
    $kg = rtrim(rtrim(number_format(((int) $quote['weight_grams']) / 1000, 3, '.', ''), '0'), '.');
    if ($kg === '') {
        $kg = '0';
    }
    fputcsv($handle, [
        'simple',
        $product['id'],
        $product['name'],
        '1',
        isset($featured[$product['id']]) ? '1' : '0',
        'visible',
        $product['summary'],
        $product['description'],
        'taxable',
        '1',
        $kg,
        number_format(((int) $quote['piece_cents']) / 100, 2, '.', ''),
        $family['name'] ?? '',
        (string) $position,
        '0',
        '0',
        $product['id'],
        $product['art'],
    ]);
}

fclose($handle);
fwrite(STDOUT, $path . "\n" . $position . " articles\n");
