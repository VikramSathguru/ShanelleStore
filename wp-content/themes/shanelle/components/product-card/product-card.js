/**
 * Shanelle Product Card Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductCard ?? {};
const i18n = config.i18n ?? {};
const WISHLIST_STORAGE_KEY = 'shanelle_wishlist';

/**
 * @param {HTMLElement} card
 */
function markTouchCard( card ) {
	if ( window.matchMedia( '(hover: none), (pointer: coarse)' ).matches ) {
		card.classList.add( 'is-touch' );
	}
}

/**
 * @param {HTMLElement} card
 * @param {string} message
 */
function announce( card, message ) {
	const live = card.querySelector( '[data-shanelle-card-live]' );

	if ( live ) {
		live.textContent = message;
	}
}

/**
 * @returns {number[]}
 */
function getWishlistIds() {
	try {
		const parsed = JSON.parse( localStorage.getItem( WISHLIST_STORAGE_KEY ) || '[]' );
		return Array.isArray( parsed ) ? parsed.map( Number ).filter( Number.isFinite ) : [];
	} catch ( error ) {
		return [];
	}
}

/**
 * @param {number[]} ids
 */
function saveWishlistIds( ids ) {
	localStorage.setItem( WISHLIST_STORAGE_KEY, JSON.stringify( ids ) );
}

/**
 * @param {HTMLButtonElement} button
 * @param {boolean} isActive
 */
function setWishlistButtonState( button, isActive ) {
	const productName = button.closest( '[data-shanelle-product-card]' )?.querySelector( '.product-card__title a' )?.textContent?.trim() || '';

	button.classList.toggle( 'is-active', isActive );
	button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );

	if ( productName ) {
		button.setAttribute(
			'aria-label',
			isActive
				? ( i18n.removeFromWishlist || 'Quitar de favoritos' ) + ': ' + productName
				: ( i18n.addToWishlist || 'Agregar a favoritos' ) + ': ' + productName
		);
	} else {
		button.setAttribute(
			'aria-label',
			isActive ? ( i18n.removeFromWishlist || 'Quitar de favoritos' ) : ( i18n.addToWishlist || 'Agregar a favoritos' )
		);
	}
}

/**
 * @param {HTMLElement} card
 */
function syncCardWishlist( card ) {
	const button = card.querySelector( '[data-shanelle-card-wishlist]' );

	if ( ! ( button instanceof HTMLButtonElement ) ) {
		return;
	}

	const productId = Number( button.dataset.productId || card.dataset.productId || 0 );
	setWishlistButtonState( button, getWishlistIds().includes( productId ) );
}

/**
 * Sync all visible card wishlist buttons (and keep PDP in sync via shared event).
 *
 * @param {number[]} [ids]
 */
function syncAllCardWishlists( ids ) {
	const wishlistIds = Array.isArray( ids ) ? ids : getWishlistIds();

	document.querySelectorAll( '[data-shanelle-card-wishlist]' ).forEach( ( button ) => {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		const productId = Number( button.dataset.productId || 0 );
		setWishlistButtonState( button, wishlistIds.includes( productId ) );
	} );
}

/**
 * @param {HTMLButtonElement} button
 */
function toggleCardWishlist( button ) {
	const card = button.closest( '[data-shanelle-product-card]' );
	const productId = Number( button.dataset.productId || card?.dataset.productId || 0 );

	if ( ! productId ) {
		return;
	}

	const ids = getWishlistIds();
	const index = ids.indexOf( productId );
	const wasActive = index >= 0;

	if ( wasActive ) {
		ids.splice( index, 1 );
	} else {
		ids.push( productId );
	}

	saveWishlistIds( ids );
	setWishlistButtonState( button, ! wasActive );

	if ( card instanceof HTMLElement ) {
		announce(
			card,
			wasActive ? ( i18n.removedFromWishlist || 'Eliminado de favoritos' ) : ( i18n.addedToWishlist || 'Agregado a favoritos' )
		);
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:wishlist:change', {
			bubbles: true,
			detail: {
				productId,
				isActive: ! wasActive,
				wishlistIds: ids,
			},
		} )
	);
}

/**
 * @param {Record<string, string>} fragments
 */
function applyWooFragments( fragments ) {
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
 * @param {HTMLButtonElement} button
 */
async function quickAddToCart( button ) {
	const card = button.closest( '[data-shanelle-product-card]' );
	const productId = button.dataset.productId;

	if ( ! card || ! productId || ! config.ajaxUrl ) {
		return;
	}

	const endpoint = config.ajaxUrl.replace( '%%endpoint%%', 'add_to_cart' );

	button.classList.add( 'is-loading' );
	button.disabled = true;

	try {
		const body = new URLSearchParams( {
			product_id: productId,
			quantity: '1',
		} );

		const response = await fetch( endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
			credentials: 'same-origin',
		} );

		if ( ! response.ok ) {
			throw new Error( 'Add to cart failed' );
		}

		const data = await response.json();

		if ( data.error ) {
			throw new Error( data.error );
		}

		applyWooFragments( data.fragments );
		announce( card, i18n.added || 'Agregado a la bolsa' );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:added_to_cart', {
				bubbles: true,
				detail: { productId, data },
			} )
		);
	} catch ( error ) {
		announce( card, i18n.error || 'No se pudo agregar a la bolsa' );
	} finally {
		button.classList.remove( 'is-loading' );
		button.disabled = false;
	}
}

/**
 * @param {HTMLElement} card
 */
function initCard( card ) {
	if ( card.dataset.shanelleCardReady === 'true' ) {
		return;
	}

	card.dataset.shanelleCardReady = 'true';

	markTouchCard( card );

	if ( card.querySelector( '.badge--sold-out' ) ) {
		card.classList.add( 'is-sold-out' );
	}

	syncCardWishlist( card );

	const wishlist = card.querySelector( '[data-shanelle-card-wishlist]' );

	if ( wishlist instanceof HTMLButtonElement ) {
		wishlist.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			event.stopPropagation();
			toggleCardWishlist( wishlist );
		} );
	}

	const quickAdd = card.querySelector( '[data-shanelle-quick-add]' );

	if ( quickAdd instanceof HTMLButtonElement ) {
		quickAdd.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			quickAddToCart( quickAdd );
		} );
	}

	const actions = card.querySelectorAll( '.product-card__action' );

	actions.forEach( ( action, index ) => {
		action.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'ArrowDown' && event.key !== 'ArrowUp' ) {
				return;
			}

			event.preventDefault();

			const next = event.key === 'ArrowDown'
				? actions[ index + 1 ] ?? actions[ 0 ]
				: actions[ index - 1 ] ?? actions[ actions.length - 1 ];

			if ( next instanceof HTMLElement ) {
				next.focus();
			}
		} );
	} );
}

document.querySelectorAll( '[data-shanelle-product-card]' ).forEach( initCard );

document.body.addEventListener( 'shanelle:wishlist:change', ( event ) => {
	const ids = event.detail?.wishlistIds;
	syncAllCardWishlists( Array.isArray( ids ) ? ids.map( Number ) : undefined );
} );

const observer = new MutationObserver( ( mutations ) => {
	mutations.forEach( ( mutation ) => {
		mutation.addedNodes.forEach( ( node ) => {
			if ( ! ( node instanceof HTMLElement ) ) {
				return;
			}

			if ( node.matches( '[data-shanelle-product-card]' ) ) {
				initCard( node );
			}

			node.querySelectorAll( '[data-shanelle-product-card]' ).forEach( initCard );
		} );
	} );
} );

observer.observe( document.body, { childList: true, subtree: true } );

export { quickAddToCart, initCard, toggleCardWishlist };
