<?php
/**
 * Product information component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders expandable product information sections on the PDP.
 */
final class ProductInformation {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-information';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-information';

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
	 * Visible accordion sections.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $sections = array();

	/**
	 * Boot component hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets on single product pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue component assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-information',
			self::COMPONENT_URI . '/product-information.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-information',
			self::COMPONENT_URI . '/product-information.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-information', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-information',
			'shanelleProductInformation',
			array(
				'i18n' => array(
					'expandSection'   => __( 'Expand section', 'shanelle' ),
					'collapseSection' => __( 'Collapse section', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the product information accordion.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		self::$product  = $product;
		self::$args     = self::parse_args( $args );
		self::$sections = self::build_sections( $product );

		if ( empty( self::$sections ) ) {
			self::reset_context();
			return;
		}

		if ( ! wp_style_is( 'shanelle-product-information', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-information.php';

		self::reset_context();
	}

	/**
	 * Render all visible accordion sections.
	 */
	public static function render_sections(): void {
		$expanded_index = self::get_default_expanded_index();

		foreach ( self::$sections as $index => $section ) {
			self::render_section( $section, $index === $expanded_index );
		}
	}

	/**
	 * Render a single accordion section.
	 *
	 * @param array<string, mixed> $section  Section data.
	 * @param bool                 $expanded Whether the section starts expanded.
	 */
	public static function render_section( array $section, bool $expanded ): void {
		$id = (string) $section['id'];
		?>
		<div class="product-information__item" data-shanelle-information-section="<?php echo esc_attr( $id ); ?>">
			<h3 class="product-information__heading">
				<button
					type="button"
					class="product-information__trigger"
					id="<?php echo esc_attr( self::get_trigger_id( $id ) ); ?>"
					aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( self::get_panel_id( $id ) ); ?>"
					data-shanelle-information-trigger
					data-section-id="<?php echo esc_attr( $id ); ?>"
				>
					<span class="product-information__trigger-label"><?php echo esc_html( (string) $section['title'] ); ?></span>
					<span class="product-information__icon" aria-hidden="true"></span>
				</button>
			</h3>
			<div
				class="product-information__panel"
				id="<?php echo esc_attr( self::get_panel_id( $id ) ); ?>"
				role="region"
				aria-labelledby="<?php echo esc_attr( self::get_trigger_id( $id ) ); ?>"
				<?php echo $expanded ? '' : 'hidden'; ?>
				data-shanelle-information-panel
			>
				<div class="product-information__content">
					<?php echo wp_kses_post( (string) $section['content'] ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Return component root ID.
	 */
	public static function get_root_id(): string {
		return 'product-information-' . self::$args['information_id'];
	}

	/**
	 * Return section heading ID.
	 */
	public static function get_heading_id(): string {
		return self::get_root_id() . '-heading';
	}

	/**
	 * Return hydration JSON for client bootstrapping.
	 */
	public static function get_information_json(): string {
		$data = array(
			'productId' => self::get_product()->get_id(),
			'sections'  => array_map(
				static function ( array $section ): array {
					return array(
						'id'    => $section['id'],
						'title' => $section['title'],
					);
				},
				self::$sections
			),
		);

		return wp_json_encode( $data ) ?: '{}';
	}

	/**
	 * Return accordion trigger ID.
	 */
	public static function get_trigger_id( string $section_id ): string {
		return self::get_root_id() . '-trigger-' . sanitize_title( $section_id );
	}

	/**
	 * Return accordion panel ID.
	 */
	public static function get_panel_id( string $section_id ): string {
		return self::get_root_id() . '-panel-' . sanitize_title( $section_id );
	}

	/**
	 * Build all accordion sections and omit empty entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_sections( \WC_Product $product ): array {
		$sections = array(
			self::build_description_section( $product ),
			self::build_specifications_section( $product ),
			self::build_filtered_section(
				'size-guide',
				__( 'Size Guide', 'shanelle' ),
				'shanelle_product_information_size_guide',
				$product
			),
			self::build_filtered_section(
				'care-instructions',
				__( 'Care Instructions', 'shanelle' ),
				'shanelle_product_information_care_instructions',
				$product
			),
			self::build_filtered_section(
				'shipping',
				__( 'Shipping', 'shanelle' ),
				'shanelle_product_information_shipping',
				$product
			),
			self::build_filtered_section(
				'returns',
				__( 'Returns', 'shanelle' ),
				'shanelle_product_information_returns',
				$product
			),
		);

		$sections = array_values(
			array_filter(
				$sections,
				static function ( $section ): bool {
					return is_array( $section ) && ! empty( $section['has_content'] );
				}
			)
		);

		/**
		 * Filter visible product information accordion sections.
		 *
		 * @param array<int, array<string, mixed>> $sections Accordion sections.
		 * @param \WC_Product                       $product  Product object.
		 */
		return apply_filters( 'shanelle_product_information_sections', $sections, $product );
	}

	/**
	 * Build description section from WooCommerce long description.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function build_description_section( \WC_Product $product ): ?array {
		$content = apply_filters( 'the_content', $product->get_description() );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( '' === $content || '' === trim( wp_strip_all_tags( $content ) ) ) {
			return null;
		}

		return array(
			'id'          => 'description',
			'title'       => __( 'Description', 'shanelle' ),
			'content'     => $content,
			'has_content' => true,
		);
	}

	/**
	 * Build specifications section from WooCommerce product data.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function build_specifications_section( \WC_Product $product ): ?array {
		$rows = self::get_specification_rows( $product );

		if ( empty( $rows ) ) {
			return null;
		}

		ob_start();
		?>
		<dl class="product-information__spec-list">
			<?php foreach ( $rows as $row ) : ?>
				<div class="product-information__spec-row">
					<dt class="product-information__spec-label"><?php echo esc_html( (string) $row['label'] ); ?></dt>
					<dd class="product-information__spec-value"><?php echo wp_kses_post( (string) $row['value'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php
		$content = (string) ob_get_clean();

		return array(
			'id'          => 'specifications',
			'title'       => __( 'Specifications', 'shanelle' ),
			'content'     => $content,
			'has_content' => true,
		);
	}

	/**
	 * Build a filter-driven section for future ACF or theme option content.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function build_filtered_section(
		string $id,
		string $title,
		string $filter,
		\WC_Product $product
	): ?array {
		/**
		 * Filter product information section content.
		 *
		 * Return HTML string. Empty string hides the section.
		 *
		 * @param string      $content Default empty content.
		 * @param \WC_Product $product Product object.
		 */
		$content = apply_filters( $filter, '', $product );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( '' === $content || '' === trim( wp_strip_all_tags( $content ) ) ) {
			return null;
		}

		return array(
			'id'          => $id,
			'title'       => $title,
			'content'     => wp_kses_post( $content ),
			'has_content' => true,
		);
	}

	/**
	 * Build specification rows from WooCommerce product data.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private static function get_specification_rows( \WC_Product $product ): array {
		$rows = array();

		if ( wc_product_sku_enabled() ) {
			$sku = $product->get_sku();

			if ( is_string( $sku ) && '' !== $sku ) {
				$rows[] = array(
					'label' => __( 'SKU', 'shanelle' ),
					'value' => esc_html( $sku ),
				);
			}
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}

			$label = wc_attribute_label( $attribute->get_name(), $product );
			$value = self::get_attribute_value( $product, $attribute );

			if ( '' === $value ) {
				continue;
			}

			$rows[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		if ( $product->has_weight() ) {
			$rows[] = array(
				'label' => __( 'Weight', 'shanelle' ),
				'value' => esc_html( wc_format_weight( $product->get_weight() ) ),
			);
		}

		if ( $product->has_dimensions() ) {
			$rows[] = array(
				'label' => __( 'Dimensions', 'shanelle' ),
				'value' => esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ),
			);
		}

		return $rows;
	}

	/**
	 * Return formatted attribute values for specifications.
	 */
	private static function get_attribute_value( \WC_Product $product, \WC_Product_Attribute $attribute ): string {
		if ( $attribute->is_taxonomy() ) {
			$values = wc_get_product_terms(
				$product->get_id(),
				$attribute->get_name(),
				array(
					'fields' => 'names',
				)
			);

			return ! empty( $values ) ? esc_html( implode( ', ', $values ) ) : '';
		}

		$options = array_map( 'strval', $attribute->get_options() );

		return ! empty( $options ) ? esc_html( implode( ', ', $options ) ) : '';
	}

	/**
	 * Return index of the first section that should start expanded.
	 */
	private static function get_default_expanded_index(): int {
		return 0;
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
				'information_id' => wp_unique_id( 'information-' ),
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$product  = null;
		self::$args     = array();
		self::$sections = array();
	}

	/**
	 * Return active product.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductInformation render context is not set.' );
		}

		return self::$product;
	}
}
