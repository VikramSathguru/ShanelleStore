<?php
/**
 * Product card component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\WooCommerce\ProductPrice;

defined( 'ABSPATH' ) || exit;

/**
 * Renders reusable WooCommerce product cards across the storefront.
 */
final class ProductCard {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-card';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-card';

	/**
	 * Active product instance for partial renders.
	 */
	private static ?\WC_Product $product = null;

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Boot product card hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue product card assets when WooCommerce is active.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-product-card',
			self::COMPONENT_URI . '/product-card.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-card',
			self::COMPONENT_URI . '/product-card.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-card', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-card',
			'shanelleProductCard',
			array(
				'ajaxUrl' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'i18n'    => array(
					'addToCart'     => __( 'Agregar a la bolsa', 'shanelle' ),
					'selectOptions' => __( 'Elegir opciones', 'shanelle' ),
					'adding'        => __( 'Agregando…', 'shanelle' ),
					'added'         => __( 'Agregado a la bolsa', 'shanelle' ),
					'error'         => __( 'No se pudo agregar a la bolsa. Intenta de nuevo.', 'shanelle' ),
					'soldOut'       => __( 'Agotado', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render a complete product card.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		self::$product = $product;
		self::$args    = self::parse_args( $args );

		require self::COMPONENT_DIR . '/product-card.php';

		self::$product = null;
		self::$args    = array();
	}

	/**
	 * Render product footer with price, sold count, and cart CTA.
	 */
	public static function render_footer(): void {
		if ( 'catalog' !== self::$args['variant'] ) {
			return;
		}

		$product = self::get_product();
		?>
		<div class="product-card__footer">
			<div class="product-card__meta">
				<?php self::render_price(); ?>
				<?php self::render_sold_count(); ?>
			</div>
			<?php self::render_catalog_cart_cta( $product ); ?>
		</div>
		<?php
	}

	/**
	 * Render sold count for catalog cards.
	 */
	public static function render_sold_count(): void {
		if ( 'catalog' !== self::$args['variant'] ) {
			return;
		}

		$product = self::get_product();
		$sold    = (int) $product->get_total_sales();

		if ( $sold <= 0 ) {
			return;
		}
		?>
		<p class="product-card__sold text-caption">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of items sold */
					_n( '%d vendido', '%d vendidos', $sold, 'shanelle' ),
					$sold
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render catalog sale badge overlay on image.
	 */
	public static function render_sale_overlay(): void {
		if ( 'catalog' !== self::$args['variant'] ) {
			return;
		}

		$product = self::get_product();

		if ( ! $product->is_on_sale() || ! $product->is_in_stock() ) {
			return;
		}

		$label = ProductPrice::get_sale_badge_label( $product );

		if ( '' === $label ) {
			return;
		}
		?>
		<span class="product-card__sale-tag"><?php echo esc_html( $label ); ?></span>
		<?php
	}

	/**
	 * Render circular cart CTA for catalog cards.
	 *
	 * @param \WC_Product $product Product instance.
	 */
	private static function render_catalog_cart_cta( \WC_Product $product ): void {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		if ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) {
			?>
			<button
				type="button"
				class="product-card__cart-cta btn btn--icon"
				data-shanelle-quick-add
				data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
				aria-label="<?php echo esc_attr( sprintf(
					/* translators: %s: product name */
					__( 'Agregar %s a la bolsa', 'shanelle' ),
					$product->get_name()
				) ); ?>"
			>
				<?php self::render_icon( 'bag' ); ?>
			</button>
			<?php
			return;
		}

		?>
		<a
			class="product-card__cart-cta btn btn--icon product-card__cart-cta--options"
			href="<?php echo esc_url( $product->get_permalink() ); ?>"
			aria-label="<?php echo esc_attr( sprintf(
				/* translators: %s: product name */
				__( 'Elegir opciones de %s', 'shanelle' ),
				$product->get_name()
			) ); ?>"
		>
			<?php self::render_icon( 'bag' ); ?>
		</a>
		<?php
	}

	/**
	 * Render product badge group.
	 */
	public static function render_badges(): void {
		if ( 'catalog' === self::$args['variant'] ) {
			return;
		}
		$product = self::get_product();
		$badges  = self::get_badges( $product );

		if ( empty( $badges ) ) {
			return;
		}
		?>
		<div class="product-card__badges badge-group">
			<?php foreach ( $badges as $badge ) : ?>
				<span class="badge badge--overlay <?php echo esc_attr( $badge['class'] ); ?>">
					<?php echo esc_html( $badge['label'] ); ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render primary and hover product images.
	 */
	public static function render_image(): void {
		$product   = self::get_product();
		$args      = self::$args;
		$permalink = $product->get_permalink();
		$primary   = (int) $product->get_image_id();
		$secondary = self::get_secondary_image_id( $product );
		$size      = (string) $args['image_size'];
		$sizes     = (string) $args['image_sizes'];
		$loading   = $args['lazy'] ? 'lazy' : 'eager';
		$alt       = $product->get_name();

		?>
		<a
			class="product-card__media-link"
			href="<?php echo esc_url( $permalink ); ?>"
			aria-label="<?php echo esc_attr( $product->get_name() ); ?>"
		>
			<div class="product-card__image-stack<?php echo $secondary ? ' product-card__image-stack--has-hover' : ''; ?>">
				<?php if ( $primary ) : ?>
					<?php
					echo wp_get_attachment_image(
						$primary,
						$size,
						false,
						array(
							'class'         => 'product-card__image product-card__image--primary',
							'loading'       => $loading,
							'decoding'      => 'async',
							'alt'           => $alt,
							'sizes'         => $sizes,
							'fetchpriority' => $args['priority'] ? 'high' : 'auto',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				<?php else : ?>
					<?php echo wc_placeholder_img( $size, array( 'class' => 'product-card__image product-card__image--primary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

				<?php if ( $secondary ) : ?>
					<?php
					echo wp_get_attachment_image(
						$secondary,
						$size,
						false,
						array(
							'class'    => 'product-card__image product-card__image--secondary',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'alt'      => '',
							'sizes'    => $sizes,
							'aria-hidden' => 'true',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}

	/**
	 * Render product price markup.
	 */
	public static function render_price(): void {
		$product = self::get_product();

		if ( ! $product->get_price_html() ) {
			return;
		}

		$classes = ProductPrice::get_compact_classes( $product );

		if ( 'catalog' === self::$args['variant'] ) {
			$classes[] = 'product-card__price--catalog';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>
		<?php
	}

	/**
	 * Render quick action controls.
	 */
	public static function render_actions(): void {
		if ( ! self::$args['show_actions'] ) {
			return;
		}

		$product = self::get_product();
		?>
		<div class="product-card__actions" role="group" aria-label="<?php esc_attr_e( 'Acciones del producto', 'shanelle' ); ?>">
			<?php self::render_quick_add_button( $product ); ?>
		</div>
		<?php
	}

	/**
	 * Render star rating when available.
	 */
	public static function render_rating(): void {
		if ( ! self::$args['show_rating'] ) {
			return;
		}

		$product = self::get_product();

		if ( $product->get_rating_count() <= 0 ) {
			return;
		}

		$rating_html = wc_get_rating_html( $product->get_average_rating() );

		if ( ! $rating_html ) {
			return;
		}
		?>
		<div class="product-card__rating" aria-label="<?php echo esc_attr( sprintf(
			/* translators: %s: average rating */
			__( 'Calificado con %s de 5', 'shanelle' ),
			$product->get_average_rating()
		) ); ?>">
			<?php echo wp_kses_post( $rating_html ); ?>
		</div>
		<?php
	}

	/**
	 * Render short visible attribute summary.
	 */
	public static function render_attributes(): void {
		if ( ! self::$args['show_attributes'] ) {
			return;
		}

		$summary = self::get_attribute_summary( self::get_product() );

		if ( '' === $summary ) {
			return;
		}
		?>
		<p class="product-card__attributes text-caption"><?php echo esc_html( $summary ); ?></p>
		<?php
	}

	/**
	 * Return active product ID for template markup.
	 */
	public static function get_render_product_id(): int {
		return self::get_product()->get_id();
	}

	/**
	 * Return active product permalink for template markup.
	 */
	public static function get_render_permalink(): string {
		return self::get_product()->get_permalink();
	}

	/**
	 * Return active product name for template markup.
	 */
	public static function get_render_name(): string {
		return self::get_product()->get_name();
	}

	/**
	 * Return whether the active product is in stock.
	 */
	public static function get_render_in_stock(): bool {
		return self::get_product()->is_in_stock();
	}

	/**
	 * Return whether the catalog card variant is active.
	 */
	public static function is_catalog_variant(): bool {
		return 'catalog' === ( self::$args['variant'] ?? 'default' );
	}

	/**
	 * Return parsed render arguments.
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args(
			$args,
			array(
				'image_size'     => 'shanelle-product-card',
				'image_sizes'    => '(max-width: 767px) 50vw, (max-width: 1023px) 33vw, 25vw',
				'lazy'           => true,
				'priority'       => false,
				'show_rating'    => true,
				'show_attributes'=> true,
				'show_actions'   => true,
				'variant'        => 'default',
				'new_days'       => (int) apply_filters( 'shanelle_product_card_new_days', 30 ),
			)
		);
	}

	/**
	 * Return the active product or throw logic guard.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductCard render context is not set.' );
		}

		return self::$product;
	}

	/**
	 * Build badge data for a product.
	 *
	 * @return array<int, array{label: string, class: string}>
	 */
	private static function get_badges( \WC_Product $product ): array {
		$badges = array();

		if ( ! $product->is_in_stock() ) {
			$badges[] = array(
				'label' => __( 'Agotado', 'shanelle' ),
				'class' => 'badge--sold-out',
			);
			return $badges;
		}

		if ( self::is_new_product( $product ) ) {
			$badges[] = array(
				'label' => __( 'Nuevo', 'shanelle' ),
				'class' => 'badge--new',
			);
		}

		if ( $product->is_on_sale() ) {
			$badges[] = array(
				'label' => ProductPrice::get_sale_badge_label( $product ),
				'class' => 'badge--sale',
			);
		}

		return $badges;
	}

	/**
	 * Determine whether a product is considered new.
	 */
	private static function is_new_product( \WC_Product $product ): bool {
		$days = (int) self::$args['new_days'];

		if ( $days <= 0 ) {
			return false;
		}

		$created = $product->get_date_created();

		if ( ! $created ) {
			return false;
		}

		$threshold = time() - ( $days * DAY_IN_SECONDS );

		return $created->getTimestamp() >= $threshold;
	}

	/**
	 * Return gallery secondary image attachment ID.
	 */
	private static function get_secondary_image_id( \WC_Product $product ): int {
		$gallery = $product->get_gallery_image_ids();

		if ( empty( $gallery ) ) {
			return 0;
		}

		return (int) $gallery[0];
	}

	/**
	 * Build comma-separated attribute summary.
	 */
	private static function get_attribute_summary( \WC_Product $product ): string {
		$labels = array();
		$limit  = (int) apply_filters( 'shanelle_product_card_attribute_limit', 2 );

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}

			$name = wc_attribute_label( $attribute->get_name(), $product );

			if ( $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms(
					$product->get_id(),
					$attribute->get_name(),
					array( 'fields' => 'names' )
				);

				if ( empty( $terms ) ) {
					continue;
				}

				$labels[] = $name . ': ' . implode( ', ', array_slice( $terms, 0, 3 ) );
			} else {
				$options = $attribute->get_options();

				if ( empty( $options ) ) {
					continue;
				}

				$labels[] = $name . ': ' . implode( ', ', array_slice( array_map( 'strval', $options ), 0, 3 ) );
			}

			if ( count( $labels ) >= $limit ) {
				break;
			}
		}

		return implode( ' · ', $labels );
	}

	/**
	 * Render quick add to cart control.
	 */
	private static function render_quick_add_button( \WC_Product $product ): void {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		if ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) {
			?>
			<button
				type="button"
				class="product-card__action btn btn--icon product-card__quick-add"
				data-shanelle-quick-add
				data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
				aria-label="<?php echo esc_attr( sprintf(
					/* translators: %s: product name */
					__( 'Agregar %s a la bolsa', 'shanelle' ),
					$product->get_name()
				) ); ?>"
			>
				<?php self::render_icon( 'bag' ); ?>
			</button>
			<?php
			return;
		}

		?>
		<a
			class="product-card__action btn btn--icon product-card__quick-add product-card__quick-add--options"
			href="<?php echo esc_url( $product->get_permalink() ); ?>"
			aria-label="<?php echo esc_attr( sprintf(
				/* translators: %s: product name */
				__( 'Elegir opciones de %s', 'shanelle' ),
				$product->get_name()
			) ); ?>"
		>
			<?php self::render_icon( 'options' ); ?>
		</a>
		<?php
	}

	/**
	 * Output inline SVG icon.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'heart'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 20.5 10.55 19.1C5.4 14.36 2 11.28 2 7.5A4.5 4.5 0 0 1 6.5 3 5.5 5.5 0 0 1 12 5.09 5.5 5.5 0 0 1 17.5 3 4.5 4.5 0 0 1 22 7.5c0 3.78-3.4 6.86-8.55 11.6Z"/></svg>',
			'eye'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
			'bag'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
			'options' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
