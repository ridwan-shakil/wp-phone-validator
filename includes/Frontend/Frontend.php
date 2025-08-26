<?php

namespace WPPhoneValidator\Frontend;

use WPPhoneValidator\Frontend\Validator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles frontend scripts and checkout validation.
 */
class Frontend {

    public function __construct() {
        // Enqueue frontend scripts only on checkout page
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // WooCommerce checkout process validation
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout']);

        // Restrict available billing countries to admin-selected ones
        add_filter('woocommerce_countries_allowed_countries', [$this, 'restrict_checkout_countries']);
    }



    /**
     * Restrict available billing countries to admin-selected ones
     */
    public function restrict_checkout_countries($countries) {
        $options   = get_option('wp_phone_validator_settings', []);
        $allowed   = ! empty($options['countries']) ? $options['countries'] : ['BD'];

        $allowed = array_map('strtoupper', $allowed);

        return array_intersect_key($countries, array_flip($allowed));
    }


    /**
     * Enqueue frontend JS/CSS for phone validation
     */
    public function enqueue_scripts() {
        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }

        // Load intl-tel-input for formatting & validation
        wp_enqueue_script(
            'intl-tel-input',
            'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js',
            [],
            '17.0.13',
            true
        );

        wp_enqueue_style(
            'intl-tel-input-css',
            'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.min.css',
            [],
            '17.0.13'
        );

        // Plugin frontend JS
        wp_enqueue_script(
            'wp-phone-validator',
            WP_PHONE_VALIDATOR_URL . 'assets/js/checkout-validation.js',
            ['jquery', 'intl-tel-input'],
            WP_PHONE_VALIDATOR_VERSION,
            true
        );

        // Admin-selected allowed countries
        $options   = get_option('wp_phone_validator_settings', []);
        $countries = ! empty($options['countries']) ? $options['countries'] : ['BD'];

        // Pass data to JS
        wp_localize_script('wp-phone-validator', 'wpPhoneValidator', [
            'defaultCountry'   => $countries[0],
            'allowedCountries' => $countries,
            'messages' => [
                'invalidForCountry' => __(' Phone number does not match the selected country.', 'wp-phone-validator'),
                'notMobile'         => __(' Phone number is not a valid mobile number.', 'wp-phone-validator'),
                'invalidFormat'     => __(' Invalid phone number format.', 'wp-phone-validator'),
                'countryNotAllowed' => __(' This country is not allowed for phone number validation.', 'wp-phone-validator'),
            ],
        ]);
    }

    /**
     * Server-side WooCommerce checkout validation
     */
    public function validate_checkout() {
        $phone   = isset($_POST['billing_phone']) ? sanitize_text_field(wp_unslash($_POST['billing_phone'])) : '';
        $country = isset($_POST['billing_country']) ? sanitize_text_field(wp_unslash($_POST['billing_country'])) : '';

        if (empty($phone) || empty($country)) {
            return;
        }

        // Admin-selected allowed countries
        $options   = get_option('wp_phone_validator_settings', []);
        $countries = ! empty($options['countries']) ? $options['countries'] : ['BD'];

        $validator = new Validator();
        $result    = $validator->validate_number($phone, $countries, $country);

        if (! $result['valid']) {
            wc_add_notice(esc_html($result['message']), 'error');
        } else {
            // Save normalized E.164 format
            $_POST['billing_phone'] = $result['formatted'];
        }
    }
}
