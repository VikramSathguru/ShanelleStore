/**
 * Shanelle Collection Archive Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCollectionPage ?? {};

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getCollectionPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.collectionState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initCollectionPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-collection-page]' );

	if ( ! root || root.dataset.collectionHydrated === 'true' ) {
		return;
	}

	root.dataset.collectionHydrated = 'true';

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:collection-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getCollectionPageState(),
				api: {
					getCollectionPageState,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-collection-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initCollectionPage( element );
	}
} );

export {
	initCollectionPage,
	getCollectionPageState,
};
