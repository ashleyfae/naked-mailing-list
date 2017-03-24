<?php

/**
 * Subscriber Import Class
 *
 * This class handles importing subscribers.
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
 * Class NML_Batch_Import_Subscribers
 *
 * @since 1.0
 */
class NML_Batch_Import_Subscribers extends NML_Batch_Import {

	/**
	 * Set up our import config
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {

		// Set up default field map values.
		$this->field_mapping = array(
			'email'        => '',
			'first_name'   => '',
			'last_name'    => '',
			'status'       => 'pending',
			'signup_date'  => gmdate( 'Y-m-d H:i:s' ),
			'confirm_date' => null,
			'ip'           => '',
			'referer'      => 'import',
			'form_name'    => '',
			'email_count'  => 0,
			'notes'        => '',
			'lists'        => array(),
			'tags'         => array()
		);

	}

	/**
	 * Process a step
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function process_step() {

		$more = false;

		if ( ! $this->can_import() ) {
			wp_die( __( 'You do not have permission to import data.', 'naked-mailing-list' ) );
		}

		// Remove actions to ensure they don't fire when creating subscribers.
		remove_action( 'nml_subscriber_set_pending', 'nml_send_subscriber_confirmation', 10 );

		$i      = 1;
		$offset = $this->step > 1 ? ( $this->per_step * ( $this->step - 1 ) ) : 0;

		if ( $offset > $this->total ) {
			$this->done = true;
		}

		if ( ! $this->done && $this->csv->data ) {

			$more = true;

			foreach ( $this->csv->data as $key => $row ) {

				// Skip all rows until we pass our offset.
				if ( $key + 1 <= $offset ) {
					continue;
				}

				// Done with this batch.
				if ( $i > $this->per_step ) {
					break;
				}

				// Import subscriber.
				$this->create_subscriber( $row );

				$i ++;

			}

		}

		return $more;

	}

	/**
	 * Create or update a subscriber from a CSV row
	 *
	 * @param array $row
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function create_subscriber( $row = array() ) {

		$args = array();

		foreach ( $this->field_mapping as $db_key => $import_key ) {
			if ( ! empty( $row[ $import_key ] ) ) {
				$args[ $db_key ] = $row[ $import_key ];
			}
		}

		nml_log( sprintf( __( 'Import: Started importing subscriber. Found args: %s', 'naked-mailing-list' ), var_export( $args, true ) ) );

		// Email is required.
		if ( empty( $args['email'] ) ) {
			nml_log( __( 'Import: Skipping import row - no email provided.', 'naked-mailing-list' ) );

			return;
		}

		// Fix statuses.
		if ( ! empty( $args['status'] ) ) {

			$status = strtolower( $args['status'] );

			if ( in_array( $status, array( 'unconfirmed', 'pending' ) ) ) {
				$args['status'] = 'pending';
			} elseif ( in_array( $status, array( 'unsubscribed', 'inactive' ) ) ) {
				$args['status'] = 'unsubscribed';
			} elseif ( in_array( $status, array( 'active', 'subscribed' ) ) ) {
				$args['status'] = 'subscribed';
			} elseif ( in_array( $status, array( 'bounced' ) ) ) {
				$args['status'] = 'bounced';
			}

		}

		// Fix the date.
		if ( ! empty( $args['signup_date'] ) ) {
			$timestamp = strtotime( $args['signup_date'] );

			if ( empty( $timestamp ) ) {
				$timestamp = $this->convert_date( $args['signup_date'] );
			}

			if ( ! empty( $timestamp ) ) {
				$date = gmdate( 'Y-m-d H:i:s', $timestamp );
			} else {
				$date = false;
			}

			if ( ! empty( $date ) ) {
				$args['signup_date'] = $date;
			} else {
				unset( $args['signup_date'] ); // will default to today
			}
		}

		$obj = naked_mailing_list()->subscribers->get_subscriber_by( 'email', $args['email'] );

		if ( ! empty( $obj ) && is_object( $obj ) ) {

			// Email already exists - let's update it.

			// First, unset any empty fields.
			$data_to_update = array();

			foreach ( $args as $key => $value ) {
				if ( ! empty( $value ) ) {
					$data_to_update[ $key ] = $value;
				}
			}

			$subscriber = new NML_Subscriber( $obj->ID );
			$subscriber->update( $data_to_update );

			nml_log( sprintf( __( 'Import: Updating subscriber %d (email: %s).', 'naked-mailing-list' ), $subscriber->ID, $subscriber->email ) );

		} else {

			// Create a new subscriber.

			// Maybe populate referer.
			if ( empty( $args['referer'] ) ) {
				$args['referer'] = 'import';
			}

			$subscriber = new NML_Subscriber();
			$subscriber->create( $args );

			nml_log( sprintf( __( 'Import: Created new subscriber %d (email: %s).', 'naked-mailing-list' ), $subscriber->ID, $subscriber->email ) );

		}

		// Back-date the activity log.
		$logs = naked_mailing_list()->activity->get_activity( array(
			'number'        => 1,
			'subscriber_id' => $subscriber->ID,
			'type'          => 'new_subscriber'
		) );

		if ( $logs && is_array( $logs ) && array_key_exists( 0, $logs ) ) {
			$log = $logs[0];

			naked_mailing_list()->activity->update( $log->ID, array(
				'date' => $subscriber->signup_date
			) );
		}

	}

	/**
	 * Check date format and conver to unix timestamp.
	 *
	 * @param string $date_string Date string to convert.
	 *
	 * @access public
	 * @since  1.0
	 * @return string|false
	 */
	public function convert_date( $date_string ) {

		// Test for 'd/m/Y G'
		$new_date = DateTime::createFromFormat( 'd/m/Y H:i', $date_string );
		if ( $new_date && $new_date->format( 'd/m/Y H:i' ) == $date_string ) {
			return $new_date->format( 'U' );
		}

		return false;

	}

	/**
	 * Retrieve the URL to the Subscribers list table
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_list_table_url() {
		return nml_get_admin_page_subscribers();
	}

	/**
	 * Retrieve Subscriber label
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_import_type_label() {
		return __( 'Subscribers', 'naked-mailing-list' );
	}

}