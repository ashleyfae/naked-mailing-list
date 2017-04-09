<?php

/**
 * Batch Import Class
 *
 * Base class for all import methods.
 *
 * Taken from Easy Digital Downloads.
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
 * Class NML_Batch_Import
 *
 * @since 1.0
 */
class NML_Batch_Import {

	/**
	 * The file being imported
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $file;

	/**
	 * The parsed CSV file being imported
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $csv;

	/**
	 * The total rows in the CSV file
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $total;

	/**
	 * The current step being processed
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $step;

	/**
	 * THe number of items to process per step
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_step = 20;

	/**
	 * The capability required to import data
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $capability_type = 'manage_options';

	/**
	 * Whether or not the import file is empty
	 *
	 * @var bool
	 * @access public
	 * @since  1.0
	 */
	public $is_empty = false;

	/**
	 * Map of CSV columns to database fields
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $field_mapping = array();

	/**
	 * NML_Batch_Import constructor.
	 *
	 * @param string $_file File to import.
	 * @param int    $_step Step to process.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $_file = '', $_step = 1 ) {

		if ( ! class_exists( 'parseCSV' ) ) {
			require_once NML_PLUGIN_DIR . 'includes/libraries/parsecsv.lib.php';
		}

		$this->step = $_step;
		$this->file = $_file;
		$this->done = false;
		$this->csv  = new parseCSV();
		$this->csv->auto( $this->file );
		$this->total = count( $this->csv->data );
		$this->init();

	}

	/**
	 * Initialize the updater. Runs after import file is loaded but before
	 * any processing is done.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Can we import?
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function can_import() {
		return (bool) apply_filters( 'nml_import_capability', current_user_can( $this->capability_type ) );
	}

	/**
	 * Get the CSV columns
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {

		return $this->csv->titles;

	}

	/**
	 * Get the first row of the CSV
	 *
	 * This is used for showing an example of what th eimport will look like.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_first_row() {

		return array_map( array( $this, 'trim_preview' ), current( $this->csv->data ) );

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
			wp_die( __( 'You do not have permissiont o import data.', 'naked-mailing-list' ) );
		}

		return $more;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @access public
	 * @since  1.0
	 * @return int|float
	 */
	public function get_percentage_complete() {

		$percentage = 100;

		if ( $this->total > 0 ) {
			$percentage = ( $this->step / $this->total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;

	}

	/**
	 * Map CSV columns to import fields
	 *
	 * @param array $import_fields
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function map_fields( $import_fields = array() ) {

		$this->field_mapping = $import_fields;

	}

	/**
	 * Retrieve the URL to the list table for the import data type
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_list_table_url() {

	}

	/**
	 * Retrieve the label for the import type. Example: Subscribers
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_import_type_label() {

	}

	/**
	 * Convert a string containing delimiters to an array
	 *
	 * @param string $str Input string to convert
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function str_to_array( $str = '' ) {

		$array = array();

		if ( is_array( $str ) ) {
			return array_map( 'trim', $str );
		}

		// Look for standard delimiters.
		if ( false !== strpos( $str, '|' ) ) {
			$delimiter = '|';
		} elseif ( false !== strpos( $str, ',' ) ) {
			$delimiter = ',';
		} elseif ( false !== strpos( $str, ';' ) ) {
			$delimiter = ';';
		} elseif ( false !== strpos( $str, '/' ) && ! filter_var( str_replace( ' ', '%20', $str ), FILTER_VALIDATE_URL ) ) {
			$delimiter = '/';
		}

		if ( ! empty( $delimiter ) ) {
			$array = (array) explode( $delimiter, $str );
		} else {
			$array[] = $str;
		}

		return array_map( 'trim', $array );

	}

	/**
	 * Trim a column value for a preview
	 *
	 * @param string $str Input string to trim down.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function trim_preview( $str = '' ) {

		if ( ! is_numeric( $str ) ) {

			$long = strlen( $str ) >= 30;
			$str  = substr( $str, 0, 30 );
			$str  = $long ? $str . '...' : $str;

		}

		return $str;

	}

	/**
	 * Set the properties specific to the import
	 *
	 * @param array $request The Form Data passed into the batch processing
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_properties( $request ) {
	}

}