<?php
/**
 * Catalog filters component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders layered catalog filters and applies them to product queries.
 */
final class CatalogFilters {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/catalog-filters';

	private const COMPONENT_URI = SHANELLE_URI . '/components/catalog-filters';

	private const QUERY_VAR_PREFIX = 'shanelle_filter_';

	private const VISIBLE_OPTIONS = 6;

	/**
	 * Active render context.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Boot catalog filter hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'shanelle_shop_archive_filters', array( self::class, 'render_hook' ), 5 );
		add_action( 'woocommerce_product_query', array( self::class, 'apply_to_product_query' ), 20 );
	}

	/**
	 * Enqueue catalog filter assets on catalog pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! ShopArchive::is_catalog_context() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-catalog-filters',
			self::COMPONENT_URI . '/catalog-filters.css',
			array( 'shanelle-main', 'shanelle-shop-archive' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-catalog-filters',
			self::COMPONENT_URI . '/catalog-filters.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-catalog-filters', 'type', 'module' );

		wp_localize_script(
			'shanelle-catalog-filters',
			'shanelleCatalogFilters',
			array(
				'i18n' => array(
					'viewMore'       => __( 'Ver más', 'shanelle' ),
					'viewLess'       => __( 'Ver menos', 'shanelle' ),
					'expandSection'  => __( 'Expandir sección', 'shanelle' ),
					'collapseSection'=> __( 'Contraer sección', 'shanelle' ),
					'priceMin'       => __( 'Mín', 'shanelle' ),
					'priceMax'       => __( 'Máx', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Hook callback for shop archive filter slots.
	 */
	public static function render_hook(): void {
		self::render( array( 'form_id' => 'catalog-filters-mobile' ) );
	}

	/**
	 * Render the catalog filters form.
	 *
	 * @param array<string, mixed> $args Render arguments.
	 */
	public static function render( array $args = array() ): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		self::$args = wp_parse_args(
			$args,
			array(
				'form_id' => 'catalog-filters-' . wp_unique_id(),
			)
		);

		require self::COMPONENT_DIR . '/catalog-filters.php';

		self::$args = array();
	}

	/**
	 * Return active form ID for templates.
	 */
	public static function get_form_id(): string {
		return (string) self::$args['form_id'];
	}

	/**
	 * Return configured filter groups mapped for rendering.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_filter_groups(): array {
		$groups = self::get_filter_definitions();

		$prepared = array();

		foreach ( $groups as $index => $group ) {
			if ( ! is_array( $group ) || empty( $group['id'] ) || empty( $group['label'] ) || empty( $group['type'] ) ) {
				continue;
			}

			$group['index']   = $index;
			$group['options'] = self::get_group_options( $group );
			$group['selected'] = self::get_selected_values( $group );

			if ( 'price' !== $group['type'] && empty( $group['options'] ) ) {
				continue;
			}

			$prepared[] = $group;
		}

		return $prepared;
	}

	/**
	 * Return raw filter group definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_filter_definitions(): array {
		$groups = array(
			array(
				'id'       => 'category',
				'label'    => __( 'Categoría', 'shanelle' ),
				'type'     => 'radio',
				'taxonomy' => 'product_cat',
				'param'    => self::QUERY_VAR_PREFIX . 'category',
			),
			array(
				'id'       => 'size',
				'label'    => __( 'Talla', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_size',
				'param'    => self::QUERY_VAR_PREFIX . 'size',
			),
			array(
				'id'       => 'color',
				'label'    => __( 'Color', 'shanelle' ),
				'type'     => 'color',
				'taxonomy' => 'pa_color',
				'param'    => self::QUERY_VAR_PREFIX . 'color',
			),
			array(
				'id'       => 'material',
				'label'    => __( 'Material', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_material',
				'param'    => self::QUERY_VAR_PREFIX . 'material',
			),
			array(
				'id'       => 'detail',
				'label'    => __( 'Detalle', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_detail',
				'param'    => self::QUERY_VAR_PREFIX . 'detail',
			),
			array(
				'id'       => 'style',
				'label'    => __( 'Estilo', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_style',
				'param'    => self::QUERY_VAR_PREFIX . 'style',
			),
			array(
				'id'       => 'type',
				'label'    => __( 'Tipo', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_type',
				'param'    => self::QUERY_VAR_PREFIX . 'type',
			),
			array(
				'id'       => 'length',
				'label'    => __( 'Largo', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_length',
				'param'    => self::QUERY_VAR_PREFIX . 'length',
			),
			array(
				'id'       => 'feature',
				'label'    => __( 'Característica', 'shanelle' ),
				'type'     => 'checkbox',
				'taxonomy' => 'pa_feature',
				'param'    => self::QUERY_VAR_PREFIX . 'feature',
			),
			array(
				'id'    => 'price',
				'label' => __( 'Rango de precio', 'shanelle' ),
				'type'  => 'price',
				'param' => self::QUERY_VAR_PREFIX . 'price',
			),
		);

		/**
		 * Filter catalog sidebar filter group configuration.
		 *
		 * @param array<int, array<string, mixed>> $groups Filter groups.
		 */
		return apply_filters( 'shanelle_catalog_filter_groups', $groups );
	}

	/**
	 * Return options for a filter group.
	 *
	 * @param array<string, mixed> $group Filter group config.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_group_options( array $group ): array {
		if ( 'price' === $group['type'] ) {
			return array();
		}

		$taxonomy = (string) ( $group['taxonomy'] ?? '' );

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term_index => $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[] = array(
				'index'    => $term_index,
				'id'       => (int) $term->term_id,
				'slug'     => $term->slug,
				'label'    => $term->name,
				'count'    => (int) $term->count,
				'color'    => self::get_color_hex( $term ),
			);
		}

		return $options;
	}

	/**
	 * Return selected values for a filter group from the request.
	 *
	 * @param array<string, mixed> $group Filter group config.
	 * @return array<int, string>
	 */
	public static function get_selected_values( array $group ): array {
		$param = (string) ( $group['param'] ?? '' );

		if ( '' === $param ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $param ] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET[ $param ] );

		if ( is_array( $raw ) ) {
			return array_values(
				array_filter(
					array_map( 'sanitize_title', $raw )
				)
			);
		}

		$value = sanitize_title( (string) $raw );

		return '' !== $value ? array( $value ) : array();
	}

	/**
	 * Return selected min/max price values.
	 *
	 * @return array{min: string, max: string}
	 */
	public static function get_selected_price_range(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min = isset( $_GET[ self::QUERY_VAR_PREFIX . 'min_price' ] )
			? wc_clean( wp_unslash( (string) $_GET[ self::QUERY_VAR_PREFIX . 'min_price' ] ) )
			: '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = isset( $_GET[ self::QUERY_VAR_PREFIX . 'max_price' ] )
			? wc_clean( wp_unslash( (string) $_GET[ self::QUERY_VAR_PREFIX . 'max_price' ] ) )
			: '';

		return array(
			'min' => $min,
			'max' => $max,
		);
	}

	/**
	 * Return whether a filter option is selected.
	 *
	 * @param array<string, mixed> $group  Filter group.
	 * @param string               $value  Option value.
	 */
	public static function is_option_selected( array $group, string $value ): bool {
		return in_array( $value, self::get_selected_values( $group ), true );
	}

	/**
	 * Return the number of initially visible options.
	 */
	public static function get_visible_option_limit(): int {
		return (int) apply_filters( 'shanelle_catalog_filter_visible_options', self::VISIBLE_OPTIONS );
	}

	/**
	 * Return color hex for a color attribute term.
	 */
	public static function get_color_hex( \WP_Term $term ): string {
		$meta = get_term_meta( $term->term_id, 'shanelle_color_hex', true );

		if ( is_string( $meta ) && preg_match( '/^#?[0-9a-fA-F]{3,8}$/', $meta ) ) {
			return '#' . ltrim( $meta, '#' );
		}

		$map = array(
			'multi'   => '#e8b4b8',
			'black'   => '#1a1a1a',
			'blue'    => '#4a6fa5',
			'white'   => '#f5f5f5',
			'pink'    => '#f4b8c8',
			'grey'    => '#9ca3af',
			'gray'    => '#9ca3af',
			'green'   => '#6b9e78',
			'yellow'  => '#f5d76e',
			'red'     => '#d64545',
			'beige'   => '#e8dcc8',
			'brown'   => '#8b5e3c',
			'purple'  => '#8b6faf',
			'orange'  => '#e89b4c',
			'nude'    => '#d4b8a8',
			'khaki'   => '#b8a88a',
		);

		$slug = sanitize_title( $term->slug );

		return (string) ( $map[ $slug ] ?? '#d4d4d4' );
	}

	/**
	 * Return hidden fields preserving unrelated query vars.
	 */
	public static function render_preserved_fields(): void {
		$preserve = array( 'orderby', 's', 'post_type' );

		foreach ( $preserve as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET[ $key ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = wc_clean( wp_unslash( (string) $_GET[ $key ] ) );

			if ( '' === $value ) {
				continue;
			}

			printf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}

	/**
	 * Apply active filters to WooCommerce product queries.
	 *
	 * @param \WP_Query $query Product query.
	 */
	public static function apply_to_product_query( \WP_Query $query ): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! ShopArchive::is_catalog_context() ) {
			return;
		}

		$tax_clauses = self::build_tax_clauses();
		$meta_clauses = self::build_price_meta_clauses();

		if ( ! empty( $tax_clauses ) ) {
			$existing = $query->get( 'tax_query' );

			if ( ! is_array( $existing ) ) {
				$existing = array();
			}

			if ( count( $tax_clauses ) > 1 ) {
				$existing[] = array(
					'relation' => 'AND',
				);
			}

			$query->set( 'tax_query', array_merge( $existing, $tax_clauses ) );
		}

		if ( ! empty( $meta_clauses ) ) {
			$existing = $query->get( 'meta_query' );

			if ( ! is_array( $existing ) ) {
				$existing = array();
			}

			$existing[] = array(
				'relation' => 'AND',
			);

			$query->set( 'meta_query', array_merge( $existing, $meta_clauses ) );
		}
	}

	/**
	 * Build taxonomy clauses from active filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_tax_clauses(): array {
		$clauses = array();

		foreach ( self::get_filter_definitions() as $group ) {
			if ( in_array( $group['type'], array( 'price' ), true ) ) {
				continue;
			}

			$selected = self::get_selected_values( $group );

			if ( empty( $selected ) ) {
				continue;
			}

			$taxonomy = (string) ( $group['taxonomy'] ?? '' );

			if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$clauses[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $selected,
				'operator' => 'IN',
			);
		}

		return $clauses;
	}

	/**
	 * Build price meta query clauses.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_price_meta_clauses(): array {
		$range = self::get_selected_price_range();
		$clauses = array();

		if ( '' !== $range['min'] && is_numeric( $range['min'] ) ) {
			$clauses[] = array(
				'key'     => '_price',
				'value'   => (float) $range['min'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( '' !== $range['max'] && is_numeric( $range['max'] ) ) {
			$clauses[] = array(
				'key'     => '_price',
				'value'   => (float) $range['max'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		return $clauses;
	}
}
