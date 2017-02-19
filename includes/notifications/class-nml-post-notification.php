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
	 * Notification ID
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $ID;

	/**
	 * Whether or not the notification is active
	 *
	 * @var int 1 if active, 0 if not.
	 * @access public
	 * @since  1.0
	 */
	public $active;

	/**
	 * Notification subject
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $subject;

	/**
	 * Body of the notification
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $body;

	/**
	 * Email from address for this notification
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $from_address;

	/**
	 * Email from name for this notification
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $from_name;

	/**
	 * Email reply-to address for this notification
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $reply_to_address;

	/**
	 * Email reply-to name for this notification
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $reply_to_name;

	/**
	 * Number of campaigns that have been sent based on this notification
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $number_campaigns;

	/**
	 * Post type to send (or 'any' for all)
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $post_type;

	/**
	 * String of comma-separated list IDs to send the campaign to
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $lists;

	/**
	 * Post object that is being sent
	 *
	 * @var WP_Post
	 * @access public
	 * @since  1.0
	 */
	public $post;

	/**
	 * The database abstraction
	 *
	 * @var NML_DB_Notifications
	 * @access protected
	 * @since  1.0
	 */
	protected $db;

	/**
	 * NML_Post_Notification constructor.
	 *
	 * @param object|int   $object_or_id Database object or notification ID.
	 * @param WP_Post|null $post         Associated post object.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $object_or_id = 0, $post = null ) {

		$this->db = new NML_DB_Notifications();

		if ( is_object( $object_or_id ) ) {
			$notification = $object_or_id;
		} else {
			$notification = naked_mailing_list()->notifications->get_notification_by( 'ID', $object_or_id );
		}

		if ( empty( $notification ) || ! is_object( $notification ) ) {
			return;
		}

		if ( ! empty( $post ) ) {
			$this->post = $post;
		}

		$this->setup_notification( $notification );

	}

	/**
	 * Given the notification data, set up all the environment variables.
	 *
	 * @param object $notification Notification object from the database.
	 *
	 * @access private
	 * @since  1.0
	 * @return bool
	 */
	private function setup_notification( $notification ) {

		if ( ! is_object( $notification ) ) {
			return false;
		}

		foreach ( $notification as $key => $value ) {
			$this->$key = $value;
		}

		if ( ! empty( $this->ID ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Verifies whether or not the WP_Post object is a valid post type for
	 * this notification.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function is_valid_post_type() {
		return ( 'any' == $this->post_type || $this->post->post_type == $this->post_type );
	}

	/**
	 * Search content for template tags and replace with dynamic values from
	 * the post object.
	 *
	 * @param string $content
	 *
	 * @access private
	 * @since  1.0
	 * @return string
	 */
	private function parse_tags( $content ) {

		// Post title
		$content = str_replace( '{post_title}', $this->post->post_title, $content );

		// Post content
		$content = str_replace( '{post_content}', $this->post->post_content, $content );

		// Post excerpt
		$content = str_replace( '{post_excerpt}', $this->post->post_excerpt, $content );

		// Filters
		if ( apply_filters( 'nml_post_notification_content_filter', true ) ) {
			$content = apply_filters( 'the_content', $content );
		}

		return $content;

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

		$list_string = $this->lists;
		$list_array  = array_map( 'absint', explode( ',', $list_string ) );

		if ( empty( $list_array ) ) {
			return false;
		}

		$subject = $this->parse_tags( $this->subject );
		$message = $this->parse_tags( $this->body );

		$newsletter_id = nml_insert_newsletter( array(
			'status'           => 'sending',
			'subject'          => $subject,
			'body'             => $message,
			'from_address'     => $this->from_address,
			'from_name'        => $this->from_name,
			'reply_to_address' => $this->reply_to_address,
			'reply_to_name'    => $this->reply_to_name,
			'lists'            => $list_array
		) );

		// Error.
		if ( empty( $newsletter_id ) || is_wp_error( $newsletter_id ) ) {
			return false;
		}

		// Increment.
		$this->increment_campaign_number();

		return true;

	}

	/**
	 * Increment campaign number
	 *
	 * @access public
	 * @since  1.0
	 * @return int New number.
	 */
	public function increment_campaign_number() {
		$new_number = $this->number_campaigns + 1;

		naked_mailing_list()->notifications->update( $this->ID, array( 'number_campaigns' => $new_number ) );

		return $new_number;
	}

}