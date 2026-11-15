<?php
/**
 * Lecture du catalogue local.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || defined('BACHEDIRECT_STANDALONE') || exit;

final class BD_Catalog {
    public static function data(): array {
        static $data = null;
        if ($data === null) {
            $loaded = require dirname(__DIR__) . '/catalog.php';
            $data = is_array($loaded) ? $loaded : [];
        }
        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    public static function families(): array {
        $families = self::data()['families'] ?? [];
        return is_array($families) ? $families : [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function products(): array {
        $products = self::data()['products'] ?? [];
        return is_array($products) ? $products : [];
    }

    /** @return array<int, string> */
    public static function featured_ids(): array {
        $ids = self::data()['featured'] ?? [];
        return is_array($ids) ? array_values($ids) : [];
    }

    public static function product(string $id): ?array {
        foreach (self::products() as $product) {
            if (($product['id'] ?? '') === $id) {
                return $product;
            }
        }
        return null;
    }

    public static function family(string $slug): ?array {
        foreach (self::families() as $family) {
            if (($family['slug'] ?? '') === $slug) {
                return $family;
            }
        }
        return null;
    }
}
