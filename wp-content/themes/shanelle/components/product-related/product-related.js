/**
 * Shanelle Related Products Component
 *
 * Shows two rows initially; “Ver más” reveals two more rows at a time.
 *
 * @package Shanelle
 */

const config = window.shanelleProductRelated ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} section
 * @returns {Record<string, unknown>}
 */
function getRecommendationData( section ) {
	try {
		return JSON.parse( section.dataset.recommendationJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} list
 * @returns {number}
 */
function getColumnCount( list ) {
	const template = window.getComputedStyle( list ).gridTemplateColumns;

	if ( ! template || template === 'none' ) {
		return 2;
	}

	const cols = template.split( ' ' ).filter( ( part ) => part && part !== '0px' ).length;

	return Math.max( 2, cols );
}

/**
 * @param {HTMLElement} list
 * @param {HTMLButtonElement|null} button
 * @returns {number}
 */
function getPageSize( list, button ) {
	const rowsAttr = button?.dataset.rowsPerPage || list.dataset.rowsPerPage || '2';
	const rows = Math.max( 1, Number.parseInt( rowsAttr, 10 ) || 2 );

	return getColumnCount( list ) * rows;
}

/**
 * @param {HTMLElement} list
 * @param {number} visibleCount
 */
function applyVisibleCount( list, visibleCount ) {
	const items = Array.from( list.querySelectorAll( '.product-related__item' ) );

	items.forEach( ( item, index ) => {
		const show = index < visibleCount;

		item.classList.toggle( 'is-deferred', ! show );
		item.hidden = ! show;
	} );
}

/**
 * @param {HTMLElement} section
 * @param {HTMLButtonElement} button
 * @param {number} visibleCount
 */
function syncLoadMoreButton( section, button, visibleCount ) {
	const list = section.querySelector( '[data-shanelle-related-items]' );
	const total = list ? list.querySelectorAll( '.product-related__item' ).length : 0;
	const wrap = button.closest( '.product-related__view-more-wrap' );
	const hasMore = visibleCount < total;

	button.hidden = ! hasMore;
	button.setAttribute( 'aria-hidden', hasMore ? 'false' : 'true' );

	if ( wrap instanceof HTMLElement ) {
		wrap.hidden = ! hasMore;
	}
}

/**
 * Collapse the grid to the first two rows for the current breakpoint.
 *
 * @param {HTMLElement} section
 * @returns {number}
 */
function collapseToFirstPage( section ) {
	const list = section.querySelector( '[data-shanelle-related-items]' );
	const button = section.querySelector( '[data-shanelle-related-load-more]' );

	if ( !( list instanceof HTMLElement ) ) {
		return 0;
	}

	const pageSize = getPageSize( list, button instanceof HTMLButtonElement ? button : null );
	applyVisibleCount( list, pageSize );

	if ( button instanceof HTMLButtonElement ) {
		syncLoadMoreButton( section, button, pageSize );
	}

	section.dataset.relatedVisible = String( pageSize );

	return pageSize;
}

/**
 * Reveal the next two rows of related product cards.
 *
 * @param {HTMLElement} section
 * @param {HTMLButtonElement} button
 */
function revealMoreRelated( section, button ) {
	const list = section.querySelector( '[data-shanelle-related-items]' );

	if ( !( list instanceof HTMLElement ) ) {
		return;
	}

	const total = list.querySelectorAll( '.product-related__item' ).length;
	const pageSize = getPageSize( list, button );
	const current = Number.parseInt( section.dataset.relatedVisible || '0', 10 ) || pageSize;
	const next = Math.min( total, current + pageSize );

	applyVisibleCount( list, next );
	section.dataset.relatedVisible = String( next );
	syncLoadMoreButton( section, button, next );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:related-products:load-more', {
			bubbles: true,
			detail: {
				section,
				revealed: next - current,
				visible: next,
				remaining: Math.max( 0, total - next ),
			},
		} )
	);
}

/**
 * @param {HTMLElement} section
 */
function initProductRelated( section ) {
	if ( section.dataset.relatedHydrated === 'true' ) {
		return;
	}

	section.dataset.relatedHydrated = 'true';

	const data = getRecommendationData( section );
	const loadMore = section.querySelector( '[data-shanelle-related-load-more]' );

	collapseToFirstPage( section );

	if ( loadMore instanceof HTMLButtonElement ) {
		loadMore.addEventListener( 'click', () => {
			revealMoreRelated( section, loadMore );
		} );
	}

	let resizeTimer = 0;

	window.addEventListener( 'resize', () => {
		window.clearTimeout( resizeTimer );
		resizeTimer = window.setTimeout( () => {
			const list = section.querySelector( '[data-shanelle-related-items]' );
			const button = section.querySelector( '[data-shanelle-related-load-more]' );

			if ( !( list instanceof HTMLElement ) ) {
				return;
			}

			const total = list.querySelectorAll( '.product-related__item' ).length;
			const pageSize = getPageSize( list, button instanceof HTMLButtonElement ? button : null );
			const current = Number.parseInt( section.dataset.relatedVisible || String( pageSize ), 10 ) || pageSize;
			const pages = Math.max( 1, Math.ceil( current / pageSize ) );
			const next = Math.min( total, pages * pageSize );

			applyVisibleCount( list, next );
			section.dataset.relatedVisible = String( next );

			if ( button instanceof HTMLButtonElement ) {
				syncLoadMoreButton( section, button, next );
			}
		}, 150 );
	} );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:related-products:ready', {
			bubbles: true,
			detail: {
				section,
				data,
				sourceProductId: Number( section.dataset.sourceProductId || data.sourceProductId || 0 ),
				items: Array.isArray( data.items ) ? data.items : [],
				i18n,
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-related]' ).forEach( initProductRelated );

export { initProductRelated, getRecommendationData, revealMoreRelated, collapseToFirstPage };
