<?php

/**
 * Admin Notices
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
 * Class NML_Admin_Notices
 *
 * @since 1.0
 */
class NML_Admin_Notices {

	/**
	 * NML_Admin_Notices constructor.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_action( 'nml_dismiss_notices', array( $this, 'dismiss_notices' ) );
	}

	/**
	 * Show relevant notices
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function show_notices() {

		$notices = array(
			'updated' => array(),
			'error'   => array(),
		);

		if ( isset( $_GET['nml-message'] ) ) {

			switch ( $_GET['nml-message'] ) {

				// Lists
				case 'list-updated' :
					$notices['updated']['nml-list-updated'] = __( 'List successfully updated.', 'naked-mailing-list' );
					break;

				case 'list-deleted' :
					$notices['updated']['nml-list-deleted'] = __( 'List successfully deleted.', 'naked-mailing-list' );
					break;

				// Subscribers
				case 'subscriber-updated' :
					$notices['updated']['nml-subscriber-updated'] = __( 'Subscriber successfully updated.', 'naked-mailing-list' );
					break;

				case 'subscriber-deleted' :
					$notices['updated']['nml-subscriber-deleted'] = __( 'Subscriber successfully deleted.', 'naked-mailing-list' );
					break;

				case 'subscriber-confirmation-resent' :
					$notices['updated']['nml-subscriber-confirmation-resent'] = __( 'The confirmation email has been successfully re-sent to the subscriber.', 'naked-mailing-list' );
					break;

				// Newsletters
				case 'newsletter-updated' :
					$notices['updated']['nml-newsletter-updated'] = __( 'Newsletter successfully updated.', 'naked-mailing-list' );
					break;

				case 'newsletter-deleted' :
					$notices['updated']['nml-newsletter-deleted'] = __( 'Newsletter successfully deleted.', 'naked-mailing-list' );
					break;

				// Settings
				case 'settings-imported' :
					$notices['updated']['nml-settings-imported'] = __( 'The settings have been successfully imported.', 'naked-mailing-list' );
					break;

			}

		}

		if ( count( $notices['updated'] ) > 0 ) {
			foreach ( $notices['updated'] as $notice => $message ) {
				add_settings_error( 'nml-notices', $notice, $message, 'updated' );
			}
		}

		if ( count( $notices['error'] ) > 0 ) {
			foreach ( $notices['error'] as $notice => $message ) {
				add_settings_error( 'nml-notices', $notice, $message, 'error' );
			}
		}

		settings_errors( 'nml-notices' );

	}

	/**
	 * Dismiss admin notices when Dismiss links are clicked
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function dismiss_notices() {

		if ( isset( $_GET['nml-notice'] ) ) {
			update_user_meta( get_current_user_id(), '_nml_' . sanitize_text_field( $_GET['nml-notice'] ) . '_dismissed', 1 );
			wp_redirect( remove_query_arg( array( 'nml_action', 'nml-notice' ) ) );
			exit;
		}

	}

}

new NML_Admin_Notices();