/**
 * Theme entry point.
 *
 * @package Shanelle
 */

import { initMobileDrawer } from './modules/mobile-drawer.js';

const header = document.querySelector( '[data-header]' );

if ( header ) {
	initMobileDrawer( header );
}
