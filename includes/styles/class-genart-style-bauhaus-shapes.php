<?php
/**
 * Bauhaus shapes style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bauhaus shapes renderer.
 */
class Genart_Style_Bauhaus_Shapes extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'bauhaus_shapes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Bauhaus shapes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		for ( $i = 0; $i < 15; $i++ ) {
			imagefilledrectangle(
				$image,
				wp_rand( 0, (int) ( $width * 0.67 ) ),
				wp_rand( 0, (int) ( $height * 0.64 ) ),
				wp_rand( (int) ( $width * 0.33 ), $width ),
				wp_rand( (int) ( $height * 0.48 ), $height ),
				$colors[ array_rand( $colors ) ]
			);
		}
	}
}
