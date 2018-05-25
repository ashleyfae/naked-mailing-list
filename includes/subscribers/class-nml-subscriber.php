<?php

/**
 * Subscriber Object
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
 * Class NML_Subscriber
 *
 * @since 1.0
 */
class NML_Subscriber {

	/**
	 * The subscriber ID
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $ID = 0;

	/**
	 * The subscriber's email address
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $email;

	/**
	 * The subscriber's first name
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $first_name;

	/**
	 * The subscriber's last name
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $last_name;

	/**
	 * The subscriber's status
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $status;

	/**
	 * The subscriber's signup date
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $signup_date;

	/**
	 * The subscriber's confirmation date
	 *
	 * @var string|null
	 * @access public
	 * @since  1.0
	 */
	public $confirm_date = null;

	/**
	 * The subscriber's IP address used during signup
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $ip;

	/**
	 * The subscriber's referring path or method
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $referer;

	/**
	 * Form the subscriber signed up with
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $form_name;

	/**
	 * Number of emails the subscriber has received
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $email_count = 0;

	/**
	 * Subscriber notes
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $notes = '';

	/**
	 * Array of lists the subscriber is added to.
	 *
	 * @var array
	 * @access protected
	 * @since  1.0
	 */
	protected $lists;

	/**
	 * Array of tags the subscriber has.
	 *
	 * @var array
	 * @access protected
	 * @since  1.0
	 */
	protected $tags;

	/**
	 * Row from the database
	 *
	 * @var object
	 * @access protected
	 * @since  1.0
	 */
	protected $db_row;

	/**
	 * The database abstraction
	 *
	 * @var NML_DB_Subscribers
	 * @access protected
	 * @since  1.0
	 */
	protected $db;

	/**
	 * NML_Subscriber constructor.
	 *
	 * @param int|string|object $_id_email_or_object Subscriber ID or email address.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $_id_email_or_object = 0 ) {

		$this->db = new NML_DB_Subscribers();

		if ( empty( $_id_email_or_object ) ) {
			return;
		}

		if ( is_object( $_id_email_or_object ) ) {

			$subscriber = $_id_email_or_object;

		} else {

			if ( is_numeric( $_id_email_or_object ) ) {
				$field = 'ID';
			} else {
				$field = 'email';
			}

			$subscriber = $this->db->get_subscriber_by( $field, $_id_email_or_object );

		}

		if ( empty( $subscriber ) || ! is_object( $subscriber ) ) {
			return;
		}

		$this->setup_subscriber( $subscriber );

	}

	/**
	 * Given the subscriber data, set up all the environment variables.
	 *
	 * @param object $subscriber Subscriber object from the database.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function setup_subscriber( $subscriber ) {

		if ( ! is_object( $subscriber ) ) {
			return false;
		}

		$this->db_row = $subscriber;

		foreach ( $subscriber as $key => $value ) {
			$this->$key = $value;
		}

		if ( ! empty( $this->ID ) && ! empty( $this->email ) ) {
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
			return new WP_Error( 'nml-subscriber-invalid-property', sprintf( __( 'Can\'t get property %s', 'naked-mailing-list' ), $key ) );
		}

	}

	/**
	 * Creates a subscriber
	 *
	 * @param array $data Array of attributes for the subscriber
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false Subscriber ID if successfully added/updated, or false on failure.
	 */
	public function create( $data = array() ) {

		if ( $this->ID != 0 || empty( $data ) ) {
			return false;
		}

		$defaults = array();

		$args = wp_parse_args( $data, $defaults );

		$lists = array_key_exists( 'lists', $data ) ? $data['lists'] : false;
		$tags  = array_key_exists( 'tags', $data ) ? $data['tags'] : false;
		$args  = $this->sanitize_columns( $args );

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			return false;
		}

		/**
		 * Fires before a subscriber is created
		 *
		 * @param array $args Contains subscriber information such as name and email.
		 */
		do_action( 'nml_subscriber_pre_create', $args );

		$created = false;

		// The DB class 'add' implies an update if the subscriber being asked to be created already exists.
		if ( $this->db->add( $data ) ) {

			// We've successfully added/updated the subscriber, reset the class vars with the new data
			$subscriber = $this->db->get_subscriber_by( 'email', $args['email'] );

			// Setup the subscriber data with the values from the DB.
			$this->setup_subscriber( $subscriber );

			/**
			 * Transition subscriber status.
			 *
			 * @param string         $new_status    New status.
			 * @param string         $old_status    Old status.
			 * @param int            $subscriber_id ID of the subscriber.
			 * @param NML_Subscriber $subscriber    Subscriber object.
			 *
			 * @since 1.0
			 */
			do_action( 'nml_subscriber_transition_status', $this->status, '', $this->ID, $this );
			do_action( 'nml_subscriber_set_' . $this->status, '', $this->ID, $this );

			/**
			 * Set lists.
			 */
			if ( ! empty( $lists ) ) {
				$this->set_lists( $lists );
			}
			if ( ! empty( $tags ) ) {
				$this->set_tags( $tags );
			}

			$created = $this->ID;

		}

		/**
		 * Fires after a subscriber is created
		 *
		 * @param int   $created If created successfully, the subscriber ID.  Defaults to false.
		 * @param array $args    Contains subscriber information such as name and email.
		 */
		do_action( 'nml_subscriber_post_create', $created, $args );

		return $created;

	}

	/**
	 * Update a subscriber record
	 *
	 * @param array $data Array of data attributes for a subscriber (checked via whitelist).
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function update( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		$lists = array_key_exists( 'lists', $data ) ? $data['lists'] : false;
		$tags  = array_key_exists( 'tags', $data ) ? $data['tags'] : false;
		$data  = $this->sanitize_columns( $data );

		do_action( 'nml_subscriber_pre_update', $this->ID, $data );

		$updated    = false;
		$old_status = $this->status;

		if ( $this->db->update( $this->ID, $data ) ) {

			$subscriber = $this->db->get_subscriber_by( 'ID', $this->ID );
			$this->setup_subscriber( $subscriber );

			$updated = true;

			/**
			 * Transition subscriber status.
			 *
			 * @param string         $new_status    New status.
			 * @param string         $old_status    Old status.
			 * @param int            $subscriber_id ID of the subscriber.
			 * @param NML_Subscriber $subscriber    Subscriber object.
			 *
			 * @since 1.0
			 */
			if ( array_key_exists( 'status', $data ) ) {
				do_action( 'nml_subscriber_transition_status', $this->status, $old_status, $this->ID, $this );
				do_action( 'nml_subscriber_set_' . $this->status, $old_status, $this->ID, $this );
			}

			/**
			 * Set lists.
			 */
			if ( ! empty( $lists ) ) {
				$append = array_key_exists( 'lists_append', $data ) ? $data['lists_append'] : false;
				$this->set_lists( $lists, $append );
			}
			if ( ! empty( $tags ) ) {
				$append = array_key_exists( 'tags_append', $data ) ? $data['tags_append'] : false;
				$this->set_tags( $tags, $append );
			}

		}

		do_action( 'nml_subscriber_post_update', $updated, $this->ID, $data );

		return $updated;

	}

	/**
	 * Get referer label
	 *
	 * Returns the location where the subscriber opted in. This might be a name like
	 * "manual insertion" or "import", or the HTML link to an actual page on the site.
	 *
	 * @access public
	 * @since  1.0
	 * @return string Textual description or formatted link to referring path.
	 */
	public function get_referer() {

		switch ( $this->referer ) {

			case 'manual' :
				$referer = __( 'manual insertion', 'naked-mailing-list' );
				break;

			case 'import' :
				$referer = __( 'import', 'naked-mailing-list' );
				break;

			case null :
				$referer = __( 'unknown', 'naked-mailing-list' );
				break;

			case '/' :
				$referer = __( 'homepage', 'naked-mailing-list' );
				break;

			default :
				if ( false !== filter_var( $this->referer, FILTER_VALIDATE_URL ) ) {
					// A full URL was provided (usually external signups).
					$url = $this->referer;
				} else {
					// A path was provided so we build our own URL. This signup took place on-site.
					$url = home_url( $this->referer );
				}
				$referer = '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $this->referer ) . '</a>';
				break;

		}

		return apply_filters( 'nml_subscriber_referer', $referer, $this->ID, $this );

	}

	/**
	 * Send confirmation email to the subscriber
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function send_confirmation_email() {

		$email          = nml_get_email_provider();
		$email->subject = apply_filters( 'nml_confirmation_email_subject', sprintf( __( 'Confirm your subscription to %s', 'naked-mailing-list' ), get_bloginfo( 'name' ) ) );
		$email->message = apply_filters( 'nml_confirmation_email_message', sprintf(
			__( 'Click here to confirm your subscription to %s: %s', 'naked-mailing-list' ),
			esc_html( get_bloginfo( 'name' ) ),
			esc_url( add_query_arg( array(
				'nml_action' => 'confirm',
				'subscriber' => urlencode( $this->ID ),
				'key'        => md5( $this->ID . $this->email )
			), home_url() ) )
		) );
		$email->set_recipients( $this->db_row );
		$result = $email->send();

		if ( ! $result ) {
			nml_log( sprintf( 'Error sending confirmation email to subscriber %d.', $this->ID ) );
		} else {
			nml_log( sprintf( 'Confirmation email sent to subscriber #%d', $this->ID ) );
		}

		do_action( 'nml_subscriber_send_confirmation_email', $this->ID, $this );

		return $result;

	}

	/**
	 * Confirm subscriber
	 *
	 * Sets confirmation date and updates status to 'subscribed'.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function confirm() {

		$data = array(
			'status'       => 'subscribed',
			'confirm_date' => gmdate( 'Y-m-d H:i:s' )
		);

		$updated = $this->update( $data );

		if ( $updated ) {
			do_action( 'nml_subscriber_confirm', $this->ID, $this );

			nml_log( sprintf( 'Subscriber #%d confirmed subscription via IP %s.', $this->ID, nml_get_ip() ) );

			return true;
		}

		return false;

	}

	/**
	 * Set signup date
	 *
	 * @param string $date Desired signup date, in any normal date format.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function set_signup_date( $date ) {

		$data = array(
			'signup_date' => gmdate( 'Y-m-d H:i:s', strtotime( $date ) )
		);

		$updated = $this->update( $data );

		return $updated ? true : false;

	}

	/**
	 * Unsubscribe
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function unsubscribe() {

		$data = array(
			'status' => 'unsubscribed'
		);

		$updated = $this->update( $data );

		if ( $updated ) {
			do_action( 'nml_subscriber_unsubscribe', $this->ID, $this );

			return true;
		}

		return false;

	}

	/**
	 * Get an array of lists the subscriber is added to.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_lists() {

		if ( ! isset( $this->lists ) ) {
			$this->lists = nml_get_object_lists( 'subscriber', $this->ID, 'list' );

			if ( ! is_array( $this->lists ) ) {
				$this->lists = array();
			}
		}

		return $this->lists;

	}

	/**
	 * Checks whether or not the subscriber is on a list.
	 *
	 * @param int|string $list_id_or_name ID or name of the list to check.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function is_on_list( $list_id_or_name ) {

		$lists   = $this->get_lists();
		$on_list = false;

		if ( is_array( $lists ) ) {
			$field  = is_numeric( $list_id_or_name ) ? 'ID' : 'name';
			$values = wp_list_pluck( $lists, $field );

			if ( in_array( $list_id_or_name, $values ) ) {
				$on_list = true;
			}
		}

		return $on_list;

	}

	/**
	 * Add the subscriber to a list.
	 *
	 * @param int $list_id ID of the list to add the subscriber to.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function add_to_list( $list_id ) {
		if ( ! $this->is_on_list( $list_id ) ) {
			nml_set_object_lists( 'subscriber', $this->ID, $list_id, 'list', true );
		}
	}

	/**
	 * Set lists
	 *
	 * @param int|array $lists  List(s) to add the subscriber to.
	 * @param bool      $append Whether or not to append the lists to existing ones.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_lists( $lists, $append = false ) {
		nml_set_object_lists( 'subscriber', $this->ID, $lists, 'list', $append );
	}

	/**
	 * Get an array of tags the subscriber has.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_tags() {

		if ( ! isset( $this->tags ) ) {
			$this->tags = nml_get_object_lists( 'subscriber', $this->ID, 'tag' );

			if ( ! is_array( $this->tags ) ) {
				$this->tags = array();
			}
		}

		return $this->tags;

	}

	/**
	 * Checks whether or not the subscriber has a given tag.
	 *
	 * @param int|string $tag_id_or_name ID or name of the tag to check.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function has_tag( $tag_id_or_name ) {

		$tags    = $this->get_tags();
		$has_tag = false;

		if ( is_array( $tags ) ) {
			$field  = is_numeric( $tag_id_or_name ) ? 'ID' : 'name';
			$values = wp_list_pluck( $tags, $field );

			if ( in_array( $tag_id_or_name, $values ) ) {
				$has_tag = true;
			}
		}

		return $has_tag;

	}

	/**
	 * Tag the subscriber
	 *
	 * @param int|string $tag_id_or_name ID or name of the tag to add to the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function tag( $tag_id_or_name ) {

		if ( ! $this->has_tag( $tag_id_or_name ) ) {
			nml_set_object_lists( 'subscriber', $this->ID, $tag_id_or_name, 'tag', true );
		}

	}

	/**
	 * Set tags
	 *
	 * @param int|array $tags   Tag(s) to add the subscriber to.
	 * @param bool      $append Whether or not to append the tags to existing ones.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_tags( $tags, $append = false ) {
		nml_set_object_lists( 'subscriber', $this->ID, $tags, 'tag', $append );
	}

	/**
	 * Deletes lists or tags from the subscriber
	 *
	 * @param string|bool $type Type of lists to delete (`list` or `tag`), or false for all.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function delete_lists( $type = false ) {

		$lists = nml_get_object_lists( 'subscriber', $this->ID, $type, array( 'fields' => 'ids' ) );

		if ( empty( $lists ) ) {
			return;
		}

		$args = array(
			'subscriber_id' => $this->ID,
			'list_id'       => $lists
		);

		// Get all the relationships with these lists and subscriber.
		$relationships = naked_mailing_list()->list_relationships->get_relationships( $args );

		if ( empty( $relationships ) ) {
			return;
		}

		$ids = wp_list_pluck( $relationships, 'ID' );

		naked_mailing_list()->list_relationships->delete_by_ids( $ids );

	}

	/**
	 * Increase the subscriber's email count.
	 *
	 * @param int $count The number to increment by.
	 *
	 * @access public
	 * @since  1.0
	 * @return int The new email count.
	 */
	public function increase_email_count( $count = 1 ) {

		// Make sure it's numeric and not negative
		if ( ! is_numeric( $count ) || $count != absint( $count ) ) {
			return false;
		}

		$new_total = (int) $this->email_count + (int) $count;

		do_action( 'nml_subscriber_pre_increase_email_count', $count, $this->ID );

		if ( $this->update( array( 'email_count' => $new_total ) ) ) {
			$this->email_count = $new_total;
		}

		do_action( 'nml_subscriber_post_increase_email_count', $this->email_count, $count, $this->ID );

		return $this->email_count;

	}

	/**
	 * Checks if the subscriber is active ("subscribed" status)
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function is_subscribed() {
		return 'subscribed' == $this->status;
	}

	/**
	 * Retrieve meta field for a subscriber.
	 *
	 * @param string $meta_key The meta key to retrieve.
	 * @param bool   $single   Whether to return a single value.
	 *
	 * @access public
	 * @since  1.0
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return naked_mailing_list()->subscriber_meta->get_meta( $this->ID, $meta_key, $single );
	}

	/**
	 * Add meta data for a subscriber.
	 *
	 * @param string $meta_key   The name of the meta field.
	 * @param mixed  $meta_value The value of the meta field.
	 * @param bool   $unique     Whether the same key should not be added.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return naked_mailing_list()->subscriber_meta->add_meta( $this->ID, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update meta data for a subscriber.
	 *
	 * @param string $meta_key   The name of the meta field.
	 * @param mixed  $meta_value The value of the meta field.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return naked_mailing_list()->subscriber_meta->update_meta( $this->ID, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete meta data field for a subscriber.
	 *
	 * @param string $meta_key   The name of the meta field.
	 * @param mixed  $meta_value The value of the meta field.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value ) {
		return naked_mailing_list()->subscriber_meta->delete_meta( $this->ID, $meta_key, $meta_value );
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
					if ( 'email' == $key ) {
						$data[ $key ] = sanitize_email( $data[ $key ] );
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
