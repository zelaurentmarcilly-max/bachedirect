<?php
/**
 * Accueil.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();

$example = '';
if (class_exists('BD_Pricing')) {
    $quoted = BD_Pricing::quote(BD_Pricing::default_input('bache-vinyle'));
    if ($quoted['ok']) {
        $quote = $quoted['quote'];
        $example = sprintf(
            'Exemple, %s %d × %d cm : %s HT.',
            $quote['product_label'],
            (int) $quote['width_cm'],
            (int) $quote['height_cm'],
            BD_Pricing::format_euro((int) $quote['piece_cents'])
        );
    }
}
?>
<section class="bd-hero">
    <div class="bd-wrap bd-hero-grid">
        <div>
            <p class="bd-kicker">Atelier grand format</p>
            <h1>Le format que vous mesurez.</h1>
            <p class="bd-lede">Bâches vinyle, mesh, textile, oriflammes, roll-ups et accessoires. La largeur, la hauteur, la matière et la finition se règlent sur la fiche. Le prix hors taxes suit un barème d'exemple, sans appel à un fournisseur.</p>
            <p><a class="bd-btn" href="<?php echo esc_url(bd_theme_shop_url()); ?>">Voir le catalogue</a></p>
            <?php if ($example !== '') : ?>
                <p class="bd-example"><?php echo esc_html($example); ?></p>
            <?php endif; ?>
        </div>
        <div class="bd-hero-art" aria-hidden="true">
            <svg class="bd-hero-svg" viewBox="0 0 440 300">
                <rect width="440" height="300" fill="#f7f5ef"/>
                <rect x="48" y="36" width="344" height="176" fill="#fbfaf6" stroke="#17191c" stroke-width="1.75"/>
                <path d="M48 22v14M34 36h14M392 22v14M406 36h-14M48 226v14M34 212h14M392 226v14M406 212h-14" fill="none" stroke="#1e3a6e" stroke-width="1.25"/>
                <g class="bd-dim" fill="none" stroke="#1e3a6e" stroke-width="1.5">
                    <path d="M48 258h344"/>
                    <path d="M48 250v16M392 250v16"/>
                </g>
                <text x="220" y="282" text-anchor="middle" fill="#1e3a6e" font-size="13" font-family="Segoe UI, sans-serif">largeur</text>
            </svg>
        </div>
    </div>
</section>

<section class="bd-section">
    <div class="bd-wrap">
        <h2>Familles</h2>
        <div class="bd-families">
            <?php foreach (bd_theme_families() as $index => $family) : ?>
                <a class="bd-family" href="<?php echo esc_url(bd_theme_family_url($family['slug'])); ?>">
                    <span class="bd-index"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    <?php bd_theme_art($family['art']); ?>
                    <h3><?php echo esc_html($family['name']); ?></h3>
                    <p><?php echo esc_html($family['blurb']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bd-section">
    <div class="bd-wrap">
        <h2>Au catalogue</h2>
        <?php
        $featured = class_exists('BD_Catalog') ? BD_Catalog::featured_ids() : ['bache-vinyle', 'oriflamme-goutte', 'rollup'];
        $cards = [];
        foreach ($featured as $sku) {
            $product_id = function_exists('wc_get_product_id_by_sku') ? wc_get_product_id_by_sku($sku) : 0;
            $product = $product_id ? wc_get_product($product_id) : false;
            if ($product instanceof WC_Product) {
                $cards[] = $product;
            }
        }
        ?>
        <?php if ($cards) : ?>
            <ul class="bd-catalog">
                <?php foreach ($cards as $product) : ?>
                    <li class="bd-card">
                        <a href="<?php echo esc_url($product->get_permalink()); ?>">
                            <div class="bd-card-art"><?php bd_theme_art_for_product($product); ?></div>
                            <div class="bd-card-body">
                                <h3><?php echo esc_html($product->get_name()); ?></h3>
                                <p><?php echo esc_html(wp_strip_all_tags($product->get_short_description())); ?></p>
                                <p class="bd-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="bd-lede">Le catalogue se remplit avec le fichier d'import : Produits, Tous les produits, puis Importer.</p>
        <?php endif; ?>
    </div>
</section>

<section class="bd-section bd-steps">
    <div class="bd-wrap">
        <h2>La commande</h2>
        <ol>
            <li><strong>Mesurer</strong><span>Largeur et hauteur, ou un format d'atelier.</span></li>
            <li><strong>Configurer</strong><span>Matière, finition, accessoires, quantité, délai. Le prix se recalcule.</span></li>
            <li><strong>Virer</strong><span>Le règlement est celui de WooCommerce. Le virement bancaire suffit.</span></li>
        </ol>
    </div>
</section>
<?php
get_footer();
