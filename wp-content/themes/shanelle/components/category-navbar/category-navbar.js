/**
 * Header category navbar — scroll controls + desktop hover mega-menu.
 *
 * @package Shanelle
 */

const config = window.shanelleCategoryNavbar ?? {};
const i18n = config.i18n ?? {};

const OPEN_DELAY = 90;
const CLOSE_DELAY = 180;
const DESKTOP_QUERY = '(min-width: 64rem)';

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
function initMegaMenu( root ) {
	const mega = root.querySelector( '[data-category-navbar-mega]' );
	const backdrop = root.querySelector( '[data-category-navbar-backdrop]' );
	const triggers = Array.from( root.querySelectorAll( '[data-category-navbar-trigger]' ) );
	const sidebarButtons = Array.from( root.querySelectorAll( '[data-category-navbar-sidebar]' ) );
	const panes = Array.from( root.querySelectorAll( '[data-category-navbar-pane]' ) );
	const desktopMq = window.matchMedia( DESKTOP_QUERY );

	if ( ! ( mega instanceof HTMLElement ) || triggers.length === 0 || panes.length === 0 ) {
		return;
	}

	/** @type {ReturnType<typeof setTimeout> | null} */
	let openTimer = null;
	/** @type {ReturnType<typeof setTimeout> | null} */
	let closeTimer = null;
	/** @type {string | null} */
	let activeId = null;

	const clearTimers = () => {
		if ( openTimer ) {
			clearTimeout( openTimer );
			openTimer = null;
		}

		if ( closeTimer ) {
			clearTimeout( closeTimer );
			closeTimer = null;
		}
	};

	/**
	 * @param {string} categoryId
	 */
	const setActivePane = ( categoryId ) => {
		activeId = categoryId;

		panes.forEach( ( pane ) => {
			if ( ! ( pane instanceof HTMLElement ) ) {
				return;
			}

			const isActive = pane.dataset.categoryId === categoryId;
			pane.hidden = ! isActive;
		} );

		sidebarButtons.forEach( ( button ) => {
			if ( ! ( button instanceof HTMLButtonElement ) ) {
				return;
			}

			const isActive = button.dataset.categoryId === categoryId;
			button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );

		triggers.forEach( ( trigger ) => {
			if ( ! ( trigger instanceof HTMLElement ) ) {
				return;
			}

			const item = trigger.closest( '.category-navbar__item--mega' );
			const isActive = trigger.dataset.categoryId === categoryId;

			trigger.setAttribute( 'aria-expanded', isActive && ! mega.hidden ? 'true' : 'false' );

			if ( item instanceof HTMLElement ) {
				item.classList.toggle( 'is-active', isActive && ! mega.hidden );
			}
		} );
	};

	/**
	 * @param {string} categoryId
	 */
	const open = ( categoryId ) => {
		if ( ! desktopMq.matches ) {
			return;
		}

		clearTimers();
		mega.hidden = false;
		requestAnimationFrame( () => {
			mega.classList.add( 'is-open' );
		} );
		document.documentElement.classList.add( 'has-category-mega-open' );
		setActivePane( categoryId );
	};

	const close = () => {
		clearTimers();
		mega.classList.remove( 'is-open' );
		document.documentElement.classList.remove( 'has-category-mega-open' );

		triggers.forEach( ( trigger ) => {
			if ( trigger instanceof HTMLElement ) {
				trigger.setAttribute( 'aria-expanded', 'false' );
				trigger.closest( '.category-navbar__item--mega' )?.classList.remove( 'is-active' );
			}
		} );

		sidebarButtons.forEach( ( button ) => {
			if ( button instanceof HTMLButtonElement ) {
				button.setAttribute( 'aria-pressed', 'false' );
			}
		} );

		const finishHide = () => {
			mega.hidden = true;
			activeId = null;
		};

		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			finishHide();
			return;
		}

		window.setTimeout( finishHide, 180 );
	};

	/**
	 * @param {string} categoryId
	 */
	const scheduleOpen = ( categoryId ) => {
		if ( ! desktopMq.matches ) {
			return;
		}

		clearTimers();
		openTimer = setTimeout( () => {
			open( categoryId );
		}, OPEN_DELAY );
	};

	const scheduleClose = () => {
		clearTimers();
		closeTimer = setTimeout( () => {
			close();
		}, CLOSE_DELAY );
	};

	triggers.forEach( ( trigger ) => {
		if ( ! ( trigger instanceof HTMLElement ) ) {
			return;
		}

		const categoryId = trigger.dataset.categoryId ?? '';

		if ( ! categoryId ) {
			return;
		}

		trigger.addEventListener( 'pointerenter', () => {
			scheduleOpen( categoryId );
		} );

		trigger.addEventListener( 'pointerleave', () => {
			scheduleClose();
		} );

		trigger.addEventListener( 'focus', () => {
			scheduleOpen( categoryId );
		} );

		trigger.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' && ! mega.hidden ) {
				event.preventDefault();
				close();
				trigger.focus();
			}
		} );
	} );

	sidebarButtons.forEach( ( button ) => {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		const categoryId = button.dataset.categoryId ?? '';

		if ( ! categoryId ) {
			return;
		}

		button.addEventListener( 'pointerenter', () => {
			if ( mega.hidden ) {
				return;
			}

			clearTimers();
			setActivePane( categoryId );
		} );

		button.addEventListener( 'click', () => {
			clearTimers();
			setActivePane( categoryId );
		} );

		button.addEventListener( 'focus', () => {
			if ( mega.hidden ) {
				return;
			}

			clearTimers();
			setActivePane( categoryId );
		} );
	} );

	mega.addEventListener( 'pointerenter', () => {
		clearTimers();
	} );

	mega.addEventListener( 'pointerleave', () => {
		scheduleClose();
	} );

	backdrop?.addEventListener( 'click', () => {
		close();
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && ! mega.hidden ) {
			close();
		}
	} );

	const onViewportChange = () => {
		if ( ! desktopMq.matches && ! mega.hidden ) {
			close();
		}
	};

	if ( typeof desktopMq.addEventListener === 'function' ) {
		desktopMq.addEventListener( 'change', onViewportChange );
	} else {
		desktopMq.addListener( onViewportChange );
	}
}

/**
 * @param {HTMLElement} root
 */
function initCategoryNavbar( root ) {
	initScrollControl( root );
	initMegaMenu( root );

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
