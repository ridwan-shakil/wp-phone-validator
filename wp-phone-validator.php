<?php

/**
 * Plugin Name: WP Phone Validator
 * Description: Validate phone numbers on WooCommerce checkout based on countries selected by admin.
 * Version: 1.0.1
 * Author: MD. Ridwan
 * Text Domain: wp-phone-validator
 */

if (! defined('ABSPATH')) exit;

define('WP_PHONE_VALIDATOR_PATH', plugin_dir_path(__FILE__));
define('WP_PHONE_VALIDATOR_URL', plugin_dir_url(__FILE__));
define('WP_PHONE_VALIDATOR_VERSION', '1.0.1');

// ✅ Load Composer autoload (PSR-4 + libphonenumber)
if (file_exists(WP_PHONE_VALIDATOR_PATH . 'vendor/autoload.php')) {
    require_once WP_PHONE_VALIDATOR_PATH . 'vendor/autoload.php';
}

function wp_phone_validator_init() {
    load_plugin_textdomain('wp-phone-validator', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (is_admin()) {

        new \WPPhoneValidator\Admin\BoilerPlate();
        new \WPPhoneValidator\Admin\Settings();
        // new \WPPhoneValidator\Admin\Settings2();

        new \WPPhoneValidator\Admin\BookCPT();
        new \WPPhoneValidator\Admin\Metabox1();
    }

    if (class_exists('WooCommerce')) {
        new \WPPhoneValidator\Frontend\Frontend();
    }
}
add_action('plugins_loaded', 'wp_phone_validator_init');


// Show admin notice
function rs_show_admin_notice() {
    $class = 'notice notice-info is-dismissible';
    $message = __('Visit out website to buy the premium version of this plugin.', 'textdomain');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}
add_action('admin_notices', 'rs_show_admin_notice');

// ============================
/**
 * Undocumented function
 *
 * @param [type] $user
 * @return void
 */
function custom_user_fields_887($user) {

    $output = '';
    $output .= '<table class="form-table">';
    $output .= '<tr>';
    $output .= '<th><label for="nickname">' . __("Nick Name", "textdomain") . '</label></th>';
    $output .= '<td>';
    $output .= '<input placeholder="Nick Name" type="text" name="nickname" id="nickname" value="' . esc_attr(get_user_meta($user->ID, 'nickname', true)) . '" class="regular-text" /><br />';
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '<tr>';
    $output .= '<th><label for="BusinessEmail">' . __("Business Email", "textdomain") . '</label></th>';
    $output .= '<td>';
    $output .= '<input type="email" name="BusinessEmail" id="BusinessEmail" value="' . esc_attr(get_user_meta($user->ID, 'BusinessEmail', true)) . '" class="regular-text" /><br />';
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';

    echo $output;
}

add_action('show_user_profile', 'custom_user_fields_887');
add_action('edit_user_profile', 'custom_user_fields_887');

// Save Custom User Profile Fields
function custom_user_fields_save_887($user_id) {

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'nickname', $_POST['nickname']);
    update_user_meta($user_id, 'BusinessEmail', $_POST['BusinessEmail']);
}

add_action('personal_options_update', 'custom_user_fields_save_887');
add_action('edit_user_profile_update', 'custom_user_fields_save_887');

// ============================
// Add Custom Dashboard Widget
function add_custom_dashboard_widgets() {

    wp_add_dashboard_widget(
        'my_custom_widget',
        'My Custom Widget',
        'dashboard_widget_function'
    );
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widgets');


function dashboard_widget_function() {

    echo "Hi WordPress, I'm a custom Dashboard Widget from wp-hasty.com";
}
// ============================