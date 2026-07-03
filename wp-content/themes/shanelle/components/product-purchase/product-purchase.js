/**
 * Shanelle Product Purchase Panel Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductPurchase ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} panel
 * @returns {Record<string, unknown>}
 */
function getPurchaseState( panel ) {
	try {
		return JSON.parse( panel.dataset.purchaseState || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} panel
 * @param {Record<string, unknown>} state
 */
function setPurchaseState( panel, state ) {
	panel.dataset.purchaseState = JSON.stringify( state );
}

/**
 * @param {HTMLElement} panel
 * @param {string} message
 */
function announce( panel, message ) {
	const status = panel.querySelector( '[data-shanelle-purchase-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} panel
 * @returns {HTMLFormElement|null}
 */
function findPurchaseForm( panel ) {
	const form = panel.closest( 'form.cart, form.variations_form' );

	return form instanceof HTMLFormElement ? form : document.querySelector( 'form.cart, form.variations_form' );
}

/**
 * @param {HTMLElement} panel
 * @returns {HTMLInputElement|null}
 */
function getQuantityInput( panel ) {
	const input = panel.querySelector( '[data-shanelle-purchase-quantity]' );

	return input instanceof HTMLInputElement ? input : null;
}

/**
 * @param {HTMLElement} panel
 * @returns {number}
 */
function getQuantity( panel ) {
	const input = getQuantityInput( panel );
	const state = getPurchaseState( panel );
	const min = Number( state.minQuantity ?? 1 );
	const value = Number( input?.value ?? min );

	return Number.isFinite( value ) && value > 0 ? value : min;
}

/**
 * @param {HTMLElement} panel
 * @param {number} quantity
 */
function setQuantity( panel, quantity ) {
	const input = getQuantityInput( panel );
	const state = getPurchaseState( panel );
	const min = Number( state.minQuantity ?? 1 );
	const max = Number( state.maxQuantity ?? 0 );
	let next = Math.max( min, Math.floor( quantity ) );

	if ( max > 0 ) {
		next = Math.min( next, max );
	}

	if ( input ) {
		input.value = String( next );
	}

	syncFormQuantity( panel, next );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-purchase:quantity-change', {
			bubbles: true,
			detail: {
				panel,
				quantity: next,
				productId: state.productId,
				variationId: state.variationId ?? 0,
			},
		} )
	);

	const template = i18n.quantityUpdated || 'Quantity updated to %d';
	announce( panel, template.replace( '%d', String( next ) ) );

	return next;
}

/**
 * @param {HTMLElement} panel
 * @param {number} quantity
 */
function syncFormQuantity( panel, quantity ) {
	const form = findPurchaseForm( panel );

	if ( ! form ) {
		return;
	}

	const formQty = form.querySelector( 'input.qty, input[name="quantity"]' );

	if ( formQty instanceof HTMLInputElement && formQty !== getQuantityInput( panel ) ) {
		formQty.value = String( quantity );
	}
}

/**
 * @param {HTMLElement} panel
 * @param {number} delta
 */
function stepQuantity( panel, delta ) {
	setQuantity( panel, getQuantity( panel ) + delta );
}

/**
 * @param {HTMLElement} panel
 * @param {Record<string, unknown>} stock
 */
function updateStockState( panel, stock ) {
	const state = getPurchaseState( panel );
	const nextState = {
		...state,
		isInStock: Boolean( stock.isInStock ?? stock.is_in_stock ?? true ),
		isOnBackorder: Boolean( stock.isOnBackorder ?? stock.is_on_backorder ?? false ),
		isLowStock: Boolean( stock.isLowStock ?? stock.is_low_stock ?? false ),
		stockStatus: String( stock.stockStatus ?? stock.stock_status ?? 'instock' ),
		stockLabel: String( stock.stockLabel ?? stock.stock_label ?? '' ),
		stockQuantity: stock.stockQuantity ?? stock.stock_quantity ?? null,
		canPurchase: Boolean( stock.canPurchase ?? stock.can_purchase ?? true ),
	};

	setPurchaseState( panel, nextState );
	updateNotices( panel, nextState );
	updateControls( panel, nextState );
	panel.classList.toggle( 'is-outofstock', ! nextState.isInStock && ! nextState.isOnBackorder );
}

/**
 * @param {HTMLElement} panel
 * @param {Record<string, unknown>} state
 */
function updateNotices( panel, state ) {
	const notices = panel.querySelector( '[data-shanelle-purchase-notices]' );

	if ( ! notices ) {
		return;
	}

	const showOut = ! state.isInStock && ! state.isOnBackorder;
	const showBackorder = Boolean( state.isOnBackorder );
	const showLow = Boolean( state.isLowStock ) && Boolean( state.isInStock );

	notices.querySelectorAll( '[data-shanelle-purchase-notice]' ).forEach( ( notice ) => {
		if ( ! ( notice instanceof HTMLElement ) ) {
			return;
		}

		const type = notice.dataset.shanellePurchaseNotice;
		let visible = false;

		if ( type === 'outofstock' ) {
			visible = showOut;
			if ( visible && state.stockLabel ) {
				notice.textContent = String( state.stockLabel );
			}
		}

		if ( type === 'backorder' ) {
			visible = showBackorder;
			if ( visible && state.stockLabel ) {
				notice.textContent = String( state.stockLabel );
			}
		}

		if ( type === 'lowstock' ) {
			visible = showLow;
			if ( visible ) {
				notice.textContent = state.stockQuantity
					? ( i18n.onlyLeft || 'Only %d left in stock' ).replace( '%d', String( state.stockQuantity ) )
					: String( state.stockLabel || i18n.lowStock || 'Low stock — order soon' );
			}
		}

		notice.hidden = ! visible;
	} );
}

/**
 * @param {HTMLElement} panel
 * @param {Record<string, unknown>} state
 */
function updateControls( panel, state ) {
	const addButton = panel.querySelector( '[data-shanelle-purchase-add]' );
	const stepperButtons = panel.querySelectorAll( '[data-shanelle-purchase-decrement], [data-shanelle-purchase-increment]' );
	const quantityInput = getQuantityInput( panel );
	const requiresVariation = Boolean( state.requiresVariation ?? state.requires_variation );
	const variationId = Number( state.variationId ?? state.variation_id ?? 0 );
	const canPurchase = Boolean( state.canPurchase ?? state.can_purchase );
	const purchasable = canPurchase && ( ! requiresVariation || variationId > 0 );
	const disabled = ! purchasable || ( ! state.isInStock && ! state.isOnBackorder );

	if ( addButton instanceof HTMLButtonElement ) {
		addButton.disabled = disabled;
		addButton.setAttribute( 'aria-disabled', disabled ? 'true' : 'false' );
	}

	stepperButtons.forEach( ( button ) => {
		if ( button instanceof HTMLButtonElement ) {
			button.disabled = disabled;
		}
	} );

	if ( quantityInput ) {
		quantityInput.disabled = disabled;
	}
}

/**
 * @param {HTMLElement} panel
 * @param {Record<string, unknown>|null} variation
 */
function applyVariationState( panel, variation ) {
	const state = getPurchaseState( panel );
	const variationId = variation ? Number( variation.variation_id ?? 0 ) : 0;
	const nextState = {
		...state,
		variationId,
	};

	if ( variation ) {
		nextState.isInStock = Boolean( variation.is_in_stock );
		nextState.isOnBackorder = Boolean( variation.is_on_backorder );
		nextState.isLowStock = String( variation.shanelle_stock_status || '' ) === 'lowstock';
		nextState.stockStatus = String( variation.shanelle_stock_status || 'instock' );
		nextState.stockLabel = String( variation.shanelle_stock_label || '' );
		nextState.canPurchase = Boolean( variation.is_purchasable );
		nextState.maxQuantity = Number( variation.max_qty ?? state.maxQuantity ?? 0 ) || state.maxQuantity;
	}

	setPurchaseState( panel, nextState );
	syncVariationId( panel, variationId );
	updateStockState( panel, nextState );
	updateControls( panel, nextState );
}

/**
 * @param {HTMLElement} panel
 * @param {number} variationId
 */
function syncVariationId( panel, variationId ) {
	const form = findPurchaseForm( panel );
	const input = form?.querySelector( 'input[name="variation_id"]' );

	if ( input instanceof HTMLInputElement ) {
		input.value = String( variationId || '' );
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
 * @param {HTMLFormElement} form
 * @returns {Record<string, string>}
 */
function collectVariationAttributes( form ) {
	/** @type {Record<string, string>} */
	const attributes = {};

	form.querySelectorAll( '[data-shanelle-variation-native] select, .variations select' ).forEach( ( select ) => {
		if ( select instanceof HTMLSelectElement && select.name && select.value ) {
			attributes[ select.name ] = select.value;
		}
	} );

	return attributes;
}

/**
 * @param {HTMLElement} panel
 * @returns {Promise<void>}
 */
async function addToCart( panel ) {
	const state = getPurchaseState( panel );
	const productId = Number( state.productId ?? state.product_id ?? 0 );
	const variationId = Number( state.variationId ?? state.variation_id ?? 0 );
	const quantity = getQuantity( panel );
	const requiresVariation = Boolean( state.requiresVariation ?? state.requires_variation );
	const addButton = panel.querySelector( '[data-shanelle-purchase-add]' );

	if ( ! productId || ! config.ajaxUrl ) {
		dispatchError( panel, i18n.error || 'Could not add to bag. Try again.' );
		return;
	}

	if ( requiresVariation && variationId <= 0 ) {
		dispatchError( panel, i18n.selectOptions || 'Select product options' );
		return;
	}

	if ( addButton instanceof HTMLButtonElement ) {
		addButton.classList.add( 'is-loading' );
		addButton.disabled = true;
	}

	/** @type {Record<string, string>} */
	const payload = {
		product_id: String( productId ),
		quantity: String( quantity ),
	};

	if ( variationId > 0 ) {
		payload.variation_id = String( variationId );

		const form = findPurchaseForm( panel );

		if ( form ) {
			Object.assign( payload, collectVariationAttributes( form ) );
		}
	}

	try {
		const endpoint = String( config.ajaxUrl ).replace( '%%endpoint%%', 'add_to_cart' );
		const body = new URLSearchParams( payload );
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
		announce( panel, i18n.added || 'Added to bag' );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:product-purchase:added', {
				bubbles: true,
				detail: {
					panel,
					productId,
					quantity,
					variationId,
					response: data,
				},
			} )
		);

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:added_to_cart', {
				bubbles: true,
				detail: {
					productId,
					quantity,
					variationId,
					data,
				},
			} )
		);
	} catch ( error ) {
		const message = error instanceof Error ? error.message : ( i18n.error || 'Could not add to bag. Try again.' );
		dispatchError( panel, message );
	} finally {
		if ( addButton instanceof HTMLButtonElement ) {
			addButton.classList.remove( 'is-loading' );
			updateControls( panel, getPurchaseState( panel ) );
		}
	}
}

/**
 * @param {HTMLElement} panel
 * @param {string} message
 */
function dispatchError( panel, message ) {
	announce( panel, message );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-purchase:error', {
			bubbles: true,
			detail: { panel, message },
		} )
	);
}

/**
 * @param {HTMLElement} panel
 */
function bindQuantityControls( panel ) {
	panel.querySelector( '[data-shanelle-purchase-decrement]' )?.addEventListener( 'click', () => {
		stepQuantity( panel, -1 );
	} );

	panel.querySelector( '[data-shanelle-purchase-increment]' )?.addEventListener( 'click', () => {
		stepQuantity( panel, 1 );
	} );

	const input = getQuantityInput( panel );

	if ( input ) {
		input.addEventListener( 'change', () => {
			setQuantity( panel, Number( input.value ) );
		} );

		input.addEventListener( 'blur', () => {
			setQuantity( panel, Number( input.value ) );
		} );
	}
}

/**
 * @param {HTMLElement} panel
 */
function bindPurchaseActions( panel ) {
	panel.querySelector( '[data-shanelle-purchase-add]' )?.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		addToCart( panel );
	} );
}

/**
 * @param {HTMLElement} panel
 */
function bindVariationEvents( panel ) {
	if ( panel.dataset.variationEventsBound === 'true' ) {
		return;
	}

	panel.dataset.variationEventsBound = 'true';

	const productId = Number( getPurchaseState( panel ).productId ?? 0 );

	document.body.addEventListener( 'shanelle:product-variations:change', ( event ) => {
		if ( ! ( event instanceof CustomEvent ) ) {
			return;
		}

		const detail = event.detail ?? {};
		const formProductId = Number( detail.form?.dataset?.product_id ?? detail.form?.querySelector( '[name="product_id"]' )?.value ?? 0 );

		if ( productId && formProductId && productId !== formProductId ) {
			return;
		}

		applyVariationState( panel, detail.variation ?? null );
	} );

	document.body.addEventListener( 'shanelle:product-variations:stock-change', ( event ) => {
		if ( ! ( event instanceof CustomEvent ) ) {
			return;
		}

		const detail = event.detail ?? {};
		const formProductId = Number( detail.root?.dataset?.productId ?? 0 );

		if ( productId && formProductId && productId !== formProductId ) {
			return;
		}

		updateStockState( panel, {
			isInStock: detail.variation ? Boolean( detail.variation.is_in_stock ) : getPurchaseState( panel ).isInStock,
			isOnBackorder: detail.variation ? Boolean( detail.variation.is_on_backorder ) : false,
			isLowStock: String( detail.stockStatus || '' ) === 'lowstock',
			stockStatus: detail.stockStatus,
			stockLabel: detail.stockLabel,
			canPurchase: detail.variation ? Boolean( detail.variation.is_purchasable ) : true,
		} );
	} );
}

/**
 * @param {HTMLElement} panel
 */
function initPurchase( panel ) {
	if ( panel.dataset.purchaseHydrated === 'true' ) {
		return;
	}

	panel.dataset.purchaseHydrated = 'true';

	const state = getPurchaseState( panel );
	panel.classList.toggle( 'is-outofstock', ! state.isInStock && ! state.isOnBackorder );

	bindQuantityControls( panel );
	bindPurchaseActions( panel );
	bindVariationEvents( panel );
	updateNotices( panel, state );
	updateControls( panel, state );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-purchase:ready', {
			bubbles: true,
			detail: { panel, state },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-purchase]' ).forEach( initPurchase );

export {
	initPurchase,
	getPurchaseState,
	setPurchaseState,
	setQuantity,
	getQuantity,
	stepQuantity,
	addToCart,
	updateStockState,
	applyVariationState,
	syncVariationId,
	applyWooFragments,
	findPurchaseForm,
};
