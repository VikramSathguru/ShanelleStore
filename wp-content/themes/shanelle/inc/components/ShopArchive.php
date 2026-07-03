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
					'filtersOpen'  => __( 'Open filters', 'shanelle' ),
					'filtersClose' => __( 'Close filters', 'shanelle' ),
					'filtersTitle' => __( 'Filters', 'shanelle' ),
					'applyFilters' => __( 'Apply filters', 'shanelle' ),
					'loading'      => __( 'Updating products…', 'shanelle' ),
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
				'wrap_before' => '<nav class="shop-archive__breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'shanelle' ) . '"><ol class="shop-archive__breadcrumbs-list">',
				'wrap_after'  => '</ol></nav>',
				'before'      => '<li class="shop-archive__breadcrumbs-item">',
				'after'       => '</li>',
				'delimiter'   => '<span class="shop-archive__breadcrumbs-sep" aria-hidden="true">/</span>',
			)
		);
	}

	/**
	 * Render archive title and description.
	 */
	public static function render_header(): void {
		$title       = self::get_archive_title();
		$description = self::get_archive_description();
		$count       = self::get_product_count();
		?>
		<header class="shop-archive__header">
			<div class="shop-archive__heading">
				<h1 class="shop-archive__title text-h2"><?php echo esc_html( $title ); ?></h1>
				<?php if ( $description ) : ?>
					<div class="shop-archive__description text-body-sm text-secondary"><?php echo wp_kses_post( $description ); ?></div>
				<?php endif; ?>
			</div>
			<p class="shop-archive__count text-caption">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of products */
						_n( '%d product', '%d products', $count, 'shanelle' ),
						$count
					)
				);
				?>
			</p>
		</header>
		<?php
	}

	/**
	 * Render catalog toolbar with filter trigger and ordering.
	 */
	public static function render_toolbar(): void {
		?>
		<div class="shop-archive__toolbar">
			<button
				type="button"
				class="btn btn--outline shop-archive__filter-toggle"
				data-shanelle-filter-open
				aria-expanded="false"
				aria-controls="shop-archive-filters"
			>
				<?php esc_html_e( 'Filters', 'shanelle' ); ?>
			</button>

			<div class="shop-archive__ordering">
				<?php self::render_ordering(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render WooCommerce catalog ordering dropdown.
	 */
	public static function render_ordering(): void {
		if ( ! function_exists( 'woocommerce_catalog_ordering' ) ) {
			return;
		}

		woocommerce_catalog_ordering();
	}

	/**
	 * Render loading placeholder for future AJAX filtering.
	 */
	public static function render_loading_placeholder(): void {
		?>
		<div class="shop-archive__loading" data-shanelle-archive-loading hidden aria-hidden="true">
			<?php ProductGrid::render_skeleton( 8 ); ?>
		</div>
		<?php
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
				'pagination_mode' => 'pagination',
				'grid_id'         => 'shop-archive-grid',
			)
		);

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
				aria-label="<?php esc_attr_e( 'Product filters', 'shanelle' ); ?>"
				data-shanelle-filters-panel
				tabindex="-1"
			>
				<div class="shop-archive__filters-header">
					<h2 class="shop-archive__filters-title text-h5"><?php esc_html_e( 'Filters', 'shanelle' ); ?></h2>
					<button
						type="button"
						class="shop-archive__filters-close btn btn--icon"
						data-shanelle-filters-close
						aria-label="<?php esc_attr_e( 'Close filters', 'shanelle' ); ?>"
					>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<div class="shop-archive__filters-body" data-shanelle-filters-content>
					<?php self::render_filters_content(); ?>
				</div>

				<div class="shop-archive__filters-footer">
					<button type="button" class="btn btn--primary shop-archive__filters-apply" data-shanelle-filters-apply>
						<?php esc_html_e( 'Apply filters', 'shanelle' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render filter widgets and extensible hook content.
	 */
	public static function render_filters_content(): void {
		if ( is_active_sidebar( 'shop-sidebar' ) ) {
			dynamic_sidebar( 'shop-sidebar' );
			return;
		}

		/**
		 * Render custom shop archive filters when no widgets are assigned.
		 */
		do_action( 'shanelle_shop_archive_filters' );

		if ( ! has_action( 'shanelle_shop_archive_filters' ) ) {
			?>
			<p class="shop-archive__filters-empty text-body-sm text-muted">
				<?php esc_html_e( 'Assign filter widgets to the Shop Sidebar area or connect layered navigation here.', 'shanelle' ); ?>
			</p>
			<?php
		}
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
			return sprintf( esc_html__( 'Search results for "%s"', 'shanelle' ), get_search_query() );
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

		return is_search() && 'product' === get_query_var( 'post_type' );
	}
}
