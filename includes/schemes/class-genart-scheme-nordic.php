<?php
/**
 * Nordic scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nordic palette.
 */
class Genart_Scheme_Nordic extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'nordic';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Nordic';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#2e3440', '#3b4252', '#434c5e', '#4c566a', '#d8dee9' );
	}
}

