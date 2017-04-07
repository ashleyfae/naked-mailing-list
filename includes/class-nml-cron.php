<?php

/**
 * Cron
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
 * Class NML_Cron
 *
 * Handles scheduled events.
 *
 * @since 1.0
 */
class NML_Cron {

	/**
	 * NML_Cron constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( 'wp', array( $this, 'schedule_events' ) );

	}

	/**
	 * Register new cron schedules
	 *
	 * @param array $schedules
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function add_schedules( $schedules = array() ) {
		// Add once per minute.
		$schedules['once_per_minute'] = array(
			'interval' => 60,
			'display'  => __( 'Every Minute', 'naked-mailing-list' )
		);

		return $schedules;
	}

	/**
	 * Schedule events
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function schedule_events() {
		$this->every_minute_events();
		$this->daily_events();
	}

	/**
	 * Events run every minute
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function every_minute_events() {

		if ( ! wp_next_scheduled( 'nml_every_minute_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'once_per_minute', 'nml_every_minute_scheduled_events' );
		}

	}

	/**
	 * Schedule daily events
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function daily_events() {

		if ( ! wp_next_scheduled( 'nml_daily_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'daily', 'nml_daily_scheduled_events' );
		}

	}

}

new NML_Cron();