<?php
/**
 * Page.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="bd-wrap bd-page">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div class="bd-prose"><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</div>
<?php
get_footer();
