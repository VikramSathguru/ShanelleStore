/**
 * Shanelle Related Products Component
 *
 * @package Shanelle
 */

/**
 * @param {HTMLElement} section
 * @returns {Record<string, unknown>}
 */
function getRecommendationData( section ) {
	try {
		return JSON.parse( section.dataset.recommendationJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} section
 */
function initProductRelated( section ) {
	if ( section.dataset.relatedHydrated === 'true' ) {
		return;
	}

	section.dataset.relatedHydrated = 'true';

	const data = getRecommendationData( section );
	const grid = section.querySelector( '[data-shanelle-product-grid]' );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:related-products:ready', {
			bubbles: true,
			detail: {
				section,
				grid,
				data,
				sourceProductId: Number( section.dataset.sourceProductId || data.sourceProductId || 0 ),
				items: Array.isArray( data.items ) ? data.items : [],
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-related]' ).forEach( initProductRelated );

export { initProductRelated, getRecommendationData };
