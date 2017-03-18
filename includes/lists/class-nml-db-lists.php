<?php

/**
 * Lists DB Class
 *
 * For interacting with the newsletter list database table.
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
 * Class NML_DB_Lists
 *
 * @since 1.0
 */
class NML_DB_Lists extends NML_DB {

	/**
	 * NML_DB_Lists constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_lists';
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
			'ID'          => '%d',
			'type'        => '%s',
			'name'        => '%s',
			'description' => '%s',
			'count'       => '%d'
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
			'type'        => 'list', // list or tag
			'name'        => '',
			'description' => '',
			'count'       => 0
		);
	}

	/**
	 * Add or update a list
	 *
	 * @param array $data List data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int List ID.
	 */
	public function add( $data = array() ) {

		$defaults = array();

		$args = wp_parse_args( $data, $defaults );

		$list = ( array_key_exists( 'ID', $args ) ) ? $this->get_list_by( 'ID', $args['ID'] ) : false;

		if ( $list ) {

			// Updating an existing list.
			$this->update( $list->ID, $args );

			return $list->ID;

		} else {

			// Adding a new list.
			return $this->insert( $args, 'list' );

		}

	}

	/**
	 * Delete a list
	 *
	 * @param int $id ID of the list to delete.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|int False on failure.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$list = $this->get_list_by( 'ID', $id );

		if ( $list->ID > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'ID' => $list->ID ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Check if a list exists.
	 *
	 * @param string $value Value of the column.
	 * @param string $field Which field to check.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function exists( $value = '', $field = 'name' ) {

		$columns = $this->get_columns();
		if ( ! array_key_exists( $field, $columns ) ) {
			return false;
		}

		return (bool) $this->get_column_by( 'ID', $field, $value );

	}

	/**
	 * Retrieves a single list from the database.
	 *
	 * @param string $field The column to search.
	 * @param int    $value The value to check against the column.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return object|false Upon success, an object of the list. Upon failure, false.
	 */
	public function get_list_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( 'ID' == $field ) {
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
				$db_field = 'name';
				$value    = wp_strip_all_tags( $value );
				break;

			default :
				return false;

		}

		if ( ! $list = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {

			return false;

		}

		return wp_unslash( $list );

	}

	/**
	 * Retrieve lists from the database.
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array Array of objects.
	 */
	public function get_lists( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'      => false,
			'number'  => 20,
			'offset'  => 0,
			'name'    => false,
			'type'    => false,
			'count'   => false,
			'orderby' => 'ID',
			'order'   => 'DESC',
			'fields'  => 'all'
		);

		$args = wp_parse_args( $args, $defaults );

		// Big ass number to get them all.
		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific lists.
		if ( ! empty( $args['ID'] ) ) {
			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}
			$where .= " AND `ID` IN( {$ids} ) ";
		}

		// Lists with a specific name.
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", wp_strip_all_tags( $args['name'] ) );
		}

		// Lists with a specific type.
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( " AND `type` LIKE '%s' ", wp_strip_all_tags( $args['type'] ) );
		}

		// Lists with a specific count.
		if ( $args['count'] !== false ) {
			if ( is_numeric( $args['count'] ) ) {
				$where .= $wpdb->prepare( " AND `count` LIKE '%d' ", absint( $args['count'] ) );
			} elseif ( is_array( $args['count'] ) ) {
				if ( array_key_exists( 'greater_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` > '%d' ", absint( $args['count'] ) );
				}

				if ( array_key_exists( 'less_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` < '%d' ", absint( $args['count'] ) );
				}
			}
		}

		$orderby = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : wp_strip_all_tags( $args['orderby'] );
		$order   = ( 'ASC' == strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
		$orderby = esc_sql( $orderby );
		$order   = esc_sql( $order );

		$cache_key = md5( 'nml_lists_' . serialize( $args ) );

		$lists = wp_cache_get( $cache_key, 'newsletter_lists' );

		$select_this = '*';
		if ( 'names' == $args['fields'] ) {
			$select_this = 'lists.name';
		} elseif ( 'ids' == $args['fields'] ) {
			$select_this = 'lists.ID';
		}

		if ( $lists === false ) {
			$query = $wpdb->prepare( "SELECT $select_this FROM  $this->table_name as lists $join $where GROUP BY $this->primary_key ORDER BY $orderby $order LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			if ( 'names' == $args['fields'] || 'ids' == $args['fields'] ) {
				$lists = $wpdb->get_col( $query );
			} else {
				$lists = $wpdb->get_results( $query );
			}
			wp_cache_set( $cache_key, $lists, 'newsletter_lists', 3600 );
		}

		return wp_unslash( $lists );

	}

	/**
	 * Count the total number of lists in the database.
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0
	 * @return int Number of results.
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'ID'    => false,
			'name'  => false,
			'type'  => false,
			'count' => false
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific lists.
		if ( ! empty( $args['ID'] ) ) {
			if ( is_array( $args['ID'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['ID'] ) );
			} else {
				$ids = intval( $args['ID'] );
			}
			$where .= " AND `ID` IN( {$ids} ) ";
		}

		// Lists with a specific name.
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", wp_strip_all_tags( $args['name'] ) );
		}

		// Lists with a specific type.
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( " AND `type` = '%s' ", wp_strip_all_tags( $args['type'] ) );
		}

		// Lists with a specific count.
		if ( $args['count'] !== false ) {
			if ( is_numeric( $args['count'] ) ) {
				$where .= $wpdb->prepare( " AND `count` LIKE '%d' ", absint( $args['count'] ) );
			} elseif ( is_array( $args['count'] ) ) {
				if ( array_key_exists( 'greater_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` > '%d' ", absint( $args['count'] ) );
				}

				if ( array_key_exists( 'less_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` < '%d' ", absint( $args['count'] ) );
				}
			}
		}

		$cache_key = md5( 'nml_lists_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'newsletter_lists' );

		if ( $count === false ) {
			$query = "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$join} {$where};";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'newsletter_lists', 3600 );
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
		type varchar(32) NOT NULL,
		name varchar(200) NOT NULL,
		description longtext NOT NULL,
		count bigint(20) NOT NULL,
		PRIMARY KEY  (ID),
		UNIQUE KEY id_type_name (ID, type, name),
		INDEX type (type),
		INDEX name (name)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}