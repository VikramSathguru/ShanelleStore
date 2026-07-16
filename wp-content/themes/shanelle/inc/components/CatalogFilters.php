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
	 * Preferred attribute role → candidate taxonomy suffixes (without pa_).
	 *
	 * @var array<string, array<int, string>>
	 */
	private const ATTRIBUTE_ROLE_CANDIDATES = array(
		'size'     => array( 'size', 'talla', 'sizes' ),
		'color'    => array( 'color', 'colour', 'color-family', 'colour-family' ),
		'material' => array( 'material', 'fabric', 'tela' ),
		'detail'   => array( 'detail', 'details', 'detalle' ),
		'style'    => array( 'style', 'estilo' ),
		'type'     => array( 'type', 'tipo', 'product-type' ),
		'length'   => array( 'length', 'largo' ),
		'feature'  => array( 'feature', 'features', 'caracteristica', 'caracteristicas' ),
	);

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
		add_action( 'woocommerce_product_query', array( self::class, 'apply_to_product_query' ), 20 );
		add_action( 'pre_get_posts', array( self::class, 'apply_to_search_query' ), 25 );
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
					'viewMore'        => __( 'Ver más', 'shanelle' ),
					'viewLess'        => __( 'Ver menos', 'shanelle' ),
					'expandSection'   => __( 'Expandir sección', 'shanelle' ),
					'collapseSection' => __( 'Contraer sección', 'shanelle' ),
					'priceMin'        => __( 'Mín', 'shanelle' ),
					'priceMax'        => __( 'Máx', 'shanelle' ),
					'clearAll'        => __( 'Limpiar filtros', 'shanelle' ),
					'removeFilter'    => __( 'Quitar filtro', 'shanelle' ),
					'activeFilters'   => __( 'Filtros activos', 'shanelle' ),
				),
			)
		);
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
				'form_id'     => 'catalog-filters-' . wp_unique_id(),
				'submit_mode' => 'instant',
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
	 * Return submit mode for the active form (`instant` | `apply`).
	 */
	public static function get_submit_mode(): string {
		$mode = (string) ( self::$args['submit_mode'] ?? 'instant' );

		return in_array( $mode, array( 'instant', 'apply' ), true ) ? $mode : 'instant';
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

			if ( 'category' === (string) $group['id'] && self::should_hide_category_filter() ) {
				continue;
			}

			$group['index']    = $index;
			$group['options']  = self::get_group_options( $group );
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
		$attribute_map = self::resolve_attribute_taxonomies();

		$groups = array(
			array(
				'id'       => 'category',
				'label'    => __( 'Categoría', 'shanelle' ),
				'type'     => 'radio',
				'taxonomy' => 'product_cat',
				'param'    => self::QUERY_VAR_PREFIX . 'category',
			),
		);

		$attribute_labels = array(
			'size'     => __( 'Talla', 'shanelle' ),
			'color'    => __( 'Color', 'shanelle' ),
			'material' => __( 'Material', 'shanelle' ),
			'detail'   => __( 'Detalle', 'shanelle' ),
			'style'    => __( 'Estilo', 'shanelle' ),
			'type'     => __( 'Tipo', 'shanelle' ),
			'length'   => __( 'Largo', 'shanelle' ),
			'feature'  => __( 'Característica', 'shanelle' ),
		);

		foreach ( $attribute_labels as $role => $label ) {
			$taxonomy = (string) ( $attribute_map[ $role ] ?? '' );

			if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$groups[] = array(
				'id'       => $role,
				'label'    => $label,
				'type'     => 'color' === $role ? 'color' : 'checkbox',
				'taxonomy' => $taxonomy,
				'param'    => self::QUERY_VAR_PREFIX . $role,
				'role'     => $role,
			);
		}

		$groups[] = array(
			'id'    => 'price',
			'label' => __( 'Rango de precio', 'shanelle' ),
			'type'  => 'price',
			'param' => self::QUERY_VAR_PREFIX . 'price',
		);

		/**
		 * Filter catalog sidebar filter group configuration.
		 *
		 * @param array<int, array<string, mixed>> $groups Filter groups.
		 */
		return apply_filters( 'shanelle_catalog_filter_groups', $groups );
	}

	/**
	 * Resolve attribute taxonomies for known fashion filter roles.
	 *
	 * Prefers standard `pa_*` slugs, then matches registered WC attributes by slug aliases.
	 *
	 * @return array<string, string> Role => taxonomy name.
	 */
	public static function resolve_attribute_taxonomies(): array {
		$resolved = array();

		foreach ( self::ATTRIBUTE_ROLE_CANDIDATES as $role => $candidates ) {
			foreach ( $candidates as $candidate ) {
				$taxonomy = 0 === strpos( $candidate, 'pa_' ) ? $candidate : 'pa_' . $candidate;

				if ( taxonomy_exists( $taxonomy ) ) {
					$resolved[ $role ] = $taxonomy;
					break;
				}
			}
		}

		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attributes = wc_get_attribute_taxonomies();

			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $attribute ) {
					$slug = sanitize_title( (string) ( $attribute->attribute_name ?? '' ) );

					if ( '' === $slug ) {
						continue;
					}

					$taxonomy = function_exists( 'wc_attribute_taxonomy_name' )
						? wc_attribute_taxonomy_name( $slug )
						: 'pa_' . $slug;

					if ( ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					foreach ( self::ATTRIBUTE_ROLE_CANDIDATES as $role => $candidates ) {
						if ( isset( $resolved[ $role ] ) ) {
							continue;
						}

						if ( in_array( $slug, $candidates, true ) ) {
							$resolved[ $role ] = $taxonomy;
						}
					}
				}
			}
		}

		/**
		 * Filter resolved attribute taxonomy map (role => taxonomy).
		 *
		 * @param array<string, string> $resolved Role map.
		 */
		$filtered = apply_filters( 'shanelle_catalog_filter_attribute_map', $resolved );

		return is_array( $filtered ) ? $filtered : $resolved;
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

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( 'product_cat' === $taxonomy ) {
			$args = array_merge( $args, self::get_category_term_query_args() );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term_index => $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[] = array(
				'index' => $term_index,
				'id'    => (int) $term->term_id,
				'slug'  => $term->slug,
				'label' => $term->name,
				'count' => (int) $term->count,
				'color' => self::get_color_hex( $term ),
			);
		}

		return $options;
	}

	/**
	 * Category term query args scoped to the current archive context.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_category_term_query_args(): array {
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$current = get_queried_object();

			if ( $current instanceof \WP_Term ) {
				return array(
					'parent' => (int) $current->term_id,
				);
			}
		}

		return array(
			'parent' => 0,
		);
	}

	/**
	 * Whether the category filter group should be hidden.
	 */
	private static function should_hide_category_filter(): bool {
		if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return false;
		}

		$current = get_queried_object();

		if ( ! $current instanceof \WP_Term ) {
			return false;
		}

		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $current->term_id,
				'hide_empty' => true,
				'number'     => 1,
				'fields'     => 'ids',
			)
		);

		return is_wp_error( $children ) || empty( $children );
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
	 * @param array<string, mixed> $group Filter group.
	 * @param string               $value Option value.
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
			'multi'  => '#e8b4b8',
			'black'  => '#1a1a1a',
			'blue'   => '#4a6fa5',
			'white'  => '#f5f5f5',
			'pink'   => '#f4b8c8',
			'grey'   => '#9ca3af',
			'gray'   => '#9ca3af',
			'green'  => '#6b9e78',
			'yellow' => '#f5d76e',
			'red'    => '#d64545',
			'beige'  => '#e8dcc8',
			'brown'  => '#8b5e3c',
			'purple' => '#8b6faf',
			'orange' => '#e89b4c',
			'nude'   => '#d4b8a8',
			'khaki'  => '#b8a88a',
		);

		$slug = sanitize_title( $term->slug );

		return (string) ( $map[ $slug ] ?? '#d4d4d4' );
	}

	/**
	 * Return hidden fields preserving unrelated query vars.
	 */
	public static function render_preserved_fields(): void {
		// Intentionally omit `paged` so filter submits restart at page 1.
		$preserve = array( 'orderby', 's', 'post_type' );

		foreach ( $preserve as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET[ $key ] ) || '' === $_GET[ $key ] ) {
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
	 * Apply active filters to WooCommerce product archive queries.
	 *
	 * @param \WP_Query $query Product query.
	 */
	public static function apply_to_product_query( \WP_Query $query ): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! ShopArchive::is_catalog_context() || is_search() ) {
			return;
		}

		self::apply_filters_to_query( $query );
	}

	/**
	 * Apply active filters to product search main queries.
	 *
	 * WooCommerce does not fire `woocommerce_product_query` on search requests.
	 *
	 * @param \WP_Query $query Main query.
	 */
	public static function apply_to_search_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );

		if ( 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
			return;
		}

		self::apply_filters_to_query( $query );
	}

	/**
	 * Merge Shanelle filter clauses into a query safely.
	 *
	 * @param \WP_Query $query Query instance.
	 */
	public static function apply_filters_to_query( \WP_Query $query ): void {
		$tax_clauses  = self::build_tax_clauses();
		$meta_clauses = self::build_price_meta_clauses();

		if ( ! empty( $tax_clauses ) ) {
			$query->set( 'tax_query', self::merge_tax_query( $query->get( 'tax_query' ), $tax_clauses ) );
		}

		if ( ! empty( $meta_clauses ) ) {
			$query->set( 'meta_query', self::merge_meta_query( $query->get( 'meta_query' ), $meta_clauses ) );
		}
	}

	/**
	 * Merge taxonomy clauses with a valid top-level relation.
	 *
	 * @param mixed                         $existing Existing tax_query value.
	 * @param array<int, array<string, mixed>> $clauses Clauses to append.
	 * @return array<string|int, mixed>
	 */
	public static function merge_tax_query( mixed $existing, array $clauses ): array {
		$merged = self::normalize_query_array( $existing );

		foreach ( $clauses as $clause ) {
			$merged[] = $clause;
		}

		if ( count( $merged ) > 1 ) {
			$merged['relation'] = 'AND';
		}

		return $merged;
	}

	/**
	 * Merge meta clauses with a valid top-level relation.
	 *
	 * @param mixed                         $existing Existing meta_query value.
	 * @param array<int, array<string, mixed>> $clauses Clauses to append.
	 * @return array<string|int, mixed>
	 */
	public static function merge_meta_query( mixed $existing, array $clauses ): array {
		$merged = self::normalize_query_array( $existing );

		foreach ( $clauses as $clause ) {
			$merged[] = $clause;
		}

		if ( count( $merged ) > 1 ) {
			$merged['relation'] = 'AND';
		}

		return $merged;
	}

	/**
	 * Normalize a tax/meta query into a list of clauses (relation stripped for rebuild).
	 *
	 * @param mixed $existing Existing query value.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_query_array( mixed $existing ): array {
		if ( ! is_array( $existing ) ) {
			return array();
		}

		$clauses = array();

		foreach ( $existing as $key => $clause ) {
			if ( 'relation' === $key ) {
				continue;
			}

			if ( ! is_array( $clause ) ) {
				continue;
			}

			// Skip invalid relation-only pseudo-clauses from older merges.
			if ( isset( $clause['relation'] ) && ! isset( $clause['taxonomy'] ) && ! isset( $clause['key'] ) ) {
				continue;
			}

			$clauses[] = $clause;
		}

		return $clauses;
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

			if ( 'category' === (string) ( $group['id'] ?? '' ) && self::should_hide_category_filter() ) {
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
		$range   = self::get_selected_price_range();
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

	/**
	 * Return active filter chips for the current request.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_active_chips(): array {
		$chips = array();

		foreach ( self::get_filter_groups() as $group ) {
			$type = (string) ( $group['type'] ?? '' );

			if ( 'price' === $type ) {
				continue;
			}

			$selected = self::get_selected_values( $group );
			$options  = array();

			foreach ( (array) ( $group['options'] ?? array() ) as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}

				$options[ (string) ( $option['slug'] ?? '' ) ] = (string) ( $option['label'] ?? '' );
			}

			foreach ( $selected as $slug ) {
				$label = $options[ $slug ] ?? $slug;
				$param = (string) ( $group['param'] ?? '' );

				$chips[] = array(
					'id'         => (string) ( $group['id'] ?? '' ) . ':' . $slug,
					'label'      => $label,
					'group'      => (string) ( $group['label'] ?? '' ),
					'remove_url' => self::build_filter_url(
						array(
							'remove_param' => $param,
							'remove_value' => $slug,
						)
					),
				);
			}
		}

		$price = self::get_selected_price_range();

		if ( '' !== $price['min'] || '' !== $price['max'] ) {
			$price_label = __( 'Precio', 'shanelle' );

			if ( '' !== $price['min'] && '' !== $price['max'] ) {
				$price_label = sprintf(
					/* translators: 1: min price, 2: max price */
					__( 'Precio: %1$s – %2$s', 'shanelle' ),
					$price['min'],
					$price['max']
				);
			} elseif ( '' !== $price['min'] ) {
				$price_label = sprintf(
					/* translators: %s: min price */
					__( 'Desde %s', 'shanelle' ),
					$price['min']
				);
			} elseif ( '' !== $price['max'] ) {
				$price_label = sprintf(
					/* translators: %s: max price */
					__( 'Hasta %s', 'shanelle' ),
					$price['max']
				);
			}

			$chips[] = array(
				'id'         => 'price',
				'label'      => $price_label,
				'group'      => __( 'Precio', 'shanelle' ),
				'remove_url' => self::build_filter_url(
					array(
						'remove_price' => true,
					)
				),
			);
		}

		return $chips;
	}

	/**
	 * Whether any Shanelle catalog filters are active.
	 */
	public static function has_active_filters(): bool {
		return ! empty( self::get_active_chips() );
	}

	/**
	 * Return URL that clears all Shanelle filter params.
	 */
	public static function get_clear_all_url(): string {
		return self::build_filter_url(
			array(
				'clear_all' => true,
			)
		);
	}

	/**
	 * Build a catalog URL with filter param mutations.
	 *
	 * @param array<string, mixed> $args Mutation args.
	 */
	public static function build_filter_url( array $args = array() ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$params = wp_unslash( $_GET );

		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( ! empty( $args['clear_all'] ) ) {
			foreach ( array_keys( $params ) as $key ) {
				if ( is_string( $key ) && str_starts_with( $key, self::QUERY_VAR_PREFIX ) ) {
					unset( $params[ $key ] );
				}
			}
		}

		if ( ! empty( $args['remove_price'] ) ) {
			unset( $params[ self::QUERY_VAR_PREFIX . 'min_price' ], $params[ self::QUERY_VAR_PREFIX . 'max_price' ] );
		}

		$remove_param = (string) ( $args['remove_param'] ?? '' );
		$remove_value = (string) ( $args['remove_value'] ?? '' );

		if ( '' !== $remove_param && isset( $params[ $remove_param ] ) ) {
			$current = $params[ $remove_param ];

			if ( is_array( $current ) ) {
				$params[ $remove_param ] = array_values(
					array_filter(
						$current,
						static function ( $value ) use ( $remove_value ): bool {
							return sanitize_title( (string) $value ) !== $remove_value;
						}
					)
				);

				if ( empty( $params[ $remove_param ] ) ) {
					unset( $params[ $remove_param ] );
				}
			} else {
				unset( $params[ $remove_param ] );
			}
		}

		unset( $params['paged'] );

		$base = self::get_current_catalog_url();

		return add_query_arg( $params, $base );
	}

	/**
	 * Return the current catalog page URL without query args.
	 */
	private static function get_current_catalog_url(): string {
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'shop' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();

			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );

				if ( ! is_wp_error( $link ) ) {
					return (string) $link;
				}
			}
		}

		if ( is_search() ) {
			return home_url( '/' );
		}

		return home_url( add_query_arg( array() ) );
	}

	/**
	 * Render active filter chips + clear-all control.
	 */
	public static function render_active_chips(): void {
		$chips = self::get_active_chips();

		if ( empty( $chips ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/active-chips.php';
	}
}
