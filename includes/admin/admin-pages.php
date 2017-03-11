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
	add_submenu_page( 'nml-newsletters', esc_html__( 'Lists', 'naked-mailing-list' ), esc_html__( 'Lists', 'naked-mailing-list' ), 'manage_options', 'nml-lists', 'nml_lists_page' );
	add_submenu_page( 'nml-newsletters', esc_html__( 'Newsletter Settings', 'naked-mailing-list' ), esc_html__( 'Settings', 'naked-mailing-list' ), 'manage_options', 'nml-settings', 'nml_options_page' );

}

add_action( 'admin_menu', 'nml_add_menu_pages' );

/**
 * Determines whether the current admin page is a Naked Mailing List page.
 *
 * @since 1.0
 * @return bool
 */
function nml_is_admin_page() {

	$screen      = get_current_screen();
	$is_nml_page = false;

	$nml_page_ids = array(
		'toplevel_page_nml-newsletters',
		'newsletter_page_nml-subscribers',
		'newsletter_page_nml-lists',
		'newsletter_page_nml-notifications'
	);

	if ( in_array( $screen->id, $nml_page_ids ) ) {
		$is_nml_page = true;
	}

	return apply_filters( 'nml_is_admin_page', $is_nml_page, $screen );

}