<?php
/**
 * Front-End Assets
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load front-end assets
 *
 * @since 1.0
 * @return void
 */
function nml_load_front_end_assets() {

	if ( ! apply_filters( 'nml_load_assets', true ) ) {
		return;
	}

	$js_dir  = NML_PLUGIN_URL . 'assets/js/';
	$css_dir = NML_PLUGIN_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	/*
	 * JavaScript
	 */
	$deps = array( 'jquery' );

	wp_enqueue_script( 'naked-mailing-list', $js_dir . 'front-end' . $suffix . '.js', $deps, NML_VERSION, true );

	$settings = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' )
	);
	wp_localize_script( 'naked-mailing-list', 'NML', $settings );

}

add_action( 'wp_enqueue_scripts', 'nml_load_front_end_assets' );