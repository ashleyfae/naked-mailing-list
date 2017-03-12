<?php
/**
 * Import Functions
 *
 * Functions used for importing data. Taken from Easy Digital Downloads.
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
 * Upload an import file with ajax
 *
 * @since 1.0
 * @return void
 */
function nml_do_ajax_import_file_upload() {

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	require_once NML_PLUGIN_DIR . 'includes/admin/import/class-nml-batch-import.php';

	if ( ! wp_verify_nonce( $_REQUEST['nml_ajax_import'], 'nml_ajax_import' ) ) {
		wp_send_json_error( array( 'error' => __( 'Nonce verification failed', 'naked-mailing-list' ) ) );
	}

	if ( empty( $_POST['nml-import-class'] ) ) {
		wp_send_json_error( array(
			'error'   => __( 'Missing import parameters. Import class must be specified.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	if ( empty( $_FILES['nml-import-file'] ) ) {
		wp_send_json_error( array(
			'error'   => __( 'Missing import file. Please provide an import file.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	$accepted_mime_types = array(
		'text/csv',
		'text/comma-separated-values',
		'text/plain',
		'text/anytext',
		'text/*',
		'text/plain',
		'text/anytext',
		'text/*',
		'application/csv',
		'application/excel',
		'application/vnd.ms-excel',
		'application/vnd.msexcel',
	);

	if ( empty( $_FILES['nml-import-file']['type'] ) || ! in_array( strtolower( $_FILES['nml-import-file']['type'] ), $accepted_mime_types ) ) {
		wp_send_json_error( array(
			'error'   => __( 'The file you uploaded does not appear to be a CSV file.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	if ( ! file_exists( $_FILES['nml-import-file']['tmp_name'] ) ) {
		wp_send_json_error( array(
			'error'   => __( 'Something went wrong during the upload process, please try again.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	// Let WordPress import the file. We will remove it after import is complete
	$import_file = wp_handle_upload( $_FILES['nml-import-file'], array( 'test_form' => false ) );

	if ( $import_file && empty( $import_file['error'] ) ) {

		do_action( 'nml_batch_import_class_include', $_POST['nml-import-class'] );

		$import = new $_POST['nml-import-class']( $import_file['file'] );

		if ( ! $import->can_import() ) {
			wp_send_json_error( array( 'error' => __( 'You do not have permission to import data', 'naked-mailing-list' ) ) );
		}

		wp_send_json_success( array(
			'form'      => $_POST,
			'class'     => $_POST['nml-import-class'],
			'upload'    => $import_file,
			'first_row' => $import->get_first_row(),
			'columns'   => $import->get_columns(),
			'nonce'     => wp_create_nonce( 'nml_ajax_import', 'nml_ajax_import' )
		) );

	} else {

		/**
		 * Error generated by _wp_handle_upload()
		 * @see _wp_handle_upload() in wp-admin/includes/file.php
		 */

		wp_send_json_error( array( 'error' => $import_file['error'] ) );

	}

	exit;

}

add_action( 'nml_upload_import_file', 'nml_do_ajax_import_file_upload' );

/**
 * Process batch imports via ajax
 *
 * @since 1.0
 * @return void
 */
function nml_do_ajax_import() {

	require_once NML_PLUGIN_DIR . 'includes/admin/import/class-nml-batch-import.php';

	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'nml_ajax_import' ) ) {
		wp_send_json_error( array( 'error' => __( 'Nonce verification failed', 'naked-mailing-list' ) ) );
	}

	if ( empty( $_REQUEST['class'] ) ) {
		wp_send_json_error( array(
			'error'   => __( 'Missing import parameters. Import class must be specified.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	if ( ! file_exists( $_REQUEST['upload']['file'] ) ) {
		wp_send_json_error( array(
			'error'   => __( 'Something went wrong during the upload process, please try again.', 'naked-mailing-list' ),
			'request' => $_REQUEST
		) );
	}

	do_action( 'nml_batch_import_class_include', $_REQUEST['class'] );

	$step   = absint( $_REQUEST['step'] );
	$class  = $_REQUEST['class'];
	$import = new $class( $_REQUEST['upload']['file'], $step );

	if ( ! $import->can_import() ) {
		wp_send_json_error( array( 'error' => __( 'You do not have permission to import data', 'naked-mailing-list' ) ) );
	}

	parse_str( $_REQUEST['mapping'], $map );

	$import->map_fields( $map['nml-import-field'] );

	$ret = $import->process_step( $step );

	$percentage = $import->get_percentage_complete();

	if ( $ret ) {

		$step += 1;
		wp_send_json_success( array(
			'step'       => $step,
			'percentage' => $percentage,
			'columns'    => $import->get_columns(),
			'mapping'    => $import->field_mapping,
			'total'      => $import->total
		) );

	} elseif ( true === $import->is_empty ) {

		wp_send_json_error( array(
			'error' => __( 'No data found for import parameters', 'naked-mailing-list' )
		) );

	} else {

		wp_send_json_success( array(
			'step'    => 'done',
			'message' => sprintf(
				__( 'Import complete! <a href="%s">View imported %s</a>.', 'easy-digital-downloads' ),
				$import->get_list_table_url(),
				$import->get_import_type_label()
			)
		) );

	}

}

add_action( 'wp_ajax_nml_do_ajax_import', 'nml_do_ajax_import' );