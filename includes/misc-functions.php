<?php
/**
 * Misc Functions
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
 * Get admin page: subscribers list
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_subscribers() {
	$url = admin_url( 'admin.php?page=nml-subscribers' );

	return apply_filters( 'nml_admin_page_subscribers', $url );
}

/**
 * Get admin page: add subscriber
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_add_subscriber() {
	$subscriber_page = nml_get_admin_page_subscribers();

	$add_sub_page = add_query_arg( array(
		'view' => 'add'
	), $subscriber_page );

	return apply_filters( 'nml_admin_page_add_subscriber', $add_sub_page );
}

/**
 * Get admin page: edit subscriber
 *
 * @param int $sub_id ID of the subscriber to edit.
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_edit_subscriber( $sub_id ) {
	$subscriber_page = nml_get_admin_page_subscribers();

	$edit_sub_page = add_query_arg( array(
		'view' => 'edit',
		'ID'   => absint( $sub_id )
	), $subscriber_page );

	return apply_filters( 'nml_admin_page_edit_subscriber', $edit_sub_page );
}

/**
 * Get admin page: delete subscriber
 *
 * @todo  actually make this work
 *
 * @param int $sub_id ID of the subscriber to delete.
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_delete_subscriber( $sub_id ) {
	$subscriber_page = nml_get_admin_page_subscribers();

	$delete_sub_page = add_query_arg( array(
		'nml_action' => urlencode( 'delete_subscriber' ),
		'ID'         => absint( $sub_id ),
		'nonce'      => wp_create_nonce( 'nml_delete_subscriber' )
	), $subscriber_page );

	return apply_filters( 'nml_admin_page_delete_subscriber', $delete_sub_page );
}

/**
 * Get User IP
 *
 * Returns the IP address of the current visitor
 *
 * @since 1.0
 * @return string User's IP address
 */
function nml_get_ip() {

	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	// Fix potential CSV returned from $_SERVER variables
	$ip_array = explode( ',', $ip );
	$ip_array = array_map( 'trim', $ip_array );

	return apply_filters( 'nml_get_ip', $ip_array[0] );
}