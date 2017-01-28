<?php
/**
 * Installation Functions
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
 * Install
 *
 * Runs on plugin install by setting up the database tables, etc.
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 *
 * @since 1.0
 * @return void
 */
function nml_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
			switch_to_blog( $blog_id );
			nml_run_install();
			restore_current_blog();
		}
	} else {
		nml_run_install();
	}

}

register_activation_hook( NML_PLUGIN_FILE, 'nml_install' );

/**
 * Run the installation process.
 *
 * @since 1.0
 * @return void
 */
function nml_run_install() {
	global $wpdb;

	@naked_mailing_list()->subscribers->create_table();
	@naked_mailing_list()->subscriber_meta->create_table();
}

//add_action('admin_init', 'nml_run_install');

/**
 * When a new blog is created in multisite, run the installer.
 *
 * @uses  nml_install()
 *
 * @param int    $blog_id ID of the blog created.
 * @param int    $user_id The user ID set as the administrator ot eh blog.
 * @param string $domain  URL of the created blog.
 * @param string $path    Site path for the created blog.
 * @param int    $site_id Site ID.
 * @param array  $meta    Blog meta data.
 *
 * @since 1.0
 * @return void
 */
function nml_new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	if ( is_plugin_active_for_network( plugin_basename( NML_PLUGIN_FILE ) ) ) {
		switch_to_blog( $blog_id );
		nml_install();
		restore_current_blog();
	}

}

/**
 * Drop our custom tables when a sub-site is deleted.
 *
 * @param array $tables  Tables to drop.
 * @param int   $blog_id ID of the blog being deleted.
 *
 * @since 1.0
 * @return array
 */
function nml_wpmu_drop_tables( $tables, $blog_id ) {

	switch_to_blog( $blog_id );

	$subscribers_db     = new NML_DB_Subscribers();
	$subscriber_meta_db = new NML_DB_Subscriber_Meta();

	if ( $subscribers_db->installed() ) {
		$tables[] = $subscribers_db->table_name;
		$tables[] = $subscriber_meta_db->table_name;
	}

	restore_current_blog();

	return $tables;

}

add_filter( 'wpmu_drop_tables', 'nml_wpmu_drop_tables', 10, 2 );