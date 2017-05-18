<?php
/**
 * Email
 *
 * Note: CSS is included automatically from assets/css/email.css
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

$header_img_id = nml_get_option( 'email_header_img' );
$footer_text   = nml_get_option( 'email_footer' );
$footer_text   = str_replace( '{year}', date( 'Y' ), $footer_text );
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo get_bloginfo( 'name' ); ?></title>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<div id="wrapper">
	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
		<tr>
			<td align="center" valign="top">
				<?php if ( ! empty( $header_img_id ) ) : ?>
					<div id="template_header_image">
						<?php echo '<p style="margin-top:0;"><img src="' . esc_url( wp_get_attachment_url( $header_img_id ) ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" /></p>'; ?>
					</div>
				<?php endif; ?>
				<table border="0" cellpadding="0" cellspacing="0" id="template-container">
					<tr>
						<td align="center" valign="top">
							<!-- Body -->
							<table border="0" cellpadding="0" cellspacing="0" id="template-body">
								<tr>
									<td valign="top" id="body-content">
										<!-- Content -->
										<table border="0" cellpadding="20" cellspacing="0" width="100%">
											<tr>
												<td valign="top">
													<div id="body-content-inner">
														<?php
														/**
														 * Inserts content before the email body.
														 *
														 * @since 1.0
														 */
														do_action( 'nml_email_body_before' );
														?>
														{email}
														<?php
														/**
														 * Inserts content after the email body.
														 *
														 * @since 1.0
														 */
														do_action( 'nml_email_body_after' );
														?>
													</div>
												</td>
											</tr>
										</table>
										<!-- End Content -->
									</td>
								</tr>
							</table>
							<!-- End Body -->
						</td>
					</tr>
					<?php if ( ! empty( $footer_text ) ) : ?>
						<tr>
							<td align="center" valign="top">
								<!-- Footer -->
								<table border="0" cellpadding="10" cellspacing="0" width="600" id="template-footer">
									<tr>
										<td valign="top">
											<table border="0" cellpadding="10" cellspacing="0" width="100%">
												<tr>
													<td colspan="2" valign="middle" id="credit">
														<?php
														/**
														 * Inserts content before the footer text.
														 *
														 * @since 1.0
														 */
														do_action( 'nml_email_before_footer_text' );

														echo wpautop( wp_kses_post( wptexturize( $footer_text ) ) );

														/**
														 * Inserts content after the footer text.
														 *
														 * @since 1.0
														 */
														do_action( 'nml_email_after_footer_text' );
														?>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<!-- End Footer -->
							</td>
						</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>
	</table>
</div>
</body>
</html>