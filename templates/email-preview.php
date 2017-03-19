<?php
/**
 * Email Preview
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

$newsletter_id = absint( $_GET['newsletter'] );
$email         = new NML_Email( $newsletter_id );

echo $email->build_email( $email->newsletter->get_body() );