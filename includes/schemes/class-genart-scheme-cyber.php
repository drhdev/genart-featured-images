<?php
/**
 * Cyber scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cyber palette.
 */
class Genart_Scheme_Cyber extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'cyber';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Cyber';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#00ff41', '#008f11', '#003b00', '#000000', '#001100' );
	}
}

