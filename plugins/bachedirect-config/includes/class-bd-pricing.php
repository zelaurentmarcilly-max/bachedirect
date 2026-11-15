<?php
/**
 * Calcul du prix d'exemple. Même règle que src/lib/pricing.ts.
 *
 * Les montants viennent de price-table.php. Rien n'est demandé à un fournisseur.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || defined('BACHEDIRECT_STANDALONE') || exit;

final class BD_Pricing {
    public static function table(): array {
        static $table = null;
        if ($table === null) {
            $loaded = require dirname(__DIR__) . '/price-table.php';
            $table = is_array($loaded) ? $loaded : [];
        }
        return $table;
    }

    public static function as_int(mixed $value): ?int {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }
        return null;
    }

    public static function today(): string {
        return (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d');
    }

    public static function add_business_days(string $iso, int $days): string {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $iso, new DateTimeZone('UTC'));
        if (!$date) {
            return $iso;
        }
        $left = $days;
        while ($left > 0) {
            $date = $date->modify('+1 day');
            $dow = (int) $date->format('w');
            if ($dow !== 0 && $dow !== 6) {
                $left--;
            }
        }
        return $date->format('Y-m-d');
    }

    public static function format_date(string $iso): string {
        $months = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];
        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $iso, new DateTimeZone('UTC'));
        if (!$date) {
            return $iso;
        }
        return $days[(int) $date->format('w')] . ' ' . (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    public static function format_euro(int $cents): string {
        $sign = $cents < 0 ? '-' : '';
        return $sign . number_format(abs($cents) / 100, 2, ',', ' ') . ' €';
    }

    public static function xml_decimal(int $cents): string {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        return $sign . intdiv($abs, 100) . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function money_to_cents(string $amount): int {
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        if (!is_numeric($amount)) {
            return 0;
        }
        return (int) round(((float) $amount) * 100, 0, PHP_ROUND_HALF_UP);
    }

    /** @return array<string, mixed> */
    public static function default_input(string $productId): array {
        $rule = self::table()['products'][$productId] ?? null;
        if (!is_array($rule)) {
            return [
                'productId' => $productId,
                'widthCm' => 0,
                'heightCm' => 0,
                'presetId' => '',
                'materialId' => '',
                'finishId' => '',
                'accessories' => [],
                'quantity' => 1,
                'leadTimeId' => 'standard',
            ];
        }
        $presetId = (string) ($rule['defaultPresetId'] ?? '');
        $preset = ($presetId !== '' && isset($rule['presets'][$presetId])) ? $rule['presets'][$presetId] : null;
        return [
            'productId' => $productId,
            'widthCm' => (int) ($preset['widthCm'] ?? ($rule['defaultWidthCm'] ?? 0)),
            'heightCm' => (int) ($preset['heightCm'] ?? ($rule['defaultHeightCm'] ?? 0)),
            'presetId' => $presetId,
            'materialId' => (string) ($rule['defaultMaterialId'] ?? ''),
            'finishId' => (string) ($rule['defaultFinishId'] ?? ''),
            'accessories' => [],
            'quantity' => 1,
            'leadTimeId' => (string) ($rule['defaultLeadTimeId'] ?? 'standard'),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: true, quote: array<string, mixed>}|array{ok: false, errors: array<int, string>}
     */
    public static function quote(array $input, ?string $today = null): array {
        $data = self::table();
        $productId = (string) ($input['productId'] ?? '');
        $rule = $data['products'][$productId] ?? null;
        if (!is_array($rule)) {
            return self::fail(['Article inconnu.']);
        }

        $materialId = (string) ($input['materialId'] ?? '');
        $finishId = (string) ($input['finishId'] ?? '');
        foreach ($data['rejections'] ?? [] as $rejection) {
            if (($rejection['productId'] ?? '') !== $productId) {
                continue;
            }
            if (!empty($rejection['finishId']) && $rejection['finishId'] !== $finishId) {
                continue;
            }
            if (!empty($rejection['materialId']) && $rejection['materialId'] !== $materialId) {
                continue;
            }
            if (empty($rejection['finishId']) && empty($rejection['materialId'])) {
                continue;
            }
            return self::fail([(string) ($rejection['message'] ?? 'Cette combinaison est refusée.')]);
        }

        $materials = $rule['materials'] ?? [];
        $finishes = $rule['finishes'] ?? [];
        if (!in_array($materialId, $materials, true)) {
            return self::fail(['Cette matière n\'est pas disponible pour cet article.']);
        }
        if (!in_array($finishId, $finishes, true)) {
            return self::fail(['Cette finition n\'est pas disponible pour cet article.']);
        }

        $quantity = $input['quantity'] ?? null;
        if (!is_int($quantity) || $quantity < 1 || $quantity > 99) {
            return self::fail(['La quantité doit être un entier entre 1 et 99.']);
        }
        $width = $input['widthCm'] ?? null;
        $height = $input['heightCm'] ?? null;
        if (!is_int($width) || !is_int($height)) {
            return self::fail(['Indiquez la largeur et la hauteur en centimètres entiers.']);
        }

        $leadId = (string) ($input['leadTimeId'] ?? '');
        $lead = $data['leadTimes'][$leadId] ?? null;
        if (!is_array($lead)) {
            return self::fail(['Ce délai n\'est pas proposé.']);
        }

        $extras = [];
        foreach ($input['accessories'] ?? [] as $accessory) {
            if (!is_array($accessory)) {
                return self::fail(['Cet accessoire n\'est pas proposé avec cet article.']);
            }
            $accQty = $accessory['quantity'] ?? null;
            if (!is_int($accQty) || $accQty < 0 || $accQty > 20) {
                return self::fail(['La quantité d\'un accessoire doit rester entre 0 et 20.']);
            }
            if ($accQty === 0) {
                continue;
            }
            $accId = (string) ($accessory['id'] ?? '');
            $defined = $data['accessories'][$accId] ?? null;
            $allowed = $rule['accessories'] ?? [];
            if (!in_array($accId, $allowed, true) || !is_array($defined)) {
                return self::fail(['Cet accessoire n\'est pas proposé avec cet article.']);
            }
            $extras[] = ['id' => $accId, 'quantity' => $accQty, 'label' => (string) $defined['label']];
        }

        $built = self::build_base($data, $rule, $input);
        if (!$built['ok']) {
            return $built;
        }
        $base = $built['built'];
        $baseCents = $base['materialCents'] + $base['finishCents'];
        $rated = (int) round($baseCents * (float) $lead['factor'], 0, PHP_ROUND_HALF_UP);
        $breaks = $data['quantityBreaks'] ?? [];
        usort($breaks, static function ($a, $b) {
            return ($b['minQuantity'] ?? 0) <=> ($a['minQuantity'] ?? 0);
        });
        $breakRule = null;
        foreach ($breaks as $item) {
            if ($quantity >= (int) ($item['minQuantity'] ?? 0)) {
                $breakRule = $item;
                break;
            }
        }
        $factor = (float) ($breakRule['factor'] ?? 1);
        $pieceCents = (int) round($rated * $factor, 0, PHP_ROUND_HALF_UP);

        $breakdown = [
            ['label' => $base['materialLine'], 'cents' => $base['materialCents'], 'group' => 'piece'],
        ];
        if ($base['showFinish']) {
            $breakdown[] = ['label' => $base['finishLine'], 'cents' => $base['finishCents'], 'group' => 'piece'];
        }
        if ($rated !== $baseCents) {
            $breakdown[] = [
                'label' => 'Majoration ' . self::lower((string) $lead['label']),
                'cents' => $rated - $baseCents,
                'group' => 'piece',
            ];
        }
        if ($pieceCents !== $rated && is_array($breakRule)) {
            $breakdown[] = [
                'label' => 'Remise quantité (dès ' . (int) $breakRule['minQuantity'] . ')',
                'cents' => $pieceCents - $rated,
                'group' => 'piece',
            ];
        }

        $accessoriesCents = 0;
        $accessoryGrams = 0;
        foreach ($extras as $extra) {
            $accessory = $data['accessories'][$extra['id']];
            $accBase = self::to_cents((float) $accessory['unitHt']) * $extra['quantity'];
            $priced = (int) round($accBase * (float) $lead['factor'], 0, PHP_ROUND_HALF_UP);
            $accessoriesCents += $priced;
            $accessoryGrams += self::grams((float) $accessory['weightKg']) * $extra['quantity'];
            $breakdown[] = [
                'label' => $accessory['label'] . ' × ' . $extra['quantity'],
                'cents' => $accBase,
                'group' => 'accessory',
            ];
            if ($priced !== $accBase) {
                $breakdown[] = [
                    'label' => 'Majoration ' . self::lower((string) $lead['label']) . ' · ' . $accessory['label'],
                    'cents' => $priced - $accBase,
                    'group' => 'accessory',
                ];
            }
        }

        $pieceSum = 0;
        foreach ($breakdown as $line) {
            if ($line['group'] === 'piece') {
                $pieceSum += $line['cents'];
            }
        }
        if ($pieceSum !== $pieceCents) {
            return self::fail(['Le barème n\'a pas pu ventiler le prix de la pièce.']);
        }

        $day = $today ?? self::today();
        return [
            'ok' => true,
            'quote' => [
                'pricing_version' => (string) ($data['version'] ?? ''),
                'product_id' => $productId,
                'product_label' => (string) ($rule['label'] ?? $productId),
                'width_cm' => $base['widthCm'],
                'height_cm' => $base['heightCm'],
                'preset_id' => $base['presetId'],
                'preset_label' => $base['presetLabel'],
                'material_id' => $materialId,
                'material_label' => $base['materialLabel'],
                'finish_id' => $finishId,
                'finish_label' => $base['finishLabel'],
                'accessories' => $extras,
                'quantity' => $quantity,
                'lead_time_id' => $leadId,
                'lead_label' => (string) $lead['label'],
                'area_m2' => round($base['areaM2'], 4),
                'perimeter_m' => round($base['perimeterM'], 2),
                'piece_cents' => $pieceCents,
                'accessories_cents' => $accessoriesCents,
                'line_cents' => $pieceCents * $quantity + $accessoriesCents,
                'weight_grams' => $base['pieceGrams'] * $quantity + $accessoryGrams,
                'production_days' => (int) $lead['businessDays'],
                'ready_on' => self::add_business_days($day, (int) $lead['businessDays']),
                'breakdown' => $breakdown,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $quotes
     * @return array<string, mixed>
     */
    public static function sum_quotes(array $quotes): array {
        $data = self::table();
        $goods = 0;
        $grams = 0;
        foreach ($quotes as $quote) {
            $goods += (int) ($quote['line_cents'] ?? 0);
            $grams += (int) ($quote['weight_grams'] ?? 0);
        }
        $shipping = self::shipping_cents($goods, $grams);
        $vatRate = (float) ($data['vatRate'] ?? 0);
        $vat = (int) round(($goods + $shipping) * $vatRate, 0, PHP_ROUND_HALF_UP);
        $freeFrom = (float) ($data['shipping']['freeFromGoodsHt'] ?? 0);
        return [
            'goods_cents' => $goods,
            'shipping_cents' => $shipping,
            'vat_rate' => $vatRate,
            'vat_cents' => $vat,
            'total_cents' => $goods + $shipping + $vat,
            'weight_grams' => $grams,
            'free_shipping' => $shipping === 0 && $goods >= self::to_cents($freeFrom),
            'pricing_version' => (string) ($data['version'] ?? ''),
        ];
    }

    public static function shipping_cents(int $goodsCents, int $weightGrams): int {
        $shipping = self::table()['shipping'] ?? [];
        if ($goodsCents >= self::to_cents((float) ($shipping['freeFromGoodsHt'] ?? 0))) {
            return 0;
        }
        $perKg = self::to_cents((float) ($shipping['perKgHt'] ?? 0));
        return self::to_cents((float) ($shipping['baseHt'] ?? 0))
            + (int) round(($weightGrams / 1000) * $perKg, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $input
     * @return array{ok: true, built: array<string, mixed>}|array{ok: false, errors: array<int, string>}
     */
    private static function build_base(array $data, array $rule, array $input): array {
        $material = $data['materials'][$input['materialId']] ?? null;
        $finish = $data['finishes'][$input['finishId']] ?? null;
        if (!is_array($material)) {
            return self::fail(['Cette matière est inconnue.']);
        }
        if (!is_array($finish)) {
            return self::fail(['Cette finition est inconnue.']);
        }
        $mode = (string) ($rule['mode'] ?? '');

        if ($mode === 'surface') {
            if (($material['kind'] ?? '') !== 'area') {
                return self::fail(['Cette matière ne se vend pas au mètre carré.']);
            }
            $width = (int) $input['widthCm'];
            $height = (int) $input['heightCm'];
            if (
                $width < (int) ($rule['minWidthCm'] ?? 0)
                || $width > (int) ($rule['maxWidthCm'] ?? 0)
                || $height < (int) ($rule['minHeightCm'] ?? 0)
                || $height > (int) ($rule['maxHeightCm'] ?? 0)
            ) {
                return self::fail([
                    'Le format doit rester entre ' . (int) $rule['minWidthCm'] . ' × ' . (int) $rule['minHeightCm']
                    . ' cm et ' . (int) $rule['maxWidthCm'] . ' × ' . (int) $rule['maxHeightCm'] . ' cm.',
                ]);
            }
            $area = ($width * $height) / 10000;
            $perimeter = (2 * ($width + $height)) / 100;
            $fromArea = (int) round($width * $height * (float) ($material['pricePerM2'] ?? 0) / 100, 0, PHP_ROUND_HALF_UP);
            $materialCents = max(self::to_cents((float) ($material['minHt'] ?? 0)), $fromArea);
            $finishCents = self::to_cents((float) $finish['flatHt'])
                + (int) round((2 * ($width + $height)) * (float) $finish['perMeterHt'], 0, PHP_ROUND_HALF_UP);
            $pieceGrams = (int) round($width * $height * self::grams((float) ($material['weightPerM2'] ?? 0)) / 10000, 0, PHP_ROUND_HALF_UP);
            return self::built($material, $finish, $materialCents, $finishCents, $pieceGrams, $width, $height, $area, $perimeter, '', '', true, self::measure($area, 2) . ' m²', self::measure($perimeter, 2) . ' m');
        }

        if ($mode === 'preset') {
            $presetId = (string) ($input['presetId'] ?? '');
            $preset = $presetId !== '' ? ($rule['presets'][$presetId] ?? null) : null;
            if (!is_array($preset)) {
                return self::fail(['Choisissez un format.']);
            }
            if (($material['kind'] ?? '') !== 'delta') {
                return self::fail(['Cette matière ne correspond pas à un format imposé.']);
            }
            $width = (int) $preset['widthCm'];
            $height = (int) $preset['heightCm'];
            $area = ($width * $height) / 10000;
            $perimeter = (2 * ($width + $height)) / 100;
            $materialCents = self::to_cents((float) $preset['unitHt']) + self::to_cents((float) ($material['deltaHt'] ?? 0));
            $finishCents = self::to_cents((float) $finish['flatHt'])
                + (int) round((2 * ($width + $height)) * (float) $finish['perMeterHt'], 0, PHP_ROUND_HALF_UP);
            $pieceGrams = self::grams((float) $preset['weightKg']) + self::grams((float) ($material['extraWeightKg'] ?? 0));
            $built = self::built(
                $material,
                $finish,
                $materialCents,
                $finishCents,
                $pieceGrams,
                $width,
                $height,
                $area,
                $perimeter,
                $presetId,
                (string) $preset['label'],
                true,
                '',
                (string) $finish['label']
            );
            $built['built']['materialLine'] = $preset['label'] . ' · ' . $material['label'];
            return $built;
        }

        if ($mode === 'length') {
            $width = (int) $input['widthCm'];
            $height = (int) $input['heightCm'];
            if ($width < (int) ($rule['minWidthCm'] ?? 0) || $width > (int) ($rule['maxWidthCm'] ?? 0)) {
                return self::fail(['La longueur doit rester entre ' . (int) $rule['minWidthCm'] . ' et ' . (int) $rule['maxWidthCm'] . ' cm.']);
            }
            if ($height < (int) ($rule['minHeightCm'] ?? 0) || $height > (int) ($rule['maxHeightCm'] ?? 0)) {
                return self::fail(['La hauteur indicative doit rester entre ' . (int) $rule['minHeightCm'] . ' et ' . (int) $rule['maxHeightCm'] . ' cm.']);
            }
            if (($material['kind'] ?? '') !== 'delta') {
                return self::fail(['Cette matière ne correspond pas au rail.']);
            }
            $meters = $width / 100;
            $fromLength = (int) round($width * (float) ($rule['pricePerM'] ?? 0), 0, PHP_ROUND_HALF_UP);
            $materialCents = max(self::to_cents((float) ($rule['minHt'] ?? 0)), $fromLength) + self::to_cents((float) ($material['deltaHt'] ?? 0));
            $finishCents = self::to_cents((float) $finish['flatHt']);
            $pieceGrams = (int) round($meters * self::grams((float) ($rule['weightPerM'] ?? 0)), 0, PHP_ROUND_HALF_UP)
                + self::grams((float) ($material['extraWeightKg'] ?? 0));
            $built = self::built($material, $finish, $materialCents, $finishCents, $pieceGrams, $width, $height, 0, 0, '', '', true, '', (string) $finish['label']);
            $built['built']['materialLine'] = $material['label'] . ' · ' . self::measure($meters, 2) . ' m';
            return $built;
        }

        $accessory = isset($rule['accessoryId']) ? ($data['accessories'][$rule['accessoryId']] ?? null) : null;
        if (!is_array($accessory)) {
            return self::fail(['Cet accessoire n\'a pas de tarif.']);
        }
        if (($material['kind'] ?? '') !== 'delta') {
            return self::fail(['Cette matière ne correspond pas à l\'accessoire.']);
        }
        $materialCents = self::to_cents((float) $accessory['unitHt']) + self::to_cents((float) ($material['deltaHt'] ?? 0));
        $finishCents = self::to_cents((float) $finish['flatHt']);
        $built = self::built(
            $material,
            $finish,
            $materialCents,
            $finishCents,
            self::grams((float) $accessory['weightKg']) + self::grams((float) ($material['extraWeightKg'] ?? 0)),
            0,
            0,
            0,
            0,
            '',
            '',
            $finishCents !== 0,
            '',
            (string) $finish['label']
        );
        $built['built']['materialLine'] = (string) $accessory['label'];
        return $built;
    }

    /**
     * @param array<string, mixed> $material
     * @param array<string, mixed> $finish
     * @return array{ok: true, built: array<string, mixed>}
     */
    private static function built(
        array $material,
        array $finish,
        int $materialCents,
        int $finishCents,
        int $pieceGrams,
        int $width,
        int $height,
        float $area,
        float $perimeter,
        string $presetId,
        string $presetLabel,
        bool $showFinish,
        string $areaLabel,
        string $finishExtra
    ): array {
        $materialLine = $areaLabel !== '' ? $material['label'] . ' · ' . $areaLabel : (string) $material['label'];
        $finishLine = $finishExtra !== '' && !str_contains($finishExtra, (string) $finish['label'])
            ? $finish['label'] . ' · ' . $finishExtra
            : ($finishExtra !== '' ? $finishExtra : (string) $finish['label']);
        if ($areaLabel !== '') {
            $finishLine = $finish['label'] . ' · ' . $finishExtra;
        }
        return [
            'ok' => true,
            'built' => [
                'materialCents' => $materialCents,
                'finishCents' => $finishCents,
                'pieceGrams' => $pieceGrams,
                'widthCm' => $width,
                'heightCm' => $height,
                'presetId' => $presetId,
                'presetLabel' => $presetLabel,
                'areaM2' => $area,
                'perimeterM' => $perimeter,
                'materialLabel' => (string) $material['label'],
                'finishLabel' => (string) $finish['label'],
                'materialLine' => $materialLine,
                'finishLine' => $finishLine,
                'showFinish' => $showFinish,
            ],
        ];
    }

    private static function to_cents(float $euros): int {
        return (int) round($euros * 100, 0, PHP_ROUND_HALF_UP);
    }

    private static function grams(float $kg): int {
        return (int) round($kg * 1000, 0, PHP_ROUND_HALF_UP);
    }

    private static function measure(float $value, int $digits): string {
        return number_format($value, $digits, ',', ' ');
    }

    private static function lower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    /** @param array<int, string> $errors
     * @return array{ok: false, errors: array<int, string>}
     */
    private static function fail(array $errors): array {
        return ['ok' => false, 'errors' => $errors];
    }
}
