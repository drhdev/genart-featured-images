<?php
/**
 * Scheme template (not auto-loaded).
 *
 * Copy this file, rename it to:
 * class-genart-scheme-your-scheme.php
 *
 * Then update the class name and IDs accordingly.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

class Genart_Scheme_Your_Scheme extends Genart_Scheme_Base {

	/**
	 * Must match filename slug converted to snake_case.
	 * Example filename: class-genart-scheme-your-scheme.php
	 * Required ID: your_scheme
	 *
	 * @return string
	 */
	public function get_id() {
		return 'your_scheme';
	}

	/**
	 * Human-readable label shown in admin dropdowns.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Your scheme';
	}

	/**
	 * Return at least 2 strict #rrggbb colors.
	 *
	 * @return array<int, string>
	 */
	public function get_colors() {
		return array(
			'#112233',
			'#334455',
			'#556677',
			'#778899',
			'#99aabb',
		);
	}
}

