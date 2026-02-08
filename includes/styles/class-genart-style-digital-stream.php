<?php
/**
 * Digital stream style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Digital stream renderer.
 */
class Genart_Style_Digital_Stream extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'digital_stream';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Digital stream';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		for ( $i = 0; $i < 60; $i++ ) {
			imageline(
				$image,
				wp_rand( 0, $width ),
				0,
				wp_rand( 0, $width ),
				$height,
				$colors[ array_rand( $colors ) ]
			);
		}
	}
}
