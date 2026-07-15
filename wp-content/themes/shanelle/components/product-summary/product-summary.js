/**
 * Shanelle Product Summary Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductSummary ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} summary
 * @param {string} message
 */
function announce( summary, message ) {
	const status = summary.querySelector( '[data-shanelle-summary-status]' );

	if ( status ) {
		status.textContent = message;
	}
}

/**
 * @param {HTMLElement} summary
 * @param {Record<string, unknown>} price
 */
function applyPriceState( summary, price ) {
	const root = summary.querySelector( '[data-shanelle-summary-price]' );
	const current = summary.querySelector( '[data-shanelle-summary-price-current]' );
	const regular = summary.querySelector( '[data-shanelle-summary-price-regular]' );
	const savings = summary.querySelector( '[data-shanelle-summary-price-savings]' );

	if ( ! root || ! current ) {
		return;
	}

	root.classList.toggle( 'product-summary__price--on-sale', Boolean( price.isOnSale ) );
	root.classList.toggle( 'product-summary__price--range', Boolean( price.isRange ) );

	current.innerHTML = String( price.currentHtml ?? '' );

	if ( regular ) {
		regular.innerHTML = String( price.regularHtml ?? '' );
		regular.closest( '.product-summary__price-regular' )?.classList.toggle(
			'sr-only',
			! price.isOnSale || ! price.regularHtml
		);
	}

	if ( savings ) {
		savings.textContent = String( price.savingsHtml ?? '' );
		savings.closest( '.product-summary__price-savings' )?.classList.toggle(
			'sr-only',
			! price.isOnSale || ! price.savingsHtml
		);
	}
}

/**
 * @param {HTMLElement} summary
 * @returns {Record<string, unknown>}
 */
function getBasePrice( summary ) {
	const root = summary.querySelector( '[data-shanelle-summary-price]' );

	if ( ! root ) {
		return {};
	}

	try {
		return JSON.parse( root.dataset.priceJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} summary
 * @param {Record<string, unknown>} variation
 */
function updateFromVariation( summary, variation ) {
	if ( ! variation || typeof variation !== 'object' ) {
		return;
	}

	const price = {
		hasPrice: Boolean( variation.display_price ),
		isOnSale: Boolean( variation.shanelle_is_on_sale ),
		isRange: false,
		currentHtml: String( variation.shanelle_current_html || variation.price_html || '' ),
		regularHtml: String( variation.shanelle_regular_html || '' ),
		savingsHtml: String( variation.shanelle_savings_html || '' ),
		savingsPercent: 0,
	};

	applyPriceState( summary, price );
	announce( summary, i18n.priceUpdated || 'Precio actualizado' );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-summary:price-change', {
			bubbles: true,
			detail: { summary, price, variation },
		} )
	);
}

/**
 * @param {HTMLElement} summary
 */
function resetPrice( summary ) {
	applyPriceState( summary, getBasePrice( summary ) );
}

/**
 * @param {HTMLElement} summary
 */
function initVariationSync( summary ) {
	const form = document.querySelector( 'form.variations_form' );

	if ( ! form || typeof window.jQuery === 'undefined' ) {
		return;
	}

	window.jQuery( form )
		.on( 'found_variation', ( event, variation ) => {
			updateFromVariation( summary, variation );
		} )
		.on( 'reset_data', () => {
			resetPrice( summary );
		} );
}

/**
 * @param {HTMLElement} summary
 */
function initSummary( summary ) {
	if ( summary.dataset.summaryHydrated === 'true' ) {
		return;
	}

	summary.dataset.summaryHydrated = 'true';
	initVariationSync( summary );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-summary:ready', {
			bubbles: true,
			detail: { summary },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-summary]' ).forEach( initSummary );

export { initSummary, applyPriceState, updateFromVariation };
