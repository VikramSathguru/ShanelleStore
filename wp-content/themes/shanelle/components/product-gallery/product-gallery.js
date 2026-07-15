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

	if ( ! status ) {
		return;
	}

	const template = i18n.imageOf || 'Imagen %1$d de %2$d';
	status.textContent = template.replace( '%1$d', String( index + 1 ) ).replace( '%2$d', String( total ) );
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
 * @param {{ animate?: boolean }} [options]
 */
function setActiveIndex( gallery, index, options = {} ) {
	const items = getItems( gallery );

	if ( ! items.length ) {
		return;
	}

	const total = items.length;
	const nextIndex = ( index + total ) % total;
	const currentIndex = Number( gallery.dataset.activeIndex || 0 );

	if ( nextIndex === currentIndex && gallery.dataset.activeIndex !== undefined ) {
		return;
	}

	const item = items[ nextIndex ];
	const thumbs = gallery.querySelectorAll( '[data-shanelle-gallery-thumb]' );
	const animate = options.animate !== false && ! gallery.classList.contains( 'is-swiping' );

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
		setActiveIndex( gallery, index, { animate: false } );
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
	if ( ! gallery.classList.contains( 'is-zoom-active' ) ) {
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

	toggle.addEventListener( 'click', () => {
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

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:product-gallery:ready', {
			bubbles: true,
			detail: { gallery },
		} )
	);
}

document.querySelectorAll( '[data-shanelle-product-gallery]' ).forEach( initGallery );

export { initGallery, setActiveIndex, step, setZoomMode };
