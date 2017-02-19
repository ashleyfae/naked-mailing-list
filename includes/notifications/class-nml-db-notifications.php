<?php

/**
 * Post Notifications DB Class
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
 * Class NML_DB_Notifications
 *
 * @since 1.0
 */
class NML_DB_Notifications extends NML_DB {

	/**
	 * NML_DB_Notifications constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_notifications';
		$this->primary_key = 'ID';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'ID'               => '%d',
			'name'             => '%s',
			'active'           => '%d',
			'subject'          => '%s',
			'body'             => '%s',
			'from_address'     => '%s',
			'from_name'        => '%s',
			'reply_to_address' => '%s',
			'reply_to_name'    => '%s',
			'number_campaigns' => '%d',
			'post_type'        => '%s',
			'lists'            => '%s'
		);
	}

	/**
	 * Get default column values
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'name'             => '',
			'active'           => 0, // (draft)
			'subject'          => '',
			'body'             => '',
			'from_address'     => '',
			'from_name'        => '',
			'reply_to_address' => '',
			'reply_to_name'    => '',
			'number_campaigns' => 0,
			'post_type'        => 'post',
			'lists'            => ''
		);
	}

	/**
	 * Add a notification
	 *
	 * @param array $data Array of notification data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated notification, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		$notification = array_key_exists( 'ID', $args ) ? $this->get_notification_by( 'ID', $args['ID'] ) : false;

		if ( $notification ) {

			// Update existing notification.
			$result = $this->update( $notification->ID, $args );

			return $result ? $notification->ID : false;

		} else {

			// Create a new notification.
			return $this->insert( $args, 'notification' );

		}

	}

	/**
	 * Delete a notification
	 *
	 * @param int $id ID of the notification to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$notification = $this->get_notification_by( 'ID', $id );

		if ( $notification->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $notification->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Delete multiple notifications by IDs
	 *
	 * @param array $ids Array of review IDs.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false Number of rows deleted or false if none.
	 */
	public function delete_by_ids( $ids ) {

		global $wpdb;

		if ( is_array( $ids ) ) {
			$ids = implode( ',', array_map( 'intval', $ids ) );
		} else {
			$ids = intval( $ids );
		}

		$results = $wpdb->query( "DELETE FROM  $this->table_name WHERE `ID` IN( {$ids} )" );

		return $results;

	}

	/**
	 * Checks if a notification exists
	 *
	 * @param string|int $value Field value.
	 * @param string     $field Column to check the value of.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function exists( $value = '', $field = 'ID' ) {

		$columns = $this->get_columns();
		if ( ! array_key_exists( $field, $columns ) ) {
			return false;
		}

		return (bool) $this->get_column_by( 'ID', $field, $value );

	}

	/**
	 * Retrieve a single notification from the database
	 *
	 * @param string $field The field to get the notification by (ID or subject).
	 * @param int    $value The value of the field to search.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_notification_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( 'ID' === $field ) {

			if ( ! is_numeric( $value ) ) {
				return false;
			}

			$value = intval( $value );

			if ( $value < 1 ) {
				return false;
			}

		}

		if ( ! $value ) {
			return false;
		}

		switch ( $field ) {
			case 'ID' :
				$db_field = 'ID';
				break;

			case 'name' :
				$value    = sanitize_text_field( $value );
				$db_field = 'name';
				break;

			case 'subject' :
				$value    = sanitize_text_field( $value );
				$db_field = 'subject';
				break;
			default :
				return false;
		}

		if ( ! $notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {
			return false;
		}

		return $notification;

	}

	/**
	 * Retrieve notifications from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_notifications( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'orderby'          => 'ID',
			'order'            => 'DESC',
			'ID'               => null,
			'name'             => null,
			'subject'          => null,
			'number_campaigns' => null, // @todo allow for greater than, etc.
			'post_type'        => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific notification(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// By activation status
		if ( array_key_exists( 'active', $args ) ) {
			$where .= $wpdb->prepare( " AND `active` = %d ", $args['active'] );
		}

		// Specific notifications by name
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", $args['name'] );
		}

		// Specific notifications by subject
		if ( ! empty( $args['subject'] ) ) {
			$where .= $wpdb->prepare( " AND `subject` LIKE '%%%%" . '%s' . "%%%%' ", $args['subject'] );
		}

		// Specific notifications by number of campaigns
		if ( ! empty( $args['number_campaigns'] ) ) {
			$where .= $wpdb->prepare( " AND `number_campaigns` = %d ", $args['number_campaigns'] );
		}

		// Specific notifications by post type
		if ( ! empty( $args['post_type'] ) ) {
			$where .= $wpdb->prepare( " AND `post_type` = %s ", $args['post_type'] );
		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		$cache_key = md5( 'nml_notifications_' . serialize( $args ) );

		$notifications = wp_cache_get( $cache_key, 'notifications' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $notifications ) {
			$query         = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$notifications = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $notifications, 'notifications', 3600 );
		}

		return $notifications;

	}

	/**
	 * Count the total number of notifications in the database
	 *
	 * @param array $args Arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'           => null,
			'name'             => null,
			'subject'          => null,
			'number_campaigns' => null, // @todo allow for greater than, etc.
			'post_type'        => null
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific notification(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// By activation status
		if ( array_key_exists( 'active', $args ) ) {
			$where .= $wpdb->prepare( " AND `active` = %d ", $args['active'] );
		}

		// Specific notifications by name
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", $args['name'] );
		}

		// Specific notifications by subject
		if ( ! empty( $args['subject'] ) ) {
			$where .= $wpdb->prepare( " AND `subject` LIKE '%%%%" . '%s' . "%%%%' ", $args['subject'] );
		}

		// Specific notifications by number of campaigns
		if ( ! empty( $args['number_campaigns'] ) ) {
			$where .= $wpdb->prepare( " AND `number_campaigns` = %d ", $args['number_campaigns'] );
		}

		// Specific notifications by post type
		if ( ! empty( $args['post_type'] ) ) {
			$where .= $wpdb->prepare( " AND `post_type` = %s ", $args['post_type'] );
		}

		$cache_key = md5( 'nml_notifications_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'notifications' );

		if ( false === $count ) {
			$query = "SELECT COUNT({$this->primary_key}) FROM  $this->table_name $join $where";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'notifications', 3600 );
		}

		return absint( $count );

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

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		name varchar(250) NOT NULL,
		active int(1) NOT NULL,
		subject varchar(250) NOT NULL,
		body longtext NOT NULL,
		from_address varchar(150) NOT NULL,
		from_name varchar(150) NOT NULL,
		reply_to_address varchar(150) NOT NULL,
		reply_to_name varchar(150) NOT NULL,
		number_campaigns bigint(20) NOT NULL,
		post_type varchar(250) NOT NULL,
		lists varchar(250) NOT NULL,
		PRIMARY KEY (ID),
		KEY active (active),
		KEY post_type (post_type)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}