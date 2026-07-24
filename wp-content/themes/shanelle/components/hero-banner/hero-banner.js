/**
 * Shanelle Hero Banner Component
 *
 * Single-slide implementation with carousel-ready exports for future use.
 *
 * @package Shanelle
 */

const config = window.shanelleHeroBanner ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} root
 * @returns {HTMLElement[]}
 */
function getSlides( root ) {
	return Array.from( root.querySelectorAll( '[data-shanelle-hero-slide]' ) ).filter(
		( slide ) => slide instanceof HTMLElement
	);
}

/**
 * @param {HTMLElement} root
 * @returns {number}
 */
function getActiveSlideIndex( root ) {
	const slides = getSlides( root );
	const active = slides.findIndex( ( slide ) => slide.classList.contains( 'is-active' ) );

	return active >= 0 ? active : 0;
}

/**
 * @param {HTMLElement} root
 * @param {number} index
 */
function goToSlide( root, index ) {
	const slides = getSlides( root );

	if ( slides.length === 0 ) {
		return;
	}

	const nextIndex = ( ( index % slides.length ) + slides.length ) % slides.length;

	slides.forEach( ( slide, slideIndex ) => {
		const isActive = slideIndex === nextIndex;

		slide.classList.toggle( 'is-active', isActive );
		slide.toggleAttribute( 'hidden', ! isActive );
		slide.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
	} );

	root.dataset.activeSlide = String( nextIndex );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:hero-banner:slide-change', {
			bubbles: true,
			detail: {
				root,
				index: nextIndex,
				slide: slides[ nextIndex ],
				total: slides.length,
			},
		} )
	);
}

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>[]}
 */
function getSlideData( root ) {
	try {
		return JSON.parse( root.dataset.heroSlides || '[]' );
	} catch ( error ) {
		return [];
	}
}

/**
 * @param {HTMLElement} root
 */
function initHeroBanner( root ) {
	if ( root.dataset.heroHydrated === 'true' ) {
		return;
	}

	root.dataset.heroHydrated = 'true';

	const slides = getSlides( root );
	const slideData = getSlideData( root );

	if ( slides.length > 0 ) {
		goToSlide( root, getActiveSlideIndex( root ) );
	}

	root.setAttribute(
		'aria-label',
		slides.length > 1
			? ( i18n.carouselLabel || 'Carrusel del banner principal' )
			: ( i18n.carouselLabel || 'Banner principal del inicio' )
	);

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:hero-banner:ready', {
			bubbles: true,
			detail: {
				root,
				slides,
				slideData,
				activeIndex: getActiveSlideIndex( root ),
				api: {
					getSlides: () => getSlides( root ),
					getSlideData: () => getSlideData( root ),
					getActiveSlideIndex: () => getActiveSlideIndex( root ),
					goToSlide: ( index ) => goToSlide( root, index ),
					nextSlide: () => goToSlide( root, getActiveSlideIndex( root ) + 1 ),
					prevSlide: () => goToSlide( root, getActiveSlideIndex( root ) - 1 ),
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-hero-banner]' ).forEach( ( root ) => {
	if ( root instanceof HTMLElement ) {
		initHeroBanner( root );
	}
} );

export {
	initHeroBanner,
	getSlides,
	getSlideData,
	getActiveSlideIndex,
	goToSlide,
};
