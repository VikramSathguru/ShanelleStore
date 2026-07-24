/**
 * Header category navbar interactions.
 *
 * @package Shanelle
 */

const config = window.shanelleCategoryNavbar ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} root
 */
function initScrollControl( root ) {
	const list = root.querySelector( '[data-category-navbar-list]' );
	const nextButton = root.querySelector( '[data-category-navbar-next]' );
	const prevButton = root.querySelector( '[data-category-navbar-prev]' );

	if ( ! ( list instanceof HTMLElement ) ) {
		return;
	}

	const updateScrollControl = () => {
		const canScroll = list.scrollWidth > list.clientWidth + 1;
		const atStart = list.scrollLeft <= 1;
		const atEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth - 1;

		if ( nextButton instanceof HTMLButtonElement ) {
			nextButton.hidden = ! canScroll || atEnd;
		}

		if ( prevButton instanceof HTMLButtonElement ) {
			prevButton.hidden = ! canScroll || atStart;
		}
	};

	/**
	 * @param {number} direction
	 */
	const scrollByDirection = ( direction ) => {
		const offset = Math.max( list.clientWidth * 0.75, 160 );
		const behavior = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';

		list.scrollBy( {
			left: offset * direction,
			behavior,
		} );
	};

	nextButton?.addEventListener( 'click', () => {
		scrollByDirection( 1 );
	} );

	prevButton?.addEventListener( 'click', () => {
		scrollByDirection( -1 );
	} );

	list.addEventListener( 'scroll', updateScrollControl, { passive: true } );
	window.addEventListener( 'resize', updateScrollControl );

	updateScrollControl();
}

/**
 * @param {HTMLElement} root
 */
function initDropdown( root ) {
	const toggle = root.querySelector( '[data-category-navbar-toggle]' );
	const panel = root.querySelector( '[data-category-navbar-panel]' );

	if ( ! ( toggle instanceof HTMLButtonElement ) || ! ( panel instanceof HTMLElement ) ) {
		return;
	}

	const close = () => {
		panel.hidden = true;
		toggle.setAttribute( 'aria-expanded', 'false' );
	};

	const open = () => {
		panel.hidden = false;
		toggle.setAttribute( 'aria-expanded', 'true' );
	};

	toggle.addEventListener( 'click', () => {
		if ( panel.hidden ) {
			open();
			return;
		}

		close();
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( panel.hidden ) {
			return;
		}

		const target = event.target;

		if ( target instanceof Node && root.contains( target ) ) {
			return;
		}

		close();
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && ! panel.hidden ) {
			close();
			toggle.focus();
		}
	} );
}

/**
 * @param {HTMLElement} root
 */
function initCategoryNavbar( root ) {
	initScrollControl( root );
	initDropdown( root );

	root.dispatchEvent(
		new CustomEvent( 'shanelle:category-navbar:ready', {
			bubbles: true,
			detail: {
				root,
				i18n,
			},
		} )
	);
}

document.querySelectorAll( '[data-category-navbar]' ).forEach( ( root ) => {
	if ( root instanceof HTMLElement ) {
		initCategoryNavbar( root );
	}
} );
