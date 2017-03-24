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
	 * Holds the email content type
	 *
	 * @var string
	 * @access protected
	 * @since  1.0
	 */
	protected $content_type;

	/**
	 * Whether to send email in HTML
	 *
	 * @var bool
	 * @access protected
	 * @since  1.0
	 */
	protected $html = true;

	/**
	 * Newsletter object
	 *
	 * @var NML_Newsletter
	 * @access protected
	 * @since  1.0
	 */
	public $newsletter;

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
		$this->newsletter = ! empty( $newsletter_id ) ? new NML_Newsletter( $newsletter_id ) : null;

		$this->from_name    = ! empty( $this->newsletter->from_name ) ? $this->newsletter->from_name : nml_get_option( 'from_name' );
		$this->from_address = ! empty( $this->newsletter->from_address ) ? $this->newsletter->from_address : nml_get_option( 'from_email' );
		// @todo reply-to stuff

		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

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
	 * Get the email content type
	 *
	 * @access public
	 * @since  1.0
	 * @return string The email content type
	 */
	public function get_content_type() {

		if ( ! $this->content_type && $this->html ) {
			/**
			 * Filters the default content type.
			 *
			 * @param string    $content_type Email content type.
			 * @param NML_Email $this         Email object.
			 *
			 * @since 1.0
			 */
			$this->content_type = apply_filters( 'nml_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}

		/**
		 * Filters the email content type.
		 *
		 * @param string    $content_type Email content type.
		 * @param NML_Email $this         Email object.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'nml_email_content_type', $this->content_type, $this );

	}

	/**
	 * Converts text formatted HTML. This is primarily for turning line breaks into <p> and <br/> tags.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function text_to_html( $message ) {

		if ( 'text/html' === $this->content_type || true === $this->html ) {
			/**
			 * Filters whether or not `wpautop()` should be applied to the email. (Default: yes.)
			 *
			 * @param bool $apply Whether or not to apply the function.
			 *
			 * @since 1.0
			 */
			if ( apply_filters( 'nml_email_template_wpautop', true ) ) {
				$message = wpautop( $message );
			}

			/**
			 * Filters whether or not to apply `the_content` filter to the email. (Default: yes.)
			 *
			 * @param bool $apply Whether or not to apply the filter.
			 *
			 * @since 1.0
			 */
			if ( apply_filters( 'nml_email_template_content_filter', true ) ) {
				$message = apply_filters( 'the_content', $message );
			}
		}

		return $message;

	}

	/**
	 * Get unsubscribe link
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_unsubscribe_link() {
		return nml_get_unsubscribe_link();
	}

	/**
	 * Appends the unsubscribe link if a newsletter is specified.
	 *
	 * @param string $message Text to append the link to.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function maybe_add_unsubscribe_link( $message ) {

		if ( empty( $this->newsletter ) ) {
			return $message;
		}

		if ( false === $this->html ) {
			$message = $message . "\n\n" . sprintf( __( 'Unsubscribe: %s' ), esc_url( $this->get_unsubscribe_link() ) );

		} else {
			$message = $message . '<p style="text-align: center; margin-top: 3em;">' . sprintf( __( '<a href="%s" target="_blank">Unsubscribe from all emails</a>', 'naked-mailing-list' ), esc_url( $this->get_unsubscribe_link() ) ) . '</p>';
		}

		return $message;

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

		$message = $this->maybe_add_unsubscribe_link( $message );

		if ( false === $this->html ) {
			return apply_filters( 'nml_email_message', wp_strip_all_tags( $message ), $this );
		}

		$message = $this->text_to_html( $message );

		ob_start();

		nml_get_template_part( 'email', $this->get_template(), true );

		$body    = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );

		// Convert CSS styles to inline style.
		require_once NML_PLUGIN_DIR . 'includes/libraries/emogrifier.php';

		/**
		 * Modifies the CSS file that's included in the email. This should be
		 * the path (not URL) to the file.
		 *
		 * @param string $css_file Path to the CSS file to include in the email.
		 *
		 * @since 1.0
		 */
		$css_file = apply_filters( 'nml_email_css_file_path', NML_PLUGIN_DIR . 'assets/css/email.css' );

		if ( file_exists( $css_file ) ) {
			$css        = file_get_contents( $css_file );
			$emogrifier = new \Pelago\Emogrifier( $message, $css );
			$message    = $emogrifier->emogrify();
		}

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

		/**
		 * Filters the parsed emails from the subscriber objects.
		 *
		 * @param array|string|false $emails     Array of emails or comma-separated string. False on failure.
		 * @param bool               $to_string  Whether or not to use a comma-separated string.
		 * @param array              $recipients Array of subscriber objects.
		 * @param NML_Email          $this       Email object.
		 *
		 * @since 1.0
		 */
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