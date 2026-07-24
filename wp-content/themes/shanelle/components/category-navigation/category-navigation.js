/**
 * Shanelle Category Navigation Component
 *
 * @package Shanelle
 */

const config = window.shanelleCategoryNavigation ?? {};
const i18n = config.i18n ?? {};

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>[]}
 */
function getCategories( root ) {
	try {
		return JSON.parse( root.dataset.categoryJson || '[]' );
	} catch ( error ) {
		return [];
	}
}

/**
 * @param {HTMLElement} root
 * @returns {Record<string, unknown>}
 */
function getSettings( root ) {
	try {
		return JSON.parse( root.dataset.settingsJson || '{}' );
	} catch ( error ) {
		return {};
	}
}

/**
 * @param {HTMLElement} root
 * @returns {HTMLElement|null}
 */
function getList( root ) {
	const list = root.querySelector( '[data-shanelle-category-list]' );

	return list instanceof HTMLElement ? list : null;
}

/**
 * @param {HTMLElement} root
 * @returns {HTMLElement[]}
 */
function getCategoryItems( root ) {
	return Array.from( root.querySelectorAll( '[data-shanelle-category-item]' ) ).filter(
		( item ) => item instanceof HTMLElement
	);
}

/**
 * @param {HTMLElement} root
 * @returns {boolean}
 */
function isScrollLayout( root ) {
	const settings = getSettings( root );
	const layout = String( settings.layout || config.layout || 'responsive' );

	if ( layout === 'scroll' ) {
		return true;
	}

	if ( layout === 'grid' ) {
		return false;
	}

	return window.matchMedia( '(max-width: 47.99rem)' ).matches;
}

/**
 * @param {HTMLElement} root
 * @param {number} offset
 */
function scrollCategoriesBy( root, offset ) {
	const list = getList( root );

	if ( ! list ) {
		return;
	}

	list.scrollBy( {
		left: offset,
		behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth',
	} );
}

/**
 * @param {HTMLElement} root
 * @param {number} direction
 */
function scrollCategoriesPage( root, direction ) {
	const list = getList( root );

	if ( ! list ) {
		return;
	}

	const distance = Math.max( list.clientWidth * 0.8, 240 );
	scrollCategoriesBy( root, distance * direction );
}

/**
 * @param {HTMLElement} root
 * @param {number} index
 */
function scrollToCategory( root, index ) {
	const items = getCategoryItems( root );
	const target = items[ index ];

	if ( ! ( target instanceof HTMLElement ) ) {
		return;
	}

	target.scrollIntoView( {
		behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth',
		block: 'nearest',
		inline: 'start',
	} );
}

/**
 * @param {HTMLElement} root
 */
function bindKeyboardScroll( root ) {
	root.addEventListener( 'keydown', ( event ) => {
		if ( ! isScrollLayout( root ) ) {
			return;
		}

		if ( event.key === 'ArrowRight' ) {
			event.preventDefault();
			scrollCategoriesPage( root, 1 );
		}

		if ( event.key === 'ArrowLeft' ) {
			event.preventDefault();
			scrollCategoriesPage( root, -1 );
		}
	} );
}

/**
 * @param {HTMLElement} root
 */
function initCategoryNavigation( root ) {
	if ( root.dataset.categoryNavigationHydrated === 'true' ) {
		return;
	}

	root.dataset.categoryNavigationHydrated = 'true';

	const categories = getCategories( root );
	const settings = getSettings( root );
	const list = getList( root );

	if ( list && isScrollLayout( root ) ) {
		list.setAttribute( 'tabindex', '0' );
		list.setAttribute( 'role', 'list' );
	}

	bindKeyboardScroll( root );

	document.body.dispatchEvent(
		new CustomEvent( 'shanelle:category-navigation:ready', {
			bubbles: true,
			detail: {
				root,
				list,
				categories,
				settings,
				api: {
					getCategories: () => getCategories( root ),
					getSettings: () => getSettings( root ),
					getCategoryItems: () => getCategoryItems( root ),
					isScrollLayout: () => isScrollLayout( root ),
					scrollCategoriesBy: ( offset ) => scrollCategoriesBy( root, offset ),
					scrollCategoriesPage: ( direction ) => scrollCategoriesPage( root, direction ),
					scrollToCategory: ( index ) => scrollToCategory( root, index ),
				},
			},
		} )
	);
}

document.querySelectorAll( '[data-shanelle-category-navigation]' ).forEach( ( root ) => {
	if ( root instanceof HTMLElement ) {
		initCategoryNavigation( root );
	}
} );

export {
	initCategoryNavigation,
	getCategories,
	getSettings,
	getCategoryItems,
	isScrollLayout,
	scrollCategoriesBy,
	scrollCategoriesPage,
	scrollToCategory,
};
