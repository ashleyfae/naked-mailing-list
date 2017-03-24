<?php

/**
 * Batch Export Subscribers to CSV
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
 * Class NML_Batch_Export_Subscribers
 *
 * @since 1.0
 */
class NML_Batch_Export_Subscribers extends NML_Batch_Export {

	/**
	 * Type of export
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $export_type = 'subscribers';

	/**
	 * Subscriber status
	 *
	 * Only export subscribers with this status.
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $status = '';

	/**
	 * List ID
	 *
	 * Only export subscribers from this list.
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $list = 0;

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function csv_cols() {
		$cols = array(
			'ID'           => __( 'ID', 'naked-mailing-list' ),
			'email'        => __( 'Email', 'naked-mailing-list' ),
			'first_name'   => __( 'First Name', 'naked-mailing-list' ),
			'last_name'    => __( 'Last Name', 'naked-mailing-list' ),
			'status'       => __( 'Status', 'naked-mailing-list' ),
			'signup_date'  => __( 'Signup Date', 'naked-mailing-list' ),
			'confirm_date' => __( 'Confirm Date', 'naked-mailing-list' ),
			'ip'           => __( 'IP', 'naked-mailing-list' ),
			'referer'      => __( 'Referer', 'naked-mailing-list' ),
			'form_name'    => __( 'Signup Form', 'naked-mailing-list' ),
			'email_count'  => __( 'Email Count', 'naked-mailing-list' ),
			'notes'        => __( 'Notes', 'naked-mailing-list' ),
			'lists'        => __( 'Lists', 'naked-mailing-list' ),
			'tags'         => __( 'Tags', 'naked-mailing-list' )
		);

		return $cols;
	}

	/**
	 * Get the data being exported
	 *
	 * @access public
	 * @since  1.0
	 * @return array|false Data for Export, or false if none
	 */
	public function get_data() {

		$data = array();

		$args = array(
			'number'  => $this->per_step,
			'offset'  => ( $this->step - 1 ) * $this->per_step,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'status'  => $this->status,
			'list'    => $this->list
		);

		$subscribers = nml_get_subscribers( $args );

		if ( empty( $subscribers ) ) {
			return false;
		}

		foreach ( $subscribers as $subscriber ) {
			$row = array();

			foreach ( $subscriber as $key => $value ) {
				$row[ $key ] = $value;
			}

			// Lists
			$lists        = nml_get_object_lists( 'subscriber', $subscriber->ID, 'list', array( 'fields' => 'names' ) );
			$row['lists'] = ! empty( $lists ) ? implode( ', ', $lists ) : '';

			// Tags
			$tags        = nml_get_object_lists( 'subscriber', $subscriber->ID, 'tag', array( 'fields' => 'names' ) );
			$row['tags'] = ! empty( $tags ) ? implode( ', ', $tags ) : '';

			$data[] = $row;
		}

		return $data;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_percentage_complete() {

		$args = array(
			'number'  => - 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'status'  => $this->status,
			'list'    => $this->list,
			'fields'  => 'ID'
		);

		$subscribers = nml_get_subscribers( $args );
		$total       = (int) count( $subscribers );
		$percentage  = 100;

		if ( $total > 0 ) {
			$percentage = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;

	}

	/**
	 * Set the properties specific to this export
	 *
	 * @param array $request Array of properties.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_properties( $request ) {
		$this->status = isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';
		$this->list   = isset( $request['list'] ) ? sanitize_text_field( $request['list'] ) : '';
	}

}