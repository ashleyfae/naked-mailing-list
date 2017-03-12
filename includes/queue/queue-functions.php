<?php
/**
 * Queue Functions
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
 * Check to see if there are any queue entries and if so, process them
 * Runs every minute via cron.
 *
 * @since 1.0
 * @return void
 */
function nml_check_and_process_queue() {

	$entries = naked_mailing_list()->queue->get_entries( array(
		'number'          => 1,
		'orderby'         => 'ID',
		'order'           => 'ASC',
		'status'          => 'pending',
		'date_to_process' => array( 'end' => current_time( 'mysql', true ) )
	) );

	if ( empty( $entries ) || ! is_array( $entries ) ) {
		return;
	}

	$entry = $entries[0];

	nml_process_queue_entry( $entry );

}

add_action( 'nml_every_minute_scheduled_events', 'nml_check_and_process_queue' );

/**
 * Process a specific queue entry.
 *
 * @param object|int $entry Entry DB object or ID number.
 *
 * @since 1.0
 */
function nml_process_queue_entry( $entry ) {

	global $wpdb;

	if ( is_numeric( $entry ) ) {
		$entry = naked_mailing_list()->queue->get_entry_by( 'ID', absint( $entry ) );
	}

	if ( ! is_object( $entry ) ) {
		return false;
	}

	// Update status.
	naked_mailing_list()->queue->update( $entry->ID, array(
		'status' => 'processing'
	) );

	$newsletter  = new NML_Newsletter( $entry->newsletter_id );
	$subscribers = $newsletter->get_subscribers( array(
		'number' => nml_number_subscribers_per_batch(),
		'offset' => absint( $entry->offset )
	) );

	// @todo send
	if ( ! empty( $subscribers ) ) {
		$email = nml_get_email_provider();
		$email->set_newsletter( $newsletter );
		$email->set_recipients( $subscribers );
		$result = $email->send();

		if ( ! $result ) {
			error_log( sprintf( 'Sending error: %s', var_export( $result ) ) );
			// @todo log error for real

			// Delay this log entry by 5 minutes.
			naked_mailing_list()->queue->update( $entry->ID, array(
				'status'          => 'pending',
				'date_to_process' => gmdate( 'Y-m-d H:i:s', strtotime( '+5 minutes' ) )
			) );

			return false;
		}
	} else {
		// @todo log no subscribers
	}

	// Delete this queue entry.
	naked_mailing_list()->queue->delete( $entry->ID );

	// Mark queue entry as processed.
	/*naked_mailing_list()->queue->update( $entry->ID, array(
		'status' => 'completed'
	) );*/

	if ( count( $subscribers ) < nml_number_subscribers_per_batch() ) {

		// We're all finished! Update the newsletter status.
		$newsletter->update( array(
			'status'    => 'sent',
			'sent_date' => gmdate( 'Y-m-d H:i:s' )
		) );

		// Increment the email count for all subscribers.
		$newsletter_lists = $newsletter->get_lists();

		if ( ! empty( $newsletter_lists ) && is_array( $newsletter_lists ) ) {
			$list_ids = wp_list_pluck( $newsletter_lists, 'ID' );

			naked_mailing_list()->subscribers->increment_email_count( array(
				'list' => $list_ids
			) );
		}

	} else {

		// There's still more to do - create the next entry.
		naked_mailing_list()->queue->add( array(
			'newsletter_id' => absint( $entry->newsletter_id ),
			'offset'        => ( $entry->offset ) + nml_number_subscribers_per_batch()
		) );

	}

	return true;

}