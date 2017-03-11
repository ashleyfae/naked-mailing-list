<?php
/**
 * MailGun Functions
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
 * Add section for MailGun
 *
 * @param array $sections
 *
 * @since 1.0
 * @return array
 */
function nml_mailgun_section( $sections ) {
	$sections['mailgun'] = esc_html__( 'MailGun', 'naked-mailing-list' );

	return $sections;
}

add_filter( 'nml_settings_sections_sending', 'nml_mailgun_section' );

/**
 * MailGun Settings
 *
 * @param array $sections
 *
 * @since 1.0
 * @return array
 */
function nml_mailgun_settings( $sections ) {
	$sections['mailgun'] = array(
		'mailgun_domain'    => array(
			'id'   => 'mailgun_domain',
			'name' => esc_html__( 'Domain Name', 'naked-mailing-list' ),
			'desc' => 'TK',
			'type' => 'text',
			'std'  => ''
		),
		'mailgun_api_key'   => array(
			'id'   => 'mailgun_api_key',
			'name' => esc_html__( 'API Key', 'naked-mailing-list' ),
			'desc' => 'TK',
			'type' => 'text',
			'std'  => ''
		),
		'mailgun_test_mode' => array(
			'id'   => 'mailgun_test_mode',
			'name' => esc_html__( 'Test Mode', 'naked-mailing-list' ),
			'type' => 'checkbox',
			'std'  => false
		),
	);

	return $sections;
}

add_filter( 'nml_settings_sending', 'nml_mailgun_settings' );