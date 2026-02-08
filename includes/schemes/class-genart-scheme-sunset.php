<?php
/**
 * Sunset scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sunset palette.
 */
class Genart_Scheme_Sunset extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'sunset';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Sunset';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#ff9900', '#ff5500', '#ff0055', '#9900bb', '#330066' );
	}
}

