/**
 * Publish sticky site-header height for dependent sticky UI (PLP toolbar).
 *
 * @package Shanelle
 */

/**
 * @param {HTMLElement} header
 */
function publishHeaderOffset( header ) {
	const height = Math.ceil( header.getBoundingClientRect().height );

	if ( height <= 0 ) {
		return;
	}

	document.documentElement.style.setProperty( '--site-header-sticky-height', `${ height }px` );
}

/**
 * @param {HTMLElement} [header]
 */
function initStickyHeaderOffset( header ) {
	const root = header instanceof HTMLElement
		? header
		: document.querySelector( '[data-header]' );

	if ( ! ( root instanceof HTMLElement ) ) {
		return;
	}

	const update = () => {
		publishHeaderOffset( root );
	};

	update();

	if ( typeof ResizeObserver !== 'undefined' ) {
		const observer = new ResizeObserver( update );
		observer.observe( root );
	}

	window.addEventListener( 'resize', update, { passive: true } );
	window.addEventListener( 'orientationchange', update, { passive: true } );
}

export { initStickyHeaderOffset, publishHeaderOffset };
