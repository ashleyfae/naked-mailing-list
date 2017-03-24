<?php
/**
 * Email Functions
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
 * Load provider files
 *
 * @since 1.0
 * @return void
 */
function nml_load_provider_files() {
	$provider = nml_get_option( 'provider', 'mailgun' );

	if ( empty( $provider ) ) {
		return;
	}

	$main_file      = NML_PLUGIN_DIR . 'includes/providers/' . $provider . '/class-nml-email-provider-' . $provider . '.php';
	$functions_file = NML_PLUGIN_DIR . 'includes/providers/' . $provider . '/functions.php';

	if ( file_exists( $main_file ) ) {
		require_once $main_file;
	}

	if ( file_exists( $functions_file ) ) {
		require_once $functions_file;
	}
}

add_action( 'plugins_loaded', 'nml_load_provider_files' );

/**
 * Get all available email providers
 *
 * @since 1.0
 * @return array
 */
function nml_get_email_providers() {
	$providers = array(
		'mailgun' => array(
			'name'  => esc_html__( 'MailGun', 'naked-mailing-list' ),
			'class' => 'NML_Email_Provider_MailGun'
		)
	);

	/**
	 * Filters the list of available email providers.
	 *
	 * @param array $providers
	 *
	 * @since 1.0
	 */
	return apply_filters( 'nml_email_providers', $providers );
}

/**
 * Get available email providers
 *
 * Basically the same as nml_get_email_providers() but just includes
 * the ID and name rather than all details.
 *
 * @uses  nml_get_email_providers()
 *
 * @since 1.0
 * @return array
 */
function nml_get_available_email_providers() {
	$providers = nml_get_email_providers();
	$available = array();

	if ( empty( $providers ) || ! is_array( $providers ) ) {
		return array();
	}

	foreach ( $providers as $key => $provider ) {
		$available[ $key ] = $provider['name'];
	}

	return $available;
}

/**
 * Get chosen email provider
 *
 * @since 1.0
 * @return NML_Email
 */
function nml_get_email_provider() {
	$provider      = nml_get_option( 'provider', 'mailgun' );
	$all_providers = nml_get_email_providers();

	if ( array_key_exists( $provider, $all_providers ) ) {
		if ( class_exists( $all_providers[ $provider ]['class'] ) ) {
			return new $all_providers[ $provider ]['class'];
		}
	}

	return new NML_Email_Provider_MailGun();
}

/**
 * Get available email templates
 *
 * @since 1.0
 * @return array
 */
function nml_get_email_templates() {
	$templates = array(
		'default' => esc_html__( 'Default', 'naked-mailing-list' ),
		'none'    => esc_html__( 'No template, plain text only', 'naked-mailing-list' )
	);

	/**
	 * Filters the list of email template types.
	 *
	 * @param array $templates Available template types.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'nml_email_templates', $templates );
}