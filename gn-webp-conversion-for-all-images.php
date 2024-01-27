<?php
/**
 * GN WebP Conversion for all images
 *
 * @package       GNWEBPCONV
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   GN WebP Conversion for all images
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-webp-conversion-for-all-images
 * Description:   Convert all images to WebP and remove original entirely
 * Version:       1.0.0
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   gn-webp-conversion-for-all-images
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with GN WebP Conversion for all images. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'GNWEBPCONV_NAME',			'GN WebP Conversion for all images' );

// Plugin version
define( 'GNWEBPCONV_VERSION',		'1.0.0' );

// Plugin Root File
define( 'GNWEBPCONV_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'GNWEBPCONV_PLUGIN_BASE',	plugin_basename( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNWEBPCONV_PLUGIN_DIR',	plugin_dir_path( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNWEBPCONV_PLUGIN_URL',	plugin_dir_url( GNWEBPCONV_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNWEBPCONV_PLUGIN_DIR . 'core/class-gn-webp-conversion-for-all-images.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Gn_Webp_Conversion_For_All_Images
 */
function GNWEBPCONV() {
	return Gn_Webp_Conversion_For_All_Images::instance();
}


/* 
** When I activate the plugin, make sure woocommerce is active and installed
** if not deactivate the plugin and show a notice
*/
function gnwebpconv_activation() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'Sorry, but this plugin requires the WooCommerce plugin to be installed and active.', 'gn-webp-conversion-for-all-images' ), 'gn-webp-conversion-for-all-images' );
	}
}
register_activation_hook( __FILE__, 'gnwebpconv_activation' );

GNWEBPCONV();
