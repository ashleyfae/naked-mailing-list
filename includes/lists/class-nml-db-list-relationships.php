<?php

/**
 * List Relationships DB Class
 *
 * This class is for interacting with the list relationships database table.
 * Used for mapping relationships between lists and subscribers.
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
 * Class NML_DB_List_Relationships
 *
 * @since 1.0
 */
class NML_DB_List_Relationships extends NML_DB {

	/**
	 * NML_DB_List_Relationships constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_list_relationships';
		$this->primary_key = 'ID';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'ID'            => '%d',
			'list_id'       => '%d',
			'subscriber_id' => '%d'
		);
	}

	/**
	 * Get default column values.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'list_id'       => 0,
			'subscriber_id' => 0
		);
	}

	/**
	 * Add a relationship
	 *
	 * @param array $data Relationship data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int Relationship ID.
	 */
	public function add( $data = array() ) {

		$defaults = array();

		$args = wp_parse_args( $data, $defaults );

		$relationship = ( array_key_exists( 'ID', $args ) ) ? $this->get_relationship_by( 'ID', $args['ID'] ) : false;

		if ( $relationship ) {

			// Updating an existing relationship.
			$this->update( $relationship->ID, $args );

			return $relationship->ID;

		} else {

			// Adding a new relationship.
			return $this->insert( $args, 'relationship' );

		}

	}

	/**
	 * Delete a relationship
	 *
	 * @param bool $id ID of the relationship to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|int False on failure.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$relationship = $this->get_relationship_by( 'ID', $id );

		if ( $relationship->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $relationship->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Delete all relationships for a given list
	 *
	 * @param int $list_id ID of the list to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public function delete_list_relationships( $list_id ) {

		global $wpdb;

		$query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE `list_id` = %d", absint( $list_id ) );

		return $wpdb->query( $query );

	}

	/**
	 * Delete all relationships for a given subscriber
	 *
	 * @param int $subscriber_id ID of the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public function delete_subscriber_relationships( $subscriber_id ) {

		global $wpdb;

		$query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE `subscriber_id` = %d", absint( $subscriber_id ) );

		return $wpdb->query( $query );

	}

	/**
	 * Retrieves a single relationship from the database.
	 *
	 * @param string $field The column to search.
	 * @param int    $value The value to check against the column.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Upon success, an object of the term. Upon failure, false.
	 */
	public function get_relationship_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( $field == 'ID' ) {
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

			default :
				return false;

		}

		if ( ! $relationship = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {

			return false;

		}

		return $relationship;

	}

	/**
	 * Retrieve relationships from the database.
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0
	 * @return array Array of objects.
	 */
	public function get_relationships( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'            => false,
			'number'        => 20,
			'offset'        => 0,
			'list_id'       => false,
			'subscriber_id' => false,
			'orderby'       => 'ID',
			'order'         => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		// Big ass number to get them all.
		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific relationships.
		if ( ! empty( $args['ID'] ) ) {
			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}
			$where .= " AND `ID` IN( {$ids} ) ";
		}

		// Specific lists.
		if ( ! empty( $args['list_id'] ) ) {
			if ( is_array( $args['list_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['list_id'] ) );
			} else {
				$ids = intval( $args['list_id'] );
			}
			$where .= " AND `list_id` IN( {$ids} ) ";
		}

		// Specific subscribers.
		if ( ! empty( $args['subscriber_id'] ) ) {
			if ( is_array( $args['subscriber_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['subscriber_id'] ) );
			} else {
				$ids = intval( $args['subscriber_id'] );
			}
			$where .= " AND `subscriber_id` IN( {$ids} ) ";
		}

		$orderby = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'list_id' : wp_strip_all_tags( $args['orderby'] );
		$order   = ( 'ASC' == strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
		$orderby = esc_sql( $orderby );
		$order   = esc_sql( $order );

		$cache_key = md5( 'nml_list_relationships_' . serialize( $args ) );

		$relationships = wp_cache_get( $cache_key, 'newsletter_list_relationships' );

		if ( $relationships === false ) {
			$query         = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY $orderby $order LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$relationships = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $relationships, 'newsletter_list_relationships', 3600 );
		}

		return $relationships;

	}

	/**
	 * Count relationships
	 *
	 * @param array $args
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'            => false,
			'offset'        => 0,
			'list_id'       => false,
			'subscriber_id' => false
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific relationships.
		if ( ! empty( $args['ID'] ) ) {
			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}
			$where .= " AND `ID` IN( {$ids} ) ";
		}

		// Specific lists.
		if ( ! empty( $args['list_id'] ) ) {
			if ( is_array( $args['list_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['list_id'] ) );
			} else {
				$ids = intval( $args['list_id'] );
			}
			$where .= " AND `list_id` IN( {$ids} ) ";
		}

		// Specific subscribers.
		if ( ! empty( $args['subscriber_id'] ) ) {
			if ( is_array( $args['subscriber_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['subscriber_id'] ) );
			} else {
				$ids = intval( $args['subscriber_id'] );
			}
			$where .= " AND `subscriber_id` IN( {$ids} ) ";
		}

		$cache_key = md5( 'nml_list_relationships_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'newsletter_list_relationships' );

		if ( $count === false ) {
			$query = "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$join} {$where};";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'newsletter_list_relationships', 3600 );
		}

		return absint( $count );

	}

	/**
	 * Create the table.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		list_id bigint(20) NOT NULL,
		subscriber_id bigint(20) NOT NULL,
		PRIMARY KEY  (ID),
		INDEX list_id (list_id),
		INDEX subscriber_id (subscriber_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}