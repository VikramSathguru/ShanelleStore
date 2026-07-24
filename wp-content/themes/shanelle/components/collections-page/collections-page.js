/**
 * Shanelle Collections Index Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCollectionsPage ?? {};

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getCollectionsPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.collectionsState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initCollectionsPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-collections-page]' );

	if ( ! root || root.dataset.collectionsHydrated === 'true' ) {
		return;
	}

	root.dataset.collectionsHydrated = 'true';

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:collections-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getCollectionsPageState(),
				api: {
					getCollectionsPageState,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-collections-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initCollectionsPage( element );
	}
} );

export {
	initCollectionsPage,
	getCollectionsPageState,
};
