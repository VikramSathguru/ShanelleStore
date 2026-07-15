<?php
/**
 * Product variation selector component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\WooCommerce\ProductPrice;

defined( 'ABSPATH' ) || exit;

/**
 * Custom variation selectors for WooCommerce variable products.
 */
final class ProductVariations {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-variations';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-variations';

	/**
	 * Active product instance.
	 */
	private static ?\WC_Product_Variable $product = null;

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Parsed attribute groups for the active product.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $attribute_groups = array();

	/**
	 * Boot component hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'woocommerce_available_variation', array( self::class, 'extend_variation_data' ), 10, 3 );
	}

	/**
	 * Extend variation JSON with selector sync fields.
	 *
	 * @param array<string, mixed>  $data      Variation data.
	 * @param \WC_Product             $product   Parent product.
	 * @param \WC_Product_Variation   $variation Variation product.
	 * @return array<string, mixed>
	 */
	public static function extend_variation_data( array $data, \WC_Product $product, \WC_Product_Variation $variation ): array {
		$stock = self::get_variation_stock_data( $variation );

		$data['shanelle_stock_status'] = $stock['status'];
		$data['shanelle_stock_label']  = $stock['label'];
		$data['shanelle_gallery_image_id'] = (int) $variation->get_image_id();
		$data['shanelle_variation_name']   = wc_get_formatted_variation( $variation, true, false );

		$price = ProductPrice::get_display_data( $variation );
		$data['shanelle_is_on_sale']   = (bool) $price['is_on_sale'];
		$data['shanelle_current_html']   = (string) $price['current_html'];
		$data['shanelle_regular_html']   = (string) $price['regular_html'];
		$data['shanelle_savings_html']   = (string) $price['savings_html'];

		return $data;
	}

	/**
	 * Enqueue assets on single product pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product_Variable ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue selector assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-variations',
			self::COMPONENT_URI . '/product-variations.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script( 'wc-add-to-cart-variation' );

		wp_enqueue_script(
			'shanelle-product-variations',
			self::COMPONENT_URI . '/product-variations.js',
			array( 'wc-add-to-cart-variation' ),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-variations', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-variations',
			'shanelleProductVariations',
			array(
				'i18n' => array(
					'chooseOption'    => __( 'Elige una opción', 'shanelle' ),
					'clearSelections' => __( 'Limpiar selección', 'shanelle' ),
					'selected'        => __( 'Seleccionado', 'shanelle' ),
					'unavailable'     => __( 'No disponible', 'shanelle' ),
					'inStock'         => __( 'En stock', 'shanelle' ),
					'outOfStock'      => __( 'Agotado', 'shanelle' ),
					'onBackorder'     => __( 'Disponible bajo pedido', 'shanelle' ),
					'lowStock'        => __( 'Poco stock', 'shanelle' ),
					'variationReady'  => __( 'Variación seleccionada', 'shanelle' ),
					'variationReset'  => __( 'Variación eliminada', 'shanelle' ),
					'optionSelected'  => __( '%1$s seleccionado: %2$s', 'shanelle' ),
					'gallerySoon'     => __( 'Sincronización de galería (próximamente)', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the variation selector.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return;
		}

		self::$product          = $product;
		self::$args             = self::parse_args( $args );
		self::$attribute_groups = self::build_attribute_groups( $product );

		if ( empty( self::$attribute_groups ) ) {
			self::reset_context();
			return;
		}

		if ( ! wp_style_is( 'shanelle-product-variations', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-variations.php';

		self::reset_context();
	}

	/**
	 * Render all attribute groups.
	 */
	public static function render_attribute_groups(): void {
		foreach ( self::$attribute_groups as $group ) {
			self::render_attribute_group( $group );
		}
	}

	/**
	 * Render a single attribute group.
	 *
	 * @param array<string, mixed> $group Attribute group data.
	 */
	public static function render_attribute_group( array $group ): void {
		$type      = (string) $group['type'];
		$attribute = (string) $group['name'];
		$slug      = (string) $group['slug'];
		$label     = (string) $group['label'];
		$options   = $group['options'];
		$legend_id = self::get_group_legend_id( $slug );
		?>
		<fieldset class="product-variations__group product-variations__group--<?php echo esc_attr( $type ); ?>">
			<legend id="<?php echo esc_attr( $legend_id ); ?>" class="product-variations__legend text-label">
				<span class="product-variations__legend-label"><?php echo esc_html( $label ); ?></span>
				<span class="product-variations__legend-value text-caption" data-shanelle-variation-selected-label="<?php echo esc_attr( $slug ); ?>"></span>
			</legend>

			<?php if ( 'color' === $type ) : ?>
				<div
					class="product-variations__swatches"
					role="radiogroup"
					aria-labelledby="<?php echo esc_attr( $legend_id ); ?>"
					data-shanelle-variation-group="<?php echo esc_attr( $slug ); ?>"
					data-attribute-name="<?php echo esc_attr( $attribute ); ?>"
				>
					<?php foreach ( $options as $option ) : ?>
						<?php self::render_color_swatch( $attribute, $option ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div
					class="product-variations__options chip-list chip-list--wrap"
					role="radiogroup"
					aria-labelledby="<?php echo esc_attr( $legend_id ); ?>"
					data-shanelle-variation-group="<?php echo esc_attr( $slug ); ?>"
					data-attribute-name="<?php echo esc_attr( $attribute ); ?>"
				>
					<?php foreach ( $options as $option ) : ?>
						<?php self::render_size_option( $attribute, $option, $type ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</fieldset>
		<?php
	}

	/**
	 * Render color swatch control.
	 *
	 * @param string               $attribute Attribute name.
	 * @param array<string, mixed> $option    Option data.
	 */
	public static function render_color_swatch( string $attribute, array $option ): void {
		$value     = (string) $option['value'];
		$label     = (string) $option['label'];
		$color     = (string) $option['color'];
		$available = (bool) $option['available'];
		$style     = '' !== $color ? '--product-variation-swatch-color:' . esc_attr( $color ) . ';' : '';
		?>
		<button
			type="button"
			class="product-variations__swatch"
			role="radio"
			aria-checked="false"
			aria-label="<?php echo esc_attr( $label ); ?>"
			data-shanelle-variation-option
			data-attribute="<?php echo esc_attr( $attribute ); ?>"
			data-value="<?php echo esc_attr( $value ); ?>"
			data-label="<?php echo esc_attr( $label ); ?>"
			<?php echo $available ? '' : 'disabled aria-disabled="true"'; ?>
			<?php echo '' !== $style ? 'style="' . esc_attr( $style ) . '"' : ''; ?>
		>
			<span class="product-variations__swatch-fill" aria-hidden="true"></span>
			<span class="sr-only"><?php echo esc_html( $label ); ?></span>
		</button>
		<?php
	}

	/**
	 * Render size or generic option control.
	 *
	 * @param string               $attribute Attribute name.
	 * @param array<string, mixed> $option    Option data.
	 * @param string               $type      Group type.
	 */
	public static function render_size_option( string $attribute, array $option, string $type ): void {
		$value     = (string) $option['value'];
		$label     = (string) $option['label'];
		$available = (bool) $option['available'];
		?>
		<button
			type="button"
			class="chip chip--filter product-variations__option product-variations__option--<?php echo esc_attr( $type ); ?>"
			role="radio"
			aria-checked="false"
			data-shanelle-variation-option
			data-attribute="<?php echo esc_attr( $attribute ); ?>"
			data-value="<?php echo esc_attr( $value ); ?>"
			data-label="<?php echo esc_attr( $label ); ?>"
			<?php echo $available ? '' : 'disabled aria-disabled="true"'; ?>
		>
			<?php echo esc_html( $label ); ?>
		</button>
		<?php
	}

	/**
	 * Render selected variation availability.
	 */
	public static function render_availability(): void {
		?>
		<div class="product-variations__availability" data-shanelle-variation-availability hidden>
			<span class="product-variations__availability-indicator" aria-hidden="true"></span>
			<span class="product-variations__availability-label" data-shanelle-variation-availability-label></span>
		</div>
		<?php
	}

	/**
	 * Render gallery synchronization placeholder.
	 */
	public static function render_gallery_sync_placeholder(): void {
		?>
		<div
			class="product-variations__gallery-sync"
			data-shanelle-variation-gallery-sync
			aria-hidden="true"
			hidden
		>
			<p class="product-variations__gallery-sync-note text-caption text-muted">
				<?php esc_html_e( 'Sincronización de galería (próximamente)', 'shanelle' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render variation reset control.
	 */
	public static function render_reset(): void {
		?>
		<button
			type="button"
			class="product-variations__reset btn btn--ghost btn--sm"
			data-shanelle-variation-reset
			aria-label="<?php esc_attr_e( 'Limpiar selección', 'shanelle' ); ?>"
		>
			<?php esc_html_e( 'Limpiar selección', 'shanelle' ); ?>
		</button>
		<?php
	}

	/**
	 * Render native WooCommerce selects for variation matching.
	 */
	public static function render_native_selects(): void {
		$product = self::get_product();

		foreach ( self::$attribute_groups as $group ) {
			$attribute = (string) $group['name'];
			$options   = array_column( $group['options'], 'value' );
			$selected  = self::get_selected_attribute_value( $product, $attribute );
			?>
			<div class="product-variations__native-item" data-shanelle-variation-native-item data-attribute-name="<?php echo esc_attr( $attribute ); ?>">
				<label class="sr-only" for="<?php echo esc_attr( self::get_select_id( $attribute ) ); ?>">
					<?php echo esc_html( (string) $group['label'] ); ?>
				</label>
				<?php
				wc_dropdown_variation_attribute_options(
					array(
						'options'          => $options,
						'attribute'        => $attribute,
						'product'          => $product,
						'selected'         => $selected,
						'required'         => true,
						'show_option_none' => sprintf(
							/* translators: %s: attribute label */
							__( 'Elige %s', 'shanelle' ),
							wc_attribute_label( $attribute, $product )
						),
						'id'               => self::get_select_id( $attribute ),
					)
				);
				?>
			</div>
			<?php
		}
	}

	/**
	 * Return component root ID.
	 */
	public static function get_root_id(): string {
		return 'product-variations-' . self::$args['variations_id'];
	}

	/**
	 * Return active product ID.
	 */
	public static function get_render_product_id(): int {
		return self::get_product()->get_id();
	}

	/**
	 * Return attribute group legend ID.
	 */
	public static function get_group_legend_id( string $slug ): string {
		return self::get_root_id() . '-legend-' . $slug;
	}

	/**
	 * Return native select element ID.
	 */
	public static function get_select_id( string $attribute ): string {
		return self::get_root_id() . '-select-' . sanitize_title( $attribute );
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
				'variations_id' => wp_unique_id( 'variations-' ),
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$product          = null;
		self::$args             = array();
		self::$attribute_groups = array();
	}

	/**
	 * Return active product.
	 */
	private static function get_product(): \WC_Product_Variable {
		if ( ! self::$product instanceof \WC_Product_Variable ) {
			throw new \LogicException( 'ProductVariations render context is not set.' );
		}

		return self::$product;
	}

	/**
	 * Build attribute groups from WooCommerce variation data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_attribute_groups( \WC_Product_Variable $product ): array {
		$attributes   = $product->get_variation_attributes();
		$availability = self::build_option_availability_map( $product );
		$groups       = array();

		foreach ( $attributes as $attribute_name => $options ) {
			if ( empty( $options ) ) {
				continue;
			}

			$slug  = sanitize_title( $attribute_name );
			$type  = self::resolve_attribute_type( $attribute_name );
			$items = array();

			foreach ( $options as $option ) {
				$option = (string) $option;
				$items[] = self::build_option_data(
					$attribute_name,
					$option,
					$availability,
					$type
				);
			}

			$groups[] = array(
				'name'    => $attribute_name,
				'slug'    => $slug,
				'label'   => wc_attribute_label( $attribute_name, $product ),
				'type'    => $type,
				'options' => $items,
			);
		}

		return $groups;
	}

	/**
	 * Build option data for a single attribute value.
	 *
	 * @param array<string, array<string, array<string, bool>>> $availability Availability map.
	 * @return array<string, mixed>
	 */
	private static function build_option_data(
		string $attribute_name,
		string $option,
		array $availability,
		string $type
	): array {
		$attr_key  = 'attribute_' . sanitize_title( $attribute_name );
		$available = $availability[ $attr_key ][ $option ]['purchasable'] ?? true;
		$label     = $option;
		$color     = '';

		if ( taxonomy_exists( $attribute_name ) ) {
			$term = get_term_by( 'slug', $option, $attribute_name );

			if ( $term instanceof \WP_Term ) {
				$label = $term->name;
				$color = (string) get_term_meta( $term->term_id, 'color', true );

				/**
				 * Filter swatch color for a variation attribute term.
				 *
				 * @param string   $color     Hex or CSS color value.
				 * @param \WP_Term $term       Attribute term.
				 * @param string   $attribute Attribute taxonomy name.
				 */
				$color = (string) apply_filters( 'shanelle_variation_swatch_color', $color, $term, $attribute_name );
			}
		}

		if ( 'color' === $type && '' === $color ) {
			$color = self::get_fallback_swatch_color( $label );
		}

		return array(
			'value'     => $option,
			'label'     => $label,
			'color'     => $color,
			'available' => $available,
		);
	}

	/**
	 * Resolve attribute UI type from attribute name.
	 */
	private static function resolve_attribute_type( string $attribute_name ): string {
		$slug = sanitize_title( $attribute_name );

		/**
		 * Filter attribute UI type for variation selectors.
		 *
		 * @param string $type            Resolved type: color, size, or option.
		 * @param string $attribute_name  Attribute name or taxonomy.
		 */
		$type = (string) apply_filters(
			'shanelle_variation_attribute_type',
			self::guess_attribute_type( $slug ),
			$attribute_name
		);

		if ( ! in_array( $type, array( 'color', 'size', 'option' ), true ) ) {
			return 'option';
		}

		return $type;
	}

	/**
	 * Guess attribute type from slug.
	 */
	private static function guess_attribute_type( string $slug ): string {
		$color_slugs = array( 'color', 'colour', 'colors', 'colours', 'pa_color', 'pa_colour' );
		$size_slugs  = array( 'size', 'sizes', 'pa_size', 'pa_sizes' );

		foreach ( $color_slugs as $match ) {
			if ( $slug === $match || str_contains( $slug, 'color' ) || str_contains( $slug, 'colour' ) ) {
				return 'color';
			}
		}

		foreach ( $size_slugs as $match ) {
			if ( $slug === $match || str_contains( $slug, 'size' ) ) {
				return 'size';
			}
		}

		return 'option';
	}

	/**
	 * Build availability map from WooCommerce variation data.
	 *
	 * @return array<string, array<string, array<string, bool>>>
	 */
	private static function build_option_availability_map( \WC_Product_Variable $product ): array {
		$map = array();

		foreach ( $product->get_available_variations() as $variation ) {
			if ( empty( $variation['attributes'] ) || ! is_array( $variation['attributes'] ) ) {
				continue;
			}

			$in_stock      = ! empty( $variation['is_in_stock'] );
			$is_purchasable = ! empty( $variation['is_purchasable'] );

			foreach ( $variation['attributes'] as $attr_key => $value ) {
				$value = (string) $value;

				if ( '' === $value ) {
					continue;
				}

				if ( ! isset( $map[ $attr_key ][ $value ] ) ) {
					$map[ $attr_key ][ $value ] = array(
						'in_stock'      => false,
						'purchasable'   => false,
					);
				}

				if ( $in_stock ) {
					$map[ $attr_key ][ $value ]['in_stock'] = true;
				}

				if ( $is_purchasable ) {
					$map[ $attr_key ][ $value ]['purchasable'] = true;
				}
			}
		}

		return $map;
	}

	/**
	 * Return selected attribute value from request or default.
	 */
	private static function get_selected_attribute_value( \WC_Product_Variable $product, string $attribute_name ): string {
		$request_key = 'attribute_' . sanitize_title( $attribute_name );

		if ( isset( $_REQUEST[ $request_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return wc_clean( wp_unslash( (string) $_REQUEST[ $request_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$default = $product->get_variation_default_attribute( $attribute_name );

		return is_string( $default ) ? $default : '';
	}

	/**
	 * Return stock data for a variation product.
	 *
	 * @return array{status: string, label: string}
	 */
	private static function get_variation_stock_data( \WC_Product_Variation $variation ): array {
		if ( ! $variation->is_in_stock() ) {
			return array(
				'status' => 'outofstock',
				'label'  => __( 'Agotado', 'shanelle' ),
			);
		}

		if ( $variation->is_on_backorder() ) {
			return array(
				'status' => 'onbackorder',
				'label'  => __( 'Disponible bajo pedido', 'shanelle' ),
			);
		}

		if ( $variation->managing_stock() && null !== $variation->get_stock_quantity() ) {
			$quantity  = (int) $variation->get_stock_quantity();
			$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

			if ( $quantity > 0 && $quantity <= $threshold ) {
				return array(
					'status' => 'lowstock',
					'label'  => __( 'Poco stock', 'shanelle' ),
				);
			}
		}

		return array(
			'status' => 'instock',
			'label'  => __( 'En stock', 'shanelle' ),
		);
	}

	/**
	 * Generate deterministic fallback swatch color from label hash.
	 */
	private static function get_fallback_swatch_color( string $label ): string {
		$palette = array(
			'#f78fb3',
			'#d4628a',
			'#ffb3cb',
			'#b84d72',
			'#ffd1e0',
			'#943d5c',
			'#e87a9e',
			'#6e2f45',
		);

		$index = abs( crc32( strtolower( $label ) ) ) % count( $palette );

		return $palette[ $index ];
	}
}
