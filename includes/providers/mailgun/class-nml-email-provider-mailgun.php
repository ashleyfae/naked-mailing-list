<?php

/**
 * Email Provider: MailGun
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
 * Class NML_Email_Provider_MailGun
 *
 * @since 1.0
 */
class NML_Email_Provider_MailGun extends NML_Email {

	/**
	 * Domain name to use for sending
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $domain_name;

	/**
	 * API key
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $api_key;

	/**
	 * Whether or not test mode is enabled
	 *
	 * @var bool
	 * @protected
	 * @since 1.0
	 */
	protected $test_mode;

	/**
	 * Initialize
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {

		$this->domain_name = nml_get_option( 'mailgun_domain' );
		$this->api_key     = nml_get_option( 'mailgun_api_key' );
		$this->test_mode   = nml_get_option( 'mailgun_test_mode' );

	}

	/**
	 * Send the fully formatted email to the provider API
	 *
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function send_to_provider( $subject, $message ) {

		if ( empty( $this->domain_name ) || empty( $this->api_key ) ) {
			return false;
		}

		$body = array(
			'from'                => "{$this->from_name} <{$this->from_address}>",
			'to'                  => $this->parse_emails(),
			'subject'             => $subject,
			'html'                => $message,
			'text'                => wp_strip_all_tags( $message ),
			'recipient-variables' => $this->parse_recipient_variables()
		);

		if ( ! empty( $this->newsletter->ID ) ) {
			$body['o:campaign'] = absint( $this->newsletter->ID );
		}

		if ( $this->test_mode ) {
			$body['o:testmode'] = 'yes';
		}

		$data = array(
			'body'    => $body,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( "api:{$this->api_key}" )
			)
		);

		$url = "https://api.mailgun.net/v2/{$this->domain_name}/messages";

		$response = wp_remote_post( $url, $data );

		if ( is_wp_error( $response ) ) {
			error_log( sprintf( 'MailGun invalid response: %s', var_export( $response, true ) ) );

			return false;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			error_log( sprintf( 'MailGun invalid response code: %s', var_export( wp_remote_retrieve_response_code( $response ), true ) ) );

			return false;
		}

		return true;

	}

	/**
	 * Parse recipient variables
	 *
	 * @access private
	 * @since  1.0
	 * @return string JSON array of variables.
	 */
	private function parse_recipient_variables() {
		$recipient_vars = array();

		if ( is_array( $this->recipients ) ) {
			foreach ( $this->recipients as $sub ) {
				if ( is_object( $sub ) ) {
					$recipient_vars[ $sub->email ] = (array) $sub;
				} else {
					$recipient_vars[ $sub ] = $sub;
				}
			}
		}

		return json_encode( $recipient_vars );
	}

}