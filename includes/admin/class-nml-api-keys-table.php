<?php

/**
 * API Keys Table
 *
 * Displays a list of all API keys.
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

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class NML_API_Keys_Table
 *
 * @since 1.0
 */
class NML_API_Keys_Table extends WP_List_Table {

	/**
	 * Number of results per page
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_page = 20;

	/**
	 * Number of keys found
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $count = 0;

	/**
	 * Total number of keys
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $total = 0;

	/**
	 * The arguments for the data set
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $args = array();

	/**
	 * NML_Subscriber_Table constructor.
	 *
	 * @see    WP_List_Table::__construct()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $status, $page;

		parent::__construct( array(
			'singular' => esc_html__( 'API Key', 'naked-mailing-list' ),
			'plural'   => esc_html__( 'API Keys', 'naked-mailing-list' ),
			'ajax'     => false
		) );

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'ID';
	}

	/**
	 * Get Columns
	 *
	 * Retrieves the column IDs and names.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox">',
			'ID'         => __( 'ID', 'naked-mailing-list' ),
			'user_id'    => __( 'Username', 'naked-mailing-list' ),
			'api_key'    => __( 'Public Key', 'naked-mailing-list' ),
			'api_secret' => __( 'Secret Key', 'naked-mailing-list' ),
			'active'     => __( 'Status', 'naked-mailing-list' )
		);

		return apply_filters( 'nml_api_key_table_columns', $columns );
	}

	/**
	 * Renders most of the columns in the list table.
	 *
	 * @param object $item        Contains all the data of the key.
	 * @param string $column_name The name of the column.
	 *
	 * @access public
	 * @since  1.0
	 * @return string Column name
	 */
	public function column_default( $item, $column_name ) {

		$value = '';

		switch ( $column_name ) {

			case 'user_id' :
				$user_id = $item->user_id;
				$user    = new WP_User( absint( $user_id ) );
				$url     = add_query_arg( 'user_id', absint( $user_id ), admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) );
				$value   = '<a href="' . esc_url( $url ) . '">' . $user->user_login . '</a>';
				break;

			case 'active' :
				$value = ( 1 === intval( $item->active ) ) ? __( 'Active', 'naked-mailing-list' ) : __( 'Disabled', 'naked-mailing-list' );
				break;

			default :
				$value = isset( $item->$column_name ) ? $item->$column_name : null;
				break;

		}

		return apply_filters( 'nml_api_key_table_column_' . $column_name, $value, $item );

	}

	/**
	 * Render Checkbox Column
	 *
	 * @param object $item Contains all the data of the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function column_cb( $item ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item->ID ); ?>">
			<?php _e( 'Select this API key', 'naked-mailing-list' ) ?>
		</label>
		<input id="cb-select-<?php echo esc_attr( $item->ID ); ?>" type="checkbox" name="api_keys[]" value="<?php echo esc_attr( $item->ID ); ?>">
		<?php

	}

	/**
	 * Render Column Name
	 *
	 * @param object $item Contains all the data of the API key.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function column_ID( $item ) {

		$actions = array(
			'view'    => '', // @todo request log
			'reissue' => sprintf(
				'<a href="%s" class="nml-regenerate-api-key">%s</a>',
				esc_url( wp_nonce_url( add_query_arg( array(
					'user_id'         => urlencode( $item->user_id ),
					'nml_action'      => 'process_api_key',
					'nml_api_process' => 'reissue'
				) ), 'nml_api_nonce' ) ),
				__( 'Reissue', 'naked-mailing-list' )
			)
		);

		if ( $item->active ) {
			$actions['deactivate'] = sprintf(
				'<a href="%s" class="nml-deactivate-api-key">%s</a>',
				esc_url( wp_nonce_url( add_query_arg( array(
					'ID'              => urlencode( $item->ID ),
					'nml_action'      => 'process_api_key',
					'nml_api_process' => 'deactivate'
				) ), 'nml_api_nonce' ) ),
				__( 'Deactivate', 'naked-mailing-list' )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%s" class="nml-activate-api-key">%s</a>',
				esc_url( wp_nonce_url( add_query_arg( array(
					'ID'              => urlencode( $item->ID ),
					'nml_action'      => 'process_api_key',
					'nml_api_process' => 'activate'
				) ), 'nml_api_nonce' ) ),
				__( 'Activate', 'naked-mailing-list' )
			);
		}

		$actions['delete'] = sprintf(
			'<a href="%s" class="nml-delete-api-key">%s</a>',
			esc_url( wp_nonce_url( add_query_arg( array(
				'ID'              => urlencode( $item->ID ),
				'nml_action'      => 'process_api_key',
				'nml_api_process' => 'delete'
			) ), 'nml_api_nonce' ) ),
			__( 'Delete', 'naked-mailing-list' )
		);

		$actions = apply_filters( 'nml_api_key_table_row_actions', array_filter( $actions ) );

		return sprintf( '%s %s', $item->ID, $this->row_actions( $actions ) );

	}

	/**
	 * Display the key generation form
	 *
	 * @param string $which
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {

		// These aren't really bulk actions but this outputs the markup in the right place.
		static $nml_api_is_bottom;

		if ( $nml_api_is_bottom ) {
			return;
		}
		?>
		<form id="nml-api-key-generate-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=nml-tools&tab=api_keys' ) ); ?>">
			<input type="hidden" name="nml_action" value="process_api_key">
			<input type="hidden" name="nml_api_process" value="create">
			<?php wp_nonce_field( 'nml_api_nonce' ); ?>
			<label for="nml-api-user-id" class="screen-reader-text"><?php _e( 'Enter a username to create the API key for', 'naked-mailing-list' ); ?></label>
			<input type="text" id="nml-api-user-id" name="username" placeholder="<?php esc_attr_e( 'Enter username', 'naked-mailing-list' ); ?>">
			<?php submit_button( __( 'Generate New API Key', 'naked-mailing-list' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
		$nml_api_is_bottom = true;

	}

	/**
	 * Table Navigation
	 *
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php

	}

	/**
	 * Retrieve the current page number.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieves the search query string.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|string Search query or false if none.
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the API key data.
	 *
	 * @access public
	 * @since  1.0
	 * @return array Array of API keys.
	 */
	public function key_data() {

		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);

		// Filter by user ID.
		if ( isset( $_GET['user_id'] ) ) {
			$args['user_id'] = absint( $_GET['user_id'] );
		}

		// Filter by status.
		if ( isset( $_GET['status'] ) ) {
			$args['status'] = ( 'active' == $_GET['status'] ) ? 'active' : 'inactive';
		}

		$this->args = $args;
		$keys       = naked_mailing_list()->api_keys->get_keys( $args );

		return $keys;

	}

	/**
	 * Prepare Items
	 *
	 * Setup the final data for the table.
	 *
	 * @uses   NML_API_Keys_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 * @uses   NML_API_Keys_Table::key_data()
	 * @uses   WP_List_Table::set_pagination_args()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->key_data();
		$this->total = naked_mailing_list()->api_keys->count( $this->args );

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
		) );

	}

}