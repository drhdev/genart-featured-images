<?php
/**
 * Summer Dream scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Summer Dream palette.
 */
class Genart_Scheme_Summer_Dream extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'summer_dream';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Summer dream';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array(
			'#fdfcdc', // light yellow.
			'#fed9b7', // soft apricot.
			'#f07167', // vibrant coral.
			'#00afb9', // tropical teal.
			'#0081a7', // cerulean.
		);
	}
}
