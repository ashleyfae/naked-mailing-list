<?php
/**
 * Render Tools Page
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
 * Render Tools page
 *
 * @since 1.0
 * @return void
 */
function nml_tools_page() {

	$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'import';
	?>
	<div class="wrap">
		<h1><?php _e( 'Tools', 'naked-mailing-list' ); ?></h1>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( nml_get_tools_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'tab' => $tab_id
				) );

				$tab_url = remove_query_arg( array(
					'nml-message'
				), $tab_url );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';

			}
			?>
		</h2>

		<div class="metabox-holder">
			<?php do_action( 'nml_tools_tab_' . $active_tab ); ?>
		</div>
	</div>
	<?php

}

/**
 * Retrieve available tools tabs
 *
 * @since 1.0
 * @return array
 */
function nml_get_tools_tabs() {

	$tabs = array(
		'import' => __( 'Import', 'naked-mailing-list' ),
		'export' => __( 'Export', 'naked-mailing-list' )
	);

	if ( nml_get_option( 'debug_mode' ) ) {
		$tabs['debug'] = __( 'Debugging', 'naked-mailing-list' );
	}

	return apply_filters( 'nml_tools_tabs', $tabs );

}

/**
 * Display the tools import tab
 *
 * @since 1.0
 * @return void
 */
function nml_tools_import_display() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	do_action( 'nml_tools_import_before' );
	?>
	<div class="postbox nml-import-subscribers">
		<h3><span><?php _e( 'Import Subscribers', 'naked-mailing-list' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Import a CSV file of subscribers.', 'naked-mailing-list' ); ?></p>
			<form id="nml-import-subscribers" class="nml-import-form nml-import-export-form" action="<?php echo esc_url( add_query_arg( 'nml_action', 'upload_import_file', admin_url() ) ); ?>" method="POST" enctype="multipart/form-data">

				<div class="nml-import-file-wrap">
					<?php wp_nonce_field( 'nml_ajax_import', 'nml_ajax_import' ); ?>
					<input type="hidden" name="nml-import-class" value="NML_Batch_Import_Subscribers">
					<p>
						<label for="nml-subscribers-import-file" class="screen-reader-text"><?php _e( 'Upload a CSV file', 'naked-mailing-list' ); ?></label>
						<input type="file" name="nml-import-file" id="nml-subscribers-import-file">
					</p>
					<span>
						<input type="submit" value="<?php _e( 'Import CSV', 'naked-mailing-list' ); ?>" class="button-secondary">
						<span class="spinner"></span>
					</span>
				</div>

				<div class="nml-import-options" id="nml-import-subscribers-options" style="display: none;">

					<p>
						<?php _e( 'Each column loaded from the CSV needs to be mapped to a payment field. Select the column that should be mapped to each field below. Any columns not needed can be ignored.', 'naked-mailing-list' ); ?>
					</p>

					<table class="widefat nml-mapped-import-fields-table">
						<thead>
						<tr>
							<th><?php _e( 'Subscriber Field', 'naked-mailing-list' ); ?></th>
							<th><?php _e( 'CSV Column', 'naked-mailing-list' ); ?></th>
							<th><?php _e( 'Data Preview', 'naked-mailing-list' ); ?></th>
						</tr>
						</thead>

						<tbody>
						<?php
						$fields = array(
							'email'        => esc_html__( 'Email', 'naked-mailing-list' ),
							'first_name'   => esc_html__( 'First Name', 'naked-mailing-list' ),
							'last_name'    => esc_html__( 'Last Name', 'naked-mailing-list' ),
							'status'       => esc_html__( 'Status', 'naked-mailing-list' ),
							'signup_date'  => esc_html__( 'Signup Date', 'naked-mailing-list' ),
							'confirm_date' => esc_html__( 'Confirm Date', 'naked-mailing-list' ),
							'ip'           => esc_html__( 'IP Address', 'naked-mailing-list' ),
							'referer'      => esc_html__( 'Referring Path', 'naked-mailing-list' ),
							'form_name'    => esc_html__( 'Signup Form Name', 'naked-mailing-list' ),
							'email_count'  => esc_html__( 'Email Count', 'naked-mailing-list' ),
							'notes'        => esc_html__( 'Notes', 'naked-mailing-list' ),
							'lists'        => esc_html__( 'Lists', 'naked-mailing-list' ),
							'tags'         => esc_html__( 'Tags', 'naked-mailing-list' )
						);

						foreach ( $fields as $key => $name ) :
							?>
							<tr>
								<td><?php echo $name; ?></td>
								<td>
									<label for="nml-import-field-<?php echo sanitize_html_class( $key ); ?>" class="screen-reader-text"><?php _e( 'Select a CSV column', 'naked-mailing-list' ); ?></label>
									<select id="nml-import-field-<?php echo sanitize_html_class( $key ); ?>" name="nml-import-field[<?php echo sanitize_html_class( $key ); ?>]" class="nml-import-csv-column">
										<option value=""><?php _e( '- Ignore this field -', 'naked-mailing-list' ); ?></option>
									</select>
								</td>
								<td class="nml-import-preview-field"><?php _e( '- select field to preview data -', 'naked-mailing-list' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<p class="submit">
						<button class="nml-import-proceed button-primary"><?php _e( 'Process Import', 'naked-mailing-list' ); ?></button>
					</p>

				</div>

			</form>
		</div>
	</div>

	<div class="postbox">
		<h3><span><?php _e( 'Import Settings', 'naked-mailing-list' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Import the Naked Mailing List settings from a .json file. This file can be obtained by exporting the settings on another site using the form in the "Export" tab.', 'naked-mailing-list' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'wp-admin/admin.php?page=nml-tools&tab=import' ); ?>">
				<p>
					<input type="file" name="import_file">
				</p>
				<p>
					<input type="hidden" name="nml_action" value="import_settings">
					<?php wp_nonce_field( 'nml_import_nonce', 'nml_import_nonce' ); ?>
					<?php submit_button( __( 'Import', 'naked-mailing-list' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div>
	</div>
	<?php
	do_action( 'nml_tools_import_after' );

}

add_action( 'nml_tools_tab_import', 'nml_tools_import_display' );

/**
 * Display the tools export tab
 *
 * @since 1.0
 * @return void
 */
function nml_tools_export_display() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	do_action( 'nml_tools_export_before' );

	?>
	<div class="postbox nml-export-subscribers">
		<h3><span><?php _e( 'Export Subscribers', 'naked-mailing-list' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Export all the subscribers on this site as a .json file.', 'naked-mailing-list' ); ?></p>
			<!-- @todo -->
		</div>
	</div>

	<div class="postbox nml-export-settings">
		<h3><span><?php _e( 'Export Settings', 'naked-mailing-list' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Export the Naked Mailing List settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'naked-mailing-list' ); ?></p>
			<form method="post" action="<?php echo admin_url( 'admin.php?page=nml-tools&tab=export' ); ?>">
				<p><input type="hidden" name="nml_action" value="export_settings"></p>
				<p>
					<?php wp_nonce_field( 'nml_export_nonce', 'nml_export_nonce' ); ?>
					<?php submit_button( __( 'Export', 'naked-mailing-list' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div>
	</div>
	<?php
	do_action( 'nml_tools_export_after' );

}

add_action( 'nml_tools_tab_export', 'nml_tools_export_display' );

/**
 * Display the debug tab
 *
 * @since 1.0
 * @return void
 */
function nml_tools_debug_display() {

	?>
	<div class="postbox nml-export-subscribers">
		<h3><span><?php _e( 'Debug Log', 'naked-mailing-list' ); ?></span></h3>
		<div class="inside">
			<form method="post" action="<?php echo admin_url( 'admin.php?page=nml-tools&tab=debug' ); ?>">
				<label for="nml-debug-log"><?php _e( 'This file contains debugging information.', 'naked-mailing-list' ); ?></label>
				<textarea id="nml-debug-log" class="large-text" rows="15"><?php echo esc_textarea( naked_mailing_list()->logs->get_log() ); ?></textarea>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Clear Debug Log', 'naked-mailing-list' ); ?>">
				<input type="hidden" name="nml_action" value="clear_debug_log">
				<?php wp_nonce_field( 'nml_clear_debug_log', 'nml_clear_debug_log_nonce' ); ?>
			</form>
		</div>
	</div>
	<?php

}

add_action( 'nml_tools_tab_debug', 'nml_tools_debug_display' );

/**
 * Clear the debug log
 *
 * @since 1.0
 * @return void
 */
function nml_clear_debug_log() {
	if ( ! isset( $_POST['nml_clear_debug_log_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['nml_clear_debug_log_nonce'], 'nml_clear_debug_log' ) ) {
		return;
	}

	naked_mailing_list()->logs->clear_log();

	$url = add_query_arg( array(
		'nml-message' => 'log-cleared'
	), admin_url( 'admin.php?page=nml-tools&tab=debug' ) );

	wp_safe_redirect( $url );
	exit;
}

add_action( 'nml_clear_debug_log', 'nml_clear_debug_log' );