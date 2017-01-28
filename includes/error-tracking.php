<?php
/**
 * Error Tracking
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
 * Error Message Logging
 *
 * Creates a new instance of WP_Error for our error messages.
 *
 * @return WP_Error
 */
function nml_errors() {
	static $wp_error;

	return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
}

/**
 * Print Errors
 *
 * Prints all stored errors.
 *
 * @uses  nml_get_errors()
 * @uses  nml_clear_errors()
 *
 * @since 1.0
 * @return void
 */
function nml_print_errors() {

	$errors = nml_get_errors();
	if ( $errors ) {
		$classes = apply_filters( 'nml_error_class', array(
			'nml_errors'
		) );
		echo '<div class="' . implode( ' ', $classes ) . '">';
		// Loop error codes and display errors
		foreach ( $errors as $error_id => $error ) {
			echo '<p class="nml-error" id="nml-error-' . $error_id . '"><strong>' . __( 'Error', 'naked-mailing-list' ) . '</strong>: ' . $error . '</p>';
		}
		echo '</div>';
		nml_clear_errors();
	}

}

/**
 * Get Errors
 *
 * @since 1.0
 * @return array
 */
function nml_get_errors() {
	return nml_errors()->errors;
}

/**
 * Set Error
 *
 * @param string|int $error_id      ID of the error.
 * @param string     $error_message Error message.
 *
 * @since 1.0
 * @return void
 */
function nml_set_error( $error_id, $error_message ) {
	nml_errors()->add( $error_id, $error_message );
}

/**
 * Remove Error
 *
 * @param string|int $error_id ID of the error to remove.
 *
 * @since 1.0
 * @return void
 */
function nml_unset_error( $error_id ) {
	nml_errors()->remove( $error_id );
}

/**
 * Clear All Errors
 *
 * @uses  nml_unset_error()
 *
 * @since 1.0
 * @return void
 */
function nml_clear_errors() {
	$codes = nml_errors()->get_error_codes();

	if ( $codes && is_array( $codes ) ) {
		foreach ( $codes as $code ) {
			nml_unset_error( $code );
		}
	}
}