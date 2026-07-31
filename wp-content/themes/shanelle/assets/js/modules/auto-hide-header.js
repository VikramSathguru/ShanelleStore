/**
 * Header auto-hide (disabled).
 *
 * Promo + main header + category nav must stay sticky and always visible.
 * Kept as a no-op export so existing imports keep working.
 *
 * @package Shanelle
 */

/**
 * @param {HTMLElement} [header]
 */
function initAutoHideHeader( header ) {
	const root = header instanceof HTMLElement
		? header
		: document.querySelector( '[data-header]' );

	if ( ! ( root instanceof HTMLElement ) ) {
		return;
	}

	root.removeAttribute( 'data-auto-hide' );
	root.classList.remove( 'is-hidden' );
}

export { initAutoHideHeader };
