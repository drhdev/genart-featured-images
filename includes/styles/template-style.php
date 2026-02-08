<?php
/**
 * Style template (not auto-loaded).
 *
 * Copy this file, rename it to:
 * class-genart-style-your-style.php
 *
 * Then update the class name and IDs accordingly.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

class Genart_Style_Your_Style extends Genart_Style_Base {

	/**
	 * Must match filename slug converted to snake_case.
	 * Example filename: class-genart-style-your-style.php
	 * Required ID: your_style
	 *
	 * @return string
	 */
	public function get_id() {
		return 'your_style';
	}

	/**
	 * Human-readable label shown in admin dropdowns.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Your style';
	}

	/**
	 * Draw art on canvas.
	 *
	 * @param resource|\GdImage $image GD image resource.
	 * @param int[]             $colors Preallocated color indexes.
	 * @param int               $width Canvas width.
	 * @param int               $height Canvas height.
	 * @return void
	 */
	public function render( $image, $colors, $width, $height ) {
		for ( $i = 0; $i < 80; $i++ ) {
			$color = $colors[ array_rand( $colors ) ];
			imageline(
				$image,
				wp_rand( 0, $width ),
				wp_rand( 0, $height ),
				wp_rand( 0, $width ),
				wp_rand( 0, $height ),
				$color
			);
		}
	}
}

