<?php
/**
 * Size guide modal template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\SizeGuide;

defined( 'ABSPATH' ) || exit;

$product = SizeGuide::get_product();
$guide   = SizeGuide::get_guide();

if ( ! $product instanceof WC_Product || empty( $guide ) ) {
	return;
}

$modal_id     = SizeGuide::get_modal_id( $product );
$title_id     = $modal_id . '-title';
$fit          = (string) ( $guide['fit'] ?? 'regular' );
$fit_labels   = is_array( $guide['fit_labels'] ?? null ) ? $guide['fit_labels'] : array();
$types        = is_array( $guide['types'] ?? null ) ? $guide['types'] : array();
$default_type = (string) ( $guide['default_type'] ?? 'tops' );
$default_unit = (string) ( $guide['default_unit'] ?? 'cm' );
$disclaimer   = (string) ( $guide['disclaimer'] ?? '' );
?>
<div
	id="<?php echo esc_attr( $modal_id ); ?>"
	class="size-guide modal modal--centered modal--lg"
	data-shanelle-size-guide
	data-size-guide="<?php echo esc_attr( SizeGuide::get_guide_json() ); ?>"
	data-unit="<?php echo esc_attr( $default_unit ); ?>"
	data-type="<?php echo esc_attr( $default_type ); ?>"
	data-chart="product"
	hidden
>
	<div class="modal__overlay size-guide__overlay" data-shanelle-size-guide-overlay tabindex="-1"></div>

	<div
		class="modal__dialog size-guide__dialog"
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
		data-shanelle-size-guide-panel
		tabindex="-1"
	>
		<header class="size-guide__header">
			<h2 id="<?php echo esc_attr( $title_id ); ?>" class="size-guide__title">
				<?php esc_html_e( 'Guía de tallas', 'shanelle' ); ?>
			</h2>
			<button
				type="button"
				class="size-guide__close"
				data-shanelle-size-guide-close
				aria-label="<?php esc_attr_e( 'Cerrar', 'shanelle' ); ?>"
			>
				<span aria-hidden="true">&times;</span>
			</button>
		</header>

		<div class="size-guide__toolbar">
			<span class="size-guide__switch-label"><?php esc_html_e( 'Cambiar a', 'shanelle' ); ?></span>

			<label class="size-guide__type">
				<span class="sr-only"><?php esc_html_e( 'Tipo de prenda', 'shanelle' ); ?></span>
				<select class="size-guide__type-select" data-shanelle-size-guide-type>
					<?php foreach ( $types as $type_key => $type_data ) : ?>
						<?php
						$type_label = is_array( $type_data ) ? (string) ( $type_data['label'] ?? $type_key ) : (string) $type_data;
						?>
						<option value="<?php echo esc_attr( (string) $type_key ); ?>" <?php selected( $default_type, (string) $type_key ); ?>>
							<?php echo esc_html( $type_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<div class="size-guide__units" role="group" aria-label="<?php esc_attr_e( 'Unidad de medida', 'shanelle' ); ?>">
				<button
					type="button"
					class="size-guide__unit<?php echo 'in' === $default_unit ? ' is-active' : ''; ?>"
					data-shanelle-size-guide-unit="in"
					aria-pressed="<?php echo 'in' === $default_unit ? 'true' : 'false'; ?>"
				>
					<?php esc_html_e( 'IN', 'shanelle' ); ?>
				</button>
				<button
					type="button"
					class="size-guide__unit<?php echo 'cm' === $default_unit ? ' is-active' : ''; ?>"
					data-shanelle-size-guide-unit="cm"
					aria-pressed="<?php echo 'cm' === $default_unit ? 'true' : 'false'; ?>"
				>
					<?php esc_html_e( 'CM', 'shanelle' ); ?>
				</button>
			</div>
		</div>

		<div class="size-guide__fit" data-fit="<?php echo esc_attr( $fit ); ?>">
			<span class="size-guide__fit-label"><?php esc_html_e( 'Tipo de ajuste', 'shanelle' ); ?></span>
			<div class="size-guide__fit-scale" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: fit label */ __( 'Ajuste del producto: %s', 'shanelle' ), (string) ( $fit_labels[ $fit ] ?? $fit ) ) ); ?>">
				<span class="size-guide__fit-track" aria-hidden="true"></span>
				<span class="size-guide__fit-marker" aria-hidden="true"></span>
				<ul class="size-guide__fit-labels">
					<?php foreach ( array( 'skinny', 'regular', 'oversized' ) as $fit_key ) : ?>
						<li class="size-guide__fit-item<?php echo $fit === $fit_key ? ' is-active' : ''; ?>" data-fit-key="<?php echo esc_attr( $fit_key ); ?>">
							<?php echo esc_html( (string) ( $fit_labels[ $fit_key ] ?? $fit_key ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="size-guide__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Tipo de tabla', 'shanelle' ); ?>">
			<button type="button" class="size-guide__tab is-active" role="tab" aria-selected="true" data-shanelle-size-guide-chart="product">
				<?php esc_html_e( 'Tabla del producto', 'shanelle' ); ?>
			</button>
			<button type="button" class="size-guide__tab" role="tab" aria-selected="false" data-shanelle-size-guide-chart="body">
				<?php esc_html_e( 'Tabla corporal', 'shanelle' ); ?>
			</button>
		</div>

		<div class="size-guide__subtabs" role="tablist" aria-label="<?php esc_attr_e( 'Categoría de prenda', 'shanelle' ); ?>">
			<?php foreach ( $types as $type_key => $type_data ) : ?>
				<?php
				$type_label = is_array( $type_data ) ? (string) ( $type_data['label'] ?? $type_key ) : (string) $type_data;
				$is_active  = $default_type === (string) $type_key;
				?>
				<button
					type="button"
					class="size-guide__subtab<?php echo $is_active ? ' is-active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					data-shanelle-size-guide-type-tab="<?php echo esc_attr( (string) $type_key ); ?>"
				>
					<?php echo esc_html( $type_label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="size-guide__body">
			<div class="size-guide__table-wrap" data-shanelle-size-guide-table></div>
		</div>

		<?php if ( '' !== $disclaimer ) : ?>
			<p class="size-guide__disclaimer" data-shanelle-size-guide-disclaimer>
				<?php echo esc_html( $disclaimer ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
