<?php

/**
 * Subscriber Table Class
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
 * Class NML_Subscriber_Table
 *
 * Renders the subscriber table.
 *
 * @since 1.0
 */
class NML_Subscriber_Table extends WP_List_Table {

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_page = 20;

	/**
	 * Number of books found
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $count = 0;

	/**
	 * Total number of books
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
	 * Display delete message
	 *
	 * @var bool
	 * @access private
	 * @since  1.0
	 */
	private $display_delete_message = false;

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
			'singular' => esc_html__( 'Subscriber', 'naked-mailing-list' ),
			'plural'   => esc_html__( 'Subscribers', 'naked-mailing-list' ),
			'ajax'     => false
		) );

	}

	/**
	 * Show the search field
	 *
	 * @param string $text     Label for the search box.
	 * @param string $input_id ID of the search box.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function search_box( $text, $input_id ) {

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '">';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '">';
		}

		$first_name = isset( $_REQUEST['first_name'] ) ? wp_unslash( $_REQUEST['first_name'] ) : '';
		$email      = isset( $_REQUEST['email'] ) ? wp_unslash( $_REQUEST['email'] ) : '';

		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-first-name"><?php esc_html_e( 'Search by first name', 'naked-mailing-list' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-first-name" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" placeholder="<?php esc_attr_e( 'First name', 'naked-mailing-list' ); ?>">

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-email"><?php esc_html_e( 'Search by email', 'naked-mailing-list' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-email" name="email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'Email address', 'naked-mailing-list' ); ?>">

			<?php submit_button( $text, 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>
		<?php

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'email';
	}

	/**
	 * Renders most of the columns in the list table.
	 *
	 * @param object $item        Contains all the data of the subscriber.
	 * @param string $column_name The name of the column.
	 *
	 * @access public
	 * @since  1.0
	 * @return string Column name
	 */
	public function column_default( $item, $column_name ) {

		$value = '';

		switch ( $column_name ) {

			case 'name' :
				$value = sprintf( '%s %s', $item->first_name, $item->last_name );
				break;

			case 'status' :
				$value = $item->status; // @todo turn into coloured label
				break;

			case 'signup_date' :
				if ( ! empty( $item->signup_date ) ) {
					$value = nml_format_mysql_date( $item->signup_date );
				}
				break;

			default :
				$value = isset( $item->$column_name ) ? $item->$column_name : null;
				break;

		}

		return apply_filters( 'nml_subscriber_table_column_' . $column_name, $value, $item );

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

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		?>
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item->ID ); ?>">
			<?php _e( 'Select this subscriber', 'naked-mailing-list' ) ?>
		</label>
		<input id="cb-select-<?php echo esc_attr( $item->ID ); ?>" type="checkbox" name="subscribers[]" value="<?php echo esc_attr( $item->ID ); ?>">
		<?php

	}

	/**
	 * Render Column Name
	 *
	 * @param object $item Contains all the data of the subscriber.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function column_email( $item ) {
		$edit_url = nml_get_admin_page_edit_subscriber( $item->ID );
		$name     = '<a href="' . esc_url( $edit_url ) . '" class="row-title" aria-label="' . esc_attr( sprintf( '%s (Edit)', $item->email ) ) . '">' . $item->email . '</a>';
		$actions  = array(
			'edit'   => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'naked-mailing-list' ) . '</a>',
			'delete' => '<a href="' . esc_url( nml_get_admin_page_delete_subscriber( $item->ID ) ) . '">' . __( 'Delete', 'naked-mailing-list' ) . '</a>'
		);

		return $name . $this->row_actions( $actions );
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
			'cb'          => '<input type="checkbox">',
			'email'       => __( 'Email', 'naked-mailing-list' ),
			'name'        => __( 'Name', 'naked-mailing-list' ),
			'status'      => __( 'Status', 'naked-mailing-list' ),
			'signup_date' => __( 'Signup Date', 'naked-mailing-list' ),
			'email_count' => __( 'Email Count', 'naked-mailing-list' )
		);

		return apply_filters( 'naked-mailing-list/books-table/book-columns', $columns );
	}

	/**
	 * Get the sortable columns.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'email'       => array( 'email', true ),
			'first_name'  => array( 'first_name', true ),
			'status'      => array( 'status', true ),
			'signup_date' => array( 'signup_date', true ),
			'email_count' => array( 'email_count', true )
		);
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

		// Display 'delete' success message.
		if ( 'top' == $which && true === $this->display_delete_message ) {
			?>
			<div id="message" class="updated notice notice-success">
				<p><?php _e( 'Subscribers successfully deleted.', 'naked-mailing-list' ); ?></p>
			</div>
			<?php
		}

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php

	}

	/**
	 * Get Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete Permanently', 'naked-mailing-list' )
		);

		return apply_filters( 'nml_subscribers_table_bulk_actions', $actions );
	}

	/**
	 * Process Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function process_bulk_actions() {

		if ( 'delete' == $this->current_action() ) {

			// Check nonce.
			if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Failed security check.', 'naked-mailing-list' ) );
			}

			// Checek capability.
			if ( ! current_user_can( 'delete_posts' ) ) {
				wp_die( __( 'You don\'t have permission to delete subscribers.', 'naked-mailing-list' ) );
			}

			if ( isset( $_GET['subscribers'] ) && is_array( $_GET['subscribers'] ) && count( $_GET['subscribers'] ) ) {
				naked_mailing_list()->subscribers->delete_by_ids( $_GET['subscribers'] ); // @todo create this

				// Display the delete message.
				$this->display_delete_message = true;
			}

		}

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
	 * Build all the subscriber data.
	 *
	 * @access public
	 * @since  1.0
	 * @return array Array of subscribers.
	 */
	public function subscribers_data() {

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

		// Filter by email
		if ( isset( $_GET['email'] ) ) {
			$args['email'] = sanitize_email( $_GET['email'] );
		}

		// Filter by first name
		if ( isset( $_GET['first_name'] ) ) {
			$args['first_name'] = sanitize_text_field( $_GET['first_name'] );
		}

		// Filter by status
		if ( isset( $_GET['status'] ) ) {
			$args['status'] = sanitize_text_field( $_GET['status'] );
		}

		$this->args  = $args;
		$subscribers = naked_mailing_list()->subscribers->get_subscribers( $args );

		return $subscribers;

	}

	/**
	 * Prepare Items
	 *
	 * Setup the final data for the table.
	 *
	 * @uses   NML_Subscribers_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 * @uses   NML_Subscribers_Table::subscribers_data()
	 * @uses   WP_List_Table::set_pagination_args()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function prepare_items() {

		// Process bulk actions.
		$this->process_bulk_actions();

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->subscribers_data();

		$this->total = naked_mailing_list()->subscribers->count( $this->args );

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
		) );

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	 */
	public function no_items() {
		printf(
			__( 'No subscribers found. Would you like to %sadd one?%', 'naked-mailing-list' ),
			'<a href="' . esc_url( nml_get_admin_page_add_subscriber() ) . '">',
			'</a>'
		);
	}

}