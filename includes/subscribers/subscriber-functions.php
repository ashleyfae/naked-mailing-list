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

/**
 * Get subscriber by email address
 *
 * @param string $email
 *
 * @since 1.0
 * @return NML_Subscriber|false
 */
function nml_get_subscriber_by_email( $email ) {

	$subscribers = nml_get_subscribers( array(
		'number' => 1,
		'email'  => $email
	) );

	if ( empty( $subscribers ) || ! is_array( $subscribers ) || ! array_key_exists( 0, $subscribers ) ) {
		return false;
	}

	return new NML_Subscriber( $subscribers[0] );

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
 * Delete a subscriber
 *
 * @param int $subscriber_id ID of the subscriber to delete.
 *
 * @since 1.0
 * @return true|WP_Error
 */
function nml_delete_subscriber( $subscriber_id ) {
	$result = naked_mailing_list()->subscribers->delete( $subscriber_id );

	if ( ! $result ) {
		return new WP_Error( 'failed-deleting-subscriber', __( 'An error occurred while deleting the subscriber.', 'naked-mailing-list' ) );
	}

	// Delete all list relationships.
	naked_mailing_list()->list_relationships->delete_subscriber_relationships( $subscriber_id ); // @todo recount

	return true;
}

/**
 * Send subscriber a confirmation email
 *
 * @param string         $old_status    Previous status before the update.
 * @param int            $subscriber_id Subscriber ID.
 * @param NML_Subscriber $subscriber    Subscriber object.
 *
 * @since 1.0
 * @return void
 */
function nml_send_subscriber_confirmation( $old_status, $subscriber_id, $subscriber ) {
	$subscriber->send_confirmation_email();
}

add_action( 'nml_subscriber_set_pending', 'nml_send_subscriber_confirmation', 10, 3 );

/**
 * Confirm subscriber and redirect to success page
 *
 * @since 1.0
 * @return void
 */
function nml_confirm_subscriber() {

	if ( ! isset( $_GET['nml_action'] ) || 'confirm' != $_GET['nml_action'] ) {
		return;
	}

	$subscriber_id = isset( $_GET['subscriber'] ) ? urldecode( $_GET['subscriber'] ) : 0;

	if ( empty( $subscriber_id ) ) {
		return;
	}

	$subscriber = new NML_Subscriber( $subscriber_id );
	$query_args = array(
		'nml-action'  => 'confirm-email',
		'nml-message' => ''
	);

	// Subscriber doesn't exist.
	if ( empty( $subscriber->ID ) ) {
		$query_args['nml-message'] = 'invalid-subscriber';
		nml_log( sprintf( 'Email Confirmation Error: Invalid subscriber ID #%d.', $subscriber_id ) );
	} else {

		// Check verification
		if ( isset( $_GET['key'] ) && md5( $subscriber->ID . $subscriber->email ) == urldecode( $_GET['key'] ) ) {
			$subscriber->confirm();
			$query_args['nml-message'] = 'email-confirmed';
		} else {
			$query_args['nml-message'] = 'invalid-subscriber-key';
			nml_log( sprintf( 'Email Confirmation Error: Invalid subscriber key for #%d. Provided: %s; Should Be: %s.', $subscriber->ID, urldecode( $_GET['key'] ), md5( $subscriber->ID, $subscriber->email ) ) );
		}

	}

	wp_safe_redirect( add_query_arg( $query_args, home_url() ) );

	exit;

}

add_action( 'template_redirect', 'nml_confirm_subscriber' );

/**
 * Unsubscribe a person
 *
 * @param int $subscriber_id ID of the subscriber to unsubscribe.
 *
 * @since 1.0
 * @return bool
 */
function nml_unsubscribe( $subscriber_id ) {
	$subscriber = new NML_Subscriber( $subscriber_id );

	return $subscriber->unsubscribe();
}

/**
 * Process unsubscribe
 *
 * @since 1.0
 * @return void
 */
function nml_process_unsubscribe() {

	if ( ! isset( $_GET['nml_action'] ) || 'unsubscribe' != $_GET['nml_action'] ) {
		return;
	}

	if ( empty( $_GET['subscriber'] ) || empty( $_GET['ID'] ) ) {
		nml_log( sprintf( 'Unsubscribe Error: Missing "subscriber" or "ID" args. Provided: %s', var_export( $_GET, true ) ) );

		return;
	}

	$email      = base64_decode( strtr( urldecode( $_GET['subscriber'] ), '._-', '+/=' ) );
	$id         = urldecode( $_GET['ID'] );
	$subscriber = new NML_Subscriber( absint( $id ) );
	$query_args = array(
		'nml-action'  => 'unsubscribe',
		'nml-message' => ''
	);

	if ( $subscriber->email != $email ) {
		$query_args['nml-message'] = 'invalid-subscriber';
	} else {
		$result = $subscriber->unsubscribe();

		if ( $result ) {
			$query_args['nml-message'] = 'successfully-unsubscribed';
		} else {
			$query_args['nml-message'] = 'unexpected-error';
			nml_log( sprintf( 'Unsubscribe Error: Unexpected error. Result: %s', var_export( $result, true ) ) );
		}
	}

	wp_safe_redirect( add_query_arg( $query_args, home_url() ) );

	exit;

}

add_action( 'template_redirect', 'nml_process_unsubscribe' );

/**
 * Resend confirmation emails to pending users x days after subscribing
 *
 * @since 1.0
 * @return void
 */
function nml_schedule_resend_confirmations() {

	$days = nml_get_option( 'resend_confirmations', 7 );

	if ( empty( $days ) ) {
		return;
	}

	nml_log( 'Beginning cron job: resending confirmations.' );

	$period = '-' . absint( $days ) . 'days';

	// Get all subscribers who signed up x days ago.
	$subscribers = nml_get_subscribers( array(
		'number'      => - 1,
		'status'      => 'pending',
		'signup_date' => array(
			'start' => date( 'Y-m-d H:i:s', strtotime( $period . ' midnight' ) ),
			'end'   => date( 'Y-m-d H:i:s', strtotime( $period . ' midnight' ) + ( DAY_IN_SECONDS - 1 ) )
		)
	) );

	if ( empty( $subscribers ) || ! is_array( $subscribers ) ) {
		return;
	}

	foreach ( $subscribers as $subscriber ) {
		$object = new NML_Subscriber();
		$object->setup_subscriber( $subscriber );

		// Don't send the email twice.
		if ( $object->get_meta( 'confirmation_resent' ) ) {
			nml_log( sprintf( 'Confirmation email already sent for subscriber #%d - skipping.', $object->ID ) );

			continue;
		}

		$object->send_confirmation_email();
		$object->add_meta( 'confirmation_resent', current_time( 'mysql', true ) );

		nml_log( sprintf( 'Confirmation email resent to subscriber #%d via cron job.', $object->ID ) );
	}

}

add_action( 'nml_daily_scheduled_events', 'nml_schedule_resend_confirmations' );

/**
 * Delete unconfirmed subscribers x days after subscribing
 *
 * @since 1.0
 * @return void
 */
function nml_schedule_delete_unconfirmed() {

	$days = nml_get_option( 'delete_unconfirmed', 30 );

	if ( empty( $days ) ) {
		return;
	}

	nml_log( 'Beginning cron job: delete unconfirmed subscribers.' );

	$period = '-' . absint( $days ) . 'days';

	// Get all subscribers who signed up x days ago.
	$subscribers = nml_get_subscribers( array(
		'number'      => - 1,
		'status'      => 'pending',
		'signup_date' => array(
			'start' => date( 'Y-m-d H:i:s', strtotime( $period . ' midnight' ) ),
			'end'   => date( 'Y-m-d H:i:s', strtotime( $period . ' midnight' ) + ( DAY_IN_SECONDS - 1 ) )
		),
		'fields'      => 'ID'
	) );

	if ( empty( $subscribers ) || ! is_array( $subscribers ) ) {
		return;
	}

	nml_log( sprintf( 'Deleting these unconfirmed subscriber IDs: %s', implode( ', ', $subscribers ) ) );

	naked_mailing_list()->subscribers->delete_by_ids( $subscribers );

}

add_action( 'nml_daily_scheduled_events', 'nml_schedule_delete_unconfirmed' );