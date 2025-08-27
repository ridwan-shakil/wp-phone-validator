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


        new \WPPhoneValidator\Admin\settings();
        // new \WPPhoneValidator\Admin\Settings2();

    }

    if (class_exists('WooCommerce')) {
        new \WPPhoneValidator\Frontend\Frontend();
    }
}
add_action('plugins_loaded', 'wp_phone_validator_init');




