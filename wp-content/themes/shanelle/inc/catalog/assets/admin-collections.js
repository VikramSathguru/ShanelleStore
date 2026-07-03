/**
 * Admin media picker for collection hero images.
 *
 * @package Shanelle\Catalog
 */
( function () {
	'use strict';

	const fields = document.querySelectorAll( '[data-shanelle-hero-field]' );

	if ( ! fields.length || 'undefined' === typeof wp || ! wp.media ) {
		return;
	}

	fields.forEach( ( field ) => {
		const input = field.querySelector( '[data-shanelle-hero-input]' );
		const preview = field.querySelector( '[data-shanelle-hero-preview]' );
		const selectButton = field.querySelector( '[data-shanelle-hero-select]' );
		const removeButton = field.querySelector( '[data-shanelle-hero-remove]' );

		if ( ! input || ! preview || ! selectButton || ! removeButton ) {
			return;
		}

		let frame = null;

		const renderPreview = ( attachment ) => {
			preview.innerHTML = '';

			if ( ! attachment || ! attachment.id ) {
				field.classList.remove( 'has-image' );
				field.classList.add( 'no-image' );
				input.value = '0';
				return;
			}

			const imageUrl = attachment.sizes?.thumbnail?.url || attachment.url;

			if ( imageUrl ) {
				const image = document.createElement( 'img' );
				image.src = imageUrl;
				image.alt = attachment.alt || '';
				preview.appendChild( image );
			}

			field.classList.add( 'has-image' );
			field.classList.remove( 'no-image' );
			input.value = String( attachment.id );
		};

		selectButton.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Select Hero Image',
				button: { text: 'Use Image' },
				library: { type: 'image' },
				multiple: false,
			} );

			frame.on( 'select', () => {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				renderPreview( attachment );
			} );

			frame.open();
		} );

		removeButton.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			renderPreview( null );
		} );
	} );
}() );
