<?php
/**
 * GN WebP Conversion for all images
 *
 * @package       GNWEBPCONV
 * @version       1.1.9
 *
 * @wordpress-plugin
 * Plugin Name:   GN WebP Conversion for all images
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-webp-conversion-for-all-images
 * Description:   Convert all images to WebP and optionally remove original entirely
 * Version:       1.1.9
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   gn-webp-conversion-for-all-images
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

// Plugin constants
define('GNWEBPCONV_NAME', 'GN WebP Conversion for all images');
define('GNWEBPCONV_VERSION', '1.1.9');
define('GNWEBPCONV_PLUGIN_FILE', __FILE__);
define('GNWEBPCONV_PLUGIN_BASE', plugin_basename(GNWEBPCONV_PLUGIN_FILE));
define('GNWEBPCONV_PLUGIN_DIR', plugin_dir_path(GNWEBPCONV_PLUGIN_FILE));
define('GNWEBPCONV_PLUGIN_URL', plugin_dir_url(GNWEBPCONV_PLUGIN_FILE));
define('GNWEBPCONV_LOG_FILE', WP_CONTENT_DIR . '/uploads/gn-webp-conversion/log.txt');

// Include the main class for core functionality
require_once GNWEBPCONV_PLUGIN_DIR . 'core/class-gn-webp-conversion-for-all-images.php';

// Add custom cron schedule interval
function gnwebpconv_add_cron_interval($schedules)
{
    $schedules['five_minutes'] = array(
        'interval' => 300, // 300 seconds = 5 minutes
        'display' => esc_html__('Every Five Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'gnwebpconv_add_cron_interval');

// Schedule the cron job to do the conversion every 5 minutes
function gnwebpconv_schedule_cron_job()
{
    if (!wp_next_scheduled('gnwebpconv_do_conversion')) {
        wp_schedule_event(time(), 'five_minutes', 'gnwebpconv_do_conversion');
    }
}
add_action('init', 'gnwebpconv_schedule_cron_job');

// Main function to load the only instance of the master class
function GNWEBPCONV()
{
    return Gn_Webp_Conversion_For_All_Images::instance();
}

// Activation hook: check for WooCommerce and initialize settings
function gnwebpconv_activation()
{
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Sorry, but this plugin requires the WooCommerce plugin to be installed and active.', 'gn-webp-conversion-for-all-images'), 'gn-webp-conversion-for-all-images');
    }

    $gnwebpconv_settings = get_option('gnwebpconv_settings', array(
        'gnwebpconv_quality' => 80,
        'gnwebpconv_library' => 'gd',
        'gnwebpconv_preserve_original' => 1,
    ));

    if (extension_loaded('gd')) {
        $gnwebpconv_settings['gnwebpconv_library'] = 'gd';
    } elseif (extension_loaded('imagick')) {
        $gnwebpconv_settings['gnwebpconv_library'] = 'imagick';
    } else {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Sorry, but this plugin requires the GD or Imagick library to be installed and active.', 'gn-webp-conversion-for-all-images'), 'gn-webp-conversion-for-all-images');
    }

    update_option('gnwebpconv_settings', $gnwebpconv_settings);
    gnwebpconv_schedule_cron_job();
}
register_activation_hook(__FILE__, 'gnwebpconv_activation');

// Deactivation hook: clear cron job and delete settings
function gnwebpconv_deactivation()
{
    delete_option('gnwebpconv_settings');
    wp_clear_scheduled_hook('gnwebpconv_do_conversion');
}
register_deactivation_hook(__FILE__, 'gnwebpconv_deactivation');

// Create an admin page for the plugin
function gnwebpconv_admin_menu()
{
    add_options_page('GN WebP Conversion for all images', 'GN WebP Conversion for all images', 'manage_options', 'gnwebpconv', 'gnwebpconv_admin_page');
}
add_action('admin_menu', 'gnwebpconv_admin_menu');

function gnwebpconv_admin_page()
{
    ?>
    <div class="wrap">
        <h1><?php echo GNWEBPCONV_NAME; ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gnwebpconv_settings');
            do_settings_sections('gnwebpconv_settings');
            submit_button();
            ?>
        </form>
        <h2>Log</h2>
        <textarea rows="20" cols="100"><?php echo esc_textarea(gnwebpconv_read_log()); ?></textarea>
        <form method="post" action="">
            <input type="hidden" name="gnwebpconv_clear_log" value="1">
            <?php submit_button('Clear Log'); ?>
        </form>
    </div>
    <?php
    if (isset($_POST['gnwebpconv_clear_log'])) {
        gnwebpconv_clear_log();
    }
}

function gnwebpconv_read_log()
{
    if (file_exists(GNWEBPCONV_LOG_FILE)) {
        return file_get_contents(GNWEBPCONV_LOG_FILE);
    }
    return '';
}

function gnwebpconv_clear_log()
{
    if (file_exists(GNWEBPCONV_LOG_FILE)) {
        file_put_contents(GNWEBPCONV_LOG_FILE, '');
    }
}

// Initialize plugin settings
function gnwebpconv_settings_init()
{
    register_setting('gnwebpconv_settings', 'gnwebpconv_settings', 'gnwebpconv_settings_sanitize');

    add_settings_section('gnwebpconv_settings_section', __('Settings', 'gn-webp-conversion-for-all-images'), 'gnwebpconv_settings_section_callback', 'gnwebpconv_settings');

    add_settings_field('gnwebpconv_quality', __('Quality', 'gn-webp-conversion-for-all-images'), 'gnwebpconv_quality_render', 'gnwebpconv_settings', 'gnwebpconv_settings_section');
    add_settings_field('gnwebpconv_library', __('Library', 'gn-webp-conversion-for-all-images'), 'gnwebpconv_library_render', 'gnwebpconv_settings', 'gnwebpconv_settings_section');
    add_settings_field('gnwebpconv_preserve_original', __('Preserve Original Images', 'gn-webp-conversion-for-all-images'), 'gnwebpconv_preserve_original_render', 'gnwebpconv_settings', 'gnwebpconv_settings_section');
}

function gnwebpconv_settings_section_callback()
{
    echo __('Settings for GN WebP Conversion for all images', 'gn-webp-conversion-for-all-images');
}

function gnwebpconv_quality_render()
{
    $options = get_option('gnwebpconv_settings');
    ?>
    <input type="number" name="gnwebpconv_settings[gnwebpconv_quality]" min="1" max="100" step="1" value="<?php echo isset($options['gnwebpconv_quality']) ? esc_attr($options['gnwebpconv_quality']) : '80'; ?>">
    <?php
}

function gnwebpconv_library_render()
{
    $options = get_option('gnwebpconv_settings');
    ?>
    <select name="gnwebpconv_settings[gnwebpconv_library]">
        <option value="gd" <?php selected($options['gnwebpconv_library'], 'gd'); ?>>GD</option>
        <option value="imagick" <?php selected($options['gnwebpconv_library'], 'imagick'); ?>>Imagick</option>
    </select>
    <?php
}

function gnwebpconv_preserve_original_render()
{
    $options = get_option('gnwebpconv_settings');
    ?>
    <input type="checkbox" name="gnwebpconv_settings[gnwebpconv_preserve_original]" value="1" <?php checked(isset($options['gnwebpconv_preserve_original']) ? $options['gnwebpconv_preserve_original'] : 1, 1); ?>>
    <?php
}

add_action('admin_init', 'gnwebpconv_settings_init');

// Sanitize the settings
function gnwebpconv_settings_sanitize($input)
{
    $input['gnwebpconv_quality'] = absint($input['gnwebpconv_quality']);
    $input['gnwebpconv_library'] = sanitize_text_field($input['gnwebpconv_library']);
    $input['gnwebpconv_preserve_original'] = isset($input['gnwebpconv_preserve_original']) ? 1 : 0;
    return $input;
}

// Add a link to the settings page to the plugins page
function gnwebpconv_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=gnwebpconv">' . __('Settings', 'gn-webp-conversion-for-all-images') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gnwebpconv_settings_link');

// Log messages to the plugin log file
function gnwebpconv_log($message)
{
    if (!file_exists(dirname(GNWEBPCONV_LOG_FILE))) {
        mkdir(dirname(GNWEBPCONV_LOG_FILE), 0755, true);
    }
    $timestamp = current_time('mysql');
    $log_entry = sprintf("[%s] %s\n", $timestamp, $message);
    file_put_contents(GNWEBPCONV_LOG_FILE, $log_entry, FILE_APPEND);
}

// Recursively scan directories for images
function gnwebpconv_scan_directory($directory)
{
    $images = array();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($files as $file) {
        if (in_array(strtolower($file->getExtension()), array('jpg', 'jpeg', 'png'))) {
            $images[] = $file->getPathname();
        }
    }
    return $images;
}

// Update image references in the database using find and replace
function gnwebpconv_update_image_references($old_url, $new_url)
{
    global $wpdb;

    gnwebpconv_log("Updating image references in the database from $old_url to $new_url");

    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    foreach ($tables as $table) {
        $table_name = $table[0];
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_N);
        foreach ($columns as $column) {
            $column_name = $column[0];
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE $column_name LIKE %s",
                    '%' . $wpdb->esc_like($old_url) . '%'
                ),
                ARRAY_A
            );

            foreach ($rows as $row) {
                $value = $row[$column_name];
                if (is_serialized($value)) {
                    $unserialized = @unserialize($value);
                    if ($unserialized !== false) {
                        $replaced = gnwebpconv_recursive_replace($old_url, $new_url, $unserialized);
                        $new_value = serialize($replaced);
                        if ($new_value !== $value) {
                            $wpdb->update(
                                $table_name,
                                array($column_name => $new_value),
                                array('ID' => $row['ID'])
                            );
                        }
                    }
                } else {
                    $new_value = str_replace($old_url, $new_url, $value);
                    if ($new_value !== $value) {
                        $wpdb->update(
                            $table_name,
                            array($column_name => $new_value),
                            array('ID' => $row['ID'])
                        );
                    }
                }
            }
        }
    }
}

function gnwebpconv_recursive_replace($find, $replace, $data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = gnwebpconv_recursive_replace($find, $replace, $value);
        }
    } elseif (is_string($data)) {
        $data = str_replace($find, $replace, $data);
    }
    return $data;
}

// Do the conversion based on the settings the user has chosen
function gnwebpconv_do_conversion()
{
    gnwebpconv_log('Starting conversion process.');

    $gnwebpconv_settings = get_option('gnwebpconv_settings');
    $gnwebpconv_quality = isset($gnwebpconv_settings['gnwebpconv_quality']) ? $gnwebpconv_settings['gnwebpconv_quality'] : 80;
    $gnwebpconv_library = isset($gnwebpconv_settings['gnwebpconv_library']) ? $gnwebpconv_settings['gnwebpconv_library'] : 'gd';
    $preserve_original = isset($gnwebpconv_settings['gnwebpconv_preserve_original']) ? $gnwebpconv_settings['gnwebpconv_preserve_original'] : 1;

    $uploads_dir = wp_get_upload_dir()['basedir'];
    $images = gnwebpconv_scan_directory($uploads_dir);

    gnwebpconv_log('Found ' . count($images) . ' images to process.');

    foreach ($images as $image_path) {
        gnwebpconv_log('Processing image: ' . $image_path);

        $image_extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        $webp_path = $image_path . '.webp';
        $relative_image_path = str_replace($uploads_dir, '', $image_path);
        $old_url = wp_get_upload_dir()['baseurl'] . $relative_image_path;
        $new_url = $old_url . '.webp';

        if (in_array($image_extension, ['jpg', 'jpeg', 'png']) && !file_exists($webp_path)) {
            gnwebpconv_log('Converting image: ' . $image_path);

            if ($gnwebpconv_library === 'gd') {
                if ($image_extension === 'jpg' || $image_extension === 'jpeg') {
                    $gd_image = imagecreatefromjpeg($image_path);
                } elseif ($image_extension === 'png') {
                    $gd_image = imagecreatefrompng($image_path);
                }
                imagewebp($gd_image, $webp_path, $gnwebpconv_quality);
                imagedestroy($gd_image);
            } elseif ($gnwebpconv_library === 'imagick') {
                $imagick_image = new Imagick($image_path);
                $imagick_image->setImageFormat('webp');
                $imagick_image->setImageCompressionQuality($gnwebpconv_quality);
                $imagick_image->writeImage($webp_path);
                $imagick_image->clear();
                $imagick_image->destroy();
            }

            // Update attachment metadata if this image is an attachment
            $attachment_id = attachment_url_to_postid($old_url);
            if ($attachment_id) {
                $attachment_meta = wp_get_attachment_metadata($attachment_id);

                foreach ($attachment_meta['sizes'] as $size => $size_info) {
                    $size_path = dirname($image_path) . '/' . $size_info['file'];
                    $size_webp_path = $size_path . '.webp';

                    if (!file_exists($size_webp_path)) {
                        if ($gnwebpconv_library === 'gd') {
                            if ($image_extension === 'jpg' || $image_extension === 'jpeg') {
                                $gd_image = imagecreatefromjpeg($size_path);
                            } elseif ($image_extension === 'png') {
                                $gd_image = imagecreatefrompng($size_path);
                            }
                            imagewebp($gd_image, $size_webp_path, $gnwebpconv_quality);
                            imagedestroy($gd_image);
                        } elseif ($gnwebpconv_library === 'imagick') {
                            $imagick_image = new Imagick($size_path);
                            $imagick_image->setImageFormat('webp');
                            $imagick_image->setImageCompressionQuality($gnwebpconv_quality);
                            $imagick_image->writeImage($size_webp_path);
                            $imagick_image->clear();
                            $imagick_image->destroy();
                        }
                    }

                    $attachment_meta['sizes'][$size]['webp'] = array(
                        'file' => basename($size_webp_path),
                        'width' => $size_info['width'],
                        'height' => $size_info['height'],
                        'mime-type' => 'image/webp',
                    );
                }

                wp_update_attachment_metadata($attachment_id, $attachment_meta);
            }

            // Update references in the database
            gnwebpconv_update_image_references($old_url, $new_url);

            // Update post content to reference the new URL
            gnwebpconv_update_post_content($old_url, $new_url);

            if (!$preserve_original) {
                gnwebpconv_log('Deleting original image: ' . $image_path);
                unlink($image_path);

                foreach ($attachment_meta['sizes'] as $size_info) {
                    $size_path = dirname($image_path) . '/' . $size_info['file'];
                    if (file_exists($size_path)) {
                        gnwebpconv_log('Deleting original size image: ' . $size_path);
                        unlink($size_path);
                    }
                }
            }
        }
    }
}
add_action('gnwebpconv_do_conversion', 'gnwebpconv_do_conversion');

function gnwebpconv_update_post_content($old_url, $new_url)
{
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)",
            $old_url,
            $new_url
        )
    );
}

?>