/**
 * Shanelle Homepage Page Script
 *
 * @package Shanelle
 */

const config = window.shanelleHomepage ?? {};

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>[]}
 */
function getHomepageSections( root ) {
	try {
		return JSON.parse( root.dataset.homepageSections || '[]' );
	} catch ( error ) {
		return Array.isArray( config.sections ) ? config.sections : [];
	}
}

/**
 * @param {HTMLElement} root
 * @returns {HTMLElement[]}
 */
function getRenderedSections( root ) {
	return Array.from( root.querySelectorAll( '[data-shanelle-homepage-section]' ) ).filter(
		( section ) => section instanceof HTMLElement
	);
}

/**
 * @param {HTMLElement} root
 * @param {string} key
 * @returns {HTMLElement|null}
 */
function getSectionByKey( root, key ) {
	const section = root.querySelector( `[data-shanelle-homepage-section][data-section-key="${ CSS.escape( key ) }"]` );

	return section instanceof HTMLElement ? section : null;
}

/**
 * @param {HTMLElement} root
 * @param {string} key
 */
function scrollToSection( root, key ) {
	const section = getSectionByKey( root, key );

	if ( ! section ) {
		return;
	}

	section.scrollIntoView( {
		behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth',
		block: 'start',
	} );
}

/**
 * @param {HTMLElement} root
 */
function initHomepage( root ) {
	if ( root.dataset.homepageHydrated === 'true' ) {
		return;
	}

	root.dataset.homepageHydrated = 'true';

	const sections = getHomepageSections( root );
	const renderedSections = getRenderedSections( root );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:homepage:ready', {
			bubbles: true,
			detail: {
				root,
				sections,
				renderedSections,
				api: {
					getHomepageSections: () => getHomepageSections( root ),
					getRenderedSections: () => getRenderedSections( root ),
					getSectionByKey: ( key ) => getSectionByKey( root, key ),
					scrollToSection: ( key ) => scrollToSection( root, key ),
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-homepage]' ).forEach( ( root ) => {
	if ( root instanceof HTMLElement ) {
		initHomepage( root );
	}
} );

export {
	initHomepage,
	getHomepageSections,
	getRenderedSections,
	getSectionByKey,
	scrollToSection,
};
