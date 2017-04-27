<?php
/**
 * Export Functions
 *
 * Process subscriber CSV export. Taken from Easy Digital Downloads.
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
 * Do ajax export
 *
 * @since 1.0
 * @return void
 */
function nml_do_ajax_export() {

	require_once NML_PLUGIN_DIR . 'includes/admin/export/class-nml-batch-export.php';
	require_once NML_PLUGIN_DIR . 'includes/admin/export/class-nml-batch-export-subscribers.php';

	$step   = absint( $_POST['step'] );
	$export = new NML_Batch_Export_Subscribers( $step );

	error_log( 'test' );

	if ( ! isset( $export ) ) {
		wp_die( '-1' );
	}

	if ( ! $export->can_export() ) {
		wp_die( '-1' );
	}

	if ( ! $export->is_writable ) {
		echo json_encode( array(
			'error'   => true,
			'message' => __( 'Export location or file not writable', 'naked-mailing-list' )
		) );
		exit;
	}

	parse_str( $_POST['form'], $form );
	$form = (array) $form;

	$export->set_properties( $form );

	$ret        = $export->process_step();
	$percentage = $export->get_percentage_complete();

	if ( $ret ) {

		$step += 1;
		echo json_encode( array( 'step' => $step, 'percentage' => $percentage ) );
		exit;

	} elseif ( true === $export->is_empty ) {

		echo json_encode( array(
			'error'   => true,
			'message' => __( 'No data found for export parameters', 'naked-mailing-list' )
		) );
		exit;

	} elseif ( true === $export->done ) {

		$message = ! empty( $export->message ) ? $export->message : __( 'Batch Processing Complete', 'naked-mailing-list' );
		echo json_encode( array( 'success' => true, 'message' => $message ) );
		exit;

	} else {

		$args = array_merge( $form, array(
			'step'       => $step,
			'nonce'      => wp_create_nonce( 'nml-batch-export' ),
			'nml_action' => 'download_batch_export',
		) );

		$download_url = add_query_arg( $args, admin_url() );

		echo json_encode( array( 'step' => 'done', 'url' => $download_url ) );
		exit;

	}

}

add_action( 'wp_ajax_nml_do_ajax_export', 'nml_do_ajax_export' );

/**
 * Download the export file
 *
 * @since 1.0
 * @return void
 */
function nml_download_batch_export() {

	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'nml-batch-export' ) ) {
		wp_die( __( 'Nonce verification failed', 'naked-mailing-list' ), __( 'Error', 'naked-mailing-list' ), array( 'response' => 403 ) );
	}

	require_once NML_PLUGIN_DIR . 'includes/admin/export/class-nml-batch-export.php';
	require_once NML_PLUGIN_DIR . 'includes/admin/export/class-nml-batch-export-subscribers.php';

	$export = new NML_Batch_Export_Subscribers();

	if ( ! isset( $export ) || empty( $export ) ) {
		wp_die( __( 'Invalid export file.', 'naked-mailng-list' ) );
	}

	$export->export();

}

add_action( 'nml_action_download_batch_export', 'nml_download_batch_export' );