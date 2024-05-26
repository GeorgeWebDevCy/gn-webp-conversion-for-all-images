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
define( 'GNWEBPCONV_NAME', 'GN WebP Conversion for all images' );

// Plugin version
define( 'GNWEBPCONV_VERSION', '1.0.0' );

// Plugin Root File
define( 'GNWEBPCONV_PLUGIN_FILE', __FILE__ );

// Plugin base
define( 'GNWEBPCONV_PLUGIN_BASE', plugin_basename( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNWEBPCONV_PLUGIN_DIR', plugin_dir_path( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNWEBPCONV_PLUGIN_URL', plugin_dir_url( GNWEBPCONV_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNWEBPCONV_PLUGIN_DIR . 'core/class-gn-webp-conversion-for-all-images.php';

/**
 * Add custom cron schedule interval
 */
function gnwebpconv_add_cron_interval($schedules) {
    $schedules['five_minutes'] = array(
        'interval' => 300, // 300 seconds = 5 minutes
        'display'  => esc_html__('Every Five Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'gnwebpconv_add_cron_interval');

/**
 * Schedule the cron job to do the conversion every 5 minutes
 */
function gnwebpconv_schedule_cron_job() {
    if ( ! wp_next_scheduled( 'gnwebpconv_do_conversion' ) ) {
        wp_schedule_event( time(), 'five_minutes', 'gnwebpconv_do_conversion' );
    }
}
add_action( 'init', 'gnwebpconv_schedule_cron_job' );

/**
 * The main function to load the only instance of our master class.
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Gn_Webp_Conversion_For_All_Images
 */
function GNWEBPCONV() {
    return Gn_Webp_Conversion_For_All_Images::instance();
}

/**
 * Activation hook: check for WooCommerce and initialize settings
 */
function gnwebpconv_activation() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Sorry, but this plugin requires the WooCommerce plugin to be installed and active.', 'gn-webp-conversion-for-all-images' ), 'gn-webp-conversion-for-all-images' );
    }

    // Log plugin activation
    error_log('[GN WebP Conversion] Plugin activated.');
    
    // Default settings
    $gnwebpconv_settings = get_option( 'gnwebpconv_settings' );
    if ( false === $gnwebpconv_settings ) {
        $gnwebpconv_settings = array(
            'gnwebpconv_quality' => 80,
            'gnwebpconv_library' => 'gd',
        );
        update_option( 'gnwebpconv_settings', $gnwebpconv_settings );
    }

    // Check for GD or Imagick library
    if ( extension_loaded( 'gd' ) ) {
        $gnwebpconv_settings['gnwebpconv_library'] = 'gd';
    } elseif ( extension_loaded( 'imagick' ) ) {
        $gnwebpconv_settings['gnwebpconv_library'] = 'imagick';
    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Sorry, but this plugin requires the GD or Imagick library to be installed and active.', 'gn-webp-conversion-for-all-images' ), 'gn-webp-conversion-for-all-images' );
    }

    update_option( 'gnwebpconv_settings', $gnwebpconv_settings );
    gnwebpconv_schedule_cron_job();
}
register_activation_hook( __FILE__, 'gnwebpconv_activation' );

/**
 * Deactivation hook: clear cron job and delete settings
 */
function gnwebpconv_deactivation() {
    delete_option( 'gnwebpconv_settings' );
    wp_clear_scheduled_hook('gnwebpconv_do_conversion');
}
register_deactivation_hook( __FILE__, 'gnwebpconv_deactivation' );

/**
 * Create an admin page for the plugin
 */
function gnwebpconv_admin_menu() {
    add_options_page( 'GN WebP Conversion for all images', 'GN WebP Conversion for all images', 'manage_options', 'gnwebpconv', 'gnwebpconv_admin_page' );
}
add_action( 'admin_menu', 'gnwebpconv_admin_menu' );

function gnwebpconv_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php echo GNWEBPCONV_NAME; ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'gnwebpconv_settings' );
            do_settings_sections( 'gnwebpconv_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function gnwebpconv_settings_init() {
    register_setting( 'gnwebpconv_settings', 'gnwebpconv_settings' );

    add_settings_section( 'gnwebpconv_settings_section', __( 'Settings', 'gn-webp-conversion-for-all-images' ), 'gnwebpconv_settings_section_callback', 'gnwebpconv_settings' );

    add_settings_field( 'gnwebpconv_quality', __( 'Quality', 'gn-webp-conversion-for-all-images' ), 'gnwebpconv_quality_render', 'gnwebpconv_settings', 'gnwebpconv_settings_section' );
    add_settings_field( 'gnwebpconv_library', __( 'Library', 'gn-webp-conversion-for-all-images' ), 'gnwebpconv_library_render', 'gnwebpconv_settings', 'gnwebpconv_settings_section' );
}

function gnwebpconv_settings_section_callback() {
    echo __( 'Settings for GN WebP Conversion for all images', 'gn-webp-conversion-for-all-images' );
}

function gnwebpconv_quality_render() {
    $options = get_option( 'gnwebpconv_settings' );
    ?>
    <input type="number" name="gnwebpconv_settings[gnwebpconv_quality]" min="1" max="100" step="1" value="<?php echo $options['gnwebpconv_quality']; ?>">
    <?php
}

function gnwebpconv_library_render() {
    $options = get_option( 'gnwebpconv_settings' );
    ?>
    <select name="gnwebpconv_settings[gnwebpconv_library]">
        <option value="gd" <?php selected( $options['gnwebpconv_library'], 'gd' ); ?>>GD</option>
        <option value="imagick" <?php selected( $options['gnwebpconv_library'], 'imagick' ); ?>>Imagick</option>
    </select>
    <?php
}

add_action( 'admin_init', 'gnwebpconv_settings_init' );

/**
 * Add a link to the settings page to the plugins page
 */
function gnwebpconv_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=gnwebpconv">' . __( 'Settings', 'gn-webp-conversion-for-all-images' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gnwebpconv_settings_link' );

/**
 * Do the conversion based on the settings the user has chosen
 */
function gnwebpconv_do_conversion() {
    error_log('[GN WebP Conversion] Starting conversion process.');

    $gnwebpconv_settings = get_option('gnwebpconv_settings');
    $gnwebpconv_quality = $gnwebpconv_settings['gnwebpconv_quality'];
    $gnwebpconv_library = $gnwebpconv_settings['gnwebpconv_library'];

    $images = get_posts(array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_wp_attachment_metadata',
                'value'   => 'webp',
                'compare' => 'NOT LIKE',
            ),
        ),
    ));

    error_log('[GN WebP Conversion] Found ' . count($images) . ' images to process.');

    foreach ($images as $image) {
        $image_path = get_attached_file($image->ID);
        error_log('[GN WebP Conversion] Processing image: ' . $image_path);

        $image_extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        if (in_array($image_extension, ['jpg', 'jpeg', 'png']) && !file_exists($image_path . '.webp')) {
            error_log('[GN WebP Conversion] Converting image: ' . $image_path);

            if ($gnwebpconv_library === 'gd') {
                if ($image_extension === 'jpg' || $image_extension === 'jpeg') {
                    $gd_image = imagecreatefromjpeg($image_path);
                } elseif ($image_extension === 'png') {
                    $gd_image = imagecreatefrompng($image_path);
                }
                imagewebp($gd_image, $image_path . '.webp', $gnwebpconv_quality);
                imagedestroy($gd_image);
            } elseif ($gnwebpconv_library === 'imagick') {
                $imagick_image = new Imagick($image_path);
                $imagick_image->setImageFormat('webp');
                $imagick_image->setImageCompressionQuality($gnwebpconv_quality);
                $imagick_image->writeImage($image_path . '.webp');
                $imagick_image->clear();
                $imagick_image->destroy();
            }

            $attachment_meta = wp_get_attachment_metadata($image->ID);
            $attachment_meta['sizes']['webp'] = array(
                'file'      => basename($image_path . '.webp'),
                'width'     => $attachment_meta['width'],
                'height'    => $attachment_meta['height'],
                'mime-type' => 'image/webp',
            );
            wp_update_attachment_metadata($image->ID, $attachment_meta);

            unlink($image_path);
        }
    }
}
add_action('gnwebpconv_do_conversion', 'gnwebpconv_do_conversion');
