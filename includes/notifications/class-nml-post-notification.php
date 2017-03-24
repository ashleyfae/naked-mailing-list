<?php

/**
 * Post Notification
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
 * Class NML_Post_Notification
 *
 * @since 1.0
 */
class NML_Post_Notification {

	/**
	 * Post object that is being sent
	 *
	 * @var WP_Post
	 * @access public
	 * @since  1.0
	 */
	public $post;

	/**
	 * List(s) to send the email to
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $lists;

	/**
	 * NML_Post_Notification constructor.
	 *
	 * @param WP_Post|null   $post    Associated post object.
	 * @param int|array|null $list_id List(s) to send the email to.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $post = null, $lists = null ) {

		if ( ! empty( $post ) ) {
			$this->post = $post;
		}

		if ( empty( $lists ) ) {
			$lists = nml_get_option( 'post_notifications' );
		}

		if ( ! is_array( $lists ) ) {
			$lists = array( $lists );
		}

		$this->lists = $lists;
	}

	/**
	 * Verifies whether or not the WP_Post object is a valid post type
	 * for a notification.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function is_valid_post_type() {
		$is_valid = 'post' == $this->post->post_type;

		return (bool) apply_filters( 'nml_is_valid_notification_post_type', $is_valid, $this->post, $this );
	}

	/**
	 * Create campaign
	 *
	 *      1) Check if the post type is valid
	 *      2) Check if we have lists specified
	 *      3) Parse the template tags for post content
	 *      4) Copy to campaign
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function create_campaign() {

		if ( ! $this->is_valid_post_type() ) {
			return false;
		}

		$list_array = array_map( 'absint', $this->lists );

		if ( empty( $list_array ) ) {
			return false;
		}

		$subject = apply_filters( 'nml_post_notification_subject', $this->post->post_title, $this->post, $this );
		$message = nml_get_post_notification_message( $this->post );

		$newsletter_id = nml_insert_newsletter( array(
			'status'           => 'draft',
			'subject'          => $subject,
			'body'             => $message,
			'from_address'     => nml_get_option( 'from_email' ),
			'from_name'        => nml_get_option( 'from_name' ),
			'reply_to_address' => nml_get_option( 'reply_to_email' ),
			'reply_to_name'    => nml_get_option( 'reply_to_name' ),
			'lists'            => $list_array
		) );

		// Error.
		if ( empty( $newsletter_id ) || is_wp_error( $newsletter_id ) ) {
			nml_log( sprintf( __( 'Error creating newsletter from post ID %d.', 'naked-mailing-list' ), $this->post->ID ) );

			return false;
		}

		// Update status to sending.
		$newsletter = new NML_Newsletter( $newsletter_id );
		$newsletter->update( array(
			'status' => 'sending'
		) );

		return true;

	}

}