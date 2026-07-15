/**
 * Shanelle Cart Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleCartPage ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

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

	bindPanelEvents();
	bindShippingCalculator();

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
	const body = new URLSearchParams( payload );
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
	root?.classList.add( 'is-loading' );
	announce( i18n.loading || 'Actualizando bolsa…' );

	try {
		const response = await requestCartAction( 'shanelle_cart_page_get' );
		applyCartPageResponse( response );
		return response;
	} finally {
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
		await requestCartAction( 'shanelle_mini_cart_update', {
			cart_item_key: cartItemKey,
			quantity: String( quantity ),
		} );

		const response = await requestCartAction( 'shanelle_cart_page_get' );
		applyCartPageResponse( response );
		announce( quantity <= 0 ? ( i18n.removed || 'Artículo eliminado de la bolsa' ) : ( i18n.updated || 'Bolsa actualizada' ) );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:mini-cart:updated', {
				bubbles: true,
				detail: { source: 'cart-page', response },
			} )
		);

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
 * Toggle shipping estimator panel and keep toggle labels accessible.
 */
function bindShippingCalculator() {
	if ( ! root || root.dataset.cartShippingBound === 'true' ) {
		return;
	}

	root.dataset.cartShippingBound = 'true';

	root.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const toggle = target.closest( '[data-shanelle-cart-page-shipping-toggle]' );

		if ( ! ( toggle instanceof HTMLButtonElement ) ) {
			return;
		}

		const panel = root.querySelector( '#shipping-calculator-form' );

		if ( ! ( panel instanceof HTMLElement ) ) {
			return;
		}

		const isOpen = panel.hidden;
		panel.hidden = ! isOpen;
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		toggle.textContent = isOpen
			? ( i18n.shippingToggleClose || 'Cerrar estimador de envío' )
			: ( i18n.shippingToggle || 'Estimar envío' );
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
	bindShippingCalculator();

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
