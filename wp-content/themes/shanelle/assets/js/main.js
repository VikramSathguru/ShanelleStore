/**
 * Theme entry point.
 *
 * @package Shanelle
 */

import { initAutoHideHeader } from './modules/auto-hide-header.js';
import { initMobileDrawer } from './modules/mobile-drawer.js';
import { initPromoBanner } from './modules/promo-banner.js';
import { initStickyHeaderOffset } from './modules/sticky-header-offset.js';

const header = document.querySelector( '[data-header]' );

if ( header ) {
	initMobileDrawer( header );
	initStickyHeaderOffset( header );
	initAutoHideHeader( header );
	initPromoBanner( header );
}
