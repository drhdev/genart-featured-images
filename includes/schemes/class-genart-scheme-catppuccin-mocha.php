<?php
/**
 * Catppuccin Mocha scheme.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catppuccin Mocha palette.
 */
class Genart_Scheme_Catppuccin_Mocha extends Genart_Scheme_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'catppuccin_mocha';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Catppuccin mocha';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_colors() {
		return array( '#1e1e2e', '#313244', '#45475a', '#6c7086', '#cdd6f4' );
	}
}

