<?php
/**
 * Register Settings
 *
 * Based on register-settings.php in Easy Digital Downloads.
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
 * Get an Option
 *
 * Looks to see if the specified setting exists, returns the default if not.
 *
 * @param string $key     Key to retrieve
 * @param mixed  $default Default option
 *
 * @global       $nml_options
 *
 * @since 1.0
 * @return mixed
 */
function nml_get_option( $key = '', $default = false ) {
	global $nml_options;

	$value = ! empty( $nml_options[ $key ] ) ? $nml_options[ $key ] : $default;
	$value = apply_filters( 'nml_get_option', $value, $key, $default );

	return apply_filters( 'nml_get_option_' . $key, $value, $key, $default );
}

/**
 * Update an Option
 *
 * Updates an existing setting value in both the DB and the global variable.
 * Passing in an empty, false, or null string value will remove the key from the nml_settings array.
 *
 * @param string $key   Key to update
 * @param mixed  $value The value to set the key to
 *
 * @global       $nml_options
 *
 * @since 1.0
 * @return bool True if updated, false if not
 */
function nml_update_option( $key = '', $value = false ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = nml_delete_option( $key );

		return $remove_option;
	}

	// First let's grab the current settings
	$options = get_option( 'nml_settings' );

	// Let's let devs alter that value coming in
	$value = apply_filters( 'nml_update_option', $value, $key );

	// Next let's try to update the value
	$options[ $key ] = $value;
	$did_update      = update_option( 'nml_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $nml_options;
		$nml_options[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Remove an Option
 *
 * Removes an setting value in both the DB and the global variable.
 *
 * @param string $key The key to delete.
 *
 * @global       $nml_options
 *
 * @since 1.0
 * @return boolean True if updated, false if not.
 */
function nml_delete_option( $key = '' ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'nml_settings' );

	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {
		unset( $options[ $key ] );
	}

	$did_update = update_option( 'nml_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $nml_options;
		$nml_options = $options;
	}

	return $did_update;
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array Naked Mailing List settings
 */
function nml_get_settings() {
	$settings = get_option( 'nml_settings', array() );

	return apply_filters( 'nml_get_settings', $settings );
}

/**
 * Add all settings sections and fields.
 *
 * @since 1.0
 * @return void
 */
function nml_register_settings() {

	if ( false == get_option( 'nml_settings' ) ) {
		add_option( 'nml_settings', array() );
	}

	foreach ( nml_get_registered_settings() as $tab => $sections ) {
		foreach ( $sections as $section => $settings ) {
			add_settings_section(
				'nml_settings_' . $tab . '_' . $section,
				__return_null(),
				'__return_false',
				'nml_settings_' . $tab . '_' . $section
			);

			foreach ( $settings as $option ) {
				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'nml_settings[' . $option['id'] . ']',
					$name,
					function_exists( 'nml_' . $option['type'] . '_callback' ) ? 'nml_' . $option['type'] . '_callback' : 'nml_missing_callback',
					'nml_settings_' . $tab . '_' . $section,
					'nml_settings_' . $tab . '_' . $section,
					array(
						'section'     => $section,
						'id'          => isset( $option['id'] ) ? $option['id'] : null,
						'desc'        => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'name'        => isset( $option['name'] ) ? $option['name'] : null,
						'size'        => isset( $option['size'] ) ? $option['size'] : null,
						'options'     => isset( $option['options'] ) ? $option['options'] : '',
						'std'         => isset( $option['std'] ) ? $option['std'] : '',
						'min'         => isset( $option['min'] ) ? $option['min'] : null,
						'max'         => isset( $option['max'] ) ? $option['max'] : null,
						'step'        => isset( $option['step'] ) ? $option['step'] : null,
						'chosen'      => isset( $option['chosen'] ) ? $option['chosen'] : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null
					)
				);
			}
		}
	}

	// Creates our settings in the options table
	register_setting( 'nml_settings', 'nml_settings', 'nml_settings_sanitize' );

}

add_action( 'admin_init', 'nml_register_settings' );

/**
 * Registered Settings
 *
 * Sets and returns the array of all plugin settings.
 * Developers can use the following filters to add their own settings or
 * modify existing ones:
 *
 *  + nml_settings_{key} - Where {key} is a specific tab. Used to modify a single tab/section.
 *  + nml_registered_settings - Includes the entire array of all settings.
 *
 * @since 1.0
 * @return array
 */
function nml_get_registered_settings() {

	$nml_settings = array(
		/* General Settings */
		'general' => apply_filters( 'nml_settings_general', array(
			'main' => array()
		) ),
		/* Emails */
		'emails'  => apply_filters( 'nml_settings_emails', array(
			'main' => array(
				'email_template' => array(
					'id'      => 'email_template',
					'name'    => esc_html__( 'Template', 'naked-mailing-list' ),
					'type'    => 'select',
					'std'     => 'default',
					'options' => nml_get_email_templates()
				),
				'email_footer'   => array(
					'id'   => 'email_footer',
					'name' => esc_html__( 'Footer Text', 'naked-mailing-list' ),
					'type' => 'textarea',
					'std'  => '<a href="' . esc_url( home_url() ) . '">' . get_bloginfo( 'name' ) . '</a>'
				)
			)
		) ),
		/* Sending (providers added via filter) */
		'sending' => apply_filters( 'nml_settings_sending', array(
			'main' => array(
				'provider'           => array(
					'id'      => 'provider',
					'name'    => esc_html__( 'Email Provider', 'naked-mailing-list' ),
					'type'    => 'select',
					'std'     => 'mailgun',
					'options' => nml_get_available_email_providers()
				),
				'post_notifications' => array(
					'id'      => 'post_notifications',
					'name'    => esc_html__( 'Post Notifications', 'naked-mailing-list' ),
					'desc'    => __( 'Email subscribers on this list when a new post is published.', 'naked-mailing-list' ),
					'type'    => 'select',
					'std'     => '',
					'options' => array( '' => esc_html__( 'Disabled', 'naked-mailing-list' ) ) + nml_get_lists_array()
				),
				'per_batch'          => array(
					'id'      => 'per_batch',
					'name'    => esc_html__( 'Emails Per Batch', 'naked-mailing-list' ),
					'desc'    => __( 'Number of emails to send per batch.', 'naked-mailing-list' ),
					'type'    => 'text',
					'std'     => 500,
					'options' => array(
						'type' => 'number'
					)
				),
				'from_name'          => array(
					'id'   => 'from_name',
					'name' => esc_html__( 'Default From Name', 'naked-mailing-list' ),
					'type' => 'text',
					'std'  => get_bloginfo( 'name' )
				),
				'from_email'         => array(
					'id'   => 'from_email',
					'name' => esc_html__( 'Default From Email', 'naked-mailing-list' ),
					'type' => 'text',
					'std'  => get_option( 'admin_email' )
				),
				'reply_to_name'      => array(
					'id'   => 'reply_to_name',
					'name' => esc_html__( 'Default Reply-To Name', 'naked-mailing-list' ),
					'type' => 'text',
					'std'  => get_bloginfo( 'name' )
				),
				'reply_to_email'     => array(
					'id'   => 'reply_to_email',
					'name' => esc_html__( 'Default Reply-To Email', 'naked-mailing-list' ),
					'type' => 'text',
					'std'  => get_option( 'admin_email' )
				),
			)
		) )
	);

	return apply_filters( 'nml_registered_settings', $nml_settings );

}

/**
 * Sanitize Settings
 *
 * Adds a settings error for the updated message.
 *
 * @param array  $input       The value inputted in the field
 *
 * @global array $nml_options Array of all the NML options
 *
 * @since 1.0
 * @return array New, sanitized settings.
 */
function nml_settings_sanitize( $input = array() ) {

	global $nml_options;

	if ( ! is_array( $nml_options ) ) {
		$nml_options = array();
	}

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = nml_get_registered_settings();
	$tab      = ( isset( $referrer['tab'] ) && $referrer['tab'] != 'import_export' ) ? $referrer['tab'] : 'books';
	$section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

	$input = $input ? $input : array();
	$input = apply_filters( 'nml_sanitize_settings_' . $tab . '_' . $section, $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {
		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $section ][ $key ]['type'] ) ? $settings[ $tab ][ $section ][ $key ]['type'] : false;
		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'nml_sanitize_settings_' . $type, $value, $key );
		}
		// General filter
		$input[ $key ] = apply_filters( 'nml_sanitize_settings', $input[ $key ], $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	$main_settings    = $section == 'main' ? $settings[ $tab ] : array();
	$section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();
	$found_settings   = array_merge( $main_settings, $section_settings );

	if ( ! empty( $found_settings ) && is_array( $nml_options ) ) {
		foreach ( $found_settings as $key => $value ) {
			if ( empty( $input[ $key ] ) || ! array_key_exists( $key, $input ) ) {
				unset( $nml_options[ $key ] );
			}
		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $nml_options, $input );

	add_settings_error( 'nml-notices', '', __( 'Settings updated.', 'naked-mailing-list' ), 'updated' );

	return $output;

}

/**
 * Sanitize Text Field
 *
 * @param string $input
 *
 * @since 1.0
 * @return string
 */
function nml_settings_sanitize_text_field( $input ) {
	return sanitize_text_field( $input );
}

add_filter( 'nml_sanitize_settings_text', 'nml_settings_sanitize_text_field' );

/**
 * Sanitize Number Field
 *
 * @param int $input
 *
 * @since 1.0
 * @return int
 */
function nml_settings_sanitize_number_field( $input ) {
	return intval( $input );
}

add_filter( 'nml_sanitize_settings_number', 'nml_settings_sanitize_number_field' );

/**
 * Sanitize Textarea Field
 *
 * @param string $input
 *
 * @since 1.0
 * @return string
 */
function nml_settings_sanitize_textarea_field( $input ) {
	return wp_kses_post( $input );
}

add_filter( 'nml_sanitize_settings_textarea', 'nml_settings_sanitize_textarea_field' );

/**
 * Sanitize Checkbox Field
 *
 * @param int $input
 *
 * @since 1.0
 * @return bool
 */
function nml_settings_sanitize_checkbox_field( $input ) {
	return ( 1 == intval( $input ) ) ? true : false;
}

add_filter( 'nml_sanitize_settings_checkbox', 'nml_settings_sanitize_checkbox_field' );

/**
 * Sanitize Select Field
 *
 * @param string $input
 *
 * @since 1.0
 * @return string
 */
function nml_settings_sanitize_select_field( $input ) {
	return trim( sanitize_text_field( wp_strip_all_tags( $input ) ) );
}

add_filter( 'nml_sanitize_settings_select', 'nml_settings_sanitize_select_field' );

/**
 * Settings Tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function nml_get_settings_tabs() {
	$tabs            = array();
	$tabs['general'] = esc_html__( 'General', 'book-database' );
	$tabs['emails']  = esc_html__( 'Emails', 'book-database' );
	$tabs['sending'] = esc_html__( 'Sending', 'book-database' );

	return apply_filters( 'nml_settings_tabs', $tabs );
}

/**
 * Setting Tab Sections
 *
 * @since 1.0
 * @return array $section
 */
function nml_get_settings_tab_sections( $tab = false ) {
	$tabs     = false;
	$sections = nml_get_registered_settings_sections();

	if ( $tab && ! empty( $sections[ $tab ] ) ) {
		$tabs = $sections[ $tab ];
	} else if ( $tab ) {
		$tabs = false;
	}

	return $tabs;
}

/**
 * Get the settings sections for each tab
 * Uses a static to avoid running the filters on every request to this function
 *
 * @since  1.0
 * @return array|false Array of tabs and sections
 */
function nml_get_registered_settings_sections() {
	static $sections = false;

	if ( false !== $sections ) {
		return $sections;
	}

	$sections = array(
		'general' => apply_filters( 'nml_settings_sections_general', array(
			'main' => esc_html__( 'General Settings', 'book-database' )
		) ),
		'emails'  => apply_filters( 'nml_settings_sections_emails', array(
			'main' => esc_html__( 'Email Settings', 'book-database' )
		) ),
		'sending' => apply_filters( 'nml_settings_sections_sending', array(
			'main' => __( 'Sending Settings', 'book-database' ),
		) )
	);

	$sections = apply_filters( 'nml_settings_sections', $sections );

	return $sections;
}

/**
 * Sanitizes a string key for Book Database Settings
 *
 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are
 * allowed
 *
 * @param  string $key String key
 *
 * @since 1.0
 * @return string Sanitized key
 */
function nml_sanitize_key( $key ) {
	$raw_key = $key;
	$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	return apply_filters( 'nml_sanitize_key', $key, $raw_key );
}

/**
 * Callbacks
 */

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @param array $args Arguments passed by the setting.
 *
 * @since 1.0
 * @return void
 */
function nml_missing_callback( $args ) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'naked-mailing-list' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

/**
 * Callback: Text
 *
 * @param array $args Arguments passed by the setting.
 *
 * @since 1.0
 * @return void
 */
function nml_text_callback( $args ) {
	$saved = nml_get_option( $args['id'] );

	if ( $saved ) {
		$value = $saved;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value            = isset( $args['std'] ) ? $args['std'] : '';
		$name             = '';
	} else {
		$name = 'name="nml_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$type = ( isset( $args['type'] ) ) ? $args['type'] : 'text';
	?>
	<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo esc_attr( sanitize_html_class( $size ) . '-text' ); ?>" id="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" <?php echo $name; ?> value="<?php echo esc_attr( wp_unslash( $value ) ); ?>">
	<?php if ( $args['desc'] ) : ?>
		<label for="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" class="nml-description"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php endif;
}

/**
 * Callback: Textarea
 *
 * @param array $args Arguments passed by the setting.
 *
 * @since 1.0
 * @return void
 */
function nml_textarea_callback( $args ) {
	$saved = nml_get_option( $args['id'] );

	if ( $saved ) {
		$value = $saved;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
	?>
	<textarea class="large-text" id="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" name="nml_settings[<?php echo nml_sanitize_key( $args['id'] ); ?>]" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
	<?php if ( $args['desc'] ) : ?>
		<label for="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" class="nml-description"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php endif;
}

/**
 * Callback: Checkbox
 *
 * @param array $args Arguments passed by the setting.
 *
 * @since 1.0
 * @return void
 */
function nml_checkbox_callback( $args ) {
	$saved = nml_get_option( $args['id'] );

	?>
	<input type="hidden" name="nml_settings[<?php echo nml_sanitize_key( $args['id'] ); ?>]" value="-1">
	<input type="checkbox" id="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" name="nml_settings[<?php echo nml_sanitize_key( $args['id'] ); ?>]" value="1" <?php checked( 1, $saved ); ?>>
	<?php if ( $args['desc'] ) : ?>
		<label for="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" class="nml-description"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php endif;
}

/**
 * Callback: Select
 *
 * @param array $args Arguments passed by the setting.
 *
 * @since 1.0
 * @return void
 */
function nml_select_callback( $args ) {
	$saved = nml_get_option( $args['id'] );

	if ( $saved ) {
		$value = $saved;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( ! is_array( $args['options'] ) ) {
		$args['options'] = array();
	}

	?>
	<select id="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" name="nml_settings[<?php echo nml_sanitize_key( $args['id'] ); ?>]">
		<?php foreach ( $args['options'] as $key => $name ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $value ); ?>><?php echo esc_html( $name ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php if ( $args['desc'] ) : ?>
		<label for="nml_settings_<?php echo nml_sanitize_key( $args['id'] ); ?>" class="nml-description"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php endif;
}

/**
 * Get Pages
 *
 * Returns a list of all published pages on the site.
 *
 * @param bool $force Force the pages to be loaded even if not on the settings page.
 *
 * @since 1.0
 * @return array
 */
function nml_get_pages( $force = false ) {

	$pages_options = array( '' => '' );

	if ( ( ! isset( $_GET['page'] ) || 'nml-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;

}