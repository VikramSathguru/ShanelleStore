/**
 * Shanelle Mini Cart Component
 *
 * @package Shanelle
 */

const config = window.shanelleMiniCart ?? {};
const i18n = config.i18n ?? {};

/** @type {HTMLElement|null} */
let root = null;

/** @type {HTMLElement|null} */
let panel = null;

/** @type {HTMLElement|null} */
let lastFocusedElement = null;

/** @type {boolean} */
let isOpen = false;

/** @type {boolean} */
let isBusy = false;

const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'textarea:not([disabled])',
	'select:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ', ' );

/**
 * @returns {Record<string, unknown>}
 */
function getCartState() {
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
function setCartState( state ) {
	if ( root ) {
		root.dataset.cartState = JSON.stringify( state );
	}
}

/**
 * @param {string} message
 */
function announce( message ) {
	const status = root?.querySelector( '[data-shanelle-mini-cart-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
function getFocusableElements( container ) {
	return Array.from( container.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter( ( element ) => {
		return element instanceof HTMLElement && ! element.hasAttribute( 'disabled' ) && element.offsetParent !== null;
	} );
}

/**
 * @param {KeyboardEvent} event
 */
function handleFocusTrap( event ) {
	if ( ! isOpen || ! panel || event.key !== 'Tab' ) {
		return;
	}

	const focusables = getFocusableElements( panel );

	if ( focusables.length === 0 ) {
		event.preventDefault();
		panel.focus();
		return;
	}

	const first = focusables[ 0 ];
	const last = focusables[ focusables.length - 1 ];
	const active = document.activeElement;

	if ( event.shiftKey && active === first ) {
		event.preventDefault();
		last.focus();
	} else if ( ! event.shiftKey && active === last ) {
		event.preventDefault();
		first.focus();
	}
}

/**
 * @param {KeyboardEvent} event
 */
function handleGlobalKeydown( event ) {
	if ( event.key === 'Escape' && isOpen ) {
		event.preventDefault();
		closeMiniCart();
	}

	handleFocusTrap( event );
}

/**
 * Open the mini cart drawer.
 *
 * @param {Record<string, unknown>} [options]
 */
function openMiniCart( options = {} ) {
	if ( ! root || ! panel || isOpen ) {
		return;
	}

	lastFocusedElement = document.activeElement instanceof HTMLElement
		? document.activeElement
		: null;

	isOpen = true;
	root.hidden = false;
	root.classList.add( 'is-open' );
	panel.setAttribute( 'aria-hidden', 'false' );
	document.body.classList.add( 'is-mini-cart-open' );

	requestAnimationFrame( () => {
		const closeButton = panel.querySelector( '[data-shanelle-mini-cart-close]' );

		if ( closeButton instanceof HTMLElement ) {
			closeButton.focus();
		} else {
			panel.focus();
		}
	} );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:mini-cart:opened', {
			bubbles: true,
			detail: {
				root,
				panel,
				state: getCartState(),
				options,
			},
		} )
	);
}

/**
 * Close the mini cart drawer.
 */
function closeMiniCart() {
	if ( ! root || ! panel || ! isOpen ) {
		return;
	}

	isOpen = false;
	root.classList.remove( 'is-open' );
	panel.setAttribute( 'aria-hidden', 'true' );
	document.body.classList.remove( 'is-mini-cart-open' );
	root.hidden = true;

	if ( lastFocusedElement ) {
		lastFocusedElement.focus();
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:mini-cart:closed', {
			bubbles: true,
			detail: {
				root,
				panel,
				state: getCartState(),
			},
		} )
	);
}

/**
 * Toggle the mini cart drawer.
 */
function toggleMiniCart() {
	if ( isOpen ) {
		closeMiniCart();
	} else {
		openMiniCart();
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

	panel = root?.querySelector( '[data-shanelle-mini-cart-panel]' ) ?? panel;
	bindPanelEvents();
}

/**
 * @param {Record<string, unknown>} response
 */
function applyCartResponse( response ) {
	if ( response?.cart ) {
		setCartState( response.cart );
		updateTitleCount( response.cart );
	}

	if ( response?.fragments ) {
		applyFragments( response.fragments );
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:mini-cart:updated', {
			bubbles: true,
			detail: {
				root,
				panel,
				state: getCartState(),
				response,
			},
		} )
	);
}

/**
 * @param {Record<string, unknown>} state
 */
function updateTitleCount( state ) {
	const count = Number( state.count ?? 0 );
	const countNode = root?.querySelector( '[data-shanelle-mini-cart-count]' );

	if ( countNode ) {
		countNode.textContent = String( count );
	}

	if ( root ) {
		const title = root.querySelector( '.mini-cart__title-text' );

		if ( title ) {
			const label = count === 1
				? ( i18n.itemCount || '%d artículo en la bolsa' ).replace( '%d', String( count ) )
				: ( i18n.itemsCount || '%d artículos en la bolsa' ).replace( '%d', String( count ) );
			title.setAttribute( 'aria-label', `${ i18n.title || 'Tu bolsa' } — ${ label }` );
		}
	}
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
 * Refresh mini cart contents from the server.
 *
 * @returns {Promise<Record<string, unknown>>}
 */
async function refreshMiniCart() {
	setBusy( true );

	try {
		const response = await requestCartAction( 'shanelle_mini_cart_get' );
		applyCartResponse( response );
		return response;
	} finally {
		setBusy( false );
	}
}

/**
 * @param {string} cartItemKey
 * @param {number} quantity
 * @returns {Promise<Record<string, unknown>>}
 */
async function updateCartItemQuantity( cartItemKey, quantity ) {
	if ( ! cartItemKey ) {
		throw new Error( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' );
	}

	const item = root?.querySelector( `[data-cart-item-key="${ CSS.escape( cartItemKey ) }"]` );
	item?.classList.add( 'is-loading' );
	setBusy( true );
	announce( i18n.loading || 'Actualizando bolsa…' );

	try {
		const response = await requestCartAction( 'shanelle_mini_cart_update', {
			cart_item_key: cartItemKey,
			quantity: String( quantity ),
		} );

		applyCartResponse( response );
		applyWooFragments( response.fragments );
		announce( i18n.updated || 'Bolsa actualizada' );

		return response;
	} finally {
		item?.classList.remove( 'is-loading' );
		setBusy( false );
	}
}

/**
 * @param {string} cartItemKey
 * @returns {Promise<Record<string, unknown>>}
 */
async function removeCartItem( cartItemKey ) {
	return updateCartItemQuantity( cartItemKey, 0 ).then( ( response ) => {
		announce( i18n.removed || 'Artículo eliminado de la bolsa' );
		return response;
	} );
}

/**
 * @param {Record<string, string>|undefined} fragments
 */
function applyWooFragments( fragments ) {
	if ( ! fragments ) {
		return;
	}

	Object.entries( fragments ).forEach( ( [ selector, html ] ) => {
		if ( selector.startsWith( '[data-shanelle-mini-cart' ) ) {
			return;
		}

		const target = document.querySelector( selector );

		if ( target ) {
			target.outerHTML = html;
		}
	} );
}

/**
 * @param {boolean} busy
 */
function setBusy( busy ) {
	isBusy = busy;
	root?.classList.toggle( 'is-loading', busy );
}

/**
 * @param {HTMLElement} item
 * @returns {HTMLInputElement|null}
 */
function getItemQuantityInput( item ) {
	const input = item.querySelector( '[data-shanelle-mini-cart-quantity]' );
	return input instanceof HTMLInputElement ? input : null;
}

/**
 * @param {string} cartItemKey
 * @param {number} delta
 */
async function stepItemQuantity( cartItemKey, delta ) {
	const item = root?.querySelector( `[data-shanelle-mini-cart-item][data-cart-item-key="${ CSS.escape( cartItemKey ) }"]` );

	if ( ! item ) {
		return;
	}

	const input = getItemQuantityInput( item );
	const current = Number( input?.value ?? 1 );
	const min = Number( input?.min ?? 1 );
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
 * Bind interactive handlers within the panel.
 */
function bindPanelEvents() {
	if ( ! panel || panel.dataset.miniCartBound === 'true' ) {
		return;
	}

	panel.dataset.miniCartBound = 'true';

	panel.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const removeButton = target.closest( '[data-shanelle-mini-cart-remove]' );

		if ( removeButton instanceof HTMLButtonElement ) {
			event.preventDefault();
			const key = removeButton.dataset.cartItemKey;

			if ( key ) {
				removeCartItem( key ).catch( ( error ) => {
					announce( error instanceof Error ? error.message : ( i18n.error || 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.' ) );
				} );
			}

			return;
		}

		const decrement = target.closest( '[data-shanelle-mini-cart-decrement]' );

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

		const increment = target.closest( '[data-shanelle-mini-cart-increment]' );

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

	panel.addEventListener( 'change', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof HTMLInputElement ) || ! target.matches( '[data-shanelle-mini-cart-quantity]' ) ) {
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
 * Bind global open/close triggers.
 */
function bindGlobalEvents() {
	document.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const openTrigger = target.closest( '[data-shanelle-mini-cart-open]' );

		if ( openTrigger ) {
			event.preventDefault();

			if ( isBusy ) {
				return;
			}

			openMiniCart( { trigger: openTrigger } );
			return;
		}

		const closeTrigger = target.closest( '[data-shanelle-mini-cart-close], [data-shanelle-mini-cart-overlay]' );

		if ( closeTrigger && isOpen ) {
			event.preventDefault();
			closeMiniCart();
		}
	} );

	document.addEventListener( 'keydown', handleGlobalKeydown );

	document.body.addEventListener( 'shanelle:added_to_cart', ( event ) => {
		if ( ! ( event instanceof CustomEvent ) ) {
			return;
		}

		const detail = event.detail ?? {};

		refreshMiniCart().then( () => {
			if ( detail.openMiniCart ) {
				openMiniCart( { source: 'added_to_cart' } );
			}
		} ).catch( () => {
			// Ignore refresh errors; add-to-cart already succeeded.
		} );

		if ( detail.data?.fragments ) {
			applyWooFragments( detail.data.fragments );
		}
	} );
}

/**
 * Initialize the mini cart component.
 *
 * @param {HTMLElement|null} [element]
 */
function initMiniCart( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-mini-cart]' );
	panel = root?.querySelector( '[data-shanelle-mini-cart-panel]' ) ?? null;

	if ( ! root || ! panel || root.dataset.miniCartHydrated === 'true' ) {
		return;
	}

	root.dataset.miniCartHydrated = 'true';

	if ( config.initialState ) {
		setCartState( config.initialState );
		updateTitleCount( config.initialState );
	}

	bindPanelEvents();
	bindGlobalEvents();

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:mini-cart:ready', {
			bubbles: true,
			detail: {
				root,
				panel,
				state: getCartState(),
				api: {
					openMiniCart,
					closeMiniCart,
					toggleMiniCart,
					refreshMiniCart,
					updateCartItemQuantity,
					removeCartItem,
					getMiniCartState: getCartState,
					applyCartResponse,
				},
			},
		} )
	);
}

function bootMiniCart() {
	initMiniCart();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', bootMiniCart );
} else {
	bootMiniCart();
}

export {
	initMiniCart,
	openMiniCart,
	closeMiniCart,
	toggleMiniCart,
	refreshMiniCart,
	updateCartItemQuantity,
	removeCartItem,
	getCartState as getMiniCartState,
	applyCartResponse,
	applyFragments,
};
