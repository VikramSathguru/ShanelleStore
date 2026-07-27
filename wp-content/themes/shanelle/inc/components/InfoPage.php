<?php
/**
 * Informational page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes WordPress informational pages (Contact, FAQ, Shipping, Returns,
 * Privacy, Terms) with PageHero + editable page content.
 *
 * Body copy always comes from the WordPress editor (or shortcodes such as
 * Fluent Forms / Omnisend). No hardcoded marketing body text.
 */
final class InfoPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/info-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/info-page';

	private const ROOT_ID = 'shanelle-info-page';

	/**
	 * Active page type key for the render cycle.
	 */
	private static string $type = '';

	/**
	 * Active page post for the render cycle.
	 */
	private static ?\WP_Post $page = null;

	/**
	 * Template file map: type => relative template path.
	 *
	 * @var array<string, string>
	 */
	private const TEMPLATES = array(
		'contact'  => 'page-templates/contact.php',
		'faq'      => 'page-templates/faq.php',
		'shipping' => 'page-templates/shipping.php',
		'returns'  => 'page-templates/returns.php',
		'privacy'  => 'page-templates/privacy.php',
		'terms'    => 'page-templates/terms.php',
	);

	/**
	 * Boot informational page hooks.
	 */
	public static function boot(): void {
		add_action( 'wp', array( self::class, 'configure_page_hooks' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'shanelle_product_information_shipping', array( self::class, 'filter_product_shipping_content' ), 10, 2 );
		add_filter( 'shanelle_product_information_returns', array( self::class, 'filter_product_returns_content' ), 10, 2 );
	}

	/**
	 * Hide the default loop title on informational templates.
	 */
	public static function configure_page_hooks(): void {
		if ( ! self::is_info_page() ) {
			return;
		}

		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress page title in the main query.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! self::is_info_page() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		return '';
	}

	/**
	 * Enqueue informational page assets when viewing an info template.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_info_page() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue informational page assets.
	 */
	private static function register_assets(): void {
		if ( ! wp_style_is( 'shanelle-page-hero', 'enqueued' ) ) {
			PageHero::enqueue_assets();
		}

		if ( wp_style_is( 'shanelle-info-page', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-info-page',
			self::COMPONENT_URI . '/info-page.css',
			array( 'shanelle-main', 'shanelle-page-hero' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render an informational page composition.
	 *
	 * @param string $type One of: contact, faq, shipping, returns, privacy, terms.
	 */
	public static function render( string $type ): void {
		$type = sanitize_key( $type );

		if ( ! isset( self::TEMPLATES[ $type ] ) ) {
			return;
		}

		$page = get_queried_object();

		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type ) {
			return;
		}

		self::$type = $type;
		self::$page = $page;

		self::register_assets();

		require self::COMPONENT_DIR . '/info-page.php';

		self::$type = '';
		self::$page = null;
	}

	/**
	 * Whether the current request uses an informational page template.
	 */
	public static function is_info_page(): bool {
		foreach ( self::TEMPLATES as $template ) {
			if ( is_page_template( $template ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the active page type key.
	 */
	public static function get_type(): string {
		return self::$type;
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		$type = self::$type;

		return '' !== $type ? self::ROOT_ID . '-' . $type : self::ROOT_ID;
	}

	/**
	 * Return heading ID used by PageHero (or Contact title when hero is omitted).
	 */
	public static function get_heading_id(): string {
		if ( 'contact' === self::$type ) {
			return self::get_root_id() . '-heading';
		}

		return 'shanelle-page-hero-heading';
	}

	/**
	 * Return CSS classes for the main element.
	 *
	 * @return array<int, string>
	 */
	public static function get_root_classes(): array {
		$classes = array( 'site-main', 'info-page' );

		if ( '' !== self::$type ) {
			$classes[] = 'info-page--' . sanitize_html_class( self::$type );
		}

		if ( 'contact' === self::$type ) {
			$classes[] = 'info-page--no-hero';
		}

		return $classes;
	}

	/**
	 * Render PageHero from the current page (title, excerpt, featured image, breadcrumbs).
	 *
	 * Contact pages omit the hero banner; they use a compact title instead.
	 */
	public static function render_hero(): void {
		if ( 'contact' === self::$type ) {
			self::render_contact_title();
			return;
		}

		$page = self::$page;

		if ( ! $page instanceof \WP_Post ) {
			return;
		}

		$title = get_the_title( $page );

		if ( '' === $title ) {
			$title = __( 'Página', 'shanelle' );
		}

		$subtitle = has_excerpt( $page ) ? get_the_excerpt( $page ) : '';
		$thumb_id = (int) get_post_thumbnail_id( $page );

		$args = array(
			'title'            => $title,
			'subtitle'         => is_string( $subtitle ) ? $subtitle : '',
			'breadcrumb'       => self::build_breadcrumb( $title ),
			'background_image' => $thumb_id > 0 ? $thumb_id : 0,
			'heading_level'    => 1,
			'class'            => 'info-page__hero',
			'id'               => 'shanelle-page-hero',
		);

		/**
		 * Filter informational page hero arguments before render.
		 *
		 * @param array<string, mixed> $args Hero args.
		 * @param string               $type Page type key.
		 * @param \WP_Post             $page Current page.
		 */
		$args = apply_filters( 'shanelle_info_page_hero_args', $args, self::$type, $page );

		shanelle_page_hero( is_array( $args ) ? $args : array() );
	}

	/**
	 * Render a compact Contact page title (no banner / PageHero).
	 */
	public static function render_contact_title(): void {
		$page = self::$page;

		if ( ! $page instanceof \WP_Post ) {
			return;
		}

		$title = get_the_title( $page );

		if ( '' === $title ) {
			$title = __( 'Contacto', 'shanelle' );
		}
		?>
		<header class="info-page__title-band">
			<div class="container info-page__title-inner">
				<?php self::render_inline_breadcrumb( $title ); ?>
				<h1 id="<?php echo esc_attr( self::get_heading_id() ); ?>" class="info-page__page-title text-h2">
					<?php echo esc_html( $title ); ?>
				</h1>
			</div>
		</header>
		<?php
	}

	/**
	 * Render a compact breadcrumb trail (shared with Contact title band).
	 */
	private static function render_inline_breadcrumb( string $current_title ): void {
		$items = self::build_breadcrumb( $current_title );

		if ( empty( $items ) ) {
			return;
		}
		?>
		<nav class="info-page__breadcrumb" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'shanelle' ); ?>">
			<ol class="info-page__breadcrumb-list">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$label   = (string) ( $item['label'] ?? '' );
					$url     = (string) ( $item['url'] ?? '' );
					$is_last = (int) $index === count( $items ) - 1;

					if ( '' === $label ) {
						continue;
					}
					?>
					<li class="info-page__breadcrumb-item">
						<?php if ( ! $is_last && '' !== $url ) : ?>
							<a class="info-page__breadcrumb-link" href="<?php echo esc_url( $url ); ?>">
								<?php echo esc_html( $label ); ?>
							</a>
						<?php else : ?>
							<span class="info-page__breadcrumb-current"<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $label ); ?>
							</span>
						<?php endif; ?>

						<?php if ( ! $is_last ) : ?>
							<span class="info-page__breadcrumb-sep" aria-hidden="true">/</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}

	/**
	 * Render the main body region (generic content or Contact layout).
	 */
	public static function render_body(): void {
		if ( 'contact' === self::$type ) {
			self::render_contact_body();
			return;
		}

		self::render_content();
	}

	/**
	 * Render Contact page: Customizer business details + optional map + WP content (form).
	 */
	public static function render_contact_body(): void {
		$contact      = Footer::get_business_contact();
		$has_details  = ! empty( $contact['has_details'] );
		$has_map      = ! empty( $contact['has_map'] );
		$has_aside    = $has_details || $has_map;
		$page         = self::$page;
		$has_content  = $page instanceof \WP_Post && '' !== trim( (string) $page->post_content );

		if ( ! $has_aside && ! $has_content ) {
			return;
		}
		?>
		<div class="container info-page__body info-page__body--contact">
			<div class="info-page__contact<?php echo $has_aside && $has_content ? ' info-page__contact--split' : ''; ?>">
				<?php if ( $has_aside ) : ?>
					<aside class="info-page__contact-aside" aria-label="<?php esc_attr_e( 'Datos de contacto', 'shanelle' ); ?>">
						<?php if ( $has_details ) : ?>
							<?php self::render_contact_details( $contact ); ?>
						<?php endif; ?>

						<?php if ( $has_map ) : ?>
							<?php self::render_contact_map( (string) $contact['maps_embed'] ); ?>
						<?php endif; ?>
					</aside>
				<?php endif; ?>

				<?php if ( $has_content ) : ?>
					<div class="info-page__contact-main">
						<div class="info-page__content entry-content">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core the_content pipeline.
							echo apply_filters( 'the_content', (string) $page->post_content );
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Customizer-driven contact detail list for the Contact page.
	 *
	 * @param array<string, mixed> $contact Normalized business contact.
	 */
	public static function render_contact_details( array $contact ): void {
		$title = trim( (string) ( $contact['title'] ?? '' ) );
		$items = array();

		$phone = trim( (string) ( $contact['phone'] ?? '' ) );
		if ( '' !== $phone ) {
			$items[] = array(
				'type'  => 'phone',
				'label' => __( 'Teléfono', 'shanelle' ),
				'value' => $phone,
				'href'  => (string) ( $contact['phone_url'] ?? '' ),
			);
		}

		$email = trim( (string) ( $contact['email'] ?? '' ) );
		if ( '' !== $email ) {
			$items[] = array(
				'type'  => 'email',
				'label' => __( 'Correo electrónico', 'shanelle' ),
				'value' => $email,
				'href'  => (string) ( $contact['email_url'] ?? '' ),
			);
		}

		$whatsapp = trim( (string) ( $contact['whatsapp'] ?? '' ) );
		$wa_url   = (string) ( $contact['whatsapp_url'] ?? '' );
		if ( '' !== $whatsapp && '' !== $wa_url ) {
			$items[] = array(
				'type'  => 'whatsapp',
				'label' => __( 'WhatsApp', 'shanelle' ),
				'value' => $whatsapp,
				'href'  => $wa_url,
			);
		}

		$address = trim( (string) ( $contact['address'] ?? '' ) );
		if ( '' !== $address ) {
			$items[] = array(
				'type'  => 'location',
				'label' => __( 'Dirección', 'shanelle' ),
				'value' => $address,
				'href'  => '',
			);
		}

		$hours_lines = is_array( $contact['hours_lines'] ?? null ) ? $contact['hours_lines'] : array();
		if ( ! empty( $hours_lines ) ) {
			$items[] = array(
				'type'  => 'hours',
				'label' => __( 'Horario de atención', 'shanelle' ),
				'value' => implode( "\n", $hours_lines ),
				'href'  => '',
			);
		}

		if ( empty( $items ) && '' === $title ) {
			return;
		}

		$title_id = self::get_root_id() . '-contact-title';
		?>
		<section
			class="info-page__contact-details"
			<?php echo '' !== $title ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>
		>
			<?php if ( '' !== $title ) : ?>
				<h2 id="<?php echo esc_attr( $title_id ); ?>" class="info-page__contact-heading text-h3">
					<?php echo esc_html( $title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<ul class="info-page__contact-list" role="list">
					<?php foreach ( $items as $item ) : ?>
						<li class="info-page__contact-item">
							<span class="info-page__contact-icon" aria-hidden="true">
								<?php Footer::render_icon( (string) $item['type'] ); ?>
							</span>
							<div class="info-page__contact-copy">
								<p class="info-page__contact-label text-caption text-muted"><?php echo esc_html( (string) $item['label'] ); ?></p>
								<?php if ( '' !== (string) $item['href'] ) : ?>
									<a
										class="info-page__contact-link"
										href="<?php echo esc_url( (string) $item['href'] ); ?>"
										<?php echo 'whatsapp' === (string) $item['type'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
									>
										<?php echo nl2br( esc_html( (string) $item['value'] ), false ); ?>
									</a>
								<?php else : ?>
									<p class="info-page__contact-value"><?php echo nl2br( esc_html( (string) $item['value'] ), false ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Render Google Maps iframe only when a sanitized embed URL is configured.
	 */
	public static function render_contact_map( string $embed_src ): void {
		$embed_src = trim( $embed_src );

		if ( '' === $embed_src || ! Footer::is_allowed_maps_embed_url( $embed_src ) ) {
			return;
		}
		?>
		<figure class="info-page__map">
			<iframe
				class="info-page__map-frame"
				src="<?php echo esc_url( $embed_src ); ?>"
				title="<?php echo esc_attr__( 'Ubicación en Google Maps', 'shanelle' ); ?>"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allowfullscreen
			></iframe>
		</figure>
		<?php
	}

	/**
	 * Render the WordPress page content (editor + shortcodes).
	 */
	public static function render_content(): void {
		$page = self::$page;

		if ( ! $page instanceof \WP_Post ) {
			return;
		}

		$content = $page->post_content;

		if ( '' === trim( (string) $content ) ) {
			return;
		}
		?>
		<div class="container info-page__body">
			<div class="info-page__content entry-content">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core the_content pipeline.
				echo apply_filters( 'the_content', $content );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Build Home → current page breadcrumb trail.
	 *
	 * @return array<int, array{label:string, url:string}>
	 */
	private static function build_breadcrumb( string $current_title ): array {
		$items = array(
			array(
				'label' => __( 'Inicio', 'shanelle' ),
				'url'   => home_url( '/' ),
			),
			array(
				'label' => $current_title,
				'url'   => '',
			),
		);

		/**
		 * Filter informational page breadcrumb items.
		 *
		 * @param array<int, array{label:string, url:string}> $items Breadcrumb items.
		 * @param string                                       $type  Page type key.
		 */
		$filtered = apply_filters( 'shanelle_info_page_breadcrumb', $items, self::$type );

		return is_array( $filtered ) ? $filtered : $items;
	}

	/**
	 * Return the relative template path for a type key.
	 */
	public static function get_template_for_type( string $type ): string {
		$type = sanitize_key( $type );

		return self::TEMPLATES[ $type ] ?? '';
	}

	/**
	 * Return all informational template paths keyed by type.
	 *
	 * @return array<string, string>
	 */
	public static function get_templates(): array {
		return self::TEMPLATES;
	}

	/**
	 * Find the first published page using a given informational template.
	 */
	public static function get_page_by_type( string $type ): ?\WP_Post {
		$template = self::get_template_for_type( $type );

		if ( '' === $template ) {
			return null;
		}

		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'meta_key'       => '_wp_page_template',
				'meta_value'     => $template,
				'no_found_rows'  => true,
			)
		);

		if ( empty( $pages ) || ! $pages[0] instanceof \WP_Post ) {
			return null;
		}

		return $pages[0];
	}

	/**
	 * Return the permalink for an informational page type, or empty string.
	 */
	public static function get_page_url( string $type ): string {
		$page = self::get_page_by_type( $type );

		if ( ! $page instanceof \WP_Post ) {
			return '';
		}

		$url = get_permalink( $page );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Build PDP accordion HTML from an informational page (teaser + link).
	 *
	 * Uses page excerpt when set; otherwise a trimmed content preview.
	 * Never invents policy text.
	 */
	public static function get_product_panel_html( string $type ): string {
		$page = self::get_page_by_type( $type );

		if ( ! $page instanceof \WP_Post ) {
			return '';
		}

		$url = get_permalink( $page );

		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}

		if ( has_excerpt( $page ) ) {
			$teaser = wp_kses_post( wpautop( get_the_excerpt( $page ) ) );
		} else {
			$raw = wp_strip_all_tags( (string) $page->post_content );

			if ( '' === trim( $raw ) ) {
				return '';
			}

			$teaser = wp_kses_post( wpautop( wp_trim_words( $raw, 55, '…' ) ) );
		}

		$link = sprintf(
			'<p class="info-page__pdp-link"><a href="%1$s">%2$s</a></p>',
			esc_url( $url ),
			esc_html__( 'Ver política completa', 'shanelle' )
		);

		return $teaser . $link;
	}

	/**
	 * Feed PDP shipping accordion from the Shipping info page when empty.
	 *
	 * @param string      $content Existing filter content.
	 * @param \WC_Product $product Product instance.
	 */
	public static function filter_product_shipping_content( string $content, $product ): string {
		unset( $product );

		if ( '' !== trim( $content ) ) {
			return $content;
		}

		return self::get_product_panel_html( 'shipping' );
	}

	/**
	 * Feed PDP returns accordion from the Returns info page when empty.
	 *
	 * @param string      $content Existing filter content.
	 * @param \WC_Product $product Product instance.
	 */
	public static function filter_product_returns_content( string $content, $product ): string {
		unset( $product );

		if ( '' !== trim( $content ) ) {
			return $content;
		}

		return self::get_product_panel_html( 'returns' );
	}
}
