<?php

/**
 * Base Batch Export Class
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
 * Class NML_Batch_Export
 *
 * @since 1.0
 */
class NML_Batch_Export {

	/**
	 * Contents of the file being exported
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $file;

	/**
	 * Name of the file the export data is being stored in.
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $filename;

	/**
	 * The file type, probably .csv
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $filetype;

	/**
	 * The current step being processed
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $step;

	/**
	 * Number to process per step
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_step = 20;

	/**
	 * Type of export
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $export_type;

	/**
	 * Whether or not the export file is writable
	 *
	 * @var bool
	 * @access public
	 * @since  1.0
	 */
	public $is_writable = true;

	/**
	 * Whether or not the export file is empty
	 *
	 * @var bool
	 * @access public
	 * @since  1.0
	 */
	public $is_empty = false;

	/**
	 * Whether or not the export file is done
	 *
	 * @var bool
	 * @access public
	 * @since  1.0
	 */
	public $done = false;

	/**
	 * CW_Batch_Export constructor.
	 *
	 * @param int $_step Step to export.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $_step = 1 ) {

		$upload_dir     = wp_upload_dir();
		$this->filetype = '.csv';
		$this->filename = 'nml-export-' . $this->export_type . $this->filetype;
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

		$this->step = $_step;
		$this->done = false;

	}

	/**
	 * Whether or not the user can export
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function can_export() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Set the export headers
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function headers() {
		ignore_user_abort( true );


		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=nml-export-' . $this->export_type . '-' . date( 'm-d-Y' ) . '.csv' );
		header( "Expires: 0" );
	}

	/**
	 * Process a step
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'naked-mailing-list' ), __( 'Error', 'naked-mailing-list' ), array( 'response' => 403 ) );
		}

		if ( $this->step < 2 ) {

			// Make sure we start with a fresh file on step 1
			@unlink( $this->file );
			$this->print_csv_cols();
		}

		$rows = $this->print_csv_rows();

		if ( $rows ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Set the CSV columns
	 *
	 * These are just examples.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function csv_cols() {
		$cols = array(
			'ID'          => __( 'ID', 'naked-mailing-list' ),
			'description' => __( 'Description', 'naked-mailing-list' )
		);

		return $cols;
	}

	/**
	 * Retrieve the CSV columns
	 *
	 * @access public
	 * @since  1.0
	 * @return array $cols Array of the columns
	 */
	public function get_csv_cols() {
		return $this->csv_cols();
	}

	/**
	 * Output the CSV columns
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function print_csv_cols() {

		$col_data = '';
		$cols     = $this->get_csv_cols();
		$i        = 1;
		foreach ( $cols as $col_id => $column ) {
			$col_data .= '"' . addslashes( $column ) . '"';
			$col_data .= $i == count( $cols ) ? '' : ',';
			$i ++;
		}
		$col_data .= "\r\n";

		$this->stash_step_data( $col_data );

		return $col_data;

	}

	/**
	 * Print the CSV rows for the current step
	 *
	 * @access public
	 * @since  1.0
	 * @return string|false
	 */
	public function print_csv_rows() {

		$row_data = '';
		$data     = $this->get_data();
		$cols     = $this->get_csv_cols();

		if ( $data ) {

			// Output each row
			foreach ( $data as $row ) {
				$i = 1;
				foreach ( $row as $col_id => $column ) {
					// Make sure the column is valid
					if ( array_key_exists( $col_id, $cols ) ) {
						$row_data .= '"' . addslashes( preg_replace( "/\"/", "'", $column ) ) . '"';
						$row_data .= $i == count( $cols ) ? '' : ',';
						$i ++;
					}
				}
				$row_data .= "\r\n";
			}

			$this->stash_step_data( $row_data );

			return $row_data;
		}

		return false;
	}

	/**
	 * Get the data being exported
	 *
	 * @access public
	 * @since  1.0
	 * @return array $data Data for Export
	 */
	public function get_data() {
		// Just a sample data array
		$data = array(
			0 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			),
			1 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			)
		);

		return $data;
	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @access public
	 * @since  1.0
	 * @return int|float
	 */
	public function get_percentage_complete() {
		return 100;
	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	}

	/**
	 * Append data to export file
	 *
	 * @param $data string The data to add to the file
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function stash_step_data( $data = '' ) {

		$file = $this->get_file();
		$file .= $data;
		@file_put_contents( $this->file, $file );

		// If we have no rows after this step, mark it as an empty export
		$file_rows    = file( $this->file, FILE_SKIP_EMPTY_LINES );
		$default_cols = $this->get_csv_cols();
		$default_cols = empty( $default_cols ) ? 0 : 1;

		$this->is_empty = count( $file_rows ) == $default_cols ? true : false;

	}

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		$file = $this->get_file();

		@unlink( $this->file );

		echo $file;

		die();
	}

	/**
	 * Set the properties specific to the export
	 *
	 * @param array $request The Form Data passed into the batch processing
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_properties( $request ) {
	}

	/**
	 * Allow for prefetching of data for the remainder of the exporter
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function pre_fetch() {
	}

}