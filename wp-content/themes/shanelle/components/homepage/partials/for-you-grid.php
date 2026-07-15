<?php
/**
 * Homepage For You product feed partial.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Homepage;
use Shanelle\Components\ProductGrid;

defined( 'ABSPATH' ) || exit;

$config     = Homepage::get_for_you_config();
$query_vars = Homepage::build_for_you_query_vars( $config );
$title      = (string) ( $config['title'] ?? '' );
?>
<section
	class="homepage__for-you"
	id="<?php echo esc_attr( (string) ( $config['anchor_id'] ?? '' ) ); ?>"
	data-shanelle-homepage-section
	data-section-key="for-you"
	aria-labelledby="<?php echo esc_attr( (string) ( $config['heading_id'] ?? '' ) ); ?>"
>
	<div class="container homepage__for-you-inner">
		<?php if ( '' !== $title ) : ?>
			<header class="homepage__for-you-header">
				<h2 id="<?php echo esc_attr( (string) ( $config['heading_id'] ?? '' ) ); ?>" class="homepage__for-you-title">
					<?php echo esc_html( $title ); ?>
				</h2>
			</header>
		<?php endif; ?>

		<div class="homepage__for-you-grid">
			<?php
			ProductGrid::render(
				$query_vars,
				array(
					'grid_id'           => (string) ( $config['grid_id'] ?? '' ),
					'pagination_mode'   => 'load_more',
					'load_more_label'   => (string) ( $config['load_more_label'] ?? __( 'Ver más', 'shanelle' ) ),
					'empty_message'     => (string) ( $config['empty_message'] ?? '' ),
					'card_args'         => array(
						'show_rating'     => false,
						'show_attributes' => false,
						'show_actions'    => true,
					),
				)
			);
			?>
		</div>
	</div>
</section>
