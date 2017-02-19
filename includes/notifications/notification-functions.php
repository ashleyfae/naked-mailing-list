<?php
/**
 * Post Notification Functions
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
 * Get notifications
 *
 * @param array $args
 *
 * @since 1.0
 * @return array
 */
function nml_get_notifications( $args = array() ) {
	return naked_mailing_list()->notifications->get_notifications( $args );
}

/**
 * Insert or update post notification
 *
 * @param array $notification_data Array of notification data. Arguments include:
 *                                 `ID` - To update an existing notification (optional).
 *                                 `name` - Name of the notification (for admin use only).
 *                                 `active` - Either 1 for active, or 0 for inactive.
 *                                 `subject` - Newsletter subject.
 *                                 `body` - Content of the newsletter.
 *                                 `from_address` - Email to send the newsletter from.
 *                                 `from_name` - Name to send the newsletter from.
 *                                 `reply_to_address` - Email to reply to.
 *                                 `reply_to_name` - Reply-to name.
 *                                 `number_campaigns` - Number of campaigns sent with this notification.
 *                                 `post_type` - Post type to associate with this notification.
 *                                 `lists` - Comma-separated string of list IDs to send the campaigns to.
 *
 * @since 1.0
 * @return int|WP_Error Notification ID on success, or WP_Error on failure.
 */
function nml_insert_post_notification( $notification_data ) {

	$args = array();

	$args['name']             = array_key_exists( 'name', $notification_data ) ? sanitize_text_field( $notification_data['name'] ) : '';
	$args['active']           = ( array_key_exists( 'active', $notification_data ) && 1 === absint( $notification_data['active'] ) ) ? 1 : 0;
	$args['subject']          = array_key_exists( 'subject', $notification_data ) ? sanitize_text_field( $notification_data['subject'] ) : '';
	$args['body']             = array_key_exists( 'body', $notification_data ) ? sanitize_text_field( $notification_data['body'] ) : '';
	$args['from_address']     = array_key_exists( 'from_address', $notification_data ) ? sanitize_text_field( $notification_data['from_address'] ) : '';
	$args['from_name']        = array_key_exists( 'from_name', $notification_data ) ? sanitize_text_field( $notification_data['from_name'] ) : '';
	$args['reply_to_address'] = array_key_exists( 'reply_to_address', $notification_data ) ? sanitize_text_field( $notification_data['reply_to_address'] ) : '';
	$args['reply_to_name']    = array_key_exists( 'reply_to_name', $notification_data ) ? sanitize_text_field( $notification_data['reply_to_name'] ) : '';
	$args['number_campaigns'] = array_key_exists( 'number_campaigns', $notification_data ) ? absint( $notification_data['number_campaigns'] ) : 0;
	$args['post_type']        = array_key_exists( 'post_type', $notification_data ) ? sanitize_text_field( $notification_data['post_type'] ) : 'post';
	$args['lists']            = array_key_exists( 'lists', $notification_data ) ? sanitize_text_field( $notification_data['lists'] ) : '';

	if ( array_key_exists( 'ID', $notification_data ) ) {
		$args['ID'] = absint( $notification_data['ID'] );
	}

	$notification_id = naked_mailing_list()->notifications->add( $args );

	if ( empty( $notification_id ) ) {
		return new WP_Error( 'error-inserting-notification', __( 'Error inserting notification into the database.', 'naked-mailing-list' ) );
	}

	return $notification_id;

}

/**
 * Create newsletter from new published post.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post object.
 *
 * @since 1.0
 * @return void
 */
function nml_create_campaign_from_post( $new_status, $old_status, $post ) {

	// Bail if not publishing for the first time.
	if ( 'publish' != $new_status || 'publish' == $old_status ) {
		return;
	}

	$notifications = nml_get_notifications( array(
		'active' => 1
	) );

	// Bail if there are no post notifications.
	if ( empty( $notifications ) ) {
		return;
	}

	foreach ( $notifications as $notification ) {

		$notify_object = new NML_Post_Notification( $notification, $post );
		$notify_object->create_campaign();

	}

}

add_action( 'transition_post_status', 'nml_create_campaign_from_post', 10, 3 );