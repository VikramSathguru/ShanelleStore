/**
 * Shanelle About Page Script
 *
 * Hydration hook plus gentle section reveal. Exposes page state and a ready
 * event so future enhancements (PWA, analytics) can attach without template changes.
 *
 * @package Shanelle
 */

/** @type {HTMLElement|null} */
let root = null;

/**
 * @returns {Record<string, unknown>}
 */
function getAboutPageState() {
	if ( ! root ) {
		return {};
	}

	try {
		return JSON.parse( root.dataset.aboutState || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * Reveal sections as they enter the viewport.
 *
 * @param {HTMLElement} pageRoot
 */
function initReveal( pageRoot ) {
	const prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const targets = pageRoot.querySelectorAll( '[data-about-reveal]' );

	if ( prefersReducedMotion || typeof IntersectionObserver === 'undefined' ) {
		targets.forEach( ( element ) => {
			element.classList.add( 'is-visible' );
		} );
		return;
	}

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( ! entry.isIntersecting ) {
					return;
				}

				entry.target.classList.add( 'is-visible' );
				observer.unobserve( entry.target );
			} );
		},
		{
			rootMargin: '0px 0px -8% 0px',
			threshold: 0.12,
		}
	);

	targets.forEach( ( element, index ) => {
		if ( index === 0 ) {
			element.classList.add( 'is-visible' );
			return;
		}

		observer.observe( element );
	} );
}

/**
 * @param {HTMLElement|null} element
 */
function initAboutPage( element = null ) {
	root = element ?? document.querySelector( '[data-shanelle-about-page]' );

	if ( ! root || root.dataset.aboutHydrated === 'true' ) {
		return;
	}

	root.dataset.aboutHydrated = 'true';
	initReveal( root );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:about-page:ready', {
			bubbles: true,
			detail: {
				root,
				state: getAboutPageState(),
				api: {
					getAboutPageState,
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-about-page]' ).forEach( ( element ) => {
	if ( element instanceof HTMLElement ) {
		initAboutPage( element );
	}
} );

export {
	initAboutPage,
	getAboutPageState,
};
