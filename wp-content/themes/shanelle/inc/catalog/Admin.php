<?php
/**
 * Admin UI for collection term meta fields.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders collection term meta using the Settings API pattern.
 */
final class Admin {

	/**
	 * Registered field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $fields = array();

	/**
	 * Register admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );

		add_action( Helpers::TAXONOMY . '_add_form_fields', array( self::class, 'render_add_form_fields' ) );
		add_action( Helpers::TAXONOMY . '_edit_form_fields', array( self::class, 'render_edit_form_fields' ), 10, 2 );

		add_action( 'created_' . Helpers::TAXONOMY, array( self::class, 'save_term_meta' ) );
		add_action( 'edited_' . Helpers::TAXONOMY, array( self::class, 'save_term_meta' ) );

		add_filter( 'manage_edit-' . Helpers::TAXONOMY . '_columns', array( self::class, 'register_columns' ) );
		add_filter( 'manage_' . Helpers::TAXONOMY . '_custom_column', array( self::class, 'render_column' ), 10, 3 );
	}

	/**
	 * Register Settings API schema and term meta definitions.
	 */
	public static function register_settings(): void {
		self::$fields = self::get_field_definitions();

		register_setting(
			Helpers::SETTINGS_GROUP,
			'shanelle_collection_term_meta_schema',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings_schema' ),
				'default'           => array(),
			)
		);

		foreach ( self::$fields as $meta_key => $field ) {
			register_meta(
				'term',
				Helpers::meta_key( $meta_key ),
				array(
					'object_subtype'    => Helpers::TAXONOMY,
					'type'              => $field['type'],
					'single'            => true,
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => array( self::class, 'authorize_meta_update' ),
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Field definitions shared by Settings API and term forms.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_field_definitions(): array {
		return array(
			Helpers::META_TYPE  => array(
				'label'             => __( 'Collection Type', 'shanelle' ),
				'type'              => 'string',
				'sanitize_callback' => array( Helpers::class, 'sanitize_collection_type' ),
				'render_callback'   => array( self::class, 'render_type_field' ),
				'description'       => __( 'Defines whether this collection is seasonal, featured, or campaign-based.', 'shanelle' ),
			),
			Helpers::META_START => array(
				'label'             => __( 'Start Date', 'shanelle' ),
				'type'              => 'string',
				'sanitize_callback' => array( Helpers::class, 'sanitize_date' ),
				'render_callback'   => array( self::class, 'render_start_date_field' ),
				'description'       => __( 'Optional go-live date in Y-m-d format.', 'shanelle' ),
			),
			Helpers::META_END   => array(
				'label'             => __( 'End Date', 'shanelle' ),
				'type'              => 'string',
				'sanitize_callback' => array( Helpers::class, 'sanitize_date' ),
				'render_callback'   => array( self::class, 'render_end_date_field' ),
				'description'       => __( 'Optional end date in Y-m-d format.', 'shanelle' ),
			),
			Helpers::META_HERO  => array(
				'label'             => __( 'Hero Image', 'shanelle' ),
				'type'              => 'integer',
				'sanitize_callback' => array( Helpers::class, 'sanitize_hero_image_id' ),
				'render_callback'   => array( self::class, 'render_hero_image_field' ),
				'description'       => __( 'Optional hero image attachment used for merchandising.', 'shanelle' ),
			),
			Helpers::META_ORDER => array(
				'label'             => __( 'Display Order', 'shanelle' ),
				'type'              => 'integer',
				'sanitize_callback' => array( Helpers::class, 'sanitize_display_order' ),
				'render_callback'   => array( self::class, 'render_display_order_field' ),
				'description'       => __( 'Lower numbers appear first in collection lists.', 'shanelle' ),
			),
		);
	}

	/**
	 * Sanitize Settings API schema submissions.
	 *
	 * @param mixed $input Raw settings input.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings_schema( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( self::get_field_definitions() as $meta_key => $field ) {
			if ( ! array_key_exists( $meta_key, $input ) ) {
				continue;
			}

			$callback            = $field['sanitize_callback'];
			$sanitized[ $meta_key ] = is_callable( $callback ) ? $callback( $input[ $meta_key ] ) : sanitize_text_field( (string) $input[ $meta_key ] );
		}

		return $sanitized;
	}

	/**
	 * Restrict term meta updates to users who can manage product terms.
	 */
	public static function authorize_meta_update( bool $allowed, string $meta_key, int $term_id ): bool {
		unset( $allowed, $meta_key, $term_id );

		return current_user_can( 'manage_product_terms' );
	}

	/**
	 * Enqueue admin assets for collection taxonomy screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'edit-tags.php' !== $hook_suffix && 'term.php' !== $hook_suffix ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';

		if ( Helpers::TAXONOMY !== $taxonomy ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'shanelle-catalog-admin',
			SHANELLE_DIR . '/inc/catalog/assets/admin-collections.css',
			array(),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-catalog-admin',
			SHANELLE_DIR . '/inc/catalog/assets/admin-collections.js',
			array(),
			SHANELLE_VERSION,
			true
		);
	}

	/**
	 * Render add-term form fields.
	 */
	public static function render_add_form_fields(): void {
		wp_nonce_field( 'shanelle_save_collection_term', 'shanelle_collection_term_nonce' );

		foreach ( self::get_field_definitions() as $meta_key => $field ) {
			self::render_field_wrapper(
				$meta_key,
				$field,
				0,
				false
			);
		}
	}

	/**
	 * Render edit-term form fields.
	 *
	 * @param \WP_Term $term Current term object.
	 */
	public static function render_edit_form_fields( \WP_Term $term ): void {
		wp_nonce_field( 'shanelle_save_collection_term', 'shanelle_collection_term_nonce' );

		foreach ( self::get_field_definitions() as $meta_key => $field ) {
			self::render_field_wrapper(
				$meta_key,
				$field,
				(int) $term->term_id,
				true
			);
		}
	}

	/**
	 * Render a Settings API style field wrapper.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	private static function render_field_wrapper( string $meta_key, array $field, int $term_id, bool $is_edit_screen ): void {
		$value = 0 === $term_id ? self::get_default_value( $meta_key ) : Helpers::get_term_meta( $term_id, $meta_key, self::get_default_value( $meta_key ) );

		$args = array(
			'meta_key'    => $meta_key,
			'field'       => $field,
			'value'       => $value,
			'term_id'     => $term_id,
			'description' => $field['description'] ?? '',
		);

		if ( $is_edit_screen ) {
			echo '<tr class="form-field shanelle-collection-field shanelle-collection-field--' . esc_attr( $meta_key ) . '">';
			echo '<th scope="row"><label for="shanelle_' . esc_attr( $meta_key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';
		} else {
			echo '<div class="form-field shanelle-collection-field shanelle-collection-field--' . esc_attr( $meta_key ) . '">';
			echo '<label for="shanelle_' . esc_attr( $meta_key ) . '">' . esc_html( $field['label'] ) . '</label>';
		}

		call_user_func( $field['render_callback'], $args );

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}

		if ( $is_edit_screen ) {
			echo '</td></tr>';
		} else {
			echo '</div>';
		}
	}

	/**
	 * Return default value for a meta key.
	 *
	 * @return mixed
	 */
	private static function get_default_value( string $meta_key ) {
		return match ( $meta_key ) {
			Helpers::META_TYPE  => Helpers::TYPE_FEATURED,
			Helpers::META_HERO  => 0,
			Helpers::META_ORDER => 0,
			default             => '',
		};
	}

	/**
	 * Render collection type select field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_type_field( array $args ): void {
		$meta_key = $args['meta_key'];
		$value    = Helpers::sanitize_collection_type( $args['value'] );
		$types    = Helpers::get_collection_types();

		echo '<select name="shanelle_collection_meta[' . esc_attr( $meta_key ) . ']" id="shanelle_' . esc_attr( $meta_key ) . '">';

		foreach ( $types as $type_key => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $type_key ),
				selected( $value, $type_key, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render start date field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_start_date_field( array $args ): void {
		self::render_date_input( $args );
	}

	/**
	 * Render end date field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_end_date_field( array $args ): void {
		self::render_date_input( $args );
	}

	/**
	 * Render shared date input field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	private static function render_date_input( array $args ): void {
		$meta_key = $args['meta_key'];
		$value    = Helpers::sanitize_date( $args['value'] );

		printf(
			'<input type="date" name="shanelle_collection_meta[%1$s]" id="shanelle_%1$s" value="%2$s" />',
			esc_attr( $meta_key ),
			esc_attr( $value )
		);
	}

	/**
	 * Render hero image field with media picker controls.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_hero_image_field( array $args ): void {
		$meta_key      = $args['meta_key'];
		$attachment_id = Helpers::sanitize_hero_image_id( $args['value'] );
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		$wrapper_class = $preview_url ? 'has-image' : 'no-image';

		echo '<div class="shanelle-hero-image-field ' . esc_attr( $wrapper_class ) . '" data-shanelle-hero-field>';

		printf(
			'<input type="hidden" name="shanelle_collection_meta[%1$s]" id="shanelle_%1$s" value="%2$d" data-shanelle-hero-input />',
			esc_attr( $meta_key ),
			(int) $attachment_id
		);

		echo '<div class="shanelle-hero-image-field__preview" data-shanelle-hero-preview>';

		if ( $preview_url ) {
			echo '<img src="' . esc_url( $preview_url ) . '" alt="" />';
		}

		echo '</div>';

		echo '<p class="shanelle-hero-image-field__actions">';
		echo '<button type="button" class="button" data-shanelle-hero-select>' . esc_html__( 'Select Image', 'shanelle' ) . '</button> ';
		echo '<button type="button" class="button" data-shanelle-hero-remove>' . esc_html__( 'Remove Image', 'shanelle' ) . '</button>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Render display order field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_display_order_field( array $args ): void {
		$meta_key = $args['meta_key'];
		$value    = Helpers::sanitize_display_order( $args['value'] );

		printf(
			'<input type="number" min="0" max="9999" step="1" name="shanelle_collection_meta[%1$s]" id="shanelle_%1$s" value="%2$d" class="small-text" />',
			esc_attr( $meta_key ),
			(int) $value
		);
	}

	/**
	 * Save collection term meta from admin forms.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function save_term_meta( int $term_id ): void {
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		if ( ! isset( $_POST['shanelle_collection_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shanelle_collection_term_nonce'] ) ), 'shanelle_save_collection_term' ) ) {
			return;
		}

		if ( ! isset( $_POST['shanelle_collection_meta'] ) || ! is_array( $_POST['shanelle_collection_meta'] ) ) {
			return;
		}

		$raw_values = wp_unslash( $_POST['shanelle_collection_meta'] );
		$sanitized  = self::sanitize_settings_schema( $raw_values );

		foreach ( self::get_field_definitions() as $meta_key => $field ) {
			if ( ! array_key_exists( $meta_key, $sanitized ) ) {
				continue;
			}

			Helpers::update_term_meta( $term_id, $meta_key, $sanitized[ $meta_key ] );
		}
	}

	/**
	 * Register admin list table columns.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function register_columns( array $columns ): array {
		$columns['collection_type']  = __( 'Type', 'shanelle' );
		$columns['collection_dates'] = __( 'Active Dates', 'shanelle' );
		$columns['collection_order'] = __( 'Order', 'shanelle' );

		return $columns;
	}

	/**
	 * Render custom admin list table column values.
	 *
	 * @param string $content     Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 */
	public static function render_column( string $content, string $column_name, int $term_id ): string {
		unset( $content );

		if ( 'collection_type' === $column_name ) {
			$type  = Helpers::get_term_meta( $term_id, Helpers::META_TYPE, Helpers::TYPE_FEATURED );
			$types = Helpers::get_collection_types();

			return esc_html( $types[ $type ] ?? $type );
		}

		if ( 'collection_dates' === $column_name ) {
			$start = Helpers::get_term_meta( $term_id, Helpers::META_START, '' );
			$end   = Helpers::get_term_meta( $term_id, Helpers::META_END, '' );

			if ( '' === $start && '' === $end ) {
				return '&mdash;';
			}

			return esc_html( trim( $start . ' — ' . $end, ' —' ) );
		}

		if ( 'collection_order' === $column_name ) {
			return esc_html( (string) Helpers::get_term_meta( $term_id, Helpers::META_ORDER, 0 ) );
		}

		return '';
	}
}
