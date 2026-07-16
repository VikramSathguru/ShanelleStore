/**
 * Shanelle Catalog Filters Component
 *
 * @package Shanelle
 */

const config = window.shanelleCatalogFilters ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLButtonElement} trigger
 */
function toggleFilterSection( trigger ) {
	const panelId = trigger.getAttribute( 'aria-controls' );
	const panel = panelId ? document.getElementById( panelId ) : null;
	const expanded = trigger.getAttribute( 'aria-expanded' ) === 'true';

	if ( ! ( panel instanceof HTMLElement ) ) {
		return;
	}

	trigger.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
	panel.hidden = expanded;
}

/**
 * @param {HTMLButtonElement} button
 */
function toggleViewMore( button ) {
	const section = button.closest( '[data-filter-section]' );

	if ( ! ( section instanceof HTMLElement ) ) {
		return;
	}

	const expanded = section.classList.toggle( 'is-expanded' );
	button.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
	button.textContent = expanded
		? i18n.viewLess || 'Ver menos'
		: i18n.viewMore ? `+ ${ i18n.viewMore }` : '+ Ver más';
}

/**
 * Desktop sidebar submits instantly; mobile sheet waits for Apply.
 *
 * @param {HTMLFormElement} form
 */
function initAutoSubmit( form ) {
	const mode = form.dataset.submitMode || 'instant';

	if ( mode === 'apply' ) {
		return;
	}

	const submit = () => {
		if ( typeof form.requestSubmit === 'function' ) {
			form.requestSubmit();
			return;
		}

		form.submit();
	};

	form.querySelectorAll( '[data-shanelle-filter-input]' ).forEach( ( input ) => {
		input.addEventListener( 'change', submit );
	} );

	let priceTimer = 0;

	form.querySelectorAll( '.catalog-filters__price-input' ).forEach( ( input ) => {
		input.addEventListener( 'input', () => {
			window.clearTimeout( priceTimer );
			priceTimer = window.setTimeout( submit, 500 );
		} );
	} );
}

/**
 * @param {HTMLElement} root
 */
function initCatalogFilters( root ) {
	const form = root.matches( 'form[data-shanelle-catalog-filters]' )
		? root
		: root.querySelector( 'form[data-shanelle-catalog-filters]' );

	if ( ! ( form instanceof HTMLFormElement ) ) {
		return;
	}

	if ( form.dataset.filtersHydrated === 'true' ) {
		return;
	}

	form.dataset.filtersHydrated = 'true';

	root.querySelectorAll( '[data-shanelle-filter-trigger]' ).forEach( ( trigger ) => {
		if ( ! ( trigger instanceof HTMLButtonElement ) ) {
			return;
		}

		trigger.addEventListener( 'click', () => {
			toggleFilterSection( trigger );
		} );
	} );

	root.querySelectorAll( '[data-shanelle-filter-view-more]' ).forEach( ( button ) => {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		button.addEventListener( 'click', () => {
			toggleViewMore( button );
		} );
	} );

	initAutoSubmit( form );
}

document.querySelectorAll( '[data-shanelle-catalog-filters]' ).forEach( initCatalogFilters );

export { initCatalogFilters };
