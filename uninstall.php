<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package GenArtFeaturedImages
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'genart_featured_images_settings' );
