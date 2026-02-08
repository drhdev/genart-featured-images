<?php
/**
 * Broken hectagons style.
 *
 * @package GenArtFeaturedImages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Broken hectagons renderer.
 */
class Genart_Style_Broken_Hectagons extends Genart_Style_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'broken_hectagons';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return 'Broken hectagons';
	}

	/**
	 * {@inheritdoc}
	 */
	public function render( $image, $colors, $width, $height ) {
		$radius       = wp_rand( 34, 52 );
		$hex_height   = sqrt( 3 ) * $radius;
		$spacing_x    = 1.5 * $radius;
		$spacing_y    = $hex_height;
		$outline_dark = imagecolorallocatealpha( $image, 20, 25, 35, 40 );

		$hole_clusters = array();
		$cluster_count = wp_rand( 2, 4 );
		for ( $i = 0; $i < $cluster_count; $i++ ) {
			$hole_clusters[] = array(
				'x'      => wp_rand( (int) ( $width * 0.1 ), (int) ( $width * 0.9 ) ),
				'y'      => wp_rand( (int) ( $height * 0.1 ), (int) ( $height * 0.9 ) ),
				'radius' => wp_rand( (int) ( $radius * 1.8 ), (int) ( $radius * 3.2 ) ),
			);
		}

		$cells = array();
		$row   = 0;
		for ( $base_y = ( $hex_height / 2 ); $base_y < ( $height + $hex_height ); $base_y += $spacing_y, $row++ ) {
			$col = 0;
			for ( $base_x = $radius; $base_x < ( $width + $radius ); $base_x += $spacing_x, $col++ ) {
				$center_x = $base_x;
				$center_y = $base_y + ( ( $col % 2 ) ? ( $hex_height / 2 ) : 0 );
				if ( $center_y < -$hex_height || $center_y > ( $height + $hex_height ) ) {
					continue;
				}

				$is_hole = false;
				foreach ( $hole_clusters as $cluster ) {
					$dx = $center_x - $cluster['x'];
					$dy = $center_y - $cluster['y'];
					if ( ( $dx * $dx ) + ( $dy * $dy ) <= ( $cluster['radius'] * $cluster['radius'] ) ) {
						$is_hole = true;
						break;
					}
				}

				if ( ! $is_hole && wp_rand( 1, 100 ) <= 5 ) {
					$is_hole = true;
				}
				if ( $is_hole ) {
					continue;
				}

				$points = $this->build_hexagon_points( $center_x, $center_y, $radius );
				$fill   = $colors[ array_rand( $colors ) ];
				imagefilledpolygon( $image, $points, 6, $fill );
				imagepolygon( $image, $points, 6, $outline_dark );

				$key          = $row . ':' . $col;
				$cells[ $key ] = array(
					'x'   => $center_x,
					'y'   => $center_y,
					'row' => $row,
					'col' => $col,
				);
			}
		}

		if ( false === $outline_dark ) {
			$outline_dark = $colors[ array_rand( $colors ) ];
		}

		foreach ( $cells as $cell ) {
			$neighbors = array(
				$cell['row'] . ':' . ( $cell['col'] + 1 ),
				$cell['row'] . ':' . ( $cell['col'] - 1 ),
				( $cell['row'] + 1 ) . ':' . $cell['col'],
				( $cell['row'] - 1 ) . ':' . $cell['col'],
			);

			foreach ( $neighbors as $neighbor_key ) {
				if ( ! isset( $cells[ $neighbor_key ] ) ) {
					continue;
				}

				$neighbor = $cells[ $neighbor_key ];
				imageline(
					$image,
					(int) round( $cell['x'] ),
					(int) round( $cell['y'] ),
					(int) round( $neighbor['x'] ),
					(int) round( $neighbor['y'] ),
					$outline_dark
				);
			}
		}
	}

	/**
	 * Builds hexagon point list.
	 *
	 * @param float $center_x Center X.
	 * @param float $center_y Center Y.
	 * @param float $radius Radius.
	 * @return array<int, int>
	 */
	private function build_hexagon_points( $center_x, $center_y, $radius ) {
		$points = array();
		for ( $i = 0; $i < 6; $i++ ) {
			$angle    = deg2rad( 60 * $i );
			$points[] = (int) round( $center_x + ( $radius * cos( $angle ) ) );
			$points[] = (int) round( $center_y + ( $radius * sin( $angle ) ) );
		}

		return $points;
	}
}

