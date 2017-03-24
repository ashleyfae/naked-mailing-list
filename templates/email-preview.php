<?php
/**
 * Email Preview
 *
 * Template for previewing specific campaigns and blog posts.
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

$message = '';
$email   = new NML_Email();

if ( isset( $_GET['newsletter'] ) ) {

	// Content of a specific campaign.
	$newsletter_id = absint( $_GET['newsletter'] );
	$email->set_newsletter( $newsletter_id );
	$message = $email->newsletter->get_body();

} elseif ( isset( $_GET['preview_email'] ) ) {

	// Previewing a post as an email.
	$message = nml_get_post_notification_message( get_the_ID() );

}

echo $email->build_email( $message );