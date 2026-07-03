/**
 * Shanelle Product Gallery Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductGallery ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} gallery
 * @returns {Array<Record<string, unknown>>}
 */
function getItems( gallery ) {
	try {
		return JSON.parse( gallery.dataset.galleryItems || '[]' );
	} catch ( error ) {
		return [];
	}
}

/**
 * @param {HTMLElement} gallery
 * @param {number} index
 */
function announce( gallery, index, total ) {
	const status = gallery.querySelector( '[data-shanelle-gallery-status]' );

	if ( ! status ) {
		return;
	}

	const template = i18n.imageOf || 'Image %1$d of %2$d';
	status.textContent = template.replace( '%1$d', String( index + 1 ) ).replace( '%2$d', String( total ) );
}

/**
 * @param {HTMLElement} gallery
 * @param {number} index
 */
function setActiveIndex( gallery, index ) {
	const items = getItems( gallery );

	if ( ! items.length ) {
		return;
	}

	const total = items.length;
	const nextIndex = ( index + total ) % total;
	const item = items[ nextIndex ];

	const main = gallery.querySelector( '[data-shanelle-gallery-main]' );
	const modalImage = gallery.querySelector( '[data-shanelle-gallery-modal-image]' );
	const thumbs = gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' );

	if ( main && item ) {
		main.src = item.src;
		main.srcset = item.srcset || '';
		main.sizes = item.sizes || '';
		main.width = item.width;
		main.height = item.height;
		main.alt = item.alt || '';
		main.dataset.index = String( nextIndex );
	}

	if ( modalImage && item ) {
		modalImage.src = item.full_src || item.src;
		modalImage.alt = item.alt || '';
	}

	thumbs.forEach( ( thumb, thumbIndex ) => {
		const isActive = thumbIndex === nextIndex;
		thumb.classList.toggle( 'is-active', isActive );
		thumb.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		thumb.tabIndex = isActive ? 0 : -1;
	} );

	gallery.dataset.activeIndex = String( nextIndex );
	announce( gallery, nextIndex, total );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-gallery:change', {
			bubbles: true,
			detail: { gallery, index: nextIndex, item },
		} )
	);
}

/**
 * @param {HTMLElement} gallery
 * @param {number} delta
 */
function step( gallery, delta ) {
	const items = getItems( gallery );

	if ( items.length <= 1 ) {
		return;
	}

	const current = Number( gallery.dataset.activeIndex || 0 );
	setActiveIndex( gallery, current + delta );
}

/**
 * @param {HTMLElement} gallery
 */
function initKeyboard( gallery ) {
	gallery.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'ArrowLeft' ) {
			event.preventDefault();
			step( gallery, -1 );
		}

		if ( event.key === 'ArrowRight' ) {
			event.preventDefault();
			step( gallery, 1 );
		}
	} );

	gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' ).forEach( ( thumb ) => {
		thumb.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' ) {
				return;
			}

			event.preventDefault();
			const buttons = Array.from( gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' ) );
			const currentIndex = buttons.indexOf( thumb );
			const next = event.key === 'ArrowRight'
				? buttons[ currentIndex + 1 ] ?? buttons[ 0 ]
				: buttons[ currentIndex - 1 ] ?? buttons[ buttons.length - 1 ];

			if ( next instanceof HTMLElement ) {
				next.focus();
				setActiveIndex( gallery, Number( next.dataset.index || 0 ) );
			}
		} );
	} );
}

/**
 * @param {HTMLElement} gallery
 */
function initThumbs( gallery ) {
	gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' ).forEach( ( thumb ) => {
		thumb.addEventListener( 'click', () => {
			setActiveIndex( gallery, Number( thumb.dataset.index || 0 ) );
		} );
	} );
}

/**
 * @param {HTMLElement} gallery
 */
function initNavigation( gallery ) {
	gallery.querySelector( '[data-shanelle-gallery-prev]' )?.addEventListener( 'click', () => step( gallery, -1 ) );
	gallery.querySelector( '[data-shanelle-gallery-next]' )?.addEventListener( 'click', () => step( gallery, 1 ) );
	gallery.querySelector( '[data-shanelle-gallery-modal-prev]' )?.addEventListener( 'click', () => step( gallery, -1 ) );
	gallery.querySelector( '[data-shanelle-gallery-modal-next]' )?.addEventListener( 'click', () => step( gallery, 1 ) );
}

/**
 * @param {HTMLElement} gallery
 */
function initSwipe( gallery ) {
	const stage = gallery.querySelector( '[data-shanelle-gallery-stage]' );

	if ( ! stage ) {
		return;
	}

	let startX = 0;
	let tracking = false;

	stage.addEventListener( 'touchstart', ( event ) => {
		if ( event.touches.length !== 1 ) {
			return;
		}

		startX = event.touches[0].clientX;
		tracking = true;
		gallery.classList.add( 'is-swiping' );
	}, { passive: true } );

	stage.addEventListener( 'touchend', ( event ) => {
		if ( ! tracking ) {
			return;
		}

		const endX = event.changedTouches[0]?.clientX ?? startX;
		const delta = endX - startX;

		if ( Math.abs( delta ) > 48 ) {
			step( gallery, delta > 0 ? -1 : 1 );
		}

		tracking = false;
		gallery.classList.remove( 'is-swiping' );
	}, { passive: true } );
}

/**
 * @param {HTMLElement} gallery
 */
function initModal( gallery ) {
	const modal = gallery.querySelector( '[data-shanelle-gallery-modal]' );
	const panel = gallery.querySelector( '[data-shanelle-gallery-modal-panel]' );
	const openBtn = gallery.querySelector( '[data-shanelle-gallery-fullscreen]' );
	const closeBtn = gallery.querySelector( '[data-shanelle-gallery-modal-close]' );
	const overlay = gallery.querySelector( '[data-shanelle-gallery-modal-overlay]' );

	if ( ! modal || ! panel || ! openBtn ) {
		return;
	}

	const open = () => {
		const index = Number( gallery.dataset.activeIndex || 0 );
		setActiveIndex( gallery, index );
		modal.hidden = false;
		panel.focus();
		document.body.style.overflow = 'hidden';
	};

	const close = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
		openBtn.focus();
	};

	openBtn.addEventListener( 'click', open );
	closeBtn?.addEventListener( 'click', close );
	overlay?.addEventListener( 'click', close );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && ! modal.hidden ) {
			close();
		}
	} );
}

/**
 * @param {HTMLElement} gallery
 */
function initLazyThumbs( gallery ) {
	const wrap = gallery.querySelector( '[data-shanelle-gallery-thumbs-wrap]' );

	if ( ! wrap || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	const observer = new IntersectionObserver( ( entries, io ) => {
		entries.forEach( ( entry ) => {
			if ( ! entry.isIntersecting ) {
				return;
			}

			entry.target.querySelectorAll( '[data-shanelle-lazy-thumb]' ).forEach( ( img ) => {
				if ( img instanceof HTMLImageElement && img.dataset.src ) {
					img.src = img.dataset.src;
					delete img.dataset.src;
				}
			} );

			io.unobserve( entry.target );
		} );
	}, { rootMargin: '120px 0px' } );

	observer.observe( wrap );
}

/**
 * @param {HTMLElement} gallery
 */
function initGallery( gallery ) {
	if ( gallery.dataset.galleryHydrated === 'true' ) {
		return;
	}

	gallery.dataset.galleryHydrated = 'true';
	gallery.dataset.activeIndex = gallery.dataset.activeIndex || '0';

	initThumbs( gallery );
	initNavigation( gallery );
	initKeyboard( gallery );
	initSwipe( gallery );
	initModal( gallery );
	initLazyThumbs( gallery );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-gallery:ready', {
			bubbles: true,
			detail: { gallery },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-gallery]' ).forEach( initGallery );

export { initGallery, setActiveIndex, step };
