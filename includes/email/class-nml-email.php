<?php

/**
 * Email
 *
 * Handles building and sending emails.
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
 * Class NML_Email
 *
 * @since 1.0
 */
class NML_Email {

	/**
	 * Email subject
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $subject;

	/**
	 * Email message
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $message;

	/**
	 * From name
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $from_name;

	/**
	 * From address
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $from_address;

	/**
	 * The email template to use
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $template;

	/**
	 * Newsletter object
	 *
	 * @var NML_Newsletter
	 * @access protected
	 * @since  1.0
	 */
	protected $newsletter;

	/**
	 * Array of subscriber DB objects the message is being sent to
	 *
	 * @var array
	 * @access protected
	 * @since  1.0
	 */
	protected $recipients;

	/**
	 * NML_Email constructor.
	 *
	 * @param int $newsletter_id ID of the newsletter being sent.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $newsletter_id = 0 ) {
		$this->newsletter = new NML_Newsletter( $newsletter_id );

		$this->init();
	}

	/**
	 * Set a property
	 *
	 * @param string           $key
	 * @param string|int|mixed $value
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Initialize
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Set newsletter
	 *
	 * @param int|NML_Newsletter $newsletter_id Newsletter ID or object.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_newsletter( $newsletter ) {
		$this->newsletter = is_a( $newsletter, 'NML_Newsletter' ) ? $newsletter : new NML_Newsletter( $newsletter );
	}

	/**
	 * Set email recipients
	 *
	 * @param object|array $subscribers Single subscriber row from the DB or array of objects.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function set_recipients( $subscribers ) {
		$this->recipients = is_array( $subscribers ) ? $subscribers : array( $subscribers );
	}

	/**
	 * Get template
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_template() {
		if ( ! $this->template ) {
			$this->template = nml_get_option( 'email_template', 'default' );
		}

		return apply_filters( 'nml_email_template', $this->template );
	}

	/**
	 * Build the email
	 *
	 * Put the email content inside the template
	 *
	 * @param string $message
	 *
	 * @access public
	 * @since  1.0
	 * @return string Fully formatted message.
	 */
	public function build_email( $message ) {

		ob_start();

		nml_get_template_part( 'emails/header', $this->get_template(), true );

		/**
		 * Hooks into the email header
		 *
		 * @param NML_Email $this
		 *
		 * @since 1.0
		 */
		do_action( 'nml_email_header', $this );

		nml_get_template_part( 'emails/body', $this->get_template(), true );

		/**
		 * Hooks into the email body
		 *
		 * @param NML_Email $this
		 *
		 * @since 1.0
		 */
		do_action( 'nml_email_body', $this );

		nml_get_template_part( 'emails/footer', $this->get_template(), true );

		/**
		 * Hooks into the email footer
		 *
		 * @param NML_Email $this
		 *
		 * @since 1.0
		 */
		do_action( 'nml_email_footer', $this );

		$body    = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );

		return apply_filters( 'nml_email_message', $message, $this );

	}

	/**
	 * Parse emails
	 *
	 * Pull out emails from the array of objects.
	 *
	 * @param bool $to_string Whether or not to convert array to a comma-separated string.
	 *
	 * @access public
	 * @since  1.0
	 * @return array|string|false Array of emails or comma-separated string. False on failure.
	 */
	public function parse_emails( $to_string = true ) {
		if ( ! is_array( $this->recipients ) ) {
			return false;
		}

		$emails = wp_list_pluck( $this->recipients, 'email' );

		if ( $to_string ) {
			$emails = implode( ',', $emails );
		}

		return apply_filters( 'nml_email_parse_emails', $emails, $to_string, $this->recipients, $this );
	}

	/**
	 * Send the email
	 *
	 * @access public
	 * @since  1.0
	 * @return bool Whether or not the email was sent successfully.
	 */
	public function send() {

		// No subject, no message, and no newsletter -- bail.
		if ( empty( $this->subject ) && empty( $this->message ) && empty( $this->newsletter->ID ) ) {
			return false;
		}

		if ( empty( $this->subject ) ) {
			$this->subject = $this->newsletter->get_subject();
		}

		if ( empty( $this->message ) ) {
			$this->message = $this->newsletter->get_body();
		}

		// If we still don't have a subject or message, bail.
		if ( empty( $this->subject ) || empty( $this->message ) ) {
			return false;
		}

		// Put the message inside the template.
		$message = $this->build_email( $this->message );

		// Send everything to the provider.
		return $this->send_to_provider( $this->subject, $message );

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

	}

}