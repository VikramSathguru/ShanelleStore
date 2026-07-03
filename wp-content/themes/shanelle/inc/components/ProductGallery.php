<?php
/**
 * Product gallery component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Reusable WooCommerce product gallery for single product and PWA contexts.
 */
final class ProductGallery {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-gallery';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-gallery';

	private const MAIN_SIZE = 'shanelle-gallery-main';

	private const THUMB_SIZE = 'shanelle-gallery-thumb';

	/**
	 * Active product instance.
	 */
	private static ?\WC_Product $product = null;

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Normalized gallery items.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $items = array();

	/**
	 * Boot gallery hooks.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_image_sizes' ), 11 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register gallery image sizes.
	 */
	public static function register_image_sizes(): void {
		add_image_size( self::MAIN_SIZE, 800, 1066, true );
		add_image_size( self::THUMB_SIZE, 120, 160, true );
	}

	/**
	 * Enqueue gallery assets on single product pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue gallery assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-gallery',
			self::COMPONENT_URI . '/product-gallery.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-gallery',
			self::COMPONENT_URI . '/product-gallery.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-gallery', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-gallery',
			'shanelleProductGallery',
			array(
				'i18n' => array(
					'previous'    => __( 'Previous image', 'shanelle' ),
					'next'        => __( 'Next image', 'shanelle' ),
					'zoomSoon'    => __( 'Zoom (coming soon)', 'shanelle' ),
					'fullscreen'  => __( 'View fullscreen', 'shanelle' ),
					'close'       => __( 'Close gallery', 'shanelle' ),
					'imageOf'     => __( 'Image %1$d of %2$d', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the product gallery.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		self::$product = $product;
		self::$args    = self::parse_args( $args );
		self::$items   = self::build_gallery_items( $product );

		if ( ! wp_style_is( 'shanelle-product-gallery', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-gallery.php';

		self::$product = null;
		self::$args    = array();
		self::$items   = array();
	}

	/**
	 * Render the main gallery stage.
	 */
	public static function render_main_image(): void {
		$items = self::$items;
		$index = 0;
		$item  = $items[ $index ] ?? null;
		?>
		<div class="product-gallery__stage" data-shanelle-gallery-stage>
			<?php self::render_navigation(); ?>

			<figure class="product-gallery__main">
				<?php if ( $item ) : ?>
					<img
						class="product-gallery__image"
						data-shanelle-gallery-main
						src="<?php echo esc_url( (string) $item['src'] ); ?>"
						<?php if ( ! empty( $item['srcset'] ) ) : ?>
							srcset="<?php echo esc_attr( (string) $item['srcset'] ); ?>"
						<?php endif; ?>
						<?php if ( ! empty( $item['sizes'] ) ) : ?>
							sizes="<?php echo esc_attr( (string) $item['sizes'] ); ?>"
						<?php endif; ?>
						width="<?php echo esc_attr( (string) $item['width'] ); ?>"
						height="<?php echo esc_attr( (string) $item['height'] ); ?>"
						alt="<?php echo esc_attr( (string) $item['alt'] ); ?>"
						decoding="async"
						fetchpriority="high"
						data-index="<?php echo esc_attr( (string) $index ); ?>"
					>
				<?php else : ?>
					<?php echo wc_placeholder_img( self::MAIN_SIZE, array( 'class' => 'product-gallery__image product-gallery__image--placeholder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

				<div class="product-gallery__zoom-lens" data-shanelle-gallery-zoom aria-hidden="true"></div>
			</figure>

			<?php self::render_actions(); ?>
		</div>
		<?php
	}

	/**
	 * Render thumbnail strip.
	 */
	public static function render_thumbnails(): void {
		$items = self::$items;

		if ( count( $items ) <= 1 ) {
			return;
		}
		?>
		<div class="product-gallery__thumbs-wrap" data-shanelle-gallery-thumbs-wrap>
			<ul class="product-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'shanelle' ); ?>" data-shanelle-gallery-thumbs>
				<?php foreach ( $items as $index => $item ) : ?>
					<li class="product-gallery__thumb-item" role="presentation">
						<button
							type="button"
							class="product-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
							role="tab"
							id="<?php echo esc_attr( self::get_thumb_id( (int) $index ) ); ?>"
							aria-controls="<?php echo esc_attr( self::get_panel_id() ); ?>"
							aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
							tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
							data-shanelle-gallery-thumb
							data-index="<?php echo esc_attr( (string) $index ); ?>"
						>
							<img
								src="<?php echo esc_url( (string) $item['thumb_src'] ); ?>"
								alt=""
								width="<?php echo esc_attr( (string) $item['thumb_width'] ); ?>"
								height="<?php echo esc_attr( (string) $item['thumb_height'] ); ?>"
								loading="lazy"
								decoding="async"
								data-shanelle-lazy-thumb
							>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render previous and next controls.
	 */
	public static function render_navigation(): void {
		$disabled = count( self::$items ) <= 1 ? ' disabled' : '';
		?>
		<div class="product-gallery__nav">
			<button
				type="button"
				class="product-gallery__nav-btn product-gallery__nav-btn--prev btn btn--icon"
				data-shanelle-gallery-prev
				aria-label="<?php esc_attr_e( 'Previous image', 'shanelle' ); ?>"
				<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php self::render_icon( 'prev' ); ?>
			</button>
			<button
				type="button"
				class="product-gallery__nav-btn product-gallery__nav-btn--next btn btn--icon"
				data-shanelle-gallery-next
				aria-label="<?php esc_attr_e( 'Next image', 'shanelle' ); ?>"
				<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php self::render_icon( 'next' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render zoom and fullscreen placeholders.
	 */
	public static function render_actions(): void {
		?>
		<div class="product-gallery__actions">
			<button
				type="button"
				class="product-gallery__action btn btn--icon"
				data-shanelle-gallery-zoom-toggle
				aria-disabled="true"
				disabled
				aria-label="<?php esc_attr_e( 'Zoom (coming soon)', 'shanelle' ); ?>"
			>
				<?php self::render_icon( 'zoom' ); ?>
			</button>
			<button
				type="button"
				class="product-gallery__action btn btn--icon"
				data-shanelle-gallery-fullscreen
				aria-label="<?php esc_attr_e( 'View fullscreen', 'shanelle' ); ?>"
				<?php echo empty( self::$items ) ? 'disabled' : ''; ?>
			>
				<?php self::render_icon( 'fullscreen' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render fullscreen modal shell.
	 */
	public static function render_modal(): void {
		?>
		<div class="product-gallery__modal" data-shanelle-gallery-modal hidden>
			<div class="product-gallery__modal-overlay" data-shanelle-gallery-modal-overlay></div>
			<div
				class="product-gallery__modal-dialog"
				role="dialog"
				aria-modal="true"
				aria-label="<?php esc_attr_e( 'Product image fullscreen view', 'shanelle' ); ?>"
				data-shanelle-gallery-modal-panel
				tabindex="-1"
			>
				<button
					type="button"
					class="product-gallery__modal-close btn btn--icon"
					data-shanelle-gallery-modal-close
					aria-label="<?php esc_attr_e( 'Close gallery', 'shanelle' ); ?>"
				>
					<?php self::render_icon( 'close' ); ?>
				</button>
				<figure class="product-gallery__modal-figure">
					<img class="product-gallery__modal-image" data-shanelle-gallery-modal-image alt="">
				</figure>
				<div class="product-gallery__modal-nav">
					<button type="button" class="btn btn--icon" data-shanelle-gallery-modal-prev aria-label="<?php esc_attr_e( 'Previous image', 'shanelle' ); ?>">
						<?php self::render_icon( 'prev' ); ?>
					</button>
					<button type="button" class="btn btn--icon" data-shanelle-gallery-modal-next aria-label="<?php esc_attr_e( 'Next image', 'shanelle' ); ?>">
						<?php self::render_icon( 'next' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Return gallery JSON for client hydration.
	 */
	public static function get_gallery_json(): string {
		return wp_json_encode( self::$items ) ?: '[]';
	}

	/**
	 * Return total image count.
	 */
	public static function get_image_count(): int {
		return count( self::$items );
	}

	/**
	 * Return main panel ID for ARIA wiring.
	 */
	public static function get_panel_id(): string {
		return 'product-gallery-panel-' . self::$args['gallery_id'];
	}

	/**
	 * Return thumb control ID.
	 */
	public static function get_thumb_id( int $index ): string {
		return self::get_panel_id() . '-thumb-' . $index;
	}

	/**
	 * Build normalized gallery items from a product.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_gallery_items( \WC_Product $product ): array {
		$attachment_ids = array();

		$featured_id = (int) $product->get_image_id();

		if ( $featured_id > 0 ) {
			$attachment_ids[] = $featured_id;
		}

		foreach ( $product->get_gallery_image_ids() as $gallery_id ) {
			$gallery_id = (int) $gallery_id;

			if ( $gallery_id > 0 && ! in_array( $gallery_id, $attachment_ids, true ) ) {
				$attachment_ids[] = $gallery_id;
			}
		}

		if ( empty( $attachment_ids ) ) {
			return array();
		}

		$items = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$main  = wp_get_attachment_image_src( $attachment_id, self::MAIN_SIZE );
			$thumb = wp_get_attachment_image_src( $attachment_id, self::THUMB_SIZE );
			$full  = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! $main ) {
				continue;
			}

			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt = $alt ? (string) $alt : $product->get_name();

			$items[] = array(
				'id'           => $attachment_id,
				'src'          => $main[0],
				'width'        => (int) $main[1],
				'height'       => (int) $main[2],
				'full_src'     => $full ? $full[0] : $main[0],
				'thumb_src'    => $thumb ? $thumb[0] : $main[0],
				'thumb_width'  => $thumb ? (int) $thumb[1] : (int) $main[1],
				'thumb_height' => $thumb ? (int) $thumb[2] : (int) $main[2],
				'srcset'       => wp_get_attachment_image_srcset( $attachment_id, self::MAIN_SIZE ) ?: '',
				'sizes'        => '(max-width: 767px) 100vw, 50vw',
				'alt'          => $alt,
			);
		}

		return $items;
	}

	/**
	 * Parse render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args(
			$args,
			array(
				'gallery_id' => wp_unique_id( 'gallery-' ),
			)
		);
	}

	/**
	 * Output inline SVG icon markup.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'prev'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>',
			'next'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>',
			'zoom'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg>',
			'fullscreen'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M16 21h5v-5M8 21H3v-5"/></svg>',
			'close'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
