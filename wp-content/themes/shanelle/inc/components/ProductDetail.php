<?php
/**
 * Product detail page orchestrator.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes existing product components into the single product layout.
 */
final class ProductDetail {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-detail';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-detail';

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
	 * Boot product detail hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_single_product_hooks' ), 20 );
	}

	/**
	 * Disable default WooCommerce single product output on product pages.
	 */
	public static function configure_single_product_hooks(): void {
		if ( ! is_product() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}

	/**
	 * Enqueue product detail assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_product() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue product detail assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-detail',
			self::COMPONENT_URI . '/product-detail.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-detail',
			self::COMPONENT_URI . '/product-detail.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-detail', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-detail',
			'shanelleProductDetail',
			array(
				'i18n' => array(
					'expandSection'   => __( 'Expandir sección', 'shanelle' ),
					'collapseSection' => __( 'Contraer sección', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the product detail page composition.
	 *
	 * @param array<string, mixed> $args Optional render arguments.
	 */
	public static function render( array $args = array() ): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		self::$product = $product;
		self::$args    = self::parse_args( $args );

		if ( ! wp_style_is( 'shanelle-product-detail', 'enqueued' ) ) {
			self::register_assets();
		}

		/**
		 * Fires before the product detail layout renders.
		 */
		do_action( 'woocommerce_before_single_product' );

		require self::COMPONENT_DIR . '/product-detail.php';

		/**
		 * Fires after the product detail layout renders.
		 */
		do_action( 'woocommerce_after_single_product' );

		self::reset_context();
	}

	/**
	 * Render breadcrumb navigation.
	 */
	public static function render_breadcrumbs(): void {
		if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
			return;
		}
		?>
		<nav class="product-detail__breadcrumbs" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'shanelle' ); ?>">
			<?php
			woocommerce_breadcrumb(
				array(
					'wrap_before' => '<ol class="product-detail__breadcrumbs-list">',
					'wrap_after'  => '</ol>',
					'before'      => '<li class="product-detail__breadcrumbs-item">',
					'after'       => '</li>',
					'delimiter'   => '<span class="product-detail__breadcrumbs-sep" aria-hidden="true">/</span>',
				)
			);
			?>
		</nav>
		<?php
	}

	/**
	 * Render the hero grid with gallery and commerce column.
	 */
	public static function render_hero(): void {
		?>
		<div class="product-detail__hero">
			<div class="product-detail__gallery">
				<?php self::render_gallery(); ?>
			</div>
			<div class="product-detail__commerce">
				<?php self::render_form_open(); ?>
				<?php self::render_commerce_stack(); ?>
				<?php self::render_form_close(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the product gallery component.
	 */
	public static function render_gallery(): void {
		ProductGallery::render( self::get_product() );
	}

	/**
	 * Render summary, variations, and purchase components.
	 */
	public static function render_commerce_stack(): void {
		ProductSummary::render( self::get_product() );

		if ( self::get_product()->is_type( 'variable' ) ) {
			ProductVariations::render( self::get_product() );
		}

		ProductPurchase::render( self::get_product() );
		shanelle_product_information( self::get_product() );
	}

	/**
	 * Render below-the-fold placeholder sections.
	 */
	public static function render_below_sections(): void {
		?>
		<div class="product-detail__below">
			<?php self::render_information_section(); ?>
			<?php self::render_reviews_section(); ?>
			<?php self::render_related_section(); ?>
			<?php self::render_recently_viewed_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render product information accordion placeholder.
	 */
	public static function render_information_section(): void {
		$panels = self::get_information_panels();
		?>
		<section
			class="product-detail__section product-detail__section--information"
			id="<?php echo esc_attr( self::get_section_id( 'information' ) ); ?>"
			data-shanelle-detail-section="information"
			data-shanelle-detail-hydrate
			aria-labelledby="<?php echo esc_attr( self::get_section_heading_id( 'information' ) ); ?>"
		>
			<h2 id="<?php echo esc_attr( self::get_section_heading_id( 'information' ) ); ?>" class="product-detail__section-title">
				<?php esc_html_e( 'Información del producto', 'shanelle' ); ?>
			</h2>

			<div class="product-detail__accordion" data-shanelle-detail-accordion="information">
				<?php foreach ( $panels as $index => $panel ) : ?>
					<div class="product-detail__accordion-item">
						<h3 class="product-detail__accordion-heading">
							<button
								type="button"
								class="product-detail__accordion-trigger"
								id="<?php echo esc_attr( self::get_accordion_trigger_id( (string) $panel['id'] ) ); ?>"
								aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( self::get_accordion_panel_id( (string) $panel['id'] ) ); ?>"
								data-shanelle-detail-accordion-trigger
							>
								<span><?php echo esc_html( (string) $panel['title'] ); ?></span>
								<span class="product-detail__accordion-icon" aria-hidden="true"></span>
							</button>
						</h3>
						<div
							class="product-detail__accordion-panel"
							id="<?php echo esc_attr( self::get_accordion_panel_id( (string) $panel['id'] ) ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( self::get_accordion_trigger_id( (string) $panel['id'] ) ); ?>"
							<?php echo 0 === $index ? '' : 'hidden'; ?>
							data-shanelle-detail-accordion-panel
						>
							<?php if ( ! empty( $panel['content'] ) ) : ?>
								<div class="product-detail__accordion-content">
									<?php echo wp_kses_post( (string) $panel['content'] ); ?>
								</div>
							<?php else : ?>
								<p class="product-detail__placeholder text-caption text-muted">
									<?php echo esc_html( (string) $panel['placeholder'] ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render customer reviews section.
	 */
	public static function render_reviews_section(): void {
		$product    = self::get_product();
		$summary    = self::get_review_summary( $product );
		$reviews    = self::get_reviews( $product, 6 );
		$tags       = self::get_review_filter_tags();
		$fit_rows   = self::get_fit_breakdown();
		$can_review = self::can_show_review_form( $product );
		$has_more   = (int) ( $summary['count'] ?? 0 ) > count( $reviews );

		require self::COMPONENT_DIR . '/partials/reviews-section.php';
	}

	/**
	 * Whether the themed review form should render.
	 */
	public static function can_show_review_form( \WC_Product $product ): bool {
		if ( 'yes' !== get_option( 'woocommerce_enable_reviews', 'yes' ) ) {
			return false;
		}

		return comments_open( $product->get_id() );
	}

	/**
	 * Render WooCommerce product review comment form with theme chrome.
	 */
	public static function render_review_form( \WC_Product $product ): void {
		if ( ! self::can_show_review_form( $product ) ) {
			return;
		}

		$commenter = wp_get_current_commenter();
		$required  = get_option( 'require_name_email', 1 ) ? ' aria-required="true" required' : '';

		$comment_form = array(
			'title_reply'          => __( 'Escribir una reseña', 'shanelle' ),
			'title_reply_to'       => __( 'Responder a %s', 'shanelle' ),
			'title_reply_before'   => '<h3 id="reply-title" class="product-reviews__form-title comment-reply-title">',
			'title_reply_after'    => '</h3>',
			'comment_notes_before' => '',
			'comment_notes_after'  => '',
			'label_submit'         => __( 'Enviar reseña', 'shanelle' ),
			'class_submit'         => 'btn btn--primary product-reviews__submit',
			'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s">%4$s</button>',
			'logged_in_as'         => '',
			'comment_field'        => '',
		);

		$name_email_required = (bool) get_option( 'require_name_email', 1 );
		$fields              = array(
			'author' => sprintf(
				'<p class="comment-form-author product-reviews__field"><label for="author">%1$s%2$s</label><input id="author" name="author" type="text" value="%3$s" size="30" autocomplete="name"%4$s /></p>',
				esc_html__( 'Nombre', 'shanelle' ),
				$name_email_required ? ' <span class="required" aria-hidden="true">*</span>' : '',
				esc_attr( $commenter['comment_author'] ),
				$name_email_required ? ' required aria-required="true"' : ''
			),
			'email'  => sprintf(
				'<p class="comment-form-email product-reviews__field"><label for="email">%1$s%2$s</label><input id="email" name="email" type="email" value="%3$s" size="30" autocomplete="email"%4$s /></p>',
				esc_html__( 'Correo electrónico', 'shanelle' ),
				$name_email_required ? ' <span class="required" aria-hidden="true">*</span>' : '',
				esc_attr( $commenter['comment_author_email'] ),
				$name_email_required ? ' required aria-required="true"' : ''
			),
		);

		$comment_form['fields'] = $fields;

		if ( wc_review_ratings_enabled() ) {
			$comment_form['comment_field'] = '<div class="comment-form-rating product-reviews__rating-field"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Tu calificación', 'shanelle' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required" aria-hidden="true">*</span>' : '' ) . '</label><select name="rating" id="rating"' . ( wc_review_ratings_required() ? ' required' : '' ) . ' aria-labelledby="comment-form-rating-label">
				<option value="">' . esc_html__( 'Calificación…', 'shanelle' ) . '</option>
				<option value="5">' . esc_html__( 'Perfecto', 'shanelle' ) . '</option>
				<option value="4">' . esc_html__( 'Bueno', 'shanelle' ) . '</option>
				<option value="3">' . esc_html__( 'Promedio', 'shanelle' ) . '</option>
				<option value="2">' . esc_html__( 'No está mal', 'shanelle' ) . '</option>
				<option value="1">' . esc_html__( 'Muy pobre', 'shanelle' ) . '</option>
			</select></div>';
		}

		$comment_form['comment_field'] .= sprintf(
			'<p class="comment-form-comment product-reviews__field"><label for="comment">%1$s&nbsp;<span class="required" aria-hidden="true">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required aria-required="true"></textarea></p>',
			esc_html__( 'Tu reseña', 'shanelle' )
		);

		unset( $required );

		/**
		 * Filter product review comment form args (WooCommerce-compatible).
		 *
		 * @param array<string, mixed> $comment_form Comment form args.
		 */
		$comment_form = apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form );
		?>
		<div class="product-reviews__form-wrap" id="review_form_wrapper">
			<div id="review_form" class="product-reviews__form">
				<?php comment_form( $comment_form, $product->get_id() ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Return approved product reviews.
	 *
	 * @return array<int, \WP_Comment>
	 */
	public static function get_reviews( \WC_Product $product, int $limit = 6 ): array {
		$comments = get_comments(
			array(
				'post_id' => $product->get_id(),
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => max( 1, $limit ),
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);

		return is_array( $comments ) ? $comments : array();
	}

	/**
	 * Return review summary data.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_review_summary( \WC_Product $product ): array {
		$average = (float) $product->get_average_rating();
		$count   = (int) $product->get_review_count();

		return array(
			'average'      => $average,
			'count'        => $count,
			'display'      => $count > 0 ? number_format_i18n( $average, 2 ) : '0.00',
			'stars'        => $count > 0 ? wc_get_rating_html( $average, $count ) : '',
			'has_reviews'  => $count > 0,
		);
	}

	/**
	 * Return mapped review filter tags when real meta is available.
	 *
	 * Defaults to empty so unused placeholder chips are never shown.
	 * Populate via `shanelle_product_review_filter_tags` when review meta exists.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_review_filter_tags(): array {
		$tags = apply_filters( 'shanelle_product_review_filter_tags', array() );

		return is_array( $tags ) ? array_values( $tags ) : array();
	}

	/**
	 * Return fit breakdown rows for the reviews summary when real meta is available.
	 *
	 * Defaults to empty so stub percentages are never shown as customer data.
	 * Populate via `shanelle_product_review_fit_breakdown` when fit meta exists.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_fit_breakdown(): array {
		$rows = apply_filters( 'shanelle_product_review_fit_breakdown', array() );

		return is_array( $rows ) ? array_values( $rows ) : array();
	}

	/**
	 * Render related products placeholder section.
	 */
	public static function render_related_section(): void {
		?>
		<section
			class="product-detail__section product-detail__section--related"
			id="<?php echo esc_attr( self::get_section_id( 'related' ) ); ?>"
			data-shanelle-detail-section="related"
			data-shanelle-detail-hydrate
			data-related-product-id="<?php echo esc_attr( (string) self::get_product()->get_id() ); ?>"
			aria-labelledby="<?php echo esc_attr( self::get_section_heading_id( 'related' ) ); ?>"
		>
			<h2 id="<?php echo esc_attr( self::get_section_heading_id( 'related' ) ); ?>" class="product-detail__section-title">
				<?php esc_html_e( 'Productos relacionados', 'shanelle' ); ?>
			</h2>
			<div class="product-detail__placeholder-grid" data-shanelle-detail-related>
				<p class="product-detail__placeholder text-caption text-muted">
					<?php esc_html_e( 'Productos relacionados próximamente.', 'shanelle' ); ?>
				</p>
			</div>
		</section>
		<?php
	}

	/**
	 * Render recently viewed placeholder section.
	 */
	public static function render_recently_viewed_section(): void {
		?>
		<section
			class="product-detail__section product-detail__section--recently-viewed"
			id="<?php echo esc_attr( self::get_section_id( 'recently-viewed' ) ); ?>"
			data-shanelle-detail-section="recently-viewed"
			data-shanelle-detail-hydrate
			data-product-id="<?php echo esc_attr( (string) self::get_product()->get_id() ); ?>"
			aria-labelledby="<?php echo esc_attr( self::get_section_heading_id( 'recently-viewed' ) ); ?>"
		>
			<h2 id="<?php echo esc_attr( self::get_section_heading_id( 'recently-viewed' ) ); ?>" class="product-detail__section-title">
				<?php esc_html_e( 'Vistos recientemente', 'shanelle' ); ?>
			</h2>
			<div class="product-detail__placeholder-grid" data-shanelle-detail-recently-viewed>
				<p class="product-detail__placeholder text-caption text-muted">
					<?php esc_html_e( 'Productos vistos recientemente próximamente.', 'shanelle' ); ?>
				</p>
			</div>
		</section>
		<?php
	}

	/**
	 * Open WooCommerce cart or variation form.
	 */
	public static function render_form_open(): void {
		$product = self::get_product();

		if ( $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable ) {
			self::render_variable_form_open( $product );
			return;
		}

		self::render_simple_form_open( $product );
	}

	/**
	 * Close commerce form.
	 */
	public static function render_form_close(): void {
		echo '</form>';
	}

	/**
	 * Return product detail root ID.
	 */
	public static function get_root_id(): string {
		return 'product-detail-' . self::$args['detail_id'];
	}

	/**
	 * Return hydration JSON for client bootstrapping.
	 */
	public static function get_detail_json(): string {
		$product = self::get_product();

		$data = array(
			'productId'   => $product->get_id(),
			'productType' => $product->get_type(),
			'sections'    => array(
				'information',
				'reviews',
				'related',
				'recently-viewed',
			),
		);

		return wp_json_encode( $data ) ?: '{}';
	}

	/**
	 * Return section element ID.
	 */
	public static function get_section_id( string $slug ): string {
		return self::get_root_id() . '-section-' . sanitize_title( $slug );
	}

	/**
	 * Return section heading ID.
	 */
	public static function get_section_heading_id( string $slug ): string {
		return self::get_section_id( $slug ) . '-heading';
	}

	/**
	 * Return accordion trigger ID.
	 */
	public static function get_accordion_trigger_id( string $panel_id ): string {
		return self::get_root_id() . '-accordion-trigger-' . sanitize_title( $panel_id );
	}

	/**
	 * Return accordion panel ID.
	 */
	public static function get_accordion_panel_id( string $panel_id ): string {
		return self::get_root_id() . '-accordion-panel-' . sanitize_title( $panel_id );
	}

	/**
	 * Open variable product form using WooCommerce variation data.
	 */
	private static function render_variable_form_open( \WC_Product_Variable $product ): void {
		$variations_json = wp_json_encode( $product->get_available_variations() );
		$variations_attr = function_exists( 'wc_esc_json' )
			? wc_esc_json( $variations_json )
			: esc_attr( $variations_json );
		?>
		<form
			class="variations_form cart product-detail__form"
			method="post"
			enctype="multipart/form-data"
			data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
			data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
		>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
			<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
			<input type="hidden" name="variation_id" class="variation_id" value="0">
		<?php
	}

	/**
	 * Open simple product cart form.
	 */
	private static function render_simple_form_open( \WC_Product $product ): void {
		?>
		<form class="cart product-detail__form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
		<?php
	}

	/**
	 * Build accordion panel definitions for product information.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function get_information_panels(): array {
		$product     = self::get_product();
		$description = apply_filters( 'the_content', $product->get_description() );
		$description = is_string( $description ) ? trim( $description ) : '';

		return array(
			array(
				'id'          => 'description',
				'title'       => __( 'Descripción', 'shanelle' ),
				'content'     => $description,
				'placeholder' => __( 'La descripción completa del producto estará disponible pronto.', 'shanelle' ),
			),
			array(
				'id'          => 'details',
				'title'       => __( 'Detalles', 'shanelle' ),
				'content'     => '',
				'placeholder' => __( 'Los detalles del producto estarán disponibles pronto.', 'shanelle' ),
			),
			array(
				'id'          => 'shipping-returns',
				'title'       => __( 'Envío y devoluciones', 'shanelle' ),
				'content'     => '',
				'placeholder' => __( 'La información de envío y devoluciones estará disponible pronto.', 'shanelle' ),
			),
		);
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
				'detail_id' => wp_unique_id( 'detail-' ),
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$product = null;
		self::$args    = array();
	}

	/**
	 * Return active product.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductDetail render context is not set.' );
		}

		return self::$product;
	}
}
