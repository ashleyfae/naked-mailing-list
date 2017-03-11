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