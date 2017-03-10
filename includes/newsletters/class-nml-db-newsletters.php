<?php

/**
 * Newsletters DB Class
 *
 * Class for interacting with the newsletters database table.
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
 * Class NML_DB_Newsletters
 *
 * @since 1.0
 */
class NML_DB_Newsletters extends NML_DB {

	/**
	 * NML_DB_Newsletters constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_newsletters';
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
			'status'           => '%s',
			'subject'          => '%s',
			'body'             => '%s',
			'from_address'     => '%s',
			'from_name'        => '%s',
			'reply_to_address' => '%s',
			'reply_to_name'    => '%s',
			'created_date'     => '%s',
			'updated_date'     => '%s',
			'sent_date'        => '%s'
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
			'status'           => 'draft', // draft, scheduled, sending, sent
			'subject'          => '',
			'body'             => '',
			'from_address'     => '',
			'from_name'        => '',
			'reply_to_address' => '',
			'reply_to_name'    => '',
			'created_date'     => gmdate( 'Y-m-d H:i:s' ),
			'updated_date'     => gmdate( 'Y-m-d H:i:s' ),
			'sent_date'        => null
		);
	}

	/**
	 * Add a newsletter
	 *
	 * @param array $data Array of newsletter data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated newsletter, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		$newsletter = array_key_exists( 'ID', $args ) ? $this->get_newsletter_by( 'ID', $args['ID'] ) : false;

		if ( $newsletter ) {

			// Update existing newsletter.
			$result = $this->update( $newsletter->ID, $args );

			return $result ? $newsletter->ID : false;

		} else {

			// Create a new newsletter.
			return $this->insert( $args, 'newsletter' );

		}

	}

	/**
	 * Delete a newsletter
	 *
	 * @todo   Maybe create a function or at least delete list relationships.
	 *
	 * @param int $id ID of the newsletter to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$newsletter = $this->get_newsletter_by( 'ID', $id );

		if ( $newsletter->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $newsletter->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Delete multiple newsletters by IDs
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
	 * Checks if a newsletter exists
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
	 * Retrieve a single newsletter from the database
	 *
	 * @param string $field The field to get the newsletter by (ID or subject).
	 * @param int    $value The value of the field to search.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_newsletter_by( $field = 'ID', $value = 0 ) {

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

			case 'subject' :
				$value    = sanitize_text_field( $value );
				$db_field = 'subject';
				break;
			default :
				return false;
		}

		if ( ! $newsletter = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {
			return false;
		}

		return $newsletter;

	}

	/**
	 * Retrieve newsletters from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_newsletters( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'orderby'      => 'ID',
			'order'        => 'DESC',
			'ID'           => null,
			'status'       => null,
			'subject'      => null,
			'created_date' => null,
			'updated_date' => null,
			'list'         => null
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific newsletter(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

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

		// Specific newsletters by subject
		if ( ! empty( $args['subject'] ) ) {
			$where .= $wpdb->prepare( " AND `subject` LIKE '%%%%" . '%s' . "%%%%' ", $args['subject'] );
		}

		// @todo by date created
		// @todo by date updated

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		$cache_key = md5( 'nml_newsletters_' . serialize( $args ) );

		$newsletters = wp_cache_get( $cache_key, 'newsletters' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $newsletters ) {
			$query       = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$newsletters = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $newsletters, 'newsletters', 3600 );
		}

		return $newsletters;

	}

	/**
	 * Count the total number of newsletters in the database
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
			'status'       => null,
			'subject'      => null,
			'created_date' => null,
			'updated_date' => null
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific newsletter(s).
		if ( ! empty( $args['ID'] ) ) {

			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}

			$where .= " AND `ID` IN( {$ids} ) ";

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

		// Specific newsletters by subject
		if ( ! empty( $args['subject'] ) ) {
			$where .= $wpdb->prepare( " AND `subject` LIKE '%%%%" . '%s' . "%%%%' ", $args['subject'] );
		}

		// @todo by date created
		// @todo by date updated

		$cache_key = md5( 'nml_newsletters_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'newsletters' );

		if ( false === $count ) {
			$query = "SELECT COUNT({$this->primary_key}) FROM  $this->table_name $join $where";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'newsletters', 3600 );
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
		status varchar(50) NOT NULL,
		subject varchar(250) NOT NULL,
		body longtext NOT NULL,
		from_address varchar(150) NOT NULL,
		from_name varchar(150) NOT NULL,
		reply_to_address varchar(150) NOT NULL,
		reply_to_name varchar(150) NOT NULL,
		created_date datetime NOT NULL,
		updated_date datetime NOT NULL,
		sent_date datetime,
		PRIMARY KEY (ID),
		KEY status (status),
		KEY type_status (type, status)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}