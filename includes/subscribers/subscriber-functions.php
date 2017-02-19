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
 *                               `lists` - Array of list IDs to add the subscriber to.
 *                               `tags` - Array of tag names to add to the subscriber.
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

	if ( array_key_exists( 'ID', $sub_db_data ) ) {

		// Update existing subscriber.
		$subscriber = new NML_Subscriber( $sub_db_data['ID'] );
		$subscriber->update( $sub_db_data );
		$sub_id = $subscriber->ID;

	} else {

		// Insert new subscriber.
		$sub_id = naked_mailing_list()->subscribers->add( $sub_db_data );

	}

	if ( empty( $sub_id ) ) {
		return new WP_Error( 'error-inserting-subscriber', __( 'Error inserting subscriber into the database.', 'naked-mailing-list' ) );
	}

	/*
	 * Set lists.
	 */

	if ( array_key_exists( 'lists', $subscriber_data ) ) {
		nml_set_object_lists( 'subscriber', $sub_id, $subscriber_data['lists'], 'list', false );
	}
	if ( array_key_exists( 'tags', $subscriber_data ) ) {
		nml_set_object_lists( 'subscriber', $sub_id, $subscriber_data['tags'], 'tag', false );
	}

	/*
	 * Return the subscriber ID.
	 */

	return $sub_id;

}

/**
 * Delete a subscriber
 *
 * Also deletes all related activity logs and list relationships.
 *
 * @param int|string $id_or_email
 *
 * @since 1.0
 * @return true|WP_Error True on successful delete, WP_Error on failure.
 */
function nml_subscriber_delete( $id_or_email ) {

	$subscriber_id = 0;

	if ( is_email( $id_or_email ) ) {
		$subscriber    = naked_mailing_list()->subscribers->get_subscriber_by( 'email', $id_or_email );
		$subscriber_id = is_object( $subscriber ) ? $subscriber->ID : false;
	} elseif ( is_numeric( $id_or_email ) ) {
		$subscriber_id = $id_or_email;
	}

	if ( empty( $subscriber_id ) ) {
		return new WP_Error( 'invalid-subscriber', __( 'Invalid subscriber.', 'naked-mailing-list' ) );
	}

	do_action( 'nml_pre_subscriber_delete', $subscriber_id );

	// First delete the subscriber.
	$successful = naked_mailing_list()->subscribers->delete( $subscriber_id );

	if ( ! $successful ) {
		return new WP_Error( 'error-deleting-subscriber', __( 'An error occurred while deleting the subscriber.', 'naked-mailing-list' ) );
	}

	// Delete all subscriber meta.
	naked_mailing_list()->subscriber_meta->delete_all_subscriber_meta( $subscriber_id );

	// Now delete subscriber activity logs.
	naked_mailing_list()->activity->delete_subscriber_entries( $subscriber_id );

	// Delete subscriber list relationships.
	naked_mailing_list()->list_relationships->delete_subscriber_relationships( $subscriber_id );

	// Update list count.
	foreach ( nml_get_object_lists( 'subscriber', $subscriber_id, false, array( 'fields' => 'ids' ) ) as $list_id ) {
		nml_update_list_count( $list_id );
	}

	do_action( 'nml_after_subscriber_delete', $subscriber_id );

	return true;

}

/**
 * Returns an array of all available subscriber statuses.
 *
 * Pending: Awaiting double opt-in confirmation.
 * Subscribed: Actively subscribed to at least one list.
 * Unsubscribed: Not subscribed to any lists.
 * Bounced: Email(s) could not be delivered.
 *
 * @since 1.0
 * @return array
 */
function nml_get_subscriber_statuses() {

	$statuses = array(
		'pending'      => esc_html__( 'Pending', 'naked-mailing-list' ),
		'subscribed'   => esc_html__( 'Subscribed', 'naked-mailing-list' ),
		'unsubscribed' => esc_html__( 'Unsubscribed', 'naked-mailing-list' ),
		'bounced'      => esc_html__( 'Bounced', 'naked-mailing-list' )
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

/**
 * Number of subscribers to email at one time
 *
 * @since 1.0
 * @return int
 */
function nml_number_subscribers_per_batch() {
	$number = nml_get_option( 'per_batch', 500 );

	return apply_filters( 'nml_number_subscribers_per_batch', $number );
}

/**
 * Get subscribers
 *
 * @param array $args Query arguments.
 *
 * @since 1.0
 * @return array|false
 */
function nml_get_subscribers( $args = array() ) {
	return naked_mailing_list()->subscribers->get_subscribers( $args );
}