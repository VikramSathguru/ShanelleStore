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

	private const MOD_MOBILE_BOTTOM_NAV = 'shanelle_my_account_page_mobile_bottom_nav';

	private const MOD_QUICK_ACTIONS = 'shanelle_my_account_page_quick_actions';

	private const RECENT_ORDERS_LIMIT = 3;

	private const MOBILE_BOTTOM_NAV_ENDPOINTS = array(
		'dashboard',
		'orders',
		'downloads',
		'edit-address',
		'edit-account',
	);

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

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_MOBILE_BOTTOM_NAV,
			__( 'Enable mobile bottom account navigation', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_QUICK_ACTIONS,
			__( 'Show dashboard quick actions', 'shanelle' ),
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
					'contentReady'    => __( 'Account content loaded', 'shanelle' ),
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
	 * Render dashboard endpoint content.
	 */
	public static function render_dashboard(): void {
		$user = wp_get_current_user();

		self::load_partial(
			'dashboard',
			array(
				'user'           => $user,
				'quick_actions'  => self::show_quick_actions() ? self::get_quick_actions() : array(),
				'recent_orders'  => self::get_recent_orders( self::RECENT_ORDERS_LIMIT ),
				'orders_url'     => wc_get_account_endpoint_url( 'orders' ),
			)
		);
	}

	/**
	 * Render orders endpoint content.
	 *
	 * @param int|string $current_page Current pagination page.
	 */
	public static function render_orders( int|string $current_page = 1 ): void {
		$current_page    = empty( $current_page ) ? 1 : absint( $current_page );
		$customer_orders = self::query_customer_orders( $current_page );
		$orders          = array();

		foreach ( $customer_orders->orders as $customer_order ) {
			$order = wc_get_order( $customer_order );

			if ( $order instanceof \WC_Order ) {
				$orders[] = self::normalize_order( $order );
			}
		}

		self::load_partial(
			'orders',
			array(
				'orders'          => $orders,
				'has_orders'      => 0 < $customer_orders->total,
				'current_page'    => $current_page,
				'max_num_pages'   => (int) $customer_orders->max_num_pages,
				'wp_button_class' => wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '',
			)
		);
	}

	/**
	 * Render single order detail endpoint content.
	 *
	 * @param int|string $order_id Order ID.
	 */
	public static function render_view_order( int|string $order_id ): void {
		$order_id = absint( $order_id );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order || ! current_user_can( 'view_order', $order_id ) ) {
			wc_print_notice(
				esc_html__( 'Invalid order.', 'woocommerce' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'woocommerce' ) . '</a>',
				'error'
			);
			return;
		}

		self::load_partial(
			'view-order',
			array(
				'order'    => self::normalize_order( $order ),
				'order_id' => $order_id,
				'notes'    => $order->get_customer_order_notes(),
			)
		);
	}

	/**
	 * Render downloads endpoint content.
	 */
	public static function render_downloads(): void {
		$downloads = self::get_download_items();

		self::load_partial(
			'downloads',
			array(
				'downloads'     => $downloads,
				'has_downloads' => ! empty( $downloads ),
			)
		);
	}

	/**
	 * Render saved addresses overview.
	 */
	public static function render_addresses(): void {
		self::load_partial(
			'addresses',
			array(
				'address_cards' => self::get_address_cards(),
				'description'   => apply_filters(
					'woocommerce_my_account_my_address_description',
					esc_html__( 'The following addresses will be used on the checkout page by default.', 'woocommerce' )
				),
			)
		);
	}

	/**
	 * Render edit account form using WooCommerce core template.
	 */
	public static function render_edit_account(): void {
		self::load_partial( 'endpoint-shell', array( 'modifier' => 'edit-account' ) );

		self::render_wc_core_template(
			'myaccount/form-edit-account.php',
			array(
				'user' => get_user_by( 'id', get_current_user_id() ),
			)
		);

		self::load_partial( 'endpoint-shell-close' );
	}

	/**
	 * Render edit address form using WooCommerce core template.
	 *
	 * @param string               $load_address Address type slug.
	 * @param array<string, mixed> $address      Address field definitions.
	 */
	public static function render_edit_address_form( string $load_address, array $address ): void {
		self::load_partial(
			'endpoint-shell',
			array(
				'modifier' => 'edit-address',
			)
		);

		self::render_wc_core_template(
			'myaccount/form-edit-address.php',
			array(
				'load_address' => $load_address,
				'address'      => $address,
			)
		);

		self::load_partial( 'endpoint-shell-close' );
	}

	/**
	 * Render payment methods endpoint content.
	 */
	public static function render_payment_methods(): void {
		$methods = self::get_payment_method_items();

		self::load_partial(
			'payment-methods',
			array(
				'methods'       => $methods,
				'has_methods'   => ! empty( $methods ),
				'add_url'       => wc_get_endpoint_url( 'add-payment-method' ),
				'show_add_link' => (bool) WC()->payment_gateways->get_available_payment_gateways(),
			)
		);
	}

	/**
	 * Render quick action links from prepared data.
	 *
	 * @param array<int, array<string, string>> $actions Quick action items.
	 */
	public static function render_quick_actions_block( array $actions ): void {
		if ( empty( $actions ) ) {
			return;
		}

		self::load_partial(
			'quick-actions',
			array(
				'actions' => $actions,
			)
		);
	}

	/**
	 * Render dashboard quick actions.
	 */
	public static function render_quick_actions(): void {
		if ( ! self::show_quick_actions() || ! is_user_logged_in() ) {
			return;
		}

		self::load_partial(
			'quick-actions',
			array(
				'actions' => self::get_quick_actions(),
			)
		);
	}

	/**
	 * Render mobile bottom navigation.
	 */
	public static function render_mobile_bottom_nav(): void {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		if ( empty( $settings['mobile_bottom_nav'] ) || ! is_user_logged_in() ) {
			return;
		}

		self::load_partial(
			'mobile-bottom-nav',
			array(
				'items' => self::get_mobile_bottom_nav_items(),
			)
		);
	}

	/**
	 * Render themed empty state.
	 *
	 * @param array<string, mixed> $args Empty state arguments.
	 */
	public static function render_empty_state( array $args ): void {
		self::load_partial( 'empty-state', $args );
	}

	/**
	 * Render loading skeleton placeholders.
	 *
	 * @param string $context Skeleton context slug.
	 * @param int    $count   Number of placeholders.
	 */
	public static function render_loading_skeleton( string $context = 'orders', int $count = 3 ): void {
		self::load_partial(
			'loading-skeleton',
			array(
				'context' => $context,
				'count'   => max( 1, min( 6, $count ) ),
			)
		);
	}

	/**
	 * Render a normalized order card.
	 *
	 * @param array<string, mixed> $order Normalized order data.
	 */
	public static function render_order_card( array $order ): void {
		self::load_partial( 'order-card', array( 'order' => $order ) );
	}

	/**
	 * Render a normalized address card.
	 *
	 * @param array<string, mixed> $address Normalized address data.
	 */
	public static function render_address_card( array $address ): void {
		self::load_partial( 'address-card', array( 'address' => $address ) );
	}

	/**
	 * Whether dashboard quick actions should render.
	 */
	public static function show_quick_actions(): bool {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return ! empty( $settings['quick_actions'] );
	}

	/**
	 * Return quick action links for the dashboard.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_quick_actions(): array {
		$shop_url = wc_get_page_permalink( 'shop' ) ?: home_url( '/' );

		return apply_filters(
			'shanelle_my_account_page_quick_actions',
			array(
				array(
					'label'    => __( 'View orders', 'shanelle' ),
					'url'      => wc_get_account_endpoint_url( 'orders' ),
					'endpoint' => 'orders',
				),
				array(
					'label'    => __( 'Manage addresses', 'shanelle' ),
					'url'      => wc_get_account_endpoint_url( 'edit-address' ),
					'endpoint' => 'edit-address',
				),
				array(
					'label'    => __( 'Account details', 'shanelle' ),
					'url'      => wc_get_account_endpoint_url( 'edit-account' ),
					'endpoint' => 'edit-account',
				),
				array(
					'label'    => __( 'Continue shopping', 'shanelle' ),
					'url'      => $shop_url,
					'endpoint' => 'shop',
				),
			)
		);
	}

	/**
	 * Return mobile bottom navigation items.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_mobile_bottom_nav_items(): array {
		$menu_items = function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : array();
		$items      = array();

		foreach ( self::MOBILE_BOTTOM_NAV_ENDPOINTS as $endpoint ) {
			if ( 'dashboard' === $endpoint ) {
				$items[] = array(
					'endpoint' => '',
					'label'    => __( 'Dashboard', 'shanelle' ),
					'url'      => wc_get_page_permalink( 'myaccount' ) ?: home_url( '/' ),
				);
				continue;
			}

			if ( ! isset( $menu_items[ $endpoint ] ) ) {
				continue;
			}

			$items[] = array(
				'endpoint' => $endpoint,
				'label'    => (string) $menu_items[ $endpoint ],
				'url'      => wc_get_account_endpoint_url( $endpoint ),
			);
		}

		return apply_filters( 'shanelle_my_account_page_mobile_bottom_nav_items', $items );
	}

	/**
	 * Return recent customer orders for the dashboard.
	 *
	 * @param int $limit Maximum number of orders.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_recent_orders( int $limit = 3 ): array {
		$query  = self::query_customer_orders(
			1,
			array(
				'limit'    => max( 1, $limit ),
				'paginate' => false,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		$orders = array();

		if ( ! is_array( $query ) ) {
			return $orders;
		}

		foreach ( $query as $order ) {
			if ( $order instanceof \WC_Order ) {
				$orders[] = self::normalize_order( $order );
			}
		}

		return $orders;
	}

	/**
	 * Query customer orders using WooCommerce APIs.
	 *
	 * @param int                  $current_page Current page number.
	 * @param array<string, mixed> $args         Optional query overrides.
	 * @return object|array<int, mixed>
	 */
	public static function query_customer_orders( int $current_page = 1, array $args = array() ) {
		$query_args = wp_parse_args(
			$args,
			array(
				'customer' => get_current_user_id(),
				'page'     => $current_page,
				'paginate' => true,
			)
		);

		return wc_get_orders(
			apply_filters( 'woocommerce_my_account_my_orders_query', $query_args )
		);
	}

	/**
	 * Normalize a WooCommerce order for themed templates.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return array<string, mixed>
	 */
	public static function normalize_order( \WC_Order $order ): array {
		$item_count = $order->get_item_count() - $order->get_item_count_refunded();
		$created    = $order->get_date_created();

		return array(
			'id'            => $order->get_id(),
			'number'        => $order->get_order_number(),
			'status'        => $order->get_status(),
			'status_label'  => wc_get_order_status_name( $order->get_status() ),
			'status_class'  => self::get_order_status_class( $order->get_status() ),
			'date'          => $created ? wc_format_datetime( $created ) : '',
			'date_iso'      => $created ? $created->date( 'c' ) : '',
			'total_html'    => $order->get_formatted_order_total(),
			'item_count'    => $item_count,
			'items_label'   => sprintf(
				/* translators: %d: number of items */
				_n( '%d item', '%d items', $item_count, 'shanelle' ),
				$item_count
			),
			'view_url'      => $order->get_view_order_url(),
			'actions'       => wc_get_account_orders_actions( $order ),
		);
	}

	/**
	 * Return normalized address cards.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_address_cards(): array {
		$customer_id   = get_current_user_id();
		$get_addresses = self::get_account_address_types( $customer_id );
		$cards         = array();

		foreach ( $get_addresses as $name => $address_title ) {
			$formatted = wc_get_account_formatted_address( $name );

			$cards[] = array(
				'type'        => $name,
				'title'       => $address_title,
				'formatted'   => $formatted,
				'is_empty'    => '' === $formatted,
				'edit_url'    => wc_get_endpoint_url( 'edit-address', $name ),
				'edit_label'  => $formatted
					? sprintf(
						/* translators: %s: address title */
						__( 'Edit %s', 'shanelle' ),
						$address_title
					)
					: sprintf(
						/* translators: %s: address title */
						__( 'Add %s', 'shanelle' ),
						$address_title
					),
			);
		}

		return $cards;
	}

	/**
	 * Return downloadable products for the current customer.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_download_items(): array {
		if ( ! WC()->customer ) {
			return array();
		}

		$downloads = WC()->customer->get_downloadable_products();
		$items     = array();

		foreach ( $downloads as $download ) {
			$items[] = array(
				'product_name' => (string) ( $download['product_name'] ?? '' ),
				'download_url' => (string) ( $download['download_url'] ?? '' ),
				'downloads_remaining' => $download['downloads_remaining'] ?? '',
				'access_expires' => ! empty( $download['access_expires'] )
					? date_i18n( get_option( 'date_format' ), strtotime( (string) $download['access_expires'] ) )
					: __( 'Never', 'shanelle' ),
				'file' => (string) ( $download['download_name'] ?? $download['product_name'] ?? '' ),
			);
		}

		return $items;
	}

	/**
	 * Return saved payment methods for the current customer.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_payment_method_items(): array {
		$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
		$items         = array();

		foreach ( $saved_methods as $methods ) {
			foreach ( $methods as $method ) {
				$label = '';

				if ( ! empty( $method['method']['last4'] ) ) {
					$label = sprintf(
						/* translators: 1: credit card type 2: last 4 digits */
						__( '%1$s ending in %2$s', 'woocommerce' ),
						wc_get_credit_card_type_label( $method['method']['brand'] ),
						$method['method']['last4']
					);
				} else {
					$label = wc_get_credit_card_type_label( $method['method']['brand'] );
				}

				$items[] = array(
					'label'       => $label,
					'expires'     => (string) ( $method['expires'] ?? '' ),
					'is_default'  => ! empty( $method['is_default'] ),
					'actions'     => $method['actions'] ?? array(),
				);
			}
		}

		return $items;
	}

	/**
	 * Return CSS modifier class for an order status.
	 */
	public static function get_order_status_class( string $status ): string {
		return sanitize_html_class( 'status-' . $status );
	}

	/**
	 * Return whether an endpoint is currently active.
	 */
	public static function is_active_endpoint( string $endpoint ): bool {
		if ( 'shop' === $endpoint ) {
			return false;
		}

		if ( '' === $endpoint ) {
			return function_exists( 'wc_is_current_account_menu_item' )
				? wc_is_current_account_menu_item( 'dashboard' )
				: '' === ( self::$state['endpoint'] ?? '' );
		}

		return function_exists( 'wc_is_current_account_menu_item' )
			? wc_is_current_account_menu_item( $endpoint )
			: ( self::$state['endpoint'] ?? '' ) === $endpoint;
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
				'quick_actions' => self::get_quick_actions(),
				'mobile_nav'    => self::get_mobile_bottom_nav_items(),
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
				'show_welcome'       => self::get_theme_mod_bool( self::MOD_SHOW_WELCOME, true ),
				'show_shop_link'     => self::get_theme_mod_bool( self::MOD_SHOW_SHOP_LINK, true ),
				'mobile_nav'         => self::get_theme_mod_bool( self::MOD_MOBILE_NAV, true ),
				'mobile_bottom_nav'  => self::get_theme_mod_bool( self::MOD_MOBILE_BOTTOM_NAV, true ),
				'quick_actions'      => self::get_theme_mod_bool( self::MOD_QUICK_ACTIONS, true ),
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
	 * Load a markup-only component partial.
	 *
	 * @param string               $partial Partial filename without extension.
	 * @param array<string, mixed> $args    Variables for the partial scope.
	 */
	private static function load_partial( string $partial, array $args = array() ): void {
		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Scoped template variables.
			extract( $args, EXTR_SKIP );
		}

		require self::COMPONENT_DIR . '/partials/' . $partial . '.php';
	}

	/**
	 * Render a WooCommerce core template without theme override recursion.
	 *
	 * @param string               $template Relative template path.
	 * @param array<string, mixed> $args     Template arguments.
	 */
	private static function render_wc_core_template( string $template, array $args = array() ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->plugin_path() ) {
			return;
		}

		wc_get_template(
			$template,
			$args,
			'',
			WC()->plugin_path() . '/templates/'
		);
	}

	/**
	 * Return address types available for the current customer.
	 *
	 * @param int $customer_id Customer user ID.
	 * @return array<string, string>
	 */
	private static function get_account_address_types( int $customer_id ): array {
		if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
			return apply_filters(
				'woocommerce_my_account_get_addresses',
				array(
					'billing'  => __( 'Billing address', 'woocommerce' ),
					'shipping' => __( 'Shipping address', 'woocommerce' ),
				),
				$customer_id
			);
		}

		return apply_filters(
			'woocommerce_my_account_get_addresses',
			array(
				'billing' => __( 'Billing address', 'woocommerce' ),
			),
			$customer_id
		);
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
