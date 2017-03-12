<?php
/**
 * Load Admin Assets
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
 * Load Admin Assets
 *
 * Only load scripts and stylesheets if on a designated NML admin page.
 *
 * @param string $hook Currently loaded page.
 *
 * @since 1.0
 * @return void
 */
function nml_load_admin_assets( $hook ) {

	if ( ! apply_filters( 'nml_load_admin_assets', nml_is_admin_page(), $hook ) ) {
		return;
	}

	$js_dir  = NML_PLUGIN_URL . 'assets/js/';
	$css_dir = NML_PLUGIN_URL . 'assets/css/';
	$screen  = get_current_screen();

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// JS
	$deps = array( 'jquery', 'jquery-form' );
	wp_enqueue_script( 'naked-mailing-list', $js_dir . 'admin-scripts' . $suffix . '.js', $deps, NML_VERSION, true );
	wp_localize_script( 'naked-mailing-list', 'nml_vars', array(
		'unsupported_browser' => __( 'Sorry but your browser is not compatible with this kind of file upload. Please upgrade your browser.', 'naked-mailing-list' )
	) );

	// CSS
	wp_enqueue_style( 'naked-mailing-list', $css_dir . 'admin' . $suffix . '.css', array(), NML_VERSION );

}

add_action( 'admin_enqueue_scripts', 'nml_load_admin_assets' );