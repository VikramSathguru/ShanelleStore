/**
 * Hide the sticky header on scroll-down; reveal on scroll-up (homepage only).
 *
 * @package Shanelle
 */

const SCROLL_DELTA = 8;
const SHOW_AT_TOP = 24;

/**
 * @param {HTMLElement} header
 * @returns {boolean}
 */
function isHomepage() {
	return document.body.classList.contains( 'home' )
		|| document.body.classList.contains( 'front-page' );
}

/**
 * @param {HTMLElement} header
 * @returns {boolean}
 */
function shouldStayVisible( header ) {
	const drawer = header.querySelector( '[data-mobile-drawer]' );

	if ( drawer instanceof HTMLElement && drawer.classList.contains( 'is-open' ) ) {
		return true;
	}

	const openPanel = header.querySelector( '[data-category-navbar-panel]:not([hidden])' );

	return openPanel instanceof HTMLElement;
}

/**
 * @param {HTMLElement} [header]
 */
function initAutoHideHeader( header ) {
	if ( ! isHomepage() ) {
		return;
	}

	const root = header instanceof HTMLElement
		? header
		: document.querySelector( '[data-header]' );

	if ( ! ( root instanceof HTMLElement ) ) {
		return;
	}

	root.setAttribute( 'data-auto-hide', 'true' );

	let lastY = window.scrollY;
	let ticking = false;

	const apply = () => {
		const y = window.scrollY;
		const delta = y - lastY;

		if ( y <= SHOW_AT_TOP || shouldStayVisible( root ) ) {
			root.classList.remove( 'is-hidden' );
		} else if ( delta > SCROLL_DELTA ) {
			root.classList.add( 'is-hidden' );
		} else if ( delta < -SCROLL_DELTA ) {
			root.classList.remove( 'is-hidden' );
		}

		lastY = y;
		ticking = false;
	};

	window.addEventListener(
		'scroll',
		() => {
			if ( ticking ) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame( apply );
		},
		{ passive: true }
	);
}

export { initAutoHideHeader };
