<?php
/**
 * Plugin Name: Naked Mailing List
 * Plugin URI: https://www.nosegraze.com
 * Description: Simple mailing list plugin.
 * Version: 1.0.0
 * Author: Ashley Gibson
 * Author URI: https://www.nosegraze.com
 * License: GPL2+
 * Text Domain: naked-mailing-list
 * Domain Path: /languages
 *
 * Naked Mailing List is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Naked Mailing List is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Naked Mailing List. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Naked_Mailing_List' ) ) :

	final class Naked_Mailing_List {

		/**
		 * @var Naked_Mailing_List The one true Naked_Mailing_List
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * Subscribers DB object.
		 *
		 * @var NML_DB_Subscribers
		 * @since 1.0
		 */
		public $subscribers;

		/**
		 * Subscriber meta DB object.
		 *
		 * @var NML_DB_Subscriber_Meta
		 * @since 1.0
		 */
		public $subscriber_meta;

		/**
		 * @var NML_DB_Activity
		 * @since 1.0
		 */
		public $activity;

		/**
		 * @var NML_DB_Lists
		 * @since 1.0
		 */
		public $lists;

		/**
		 * @var NML_DB_List_Relationships
		 * @since 1.0
		 */
		public $list_relationships;

		/**
		 * @var NML_DB_Newsletters
		 * @since 1.0
		 */
		public $newsletters;

		/**
		 * @var NML_DB_Newsletter_List_Relationships
		 * @since 1.0
		 */
		public $newsletter_list_relationships;

		/**
		 * @var NML_DB_Queue
		 * @since 1.0
		 */
		public $queue;

		/**
		 * Main Naked_Mailing_List Instance
		 *
		 * Ensures that only one instance of Naked_Mailing_List exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access public
		 * @static
		 * @since  1.0
		 * @return Naked_Mailing_List
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Naked_Mailing_List ) ) {
				self::$instance = new Naked_Mailing_List;
				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();
				self::$instance->subscribers                   = new NML_DB_Subscribers();
				self::$instance->subscriber_meta               = new NML_DB_Subscriber_Meta();
				self::$instance->activity                      = new NML_DB_Activity();
				self::$instance->lists                         = new NML_DB_Lists();
				self::$instance->list_relationships            = new NML_DB_List_Relationships();
				self::$instance->newsletters                   = new NML_DB_Newsletters();
				self::$instance->newsletter_list_relationships = new NML_DB_Newsletter_List_Relationships();
				self::$instance->queue                         = new NML_DB_Queue();
			}

			return self::$instance;

		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @since  1.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'naked-mailing-list' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access public
		 * @since  1.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'naked-mailing-list' ), '1.0' );
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since  1.0
		 * @return void
		 */
		private function setup_constants() {

			// Plugin version.
			if ( ! defined( 'NML_VERSION' ) ) {
				define( 'NML_VERSION', '1.0' );
			}

			// Plugin Folder Path.
			if ( ! defined( 'NML_PLUGIN_DIR' ) ) {
				define( 'NML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'NML_PLUGIN_URL' ) ) {
				define( 'NML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'NML_PLUGIN_FILE' ) ) {
				define( 'NML_PLUGIN_FILE', __FILE__ );
			}

		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since  1.0
		 * @return void
		 */
		private function includes() {

			// @todo settings here

			require_once NML_PLUGIN_DIR . 'includes/class-nml-db.php';
			require_once NML_PLUGIN_DIR . 'includes/activity/class-nml-db-activity.php';
			require_once NML_PLUGIN_DIR . 'includes/lists/class-nml-db-lists.php';
			require_once NML_PLUGIN_DIR . 'includes/lists/class-nml-db-list-relationships.php';
			require_once NML_PLUGIN_DIR . 'includes/lists/list-functions.php';
			require_once NML_PLUGIN_DIR . 'includes/newsletters/class-nml-db-newsletters.php';
			require_once NML_PLUGIN_DIR . 'includes/newsletters/class-nml-db-newsletter-list-relationships.php';
			require_once NML_PLUGIN_DIR . 'includes/newsletters/class-nml-newsletter.php';
			require_once NML_PLUGIN_DIR . 'includes/newsletters/newsletter-functions.php';
			require_once NML_PLUGIN_DIR . 'includes/queue/class-nml-db-queue.php';
			require_once NML_PLUGIN_DIR . 'includes/subscribers/class-nml-db-subscribers.php';
			require_once NML_PLUGIN_DIR . 'includes/subscribers/class-nml-db-subscriber-meta.php';
			require_once NML_PLUGIN_DIR . 'includes/subscribers/class-nml-subscriber.php';
			require_once NML_PLUGIN_DIR . 'includes/subscribers/subscriber-functions.php';
			require_once NML_PLUGIN_DIR . 'includes/error-tracking.php';
			require_once NML_PLUGIN_DIR . 'includes/misc-functions.php';

			require_once NML_PLUGIN_DIR . 'includes/install.php';

			if ( is_admin() ) {
				require_once NML_PLUGIN_DIR . 'includes/admin/admin-actions.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/admin-assets.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/admin-pages.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/newsletters/newsletter-actions.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/newsletters/newsletter-functions.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/newsletters/newsletters.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/subscribers/subscriber-actions.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/subscribers/subscriber-functions.php';
				require_once NML_PLUGIN_DIR . 'includes/admin/subscribers/subscribers.php';
			}

		}

		/**
		 * Load the plugin language files.
		 *
		 * @access public
		 * @since  1.0
		 * @return void
		 */
		public function load_textdomain() {

			// @todo

		}

	}

endif;

/**
 * The main function that returns Naked_Mailing_List
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $nml = naked_mailing_list(); ?>
 *
 * @since 1.0
 * @return Naked_Mailing_List
 */
function naked_mailing_list() {
	return Naked_Mailing_List::instance();
}

naked_mailing_list();