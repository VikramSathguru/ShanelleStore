<?php
/**
 * Shop archive component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes ProductGrid and archive chrome for WooCommerce catalog pages.
 */
final class ShopArchive {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/shop-archive';

	private const COMPONENT_URI = SHANELLE_URI . '/components/shop-archive';

	private const ORDERBY_SELECT_ID = 'shop-archive-orderby';

	/**
	 * Boot shop archive hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_archive_hooks' ), 20 );
	}

	/**
	 * Adjust WooCommerce hooks on catalog archive pages.
	 */
	public static function configure_archive_hooks(): void {
		if ( ! self::is_catalog_context() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );
		remove_action( 'woocommerce_before_shop_loop_item', 'shanelle_product_card_start', 5 );

		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
	}

	/**
	 * Enqueue shop archive assets on catalog pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_catalog_context() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-shop-archive',
			self::COMPONENT_URI . '/shop-archive.css',
			array( 'shanelle-main', 'shanelle-product-grid' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-shop-archive',
			self::COMPONENT_URI . '/shop-archive.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-shop-archive', 'type', 'module' );

		wp_localize_script(
			'shanelle-shop-archive',
			'shanelleShopArchive',
			array(
				'i18n' => array(
					'filtersOpen'  => __( 'Abrir filtros', 'shanelle' ),
					'filtersClose' => __( 'Cerrar filtros', 'shanelle' ),
					'filtersTitle' => __( 'Filtros', 'shanelle' ),
					'applyFilters' => __( 'Aplicar filtros', 'shanelle' ),
					'loading'      => __( 'Actualizando productos…', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the complete shop archive experience.
	 */
	public static function render(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		require self::COMPONENT_DIR . '/shop-archive.php';
	}

	/**
	 * Render WooCommerce breadcrumbs.
	 */
	public static function render_breadcrumbs(): void {
		if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
			return;
		}

		woocommerce_breadcrumb(
			array(
				'wrap_before' => '<nav class="shop-archive__breadcrumbs" aria-label="' . esc_attr__( 'Ruta de navegación', 'shanelle' ) . '"><ol class="shop-archive__breadcrumbs-list">',
				'wrap_after'  => '</ol></nav>',
				'before'      => '<li class="shop-archive__breadcrumbs-item">',
				'after'       => '</li>',
				'delimiter'   => '<span class="shop-archive__breadcrumbs-sep" aria-hidden="true">/</span>',
			)
		);
	}

	/**
	 * Archive title / count band removed — keep a screen-reader H1 for accessibility.
	 */
	public static function render_header(): void {
		$title = self::get_archive_title();
		?>
		<h1 class="sr-only"><?php echo esc_html( $title ); ?></h1>
		<?php
	}

	/**
	 * Render catalog toolbar with filter trigger and ordering.
	 */
	public static function render_toolbar(): void {
		$active_count = count( CatalogFilters::get_active_chips() );
		?>
		<div class="shop-archive__toolbar" data-shanelle-archive-toolbar>
			<button
				type="button"
				class="btn btn--outline shop-archive__filter-toggle"
				data-shanelle-filter-open
				aria-expanded="false"
				aria-controls="shop-archive-filters"
			>
				<?php esc_html_e( 'Filtros', 'shanelle' ); ?>
				<?php if ( $active_count > 0 ) : ?>
					<span class="shop-archive__filter-count" aria-hidden="true"><?php echo esc_html( (string) $active_count ); ?></span>
					<span class="screen-reader-text">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: active filter count */
								_n( '%d filtro activo', '%d filtros activos', $active_count, 'shanelle' ),
								$active_count
							)
						);
						?>
					</span>
				<?php endif; ?>
			</button>

			<div class="shop-archive__ordering">
				<label class="shop-archive__ordering-label" for="<?php echo esc_attr( self::ORDERBY_SELECT_ID ); ?>">
					<?php esc_html_e( 'Ordenar por', 'shanelle' ); ?>
				</label>
				<div class="shop-archive__ordering-control">
					<?php self::render_ordering(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render active filter chips under the toolbar.
	 */
	public static function render_active_filters(): void {
		CatalogFilters::render_active_chips();
	}

	/**
	 * Render desktop filter sidebar (CatalogFilters is the only source of truth).
	 */
	public static function render_sidebar(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		?>
		<aside class="shop-archive__sidebar" aria-label="<?php esc_attr_e( 'Filtros de productos', 'shanelle' ); ?>">
			<?php
			CatalogFilters::render(
				array(
					'form_id'     => 'catalog-filters-sidebar',
					'submit_mode' => 'instant',
				)
			);
			?>
		</aside>
		<?php
	}

	/**
	 * Render WooCommerce catalog ordering dropdown.
	 */
	public static function render_ordering(): void {
		if ( ! function_exists( 'woocommerce_catalog_ordering' ) ) {
			return;
		}

		ob_start();
		woocommerce_catalog_ordering();
		$html = (string) ob_get_clean();

		if ( '' === $html ) {
			return;
		}

		// Guarantee a stable id for the visible label, even if WC/plugins set another id.
		$html = preg_replace( '/\s+id=(["\']).*?\1/i', '', $html ) ?? $html;
		$html = preg_replace_callback(
			'/<select\b([^>]*)>/i',
			static function ( array $matches ): string {
				$attrs = $matches[1];

				if ( preg_match( '/\bclass=(["\'])([^"\']*)\1/i', $attrs, $class_match ) ) {
					$classes = trim( $class_match[2] . ' select shop-archive__orderby' );
					$attrs   = preg_replace(
						'/\bclass=(["\'])([^"\']*)\1/i',
						'class=' . $class_match[1] . $classes . $class_match[1],
						$attrs,
						1
					) ?? $attrs;
				} else {
					$attrs .= ' class="orderby select shop-archive__orderby"';
				}

				return '<select id="' . esc_attr( self::ORDERBY_SELECT_ID ) . '"' . $attrs . '>';
			},
			$html,
			1
		) ?? $html;

		echo wp_kses(
			$html,
			array(
				'form'   => array(
					'class'  => true,
					'method' => true,
				),
				'select' => array(
					'id'       => true,
					'name'     => true,
					'class'    => true,
					'aria-label' => true,
				),
				'option' => array(
					'value'    => true,
					'selected' => true,
				),
			)
		);
	}

	/**
	 * Render a height-stable filtering indicator (no skeleton layout shift).
	 */
	public static function render_loading_placeholder(): void {
		?>
		<div
			class="shop-archive__loading"
			data-shanelle-archive-loading
			hidden
			aria-hidden="true"
		>
			<div
				class="shop-archive__loading-bar"
				role="progressbar"
				aria-valuemin="0"
				aria-valuemax="100"
				aria-label="<?php esc_attr_e( 'Actualizando productos', 'shanelle' ); ?>"
			></div>
		</div>
		<?php
	}

	/**
	 * Build empty-state actions that preserve archive / filter context.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_empty_actions(): array {
		$actions = array();

		if ( CatalogFilters::has_active_filters() ) {
			$actions[] = array(
				'url'     => CatalogFilters::get_clear_all_url(),
				'label'   => __( 'Limpiar filtros', 'shanelle' ),
				'primary' => true,
			);

			return $actions;
		}

		if ( is_search() ) {
			$actions[] = array(
				'url'     => home_url( '/' ),
				'label'   => __( 'Volver al inicio', 'shanelle' ),
				'primary' => true,
			);

			return $actions;
		}

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$parent = self::get_archive_parent_url();

			if ( '' !== $parent ) {
				$actions[] = array(
					'url'     => $parent,
					'label'   => __( 'Ver categoría superior', 'shanelle' ),
					'primary' => true,
				);
			}

			$actions[] = array(
				'url'     => self::get_shop_url(),
				'label'   => __( 'Explorar la tienda', 'shanelle' ),
				'primary' => empty( $actions ),
			);

			return $actions;
		}

		if ( function_exists( 'is_tax' ) && is_tax( 'product_collection' ) ) {
			$collections = self::get_collections_index_url();

			if ( '' !== $collections ) {
				$actions[] = array(
					'url'     => $collections,
					'label'   => __( 'Ver colecciones', 'shanelle' ),
					'primary' => true,
				);
			}

			$actions[] = array(
				'url'     => self::get_shop_url(),
				'label'   => __( 'Explorar la tienda', 'shanelle' ),
				'primary' => empty( $actions ),
			);

			return $actions;
		}

		$actions[] = array(
			'url'     => home_url( '/' ),
			'label'   => __( 'Volver al inicio', 'shanelle' ),
			'primary' => true,
		);

		return $actions;
	}

	/**
	 * Shop page URL helper.
	 */
	private static function get_shop_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'shop' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Parent term archive URL when available.
	 */
	private static function get_archive_parent_url(): string {
		$term = get_queried_object();

		if ( ! $term instanceof \WP_Term || $term->parent <= 0 ) {
			return '';
		}

		$link = get_term_link( (int) $term->parent, $term->taxonomy );

		return is_wp_error( $link ) ? '' : (string) $link;
	}

	/**
	 * Collections index page URL when a page uses the Collections template.
	 */
	private static function get_collections_index_url(): string {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'page-templates/collections.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( empty( $pages[0] ) ) {
			return '';
		}

		$url = get_permalink( (int) $pages[0] );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Render the product grid using the main query.
	 */
	public static function render_grid(): void {
		global $wp_query;

		/**
		 * Filter shop archive product grid configuration.
		 *
		 * @param array<string, mixed> $grid_args Grid arguments.
		 */
		$grid_args = apply_filters(
			'shanelle_shop_archive_grid_args',
			array(
				'pagination_mode' => 'load_more',
				'load_more_label' => __( 'Ver más', 'shanelle' ),
				'grid_id'         => 'shop-archive-grid',
				'card_args'       => array(
					'variant'         => 'catalog',
					'show_rating'     => false,
					'show_attributes' => false,
					'show_actions'    => false,
					'show_favourite'  => false,
				),
			)
		);

		// Always resolve empty CTAs after filters so archive/search context wins.
		$grid_args['empty_actions'] = self::get_empty_actions();

		if ( CatalogFilters::has_active_filters() ) {
			$grid_args['empty_message'] = __( 'Ningún producto coincide con estos filtros. Quita un filtro o limpia todos para seguir explorando.', 'shanelle' );
		}

		ProductGrid::render( $wp_query, $grid_args );
	}

	/**
	 * Render filter slide-over / bottom sheet panel.
	 */
	public static function render_filters_panel(): void {
		?>
		<div
			id="shop-archive-filters"
			class="shop-archive__filters"
			data-shanelle-filters
			hidden
		>
			<div class="shop-archive__filters-overlay" data-shanelle-filters-overlay></div>
			<div
				class="shop-archive__filters-panel"
				role="dialog"
				aria-modal="true"
				aria-label="<?php esc_attr_e( 'Filtros de productos', 'shanelle' ); ?>"
				data-shanelle-filters-panel
				tabindex="-1"
			>
				<div class="shop-archive__filters-header">
					<h2 class="shop-archive__filters-title text-h5"><?php esc_html_e( 'Filtros', 'shanelle' ); ?></h2>
					<button
						type="button"
						class="shop-archive__filters-close btn btn--icon"
						data-shanelle-filters-close
						aria-label="<?php esc_attr_e( 'Cerrar filtros', 'shanelle' ); ?>"
					>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<div class="shop-archive__filters-body" data-shanelle-filters-content>
					<?php self::render_filters_content(); ?>
				</div>

				<div class="shop-archive__filters-footer">
					<button type="button" class="btn btn--primary shop-archive__filters-apply" data-shanelle-filters-apply>
						<?php esc_html_e( 'Aplicar filtros', 'shanelle' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render CatalogFilters in the mobile sheet (sole filter source of truth).
	 *
	 * Optional UI may append via `shanelle_shop_archive_filters_after`.
	 * Widget sidebars are not used for PLP filters.
	 */
	public static function render_filters_content(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			?>
			<p class="shop-archive__filters-empty text-body-sm text-muted">
				<?php esc_html_e( 'Activa WooCommerce para usar los filtros del catálogo.', 'shanelle' ); ?>
			</p>
			<?php
			return;
		}

		CatalogFilters::render(
			array(
				'form_id'     => 'catalog-filters-mobile',
				'submit_mode' => 'apply',
			)
		);

		/**
		 * Optional UI extensions below CatalogFilters (must not replace it).
		 */
		do_action( 'shanelle_shop_archive_filters_after' );
	}

	/**
	 * Output WooCommerce storefront notices.
	 */
	public static function render_notices(): void {
		if ( function_exists( 'woocommerce_output_all_notices' ) ) {
			woocommerce_output_all_notices();
		}
	}

	/**
	 * Return archive page title.
	 */
	public static function get_archive_title(): string {
		if ( is_search() ) {
			/* translators: %s: search query */
			return sprintf( esc_html__( 'Resultados de búsqueda para "%s"', 'shanelle' ), get_search_query() );
		}

		if ( is_product_category() ) {
			return (string) single_cat_title( '', false );
		}

		if ( is_product_tag() ) {
			return (string) single_tag_title( '', false );
		}

		if ( is_product_taxonomy() ) {
			return (string) single_term_title( '', false );
		}

		return (string) woocommerce_page_title( false );
	}

	/**
	 * Return archive description when available.
	 */
	public static function get_archive_description(): string {
		if ( is_product_category() || is_product_tag() || is_product_taxonomy() ) {
			return (string) term_description();
		}

		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $shop_page_id > 0 && is_shop() ) {
			$shop_page = get_post( $shop_page_id );

			if ( $shop_page instanceof \WP_Post ) {
				return (string) apply_filters( 'the_content', $shop_page->post_content );
			}
		}

		return '';
	}

	/**
	 * Return total products in the current catalog query.
	 */
	public static function get_product_count(): int {
		global $wp_query;

		return $wp_query instanceof \WP_Query ? (int) $wp_query->found_posts : 0;
	}

	/**
	 * Determine whether the current request is a catalog archive context.
	 */
	public static function is_catalog_context(): bool {
		if ( ! shanelle_is_woocommerce_active() ) {
			return false;
		}

		if ( is_shop() || is_product_taxonomy() ) {
			return true;
		}

		// SearchPage forces product results when WooCommerce is active.
		return is_search();
	}
}
