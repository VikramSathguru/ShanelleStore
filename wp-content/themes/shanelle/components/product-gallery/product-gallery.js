/**
 * Shanelle Product Gallery Component
 *
 * @package Shanelle
 */

const config = window.shanelleProductGallery ?? {};
const i18n = config.i18n ?? {};

const CROSSFADE_MS = 320;
const ZOOM_FACTOR = 2.25;

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
 * @param {number} total
 */
function announce( gallery, index, total ) {
	const status = gallery.querySelector( '[data-shanelle-gallery-status]' );
	const current = index + 1;
	const template = i18n.imageOf || 'Imagen %1$d de %2$d';
	const label = template.replace( '%1$d', String( current ) ).replace( '%2$d', String( total ) );

	if ( status ) {
		status.textContent = label;
	}

	const positionCurrent = gallery.querySelector( '[data-shanelle-gallery-position-current]' );

	if ( positionCurrent ) {
		positionCurrent.textContent = String( current );
	}
}

/**
 * Scroll the active thumbnail into view within its strip (no page jump).
 *
 * @param {HTMLElement} thumb
 */
function scrollThumbIntoView( thumb ) {
	if ( ! ( thumb instanceof HTMLElement ) ) {
		return;
	}

	const wrap = thumb.closest( '[data-shanelle-gallery-thumbs-wrap]' );
	const list = thumb.closest( '[data-shanelle-gallery-thumbs]' );

	if ( ! ( wrap instanceof HTMLElement ) ) {
		return;
	}

	const reduceMotion = typeof window.matchMedia === 'function'
		&& window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const behavior = reduceMotion ? 'auto' : 'smooth';
	const isColumn = list instanceof HTMLElement
		&& window.getComputedStyle( list ).flexDirection === 'column';

	if ( isColumn && list instanceof HTMLElement && list.scrollHeight > list.clientHeight ) {
		const listRect = list.getBoundingClientRect();
		const thumbRect = thumb.getBoundingClientRect();
		const delta = ( thumbRect.top + thumbRect.height / 2 ) - ( listRect.top + listRect.height / 2 );

		list.scrollBy( { top: delta, behavior } );
		return;
	}

	if ( wrap.scrollWidth > wrap.clientWidth ) {
		const wrapRect = wrap.getBoundingClientRect();
		const thumbRect = thumb.getBoundingClientRect();
		const delta = ( thumbRect.left + thumbRect.width / 2 ) - ( wrapRect.left + wrapRect.width / 2 );

		wrap.scrollBy( { left: delta, behavior } );
	}
}

/**
 * @param {HTMLImageElement} image
 * @param {Record<string, unknown>} item
 * @param {number} index
 */
function applyImageData( image, item, index ) {
	image.src = item.src;
	image.srcset = item.srcset || '';
	image.sizes = item.sizes || '';
	image.width = item.width;
	image.height = item.height;
	image.alt = item.alt || '';
	image.dataset.index = String( index );
	image.dataset.fullSrc = item.full_src || item.src;
}

/**
 * @param {HTMLElement} gallery
 * @returns {HTMLImageElement|null}
 */
function getActiveImage( gallery ) {
	const stack = gallery.querySelector( '[data-shanelle-gallery-stack]' );
	const active = stack?.querySelector( '.product-gallery__image.is-active' );

	return active instanceof HTMLImageElement ? active : null;
}

/**
 * @param {HTMLElement} gallery
 * @param {Record<string, unknown>} item
 * @param {number} index
 */
function swapImageInstant( gallery, item, index ) {
	const main = getActiveImage( gallery ) || gallery.querySelector( '[data-shanelle-gallery-main]' );
	const modalImage = gallery.querySelector( '[data-shanelle-gallery-modal-image]' );

	if ( main ) {
		applyImageData( main, item, index );
	}

	if ( modalImage && item ) {
		modalImage.src = item.full_src || item.src;
		modalImage.alt = item.alt || '';
	}

	updateZoomSource( gallery );
}

/**
 * @param {HTMLElement} gallery
 * @param {Record<string, unknown>} item
 * @param {number} index
 * @returns {Promise<void>}
 */
function swapImageAnimated( gallery, item, index ) {
	const stack = gallery.querySelector( '[data-shanelle-gallery-stack]' );

	if ( ! stack ) {
		swapImageInstant( gallery, item, index );
		return Promise.resolve();
	}

	const current = getActiveImage( gallery );

	if ( ! current ) {
		swapImageInstant( gallery, item, index );
		return Promise.resolve();
	}

	if ( Number( current.dataset.index ) === index ) {
		return Promise.resolve();
	}

	let next = stack.querySelector( '.product-gallery__image:not(.is-active)' );

	if ( ! ( next instanceof HTMLImageElement ) ) {
		next = current.cloneNode( false );
		next.removeAttribute( 'data-shanelle-gallery-main' );
		next.classList.remove( 'is-active', 'is-leaving', 'is-entering' );
		stack.appendChild( next );
	}

	applyImageData( next, item, index );

	return new Promise( ( resolve ) => {
		const finish = () => {
			next.classList.add( 'is-active' );
			current.classList.remove( 'is-active' );
			current.classList.add( 'is-leaving' );

			requestAnimationFrame( () => {
				next.classList.add( 'is-entering' );
			} );

			window.setTimeout( () => {
				current.classList.remove( 'is-leaving', 'is-entering' );
				next.classList.remove( 'is-entering' );
				next.dataset.shanelleGalleryMain = '';
				current.removeAttribute( 'data-shanelle-gallery-main' );

				const modalImage = gallery.querySelector( '[data-shanelle-gallery-modal-image]' );

				if ( modalImage ) {
					modalImage.src = item.full_src || item.src;
					modalImage.alt = item.alt || '';
				}

				updateZoomSource( gallery );
				resolve();
			}, CROSSFADE_MS );
		};

		if ( next.complete ) {
			finish();
			return;
		}

		next.addEventListener( 'load', finish, { once: true } );
		next.addEventListener( 'error', finish, { once: true } );
	} );
}

/**
 * @param {HTMLElement} gallery
 * @param {number} index
 * @param {{ animate?: boolean, force?: boolean }} [options]
 */
function setActiveIndex( gallery, index, options = {} ) {
	const items = getItems( gallery );

	if ( ! items.length ) {
		return;
	}

	const total = items.length;
	const nextIndex = ( index + total ) % total;
	const currentIndex = Number( gallery.dataset.activeIndex || 0 );

	if ( nextIndex === currentIndex && gallery.dataset.activeIndex !== undefined && ! options.force ) {
		return;
	}

	const item = items[ nextIndex ];
	const thumbs = gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' );
	const animate = options.animate !== false && ! gallery.classList.contains( 'is-swiping' ) && ! options.force;

	const applySwap = () => {
		if ( animate ) {
			return swapImageAnimated( gallery, item, nextIndex );
		}

		swapImageInstant( gallery, item, nextIndex );
		return Promise.resolve();
	};

	applySwap().then( () => {
		thumbs.forEach( ( thumb, thumbIndex ) => {
			const isActive = thumbIndex === nextIndex;
			thumb.classList.toggle( 'is-active', isActive );
			thumb.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			thumb.tabIndex = isActive ? 0 : -1;
		} );

		const activeThumb = thumbs[ nextIndex ];

		if ( activeThumb instanceof HTMLElement ) {
			scrollThumbIntoView( activeThumb );
		}

		gallery.dataset.activeIndex = String( nextIndex );
		announce( gallery, nextIndex, total );

		document.body.dispatchEvent(
			new CustomEvent( 'shanelle:product-gallery:change', {
				bubbles: true,
				detail: { gallery, index: nextIndex, item },
			} )
		);
	} );
}

/**
 * Sync lightbox image from the current gallery item without requiring an index change.
 *
 * @param {HTMLElement} gallery
 */
function syncModalImage( gallery ) {
	const items = getItems( gallery );
	const index = Number( gallery.dataset.activeIndex || 0 );
	const item = items[ index ];
	const modalImage = gallery.querySelector( '[data-shanelle-gallery-modal-image]' );

	if ( ! item || ! ( modalImage instanceof HTMLImageElement ) ) {
		return;
	}

	modalImage.src = String( item.full_src || item.src || '' );
	modalImage.alt = String( item.alt || '' );
}

/**
 * Whether the device should use lightbox instead of inline mouse zoom.
 *
 * @returns {boolean}
 */
function prefersTouchGallery() {
	if ( typeof window.matchMedia !== 'function' ) {
		return false;
	}

	return window.matchMedia( '(hover: none), (pointer: coarse)' ).matches;
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
	setActiveIndex( gallery, current + delta, { animate: true } );
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
				setActiveIndex( gallery, Number( next.dataset.index || 0 ), { animate: true } );
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
			setActiveIndex( gallery, Number( thumb.dataset.index || 0 ), { animate: true } );
		} );

		thumb.addEventListener( 'mouseenter', () => {
			setActiveIndex( gallery, Number( thumb.dataset.index || 0 ), { animate: true } );
		} );

		thumb.addEventListener( 'focus', () => {
			setActiveIndex( gallery, Number( thumb.dataset.index || 0 ), { animate: true } );
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

	const AXIS_LOCK_PX = 10;
	const SWIPE_COMMIT_PX = 48;

	let startX = 0;
	let startY = 0;
	/** @type {'x'|'y'|null} */
	let axis = null;
	let tracking = false;

	stage.addEventListener( 'touchstart', ( event ) => {
		if ( event.touches.length !== 1 ) {
			return;
		}

		startX = event.touches[0].clientX;
		startY = event.touches[0].clientY;
		axis = null;
		tracking = true;
		gallery.dataset.gallerySwipeMoved = 'false';
		gallery.classList.add( 'is-swiping' );
	}, { passive: true } );

	stage.addEventListener( 'touchmove', ( event ) => {
		if ( ! tracking || event.touches.length !== 1 ) {
			return;
		}

		const touch = event.touches[0];
		const dx = touch.clientX - startX;
		const dy = touch.clientY - startY;

		if ( axis === null ) {
			if ( Math.abs( dx ) < AXIS_LOCK_PX && Math.abs( dy ) < AXIS_LOCK_PX ) {
				return;
			}

			axis = Math.abs( dx ) >= Math.abs( dy ) ? 'x' : 'y';

			if ( axis === 'y' ) {
				tracking = false;
				gallery.classList.remove( 'is-swiping' );
				return;
			}
		}

		if ( axis === 'x' && event.cancelable ) {
			event.preventDefault();
		}
	}, { passive: false } );

	stage.addEventListener( 'touchend', ( event ) => {
		if ( ! tracking ) {
			axis = null;
			gallery.classList.remove( 'is-swiping' );
			return;
		}

		const endX = event.changedTouches[0]?.clientX ?? startX;
		const delta = endX - startX;

		if ( axis === 'x' && Math.abs( delta ) > SWIPE_COMMIT_PX ) {
			gallery.dataset.gallerySwipeMoved = 'true';
			step( gallery, delta > 0 ? -1 : 1 );
		}

		tracking = false;
		axis = null;
		gallery.classList.remove( 'is-swiping' );
	}, { passive: true } );

	stage.addEventListener( 'touchcancel', () => {
		tracking = false;
		axis = null;
		gallery.classList.remove( 'is-swiping' );
	}, { passive: true } );
}

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

/**
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
function getFocusableElements( container ) {
	return Array.from( container.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter( ( element ) => {
		return element instanceof HTMLElement
			&& ! element.hasAttribute( 'disabled' )
			&& element.offsetParent !== null;
	} );
}

/**
 * @param {HTMLElement} gallery
 * @returns {{ open: (trigger?: HTMLElement|null) => void, close: () => void }|null}
 */
function initModal( gallery ) {
	const modal = gallery.querySelector( '[data-shanelle-gallery-modal]' );
	const panel = gallery.querySelector( '[data-shanelle-gallery-modal-panel]' );
	const openBtn = gallery.querySelector( '[data-shanelle-gallery-fullscreen]' );
	const closeBtn = gallery.querySelector( '[data-shanelle-gallery-modal-close]' );
	const overlay = gallery.querySelector( '[data-shanelle-gallery-modal-overlay]' );
	const main = gallery.querySelector( '.product-gallery__main' );

	if ( ! modal || ! ( panel instanceof HTMLElement ) || ! openBtn ) {
		return null;
	}

	/** @type {HTMLElement|null} */
	let lastFocused = null;
	let isOpen = false;

	/**
	 * @param {KeyboardEvent} event
	 */
	const trapFocus = ( event ) => {
		if ( event.key !== 'Tab' || ! isOpen ) {
			return;
		}

		const focusable = getFocusableElements( panel );

		if ( focusable.length === 0 ) {
			event.preventDefault();
			panel.focus();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];
		const active = document.activeElement;

		if ( event.shiftKey && active === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && active === last ) {
			event.preventDefault();
			first.focus();
		}
	};

	/**
	 * @param {HTMLElement|null|undefined} trigger
	 */
	const open = ( trigger ) => {
		if ( isOpen ) {
			return;
		}

		if ( gallery.classList.contains( 'is-zoom-active' ) ) {
			setZoomMode( gallery, false );
		}

		lastFocused = trigger instanceof HTMLElement
			? trigger
			: ( document.activeElement instanceof HTMLElement ? document.activeElement : openBtn );

		const index = Number( gallery.dataset.activeIndex || 0 );
		setActiveIndex( gallery, index, { animate: false, force: true } );
		syncModalImage( gallery );
		modal.hidden = false;
		isOpen = true;
		document.body.style.overflow = 'hidden';

		requestAnimationFrame( () => {
			const focusTarget = closeBtn instanceof HTMLElement ? closeBtn : panel;
			focusTarget.focus();
		} );
	};

	const close = () => {
		if ( ! isOpen ) {
			return;
		}

		modal.hidden = true;
		isOpen = false;
		document.body.style.overflow = '';

		const restore = lastFocused instanceof HTMLElement && document.contains( lastFocused )
			? lastFocused
			: openBtn;

		if ( restore instanceof HTMLElement ) {
			restore.focus();
		}

		lastFocused = null;
	};

	openBtn.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		open( openBtn instanceof HTMLElement ? openBtn : null );
	} );
	closeBtn?.addEventListener( 'click', close );
	overlay?.addEventListener( 'click', close );

	// Tap / click main image opens lightbox without fighting swipe.
	if ( main instanceof HTMLElement ) {
		main.addEventListener( 'click', ( event ) => {
			if ( event.target instanceof Element && event.target.closest( 'button' ) ) {
				return;
			}

			if ( gallery.dataset.gallerySwipeMoved === 'true' ) {
				gallery.dataset.gallerySwipeMoved = 'false';
				return;
			}

			const galleryPanel = gallery.querySelector( '[data-shanelle-gallery-panel]' );
			const restoreTarget = galleryPanel instanceof HTMLElement
				? galleryPanel
				: ( openBtn instanceof HTMLElement ? openBtn : null );

			open( restoreTarget );
		} );
	}

	document.addEventListener( 'keydown', ( event ) => {
		if ( ! isOpen ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			event.preventDefault();
			close();
			return;
		}

		trapFocus( event );
	} );

	return { open, close };
}

/**
 * @param {HTMLElement} gallery
 */
function updateZoomSource( gallery ) {
	const active = getActiveImage( gallery );
	const pane = gallery.querySelector( '[data-shanelle-gallery-zoom-pane]' );

	if ( ! active || ! pane ) {
		return;
	}

	const fullSrc = active.dataset.fullSrc || active.src;
	pane.style.backgroundImage = fullSrc ? `url("${ fullSrc.replace( /"/g, '\\"' ) }")` : '';
}

/**
 * @param {HTMLElement} gallery
 * @param {boolean} enabled
 */
function setZoomMode( gallery, enabled ) {
	if ( enabled && prefersTouchGallery() ) {
		enabled = false;
	}

	const toggle = gallery.querySelector( '[data-shanelle-gallery-zoom-toggle]' );
	const lens = gallery.querySelector( '[data-shanelle-gallery-zoom]' );
	const pane = gallery.querySelector( '[data-shanelle-gallery-zoom-pane]' );

	gallery.classList.toggle( 'is-zoom-active', enabled );

	if ( toggle instanceof HTMLButtonElement ) {
		toggle.setAttribute( 'aria-pressed', enabled ? 'true' : 'false' );
		toggle.setAttribute( 'aria-label', enabled ? ( i18n.zoomOff || 'Desactivar zoom' ) : ( i18n.zoomOn || 'Activar zoom' ) );
		toggle.classList.toggle( 'is-active', enabled );
	}

	if ( lens ) {
		lens.setAttribute( 'aria-hidden', enabled ? 'false' : 'true' );
	}

	if ( pane ) {
		pane.hidden = ! enabled;
		pane.setAttribute( 'aria-hidden', enabled ? 'false' : 'true' );
	}

	if ( enabled ) {
		updateZoomSource( gallery );
	}
}

/**
 * @param {HTMLElement} gallery
 * @param {MouseEvent} event
 */
function handleZoomMove( gallery, event ) {
	if ( ! gallery.classList.contains( 'is-zoom-active' ) || prefersTouchGallery() ) {
		return;
	}

	const stage = gallery.querySelector( '[data-shanelle-gallery-stage]' );
	const main = gallery.querySelector( '.product-gallery__main' );
	const lens = gallery.querySelector( '[data-shanelle-gallery-zoom]' );
	const pane = gallery.querySelector( '[data-shanelle-gallery-zoom-pane]' );
	const active = getActiveImage( gallery );

	if ( ! ( stage instanceof HTMLElement ) || ! ( main instanceof HTMLElement ) || ! ( lens instanceof HTMLElement ) || ! ( pane instanceof HTMLElement ) || ! active ) {
		return;
	}

	const rect = main.getBoundingClientRect();
	const x = event.clientX - rect.left;
	const y = event.clientY - rect.top;

	if ( x < 0 || y < 0 || x > rect.width || y > rect.height ) {
		lens.hidden = true;
		return;
	}

	lens.hidden = false;

	const lensSize = 96;
	const half = lensSize / 2;
	const clampedX = Math.max( half, Math.min( rect.width - half, x ) );
	const clampedY = Math.max( half, Math.min( rect.height - half, y ) );

	lens.style.width = `${ lensSize }px`;
	lens.style.height = `${ lensSize }px`;
	lens.style.left = `${ clampedX - half }px`;
	lens.style.top = `${ clampedY - half }px`;

	const percentX = ( clampedX / rect.width ) * 100;
	const percentY = ( clampedY / rect.height ) * 100;

	pane.style.backgroundSize = `${ rect.width * ZOOM_FACTOR }px ${ rect.height * ZOOM_FACTOR }px`;
	pane.style.backgroundPosition = `${ percentX }% ${ percentY }%`;
}

/**
 * @param {HTMLElement} gallery
 */
function initZoom( gallery ) {
	const toggle = gallery.querySelector( '[data-shanelle-gallery-zoom-toggle]' );
	const stage = gallery.querySelector( '[data-shanelle-gallery-stage]' );
	const main = gallery.querySelector( '.product-gallery__main' );

	if ( ! toggle || ! stage || ! main ) {
		return;
	}

	const syncTouchZoomAvailability = () => {
		const touchOnly = prefersTouchGallery();
		gallery.classList.toggle( 'is-touch-gallery', touchOnly );

		if ( toggle instanceof HTMLButtonElement ) {
			toggle.hidden = touchOnly;
			toggle.disabled = touchOnly;
			toggle.setAttribute( 'aria-hidden', touchOnly ? 'true' : 'false' );
		}

		if ( touchOnly && gallery.classList.contains( 'is-zoom-active' ) ) {
			setZoomMode( gallery, false );
		}
	};

	syncTouchZoomAvailability();

	if ( typeof window.matchMedia === 'function' ) {
		const media = window.matchMedia( '(hover: none), (pointer: coarse)' );
		const onChange = () => syncTouchZoomAvailability();

		if ( typeof media.addEventListener === 'function' ) {
			media.addEventListener( 'change', onChange );
		} else if ( typeof media.addListener === 'function' ) {
			media.addListener( onChange );
		}
	}

	toggle.addEventListener( 'click', () => {
		if ( prefersTouchGallery() ) {
			return;
		}

		const enabled = ! gallery.classList.contains( 'is-zoom-active' );
		setZoomMode( gallery, enabled );
	} );

	stage.addEventListener( 'mousemove', ( event ) => {
		if ( event instanceof MouseEvent ) {
			handleZoomMove( gallery, event );
		}
	} );

	stage.addEventListener( 'mouseleave', () => {
		const lens = gallery.querySelector( '[data-shanelle-gallery-zoom]' );

		if ( lens instanceof HTMLElement ) {
			lens.hidden = true;
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
 * Find the gallery that belongs to the same PDP as a variations root.
 *
 * @param {HTMLElement|null|undefined} root
 * @returns {HTMLElement|null}
 */
function findRelatedGallery( root ) {
	const detail = root instanceof HTMLElement
		? root.closest( '[data-shanelle-product-detail]' )
		: null;

	const scoped = detail?.querySelector( '[data-shanelle-product-gallery]' );

	if ( scoped instanceof HTMLElement ) {
		return scoped;
	}

	const fallback = document.querySelector( '[data-shanelle-product-gallery]' );

	return fallback instanceof HTMLElement ? fallback : null;
}

/**
 * Sync main gallery image to a variation attachment ID.
 * imageId 0 resets to the default (first) gallery image.
 *
 * @param {HTMLElement} gallery
 * @param {number} imageId
 */
function syncGalleryToImageId( gallery, imageId ) {
	const items = getItems( gallery );

	if ( ! items.length ) {
		return;
	}

	const targetId = Number( imageId || 0 );

	if ( targetId <= 0 ) {
		setActiveIndex( gallery, 0, { animate: true } );
		return;
	}

	const index = items.findIndex( ( item ) => Number( item.id ) === targetId );

	if ( index >= 0 ) {
		setActiveIndex( gallery, index, { animate: true } );
	}
}

/**
 * Listen for ProductVariations gallery-change events.
 *
 * @param {HTMLElement} gallery
 */
function bindVariationGallerySync( gallery ) {
	if ( gallery.dataset.variationGalleryBound === 'true' ) {
		return;
	}

	gallery.dataset.variationGalleryBound = 'true';

	document.body.addEventListener( 'shanelle:product-variations:gallery-change', ( event ) => {
		if ( ! ( event instanceof CustomEvent ) ) {
			return;
		}

		const detail = event.detail ?? {};
		const related = findRelatedGallery( detail.root );

		if ( related !== gallery ) {
			return;
		}

		syncGalleryToImageId( gallery, Number( detail.imageId || 0 ) );
	} );
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
	initZoom( gallery );
	initLazyThumbs( gallery );
	bindVariationGallerySync( gallery );

	const items = getItems( gallery );

	if ( items.length ) {
		announce( gallery, Number( gallery.dataset.activeIndex || 0 ), items.length );
	}

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-gallery:ready', {
			bubbles: true,
			detail: { gallery },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-gallery]' ).forEach( initGallery );

export { initGallery, setActiveIndex, step, setZoomMode, syncGalleryToImageId, syncModalImage };
