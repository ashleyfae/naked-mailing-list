<?php

/**
 * Subscribers DB Class
 *
 * This class is for interacting with the subscribers' database table.
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
 * Class NML_DB_Subscribers
 *
 * @since 1.0
 */
class NML_DB_Subscribers extends NML_DB {

	/**
	 * NML_DB_Subscribers constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_subscribers';
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
			'ID'           => '%d',
			'email'        => '%s',
			'first_name'   => '%s',
			'last_name'    => '%s',
			'status'       => '%s',
			'signup_date'  => '%s',
			'confirm_date' => '%s',
			'ip'           => '%s',
			'referer'     => '%s',
			'form_name'    => '%s',
			'email_count'  => '%d',
			'notes'        => '%s'
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
			'email'        => '',
			'first_name'   => '',
			'last_name'    => '',
			'status'       => 'pending',
			'signup_date'  => gmdate( 'Y-m-d H:i:s' ),
			'confirm_date' => null,
			'ip'           => nml_get_ip(),
			'referer'     => 'manual',
			'form_name'    => '',
			'email_count'  => 0,
			'notes'        => ''
		);
	}

	/**
	 * Add a subscriber
	 *
	 * @param array $data Array of subscriber data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated subscriber, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			return false;
		}

		$subscriber = $this->get_subscriber_by( 'email', $args['email'] );

		if ( $subscriber ) {

			// Update existing subscriber.
			$result = $this->update( $subscriber->ID, $args );

			return $result ? $subscriber->ID : false;

		} else {

			// Create a new subscriber.
			return $this->insert( $args, 'subscriber' );

		}

	}

	/**
	 * Delete a subscriber
	 *
	 * NOTE: This should not be called directly as it does not make necessary changes to
	 * the subscriber meta, activity, or lists. Use nml_subscriber_delete() instead.
	 *
	 * @see    nml_subscriber_delete()
	 *
	 * @param int|string $id_or_email Subscriber's ID number or email address.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $id_or_email = false ) {

		if ( empty( $id_or_email ) ) {
			return false;
		}

		$column     = is_email( $id_or_email ) ? 'email' : 'ID';
		$subscriber = $this->get_subscriber_by( $column, $id_or_email );

		if ( $subscriber->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $subscriber->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Delete multiple subscribers by IDs
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
	 * Checks if a subscriber exists
	 *
	 * @param string $value Field value.
	 * @param string $field Column to check the value of.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function exists( $value = '', $field = 'email' ) {

		$columns = $this->get_columns();
		if ( ! array_key_exists( $field, $columns ) ) {
			return false;
		}

		return (bool) $this->get_column_by( 'ID', $field, $value );

	}

	/**
	 * Retrieve a single subscriber from the database
	 *
	 * @param string $field The field to get the subscriber by (ID or email).
	 * @param int    $value The value of the field to search.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_subscriber_by( $field = 'ID', $value = 0 ) {

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

		} elseif ( 'email' === $field ) {

			if ( ! is_email( $value ) ) {
				return false;
			}

			$value = trim( $value );

		}

		if ( ! $value ) {
			return false;
		}

		switch ( $field ) {
			case 'ID' :
				$db_field = 'ID';
				break;

			case 'email' :
				$value    = sanitize_text_field( $value );
				$db_field = 'email';
				break;
			default :
				return false;
		}

		if ( ! $subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {
			return false;
		}

		return $subscriber;

	}

	/**
	 * Retrieve subscribers from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_subscribers( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'     => 20,
			'offset'     => 0,
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'fields'     => 'all',
			'ID'         => null,
			'email'      => null,
			'first_name' => null,
			'last_name'  => null,
			'status'     => null,
			'referer'   => null,
			'form_name'  => null,
			'ip'         => null,
			'list'       => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific subscriber(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND s.ID IN( {$ids} ) ";

		}

		// Specific subscriber by email.
		if ( ! empty( $args['email'] ) ) {

			if ( is_array( $args['email'] ) ) {

				$emails_count       = count( $args['email'] );
				$emails_placeholder = array_fill( 0, $emails_count, '%s' );
				$emails             = implode( ', ', $emails_placeholder );

				$where .= $wpdb->prepare( " AND `email` IN( $emails ) ", $args['email'] );

			} else {

				$where .= $wpdb->prepare( " AND `email` = %s ", $args['email'] );

			}

		}

		// Specific subscriber by first name.
		if ( ! empty( $args['first_name'] ) ) {
			$where .= $wpdb->prepare( " AND `first_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['first_name'] );
		}

		// Specific subscriber by last name.
		if ( ! empty( $args['last_name'] ) ) {
			$where .= $wpdb->prepare( " AND `last_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['last_name'] );
		}

		// By status
		if ( ! empty( $args['status'] ) ) {

			if ( is_array( $args['status'] ) ) {

				$status_count       = count( $args['status'] );
				$status_placeholder = array_fill( 0, $status_count, '%s' );
				$statuses           = implode( ', ', $status_placeholder );

				$where .= $wpdb->prepare( " AND `status` IN( $statuses ) ", $args['status'] );

			} else {

				$where .= $wpdb->prepare( " AND `status` = %s ", $args['status'] );

			}

		}

		// By referer
		if ( ! empty( $args['referer'] ) ) {
			$where .= $wpdb->prepare( " AND `referer` = %s ", $args['referer'] );
		}

		// By form name
		if ( ! empty( $args['form_name'] ) ) {
			$where .= $wpdb->prepare( " AND `form_name` = %s ", $args['form_name'] );
		}

		// @todo by date

		// By IP
		if ( ! empty( $args['ip'] ) ) {
			$where .= $wpdb->prepare( " AND `ip` = %s ", $args['ip'] );
		}

		// By list(s)
		if ( ! empty( $args['list'] ) ) {

			if ( is_array( $args['list'] ) ) {
				$list_ids = implode( ',', array_map( 'intval', $args['list'] ) );
			} else {
				$list_ids = intval( $args['list'] );
			}

			$relationship_table = naked_mailing_list()->list_relationships->table_name;

			$join .= " RIGHT JOIN $relationship_table as r on s.ID = r.subscriber_id AND r.list_id IN( {$list_ids} )";

		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		// Sort out the selection fields.
		$select_this = '*';
		if ( 'all' != $args['fields'] && array_key_exists( $args['fields'], $this->get_columns() ) ) {
			$select_this = esc_sql( $args['fields'] );
		}

		$cache_key = md5( 'nml_subscribers_' . serialize( $args ) );

		$subscribers = wp_cache_get( $cache_key, 'subscribers' );

		if ( 'ID' == $args['orderby'] ) {
			$args['orderby'] = 's.ID';
		}

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $subscribers ) {
			$query = $wpdb->prepare( "SELECT $select_this FROM  $this->table_name s $join $where GROUP BY s.ID ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			if ( 'all' == $args['fields'] ) {
				$subscribers = $wpdb->get_results( $query );
			} else {
				$subscribers = $wpdb->get_col( $query );
			}
			wp_cache_set( $cache_key, $subscribers, 'subscribers', 3600 );
		}

		return $subscribers;

	}

	/**
	 * Count the total number of subscribers in the database
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
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'ID'         => null,
			'email'      => null,
			'first_name' => null,
			'last_name'  => null,
			'status'     => null,
			'referer'   => null,
			'form_name'  => null,
			'ip'         => null,
			'list'       => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific subscriber(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// Specific subscriber by email.
		if ( ! empty( $args['email'] ) ) {

			if ( is_array( $args['email'] ) ) {

				$emails_count       = count( $args['email'] );
				$emails_placeholder = array_fill( 0, $emails_count, '%s' );
				$emails             = implode( ', ', $emails_placeholder );

				$where .= $wpdb->prepare( " AND `email` IN( $emails ) ", $args['email'] );

			} else {

				$where .= $wpdb->prepare( " AND `email` = %s ", $args['email'] );

			}

		}

		// Specific subscriber by first name.
		if ( ! empty( $args['first_name'] ) ) {
			$where .= $wpdb->prepare( " AND `first_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['first_name'] );
		}

		// Specific subscriber by last name.
		if ( ! empty( $args['last_name'] ) ) {
			$where .= $wpdb->prepare( " AND `last_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['last_name'] );
		}

		// By status
		if ( ! empty( $args['status'] ) ) {

			if ( is_array( $args['status'] ) ) {

				$status_count       = count( $args['status'] );
				$status_placeholder = array_fill( 0, $status_count, '%s' );
				$statuses           = implode( ', ', $status_placeholder );

				$where .= $wpdb->prepare( " AND `status` IN( $statuses ) ", $args['status'] );

			} else {

				$where .= $wpdb->prepare( " AND `status` = %s ", $args['status'] );

			}

		}

		// By referer
		if ( ! empty( $args['referer'] ) ) {
			$where .= $wpdb->prepare( " AND `referer` = %s ", $args['referer'] );
		}

		// By form name
		if ( ! empty( $args['form_name'] ) ) {
			$where .= $wpdb->prepare( " AND `form_name` = %s ", $args['form_name'] );
		}

		// @todo by date

		// By IP
		if ( ! empty( $args['ip'] ) ) {
			$where .= $wpdb->prepare( " AND `ip` = %s ", $args['ip'] );
		}

		// By list(s)
		if ( ! empty( $args['list'] ) ) {

			if ( is_array( $args['list'] ) ) {
				$list_ids = implode( ',', array_map( 'intval', $args['list'] ) );
			} else {
				$list_ids = intval( $args['list'] );
			}

			$relationship_table = naked_mailing_list()->list_relationships->table_name;

			$join .= " RIGHT JOIN $relationship_table as r on s.ID = r.subscriber_id AND r.list_id IN( {$list_ids} )";

		}

		$cache_key = md5( 'nml_subscribers_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'subscribers' );

		if ( false === $count ) {
			$query = "SELECT COUNT({$this->primary_key}) FROM  $this->table_name $join $where";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'subscribers', 3600 );
		}

		return absint( $count );

	}

	/**
	 * Increment subscriber email count
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function increment_email_count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'         => null,
			'email'      => null,
			'first_name' => null,
			'last_name'  => null,
			'status'     => 'subscribed',
			'referer'   => null,
			'form_name'  => null,
			'ip'         => null,
			'list'       => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific subscriber(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND s.ID IN( {$ids} ) ";

		}

		// Specific subscriber by email.
		if ( ! empty( $args['email'] ) ) {

			if ( is_array( $args['email'] ) ) {

				$emails_count       = count( $args['email'] );
				$emails_placeholder = array_fill( 0, $emails_count, '%s' );
				$emails             = implode( ', ', $emails_placeholder );

				$where .= $wpdb->prepare( " AND `email` IN( $emails ) ", $args['email'] );

			} else {

				$where .= $wpdb->prepare( " AND `email` = %s ", $args['email'] );

			}

		}

		// Specific subscriber by first name.
		if ( ! empty( $args['first_name'] ) ) {
			$where .= $wpdb->prepare( " AND `first_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['first_name'] );
		}

		// Specific subscriber by last name.
		if ( ! empty( $args['last_name'] ) ) {
			$where .= $wpdb->prepare( " AND `last_name` LIKE '%%%%" . '%s' . "%%%%' ", $args['last_name'] );
		}

		// By status
		if ( ! empty( $args['status'] ) ) {

			if ( is_array( $args['status'] ) ) {

				$status_count       = count( $args['status'] );
				$status_placeholder = array_fill( 0, $status_count, '%s' );
				$statuses           = implode( ', ', $status_placeholder );

				$where .= $wpdb->prepare( " AND `status` IN( $statuses ) ", $args['status'] );

			} else {

				$where .= $wpdb->prepare( " AND `status` = %s ", $args['status'] );

			}

		}

		// By referer
		if ( ! empty( $args['referer'] ) ) {
			$where .= $wpdb->prepare( " AND `referer` = %s ", $args['referer'] );
		}

		// By form name
		if ( ! empty( $args['form_name'] ) ) {
			$where .= $wpdb->prepare( " AND `form_name` = %s ", $args['form_name'] );
		}

		// @todo by date

		// By IP
		if ( ! empty( $args['ip'] ) ) {
			$where .= $wpdb->prepare( " AND `ip` = %s ", $args['ip'] );
		}

		// By list(s)
		if ( ! empty( $args['list'] ) ) {

			if ( is_array( $args['list'] ) ) {
				$list_ids = implode( ',', array_map( 'intval', $args['list'] ) );
			} else {
				$list_ids = intval( $args['list'] );
			}

			$relationship_table = naked_mailing_list()->list_relationships->table_name;

			$join .= " RIGHT JOIN $relationship_table as r on s.ID = r.subscriber_id AND r.list_id IN( {$list_ids} )";

		}

		$query = "UPDATE {$this->table_name} s {$join} SET email_count = email_count + 1 {$where}";
		$result = $wpdb->query( $query );

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
		email varchar(50) NOT NULL,
		first_name mediumtext NOT NULL,
		last_name mediumtext NOT NULL,
		status varchar(50) NOT NULL,
		signup_date datetime NOT NULL,
		confirm_date datetime,
		ip mediumtext NOT NULL,
		referer varchar(255),
		form_name varchar(255),
		email_count bigint(20) NOT NULL,
		notes longtext NOT NULL,
		PRIMARY KEY (ID),
		UNIQUE KEY email (email),
		KEY status (status),
		KEY confirm_date (confirm_date)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}