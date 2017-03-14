<?php
/**
 * Graph Functions
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
 * Render reports page
 *
 * @since 1.0
 * @return void
 */
function nml_reports_page() {
	?>
	<div class="wrap">
		<h1><?php _e( 'Reports', 'naked-mailing-list' ); ?></h1>
		<div class="metabox-holder">
			<div class="postbox">
				<h3><?php _e( 'New Signups', 'naked-mailing-list' ); ?></h3>
				<div class="inside">
					<?php do_action( 'nml_reports_new_signups' ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render date selection fields
 *
 * @since 1.0
 * @return void
 */
function nml_reports_date_selection() {

	$date_options = apply_filters( 'nml_reports_date_options', array(
		'today'        => __( 'Today', 'naked-mailing-list' ),
		'yesterday'    => __( 'Yesterday', 'naked-mailing-list' ),
		'this_week'    => __( 'This Week', 'naked-mailing-list' ),
		'last_week'    => __( 'Last Week', 'naked-mailing-list' ),
		'this_month'   => __( 'This Month', 'naked-mailing-list' ),
		'last_month'   => __( 'Last Month', 'naked-mailing-list' ),
		'this_quarter' => __( 'This Quarter', 'naked-mailing-list' ),
		'last_quarter' => __( 'Last Quarter', 'naked-mailing-list' ),
		'this_year'    => __( 'This Year', 'naked-mailing-list' ),
		'last_year'    => __( 'Last Year', 'naked-mailing-list' ),
		'other'        => __( 'Custom', 'naked-mailing-list' )
	) );

	$dates   = nml_get_report_dates();
	$display = $dates['range'] == 'other' ? '' : 'style="display:none;"';

	if ( empty( $dates['day_end'] ) ) {
		$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, date( 'n' ), date( 'Y' ) );
	}

	?>
	<form id="nml-reports-filter" method="POST">
		<div class="tablenav top">
			<div class=" actions">
				<label for="nml-graphs-date-options" class="screen-reader-text"><?php _e( 'Select a date range', 'naked-mailing-list' ); ?></label>
				<select id="nml-graphs-date-options" name="range">
					<?php foreach ( $date_options as $key => $option ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $dates['range'] ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>

				<div id="nml-date-range-options" <?php echo $display; ?>>
					<span><?php _e( 'From', 'naked-mailing-list' ); ?>&nbsp;</span>
					<select id="nml-graphs-month-start" name="m_start">
						<?php for ( $i = 1; $i <= 12; $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_start'] ); ?>><?php echo nml_month_num_to_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="nml-graphs-day-start" name="day">
						<?php for ( $i = 1; $i <= 31; $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<select id="nml-graphs-year-start" name="year">
						<?php for ( $i = 2007; $i <= date( 'Y' ); $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<span><?php _e( 'To', 'naked-mailing-list' ); ?>&nbsp;</span>
					<select id="nml-graphs-month-end" name="m_end">
						<?php for ( $i = 1; $i <= 12; $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_end'] ); ?>><?php echo nml_month_num_to_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="nml-graphs-day-end" name="day_end">
						<?php for ( $i = 1; $i <= 31; $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day_end'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<select id="nml-graphs-year-end" name="year_end">
						<?php for ( $i = 2007; $i <= date( 'Y' ); $i ++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year_end'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
				</div>

				<div class="nml-graph-filter-submit graph-option-section">
					<?php wp_nonce_field( 'nnl_get_signups_data', 'nnl_get_signups_data_nonce' ); ?>
					<input type="submit" class="button-secondary" value="<?php _e( 'Filter', 'naked-mailing-list' ); ?>"/>
				</div>
			</div>
		</div>
	</form>
	<?php

}

add_action( 'nml_reports_new_signups', 'nml_reports_date_selection' );

/**
 * Show report graphs
 *
 * Actual graph is loaded via ajax.
 * @see   nml_reports_get_signups_data_ajax()
 *
 * @since 1.0
 * @return void
 */
function nml_reports_graph() {
	?>
	<div id="nml-graph-wrapper">
	<canvas id="nml-signups-graph"></canvas>
	</div>

	<p id="nml-reports-total-signups">
		<strong><?php _e( 'Total signups in this period:', 'naked-mailing-list' ); ?></strong> <span></span>
	</p>
	<?php
}

add_action( 'nml_reports_new_signups', 'nml_reports_graph' );

/**
 * Ajax CB: Get Data
 *
 * @uses  nml_get_signups_reports_data()
 *
 * @since 1.0
 * @return void
 */
function nml_reports_get_signups_data_ajax() {

	check_ajax_referer( 'nnl_get_signups_data', 'nonce' );

	wp_send_json_success( nml_get_signups_reports_data() );

	exit;

}

add_action( 'wp_ajax_nml_reports_get_signups_data', 'nml_reports_get_signups_data_ajax' );

/**
 * Ajax CB: Update Data
 *
 * @uses  nml_get_signups_reports_data()
 *
 * @since 1.0
 * @return void
 */
function nml_reports_update_data_ajax() {

	check_ajax_referer( 'nnl_get_signups_data', 'nonce' );

	if ( ! isset( $_POST['date'] ) ) {
		wp_die( __( "Error: Can't find date range data.", 'naked-mailing-list' ) );
	}

	parse_str( $_POST['date'], $date );

	if ( ! is_array( $date ) ) {
		wp_die( __( "Error: Form data in unexpected format.", 'naked-mailing-list' ) );
	}

	foreach ( $date as $key => $value ) {
		$_POST[ $key ] = wp_strip_all_tags( $value );
	}

	wp_send_json_success( nml_get_signups_reports_data() );

	exit;

}

add_action( 'wp_ajax_nml_reports_update_data_ajax', 'nml_reports_update_data_ajax' );

/**
 * Get data for signups
 *
 * @since 1.0
 * @return array
 */
function nml_get_signups_reports_data() {

	$dates = nml_get_report_dates();

	// Determine graph options
	switch ( $dates['range'] ) {
		case 'today' :
		case 'yesterday' :
			$day_by_day = true;
			break;
		case 'last_year' :
			$day_by_day = false;
			break;
		case 'this_year' :
			$day_by_day = false;
			break;
		case 'last_quarter' :
			$day_by_day = false;
			break;
		case 'this_quarter' :
			$day_by_day = false;
			break;
		case 'other' :
			if ( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] ) {
				$day_by_day = false;
			} else {
				$day_by_day = true;
			}
			break;
		default:
			$day_by_day = true;
			break;
	}

	$signup_totals = 0;
	$labels        = array();
	$signup_data   = array();

	if ( $dates['range'] == 'today' || $dates['range'] == 'yesterday' ) {

		// Hour by hour
		$month  = $dates['m_start'];
		$hour   = 1;
		$minute = 0;
		$second = 0;

		while ( $hour <= 23 ) {

			if ( $hour == 23 ) {
				$minute = $second = 59;
			}

			$date     = mktime( $hour, $minute, $second, $month, $dates['day'], $dates['year'] );
			$date_end = mktime( $hour + 1, $minute, $second, $month, $dates['day'], $dates['year'] );

			$query_args = array( 'type' => 'new_subscriber', 'date' => array( 'start' => $date, 'end' => $date_end ) );
			$signups    = naked_mailing_list()->activity->count( $query_args, false );
			$signup_totals += $signups;

			$labels[]      = date_i18n( 'g:iA', $date );
			$signup_data[] = $signups;

			$hour ++;
		}

	} elseif ( $dates['range'] == 'this_week' || $dates['range'] == 'last_week' ) {

		$num_of_days = cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] );

		$report_dates = array();
		$i            = 0;

		while ( $i <= 6 ) {

			if ( ( $dates['day'] + $i ) <= $num_of_days ) {
				$report_dates[ $i ] = array(
					'day'   => (string) ( $dates['day'] + $i ),
					'month' => $dates['m_start'],
					'year'  => $dates['year'],
				);
			} else {
				$report_dates[ $i ] = array(
					'day'   => (string) $i,
					'month' => $dates['m_end'],
					'year'  => $dates['year_end'],
				);
			}

			$i ++;
		}

		foreach ( $report_dates as $report_date ) {

			$date     = mktime( 0, 0, 0, $report_date['month'], $report_date['day'], $report_date['year'] );
			$date_end = mktime( 23, 59, 59, $report_date['month'], $report_date['day'], $report_date['year'] );

			$query_args = array( 'type' => 'new_subscriber', 'date' => array( 'start' => $date, 'end' => $date_end ) );
			$signups    = naked_mailing_list()->activity->count( $query_args );
			$signup_totals += $signups;

			$labels[]      = date_i18n( 'j M', $date );
			$signup_data[] = $signups;

		}

	} else {

		$y         = $dates['year'];
		$temp_data = array();

		while ( $y <= $dates['year_end'] ) {

			if ( $dates['year'] == $dates['year_end'] ) {
				$month_start = $dates['m_start'];
				$month_end   = $dates['m_end'];
			} elseif ( $y == $dates['year'] ) {
				$month_start = $dates['m_start'];
				$month_end   = 12;
			} elseif ( $y == $dates['year_end'] ) {
				$month_start = 1;
				$month_end   = $dates['m_end'];
			} else {
				$month_start = 1;
				$month_end   = 12;
			}

			$i = $month_start;
			while ( $i <= $month_end ) {


				$d = $dates['day'];

				if ( $i == $month_end ) {

					$num_of_days = $dates['day_end'];

					if ( $month_start < $month_end ) {

						$d = 1;

					}

				} else {

					$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

				}

				while ( $d <= $num_of_days ) {

					$date     = mktime( 0, 0, 0, $i, $d, $y );
					$end_date = mktime( 23, 59, 59, $i, $d, $y );

					$query_args = array(
						'type' => 'new_subscriber',
						'date' => array( 'start' => $date, 'end' => $end_date )
					);
					$signups    = naked_mailing_list()->activity->count( $query_args );
					$signup_totals += $signups;

					$temp_data['signups'][ $y ][ $i ][ $d ] = $signups;

					$d ++;

				}

				$i ++;

			}

			$y ++;
		}

		$signup_data = array();

		// When using 2 months or smaller as the custom range, show each day individually on the graph
		if ( $day_by_day ) {

			foreach ( $temp_data['signups'] as $year => $months ) {
				foreach ( $months as $month => $dates ) {
					foreach ( $dates as $day => $signups ) {
						$date = mktime( 0, 0, 0, $month, $day, $year );

						$labels[]      = date_i18n( 'j M', $date );
						$signup_data[] = $signups;
					}

				}
			}

			// When showing more than 2 months of results, group them by month, by the first (except for the last month, group on the last day of the month selected)
		} else {

			foreach ( $temp_data['signups'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$signups = array_sum( $days );
					$date    = mktime( 0, 0, 0, $month, $consolidated_date, $year );

					$labels[]      = date_i18n( 'M Y', $date );
					$signup_data[] = $signups;

				}

			}

		}

	}

	$return = array(
		'labels' => $labels,
		'data'   => $signup_data,
		'total'  => $signup_totals
	);

	return apply_filters( 'nml_get_signup_graph_data', $return );

}

/**
 * Sets up the dates used to filter graph data
 *
 * Date sent via $_POST is read first and then modified (if needed) to match the
 * selected date-range (if any)
 *
 * Taken from Easy Digital Downloads.
 *
 * @since 1.0
 * @return array
 */
function nml_get_report_dates() {

	$dates = array();

	$current_time = current_time( 'timestamp' );

	$dates['range'] = isset( $_POST['range'] ) ? $_POST['range'] : 'this_month';

	if ( 'custom' !== $dates['range'] ) {
		$dates['year']     = isset( $_POST['year'] ) ? $_POST['year'] : date( 'Y' );
		$dates['year_end'] = isset( $_POST['year_end'] ) ? $_POST['year_end'] : date( 'Y' );
		$dates['m_start']  = isset( $_POST['m_start'] ) ? $_POST['m_start'] : 1;
		$dates['m_end']    = isset( $_POST['m_end'] ) ? $_POST['m_end'] : 12;
		$dates['day']      = isset( $_POST['day'] ) ? $_POST['day'] : 1;
		$dates['day_end']  = isset( $_POST['day_end'] ) ? $_POST['day_end'] : cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
	}

	// Modify dates based on predefined ranges
	switch ( $dates['range'] ) :

		case 'this_month' :
			$dates['m_start']  = date( 'n', $current_time );
			$dates['m_end']    = date( 'n', $current_time );
			$dates['day']      = 1;
			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year']     = date( 'Y' );
			$dates['year_end'] = date( 'Y' );
			break;

		case 'last_month' :
			if ( date( 'n' ) == 1 ) {
				$dates['m_start']  = 12;
				$dates['m_end']    = 12;
				$dates['year']     = date( 'Y', $current_time ) - 1;
				$dates['year_end'] = date( 'Y', $current_time ) - 1;
			} else {
				$dates['m_start']  = date( 'n' ) - 1;
				$dates['m_end']    = date( 'n' ) - 1;
				$dates['year_end'] = $dates['year'];
			}
			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			break;

		case 'today' :
			$dates['day']     = date( 'd', $current_time );
			$dates['m_start'] = date( 'n', $current_time );
			$dates['m_end']   = date( 'n', $current_time );
			$dates['year']    = date( 'Y', $current_time );
			break;

		case 'yesterday' :

			$year  = date( 'Y', $current_time );
			$month = date( 'n', $current_time );
			$day   = date( 'd', $current_time );

			if ( $month == 1 && $day == 1 ) {

				$year -= 1;
				$month = 12;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} elseif ( $month > 1 && $day == 1 ) {

				$month -= 1;
				$day = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} else {

				$day -= 1;

			}

			$dates['day']      = $day;
			$dates['m_start']  = $month;
			$dates['m_end']    = $month;
			$dates['year']     = $year;
			$dates['year_end'] = $year;
			break;

		case 'this_week' :
		case 'last_week' :
			$base_time = $dates['range'] === 'this_week' ? current_time( 'mysql' ) : date( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
			$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

			$dates['day']     = date( 'd', $start_end['start'] );
			$dates['m_start'] = date( 'n', $start_end['start'] );
			$dates['year']    = date( 'Y', $start_end['start'] );

			$dates['day_end']  = date( 'd', $start_end['end'] );
			$dates['m_end']    = date( 'n', $start_end['end'] );
			$dates['year_end'] = date( 'Y', $start_end['end'] );
			break;

		case 'this_quarter' :
			$month_now         = date( 'n', $current_time );
			$dates['year']     = date( 'Y', $current_time );
			$dates['year_end'] = $dates['year'];

			if ( $month_now <= 3 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 3;


			} else if ( $month_now <= 6 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 6;


			} else if ( $month_now <= 9 ) {

				$dates['m_start'] = 7;
				$dates['m_end']   = 9;

			} else {

				$dates['m_start'] = 10;
				$dates['m_end']   = 12;

			}

			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			break;

		case 'last_quarter' :
			$month_now = date( 'n' );

			if ( $month_now <= 3 ) {

				$dates['m_start'] = 10;
				$dates['m_end']   = 12;
				$dates['year']    = date( 'Y', $current_time ) - 1; // Previous year

			} else if ( $month_now <= 6 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 3;
				$dates['year']    = date( 'Y', $current_time );

			} else if ( $month_now <= 9 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 6;
				$dates['year']    = date( 'Y', $current_time );

			} else {

				$dates['m_start'] = 7;
				$dates['m_end']   = 9;
				$dates['year']    = date( 'Y', $current_time );

			}

			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year_end'] = $dates['year'];
			break;

		case 'this_year' :
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time );
			$dates['year_end'] = $dates['year'];
			break;

		case 'last_year' :
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time ) - 1;
			$dates['year_end'] = date( 'Y', $current_time ) - 1;
			break;

	endswitch;

	return apply_filters( 'nml_report_dates', $dates );

}