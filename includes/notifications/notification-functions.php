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