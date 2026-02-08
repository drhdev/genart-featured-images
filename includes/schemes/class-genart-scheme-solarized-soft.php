<?php
/**
 * Solarized Soft scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Solarized Soft palette.
 */
class Genart_Scheme_Solarized_Soft extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'solarized_soft';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Solarized soft';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#073642', '#586e75', '#657b83', '#839496', '#eee8d5' );
	}
}

