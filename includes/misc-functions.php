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

/**
 * Get current page slug
 *
 * @since 1.0
 * @return string
 */
function nml_get_current_page_slug() {
	global $wp;

	return $wp->request;
}

/**
 * Get unsubscribe link
 *
 * @param string $subscriber_email Email of specific subscriber (optional).
 *
 * @since 1.0
 * @return string
 */
function nml_get_unsubscribe_link( $subscriber_email_or_id = '' ) {
	$subscriber = new NML_Subscriber( $subscriber_email_or_id );

	$query_args = array(
		'nml_action' => 'unsubscribe'
	);

	if ( ! empty( $subscriber_email_or_id ) ) {
		$query_args['subscriber'] = urlencode( $subscriber->email );
		$query_args['ID']         = urlencode( $subscriber->ID );
	}

	$url = add_query_arg( $query_args, home_url() );

	return apply_filters( 'nml_unsubscribe_link', $url, $subscriber_email_or_id );
}

/**
 * Month Num To Name
 *
 * Takes a month number and returns the name three letter name of it.
 *
 * Taken from Easy Digital Downloads.
 *
 * @param integer $n
 *
 * @since 1.0
 * @return string Short month name
 */
function nml_month_num_to_name( $n ) {
	$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

	return date_i18n( "M", $timestamp );
}

/**
 * Checks if a value is a valid tiemstamp.
 *
 * @param int|string $timestamp Timestamp to check.
 *
 * @since 1.0
 * @return bool
 */
function nml_is_valid_timestamp( $timestamp ) {
	return ( (string) (int) $timestamp === $timestamp ) && ( $timestamp <= PHP_INT_MAX ) && ( $timestamp >= ~PHP_INT_MAX );
}

/**
 * Log a message if debug mode is enabled
 *
 * @param string $message
 *
 * @since 1.0
 * @return void
 */
function nml_log( $message = '' ) {

	if ( nml_get_option( 'debug_mode' ) ) {
		naked_mailing_list()->logs->log( $message );
	}

}