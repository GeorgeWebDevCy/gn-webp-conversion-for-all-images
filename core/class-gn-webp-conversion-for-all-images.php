<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Gn_Webp_Conversion_For_All_Images' ) ) :

	/**
	 * Main Gn_Webp_Conversion_For_All_Images Class.
	 *
	 * @package		GNWEBPCONV
	 * @subpackage	Classes/Gn_Webp_Conversion_For_All_Images
	 * @since		1.0.0
	 * @author		George Nicolaou
	 */
	final class Gn_Webp_Conversion_For_All_Images {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Gn_Webp_Conversion_For_All_Images
		 */
		private static $instance;

		/**
		 * GNWEBPCONV helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gn_Webp_Conversion_For_All_Images_Helpers
		 */
		public $helpers;

		/**
		 * GNWEBPCONV settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gn_Webp_Conversion_For_All_Images_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'gn-webp-conversion-for-all-images' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'gn-webp-conversion-for-all-images' ), '1.0.0' );
		}

		/**
		 * Main Gn_Webp_Conversion_For_All_Images Instance.
		 *
		 * Insures that only one instance of Gn_Webp_Conversion_For_All_Images exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Gn_Webp_Conversion_For_All_Images	The one true Gn_Webp_Conversion_For_All_Images
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Gn_Webp_Conversion_For_All_Images ) ) {
				self::$instance					= new Gn_Webp_Conversion_For_All_Images;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Gn_Webp_Conversion_For_All_Images_Helpers();
				self::$instance->settings		= new Gn_Webp_Conversion_For_All_Images_Settings();

				//Fire the plugin logic
				new Gn_Webp_Conversion_For_All_Images_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'GNWEBPCONV/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once GNWEBPCONV_PLUGIN_DIR . 'core/includes/classes/class-gn-webp-conversion-for-all-images-helpers.php';
			require_once GNWEBPCONV_PLUGIN_DIR . 'core/includes/classes/class-gn-webp-conversion-for-all-images-settings.php';

			require_once GNWEBPCONV_PLUGIN_DIR . 'core/includes/classes/class-gn-webp-conversion-for-all-images-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'gn-webp-conversion-for-all-images', FALSE, dirname( plugin_basename( GNWEBPCONV_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.