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
define( 'GNWEBPCONV_NAME',            'GN WebP Conversion for all images' );

// Plugin version
define( 'GNWEBPCONV_VERSION',        '1.0.0' );

// Plugin Root File
define( 'GNWEBPCONV_PLUGIN_FILE',    __FILE__ );

// Plugin base
define( 'GNWEBPCONV_PLUGIN_BASE',    plugin_basename( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNWEBPCONV_PLUGIN_DIR',        plugin_dir_path( GNWEBPCONV_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNWEBPCONV_PLUGIN_URL',        plugin_dir_url( GNWEBPCONV_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNWEBPCONV_PLUGIN_DIR . 'core/class-gn-webp-conversion-for-all-images.php';

/**
 * shedule the cron job to do the conversion ecery 5 minutes use a plugin related prefix for the cron job
 */
function gnwebpconv_schedule_cron_job() {
    if ( ! wp_next_scheduled( 'gnwebpconv_do_conversion' ) ) {
        wp_schedule_event( time(), 'five_minutes', 'gnwebpconv_do_conversion' );
    }
}
add_action( 'init', 'gnwebpconv_schedule_cron_job' );

 

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
** When I activate the plugin, make sure WooCommerce is active and installed
** if not deactivate the plugin and show a notice
*/
function gnwebpconv_activation() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Sorry, but this plugin requires the WooCommerce plugin to be installed and active.', 'gn-webp-conversion-for-all-images' ), 'gn-webp-conversion-for-all-images' );
    }

    // Log plugin activation
    error_log('[GN WebP Conversion] Plugin activated.');
    //make sure the plugin settings are set to the default values when the plugin is activated for the first time unless the user has changed them
    $gnwebpconv_settings = get_option( 'gnwebpconv_settings' );
    if ( false === $gnwebpconv_settings ) {
        $gnwebpconv_settings = array(
            'gnwebpconv_quality' => 80,
            'gnwebpconv_library' => 'gd',
        );
        update_option( 'gnwebpconv_settings', $gnwebpconv_settings );
    }

    // Check if the GD or magick library is installed and activate the one that is installed by default the GD library should be activated first if both are installed
    if ( extension_loaded( 'gd' ) ) {
        $gnwebpconv_settings = array(
            'gnwebpconv_quality' => 80,
            'gnwebpconv_library' => 'gd',
        );
    } elseif ( extension_loaded( 'imagick' ) ) {
        $gnwebpconv_settings = array(
            'gnwebpconv_quality' => 80,
            'gnwebpconv_library' => 'imagick',
        );
    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Sorry, but this plugin requires the GD or Imagick library to be installed and active.', 'gn-webp-conversion-for-all-images' ), 'gn-webp-conversion-for-all-images' );
    }
}


register_activation_hook( __FILE__, 'gnwebpconv_activation' );



/**
 * Create an admin page for the plugin
 * make sure it has a setting to allow the user to change the quality of the images default should be 80
 * also display which webp conversion libraries are installed and allow the user to choose which one to use default should be gd library
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
 * On deactivation of the plugin, remove the settings
 */
function gnwebpconv_deactivation() {
    delete_option( 'gnwebpconv_settings' );
}
register_deactivation_hook( __FILE__, 'gnwebpconv_deactivation' );

/**
 * do the conversion based on the settings the user has chosen
 * make sure the original image is deleted after successful conversion
 * make sure the image is converted only once
 * make sure the image is converted only if it is a jpg or png
 * make sure the image is converted only if it is not a webp
 * do needed database changes
 * use a cron job to do the conversion so that the user can see the progress of the conversion
 */
function gnwebpconv_do_conversion() {
    $gnwebpconv_settings = get_option( 'gnwebpconv_settings' );
    $gnwebpconv_quality = $gnwebpconv_settings['gnwebpconv_quality'];
    $gnwebpconv_library = $gnwebpconv_settings['gnwebpconv_library'];

    //get all the images that are not webp
    $images = get_posts( array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wp_attachment_metadata',
                'value' => 'webp',
                'compare' => 'NOT LIKE',
            ),
        ),
    ) );

    //loop through the images and convert them
    foreach ( $images as $image ) {
        //get the image path
        $image_path = get_attached_file( $image->ID );

        //get the image extension
        $image_extension = pathinfo( $image_path, PATHINFO_EXTENSION );

        //check if the image is a jpg or png
        if ( 'jpg' === $image_extension || 'jpeg' === $image_extension || 'png' === $image_extension ) {
            //check if the image is already converted
            if ( ! file_exists( $image_path . '.webp' ) ) {
                //check if the image is a jpg or png
                if ( 'jpg' === $image_extension || 'jpeg' === $image_extension ) {
                    //convert the image to webp
                    if ( 'gd' === $gnwebpconv_library ) {
                        //convert the image to webp using the gd library
                        $gd_image = imagecreatefromjpeg( $image_path );
                        imagewebp( $gd_image, $image_path . '.webp', $gnwebpconv_quality );
                        imagedestroy( $gd_image );
                    } elseif ( 'imagick' === $gnwebpconv_library ) {
                        //convert the image to webp using the imagick library
                        $imagick_image = new Imagick( $image_path );
                        $imagick_image->setImageFormat( 'webp' );
                        $imagick_image->setImageCompressionQuality( $gnwebpconv_quality );
                        $imagick_image->writeImage( $image_path . '.webp' );
                        $imagick_image->clear();
                        $imagick_image->destroy();
                    }
                } elseif ( 'png' === $image_extension ) {

                    //convert the image to webp using the gd library
                    $gd_image = imagecreatefrompng( $image_path );
                    imagewebp( $gd_image, $image_path . '.webp', $gnwebpconv_quality );
                    imagedestroy( $gd_image );
                }

                //update the database to show that the image is converted
                $attachment_meta = wp_get_attachment_metadata( $image->ID );
                $attachment_meta['sizes']['webp'] = array(
                    'file' => $image->post_name . '.webp',
                    'width' => $attachment_meta['width'],
                    'height' => $attachment_meta['height'],
                    'mime-type' => 'image/webp',
                );
                wp_update_attachment_metadata( $image->ID, $attachment_meta );

                //delete the original image
                unlink( $image_path );
            }
        }
    }
}

/**
 * Add a notice to the admin dashboard to show the progress of the conversion
 */
function gnwebpconv_admin_notice() {
    $gnwebpconv_settings = get_option( 'gnwebpconv_settings' );
    $gnwebpconv_quality = $gnwebpconv_settings['gnwebpconv_quality'];
    $gnwebpconv_library = $gnwebpconv_settings['gnwebpconv_library'];

    //get all the images that are not webp
    $images = get_posts( array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wp_attachment_metadata',
                'value' => 'webp',
                'compare' => 'NOT LIKE',
            ),
        ),
    ) );

    //get the total number of images
    $total_images = count( $images );

    //get the number of images that are converted
    $converted_images = 0;
    foreach ( $images as $image ) {
        //get the image path
        $image_path = get_attached_file( $image->ID );

        //get the image extension
        $image_extension = pathinfo( $image_path, PATHINFO_EXTENSION );

        //check if the image is a jpg or png
        if ( 'jpg' === $image_extension || 'jpeg' === $image_extension || 'png' === $image_extension ) {
            //check if the image is already converted
            if ( file_exists( $image_path . '.webp' ) ) {
                $converted_images++;
            }
        }
    }

    //check if the conversion is complete
    if ( $total_images === $converted_images ) {
        //remove the notice
        remove_action( 'admin_notices', 'gnwebpconv_admin_notice' );
    } else {
        //show the notice
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo sprintf( __( 'GN WebP Conversion for all images is converting images to WebP. %s out of %s images are converted.', 'gn-webp-conversion-for-all-images' ), $converted_images, $total_images ); ?></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'gnwebpconv_admin_notice' );

GNWEBPCONV();
