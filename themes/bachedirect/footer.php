<?php
/**
 * Pied de page.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;
?>
</main>
<footer class="bd-footer">
    <div class="bd-wrap bd-footer-grid">
        <div>
            <strong>Bâche Direct</strong>
            <p>bachedirect.fr</p>
            <p>Les prix du catalogue sont un barème d'exemple. Le paiement se fait dans WooCommerce, par virement si vous l'activez.</p>
        </div>
        <div>
            <p class="bd-kicker">Familles</p>
            <ul>
                <?php foreach (bd_theme_families() as $family) : ?>
                    <li><a href="<?php echo esc_url(bd_theme_family_url($family['slug'])); ?>"><?php echo esc_html($family['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <p class="bd-kicker">Atelier</p>
            <p><a href="<?php echo esc_url(bd_theme_shop_url()); ?>">Catalogue</a></p>
            <p><a href="<?php echo esc_url(bd_theme_cart_url()); ?>">Panier</a></p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
