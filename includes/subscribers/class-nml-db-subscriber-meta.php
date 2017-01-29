<?php

/**
 * Subscriber Meta DB class
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
 * Class NML_DB_Subscriber_Meta
 *
 * @since 1.0
 */
class NML_DB_Subscriber_Meta extends NML_DB {

	/**
	 * NML_DB_Subscriber_Meta constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_subscribermeta';
		$this->primary_key = 'meta_id';
		$this->version     = '1.0';

		add_action( 'plugins_loaded', array( $this, 'register_table' ), 11 );

	}

	/**
	 * Get table columns and data types
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'meta_id'       => '%d',
			'subscriber_id' => '%d',
			'meta_key'      => '%s',
			'meta_value'    => '%s',
		);
	}

	/**
	 * Register the table with $wpdb so the metadata API can find it
	 *
	 * @access public
	 * @since  1.0
	 */
	public function register_table() {
		global $wpdb;
		$wpdb->subscribermeta = $this->table_name;
	}

	/**
	 * Retrieve meta field for a subscriber.
	 *
	 * For internal use only. Use NML_Subscriber->get_meta() for public usage.
	 *
	 * @param int    $subscriber_id ID of the subscriber.
	 * @param string $meta_key      Name of the meta key.
	 * @param bool   $single        Whether to retrieve a single value.
	 *
	 * @access public
	 * @since  1.0
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $subscriber_id = 0, $meta_key = '', $single = false ) {

		$subscriber_id = $this->sanitize_subscriber_id( $subscriber_id );
		if ( false === $subscriber_id ) {
			return false;
		}

		return get_metadata( 'subscriber', $subscriber_id, $meta_key, $single );

	}

	/**
	 * Add meta field for a subscriber.
	 *
	 * For internal use only. Use NML_Subscriber->add_meta() for public usage.
	 *
	 * @param int    $subscriber_id ID of the subscriber.
	 * @param string $meta_key      Name of the meta key.
	 * @param mixed  $meta_key      Value of the meta field.
	 * @param bool   $unique        Optional. Whether the same key should not be added.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function add_meta( $subscriber_id = 0, $meta_key = '', $meta_value, $unique = false ) {

		$subscriber_id = $this->sanitize_subscriber_id( $subscriber_id );
		if ( false === $subscriber_id ) {
			return false;
		}

		return add_metadata( 'subscriber', $subscriber_id, $meta_key, $meta_value, $unique );

	}

	/**
	 * Update meta field for a subscriber.
	 *
	 * For internal use only. Use NML_Subscriber->update_meta() for public usage.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and subscriber ID.
	 *
	 * @param int    $subscriber_id ID of the subscriber.
	 * @param string $meta_key      Name of the meta key.
	 * @param mixed  $meta_key      Value of the meta field.
	 * @param mixed  $prev_value    Optional. Previous value to check before updating.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function update_meta( $subscriber_id = 0, $meta_key = '', $meta_value, $prev_value = '' ) {

		$subscriber_id = $this->sanitize_subscriber_id( $subscriber_id );
		if ( false === $subscriber_id ) {
			return false;
		}

		return update_metadata( 'subscriber', $subscriber_id, $meta_key, $meta_value, $prev_value );

	}

	/**
	 * Delete meta field for a subscriber.
	 *
	 * For internal use only. Use NML_Subscriber->delete_meta() for public usage.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @param int    $subscriber_id ID of the subscriber.
	 * @param string $meta_key      Name of the meta key.
	 * @param mixed  $meta_key      Optional. Value of the meta field.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool False for failure, true for success.
	 */
	public function delete_meta( $subscriber_id = 0, $meta_key = '', $meta_value = '' ) {

		$subscriber_id = $this->sanitize_subscriber_id( $subscriber_id );
		if ( false === $subscriber_id ) {
			return false;
		}

		return delete_metadata( 'subscriber', $subscriber_id, $meta_key, $meta_value );

	}

	/**
	 * Delete all meta data associated with a subscriber.
	 *
	 * @param int $subscriber_id ID of the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|false|int
	 */
	public function delete_all_subscriber_meta( $subscriber_id ) {

		$subscriber_id = $this->sanitize_subscriber_id( $subscriber_id );
		if ( false === $subscriber_id ) {
			return false;
		}

		global $wpdb;

		return $wpdb->delete( $this->table_name, array( 'subscriber_id' => $subscriber_id ), array( '%d' ) );

	}

	/**
	 * Create the table
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			subscriber_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY subscriber_id (subscriber_id),
			KEY meta_key (meta_key)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

	/**
	 * Sanitize subscriber ID
	 *
	 * Given a subscriber ID, make sure it's a positive number before inserting or adding.
	 *
	 * @param int $subscriber_id
	 *
	 * @access private
	 * @since  1.0
	 * @return int|bool The normalized subscriber ID or false if it's not found to be valid.
	 */
	private function sanitize_subscriber_id( $subscriber_id ) {

		if ( ! is_numeric( $subscriber_id ) ) {
			return false;
		}

		$subscriber_id = (int) $subscriber_id;

		// We were given a non-positive number.
		if ( absint( $subscriber_id ) !== $subscriber_id ) {
			return false;
		}

		if ( empty( $subscriber_id ) ) {
			return false;
		}

		return absint( $subscriber_id );

	}

}