/**
 * Shanelle Product Grid Component
 *
 * AJAX load-more, infinite scroll, PWA hydration hooks.
 *
 * @package Shanelle
 */

const config = window.shanelleProductGrid ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} grid
 * @param {string} message
 */
function announce( grid, message ) {
	const status = grid.querySelector( '[data-shanelle-grid-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} grid
 * @param {boolean} loading
 */
function setLoading( grid, loading ) {
	grid.classList.toggle( 'is-loading', loading );

	const skeletonHost = grid.querySelector( '[data-shanelle-grid-skeleton]' );

	if ( skeletonHost ) {
		skeletonHost.hidden = ! loading;
	}
}

/**
 * @param {HTMLElement} grid
 * @param {boolean} errored
 */
function setError( grid, errored ) {
	grid.classList.toggle( 'is-error', errored );

	const error = grid.querySelector( '[data-shanelle-grid-error]' );

	if ( error ) {
		error.hidden = ! errored;
	}
}

/**
 * @param {HTMLElement} grid
 * @returns {Record<string, unknown>}
 */
function getQueryVars( grid ) {
	try {
		return JSON.parse( grid.dataset.gridQuery || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} grid
 * @returns {Record<string, unknown>}
 */
function getCardArgs( grid ) {
	try {
		return JSON.parse( grid.dataset.gridCardArgs || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} grid
 * @param {number} page
 */
async function fetchPage( grid, page ) {
	const body = new URLSearchParams( {
		action: 'shanelle_load_product_grid',
		nonce: config.nonce || '',
		page: String( page ),
		query_vars: JSON.stringify( getQueryVars( grid ) ),
		card_args: JSON.stringify( getCardArgs( grid ) ),
	} );

	const response = await fetch( config.ajaxUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: body.toString(),
		credentials: 'same-origin',
	} );

	if ( ! response.ok ) {
		throw new Error( 'Error al cargar la cuadrícula' );
	}

	const payload = await response.json();

	if ( ! payload.success ) {
		throw new Error( payload.data?.message || 'Error al cargar la cuadrícula' );
	}

	return payload.data;
}

/**
 * @param {HTMLElement} grid
 */
async function loadNextPage( grid ) {
	const current = Number( grid.dataset.gridPage || 1 );
	const maxPages = Number( grid.dataset.gridMaxPages || 1 );

	if ( current >= maxPages || grid.dataset.gridLoading === 'true' ) {
		return;
	}

	const nextPage = current + 1;

	grid.dataset.gridLoading = 'true';
	setLoading( grid, true );
	setError( grid, false );
	announce( grid, i18n.loading || 'Cargando productos…' );

	try {
		const data = await fetchPage( grid, nextPage );
		const list = grid.querySelector( '[data-shanelle-grid-items]' );

		if ( list && data.html ) {
			list.insertAdjacentHTML( 'beforeend', data.html );
		}

		grid.dataset.gridPage = String( data.page );
		grid.dataset.gridMaxPages = String( data.max_pages );

		const loadMore = grid.querySelector( '[data-shanelle-load-more]' );
		const sentinel = grid.querySelector( '[data-shanelle-infinite-sentinel]' );

		if ( ! data.has_more ) {
			loadMore?.setAttribute( 'hidden', 'hidden' );
			sentinel?.setAttribute( 'hidden', 'hidden' );
		}

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:product-grid:loaded', {
				bubbles: true,
				detail: { grid, data },
			} )
		);

		announce( grid, '' );
	} catch ( error ) {
		setError( grid, true );
		announce( grid, i18n.error || 'No se pudieron cargar los productos.' );
	} finally {
		grid.dataset.gridLoading = 'false';
		setLoading( grid, false );
	}
}

/**
 * @param {HTMLElement} grid
 */
function initInfiniteScroll( grid ) {
	const mode = grid.dataset.gridMode;
	const enabled = grid.dataset.gridInfinite === 'true';
	const sentinel = grid.querySelector( '[data-shanelle-infinite-sentinel]' );

	if ( mode !== 'infinite' || ! enabled || ! sentinel ) {
		return;
	}

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					loadNextPage( grid );
				}
			} );
		},
		{ rootMargin: '240px 0px' }
	);

	observer.observe( sentinel );
}

/**
 * @param {HTMLElement} grid
 */
function initLoadMore( grid ) {
	const button = grid.querySelector( '[data-shanelle-load-more]' );

	if ( ! button ) {
		return;
	}

	button.addEventListener( 'click', () => {
		loadNextPage( grid );
	} );
}

/**
 * @param {HTMLElement} grid
 */
function initRetry( grid ) {
	const retry = grid.querySelector( '[data-shanelle-grid-retry]' );

	if ( ! retry ) {
		return;
	}

	retry.addEventListener( 'click', () => {
		loadNextPage( grid );
	} );
}

/**
 * Hydrate grid for future PWA usage.
 *
 * @param {HTMLElement} grid
 */
function hydrateGrid( grid ) {
	initLoadMore( grid );
	initInfiniteScroll( grid );
	initRetry( grid );
}

/**
 * @param {HTMLElement} grid
 */
function initGrid( grid ) {
	if ( grid.dataset.gridHydrated === 'true' ) {
		return;
	}

	grid.dataset.gridHydrated = 'true';
	hydrateGrid( grid );
}

document.querySelectorAll( '[data-shanelle-product-grid]' ).forEach( initGrid );

const observer = new MutationObserver( ( mutations ) => {
	mutations.forEach( ( mutation ) => {
		mutation.addedNodes.forEach( ( node ) => {
			if ( ! ( node instanceof HTMLElement ) ) {
				return;
			}

			if ( node.matches( '[data-shanelle-product-grid]' ) ) {
				initGrid( node );
			}

			node.querySelectorAll( '[data-shanelle-product-grid]' ).forEach( initGrid );
		} );
	} );
} );

observer.observe( document.body, { childList: true, subtree: true } );

export { hydrateGrid, loadNextPage, fetchPage, initGrid };
