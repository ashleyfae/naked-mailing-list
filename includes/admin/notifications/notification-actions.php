<?php
/**
 * Notification Actions
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add all newsletter fields to the "Notification Details" box.
 *
 * @since 1.0
 */
add_action( 'nml_edit_notification_info_fields', function ( $notification ) {
	do_action( 'nml_edit_newsletter_info_fields', $notification );
} );

/**
 * Add all newsletter fields to the "Headers" box.
 *
 * @since 1.0
 */
add_action( 'nml_edit_notification_headers_fields', function ( $notification ) {
	do_action( 'nml_edit_newsletter_headers_fields', $notification );
} );

/**
 * Save Notification
 *
 * @since 1.0
 * @return void
 */
function nml_save_notification() {

	$nonce = isset( $_POST['nml_save_notification_nonce'] ) ? $_POST['nml_save_notification_nonce'] : false;

	if ( ! $nonce ) {
		return;
	}

	if ( ! wp_verify_nonce( $nonce, 'nml_save_notification' ) ) {
		wp_die( __( 'Failed security check.', 'naked-mailing-list' ) );
	}

	if ( ! current_user_can( 'edit_posts' ) ) { // @todo maybe change
		wp_die( __( 'You don\'t have permission to edit notifications.', 'naked-mailing-list' ) );
	}

	$notification_id = $_POST['notification_id'];

	$data = array(
		'ID' => $notification_id
	);

	$fields = array(
		'name'             => 'notification_name',
		'active'           => 'nml_notification_activation',
		'subject'          => 'nml_newsletter_subject',
		'body'             => 'nml_newsletter_body',
		'from_name'        => 'nml_newsletter_from_name',
		'from_address'     => 'nml_newsletter_from_address',
		'reply_to_name'    => 'nml_newsletter_reply_to_name',
		'reply_to_address' => 'nml_newsletter_reply_to_address'
	);

	foreach ( $fields as $data_field => $post_name ) {
		if ( isset( $_POST[ $post_name ] ) ) {
			$data[ $data_field ] = $_POST[ $post_name ];
		}
	}

	$new_id = nml_insert_post_notification( $data );

	if ( ! $new_id || is_wp_error( $new_id ) ) {
		wp_die( __( 'An error occurred while inserting the notification.', 'naked-mailing-list' ) );
	}

	$edit_url = add_query_arg( array(
		'nml-message' => 'notification-updated'
	), nml_get_admin_page_edit_notification( absint( $new_id ) ) );

	wp_safe_redirect( $edit_url );

	exit;

}

add_action( 'nml_save_notification', 'nml_save_notification' );