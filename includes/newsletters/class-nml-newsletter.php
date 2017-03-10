<?php

/**
 * Newsletter Object
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
 * Class NML_Newsletter
 *
 * @since 1.0
 */
class NML_Newsletter {

	/**
	 * The newsletter ID
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $ID = 0;

	/**
	 * Newsletter type
	 *
	 * @see    nml_get_newsletter_types()
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $type;

	/**
	 * Newsletter status
	 *
	 * @see    nml_get_newsletter_statuses()
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $status;

	/**
	 * Newsletter subject
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $subject;

	/**
	 * Body of the newsletter
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $body;

	/**
	 * Email from address for this newsletter
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $from_address;

	/**
	 * Email from name for this newsletter
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $from_name;

	/**
	 * Email reply-to address for this newsletter
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $reply_to_address;

	/**
	 * Email reply-to name for this newsletter
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $reply_to_name;

	/**
	 * Date the newsletter was created, in MySQL format
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $created_date;

	/**
	 * Date the newsletter was last updated, in MySQL format
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $updated_date;

	/**
	 * Date the newsletter was sent, in MySQL format
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $sent_date;

	/**
	 * The database abstraction
	 *
	 * @var NML_DB_Newsletters
	 * @access protected
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Newsletter/list relationship database abstraction
	 *
	 * @var NML_DB_Newsletter_List_Relationships
	 * @access protected
	 * @since  1.0
	 */
	protected $list_relationships;

	/**
	 * NML_Newsletter constructor.
	 *
	 * @param int $newsletter_id ID of the newsletter.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $newsletter_id = 0 ) {

		$this->db                 = new NML_DB_Newsletters();
		$this->list_relationships = new NML_DB_Newsletter_List_Relationships();

		if ( empty( $newsletter_id ) ) {
			return;
		}

		$newsletter = $this->db->get_newsletter_by( 'ID', $newsletter_id );

		if ( empty( $newsletter ) || ! is_object( $newsletter ) ) {
			return;
		}

		$this->setup_newsletter( $newsletter );

	}

	/**
	 * Given the newsletter data, set up all the environment variables.
	 *
	 * @param object $newsletter Newsletter object from the database.
	 *
	 * @access private
	 * @since  1.0
	 * @return bool
	 */
	private function setup_newsletter( $newsletter ) {

		if ( ! is_object( $newsletter ) ) {
			return false;
		}

		foreach ( $newsletter as $key => $value ) {
			$this->$key = $value;
		}

		if ( ! empty( $this->ID ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @param $key
	 *
	 * @access public
	 * @since  1.0
	 * @return mixed
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			return new WP_Error( 'nml-newsletter-invalid-property', sprintf( __( 'Can\'t get property %s', 'naked-mailing-list' ), $key ) );
		}

	}

	/**
	 * Creates a newsletter
	 *
	 * @param array $data Array of attributes for the newsletter
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false Newsletter ID if successfully added/updated, or false on failure.
	 */
	public function create( $data = array() ) {

		if ( $this->ID != 0 || empty( $data ) ) {
			return false;
		}

		$defaults = array();

		$args = wp_parse_args( $data, $defaults );
		$args = $this->sanitize_columns( $args );

		/**
		 * Fires before a newsletter is created
		 *
		 * @param array $args Contains newsletter information such as subject and body.
		 */
		do_action( 'nml_newsletter_pre_create', $args );

		$created = false;

		$new_id = $this->db->add( $data );

		// The DB class 'add' implies an update if the newsletter being asked to be created already exists.
		if ( $new_id ) {

			// We've successfully added/updated the newsletter, reset the class vars with the new data
			$newsletter = $this->db->get_newsletter_by( 'ID', $new_id );

			// Setup the newsletter data with the values from the DB.
			$this->setup_newsletter( $newsletter );

			/**
			 * Transition newsletter status.
			 *
			 * @param string         $new_status    New status.
			 * @param string         $old_status    Old status.
			 * @param int            $newsletter_id ID of the newsletter.
			 * @param NML_Newsletter $newsletter    Newsletter object.
			 *
			 * @since 1.0
			 */
			do_action( 'nml_newsletter_transition_status', $this->status, '', $this->ID, $this );

			$created = $this->ID;

		}

		/**
		 * Fires after a newsletter is created
		 *
		 * @param int   $created If created successfully, the newsletter ID.  Defaults to false.
		 * @param array $args    Contains newsletter information such as subject and body.
		 */
		do_action( 'nml_newsletter_post_create', $created, $args );

		return $created;

	}

	/**
	 * Update a newsletter record
	 *
	 * @param array $data Array of data attributes for a newsletter (checked via whitelist).
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function update( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		do_action( 'nml_newsletter_pre_update', $this->ID, $data );

		$updated    = false;
		$old_status = $this->status;

		if ( $this->db->update( $this->ID, $data ) ) {

			$newsletter = $this->db->get_newsletter_by( 'ID', $this->ID );
			$this->setup_newsletter( $newsletter );

			$updated = true;

			/**
			 * Transition newsletter status.
			 *
			 * @param string         $new_status    New status.
			 * @param string         $old_status    Old status.
			 * @param int            $newsletter_id ID of the newsletter.
			 * @param NML_Newsletter $newsletter    Newsletter object.
			 *
			 * @since 1.0
			 */
			if ( array_key_exists( 'status', $data ) && $data['status'] != $old_status ) {
				do_action( 'nml_newsletter_transition_status', $this->status, $old_status, $this->ID, $this );
			}

		}

		do_action( 'nml_newsletter_post_update', $updated, $this->ID, $data );

		return $updated;

	}

	/**
	 * Get subject
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'nml_newsletter_get_subject', $this->subject, $this->ID, $this );
	}

	/**
	 * Get message body
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_body() {
		return apply_filters( 'nml_newsletter_get_body', $this->body, $this->ID, $this );
	}

	/**
	 * Get an array of lists associated with this newsletter.
	 *
	 * @param string $type Which to retrive: all, tags, lists.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_lists( $type = 'all' ) {

		switch ( $type ) {
			case 'lists' :
				$lists = nml_get_object_lists( 'newsletter', $this->ID, 'lists' );
				break;

			case 'tags' :
				$lists = nml_get_object_lists( 'newsletter', $this->ID, 'tags' );
				break;

			default :
				$lists = nml_get_object_lists( 'newsletter', $this->ID );
				break;
		}

		return apply_filters( 'nml_newsletter_get_tags', $lists, $this->ID, $this );

	}

	/**
	 * Get the subscribers to receive this newsletter
	 *
	 * @param array $args Query arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_subscribers( $args = array() ) {

		$newsletter_lists = $this->get_lists();
		$list_ids         = wp_list_pluck( $newsletter_lists, 'ID' );

		$defaults = array(
			'list'   => $list_ids,
			'number' => - 1 // Eeks
		);

		$args = wp_parse_args( $args, $defaults );

		$subscribers = nml_get_subscribers( $args );

		return $subscribers;

	}

	/**
	 * Sanitize the data for update/create
	 *
	 * @param array $data The data to sanitize.
	 *
	 * @access private
	 * @since  1.0
	 * @return array The sanitized data, based off column defaults.
	 */
	private function sanitize_columns( $data ) {

		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {

			// Only sanitize data that we were provided.
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch ( $type ) {

				case '%s' :
					if ( 'from_address' == $key || 'reply_to_address' == $key ) {
						$data[ $key ] = sanitize_email( $data[ $key ] );
					} elseif ( 'body' == $key ) {
						$data[ $key ] = wp_kses_post( $data[ $key ] );
					} else {
						$data[ $key ] = sanitize_text_field( $data[ $key ] );
					}
					break;

				case '%d' :
					if ( ! is_numeric( $data[ $key ] ) || (int) $data[ $key ] !== absint( $data[ $key ] ) ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;

				case '%f' :
					// Convert what was given to a float.
					$value = floatval( $data[ $key ] );

					if ( ! is_float( $value ) ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = $value;
					}
					break;

				default :
					$data[ $key ] = sanitize_text_field( $data[ $key ] );
					break;


			}

		}

		return $data;

	}

}