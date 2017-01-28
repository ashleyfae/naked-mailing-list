<?php
/**
 * Admin Pages
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
 * Add Menu Pages
 *
 * @since 1.0
 * @return void
 */
function nml_add_menu_pages() {

	add_menu_page( esc_html__( 'Newsletters', 'naked-mailing-list' ), esc_html__( 'Newsletter', 'naked-mailing-list' ), 'manage_options', 'nml-newsletters', 'nml_newsletters_page', 'dashicons-email-alt' );
	add_submenu_page( 'nml-newsletters', esc_html__( 'Subscribers', 'naked-mailing-list' ), esc_html__( 'Subscribers', 'naked-mailing-list' ), 'manage_options', 'nml-subscribers', 'nml_subscribers_page' );

}

add_action( 'admin_menu', 'nml_add_menu_pages' );

function nml_newsletters_page() {
	// @todo move
}

/**
 * Determines whether the current admin page is a Naked Mailing List page.
 *
 * @since 1.0
 * @return bool
 */
function nml_is_admin_page() {
	// @todo
}