<?php
/**
 * Modern Blue scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modern Blue palette.
 */
class Genart_Scheme_Modern_Blue extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'modern_blue';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Modern blue';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#003f5c', '#2f4b7c', '#665191', '#a05195', '#d45087' );
	}
}

