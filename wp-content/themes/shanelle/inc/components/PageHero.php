<?php
/**
 * Page hero component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Reusable informational page hero.
 *
 * Accepts title, optional subtitle, breadcrumb trail, and optional background
 * image. Used by Contact, FAQ, Shipping, Privacy, Terms, Returns, and similar
 * content pages. Not the homepage HeroBanner.
 */
final class PageHero {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/page-hero';

	private const COMPONENT_URI = SHANELLE_URI . '/components/page-hero';

	private const ROOT_ID = 'shanelle-page-hero';

	private const IMAGE_SIZE = 'large';

	/**
	 * Active render arguments for the current cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Boot page hero hooks.
	 */
	public static function boot(): void {
		// Assets enqueue on render so unused storefront routes stay lean.
	}

	/**
	 * Render the page hero.
	 *
	 * Supported args:
	 * - title (string, required)
	 * - subtitle (string, optional)
	 * - breadcrumb (array<int, array{label:string, url?:string}>, optional)
	 * - background_image (int attachment ID or string URL, optional)
	 * - background_image_alt (string, optional; used with URL or when attachment alt is empty)
	 * - heading_level (int 1–2, optional; default 1)
	 * - class (string, optional extra root classes)
	 * - id (string, optional root element id)
	 *
	 * @param array<string, mixed> $args Render arguments.
	 */
	public static function render( array $args = array() ): void {
		self::$args = self::normalize_args( $args );

		if ( '' === (string) self::$args['title'] ) {
			self::$args = array();
			return;
		}

		self::enqueue_assets();

		require self::COMPONENT_DIR . '/page-hero.php';

		self::$args = array();
	}

	/**
	 * Enqueue page hero styles.
	 */
	public static function enqueue_assets(): void {
		if ( wp_style_is( 'shanelle-page-hero', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-page-hero',
			self::COMPONENT_URI . '/page-hero.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Return normalized active args.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_args(): array {
		return self::$args;
	}

	/**
	 * Return root element id.
	 */
	public static function get_root_id(): string {
		$id = (string) ( self::$args['id'] ?? '' );

		return '' !== $id ? $id : self::ROOT_ID;
	}

	/**
	 * Return heading element id for aria-labelledby.
	 */
	public static function get_heading_id(): string {
		return self::get_root_id() . '-heading';
	}

	/**
	 * Return CSS classes for the root section.
	 *
	 * @return array<int, string>
	 */
	public static function get_root_classes(): array {
		$classes = array( 'page-hero' );

		if ( self::has_background() ) {
			$classes[] = 'page-hero--has-media';
		} else {
			$classes[] = 'page-hero--plain';
		}

		$extra = trim( (string) ( self::$args['class'] ?? '' ) );

		if ( '' !== $extra ) {
			foreach ( preg_split( '/\s+/', $extra ) ?: array() as $class ) {
				$class = sanitize_html_class( $class );

				if ( '' !== $class ) {
					$classes[] = $class;
				}
			}
		}

		return $classes;
	}

	/**
	 * Whether a background image is available.
	 */
	public static function has_background(): bool {
		$image = self::$args['background'] ?? null;

		return is_array( $image ) && '' !== (string) ( $image['src'] ?? '' );
	}

	/**
	 * Return normalized background image data.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_background(): array {
		return is_array( self::$args['background'] ?? null ) ? self::$args['background'] : array();
	}

	/**
	 * Return breadcrumb items.
	 *
	 * @return array<int, array{label:string, url:string}>
	 */
	public static function get_breadcrumb(): array {
		$items = self::$args['breadcrumb'] ?? array();

		return is_array( $items ) ? $items : array();
	}

	/**
	 * Whether breadcrumbs should render.
	 */
	public static function has_breadcrumb(): bool {
		return ! empty( self::get_breadcrumb() );
	}

	/**
	 * Return the heading tag name (h1 or h2).
	 */
	public static function get_heading_tag(): string {
		$level = (int) ( self::$args['heading_level'] ?? 1 );

		return 2 === $level ? 'h2' : 'h1';
	}

	/**
	 * Render the optional background media.
	 */
	public static function render_background(): void {
		if ( ! self::has_background() ) {
			return;
		}

		$image = self::get_background();
		$alt   = (string) ( $image['alt'] ?? '' );
		?>
		<div class="page-hero__media" aria-hidden="<?php echo '' === $alt ? 'true' : 'false'; ?>">
			<img
				class="page-hero__image"
				src="<?php echo esc_url( (string) $image['src'] ); ?>"
				<?php if ( ! empty( $image['srcset'] ) ) : ?>
					srcset="<?php echo esc_attr( (string) $image['srcset'] ); ?>"
					sizes="<?php echo esc_attr( (string) ( $image['sizes'] ?? '100vw' ) ); ?>"
				<?php endif; ?>
				alt="<?php echo esc_attr( $alt ); ?>"
				<?php if ( ! empty( $image['width'] ) ) : ?>
					width="<?php echo esc_attr( (string) $image['width'] ); ?>"
					height="<?php echo esc_attr( (string) $image['height'] ); ?>"
				<?php endif; ?>
				loading="eager"
				decoding="async"
				fetchpriority="high"
			>
			<span class="page-hero__veil" aria-hidden="true"></span>
		</div>
		<?php
	}

	/**
	 * Render breadcrumb navigation.
	 */
	public static function render_breadcrumb(): void {
		$items = self::get_breadcrumb();

		if ( empty( $items ) ) {
			return;
		}
		?>
		<nav class="page-hero__breadcrumb" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'shanelle' ); ?>">
			<ol class="page-hero__breadcrumb-list">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$label   = (string) ( $item['label'] ?? '' );
					$url     = (string) ( $item['url'] ?? '' );
					$is_last = (int) $index === count( $items ) - 1;

					if ( '' === $label ) {
						continue;
					}
					?>
					<li class="page-hero__breadcrumb-item">
						<?php if ( ! $is_last && '' !== $url ) : ?>
							<a class="page-hero__breadcrumb-link" href="<?php echo esc_url( $url ); ?>">
								<?php echo esc_html( $label ); ?>
							</a>
						<?php else : ?>
							<span class="page-hero__breadcrumb-current"<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $label ); ?>
							</span>
						<?php endif; ?>

						<?php if ( ! $is_last ) : ?>
							<span class="page-hero__breadcrumb-sep" aria-hidden="true">/</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}

	/**
	 * Normalize and sanitize render arguments.
	 *
	 * @param array<string, mixed> $args Raw args.
	 * @return array<string, mixed>
	 */
	private static function normalize_args( array $args ): array {
		$defaults = array(
			'title'                => '',
			'subtitle'             => '',
			'breadcrumb'           => array(),
			'background_image'     => 0,
			'background_image_alt' => '',
			'heading_level'        => 1,
			'class'                => '',
			'id'                   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$title = sanitize_text_field( (string) $args['title'] );
		$level = (int) $args['heading_level'];

		if ( $level < 1 || $level > 2 ) {
			$level = 1;
		}

		$normalized = array(
			'title'         => $title,
			'subtitle'      => sanitize_text_field( (string) $args['subtitle'] ),
			'breadcrumb'    => self::normalize_breadcrumb( $args['breadcrumb'] ),
			'background'    => self::normalize_background( $args['background_image'], (string) $args['background_image_alt'] ),
			'heading_level' => $level,
			'class'         => sanitize_text_field( (string) $args['class'] ),
			'id'            => sanitize_html_class( (string) $args['id'] ),
		);

		/**
		 * Filter PageHero normalized arguments before render.
		 *
		 * @param array<string, mixed> $normalized Normalized args.
		 * @param array<string, mixed> $args       Original args.
		 */
		return apply_filters( 'shanelle_page_hero_args', $normalized, $args );
	}

	/**
	 * Normalize breadcrumb items.
	 *
	 * @param mixed $breadcrumb Raw breadcrumb value.
	 * @return array<int, array{label:string, url:string}>
	 */
	private static function normalize_breadcrumb( mixed $breadcrumb ): array {
		if ( ! is_array( $breadcrumb ) || empty( $breadcrumb ) ) {
			return array();
		}

		$items = array();

		foreach ( $breadcrumb as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $item['label'] ?? $item['title'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			$items[] = array(
				'label' => $label,
				'url'   => esc_url_raw( (string) ( $item['url'] ?? '' ) ),
			);
		}

		return $items;
	}

	/**
	 * Normalize background image from attachment ID or URL.
	 *
	 * @param mixed  $source Attachment ID, URL string, or empty.
	 * @param string $alt    Optional alt override.
	 * @return array<string, mixed>
	 */
	private static function normalize_background( mixed $source, string $alt = '' ): array {
		$empty = array(
			'src'    => '',
			'srcset' => '',
			'sizes'  => '100vw',
			'alt'    => '',
			'width'  => 0,
			'height' => 0,
		);

		$alt = sanitize_text_field( $alt );

		if ( is_numeric( $source ) ) {
			$attachment_id = absint( $source );

			if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
				return $empty;
			}

			$meta = wp_get_attachment_metadata( $attachment_id );
			$meta_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

			return array(
				'src'    => (string) ( wp_get_attachment_image_url( $attachment_id, self::IMAGE_SIZE ) ?: '' ),
				'srcset' => (string) ( wp_get_attachment_image_srcset( $attachment_id, self::IMAGE_SIZE ) ?: '' ),
				'sizes'  => '100vw',
				'alt'    => '' !== $alt ? $alt : ( is_string( $meta_alt ) ? $meta_alt : '' ),
				'width'  => isset( $meta['width'] ) ? (int) $meta['width'] : 0,
				'height' => isset( $meta['height'] ) ? (int) $meta['height'] : 0,
			);
		}

		if ( is_string( $source ) ) {
			$url = esc_url_raw( $source );

			if ( '' === $url ) {
				return $empty;
			}

			return array(
				'src'    => $url,
				'srcset' => '',
				'sizes'  => '100vw',
				'alt'    => $alt,
				'width'  => 0,
				'height' => 0,
			);
		}

		return $empty;
	}
}
