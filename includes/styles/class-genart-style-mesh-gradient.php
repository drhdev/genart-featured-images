<?php
/**
 * Mesh gradient style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mesh gradient renderer.
 */
class Genart_Style_Mesh_Gradient extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'mesh_gradient';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Mesh gradient';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		for ( $i = 0; $i < 12; $i++ ) {
			imagefilledellipse(
				$image,
				wp_rand( 0, $width ),
				wp_rand( 0, $height ),
				wp_rand( (int) ( $width * 0.33 ), (int) ( $width * 0.75 ) ),
				wp_rand( (int) ( $height * 0.6 ), (int) ( $height * 1.4 ) ),
				$colors[ array_rand( $colors ) ]
			);
		}
	}
}
