<?php
/**
 * Catalog filters component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CatalogFilters;

defined( 'ABSPATH' ) || exit;

$groups       = CatalogFilters::get_filter_groups();
$form_id      = CatalogFilters::get_form_id();
$visible_limit = CatalogFilters::get_visible_option_limit();
$price_range  = CatalogFilters::get_selected_price_range();
?>
<form
	id="<?php echo esc_attr( $form_id ); ?>"
	class="catalog-filters"
	method="get"
	action=""
	data-shanelle-catalog-filters
>
	<div class="catalog-filters__header">
		<h2 class="catalog-filters__title text-h5"><?php esc_html_e( 'Filtro', 'shanelle' ); ?></h2>
	</div>

	<div class="catalog-filters__scroll" data-shanelle-filters-scroll>
		<div class="catalog-filters__sections" data-shanelle-filters-accordion>
			<?php foreach ( $groups as $group ) : ?>
				<?php
				$section_id = $form_id . '-section-' . sanitize_html_class( (string) $group['id'] );
				$panel_id   = $form_id . '-panel-' . sanitize_html_class( (string) $group['id'] );
				$options    = (array) ( $group['options'] ?? array() );
				$has_hidden = count( $options ) > $visible_limit;
				?>
				<section
					class="catalog-filters__section"
					data-filter-section
					data-filter-type="<?php echo esc_attr( (string) $group['type'] ); ?>"
				>
					<h3 class="catalog-filters__heading">
						<button
							type="button"
							class="catalog-filters__trigger"
							data-shanelle-filter-trigger
							aria-expanded="true"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							id="<?php echo esc_attr( $section_id ); ?>"
						>
							<span class="catalog-filters__trigger-label"><?php echo esc_html( (string) $group['label'] ); ?></span>
							<span class="catalog-filters__icon" aria-hidden="true"></span>
						</button>
					</h3>

					<div
						class="catalog-filters__panel"
						id="<?php echo esc_attr( $panel_id ); ?>"
						role="region"
						aria-labelledby="<?php echo esc_attr( $section_id ); ?>"
					>
						<?php if ( 'price' === $group['type'] ) : ?>
							<div class="catalog-filters__price">
								<label class="catalog-filters__price-field">
									<span class="catalog-filters__price-label"><?php esc_html_e( 'Mín', 'shanelle' ); ?></span>
									<input
										type="number"
										class="catalog-filters__price-input"
										name="shanelle_filter_min_price"
										value="<?php echo esc_attr( $price_range['min'] ); ?>"
										min="0"
										step="0.01"
										inputmode="decimal"
										placeholder="0"
										data-shanelle-filter-input
									/>
								</label>
								<span class="catalog-filters__price-sep" aria-hidden="true">–</span>
								<label class="catalog-filters__price-field">
									<span class="catalog-filters__price-label"><?php esc_html_e( 'Máx', 'shanelle' ); ?></span>
									<input
										type="number"
										class="catalog-filters__price-input"
										name="shanelle_filter_max_price"
										value="<?php echo esc_attr( $price_range['max'] ); ?>"
										min="0"
										step="0.01"
										inputmode="decimal"
										placeholder="0"
										data-shanelle-filter-input
									/>
								</label>
							</div>
						<?php elseif ( 'color' === $group['type'] ) : ?>
							<ul class="catalog-filters__options catalog-filters__options--color">
								<?php foreach ( $options as $option ) : ?>
									<?php
									$input_id = $form_id . '-' . sanitize_html_class( (string) $group['id'] ) . '-' . (int) $option['index'];
									$checked  = CatalogFilters::is_option_selected( $group, (string) $option['slug'] );
									$hidden   = (int) $option['index'] >= $visible_limit;
									?>
									<li class="catalog-filters__option<?php echo $hidden ? ' is-collapsed' : ''; ?>" data-filter-option>
										<label class="catalog-filters__color-label" for="<?php echo esc_attr( $input_id ); ?>">
											<input
												type="checkbox"
												class="catalog-filters__input catalog-filters__input--color"
												id="<?php echo esc_attr( $input_id ); ?>"
												name="<?php echo esc_attr( (string) $group['param'] ); ?>[]"
												value="<?php echo esc_attr( (string) $option['slug'] ); ?>"
												<?php checked( $checked ); ?>
												data-shanelle-filter-input
											/>
											<span
												class="catalog-filters__swatch"
												style="--swatch-color: <?php echo esc_attr( (string) $option['color'] ); ?>"
												aria-hidden="true"
											></span>
											<span class="catalog-filters__option-text"><?php echo esc_html( (string) $option['label'] ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php elseif ( 'radio' === $group['type'] ) : ?>
							<ul class="catalog-filters__options catalog-filters__options--radio">
								<?php foreach ( $options as $option ) : ?>
									<?php
									$input_id = $form_id . '-' . sanitize_html_class( (string) $group['id'] ) . '-' . (int) $option['index'];
									$checked  = CatalogFilters::is_option_selected( $group, (string) $option['slug'] );
									$hidden   = (int) $option['index'] >= $visible_limit;
									?>
									<li class="catalog-filters__option<?php echo $hidden ? ' is-collapsed' : ''; ?>" data-filter-option>
										<label class="catalog-filters__radio-label" for="<?php echo esc_attr( $input_id ); ?>">
											<input
												type="radio"
												class="catalog-filters__input catalog-filters__input--radio"
												id="<?php echo esc_attr( $input_id ); ?>"
												name="<?php echo esc_attr( (string) $group['param'] ); ?>"
												value="<?php echo esc_attr( (string) $option['slug'] ); ?>"
												<?php checked( $checked ); ?>
												data-shanelle-filter-input
											/>
											<span class="catalog-filters__option-text"><?php echo esc_html( (string) $option['label'] ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<ul class="catalog-filters__options catalog-filters__options--checkbox">
								<?php foreach ( $options as $option ) : ?>
									<?php
									$input_id = $form_id . '-' . sanitize_html_class( (string) $group['id'] ) . '-' . (int) $option['index'];
									$checked  = CatalogFilters::is_option_selected( $group, (string) $option['slug'] );
									$hidden   = (int) $option['index'] >= $visible_limit;
									?>
									<li class="catalog-filters__option<?php echo $hidden ? ' is-collapsed' : ''; ?>" data-filter-option>
										<label class="catalog-filters__checkbox-label" for="<?php echo esc_attr( $input_id ); ?>">
											<input
												type="checkbox"
												class="catalog-filters__input catalog-filters__input--checkbox"
												id="<?php echo esc_attr( $input_id ); ?>"
												name="<?php echo esc_attr( (string) $group['param'] ); ?>[]"
												value="<?php echo esc_attr( (string) $option['slug'] ); ?>"
												<?php checked( $checked ); ?>
												data-shanelle-filter-input
											/>
											<span class="catalog-filters__option-text"><?php echo esc_html( (string) $option['label'] ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php if ( $has_hidden && 'price' !== $group['type'] ) : ?>
							<button
								type="button"
								class="catalog-filters__view-more"
								data-shanelle-filter-view-more
								aria-expanded="false"
							>
								<?php esc_html_e( '+ Ver más', 'shanelle' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	</div>

	<?php CatalogFilters::render_preserved_fields(); ?>
</form>
