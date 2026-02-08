<?php
/**
 * Tangled sinus style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tangled sinus renderer.
 */
class Genart_Style_Tangled_Sinus extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'tangled_sinus';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Tangled sinus';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		$curve_count = wp_rand( 12, 18 );

		for ( $curve = 0; $curve < $curve_count; $curve++ ) {
			$line_color = $colors[ array_rand( $colors ) ];
			$amplitude  = wp_rand( (int) ( $height * 0.06 ), (int) ( $height * 0.24 ) );
			$frequency  = wp_rand( 10, 34 ) / 100;
			$phase      = wp_rand( 0, 628 ) / 100;
			$baseline   = wp_rand( (int) ( $height * 0.08 ), (int) ( $height * 0.92 ) );
			$thickness  = wp_rand( 1, 4 );
			$drift      = wp_rand( -22, 22 ) / 100;

			$last_x = 0;
			$last_y = (int) round( $baseline + ( $amplitude * sin( $phase ) ) );

			for ( $x = 2; $x <= $width; $x += 2 ) {
				$t = ( $x / max( 1, $width ) ) * ( 2 * pi() );
				$y = (int) round(
					$baseline +
					( $amplitude * sin( ( $t * $frequency * 5 ) + $phase ) ) +
					( ( $amplitude * 0.32 ) * sin( ( $t * ( $frequency * 11 ) ) - ( $phase * 0.7 ) ) ) +
					( $drift * $x )
				);

				for ( $offset = -$thickness; $offset <= $thickness; $offset++ ) {
					imageline( $image, $last_x, $last_y + $offset, $x, $y + $offset, $line_color );
				}

				$last_x = $x;
				$last_y = $y;
			}
		}

		$accent_count = wp_rand( 4, 7 );
		for ( $accent = 0; $accent < $accent_count; $accent++ ) {
			$line_color = $colors[ array_rand( $colors ) ];
			$amplitude  = wp_rand( (int) ( $height * 0.03 ), (int) ( $height * 0.1 ) );
			$frequency  = wp_rand( 20, 55 ) / 100;
			$phase      = wp_rand( 0, 628 ) / 100;
			$baseline   = wp_rand( (int) ( $height * 0.12 ), (int) ( $height * 0.88 ) );

			$last_x = 0;
			$last_y = (int) round( $baseline + ( $amplitude * sin( $phase ) ) );

			for ( $x = 1; $x <= $width; $x++ ) {
				$t = ( $x / max( 1, $width ) ) * ( 2 * pi() );
				$y = (int) round( $baseline + ( $amplitude * sin( ( $t * $frequency * 13 ) + $phase ) ) );
				imageline( $image, $last_x, $last_y, $x, $y, $line_color );
				$last_x = $x;
				$last_y = $y;
			}
		}
	}
}
