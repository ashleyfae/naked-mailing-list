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

/*
 * Below: Newsletter Pages
 */

/**
 * Get admin page: subscribers list
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_newsletters() {
	$url = admin_url( 'admin.php?page=nml-newsletters' );

	return apply_filters( 'nml_admin_page_newsletters', $url );
}

/**
 * Get admin page: add newsletter
 *
 * @param string $type Type of newsletter to add.
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_add_newsletter( $type = 'newsletter' ) {
	$newsletter_page = nml_get_admin_page_newsletters();

	$add_newsletter_page = add_query_arg( array(
		'view' => 'add',
		'type' => urlencode( $type )
	), $newsletter_page );

	return apply_filters( 'nml_admin_page_add_newsletter', $add_newsletter_page );
}

/**
 * Get admin page: edit newsletter
 *
 * @param int $newsletter_id ID of the newsletter to edit.
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_edit_newsletter( $newsletter_id ) {
	$newsletter_page = nml_get_admin_page_newsletters();

	$edit_newsletter_page = add_query_arg( array(
		'view' => 'edit',
		'ID'   => absint( $newsletter_id )
	), $newsletter_page );

	return apply_filters( 'nml_admin_page_edit_newsletter', $edit_newsletter_page );
}

/**
 * Get admin page: delete newsletter
 *
 * @todo  actually make this work
 *
 * @param int $newsletter_id ID of the newsletter to delete.
 *
 * @since 1.0
 * @return string
 */
function nml_get_admin_page_delete_newsletter( $newsletter_id ) {
	$newsletter_page = nml_get_admin_page_newsletters();

	$delete_newsletter_page = add_query_arg( array(
		'nml_action' => urlencode( 'delete_newsletter' ),
		'ID'         => absint( $newsletter_id ),
		'nonce'      => wp_create_nonce( 'nml_delete_newsletter' )
	), $newsletter_page );

	return apply_filters( 'nml_admin_page_delete_newsletter', $delete_newsletter_page );
}

/*
 * Below: Subscriber Pages
 */

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

/**
 * Format MySQL date for display
 *
 * @param string      $mysql_date MySQL date in GMT timezone.
 * @param bool|string $format     Date format or leave false to use WP date setting.
 *
 * @since 1.0
 * @return bool|int|string Formatted date in blog's timezone.
 */
function nml_format_mysql_date( $mysql_date, $format = false ) {

	if ( empty( $mysql_date ) ) {
		return false;
	}

	if ( false == $format ) {
		$format = get_option( 'date_format' );
	}

	$gmt_date = $mysql_date ? get_date_from_gmt( $mysql_date, 'U' ) : false;
	$date     = date_i18n( $format, $gmt_date );

	return $date;

}

/**
 * Get full date and time format
 *
 * Joins together the 'date' and 'time' settings.
 *
 * @since 1.0
 * @return string
 */
function nml_full_date_time_format() {
	$date = get_option( 'date_format' );
	$time = get_option( 'time_format' );

	return $date . ' ' . $time;
}