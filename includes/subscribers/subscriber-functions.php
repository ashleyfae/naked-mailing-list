<?php
/**
 * Subscriber Functions
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
 * Insert a new subscriber or update an existing one.
 *
 * If the `ID` key is passed into the `$subscriber_data` array then an existing
 * subscriber is updated.
 *
 * @param array $subscriber_data Array of subscriber data. Arguments include:
 *                               `ID` - To update an existing subscriber (optional).
 *                               `email` - Subscriber's email address (required).
 *                               `first_name` - Subscriber's first name (optional).
 *                               `last_name` - Subscriber's last name (optional).
 *                               `status` - Subscriber's status (default is 'pending').
 *                               `signup_date` - Signup date (default is current time).
 *                               `confirm_date` - Confirmation date for double opt-in (default is null).
 *                               `ip` - Subscriber's IP address. Omit to retrieve automatically from current user.
 *                               `email_count` - Number of emails the subscriber has received. Leave blank to calculate
 *                               automatically.
 *                               `notes` - Subscriber notes (optional).
 *                               `lists` - Array of list IDs to add the subscriber to. @todo
 *                               `tags` - Array of tag names to add to the subscriber. @todo
 *
 * @since 1.0
 * @return int|WP_Error ID of the subscriber inserted or updated, or WP_Error on failure.
 */
function nml_insert_subscriber( $subscriber_data ) {

	$sub_db_data = array();

	$sub_db_data['email']      = array_key_exists( 'email', $subscriber_data ) ? sanitize_email( $subscriber_data['email'] ) : '';
	$sub_db_data['first_name'] = array_key_exists( 'first_name', $subscriber_data ) ? sanitize_text_field( $subscriber_data['first_name'] ) : '';
	$sub_db_data['last_name']  = array_key_exists( 'last_name', $subscriber_data ) ? sanitize_text_field( $subscriber_data['last_name'] ) : '';
	$sub_db_data['status']     = array_key_exists( 'status', $subscriber_data ) ? sanitize_text_field( $subscriber_data['status'] ) : nml_get_default_subscriber_status();
	$sub_db_data['notes']      = array_key_exists( 'notes', $subscriber_data ) ? wp_strip_all_tags( $subscriber_data['notes'] ) : '';

	// Signup date.
	if ( array_key_exists( 'signup_date', $subscriber_data ) ) {
		$sub_db_data['signup_date'] = sanitize_text_field( get_gmt_from_date( wp_strip_all_tags( $subscriber_data['signup_date'] ) ) );
	}

	// Confirm date.
	if ( array_key_exists( 'confirm_date', $subscriber_data ) ) {
		$sub_db_data['confirm_date'] = sanitize_text_field( get_gmt_from_date( wp_strip_all_tags( $subscriber_data['confirm_date'] ) ) );
	}

	// Email count.
	if ( array_key_exists( 'email_count', $subscriber_data ) ) {
		$sub_db_data['email_count'] = absint( $subscriber_data['email_count'] );
	}

	// Subscriber ID.
	if ( array_key_exists( 'ID', $subscriber_data ) ) {
		$sub_db_data['ID'] = absint( $subscriber_data['ID'] );
	}

	// Email is required.
	if ( empty( $sub_db_data['email'] ) ) {
		return new WP_Error( 'missing-email-address', __( 'Error inserting subscriber: email address is required.', 'naked-mailing-list' ) );
	}

	/*
	 * Add/update the subscriber.
	 */

	$sub_id = naked_mailing_list()->subscribers->add( $sub_db_data );

	if ( empty( $sub_id ) ) {
		return new WP_Error( 'error-inserting-subscriber', __( 'Error inserting subscriber into the database.', 'naked-mailing-list' ) );
	}

	return $sub_id;

}

/**
 * Returns an array of all available subscriber statuses.
 *
 * Pending: Awaiting double opt-in confirmation.
 * Subscribed: Actively subscribed to at least one list.
 * Unsubscribed: Not subscribed to any lists.
 *
 * @since 1.0
 * @return array
 */
function nml_get_subscriber_statuses() {

	$statuses = array(
		'pending'      => esc_html__( 'Pending', 'naked-mailing-list' ),
		'subscribed'   => esc_html__( 'Subscribed', 'naked-mailing-list' ),
		'unsubscribed' => esc_html__( 'Unsubscribed', 'naked-mailing-list' )
	);

	return apply_filters( 'nml_subscriber_statuses', $statuses );

}

/**
 * Returns the default status to use when adding new subscribers.
 *
 * @since 1.0
 * @return string
 */
function nml_get_default_subscriber_status() {
	$default = 'pending';

	return apply_filters( 'nml_default_subscriber_status', $default );
}