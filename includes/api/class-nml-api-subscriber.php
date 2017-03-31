<?php

/**
 * REST API Subscriber Object
 *
 * Extends the NML_Subscriber class to make extra information available.
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
 * Class NML_API_Subscriber
 *
 * @since 1.0
 */
class NML_API_Subscriber extends NML_Subscriber {

	/**
	 * Lists the subscriber is part of
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $lists;

	/**
	 * Tags the subscriber has
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $tags;

	/**
	 * Setup properties for the API response.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function setup_api_properties() {

		// Setup lists
		$lists     = $this->get_lists();
		$new_lists = array();

		if ( is_array( $lists ) ) {
			foreach ( $lists as $list ) {
				$new_lists[ $list->ID ] = $list->name;
			}
		}

		$this->lists = $new_lists;

		// Setup tags
		$tags     = $this->get_tags();
		$new_tags = array();

		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				$new_tags[ $tag->ID ] = $tag->name;
			}
		}

		$this->tags = $new_tags;

	}

}