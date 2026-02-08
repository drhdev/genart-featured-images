<?php
/**
 * Base class for pluggable GenArt styles.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base style API.
 */
abstract class Genart_Style_Base {

	/**
	 * Gets unique style ID.
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Gets label shown in admin/editor dropdowns.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Renders artwork on the image resource.
	 *
	 * @param resource|\GdImage $image GD image resource.
	 * @param int[]             $colors Preallocated color indexes.
	 * @param int               $width Canvas width.
	 * @param int               $height Canvas height.
	 * @return void
	 */
	abstract public function render( $image, $colors, $width, $height );
}

