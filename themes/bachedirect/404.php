<?php
/**
 * Page absente.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="bd-wrap bd-page">
    <h1>Page introuvable</h1>
    <p><a class="bd-btn" href="<?php echo esc_url(home_url('/')); ?>">Retour à l'accueil</a></p>
</div>
<?php
get_footer();
