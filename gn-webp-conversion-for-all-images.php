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
    $settings_link = '<a href="options-general.php?page=gnwebpconv
