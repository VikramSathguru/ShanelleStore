/**
 * Shanelle Checkout Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCheckoutPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/** @type {IntersectionObserver|null} */
let placeOrderObserver = null;

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
 * @returns {boolean}
 */
function prefersReducedMotion() {
	return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
}

/**
 * @param {HTMLElement} element
 */
function scrollElementIntoView( element ) {
	element.scrollIntoView( {
		behavior: prefersReducedMotion() ? 'auto' : 'smooth',
		block: 'center',
		inline: 'nearest',
	} );
}

/**
 * Focus the first visible checkout validation error / notice.
 * Preserves WooCommerce validation; only improves scroll + focus.
 */
function focusFirstCheckoutError() {
	if ( ! root ) {
		return;
	}

	const noticeGroup = root.querySelector(
		'.woocommerce-NoticeGroup-checkout, .woocommerce-NoticeGroup, .checkout-page__notices .woocommerce-error'
	);

	const invalidField = root.querySelector(
		'.woocommerce-invalid input:not([type="hidden"]), .woocommerce-invalid select, .woocommerce-invalid textarea'
	);

	const target =
		invalidField instanceof HTMLElement
			? invalidField
			: noticeGroup instanceof HTMLElement
				? noticeGroup
				: root.querySelector( '.woocommerce-error' );

	if ( ! ( target instanceof HTMLElement ) ) {
		return;
	}

	scrollElementIntoView( target );

	window.setTimeout( () => {
		if ( typeof target.focus === 'function' ) {
			if ( ! target.hasAttribute( 'tabindex' ) && ! /^(INPUT|SELECT|TEXTAREA|BUTTON|A)$/i.test( target.tagName ) ) {
				target.setAttribute( 'tabindex', '-1' );
			}

			try {
				target.focus( { preventScroll: true } );
			} catch ( error ) {
				target.focus();
			}
		}
	}, prefersReducedMotion() ? 0 : 280 );
}

/**
 * Sync sticky total + label from the live order review / #place_order.
 */
function syncStickyPlaceOrderContent() {
	if ( ! root ) {
		return;
	}

	const sticky = root.querySelector( '[data-shanelle-checkout-sticky]' );

	if ( ! ( sticky instanceof HTMLElement ) ) {
		return;
	}

	const totalNode = sticky.querySelector( '[data-shanelle-checkout-sticky-total]' );
	const totalSource = root.querySelector( '.checkout-page__total-row--total .checkout-page__total-value' );

	if ( totalNode instanceof HTMLElement && totalSource instanceof HTMLElement ) {
		totalNode.innerHTML = totalSource.innerHTML;
	}

	const stickySubmit = sticky.querySelector( '[data-shanelle-checkout-sticky-submit]' );
	const placeOrder = document.getElementById( 'place_order' );

	if ( stickySubmit instanceof HTMLElement && placeOrder instanceof HTMLElement ) {
		const label =
			placeOrder.getAttribute( 'data-value' ) ||
			placeOrder.textContent?.trim() ||
			i18n.placeOrder ||
			'';

		if ( label ) {
			stickySubmit.textContent = label;
		}
	}
}

/**
 * Observe WC #place_order: show sticky only when that control is off-screen.
 */
function observePlaceOrderButton() {
	const sticky = root?.querySelector( '[data-shanelle-checkout-sticky]' );

	if ( ! ( sticky instanceof HTMLElement ) ) {
		return;
	}

	placeOrderObserver?.disconnect();
	placeOrderObserver = null;

	const placeOrder = document.getElementById( 'place_order' );

	if ( ! ( placeOrder instanceof HTMLElement ) ) {
		sticky.hidden = true;
		return;
	}

	syncStickyPlaceOrderContent();

	placeOrderObserver = new IntersectionObserver(
		( entries ) => {
			const entry = entries[ 0 ];

			if ( ! entry || ! document.getElementById( 'place_order' ) ) {
				sticky.hidden = true;
				return;
			}

			// Hide sticky when the native WC button is substantially visible.
			sticky.hidden = entry.isIntersecting && entry.intersectionRatio >= 0.35;
		},
		{
			threshold: [ 0, 0.35, 1 ],
			rootMargin: '0px 0px -10% 0px',
		}
	);

	placeOrderObserver.observe( placeOrder );
}

/**
 * Sticky CTA clicks WooCommerce's existing #place_order submit control.
 * Bound only to native place order — not express / wallet buttons (those
 * are owned by gateway plugins and bypass #place_order).
 */
function bindStickyPlaceOrder() {
	if ( ! root ) {
		return;
	}

	const sticky = root.querySelector( '[data-shanelle-checkout-sticky]' );
	const stickySubmit = sticky?.querySelector( '[data-shanelle-checkout-sticky-submit]' );

	if ( ! ( stickySubmit instanceof HTMLElement ) ) {
		return;
	}

	stickySubmit.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		const placeOrder = document.getElementById( 'place_order' );

		if ( placeOrder instanceof HTMLElement ) {
			placeOrder.click();
		}
	} );

	observePlaceOrderButton();
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

	bindStickyPlaceOrder();

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
		announce( i18n.updated || 'Resumen del pedido actualizado' );
		syncStickyPlaceOrderContent();
		observePlaceOrderButton();

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

	jQuery( document.body ).on( 'checkout_error', () => {
		announce( i18n.validationError || 'Corrige los errores abajo antes de realizar tu pedido.' );
		focusFirstCheckoutError();
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
