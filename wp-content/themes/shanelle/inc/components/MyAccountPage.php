<?php
/**
 * My Account page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the WooCommerce My Account page using theme layout and WC account APIs.
 */
final class MyAccountPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/my-account-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/my-account-page';

	private const ROOT_ID = 'shanelle-my-account-page';

	private const MOD_SHOW_WELCOME = 'shanelle_my_account_page_show_welcome';

	private const MOD_SHOW_SHOP_LINK = 'shanelle_my_account_page_show_shop_link';

	private const MOD_MOBILE_NAV = 'shanelle_my_account_page_mobile_nav';

	/**
	 * Active guest view slug.
	 */
	private static string $guest_view = '';

	/**
	 * Guest view template arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $guest_args = array();

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot My Account page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_account_hooks' ), 20 );
	}

	/**
	 * Adjust WooCommerce hooks on the account page.
	 */
	public static function configure_account_hooks(): void {
		if ( ! is_account_page() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );
		remove_action( 'woocommerce_account_content', 'woocommerce_output_all_notices', 5 );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress account page title.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! is_account_page() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		$account_page_id = wc_get_page_id( 'myaccount' );

		if ( $account_page_id > 0 && (int) $id === $account_page_id ) {
			return '';
		}

		return $title;
	}

	/**
	 * Register Theme Customizer settings for the account page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_my_account_page',
			array(
				'title'       => __( 'My Account Page', 'shanelle' ),
				'description' => __( 'Configure account page presentation.', 'shanelle' ),
				'priority'    => 172,
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_WELCOME,
			__( 'Show welcome line in account header', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_SHOP_LINK,
			__( 'Show return to shop link', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_MOBILE_NAV,
			__( 'Enable mobile account navigation toggle', 'shanelle' ),
			true
		);
	}

	/**
	 * Enqueue account page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-my-account-page',
			self::COMPONENT_URI . '/my-account-page.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-my-account-page',
			self::COMPONENT_URI . '/my-account-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-my-account-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-my-account-page',
			'shanelleMyAccountPage',
			array(
				'initialState' => self::build_page_state(),
				'i18n'         => array(
					'pageTitle'       => __( 'My account', 'shanelle' ),
					'navToggle'       => __( 'Account menu', 'shanelle' ),
					'navToggleClose'  => __( 'Close account menu', 'shanelle' ),
					'returnToShop'    => __( 'Continue shopping', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the logged-in account page composition.
	 *
	 * @param array<string, mixed> $args Template arguments from WooCommerce.
	 */
	public static function render( array $args = array() ): void {
		self::$guest_view = '';
		self::$guest_args = array();
		self::$state      = self::build_page_state( 'account', $args );

		if ( ! wp_style_is( 'shanelle-my-account-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/my-account-page.php';

		self::$state = array();
	}

	/**
	 * Render a guest account view (login, lost password, reset password).
	 *
	 * @param string               $view Guest view slug.
	 * @param array<string, mixed> $args Optional template arguments.
	 */
	public static function render_guest( string $view, array $args = array() ): void {
		self::$guest_view = $view;
		self::$guest_args = $args;
		self::$state      = self::build_page_state( $view, $args );

		if ( ! wp_style_is( 'shanelle-my-account-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/my-account-page-guest.php';

		self::$guest_view = '';
		self::$guest_args = array();
		self::$state      = array();
	}

	/**
	 * Render WooCommerce notices.
	 */
	public static function render_notices(): void {
		if ( ! function_exists( 'woocommerce_output_all_notices' ) ) {
			return;
		}

		echo '<div class="my-account-page__notices">';
		woocommerce_output_all_notices();
		echo '</div>';
	}

	/**
	 * Render account navigation panel.
	 */
	public static function render_navigation(): void {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();
		?>
		<?php if ( ! empty( $settings['mobile_nav'] ) ) : ?>
			<button
				type="button"
				class="btn btn--outline btn--sm my-account-page__nav-toggle"
				data-shanelle-account-nav-toggle
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( self::get_nav_panel_id() ); ?>"
			>
				<?php esc_html_e( 'Account menu', 'shanelle' ); ?>
			</button>
		<?php endif; ?>

		<div
			class="my-account-page__nav-panel"
			id="<?php echo esc_attr( self::get_nav_panel_id() ); ?>"
			data-shanelle-account-nav-panel
		>
			<?php do_action( 'woocommerce_account_navigation' ); ?>
		</div>
		<?php
	}

	/**
	 * Render account endpoint content.
	 */
	public static function render_content(): void {
		?>
		<div class="woocommerce-MyAccount-content my-account-page__content-inner">
			<?php do_action( 'woocommerce_account_content' ); ?>
		</div>
		<?php
	}

	/**
	 * Render guest view partial markup.
	 */
	public static function render_guest_content(): void {
		$partial = match ( self::$guest_view ) {
			'login' => 'partials/form-login.php',
			'lost-password' => 'partials/form-lost-password.php',
			'reset-password' => 'partials/form-reset-password.php',
			'lost-password-confirmation' => 'partials/lost-password-confirmation.php',
			default => '',
		};

		if ( '' === $partial ) {
			return;
		}

		$args = self::$guest_args;
		require self::COMPONENT_DIR . '/' . $partial;
	}

	/**
	 * Return the page heading for the active view.
	 */
	public static function get_page_title(): string {
		$title = (string) ( self::$state['title'] ?? '' );

		if ( '' !== $title ) {
			return $title;
		}

		return __( 'My account', 'shanelle' );
	}

	/**
	 * Return optional welcome line for logged-in users.
	 */
	public static function get_welcome_line(): string {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		if ( empty( $settings['show_welcome'] ) || ! is_user_logged_in() ) {
			return '';
		}

		$user = wp_get_current_user();

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return '';
		}

		return sprintf(
			/* translators: %s: user display name */
			__( 'Welcome back, %s', 'shanelle' ),
			$user->display_name
		);
	}

	/**
	 * Whether the return-to-shop link should render.
	 */
	public static function show_shop_link(): bool {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return ! empty( $settings['show_shop_link'] );
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return page heading ID.
	 */
	public static function get_heading_id(): string {
		return self::ROOT_ID . '-heading';
	}

	/**
	 * Return navigation panel ID.
	 */
	public static function get_nav_panel_id(): string {
		return self::ROOT_ID . '-nav-panel';
	}

	/**
	 * Return page state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return active guest view slug.
	 */
	public static function get_guest_view(): string {
		return self::$guest_view;
	}

	/**
	 * Whether the current render is a guest view.
	 */
	public static function is_guest_view(): bool {
		return '' !== self::$guest_view;
	}

	/**
	 * Build normalized account page state.
	 *
	 * @param string               $view Active view slug.
	 * @param array<string, mixed> $args Optional template arguments.
	 * @return array<string, mixed>
	 */
	public static function build_page_state( string $view = 'account', array $args = array() ): array {
		$endpoint = function_exists( 'WC' ) && WC()->query ? WC()->query->get_current_endpoint() : '';
		$user     = wp_get_current_user();

		return apply_filters(
			'shanelle_my_account_page_state',
			array(
				'view'         => $view,
				'endpoint'     => $endpoint,
				'title'        => self::resolve_page_title( $view, $endpoint ),
				'menu_items'   => function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : array(),
				'is_logged_in' => is_user_logged_in(),
				'user'         => ( $user instanceof \WP_User && $user->exists() )
					? array(
						'display_name' => $user->display_name,
						'email'        => $user->user_email,
					)
					: array(),
				'settings'     => self::get_settings(),
				'urls'         => array(
					'account' => wc_get_page_permalink( 'myaccount' ) ?: home_url( '/' ),
					'shop'    => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
					'cart'    => wc_get_cart_url(),
					'logout'  => wc_logout_url(),
				),
				'args'         => $args,
			),
			$view,
			$args
		);
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_my_account_page_settings',
			array(
				'show_welcome' => self::get_theme_mod_bool( self::MOD_SHOW_WELCOME, true ),
				'show_shop_link' => self::get_theme_mod_bool( self::MOD_SHOW_SHOP_LINK, true ),
				'mobile_nav'   => self::get_theme_mod_bool( self::MOD_MOBILE_NAV, true ),
			)
		);
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Resolve the page heading for the active view.
	 */
	private static function resolve_page_title( string $view, string $endpoint ): string {
		return match ( $view ) {
			'login' => __( 'Sign in', 'shanelle' ),
			'lost-password' => __( 'Reset password', 'shanelle' ),
			'reset-password' => __( 'Choose a new password', 'shanelle' ),
			'lost-password-confirmation' => __( 'Check your email', 'shanelle' ),
			default => self::resolve_endpoint_title( $endpoint ),
		};
	}

	/**
	 * Resolve the heading for a logged-in endpoint.
	 */
	private static function resolve_endpoint_title( string $endpoint ): string {
		if ( '' === $endpoint ) {
			return __( 'My account', 'shanelle' );
		}

		if ( function_exists( 'wc_get_endpoint_title' ) ) {
			$title = wc_get_endpoint_title( $endpoint );

			if ( is_string( $title ) && '' !== $title ) {
				return $title;
			}
		}

		$menu_items = function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : array();

		if ( isset( $menu_items[ $endpoint ] ) ) {
			return (string) $menu_items[ $endpoint ];
		}

		return __( 'My account', 'shanelle' );
	}

	/**
	 * Register a checkbox customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_checkbox_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		bool $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => array( self::class, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_my_account_page',
				'type'    => 'checkbox',
			)
		);
	}

	/**
	 * Read a sanitized boolean theme mod.
	 */
	private static function get_theme_mod_bool( string $key, bool $default ): bool {
		$value = get_theme_mod( $key, $default );

		return (bool) $value;
	}
}
