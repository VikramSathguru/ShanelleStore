/**
 * Shanelle Checkout Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCheckoutPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getCheckoutPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.checkoutState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {Record<string, unknown>} state
 */
function setCheckoutPageState( state ) {
	if ( root ) {
		root.dataset.checkoutState = JSON.stringify( state );
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-checkout-page-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement|null} element
 */
function initCheckoutPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-checkout-page]' );

	if ( ! root || root.dataset.checkoutHydrated === 'true' ) {
		return;
	}

	root.dataset.checkoutHydrated = 'true';

	if ( config.initialState ) {
		setCheckoutPageState( config.initialState );
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:checkout-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getCheckoutPageState(),
				api: {
					getCheckoutPageState,
					announce,
				},
			},
		} )
	);
}

function bindCheckoutUpdates() {
	if ( typeof jQuery === 'undefined' ) {
		return;
	}

	jQuery( document.body ).on( 'updated_checkout', () => {
		announce( i18n.updated || 'Order summary updated' );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:checkout-page:updated', {
				bubbles: true,
				detail: {
					root,
					state: getCheckoutPageState(),
				},
			} )
		);
	} );
}

document.querySelectorAll( '[data-shanelle-checkout-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initCheckoutPage( element );
	}
} );

bindCheckoutUpdates();

export {
	initCheckoutPage,
	getCheckoutPageState,
	announce,
};
