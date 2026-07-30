<?php
/**
 * Product size guide modal — presentation only.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Screenshot-matched size guide modal for the PDP size row.
 *
 * Plugin First: Mature size-chart plugins exist, but they do not deliver this
 * brand-specific modal UX (fit scale, dual charts, unit toggle, type switch)
 * without heavy restyling. Chart data remains filterable for CMS/plugin feeds.
 */
final class SizeGuide {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/size-guide';

	private const COMPONENT_URI = SHANELLE_URI . '/components/size-guide';

	private const META_FIT = '_shanelle_size_guide_fit';

	/**
	 * Active product for the current render.
	 */
	private static ?\WC_Product $product = null;

	/**
	 * Normalized guide payload for the current render.
	 *
	 * @var array<string, mixed>
	 */
	private static array $guide = array();

	/**
	 * Boot component hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets on product pages when a size guide can render.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$guide = self::get_guide_data( $product );

		if ( empty( $guide['enabled'] ) ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Whether the size guide should be offered for a product.
	 */
	public static function is_enabled( \WC_Product $product ): bool {
		$guide = self::get_guide_data( $product );

		return ! empty( $guide['enabled'] );
	}

	/**
	 * Build filterable size guide data (measurements stored in CM).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_guide_data( \WC_Product $product ): array {
		$defaults = self::get_default_guide_data( $product );

		/**
		 * Filter size guide payload for the PDP modal.
		 *
		 * @param array<string, mixed> $guide   Guide data.
		 * @param \WC_Product          $product Product.
		 */
		$filtered = apply_filters( 'shanelle_size_guide_data', $defaults, $product );

		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}

		return self::normalize_guide_data( $filtered, $defaults );
	}

	/**
	 * Render the open trigger (placed on the size options row).
	 */
	public static function render_trigger( \WC_Product $product ): void {
		if ( ! self::is_enabled( $product ) ) {
			return;
		}

		if ( ! wp_style_is( 'shanelle-size-guide', 'enqueued' ) ) {
			self::register_assets();
		}

		$modal_id = self::get_modal_id( $product );
		?>
		<button
			type="button"
			class="product-variations__size-guide-trigger"
			data-shanelle-size-guide-open
			aria-haspopup="dialog"
			aria-controls="<?php echo esc_attr( $modal_id ); ?>"
		>
			<?php esc_html_e( 'Guía de tallas', 'shanelle' ); ?>
		</button>
		<?php
	}

	/**
	 * Render the size guide modal once per product.
	 */
	public static function render_modal( \WC_Product $product ): void {
		if ( ! self::is_enabled( $product ) ) {
			return;
		}

		if ( ! wp_style_is( 'shanelle-size-guide', 'enqueued' ) ) {
			self::register_assets();
		}

		self::$product = $product;
		self::$guide   = self::get_guide_data( $product );

		require self::COMPONENT_DIR . '/size-guide.php';

		self::$product = null;
		self::$guide   = array();
	}

	/**
	 * Modal element id.
	 */
	public static function get_modal_id( \WC_Product $product ): string {
		return 'shanelle-size-guide-' . (int) $product->get_id();
	}

	/**
	 * Active product for the template.
	 */
	public static function get_product(): ?\WC_Product {
		return self::$product;
	}

	/**
	 * Active guide payload for the template.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_guide(): array {
		return self::$guide;
	}

	/**
	 * JSON for the modal root (client unit/type/tab switching).
	 */
	public static function get_guide_json(): string {
		$encoded = wp_json_encode( self::$guide );

		return is_string( $encoded ) ? $encoded : '{}';
	}

	/**
	 * Register and enqueue component assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-size-guide',
			self::COMPONENT_URI . '/size-guide.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-size-guide',
			self::COMPONENT_URI . '/size-guide.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-size-guide', 'type', 'module' );

		wp_localize_script(
			'shanelle-size-guide',
			'shanelleSizeGuide',
			array(
				'i18n' => array(
					'close'     => __( 'Cerrar', 'shanelle' ),
					'openLabel' => __( 'Guía de tallas', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Default women’s fashion charts (CM).
	 *
	 * @return array<string, mixed>
	 */
	private static function get_default_guide_data( \WC_Product $product ): array {
		$fit = self::resolve_fit_type( $product );

		return array(
			'enabled'      => true,
			'fit'          => $fit,
			'default_unit' => 'cm',
			'default_type' => self::guess_default_type( $product ),
			'default_chart'=> 'product',
			'types'        => array(
				'tops'    => array(
					'label' => __( 'Tops', 'shanelle' ),
				),
				'pants'   => array(
					'label' => __( 'Pantalones', 'shanelle' ),
				),
				'dresses' => array(
					'label' => __( 'Vestidos', 'shanelle' ),
				),
			),
			'fit_labels'   => array(
				'skinny'    => __( 'Ajustado', 'shanelle' ),
				'regular'   => __( 'Regular', 'shanelle' ),
				'oversized' => __( 'Oversized', 'shanelle' ),
			),
			'column_labels'=> array(
				'size'    => __( 'Talla', 'shanelle' ),
				'shoulder'=> __( 'Hombro', 'shanelle' ),
				'bust'    => __( 'Busto', 'shanelle' ),
				'waist'   => __( 'Cintura', 'shanelle' ),
				'hip'     => __( 'Cadera', 'shanelle' ),
				'length'  => __( 'Largo', 'shanelle' ),
				'sleeve'  => __( 'Largo de manga', 'shanelle' ),
				'inseam'  => __( 'Entrepierna', 'shanelle' ),
			),
			'charts'       => array(
				'product' => array(
					'tops'    => array(
						'columns'   => array( 'size', 'shoulder', 'bust', 'length', 'sleeve' ),
						'highlight' => 'length',
						'rows'      => array(
							array(
								'size'     => 'XS',
								'shoulder' => 35.0,
								'bust'     => 82.0,
								'length'   => 58.0,
								'sleeve'   => 58.0,
							),
							array(
								'size'     => 'S',
								'shoulder' => 36.5,
								'bust'     => 86.0,
								'length'   => 60.0,
								'sleeve'   => 59.0,
							),
							array(
								'size'     => 'M',
								'shoulder' => 38.0,
								'bust'     => 90.0,
								'length'   => 62.0,
								'sleeve'   => 60.0,
							),
							array(
								'size'     => 'L',
								'shoulder' => 39.5,
								'bust'     => 96.0,
								'length'   => 64.0,
								'sleeve'   => 61.0,
							),
							array(
								'size'     => 'XL',
								'shoulder' => 41.0,
								'bust'     => 102.0,
								'length'   => 66.0,
								'sleeve'   => 62.0,
							),
						),
					),
					'pants'   => array(
						'columns'   => array( 'size', 'waist', 'hip', 'length', 'inseam' ),
						'highlight' => 'length',
						'rows'      => array(
							array(
								'size'   => 'XS',
								'waist'  => 62.0,
								'hip'    => 88.0,
								'length' => 98.0,
								'inseam' => 74.0,
							),
							array(
								'size'   => 'S',
								'waist'  => 66.0,
								'hip'    => 92.0,
								'length' => 99.0,
								'inseam' => 75.0,
							),
							array(
								'size'   => 'M',
								'waist'  => 70.0,
								'hip'    => 96.0,
								'length' => 100.0,
								'inseam' => 76.0,
							),
							array(
								'size'   => 'L',
								'waist'  => 76.0,
								'hip'    => 102.0,
								'length' => 101.0,
								'inseam' => 77.0,
							),
							array(
								'size'   => 'XL',
								'waist'  => 82.0,
								'hip'    => 108.0,
								'length' => 102.0,
								'inseam' => 78.0,
							),
						),
					),
					'dresses' => array(
						'columns'   => array( 'size', 'bust', 'waist', 'hip', 'length' ),
						'highlight' => 'length',
						'rows'      => array(
							array(
								'size'   => 'XS',
								'bust'   => 82.0,
								'waist'  => 64.0,
								'hip'    => 88.0,
								'length' => 110.0,
							),
							array(
								'size'   => 'S',
								'bust'   => 86.0,
								'waist'  => 68.0,
								'hip'    => 92.0,
								'length' => 112.0,
							),
							array(
								'size'   => 'M',
								'bust'   => 90.0,
								'waist'  => 72.0,
								'hip'    => 96.0,
								'length' => 114.0,
							),
							array(
								'size'   => 'L',
								'bust'   => 96.0,
								'waist'  => 78.0,
								'hip'    => 102.0,
								'length' => 116.0,
							),
							array(
								'size'   => 'XL',
								'bust'   => 102.0,
								'waist'  => 84.0,
								'hip'    => 108.0,
								'length' => 118.0,
							),
						),
					),
				),
				'body'    => array(
					'tops'    => array(
						'columns'   => array( 'size', 'bust', 'waist', 'hip' ),
						'highlight' => 'bust',
						'rows'      => array(
							array(
								'size'  => 'XS',
								'bust'  => 80.0,
								'waist' => 62.0,
								'hip'   => 88.0,
							),
							array(
								'size'  => 'S',
								'bust'  => 84.0,
								'waist' => 66.0,
								'hip'   => 92.0,
							),
							array(
								'size'  => 'M',
								'bust'  => 88.0,
								'waist' => 70.0,
								'hip'   => 96.0,
							),
							array(
								'size'  => 'L',
								'bust'  => 94.0,
								'waist' => 76.0,
								'hip'   => 102.0,
							),
							array(
								'size'  => 'XL',
								'bust'  => 100.0,
								'waist' => 82.0,
								'hip'   => 108.0,
							),
						),
					),
					'pants'   => array(
						'columns'   => array( 'size', 'waist', 'hip' ),
						'highlight' => 'waist',
						'rows'      => array(
							array(
								'size'  => 'XS',
								'waist' => 62.0,
								'hip'   => 88.0,
							),
							array(
								'size'  => 'S',
								'waist' => 66.0,
								'hip'   => 92.0,
							),
							array(
								'size'  => 'M',
								'waist' => 70.0,
								'hip'   => 96.0,
							),
							array(
								'size'  => 'L',
								'waist' => 76.0,
								'hip'   => 102.0,
							),
							array(
								'size'  => 'XL',
								'waist' => 82.0,
								'hip'   => 108.0,
							),
						),
					),
					'dresses' => array(
						'columns'   => array( 'size', 'bust', 'waist', 'hip' ),
						'highlight' => 'bust',
						'rows'      => array(
							array(
								'size'  => 'XS',
								'bust'  => 80.0,
								'waist' => 62.0,
								'hip'   => 88.0,
							),
							array(
								'size'  => 'S',
								'bust'  => 84.0,
								'waist' => 66.0,
								'hip'   => 92.0,
							),
							array(
								'size'  => 'M',
								'bust'  => 88.0,
								'waist' => 70.0,
								'hip'   => 96.0,
							),
							array(
								'size'  => 'L',
								'bust'  => 94.0,
								'waist' => 76.0,
								'hip'   => 102.0,
							),
							array(
								'size'  => 'XL',
								'bust'  => 100.0,
								'waist' => 82.0,
								'hip'   => 108.0,
							),
						),
					),
				),
			),
			'disclaimer'   => __( '*Estas medidas se obtuvieron midiendo el producto a mano; pueden variar 1–2 CM.', 'shanelle' ),
		);
	}

	/**
	 * @param array<string, mixed> $guide    Filtered guide.
	 * @param array<string, mixed> $defaults Defaults.
	 * @return array<string, mixed>
	 */
	private static function normalize_guide_data( array $guide, array $defaults ): array {
		$merged               = array_replace_recursive( $defaults, $guide );
		$merged['enabled']    = ! empty( $merged['enabled'] );
		$merged['fit']        = in_array( (string) ( $merged['fit'] ?? '' ), array( 'skinny', 'regular', 'oversized' ), true )
			? (string) $merged['fit']
			: 'regular';
		$merged['default_unit'] = 'in' === strtolower( (string) ( $merged['default_unit'] ?? 'cm' ) ) ? 'in' : 'cm';

		$type_keys = array_keys( is_array( $merged['types'] ?? null ) ? $merged['types'] : array() );
		$default_type = (string) ( $merged['default_type'] ?? 'tops' );
		$merged['default_type'] = in_array( $default_type, $type_keys, true ) ? $default_type : ( $type_keys[0] ?? 'tops' );

		$merged['default_chart'] = 'body' === ( $merged['default_chart'] ?? '' ) ? 'body' : 'product';

		return $merged;
	}

	/**
	 * Resolve fit type from product meta or default.
	 */
	private static function resolve_fit_type( \WC_Product $product ): string {
		$meta = get_post_meta( $product->get_id(), self::META_FIT, true );

		if ( is_string( $meta ) && in_array( $meta, array( 'skinny', 'regular', 'oversized' ), true ) ) {
			return $meta;
		}

		/**
		 * Filter product fit type for the size guide scale.
		 *
		 * @param string      $fit     skinny|regular|oversized.
		 * @param \WC_Product $product Product.
		 */
		$fit = (string) apply_filters( 'shanelle_size_guide_fit', 'regular', $product );

		return in_array( $fit, array( 'skinny', 'regular', 'oversized' ), true ) ? $fit : 'regular';
	}

	/**
	 * Guess default garment type from product categories.
	 */
	private static function guess_default_type( \WC_Product $product ): string {
		$slugs = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );

		if ( ! is_array( $slugs ) ) {
			return 'tops';
		}

		$joined = strtolower( implode( ' ', $slugs ) );

		if ( str_contains( $joined, 'pant' ) || str_contains( $joined, 'pantal' ) || str_contains( $joined, 'jean' ) || str_contains( $joined, 'skirt' ) || str_contains( $joined, 'falda' ) ) {
			return 'pants';
		}

		if ( str_contains( $joined, 'dress' ) || str_contains( $joined, 'vestid' ) ) {
			return 'dresses';
		}

		return 'tops';
	}
}
