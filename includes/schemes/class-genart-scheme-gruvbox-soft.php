<?php
/**
 * Gruvbox Soft scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gruvbox Soft palette.
 */
class Genart_Scheme_Gruvbox_Soft extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'gruvbox_soft';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Gruvbox soft';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#282828', '#504945', '#665c54', '#a89984', '#d5c4a1' );
	}
}

