<?php
/**
 * Search overlay component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\SearchOverlay;
use Shanelle\Components\SearchResults;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="search-overlay"
	id="<?php echo esc_attr( SearchOverlay::get_root_id() ); ?>"
	data-shanelle-search-overlay
	data-search-state="<?php echo esc_attr( SearchOverlay::get_state_json() ); ?>"
	hidden
>
	<button
		type="button"
		class="search-overlay__backdrop"
		data-shanelle-search-close
		tabindex="-1"
		aria-hidden="true"
	></button>

	<div
		class="search-overlay__dialog"
		data-shanelle-search-dialog
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( SearchOverlay::get_title_id() ); ?>"
		tabindex="-1"
	>
		<header class="search-overlay__header">
			<h2 class="search-overlay__title" id="<?php echo esc_attr( SearchOverlay::get_title_id() ); ?>">
				<?php esc_html_e( 'Buscar', 'shanelle' ); ?>
			</h2>

			<form
				class="search-overlay__form"
				role="search"
				method="get"
				action="<?php echo esc_url( home_url( '/' ) ); ?>"
				data-shanelle-search-form
			>
				<label class="screen-reader-text" for="<?php echo esc_attr( SearchOverlay::get_input_id() ); ?>">
					<?php esc_html_e( 'Buscar productos', 'shanelle' ); ?>
				</label>

				<div class="search-overlay__field">
					<input
						type="search"
						id="<?php echo esc_attr( SearchOverlay::get_input_id() ); ?>"
						class="input input--search search-overlay__input"
						name="s"
						value=""
						placeholder="<?php echo esc_attr( SearchOverlay::get_placeholder() ); ?>"
						autocomplete="off"
						autocapitalize="off"
						spellcheck="false"
						enterkeyhint="search"
						data-shanelle-search-input
						role="combobox"
						aria-autocomplete="list"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( SearchOverlay::get_results_id() ); ?>"
					/>
					<input type="hidden" name="post_type" value="product" />

					<button type="submit" class="btn btn--primary search-overlay__submit">
						<?php esc_html_e( 'Buscar', 'shanelle' ); ?>
					</button>
				</div>
			</form>

			<button
				type="button"
				class="search-overlay__close btn btn--ghost btn--icon"
				data-shanelle-search-close
				aria-label="<?php esc_attr_e( 'Cerrar búsqueda', 'shanelle' ); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
		</header>

		<div
			class="search-overlay__body"
			id="<?php echo esc_attr( SearchOverlay::get_results_id() ); ?>"
			data-shanelle-search-results
		>
			<?php SearchResults::render_idle(); ?>
		</div>

		<p class="screen-reader-text" data-shanelle-search-status role="status" aria-live="polite" aria-atomic="true"></p>
	</div>
</div>
