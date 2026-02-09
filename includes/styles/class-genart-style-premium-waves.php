<?php
/**
 * Premium waves style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Premium waves renderer.
 */
class Genart_Style_Premium_Waves extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'premium_waves';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Premium waves';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		$noise_scale  = 0.001;
		$layer_count  = 100;
		$render_scale = 2;
		$scaled_w     = max( 2, (int) round( $width * $render_scale ) );
		$scaled_h     = max( 2, (int) round( $height * $render_scale ) );
		$center_y     = $scaled_h / 2;
		$noise_shift_x = wp_rand( 0, 200000 ) / 1000;
		$noise_shift_y = wp_rand( 0, 200000 ) / 1000;
		$envelope_curve = wp_rand( 85, 125 ) / 100;
		$global_height_scale = 0.45;
		$global_hard_cap     = (int) round( $scaled_h * 0.16 );

		$scaled = imagecreatetruecolor( $scaled_w, $scaled_h );
		if ( false === $scaled ) {
			return;
		}

		$scaled_colors = $this->map_palette_to_canvas( $image, $scaled, $colors );
		if ( empty( $scaled_colors ) ) {
			imagedestroy( $scaled );
			return;
		}

		$background_src = imagecolorsforindex( $image, imagecolorat( $image, 0, 0 ) );
		$background     = imagecolorallocatealpha(
			$scaled,
			$background_src['red'],
			$background_src['green'],
			$background_src['blue'],
			0
		);
		imagefill( $scaled, 0, 0, $background );

		$all_wave_colors   = $scaled_colors;
		$front_wave_colors = array_slice( $scaled_colors, 1 );
		if ( empty( $front_wave_colors ) ) {
			$front_wave_colors = $scaled_colors;
		}

		$accent_color       = $all_wave_colors[ array_rand( $all_wave_colors ) ];
		$front_layer_start  = $layer_count - 10;
		$front_height_scale = 0.12;
		$front_hard_cap     = (int) round( $scaled_h * 0.05 );

		for ( $i = 0; $i < $layer_count; $i++ ) {
			$is_front_layer = $i >= $front_layer_start;

			if ( $i === ( $layer_count - 2 ) || $is_front_layer ) {
				$layer_color = $front_wave_colors[ array_rand( $front_wave_colors ) ];
			} elseif ( wp_rand( 1, 100 ) <= 10 ) {
				$layer_color = $accent_color;
			} else {
				$layer_color = $all_wave_colors[ array_rand( $all_wave_colors ) ];
			}

			for ( $x = 0; $x < $scaled_w; $x++ ) {
				$t            = ( $x / max( 1, ( $scaled_w - 1 ) ) ) * pi();
				$base_envelope = max( 0.0, sin( $t ) );
				$envelope      = pow( $base_envelope, $envelope_curve );

				$h_max = ( ( $scaled_h / 2 ) - 2 ) * $envelope * $global_height_scale;
				if ( $is_front_layer ) {
					$h_max *= $front_height_scale;
				}

				$h     = max(
					1,
					(int) round(
						$this->noise_2d(
							( $x * $noise_scale ) + $noise_shift_x,
							( $i * 0.017 ) + ( $noise_shift_y * 0.33 )
						) * $h_max
					)
				);
				if ( $is_front_layer ) {
					$h = min( $h, $front_hard_cap );
				}
				$h = min( $h, $global_hard_cap );

				$half_h    = $h / 2;
				$y_max     = ( ( $scaled_h / 2 ) - 2 ) * $envelope;
				$y_range   = max( 0.0, $y_max - $half_h );
				$y_noise   = (
					$this->noise_2d(
						( $i * 0.01 ) + ( $noise_shift_y * 0.12 ),
						( $x * $noise_scale * 2 ) + ( $noise_shift_x * 0.85 )
					) * 2
				) - 1;
				$y_center  = $center_y + ( $y_noise * $y_range );
				$y_start   = (int) round( $y_center - $half_h );
				$y_end     = (int) round( $y_center + $half_h );
				$y_start   = max( 0, min( $scaled_h - 1, $y_start ) );
				$y_end     = max( 0, min( $scaled_h - 1, $y_end ) );

				if ( $y_end < $y_start ) {
					$tmp     = $y_start;
					$y_start = $y_end;
					$y_end   = $tmp;
				}

				imageline( $scaled, $x, $y_start, $x, $y_end, $layer_color );
			}
		}

		if ( function_exists( 'imagefilter' ) && defined( 'IMG_FILTER_GAUSSIAN_BLUR' ) ) {
			imagefilter( $scaled, IMG_FILTER_GAUSSIAN_BLUR );
		}

		imagecopyresampled( $image, $scaled, 0, 0, 0, 0, $width, $height, $scaled_w, $scaled_h );
		imagedestroy( $scaled );
	}

	/**
	 * Maps existing palette colors to another GD canvas.
	 *
	 * @param resource|\GdImage $source Source GD image.
	 * @param resource|\GdImage $target Target GD image.
	 * @param int[]             $colors Source image color indexes.
	 * @return int[]
	 */
	private function map_palette_to_canvas( $source, $target, $colors ) {
		$mapped = array();
		foreach ( $colors as $index ) {
			$rgba = imagecolorsforindex( $source, $index );
			if ( false === $rgba ) {
				continue;
			}

			$mapped[] = imagecolorallocatealpha(
				$target,
				$rgba['red'],
				$rgba['green'],
				$rgba['blue'],
				0
			);
		}

		return $mapped;
	}

	/**
	 * Smooth 2D value noise in range [0,1].
	 *
	 * @param float $x Sample X.
	 * @param float $y Sample Y.
	 * @return float
	 */
	private function noise_2d( $x, $y ) {
		$x0 = (int) floor( $x );
		$y0 = (int) floor( $y );
		$x1 = $x0 + 1;
		$y1 = $y0 + 1;

		$fx = $x - $x0;
		$fy = $y - $y0;

		$v00 = $this->hash_2d( $x0, $y0 );
		$v10 = $this->hash_2d( $x1, $y0 );
		$v01 = $this->hash_2d( $x0, $y1 );
		$v11 = $this->hash_2d( $x1, $y1 );

		$ux = $this->smoothstep( $fx );
		$uy = $this->smoothstep( $fy );

		$nx0 = $this->lerp( $v00, $v10, $ux );
		$nx1 = $this->lerp( $v01, $v11, $ux );

		return $this->lerp( $nx0, $nx1, $uy );
	}

	/**
	 * Deterministic lattice hash in range [0,1].
	 *
	 * @param int $x Grid X.
	 * @param int $y Grid Y.
	 * @return float
	 */
	private function hash_2d( $x, $y ) {
		$dot = ( $x * 12.9898 ) + ( $y * 78.233 );
		$s   = sin( $dot ) * 43758.5453;

		return $s - floor( $s );
	}

	/**
	 * Smooth interpolation curve.
	 *
	 * @param float $t Input in range [0,1].
	 * @return float
	 */
	private function smoothstep( $t ) {
		return $t * $t * ( 3 - ( 2 * $t ) );
	}

	/**
	 * Linear interpolation.
	 *
	 * @param float $a Start value.
	 * @param float $b End value.
	 * @param float $t Interpolation factor.
	 * @return float
	 */
	private function lerp( $a, $b, $t ) {
		return $a + ( ( $b - $a ) * $t );
	}
}
