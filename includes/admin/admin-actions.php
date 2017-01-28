<?php
/**
 * Admin Actions
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
 * Processes all actions sent via POST and GET by looking for the 'nml_action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function nml_process_actions() {
	if ( isset( $_POST['nml_action'] ) ) {
		do_action( 'nml_' . $_POST['nml_action'], $_POST );
	}

	if ( isset( $_GET['nml_action'] ) ) {
		do_action( 'nml_' . $_GET['nml_action'], $_GET );
	}
}

add_action( 'admin_init', 'nml_process_actions' );