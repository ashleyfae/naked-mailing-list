<?php
/**
 * Newsletter Functions
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
 * Get array of available newsletter types
 *
 * @since 1.0
 * @return array
 */
function nml_get_newsletter_types() {
	$types = array(
		'newsletter'        => esc_html__( 'Newsletter', 'naked-mailing-list' ),
		'post_notification' => esc_html__( 'Post Notification', 'naked-mailing-list' )
	);

	return apply_filters( 'nml_newsletter_types', $types );
}

/**
 * Get array of available newsletter statuses
 *
 * @since 1.0
 * @return array
 */
function nml_get_newsletter_statuses() {
	$statuses = array(
		'draft'     => esc_html__( 'Draft', 'naked-mailing-list' ),
		'scheduled' => esc_html__( 'Scheduled', 'naked-mailing-list' ),
		'sending'   => esc_html__( 'Sending', 'naked-mailing-list' ),
		'sent'      => esc_html__( 'Sent', 'naked-mailing-list' )
	);

	return apply_filters( 'nml_newsletter_statuses', $statuses );
}