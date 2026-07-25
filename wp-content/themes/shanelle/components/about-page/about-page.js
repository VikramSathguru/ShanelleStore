/**
 * Shanelle About Page Script
 *
 * Lightweight hydration hook. Exposes page state and a ready event so future
 * enhancements (PWA, analytics) can attach without template changes.
 *
 * @package Shanelle
 */

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getAboutPageState() {
	if ( ! root ) {
		return {};
	}

	try {
		return JSON.parse( root.dataset.aboutState || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initAboutPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-about-page]' );

	if ( ! root || root.dataset.aboutHydrated === 'true' ) {
		return;
	}

	root.dataset.aboutHydrated = 'true';

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:about-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getAboutPageState(),
				api: {
					getAboutPageState,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-about-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initAboutPage( element );
	}
} );

export {
	initAboutPage,
	getAboutPageState,
};
