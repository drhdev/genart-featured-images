<?php
/**
 * Base class for pluggable GenArt color schemes.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base scheme API.
 */
abstract class Genart_Scheme_Base {

	/**
	 * Gets unique scheme ID.
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
	 * Gets HEX color list.
	 *
	 * @return array<int, string>
	 */
	abstract public function get_colors();
}

