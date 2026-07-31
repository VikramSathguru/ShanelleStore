/**
 * Shanelle Cart Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCartPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/** @type {boolean} */
let isRefreshing = false;

/**
 * @returns {Record<string, unknown>}
 */
function getCartPageState() {
	if ( ! root ) {
		return config.initialState ?? {};
	}

	try {
		return JSON.parse( root.dataset.cartState || '{}' );
	} catch ( error ) {
		return config.initialState ?? {};
	}
}

/**
 * @param {Record<string, unknown>} state
 */
function setCartPageState( state ) {
	if ( root ) {
		root.dataset.cartState = JSON.stringify( state );
		root.classList.toggle( 'cart-page--empty', Boolean( state.is_empty ) );
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-cart-page-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {Record<string, string>} fragments
 */
function applyFragments( fragments ) {
	if ( ! fragments || typeof fragments !== 'object' ) {
		return;
	}

	Object.entries( fragments ).forEach( ( [ selector, html ] ) => {
		const target = document.querySelector( selector );

		if ( target ) {
			target.outerHTML = html;
		}
	} );
}

/**
 * @param {Record<string, unknown>|null|undefined} state
 */
function syncStickyCheckout( state = null ) {
	const sticky = root?.querySelector( '[data-shanelle-cart-page-sticky]' );

	if ( ! ( sticky instanceof HTMLElement ) ) {
		return;
	}

	const cartState = state ?? getCartPageState();

	if ( cartState?.is_empty ) {
		sticky.hidden = true;
		return;
	}

	sticky.hidden = false;

	const totalNode = sticky.querySelector( '[data-shanelle-cart-page-sticky-total]' );
	const totals = Array.isArray( cartState.totals ) ? cartState.totals : [];
	const totalRow = totals.find( ( row ) => row && typeof row === 'object' && row.class === 'total' );

	if ( totalNode instanceof HTMLElement && totalRow && typeof totalRow.value_html === 'string' ) {
		totalNode.innerHTML = totalRow.value_html;
	}

	const checkoutLink = sticky.querySelector( '[data-shanelle-cart-page-sticky-checkout]' );
	const checkoutUrl = cartState?.urls && typeof cartState.urls === 'object'
		? cartState.urls.checkout
		: null;

	if ( checkoutLink instanceof HTMLAnchorElement && typeof checkoutUrl === 'string' && checkoutUrl ) {
		checkoutLink.href = checkoutUrl;
	}
}

/**
 * @param {Record<string, unknown>} response
 */
function applyCartPageResponse( response ) {
	if ( response?.state?.is_empty ) {
		window.location.reload();
		return;
	}

	if ( response?.state ) {
		setCartPageState( response.state );
	}

	if ( response?.fragments ) {
		applyFragments( response.fragments );
	}

	syncStickyCheckout( response?.state ?? getCartPageState() );
	bindPanelEvents();

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:cart-page:updated', {
			bubbles: true,
			detail: {
				root,
				state: getCartPageState(),
				response,
			},
		} )
	);

	if ( response?.cart || response?.fragments ) {
		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:mini-cart:updated', {
				bubbles: true,
				detail: {
					source: 'cart-page',
					response,
					state: response.cart ?? null,
				},
			} )
		);
	}
}

/**
 * Sync cart page when MiniCart mutates the session while the cart page is open.
 *
 * @param {CustomEvent} event
 */
function handleMiniCartUpdated( event ) {
	if ( ! root || ! ( event instanceof CustomEvent ) ) {
		return;
	}

	const response = event.detail?.response;

	if ( ! response?.state || event.detail?.source === 'cart-page' ) {
		return;
	}

	if ( response.state.is_empty ) {
		window.location.reload();
		return;
	}

	setCartPageState( response.state );

	if ( response.fragments ) {
		applyFragments( response.fragments );
	}

	syncStickyCheckout( response.state );
}

/**
 * @param {string} endpoint
 * @param {Record<string, string>} payload
 * @returns {Promise<Record<string, unknown>>}
 */
async function requestCartAction( endpoint, payload = {} ) {
	if ( ! config.ajaxUrl ) {
		throw new Error( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' );
	}

	const url = String( config.ajaxUrl ).replace( '%%endpoint%%', endpoint );
	const body = new URLSearchParams( {
		...payload,
		nonce: config.nonce || '',
	} );
	const response = await fetch( url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: body.toString(),
		credentials: 'same-origin',
	} );

	if ( ! response.ok ) {
		throw new Error( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' );
	}

	const data = await response.json();

	if ( ! data.success ) {
		throw new Error( data?.data?.message || i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' );
	}

	return data.data ?? {};
}

/**
 * @returns {Promise<Record<string, unknown>>}
 */
async function refreshCartPage() {
	isRefreshing = true;
	root?.classList.add( 'is-loading' );
	announce( i18n.loading || 'Actualizando bolsa…' );

	try {
		const response = await requestCartAction( 'shanelle_cart_page_get' );
		applyCartPageResponse( response );
		return response;
	} finally {
		isRefreshing = false;
		root?.classList.remove( 'is-loading' );
	}
}

/**
 * @param {string} cartItemKey
 * @param {number} quantity
 * @returns {Promise<Record<string, unknown>>}
 */
async function updateCartItemQuantity( cartItemKey, quantity ) {
	const item = root?.querySelector( `[data-shanelle-cart-page-item][data-cart-item-key="${ CSS.escape( cartItemKey ) }"]` );
	item?.classList.add( 'is-loading' );
	announce( i18n.loading || 'Actualizando bolsa…' );

	try {
		const response = await requestCartAction( 'shanelle_mini_cart_update', {
			cart_item_key: cartItemKey,
			quantity: String( quantity ),
			include_cart_page: '1',
		} );

		applyCartPageResponse( response );
		announce( quantity <= 0 ? ( i18n.removed || 'Artículo eliminado de la bolsa' ) : ( i18n.updated || 'Bolsa actualizada' ) );

		return response;
	} finally {
		item?.classList.remove( 'is-loading' );
	}
}

/**
 * @param {string} cartItemKey
 * @returns {Promise<Record<string, unknown>>}
 */
async function removeCartItem( cartItemKey ) {
	return updateCartItemQuantity( cartItemKey, 0 );
}

/**
 * @param {HTMLElement} item
 * @returns {HTMLInputElement|null}
 */
function getItemQuantityInput( item ) {
	const input = item.querySelector( '[data-shanelle-cart-page-quantity]' );
	return input instanceof HTMLInputElement ? input : null;
}

/**
 * @param {string} cartItemKey
 * @param {number} delta
 */
async function stepItemQuantity( cartItemKey, delta ) {
	const item = root?.querySelector( `[data-shanelle-cart-page-item][data-cart-item-key="${ CSS.escape( cartItemKey ) }"]` );

	if ( ! item ) {
		return;
	}

	const input = getItemQuantityInput( item );
	const current = Number( input?.value ?? 1 );
	const min = Number( input?.min ?? 0 );
	const max = Number( input?.max ?? 0 );
	let next = Math.max( min, current + delta );

	if ( max > 0 ) {
		next = Math.min( next, max );
	}

	if ( input ) {
		input.value = String( next );
	}

	await updateCartItemQuantity( cartItemKey, next );
}

/**
 * Bind interactive handlers within the cart page.
 */
function bindPanelEvents() {
	if ( ! root || root.dataset.cartPageBound === 'true' ) {
		return;
	}

	root.dataset.cartPageBound = 'true';

	root.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const removeButton = target.closest( '[data-shanelle-cart-page-remove]' );

		if ( removeButton instanceof HTMLAnchorElement ) {
			event.preventDefault();
			const key = removeButton.dataset.cartItemKey;

			if ( key ) {
				removeCartItem( key ).catch( ( error ) => {
					announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
				} );
			}

			return;
		}

		const decrement = target.closest( '[data-shanelle-cart-page-decrement]' );

		if ( decrement instanceof HTMLButtonElement ) {
			event.preventDefault();
			const key = decrement.dataset.cartItemKey;

			if ( key ) {
				stepItemQuantity( key, -1 ).catch( ( error ) => {
					announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
				} );
			}

			return;
		}

		const increment = target.closest( '[data-shanelle-cart-page-increment]' );

		if ( increment instanceof HTMLButtonElement ) {
			event.preventDefault();
			const key = increment.dataset.cartItemKey;

			if ( key ) {
				stepItemQuantity( key, 1 ).catch( ( error ) => {
					announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
				} );
			}
		}
	} );

	root.addEventListener( 'change', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof HTMLInputElement ) || ! target.matches( '[data-shanelle-cart-page-quantity]' ) ) {
			return;
		}

		const key = target.dataset.cartItemKey;
		const quantity = Number( target.value );

		if ( ! key || ! Number.isFinite( quantity ) ) {
			return;
		}

		updateCartItemQuantity( key, quantity ).catch( ( error ) => {
			announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
		} );
	} );
}

/**
 * Re-render the themed summary after WooCommerce recalculates the cart.
 *
 * WooCommerce cart.js owns the shipping calculator (toggle, country/state sync,
 * submit) and only refreshes its own `.cart_totals` markup, which this theme
 * does not render — so the theme pulls its own fragments from the cart session.
 */
function bindWooCartEvents() {
	const jq = window.jQuery;

	if ( ! jq || ! root || root.dataset.cartWooEventsBound === 'true' ) {
		return;
	}

	root.dataset.cartWooEventsBound = 'true';

	jq( document.body ).on( 'updated_wc_div updated_shipping_method', () => {
		if ( isRefreshing ) {
			return;
		}

		refreshCartPage().catch( ( error ) => {
			announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
		} );
	} );
}

/**
 * Open the calculator panel when WooCommerce cart.js is unavailable.
 *
 * Only a visibility fallback: the form still posts to WooCommerce, which owns
 * address validation and rate calculation.
 */
function bindShippingFallbackToggle() {
	if ( ! root || root.dataset.cartShippingFallbackBound === 'true' ) {
		return;
	}

	if ( window.jQuery && window.wc_cart_params ) {
		return;
	}

	root.dataset.cartShippingFallbackBound = 'true';

	root.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const toggle = target.closest( '.shipping-calculator-button' );

		if ( ! ( toggle instanceof HTMLElement ) ) {
			return;
		}

		const panel = root.querySelector( '.shipping-calculator-form' );

		if ( ! ( panel instanceof HTMLElement ) ) {
			return;
		}

		event.preventDefault();

		const isOpen = panel.style.display !== 'none';
		panel.style.display = isOpen ? 'none' : 'block';
		toggle.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
	} );
}

/**
 * @param {HTMLElement|null} element
 */
function initCartPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-cart-page]' );

	if ( ! root || root.dataset.cartPageHydrated === 'true' ) {
		return;
	}

	root.dataset.cartPageHydrated = 'true';

	if ( config.initialState ) {
		setCartPageState( config.initialState );
	}

	bindPanelEvents();
	bindWooCartEvents();
	bindShippingFallbackToggle();
	syncStickyCheckout();
	document.body.addEventListener( 'shanelle:mini-cart:updated', handleMiniCartUpdated );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:cart-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getCartPageState(),
				api: {
					getCartPageState,
					refreshCartPage,
					updateCartItemQuantity,
					removeCartItem,
					applyCartPageResponse,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-cart-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initCartPage( element );
	}
} );

export {
	initCartPage,
	getCartPageState,
	refreshCartPage,
	updateCartItemQuantity,
	removeCartItem,
	applyCartPageResponse,
};
