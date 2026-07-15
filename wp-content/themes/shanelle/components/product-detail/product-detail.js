/**
 * Shanelle Product Detail Page
 *
 * @package Shanelle
 */

const config = window.shanelleProductDetail ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} detail
 * @returns {Record<string, unknown>}
 */
function getDetailData( detail ) {
	try {
		return JSON.parse( detail.dataset.detailJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} detail
 * @returns {Array<HTMLElement>}
 */
function getHydrationTargets( detail ) {
	return Array.from( detail.querySelectorAll( '[data-shanelle-detail-hydrate]' ) );
}

/**
 * @param {HTMLButtonElement} trigger
 */
function toggleAccordionPanel( trigger ) {
	const panelId = trigger.getAttribute( 'aria-controls' );
	const panel = panelId ? document.getElementById( panelId ) : null;
	const accordion = trigger.closest( '[data-shanelle-detail-accordion]' );
	const expanded = trigger.getAttribute( 'aria-expanded' ) === 'true';

	if ( ! ( panel instanceof HTMLElement ) || ! accordion ) {
		return;
	}

	accordion.querySelectorAll( '[data-shanelle-detail-accordion-trigger]' ).forEach( ( otherTrigger ) => {
		if ( ! ( otherTrigger instanceof HTMLButtonElement ) || otherTrigger === trigger ) {
			return;
		}

		const otherPanelId = otherTrigger.getAttribute( 'aria-controls' );
		const otherPanel = otherPanelId ? document.getElementById( otherPanelId ) : null;

		otherTrigger.setAttribute( 'aria-expanded', 'false' );
		otherTrigger.setAttribute(
			'aria-label',
			`${ otherTrigger.textContent?.trim() || '' } — ${ i18n.expandSection || 'Expandir sección' }`
		);

		if ( otherPanel instanceof HTMLElement ) {
			otherPanel.hidden = true;
		}
	} );

	trigger.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
	trigger.setAttribute(
		'aria-label',
		`${ trigger.textContent?.trim() || '' } — ${ expanded ? i18n.expandSection || 'Expandir sección' : i18n.collapseSection || 'Contraer sección' }`
	);
	panel.hidden = expanded;

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-detail:accordion-toggle', {
			bubbles: true,
			detail: {
				trigger,
				panel,
				expanded: ! expanded,
			},
		} )
	);
}

/**
 * @param {HTMLElement} detail
 */
function initAccordion( detail ) {
	detail.querySelectorAll( '[data-shanelle-detail-accordion-trigger]' ).forEach( ( trigger ) => {
		if ( ! ( trigger instanceof HTMLButtonElement ) ) {
			return;
		}

		trigger.addEventListener( 'click', () => {
			toggleAccordionPanel( trigger );
		} );
	} );
}

/**
 * @param {HTMLElement} detail
 */
function initProductDetail( detail ) {
	if ( detail.dataset.detailHydrated === 'true' ) {
		return;
	}

	detail.dataset.detailHydrated = 'true';

	initAccordion( detail );

	const data = getDetailData( detail );
	const sections = getHydrationTargets( detail );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-detail:ready', {
			bubbles: true,
			detail: {
				detail,
				data,
				sections,
				hydrationTargets: {
					information: detail.querySelector( '[data-shanelle-detail-section="information"]' ),
					reviews: detail.querySelector( '[data-shanelle-detail-reviews]' ),
					related: detail.querySelector( '[data-shanelle-detail-related]' ),
					recentlyViewed: detail.querySelector( '[data-shanelle-detail-recently-viewed]' ),
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-detail]' ).forEach( initProductDetail );

export {
	initProductDetail,
	getDetailData,
	getHydrationTargets,
	toggleAccordionPanel,
};
