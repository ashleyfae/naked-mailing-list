<?php

/**
 * Queue DB Class
 *
 * For interacting with the queue database table and processing emails.
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
 * Class NML_DB_Queue
 *
 * @since 1.0
 */
class NML_DB_Queue extends NML_DB {

	/**
	 * NML_DB_Queue constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_queue';
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
			'ID'              => '%d',
			'newsletter_id'   => '%d',
			'status'          => '%s',
			'offset'          => '%d',
			'date_created'    => '%s',
			'date_to_process' => '%s'
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
			'newsletter_id'   => 0,
			'status'          => 'pending',
			'offset'          => 0,
			'date_created'    => gmdate( 'Y-m-d H:i:s' ),
			'date_to_process' => gmdate( 'Y-m-d H:i:s' )
		);
	}

	/**
	 * Add a queue entry
	 *
	 * @param array $data Array of queue data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated entry, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		// Newsletter ID is required.
		if ( empty( $args['newsletter_id'] ) ) {
			return false;
		}

		$entry = array_key_exists( 'ID', $args ) ? $this->get_entry_by( 'ID', $args['ID'] ) : false;

		if ( $entry ) {

			// Update existing entry.
			$result = $this->update( $entry->ID, $args );

			return $result ? $entry->ID : false;

		} else {

			// Create a new entry.
			return $this->insert( $args, 'queue_entry' );

		}

	}

	/**
	 * Delete a queue entry
	 *
	 * @param int $id Queue ID number.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$entry = $this->get_entry_by( 'ID', $id );

		if ( $entry->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $entry->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Checks if a queue entry exists for a newsletter
	 *
	 * @param string $value Field value.
	 * @param string $field Column to check the value of.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function exists( $value = '', $field = 'newsletter_id' ) {

		$columns = $this->get_columns();
		if ( ! array_key_exists( $field, $columns ) ) {
			return false;
		}

		return (bool) $this->get_column_by( 'ID', $field, $value );

	}

	/**
	 * Retrieve a single queue entry from the database
	 *
	 * @param string $field The field to get the entry by (ID or newsletter_id).
	 * @param int    $value The value of the field to search.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_entry_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( 'ID' === $field || 'newsletter_id' == $field ) {

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

			case 'newsletter_id' :
				$db_field = 'newsletter_id';
				break;
			default :
				return false;
		}

		if ( ! $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {
			return false;
		}

		return $entry;

	}

	/**
	 * Retrieve queue entries from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_entries( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'          => 20,
			'offset'          => 0,
			'orderby'         => 'ID',
			'order'           => 'DESC',
			'ID'              => null,
			'newsletter_id'   => null,
			'status'          => null,
			'date_created'    => null,
			'date_to_process' => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific entries.
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// Specific entries by newsletter ID.
		if ( ! empty( $args['newsletter_id'] ) ) {

			if ( is_array( $args['newsletter_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['newsletter_id'] ) );
			} else {
				$ids = intval( $args['newsletter_id'] );
			}

			$where .= " AND `newsletter_id` IN( {$ids} ) ";

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

		// By date created.
		if ( ! empty( $args['date_created'] ) ) {

			if ( is_array( $args['date_created'] ) ) {

				if ( ! empty( $args['date_created']['start'] ) ) {
					$start = get_gmt_from_date( wp_strip_all_tags( $args['date_created']['start'] ), 'Y-m-d H:i:s' );
					$where .= $wpdb->prepare( " AND `date_created` >= %s", $start );
				}

				if ( ! empty( $args['date_created']['end'] ) ) {
					$end = get_gmt_from_date( wp_strip_all_tags( $args['date_created']['end'] ), 'Y-m-d H:i:s' );
					$wpdb->prepare( " AND `date_created` <= %s", $end );
				}

			} else {

				$year  = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'Y' );
				$month = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'm' );
				$day   = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'd' );
				$where .= $wpdb->prepare( " AND %d = YEAR ( date_created ) AND %d = MONTH ( date_created ) AND %d = DAY ( date_created )", $year, $month, $day );

			}

		}

		// By processing date.
		if ( ! empty( $args['date_to_process'] ) ) {

			if ( is_array( $args['date_to_process'] ) ) {

				if ( ! empty( $args['date_to_process']['start'] ) ) {
					$start = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process']['start'] ), 'Y-m-d H:i:s' );
					$where .= $wpdb->prepare( " AND `date_to_process` >= %s", $start );
				}

				if ( ! empty( $args['date_to_process']['end'] ) ) {
					$end = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process']['end'] ), 'Y-m-d H:i:s' );
					$wpdb->prepare( " AND `date_to_process` <= %s", $end );
				}

			} else {

				$year  = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'Y' );
				$month = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'm' );
				$day   = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'd' );
				$where .= $wpdb->prepare( " AND %d = YEAR ( date_to_process ) AND %d = MONTH ( date_to_process ) AND %d = DAY ( date_to_process )", $year, $month, $day );

			}

		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		$cache_key = md5( 'nml_queue_' . serialize( $args ) );

		$entries = wp_cache_get( $cache_key, 'newsletter_queue' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $entries ) {
			$query   = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$entries = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $entries, 'newsletter_queue', 3600 );
		}

		return $entries;

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
			'ID'              => null,
			'newsletter_id'   => null,
			'status'          => null,
			'date_created'    => null,
			'date_to_process' => null
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific entries.
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// Specific entries by newsletter ID.
		if ( ! empty( $args['newsletter_id'] ) ) {

			if ( is_array( $args['newsletter_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['newsletter_id'] ) );
			} else {
				$ids = intval( $args['newsletter_id'] );
			}

			$where .= " AND `newsletter_id` IN( {$ids} ) ";

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

		// By date created.
		if ( ! empty( $args['date_created'] ) ) {

			if ( is_array( $args['date_created'] ) ) {

				if ( ! empty( $args['date_created']['start'] ) ) {
					$start = get_gmt_from_date( wp_strip_all_tags( $args['date_created']['start'] ), 'Y-m-d H:i:s' );
					$where .= $wpdb->prepare( " AND `date_created` >= %s", $start );
				}

				if ( ! empty( $args['date_created']['end'] ) ) {
					$end = get_gmt_from_date( wp_strip_all_tags( $args['date_created']['end'] ), 'Y-m-d H:i:s' );
					$wpdb->prepare( " AND `date_created` <= %s", $end );
				}

			} else {

				$year  = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'Y' );
				$month = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'm' );
				$day   = get_gmt_from_date( wp_strip_all_tags( $args['date_created'] ), 'd' );
				$where .= $wpdb->prepare( " AND %d = YEAR ( date_created ) AND %d = MONTH ( date_created ) AND %d = DAY ( date_created )", $year, $month, $day );

			}

		}

		// By processing date.
		if ( ! empty( $args['date_to_process'] ) ) {

			if ( is_array( $args['date_to_process'] ) ) {

				if ( ! empty( $args['date_to_process']['start'] ) ) {
					$start = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process']['start'] ), 'Y-m-d H:i:s' );
					$where .= $wpdb->prepare( " AND `date_to_process` >= %s", $start );
				}

				if ( ! empty( $args['date_to_process']['end'] ) ) {
					$end = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process']['end'] ), 'Y-m-d H:i:s' );
					$wpdb->prepare( " AND `date_to_process` <= %s", $end );
				}

			} else {

				$year  = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'Y' );
				$month = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'm' );
				$day   = get_gmt_from_date( wp_strip_all_tags( $args['date_to_process'] ), 'd' );
				$where .= $wpdb->prepare( " AND %d = YEAR ( date_to_process ) AND %d = MONTH ( date_to_process ) AND %d = DAY ( date_to_process )", $year, $month, $day );

			}

		}

		$cache_key = md5( 'nml_queue_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'newsletter_queue' );

		if ( false === $count ) {
			$query = "SELECT COUNT({$this->primary_key}) FROM $this->table_name $join $where;";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'newsletter_queue', 3600 );
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
		newsletter_id bigint(20) NOT NULL,
		status varchar(50) NOT NULL,
		offset bigint(20) NOT NULL,
		date_created datetime NOT NULL,
		date_to_process datetime NOT NULL,
		PRIMARY KEY (ID),
		KEY status (status),
		KEY date_to_process_status (date_to_process, status)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}