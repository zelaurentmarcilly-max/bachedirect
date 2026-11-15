<?php
/**
 * Catalogue.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();

$current = is_product_category() ? get_queried_object() : null;
$current_slug = $current instanceof WP_Term ? $current->slug : '';
?>
<div class="bd-wrap bd-page">
    <p class="bd-kicker">Bâche Direct</p>
    <h1><?php woocommerce_page_title(); ?></h1>
    <?php if (is_product_category() && $current instanceof WP_Term && $current->description) : ?>
        <p class="bd-lede"><?php echo esc_html(wp_strip_all_tags($current->description)); ?></p>
    <?php else : ?>
        <p class="bd-lede">Vinyle, mesh, textile, oriflammes, roll-ups et accessoires. Le prix affiché est un point de départ hors taxes.</p>
    <?php endif; ?>

    <nav class="bd-chips" aria-label="Familles">
        <a class="<?php echo $current_slug === '' ? 'is-current' : ''; ?>" href="<?php echo esc_url(bd_theme_shop_url()); ?>">Tout</a>
        <?php foreach (bd_theme_families() as $family) : ?>
            <a class="<?php echo $current_slug === $family['slug'] ? 'is-current' : ''; ?>" href="<?php echo esc_url(bd_theme_family_url($family['slug'])); ?>">
                <?php echo esc_html($family['name']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (woocommerce_product_loop()) : ?>
        <ul class="bd-catalog">
            <?php
            while (have_posts()) {
                the_post();
                wc_get_template_part('content', 'product');
            }
            ?>
        </ul>
        <?php woocommerce_pagination(); ?>
    <?php else : ?>
        <p>Aucun produit dans cette famille. Importez <code>bachedirect-produits.csv</code> depuis Produits, Tous les produits, puis Importer.</p>
    <?php endif; ?>
</div>
<?php
get_footer();
