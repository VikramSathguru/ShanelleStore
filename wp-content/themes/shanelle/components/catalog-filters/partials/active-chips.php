<?php
/**
 * Active catalog filter chips.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CatalogFilters;

defined( 'ABSPATH' ) || exit;

$chips = CatalogFilters::get_active_chips();

if ( empty( $chips ) ) {
	return;
}
?>
<div
	class="catalog-filters-chips"
	data-shanelle-active-filters
	aria-label="<?php esc_attr_e( 'Filtros activos', 'shanelle' ); ?>"
>
	<ul class="catalog-filters-chips__list" role="list">
		<?php foreach ( $chips as $chip ) : ?>
			<li class="catalog-filters-chips__item">
				<a
					class="catalog-filters-chips__chip"
					href="<?php echo esc_url( (string) ( $chip['remove_url'] ?? '#' ) ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: filter label */ __( 'Quitar filtro %s', 'shanelle' ), (string) ( $chip['label'] ?? '' ) ) ); ?>"
				>
					<span class="catalog-filters-chips__label"><?php echo esc_html( (string) ( $chip['label'] ?? '' ) ); ?></span>
					<span class="catalog-filters-chips__remove" aria-hidden="true">&times;</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<a class="catalog-filters-chips__clear" href="<?php echo esc_url( CatalogFilters::get_clear_all_url() ); ?>">
		<?php esc_html_e( 'Limpiar filtros', 'shanelle' ); ?>
	</a>
</div>
