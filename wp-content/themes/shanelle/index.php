<?php
/**
 * Main template fallback.
 *
 * @package Shanelle
 */

declare(strict_types=1);

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
					<header class="entry__header">
						<?php the_title( '<h1 class="entry__title">', '</h1>' ); ?>
					</header>
					<div class="entry__content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<?php shanelle_component( 'empty-state', array(
				'title'   => __( 'Nothing found', 'shanelle' ),
				'message' => __( 'Try searching or browse our latest styles.', 'shanelle' ),
			) ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
