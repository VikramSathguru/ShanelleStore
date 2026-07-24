/**
 * Shanelle Search Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleSearchPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getSearchPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.searchState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-search-page-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initSearchPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-search-page]' );

	if ( ! root || root.dataset.searchHydrated === 'true' ) {
		return;
	}

	root.dataset.searchHydrated = 'true';

	const form = root.querySelector( '[data-shanelle-search-form]' );
	const input = form?.querySelector( 'input[name="s"]' );

	if ( input instanceof HTMLInputElement && ! input.value ) {
		input.focus();
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:search-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getSearchPageState(),
				api: {
					getSearchPageState,
					announce,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-search-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initSearchPage( element );
	}
} );

export {
	initSearchPage,
	getSearchPageState,
	announce,
};
