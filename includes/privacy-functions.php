<?php
/**
 * Privacy Functions
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2018, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register privacy exporters
 *
 * @param array $exporters
 *
 * @since 1.0
 * @return array
 */
function nml_register_privacy_exporters( $exporters ) {

	$exporters[] = array(
		'exporter_friendly_name' => __( 'Mailing List Record', 'naked-mailing-list' ),
		'callback'               => 'nml_privacy_subscriber_record_exporter',
	);

	return $exporters;

}

add_filter( 'wp_privacy_personal_data_exporters', 'nml_register_privacy_exporters' );

/**
 * Retrieve the subscriber record for the privacy data exporter
 *
 * @param string $email_address Email address being requested.
 * @param int    $page          Page number.
 *
 * @since 1.0
 * @return array
 */
function nml_privacy_subscriber_record_exporter( $email_address = '', $page = 1 ) {

	$subscriber  = new NML_Subscriber( $email_address );
	$export_data = array();

	if ( ! empty( $subscriber->ID ) ) {
		$export_data = array(
			'group_id'    => 'nml-subscriber-record',
			'group_label' => __( 'Mailing List Subscriber Record', 'naked-mailing-list' ),
			'item_id'     => 'nml-subscriber-record-' . $subscriber->ID,
			'data'        => array(
				array(
					'name'  => __( 'Subscriber ID', 'naked-mailing-list' ),
					'value' => $subscriber->ID
				),
				array(
					'name'  => __( 'Email', 'naked-mailing-list' ),
					'value' => $subscriber->email
				),
				array(
					'name'  => __( 'First Name', 'naked-mailing-list' ),
					'value' => $subscriber->first_name
				),
				array(
					'name'  => __( 'Last Name', 'naked-mailing-list' ),
					'value' => $subscriber->last_name
				),
				array(
					'name'  => __( 'Status', 'naked-mailing-list' ),
					'value' => $subscriber->status
				),
				array(
					'name'  => __( 'Signup Date', 'naked-mailing-list' ),
					'value' => $subscriber->signup_date
				),
				array(
					'name'  => __( 'Confirm Date', 'naked-mailing-list' ),
					'value' => $subscriber->confirm_date
				),
				array(
					'name'  => __( 'IP Address', 'naked-mailing-list' ),
					'value' => $subscriber->ip
				),
				array(
					'name'  => __( 'Referer', 'naked-mailing-list' ),
					'value' => $subscriber->referer
				),
				array(
					'name'  => __( 'Signup Form', 'naked-mailing-list' ),
					'value' => $subscriber->form_name
				),
				array(
					'name'  => __( 'Number of Emails Received', 'naked-mailing-list' ),
					'value' => $subscriber->email_count
				)
			)
		);
	}

	return array( 'data' => array( $export_data ), 'done' => true );

}

/**
 * Register eraser.
 *
 * @param array $erasers
 *
 * @since 1.0
 * @return array
 */
function nml_privacy_erasers( $erasers = array() ) {

	$erasers[] = array(
		'eraser_friendly_name' => __( 'Mailing List Subscriber Record', 'naked-mailing-list' ),
		'callback'             => 'nml_privacy_subscriber_record_eraser'
	);

	return $erasers;

}

add_filter( 'wp_privacy_personal_data_erasers', 'nml_privacy_erasers' );

/**
 * Erase mailing list data.
 *
 * @param string $email_address
 * @param int    $page
 *
 * @since 1.0
 * @return array
 */
function nml_privacy_subscriber_record_eraser( $email_address, $page = 1 ) {

	$subscriber = new NML_Subscriber( $email_address );

	$messages       = array();
	$items_removed  = false;
	$items_retained = false;

	if ( empty( $subscriber->ID ) ) {
		$messages[] = __( 'No subscriber record found.', 'naked-mailing-list' );

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => true
		);
	}

	$result = nml_subscriber_delete( $subscriber->ID );

	if ( is_wp_error( $result ) ) {
		$items_retained = true;
		$messages[]     = __( 'Your mailing list subscriber record was unable to be removed at this time.', 'naked-mailing-list' );
	} else {
		$items_removed = true;
	}

	return array(
		'items_removed'  => $items_removed,
		'items_retained' => $items_retained,
		'messages'       => $messages,
		'done'           => true
	);

}