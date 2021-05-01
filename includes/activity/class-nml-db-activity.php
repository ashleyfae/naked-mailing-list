<?php

/**
 * Activity DB Class
 *
 * This class is for managing activity logs.
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
 * Class NML_DB_Activity
 *
 * @since 1.0
 */
class NML_DB_Activity extends NML_DB {

	/**
	 * NML_DB_Activity constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_activity';
		$this->primary_key = 'ID';
		$this->version     = '1.0';

		$this->init();

	}

	/**
	 * Initialize automated activity log creation.
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function init() {

		add_action( 'nml_post_insert_subscriber', array( $this, 'new_subscriber' ), 10, 2 );
		add_action( 'nml_subscriber_send_confirmation_email', array( $this, 'confirmation_sent' ), 10, 2 );
		add_action( 'nml_subscriber_confirm', array( $this, 'confirm_subscriber' ), 10, 2 );
		add_action( 'nml_subscriber_set_unsubscribed', array( $this, 'unsubscribe' ), 10, 3 );

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
			'ID'            => '%d',
			'type'          => '%s',
			'subscriber_id' => '%d',
			'newsletter_id' => '%d',
			'date'          => '%s'
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
			'type'          => '',
			'subscriber_id' => null,
			'newsletter_id' => null,
			'date'          => gmdate( 'Y-m-d H:i:s' )
		);
	}

	/**
	 * Get types of activity
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_types() {

		$types = array(
			'new_subscriber',
			'subscriber_confirmation_sent',
			'subscriber_confirm',
			'unsubscribe',
			'newsletter_processing_started',
			'newsletter_processing_finished'
		);

		/**
		 * Filters the types of activities that are available.
		 *
		 * @param array $types
		 *
		 * @since 1.0
		 */
		return apply_filters( 'nml_activity_types', $types );

	}

	/**
	 * Add an activity log
	 *
	 * @param array $data Array of activity data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated activity, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		$activity = array_key_exists( 'ID', $data ) ? $this->get_entry( $args['ID'] ) : false;

		if ( $activity ) {

			// Update existing activity log.
			$result = $this->update( $activity->ID, $args );

			return $result ? $activity->ID : false;

		} else {

			// Create a new activity log.
			return $this->insert( $args, 'activity' );

		}

	}

	/**
	 * Delete a specific activity entry
	 *
	 * @param int $id ID of the activity entry to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$entry = $this->get_entry( $id );

		if ( $entry && $entry->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $entry->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Delete all entries for a given subscriber
	 *
	 * @param int $subscriber_id ID of the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public function delete_subscriber_entries( $subscriber_id ) {

		global $wpdb;

		$query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE `subscriber_id` = %d", absint( $subscriber_id ) );

		return $wpdb->query( $query );

	}

	/**
	 * Retrieve a single activity entry from the database (by ID).
	 *
	 * @param int $id ID of the entry to retrieve.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_entry( $id ) {

		global $wpdb;

		if ( ! is_numeric( $id ) ) {
			return false;
		}

		$entry_id = absint( $id );

		if ( ! $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE `ID` = %d LIMIT 1", $entry_id ) ) ) {
			return false;
		}

		return $entry;

	}

	/**
	 * Retrieve activity logs from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_activity( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'        => 20,
			'offset'        => 0,
			'orderby'       => 'ID',
			'order'         => 'DESC',
			'ID'            => null,
			'type'          => null,
			'subscriber_id' => null,
			'newsletter_id' => null,
			'date'          => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific activity log(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// By type
		if ( ! empty( $args['type'] ) ) {

			if ( is_array( $args['type'] ) ) {

				$type_count       = count( $args['type'] );
				$type_placeholder = array_fill( 0, $type_count, '%s' );
				$types            = implode( ', ', $type_placeholder );

				$where .= $wpdb->prepare( " AND `type` IN( $types ) ", $args['type'] );

			} else {

				$where .= $wpdb->prepare( " AND `type` = %s ", $args['type'] );

			}

		}

		// By subscriber ID
		if ( ! empty( $args['subscriber_id'] ) ) {

			if ( is_array( $args['subscriber_id'] ) ) {

				$subscriber_count       = count( $args['subscriber_id'] );
				$subscriber_placeholder = array_fill( 0, $subscriber_count, '%s' );
				$subscribers            = implode( ', ', $subscriber_placeholder );

				$where .= $wpdb->prepare( " AND `subscriber_id` IN( $subscribers ) ", $args['subscriber_id'] );

			} else {

				$where .= $wpdb->prepare( " AND `subscriber_id` = %d ", absint( $args['subscriber_id'] ) );

			}

		}

		// By newsletter ID
		if ( ! empty( $args['newsletter_id'] ) ) {

			if ( is_array( $args['newsletter_id'] ) ) {

				$newsletter_count       = count( $args['newsletter_id'] );
				$newsletter_placeholder = array_fill( 0, $newsletter_count, '%s' );
				$newsletter             = implode( ', ', $newsletter_placeholder );

				$where .= $wpdb->prepare( " AND `newsletter_id` IN( $newsletter ) ", $args['newsletter_id'] );

			} else {

				$where .= $wpdb->prepare( " AND `newsletter_id` = %d ", absint( $args['newsletter_id'] ) );

			}

		}

		// @todo by date

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		$cache_key = md5( 'nml_activity_' . serialize( $args ) );

		$activity = wp_cache_get( $cache_key, 'activity' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $activity ) {
			$query    = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$activity = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $activity, 'activity', 3600 );
		}

		return $activity;

	}

	/**
	 * Count the total number of activity logs in the database
	 *
	 * @param array $args     Arguments to override the defaults.
	 * @param bool  $full_day Whether or not to use full days.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function count( $args = array(), $full_day = false ) {

		global $wpdb;

		$defaults = array(
			'ID'            => null,
			'type'          => null,
			'subscriber_id' => null,
			'newsletter_id' => null,
			'date'          => null
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific activity log(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

		}

		// By type
		if ( ! empty( $args['type'] ) ) {

			if ( is_array( $args['type'] ) ) {

				$type_count       = count( $args['type'] );
				$type_placeholder = array_fill( 0, $type_count, '%s' );
				$types            = implode( ', ', $type_placeholder );

				$where .= $wpdb->prepare( " AND `type` IN( $types ) ", $args['type'] );

			} else {

				$where .= $wpdb->prepare( " AND `type` = %s ", $args['type'] );

			}

		}

		// By subscriber ID
		if ( ! empty( $args['subscriber_id'] ) ) {

			if ( is_array( $args['subscriber_id'] ) ) {

				$subscriber_count       = count( $args['subscriber_id'] );
				$subscriber_placeholder = array_fill( 0, $subscriber_count, '%s' );
				$subscribers            = implode( ', ', $subscriber_placeholder );

				$where .= $wpdb->prepare( " AND `subscriber_id` IN( $subscribers ) ", $args['subscriber_id'] );

			} else {

				$where .= $wpdb->prepare( " AND `subscriber_id` = %d ", absint( $args['subscriber_id'] ) );

			}

		}

		// By newsletter ID
		if ( ! empty( $args['newsletter_id'] ) ) {

			if ( is_array( $args['newsletter_id'] ) ) {

				$newsletter_count       = count( $args['newsletter_id'] );
				$newsletter_placeholder = array_fill( 0, $newsletter_count, '%s' );
				$newsletter             = implode( ', ', $newsletter_placeholder );

				$where .= $wpdb->prepare( " AND `newsletter_id` IN( $newsletter ) ", $args['newsletter_id'] );

			} else {

				$where .= $wpdb->prepare( " AND `newsletter_id` = %d ", absint( $args['newsletter_id'] ) );

			}

		}

		// Entries created in a specific date or in a date range.
		if ( ! empty( $args['date'] ) ) {

			if ( is_array( $args['date'] ) ) {

				if ( ! empty( $args['date']['start'] ) ) {

					$start_temp   = nml_is_valid_timestamp( (string) $args['date']['start'] ) ? intval( $args['date']['start'] ) : strtotime( $args['date']['start'] );
					$start_format = $full_day ? 'Y-m-d 00:00:00' : 'Y-m-d H:i:s';
					$start        = date( $start_format, $start_temp );
					$where        .= " AND `date` >= '{$start}'";

				}

				if ( ! empty( $args['date']['end'] ) ) {

					$end_temp   = nml_is_valid_timestamp( (string) $args['date']['end'] ) ? intval( $args['date']['end'] ) : strtotime( $args['date']['end'] );
					$end_format = $full_day ? 'Y-m-d 23:59:59' : 'Y-m-d H:i:s';
					$end        = date( $end_format, $end_temp );
					$where      .= " AND `date` <= '{$end}'";

				}

			} else {

				$year  = date( 'Y', strtotime( $args['date'] ) );
				$month = date( 'm', strtotime( $args['date'] ) );
				$day   = date( 'd', strtotime( $args['date'] ) );

				$where .= " AND $year = YEAR ( date ) AND $month = MONTH ( date ) AND $day = DAY ( date )";
			}

		}

		$cache_key = md5( 'nml_activity_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'activity' );

		if ( false === $count ) {
			$query = "SELECT COUNT($this->primary_key) FROM  $this->table_name $join $where";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'activity', 3600 );
		}

		return absint( $count );

	}

	/**
	 * Insert new activity log when a new subscriber is added.
	 *
	 * @param int   $subscriber_id   ID of the newly added subscriber.
	 * @param array $subscriber_data Array of subscriber data.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function new_subscriber( $subscriber_id = 0, $subscriber_data = array() ) {

		if ( empty( $subscriber_id ) ) {
			return;
		}

		$activity_data = array(
			'type'          => 'new_subscriber',
			'subscriber_id' => absint( $subscriber_id )
		);

		$this->add( $activity_data );

	}

	/**
	 * Insert new activity log when a confirmation email is sent to a subscriber.
	 * This can happen multiple times (when confirmations are resent via cron job
	 * or manually).
	 *
	 * @param int            $subscriber_id
	 * @param NML_Subscriber $subscriber
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function confirmation_sent( $subscriber_id, $subscriber ) {

		if ( empty( $subscriber_id ) ) {
			return;
		}

		$activity_data = array(
			'type'          => 'subscriber_confirmation_sent',
			'subscriber_id' => absint( $subscriber_id )
		);

		$this->add( $activity_data );

	}

	/**
	 * Insert new activity log when a subscriber is confirmed.
	 *
	 * @param int            $subscriber_id ID of the subscriber.
	 * @param NML_Subscriber $subscriber    Subscriber object.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function confirm_subscriber( $subscriber_id, $subscriber ) {

		if ( empty( $subscriber_id ) ) {
			return;
		}

		$activity_data = array(
			'type'          => 'subscriber_confirm',
			'subscriber_id' => absint( $subscriber_id )
		);

		$this->add( $activity_data );

	}

	/**
	 * @param string         $old_status    Previous status.
	 * @param int            $subscriber_id Subscriber ID.
	 * @param NML_Subscriber $subscriber    Subscriber object.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function unsubscribe( $old_status, $subscriber_id, $subscriber ) {

		$activity_data = array(
			'type'          => 'unsubscribe',
			'subscriber_id' => absint( $subscriber_id )
		);

		$this->add( $activity_data );

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
		type varchar(50) NOT NULL,
		subscriber_id bigint(20),
		newsletter_id bigint(20),
		date datetime NOT NULL,
		PRIMARY KEY (ID),
		KEY subscriber_id (subscriber_id),
		KEY date (date),
		KEY type_date (type, date)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}
