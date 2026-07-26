<?php
/**
 * Footer brand column component.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * First footer column: logo, brand description, and social icons.
 *
 * Presentation only. Customizer ownership stays on {@see Footer}.
 */
final class FooterBrand {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/footer-brand';

	private const COMPONENT_URI = SHANELLE_URI . '/components/footer-brand';

	/**
	 * Default brand description (Spanish LATAM storefront).
	 */
	public const DEFAULT_DESCRIPTION = 'Descubre prendas cuidadosamente seleccionadas que combinan elegancia, calidad y estilo.';

	/**
	 * Social networks in display order.
	 *
	 * @var array<int, string>
	 */
	private const SOCIAL_ORDER = array(
		'instagram',
		'facebook',
		'pinterest',
		'tiktok',
		'youtube',
	);

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Boot footer brand assets.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue footer brand styles site-wide (footer is global).
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-footer-brand',
			self::COMPONENT_URI . '/footer-brand.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render the footer brand column.
	 *
	 * @param array<string, mixed> $args Optional overrides (logo_id, description, social, show_logo, show_social).
	 */
	public static function render( array $args = array() ): void {
		self::$args = self::parse_args( $args );

		if ( ! wp_style_is( 'shanelle-footer-brand', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/footer-brand.php';

		self::$args = array();
	}

	/**
	 * Render logo link with alt text.
	 */
	public static function render_logo(): void {
		$args = self::active_args();

		if ( empty( $args['show_logo'] ) ) {
			return;
		}

		$site_name = (string) get_bloginfo( 'name', 'display' );
		$alt       = '' !== $site_name ? $site_name : 'Shanelle';
		$logo_id   = (int) ( $args['logo_id'] ?? 0 );
		$home_url  = home_url( '/' );
		?>
		<a class="footer-brand__logo" href="<?php echo esc_url( $home_url ); ?>">
			<?php
			if ( $logo_id > 0 && wp_attachment_is_image( $logo_id ) ) {
				echo wp_get_attachment_image(
					$logo_id,
					'medium',
					false,
					array(
						'class'    => 'footer-brand__logo-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => $alt,
					)
				);
			} else {
				echo esc_html( $alt );
			}
			?>
		</a>
		<?php
	}

	/**
	 * Render brand description.
	 */
	public static function render_description(): void {
		$args        = self::active_args();
		$description = (string) ( $args['description'] ?? '' );

		if ( '' === $description ) {
			return;
		}
		?>
		<p class="footer-brand__description text-body-sm text-secondary">
			<?php echo esc_html( $description ); ?>
		</p>
		<?php
	}

	/**
	 * Render horizontal social icon row.
	 */
	public static function render_social(): void {
		$args = self::active_args();

		if ( empty( $args['show_social'] ) ) {
			return;
		}

		$links = is_array( $args['social'] ?? null ) ? $args['social'] : array();

		if ( empty( $links ) ) {
			return;
		}
		?>
		<ul class="footer-brand__social" aria-label="<?php esc_attr_e( 'Redes sociales', 'shanelle' ); ?>">
			<?php foreach ( $links as $network => $url ) : ?>
				<?php
				$label          = self::get_social_label( (string) $network );
				$href           = (string) $url;
				$is_placeholder = '#' === $href || '' === $href;
				?>
				<li class="footer-brand__social-item">
					<a
						class="footer-brand__social-link"
						href="<?php echo esc_url( $is_placeholder ? '#' : $href ); ?>"
						aria-label="<?php echo esc_attr( $label ); ?>"
						<?php echo $is_placeholder ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>
					>
						<span class="footer-brand__social-icon" aria-hidden="true">
							<?php Footer::render_icon( (string) $network ); ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Return active args or defaults when rendering outside render().
	 *
	 * @return array<string, mixed>
	 */
	private static function active_args(): array {
		return ! empty( self::$args ) ? self::$args : self::get_default_args();
	}

	/**
	 * Build default brand args from Theme Customizer / site logo.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_args(): array {
		$settings = Footer::get_settings();

		$logo_id = (int) ( $settings['logo_id'] ?? 0 );

		if ( $logo_id <= 0 && has_custom_logo() ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
		}

		$description = (string) ( $settings['brand_description'] ?? '' );

		if ( '' === trim( $description ) ) {
			$description = self::DEFAULT_DESCRIPTION;
		}

		return apply_filters(
			'shanelle_footer_brand_args',
			array(
				'show_logo'   => ! empty( $settings['show_logo'] ),
				'logo_id'     => $logo_id,
				'description' => $description,
				'show_social' => ! array_key_exists( 'show_social', $settings ) || ! empty( $settings['show_social'] ),
				'social'      => self::build_social_links( $settings ),
			)
		);
	}

	/**
	 * Always return the five brand social networks; empty Customizer URLs become "#".
	 *
	 * @param array<string, mixed> $settings Footer settings.
	 * @return array<string, string>
	 */
	public static function build_social_links( array $settings ): array {
		$map = array(
			'instagram' => (string) ( $settings['social_instagram'] ?? '' ),
			'facebook'  => (string) ( $settings['social_facebook'] ?? '' ),
			'pinterest' => (string) ( $settings['social_pinterest'] ?? '' ),
			'tiktok'    => (string) ( $settings['social_tiktok'] ?? '' ),
			'youtube'   => (string) ( $settings['social_youtube'] ?? '' ),
		);

		$links = array();

		foreach ( self::SOCIAL_ORDER as $network ) {
			$url = trim( (string) ( $map[ $network ] ?? '' ) );
			$links[ $network ] = '' !== $url ? $url : '#';
		}

		return $links;
	}

	/**
	 * Accessible label for a social network.
	 */
	private static function get_social_label( string $network ): string {
		$labels = array(
			'instagram' => __( 'Instagram', 'shanelle' ),
			'facebook'  => __( 'Facebook', 'shanelle' ),
			'pinterest' => __( 'Pinterest', 'shanelle' ),
			'tiktok'    => __( 'TikTok', 'shanelle' ),
			'youtube'   => __( 'YouTube', 'shanelle' ),
		);

		return $labels[ $network ] ?? ucfirst( $network );
	}

	/**
	 * Parse render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args( $args, self::get_default_args() );
	}
}
