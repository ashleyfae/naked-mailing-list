<?php

/**
 * Logging Class
 *
 * Used to create logs when debugging is enabled.
 *
 * Taken from AffiliateWP
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
 * Class NML_Logging
 *
 * @since 1.0
 */
class NML_Logging {

	/**
	 * Whether or not the log file is writable
	 *
	 * @var bool
	 * @access public
	 * @since  1.0
	 */
	public $is_writable = true;

	/**
	 * Name of the log file
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $filename = '';

	/**
	 * Full path to the log file
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $file = '';

	/**
	 * NML_Logging constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Setup the class properties
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {

		$upload_dir     = wp_upload_dir();
		$this->filename = 'nml-debug.log';
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

	}

	/**
	 * Retrieve the log data
	 *
	 * @uses   NML_Logging::get_file()
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_log() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 *
	 * @uses   NML_Logging::write_to_log()
	 *
	 * @param string $message Message to log.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function log( $message = '' ) {

		$message = date( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";
		$this->write_to_log( $message );

	}

	/**
	 * Retrieve the log file data
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
	 * Write the log message
	 *
	 * @param string $message Message to log.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	protected function write_to_log( $message = '' ) {

		$file = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );

	}

	/**
	 * Clear the log file
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function clear_log() {
		@unlink( $this->file );
	}

}