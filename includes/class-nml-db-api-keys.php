<?php

/**
 * API Keys DB Class
 *
 * This class is for interacting with the API Key database table.
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
 * Class NML_DB_API_Keys
 *
 * @since 1.0
 */
class NML_DB_API_Keys extends NML_DB {

	/**
	 * NML_DB_API_Keys constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'nml_api_keys';
		$this->primary_key = 'ID';
		$this->version     = '1.0';

		add_action( 'nml_action_process_api_key', array( $this, 'process_api_key' ) );

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
			'user_id'      => '%d',
			'api_key'      => '%s',
			'api_secret'   => '%s',
			'active'       => '%d',
			'last_updated' => '%s'
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
			'user_id'      => 0,
			'api_key'      => '',
			'api_secret'   => '',
			'active'       => 1,
			'last_updated' => gmdate( 'Y-m-d H:i:s' )
		);
	}

	/**
	 * Add an API key
	 *
	 * @param array $data Array of data.
	 *
	 * @access public
	 * @since  1.0
	 * @return int|false ID of the added/updated key, or false on failure.
	 */
	public function add( $data = array() ) {

		$defaults = array();
		$args     = wp_parse_args( $data, $defaults );

		if ( empty( $args['user_id'] ) || ! is_numeric( $args['user_id'] ) ) {
			return false;
		}

		// Auto create key.
		if ( empty( $args['api_key'] ) ) {
			$args['api_key'] = $this->generate_key( $args['user_id'] );
		}

		// Auto create secret.
		if ( empty( $args['api_secret'] ) ) {
			$args['api_secret'] = $this->generate_secret( $args['user_id'] );
		}

		// Add last updated as now.
		$args['last_updated'] = gmdate( 'Y-m-d H:i:s' );

		$key = $this->get_key_by( 'user_id', $args['user_id'] );

		if ( $key ) {

			// Update existing key.
			$result = $this->update( $key->ID, $args );

			return $result ? $key->ID : false;

		} else {

			// Create a new key.
			return $this->insert( $args, 'api_key' );

		}

	}

	/**
	 * Generate API key
	 *
	 * Note: This does not save the key, just returns it.
	 *
	 * @param int|string $user_id_or_email User ID or email address.
	 *
	 * @access public
	 * @since  1.0
	 * @return string|false API key or false on failure.
	 */
	public function generate_key( $user_id_or_email ) {
		$user_email = false;

		if ( is_numeric( $user_id_or_email ) ) {
			$user       = new WP_User( $user_id_or_email );
			$user_email = $user->user_email;
		} elseif ( is_email( $user_id_or_email ) ) {
			$user_email = $user_id_or_email;
		}

		if ( empty( $user_email ) ) {
			return false;
		}

		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$key      = hash( 'md5', $user_email . $auth_key . date( 'U' ) );

		return $key;
	}

	/**
	 * Generate API secret
	 *
	 * Note: This does not save the secret, just returns it.
	 *
	 * @param int|string $user_id_or_email User ID or email address.
	 *
	 * @access public
	 * @since  1.0
	 * @return string|false API secret or false on failure.
	 */
	public function generate_secret( $user_id_or_email ) {
		$user_id = 0;

		if ( is_numeric( $user_id_or_email ) ) {
			$user_id = absint( $user_id_or_email );
		} elseif ( is_email( $user_id_or_email ) ) {
			$user = get_user_by( 'email', $user_id_or_email );

			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
			}
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secret   = hash( 'md5', $user_id . $auth_key . date( 'U' ) );

		return $secret;
	}

	/**
	 * Process API key actions from the table
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function process_api_key() {

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'nml_api_nonce' ) ) {
			wp_die( __( 'Nonce verification failed.', 'naked-mailing-list' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to edit API keys.', 'naked-mailing-list' ) );
		}

		$process = isset( $_REQUEST['nml_api_process'] ) ? $_REQUEST['nml_api_process'] : '';

		if ( empty( $process ) ) {
			wp_die( __( 'Missing API key process.', 'naked-mailing-list' ) );
		}

		switch ( $process ) {

			case 'create' :
				$username = isset( $_REQUEST['username'] ) ? wp_strip_all_tags( $_REQUEST['username'] ) : '';
				if ( empty( $username ) || ! username_exists( $username ) ) {
					wp_die( __( 'Error: Invalid username.', 'naked-mailing-list' ) );
				}
				$user = get_user_by( 'login', $username );
				$this->add( array( 'user_id' => $user->ID ) );

				$url = add_query_arg( array(
					'nml-message' => 'api-key-created'
				), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );

				wp_safe_redirect( $url );
				break;

			case 'reissue' :
				$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
				if ( empty( $user_id ) ) {
					wp_die( __( 'Invalid user ID', 'naked-mailing-list' ) );
				}
				$this->add( array( 'user_id' => $user_id ) );

				$url = add_query_arg( array(
					'nml-message' => 'api-key-reissued'
				), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );

				wp_safe_redirect( $url );
				break;

			case 'activate' :
				$id = isset( $_REQUEST['ID'] ) ? absint( $_REQUEST['ID'] ) : false;
				if ( empty( $id ) ) {
					wp_die( __( 'Invalid API key ID', 'naked-mailing-list' ) );
				}
				$this->update( $id, array( 'active' => 1, 'last_updated' => gmdate( 'Y-m-d H:i:s' ) ) );

				$url = add_query_arg( array(
					'nml-message' => 'api-key-activated'
				), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );

				wp_safe_redirect( $url );
				break;

			case 'deactivate' :
				$id = isset( $_REQUEST['ID'] ) ? absint( $_REQUEST['ID'] ) : false;
				if ( empty( $id ) ) {
					wp_die( __( 'Invalid API key ID', 'naked-mailing-list' ) );
				}
				$this->update( $id, array( 'active' => 0, 'last_updated' => gmdate( 'Y-m-d H:i:s' ) ) );

				$url = add_query_arg( array(
					'nml-message' => 'api-key-deactivated'
				), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );

				wp_safe_redirect( $url );
				break;

			case 'delete' :
				$id = isset( $_REQUEST['ID'] ) ? absint( $_REQUEST['ID'] ) : false;
				if ( empty( $id ) ) {
					wp_die( __( 'Invalid API key ID', 'naked-mailing-list' ) );
				}
				$this->delete( $id );
				$url = add_query_arg( array(
					'nml-message' => 'api-key-deleted'
				), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );

				wp_safe_redirect( $url );
				break;

		}

		exit;

	}

	/**
	 * Delete a key by the user ID
	 *
	 * @param int $user_id ID of the user whose key to delete
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function delete_user_key( $user_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $user_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE `user_id` = %d", $row_id ) ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Checks if an API key exists for a user
	 *
	 * @param int $user_id ID of the user to check.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function exists( $user_id ) {

		return (bool) $this->get_column_by( 'ID', 'user_id', absint( $user_id ) );

	}

	/**
	 * Retrieve a single key from the database
	 *
	 * @param string $field The field to get the key by (ID, user_id, key, secret).
	 * @param int    $value The value of the field to search.
	 *
	 * @access public
	 * @since  1.0
	 * @return object|false Database row object on success, false on failure.
	 */
	public function get_key_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( 'ID' === $field || 'user_id' == $field ) {

			if ( ! is_numeric( $value ) ) {
				return false;
			}

			$value = intval( $value );

			if ( $value < 1 ) {
				return false;
			}

		} elseif ( 'api_key' === $field || 'api_secret' == $field ) {

			$value = trim( $value );

		}

		if ( ! $value ) {
			return false;
		}

		switch ( $field ) {
			case 'ID' :
				$db_field = 'ID';
				break;

			case 'user_id' :
				$db_field = 'user_id';
				break;

			case 'api_key' :
				$value    = sanitize_text_field( $value );
				$db_field = 'api_key';
				break;
			case 'api_secret' :
				$value    = sanitize_text_field( $value );
				$db_field = 'api_secret';
				break;
			default :
				return false;
		}

		if ( ! $key = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {
			return false;
		}

		return $key;

	}

	/**
	 * Check if a key is valid (and, optionally, active)
	 *
	 * @param string $api_key      API key to check.
	 * @param bool   $check_active Whether or not to check if the key is active.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function is_valid_key( $api_key, $check_active = true ) {

		$key = $this->get_key_by( 'api_key', $api_key );

		if ( empty( $key ) ) {
			return false;
		}

		if ( $check_active && 1 !== intval( $key->active ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Retrieve API keys from the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false
	 */
	public function get_keys( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'ID',
			'order'   => 'DESC',
			'fields'  => 'all',
			'user_id' => null,
			'status'  => null // null for all, 'active' for active only, 'inactive' for inactive
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// By user ID
		if ( ! empty( $args['user_id'] ) ) {

			if ( is_array( $args['user_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['user_id'] ) );
			} else {
				$ids = intval( $args['user_id'] );
			}

			$where .= " AND user_id IN( {$ids} ) ";

		}

		// By status
		if ( 'active' == $args['status'] ) {
			$where .= " AND active = 1";
		} elseif ( 'inactive' == $args['status'] ) {
			$where .= " AND active = 0";
		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'ID' : $args['orderby'];

		// Sort out the selection fields.
		$select_this = '*';
		if ( 'all' != $args['fields'] && array_key_exists( $args['fields'], $this->get_columns() ) ) {
			$select_this = esc_sql( $args['fields'] );
		}

		$cache_key = md5( 'nml_api_keys_' . serialize( $args ) );

		$api_keys = wp_cache_get( $cache_key, 'api_keys' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $api_keys ) {
			$query = $wpdb->prepare( "SELECT $select_this FROM  $this->table_name $join $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			if ( 'all' == $args['fields'] ) {
				$api_keys = $wpdb->get_results( $query );
			} else {
				$api_keys = $wpdb->get_col( $query );
			}
			wp_cache_set( $cache_key, $api_keys, 'api_keys', 3600 );
		}

		return $api_keys;

	}

	/**
	 * Count the API keys in the database
	 *
	 * @param array $args Array of arguments to override the defaults.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'user_id' => null,
			'status'  => null // null for all, 'active' for active only, 'inactive' for inactive
		);

		$args = wp_parse_args( $args, $defaults );

		$join  = '';
		$where = ' WHERE 1=1 ';

		// By user ID
		if ( ! empty( $args['user_id'] ) ) {

			if ( is_array( $args['user_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['user_id'] ) );
			} else {
				$ids = intval( $args['user_id'] );
			}

			$where .= " AND user_id IN( {$ids} ) ";

		}

		// By status
		if ( 'active' == $args['status'] ) {
			$where .= " AND active = 1";
		} elseif ( 'inactive' == $args['status'] ) {
			$where .= " AND active = 0";
		}

		$cache_key = md5( 'nml_api_keys_count_' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'api_keys' );

		if ( false === $count ) {
			$query = "SELECT COUNT({$this->primary_key}) FROM  $this->table_name $join $where";
			$count = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $count, 'api_keys', 3600 );
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
		user_id bigint(20) NOT NULL,
		api_key char(32) NOT NULL,
		api_secret char(32) NOT NULL,
		active tinyint(1) NOT NULL,
		last_updated datetime NOT NULL,
		PRIMARY KEY (ID),
		KEY user_id (user_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}