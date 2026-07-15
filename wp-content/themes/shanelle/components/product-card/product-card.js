/**
 * Shanelle Product Card Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductCard ?? {};
const i18n = config.i18n ?? {};

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
	markTouchCard( card );

	if ( card.querySelector( '.badge--sold-out' ) ) {
		card.classList.add( 'is-sold-out' );
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

export { quickAddToCart, initCard };
