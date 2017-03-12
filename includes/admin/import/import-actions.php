<?php
/**
 * Import Actions
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
 * Add hook for registering batch importers
 *
 * @since 1.0
 * @return void
 */
function nml_register_batch_importers() {
	if ( is_admin() ) {
		do_action( 'nml_register_batch_importer' );
	}
}

add_action( 'plugins_loaded', 'nml_register_batch_importers' );

/**
 * Register the subscribers batch importer
 *
 * @since 1.0
 * @return void
 */
function nml_register_subscribers_batch_import() {
	add_action( 'nml_batch_import_class_include', 'nml_include_subscribers_batch_import_class', 10 );
}

add_action( 'nml_register_batch_importer', 'nml_register_subscribers_batch_import' );

/**
 * Loads the subscribers batch process if needed
 *
 * @param string $class The class being requested to run for the batch import.
 *
 * @since 1.0
 * @return void
 */
function nml_include_subscribers_batch_import_class( $class ) {
	if ( 'NML_Batch_Import_Subscribers' === $class ) {
		require_once NML_PLUGIN_DIR . 'includes/admin/import/class-nml-batch-import-subscribers.php';
	}
}