<?php
/**
 * Template Functions
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
 * Returns the path to the NML templates directory
 *
 * @since 1.0
 * @return string
 */
function nml_get_templates_dir() {
	return NML_PLUGIN_DIR . 'templates';
}

/**
 * Reaturns the URL to the templates directory
 *
 * @since 1.0
 * @return string
 */
function nml_get_templates_url() {
	return NML_PLUGIN_URL . 'templates';
}

/**
 * Retrieves a template part
 *
 * Taken from bbPress
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Default null
 * @param bool   $load Whether or not to load the template part. Optional.
 *
 * @uses   nml_locate_template()
 * @uses   load_template()
 * @uses   get_template_part()
 *
 * @since  1.0
 * @return string The template filename if one is located.
 */
function nml_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = $slug . '-' . $name . '.php';
	}
	$templates[] = $slug . '.php';

	// Allow template parst to be filtered
	$templates = apply_filters( 'nml_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return nml_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the NML templates folder last.
 *
 * Taken from bbPress
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool         $load           If true the template file will be loaded if it is found.
 * @param bool         $require_once   Whether to require_once or require. Default true.
 *                                     Has no effect if $load is false.
 *
 * @since  1.0
 * @return string The template filename if one is located.
 */
function nml_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	$template_stack = array();

	// check child theme first
	$template_stack[] = trailingslashit( get_stylesheet_directory() ) . 'rcp/';

	// check parent theme next
	$template_stack[] = trailingslashit( get_template_directory() ) . 'rcp/';

	// check custom directories
	$template_stack = apply_filters( 'nml_template_stack', $template_stack, $template_names );

	// check theme compatibility last
	$template_stack[] = trailingslashit( nml_get_templates_dir() );

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) ) {
			continue;
		}

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Loop through template stack.
		foreach ( (array) $template_stack as $template_location ) {

			// Continue if $template_location is empty.
			if ( empty( $template_location ) ) {
				continue;
			}

			// Check child theme first.
			if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$located = trailingslashit( $template_location ) . $template_name;
				break 2;
			}
		}

	}

	if ( ( true == $load ) && ! empty( $located ) ) {
		load_template( $located, $require_once );
	}

	return $located;
}