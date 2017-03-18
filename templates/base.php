<?php
/**
 * Base Template
 *
 * Used for showing email confirmation and unsubscribe notices, as well
 * as list management.
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

$action = $_GET['nml-action']; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( nml_get_base_css_url() ); ?>" type="text/css">

	<title>
		<?php
		/**
		 * Modifies the <title> based on which action is being performed.
		 *
		 * @see   nml_base_template_title()
		 *
		 * @param string $title  Default title text. This is modifed based on the action.
		 * @param string $action Which action is being performed.
		 *
		 * @since 1.0
		 */
		$title = apply_filters( 'nml_base_template_title', __( 'Unsubscribe', 'naked-mailing-list' ), $action );
		esc_html( $title );
		?>
	</title>

	<?php
	/**
	 * Used to inject code into the <head> area.
	 *
	 * @since 1.0
	 */
	do_action( 'nml_base_template_head', $action );
	?>
</head>

<body class="<?php echo esc_attr( sanitize_html_class( $action ) ); ?>">

<div id="page">
	<main>
		<?php
		/**
		 * Used to display notices based on which action is taking place.
		 *
		 * @see   nml_front_end_notices()
		 * @since 1.0
		 */
		do_action( 'nml_base_template_notices' );
		?>
	</main>

	<footer>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php printf( __( '&laquo; Return to %s', 'naked-mailing-list' ), get_bloginfo( 'name' ) ); ?></a>
	</footer>
</div>

</body>
</html>