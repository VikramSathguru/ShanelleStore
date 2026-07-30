/**
 * Stadium-style promotion banner.
 *
 * When promo items overflow the viewport, duplicates the track and runs a
 * seamless horizontal marquee. When they fit, stays static and centered.
 *
 * @package Shanelle
 */

/**
 * @param {HTMLElement} root
 */
function clearClones( root ) {
	root.querySelectorAll( '[data-promo-clone]' ).forEach( ( node ) => {
		node.remove();
	} );
}

/**
 * @param {HTMLElement} track
 * @param {HTMLElement} viewport
 * @param {number} speed
 */
function enableMarquee( track, viewport, speed ) {
	const originals = Array.from( track.children );

	const gap = document.createElement( 'span' );
	gap.className = 'site-header__promo-sep';
	gap.setAttribute( 'aria-hidden', 'true' );
	gap.dataset.promoClone = 'true';
	track.appendChild( gap );

	// Distance of one loop: originals + separator before the duplicate sequence.
	const distance = track.scrollWidth;

	originals.forEach( ( child ) => {
		const clone = child.cloneNode( true );

		if ( !( clone instanceof HTMLElement ) ) {
			return;
		}

		clone.dataset.promoClone = 'true';
		clone.setAttribute( 'aria-hidden', 'true' );

		clone.querySelectorAll( 'a' ).forEach( ( link ) => {
			link.setAttribute( 'tabindex', '-1' );
		} );

		track.appendChild( clone );
	} );

	const duration = Math.max( distance / Math.max( speed, 1 ), 8 );

	track.style.setProperty( '--promo-marquee-distance', `${ distance }px` );
	track.style.setProperty( '--promo-marquee-duration', `${ duration }s` );
	track.classList.add( 'is-marquee' );
	viewport.closest( '[data-shanelle-promo-banner]' )?.classList.remove( 'is-static' );
}

/**
 * @param {HTMLElement} root
 */
function layoutPromoBanner( root ) {
	const viewport = root.querySelector( '[data-shanelle-promo-viewport]' );
	const track = root.querySelector( '[data-shanelle-promo-track]' );

	if ( !( viewport instanceof HTMLElement ) || !( track instanceof HTMLElement ) ) {
		return;
	}

	clearClones( track );
	track.classList.remove( 'is-marquee' );
	track.style.removeProperty( '--promo-marquee-distance' );
	track.style.removeProperty( '--promo-marquee-duration' );

	// Measure as a single unwrapped row (is-static wraps and caps width).
	root.classList.remove( 'is-static' );

	const prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const speed = Number.parseInt( root.dataset.promoSpeed || '40', 10 ) || 40;

	// Force layout so scrollWidth is accurate after clearing clones.
	void track.offsetWidth;

	const overflows = track.scrollWidth > viewport.clientWidth + 2;

	if ( prefersReducedMotion || ! overflows ) {
		root.classList.add( 'is-static' );
		return;
	}

	enableMarquee( track, viewport, speed );
}

/**
 * @param {HTMLElement|null} header
 */
export function initPromoBanner( header = null ) {
	const scope = header ?? document;
	const roots = scope.querySelectorAll( '[data-shanelle-promo-banner]' );

	roots.forEach( ( root ) => {
		if ( !( root instanceof HTMLElement ) || root.dataset.promoHydrated === 'true' ) {
			return;
		}

		root.dataset.promoHydrated = 'true';

		const schedule = () => {
			window.requestAnimationFrame( () => layoutPromoBanner( root ) );
		};

		schedule();

		if ( typeof ResizeObserver !== 'undefined' ) {
			const observer = new ResizeObserver( schedule );
			observer.observe( root );
		} else {
			window.addEventListener( 'resize', schedule, { passive: true } );
		}

		document.fonts?.ready?.then( schedule ).catch( () => {} );
	} );
}
