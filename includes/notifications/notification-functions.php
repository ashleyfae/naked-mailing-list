<?php
/**
 * Post Notification Functions
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

	$list_id = nml_get_option( 'post_notifications' );

	// Bail if disabled.
	if ( empty( $list_id ) ) {
		return;
	}

	$notify_object = new NML_Post_Notification( $post, $list_id );
	$notify_object->create_campaign();

}

add_action( 'transition_post_status', 'nml_create_campaign_from_post', 10, 3 );

/**
 * Returns the body of the email for post notifications.
 *
 * @param WP_Post|int $post Post object or ID.
 *
 * @since 1.0
 * @return string
 */
function nml_get_post_notification_message( $post ) {
	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	$message = '<h1><a href="' . esc_url( get_permalink( $post ) ) . '">' . $post->post_title . '</a></h1>' . $post->post_content;
	$message .= '<p class="text-center"><a href="' . esc_url( get_comments_link( $post ) ) . '" class="button">' . __( 'Leave a Comment', 'naked-mailing-list' ) . '</a>';

	/**
	 * Modifies the contents of the post notification message.
	 *
	 * @param string  $message Body of the email.
	 * @param WP_Post $post    Post object.
	 *
	 * @since 1.0
	 */
	$message = apply_filters( 'nml_post_notification_message', $message, $post );

	return $message;
}