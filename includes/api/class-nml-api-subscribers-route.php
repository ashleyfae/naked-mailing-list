<?php

/**
 * API Route: Subscribers
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
 * Class NML_API_Subscribers_Route
 *
 * @since 1.0
 */
class NML_API_Subscribers_Route extends WP_REST_Controller {

	/**
	 * Version number
	 *
	 * @var int
	 * @access protected
	 * @since  1.0
	 */
	protected $version;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $namespace;

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $rest_base;

	/**
	 * Whitelist of subscriber fields that can be created/altered
	 *
	 * @var array
	 * @access protected
	 * @since  1.0
	 */
	protected $subscriber_fields;

	/**
	 * NML_API_Subscribers_Route constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		$this->version           = '1';
		$this->namespace         = 'nml/v' . $this->version;
		$this->rest_base         = 'subscribers';
		$this->subscriber_fields = array(
			'ID'           => 'ID',
			'email'        => 'email',
			'first_name'   => 'first_name',
			'last_name'    => 'last_name',
			'status'       => 'status',
			'signup_date'  => 'signup_date',
			'confirm_date' => 'confirm_date',
			'ip'           => 'ip',
			'email_count'  => 'email_count',
			'notes'        => 'notes',
			'lists'        => 'lists',
			'tags'         => 'tags'
		);

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function register_routes() {

		// Get all subscribers.
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_items' ),
			'permission_callback' => array( $this, 'permission_check' )
		) );

		// Get individual subscriber by ID.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_item' ),
			'permission_callback' => array( $this, 'permission_check' )
		) );

		// Create new subscriber.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/new', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_item' ),
			'permission_callback' => array( $this, 'permission_check' )
		) );

		// Update existing subscriber.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/update/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'update_item' ),
			'permission_callback' => array( $this, 'permission_check' )
		) );

		// Delete existing subscriber.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/delete/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'delete_item' ),
			'permission_callback' => array( $this, 'permission_check' )
		) );

	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return WP_Error|boolean
	 */
	public function permission_check( $request ) {

		$params = $request->get_params();

		if ( ! array_key_exists( 'api_key', $params ) || empty( $params['api_key'] ) ) {
			return new WP_Error( 'missing-api-key', __( 'Missing API key', 'naked-mailing-list' ), array( 'status' => 403 ) );
		}

		if ( ! naked_mailing_list()->api_keys->is_valid_key( $params['api_key'] ) ) {
			return new WP_Error( 'invalid-api-key', __( 'Invalid API key', 'naked-mailing-list' ), array( 'status' => 403 ) );
		}

		return true;

	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|WP_Error
	 */
	public function get_items( $request ) {

		$raw_subscribers = nml_get_subscribers( $request->get_params() );

		if ( ! empty( $raw_subscribers ) ) {
			$subscribers = array();

			foreach ( $raw_subscribers as $subscriber ) {
				$object = new NML_API_Subscriber();
				$object->setup_subscriber( $subscriber );
				$object->setup_api_properties();

				$subscribers[ $object->ID ] = $object;
			}
		} else {
			return new WP_Error( 'no-subscribers', __( 'No subscribers found', 'naked-mailing-list' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $subscribers );

	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|WP_Error
	 */
	public function get_item( $request ) {

		$subscriber = new NML_API_Subscriber( absint( $request->get_param( 'id' ) ) );

		if ( empty( $subscriber->ID ) ) {
			$subscriber = new WP_Error( 'no-subscriber', __( 'Invalid subscriber', 'naked-mailing-list' ), array( 'status' => 404 ) );
		}

		$subscriber->setup_api_properties();

		return $subscriber;

	}

	/**
	 * Create a new subscriber
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		$params = $request->get_body_params();
		$args   = array();

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $this->subscriber_fields ) ) {
				$args[ $this->subscriber_fields[ $key ] ] = wp_unslash( $value );
			}
		}

		// Email is required.
		if ( empty( $args['email'] ) ) {
			return new WP_Error( 'missing-email', __( 'No email address specified', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		// Check if subscriber exists.
		if ( naked_mailing_list()->subscribers->exists( $args['email'] ) ) {
			// @todo maybe switch to update instead
			return new WP_Error( 'subscriber-exists', __( 'Subscriber already exists with this email address', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		$subscriber = new NML_API_Subscriber();
		$created    = $subscriber->create( $args );

		if ( empty( $created ) ) {
			return new WP_Error( 'create-failed', __( 'Create failed', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		// Return ID of new subscriber.
		return new WP_REST_Response( $subscriber->ID );

	}

	/**
	 * Update an existing subscriber
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {

		$params = $request->get_body_params();
		$id     = $request->get_param( 'id' );
		$args   = array(
			'lists_append' => true, // Append new lists by default.
			'tags_append'  => true  // Append new tags by default.
		);

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $this->subscriber_fields ) ) {
				$args[ $this->subscriber_fields[ $key ] ] = wp_unslash( $value );
			}
		}

		// ID is required.
		if ( empty( $id ) ) {
			return new WP_Error( 'missing-id', __( 'No subscriber ID specified', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		$subscriber = new NML_API_Subscriber( $id );
		$result     = $subscriber->update( $args );

		if ( empty( $result ) ) {
			return new WP_Error( 'update-failed', __( 'Update failed', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( 1 );

	}

	/**
	 * Delete a subscriber
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @access public
	 * @since  1.0
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {

		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing-id', __( 'No subscriber ID specified', 'naked-mailing-list' ), array( 'status' => 500 ) );
		}

		// Subscriber doesn't exist.
		if ( ! naked_mailing_list()->subscribers->exists( $id, 'ID' ) ) {
			return new WP_REST_Response( 1 );
		}

		$success = nml_delete_subscriber( $id );

		if ( is_wp_error( $success ) ) {
			return $success;
		}

		return new WP_REST_Response( 1 );

	}

}

new NML_API_Subscribers_Route();