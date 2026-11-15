<?php
/**
 * Repli.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="bd-wrap bd-page">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('bd-prose'); ?>>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <h1>Rien ici</h1>
        <p><a href="<?php echo esc_url(home_url('/')); ?>">Retour à l'accueil</a></p>
    <?php endif; ?>
</div>
<?php
get_footer();
