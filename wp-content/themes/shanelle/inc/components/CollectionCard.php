<?php
/**
 * Collection card component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders reusable collection cards for collection listings.
 */
final class CollectionCard {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/collection-card';

	private const COMPONENT_URI = SHANELLE_URI . '/components/collection-card';

	private const IMAGE_SIZE = 'shanelle-collection-card';

	/**
	 * Active card data for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $collection = array();

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Boot collection card hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'after_setup_theme', array( self::class, 'register_image_sizes' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register collection card image size.
	 */
	public static function register_image_sizes(): void {
		add_image_size( self::IMAGE_SIZE, 480, 600, true );
	}

	/**
	 * Enqueue collection card assets.
	 */
	public static function enqueue_assets(): void {
		wp_enqueue_style(
			'shanelle-collection-card',
			self::COMPONENT_URI . '/collection-card.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render a collection card.
	 *
	 * @param array<string, mixed> $collection Normalized collection data.
	 * @param array<string, mixed> $args       Optional render arguments.
	 */
	public static function render( array $collection, array $args = array() ): void {
		if ( empty( $collection['url'] ) ) {
			return;
		}

		self::$collection = $collection;
		self::$args       = wp_parse_args(
			$args,
			array(
				'show_count' => true,
				'show_type'  => false,
			)
		);

		if ( ! wp_style_is( 'shanelle-collection-card', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/collection-card.php';

		self::$collection = array();
		self::$args       = array();
	}

	/**
	 * Return card CSS classes.
	 *
	 * @return array<int, string>
	 */
	public static function get_card_classes(): array {
		$classes = array( 'collection-card' );

		if ( ! empty( self::$args['show_type'] ) && ! empty( self::$collection['type'] ) ) {
			$classes[] = 'collection-card--' . sanitize_html_class( (string) self::$collection['type'] );
		}

		return $classes;
	}

	/**
	 * Render collection card media.
	 */
	public static function render_media(): void {
		$hero_id = (int) ( self::$collection['hero_id'] ?? 0 );
		$name    = (string) ( self::$collection['name'] ?? '' );
		?>
		<div class="collection-card__media">
			<?php if ( $hero_id > 0 ) : ?>
				<?php
				shanelle_responsive_image(
					$hero_id,
					self::IMAGE_SIZE,
					array(
						'class'    => 'collection-card__image',
						'alt'      => $name,
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
			<?php else : ?>
				<div class="collection-card__placeholder" aria-hidden="true"></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Whether product count should render.
	 */
	public static function show_product_count(): bool {
		return ! empty( self::$args['show_count'] );
	}

	/**
	 * Whether collection type badge should render.
	 */
	public static function show_type_badge(): bool {
		return ! empty( self::$args['show_type'] );
	}

	/**
	 * Return active collection data.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_collection(): array {
		return self::$collection;
	}
}
