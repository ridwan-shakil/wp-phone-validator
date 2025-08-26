<?php

namespace WPPhoneValidator\Frontend;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberFormat;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles phone number validation using libphonenumber
 * Number must be valid according to libphonenumber.
 * Number must match the selected checkout country.
 * Checkout country itself must be in admin-allowed countries.
 */
class Validator {

    /**
     * Validate a phone number
     *
     * @param string $phone Raw phone number input
     * @param array  $allowed_countries Admin-selected countries (ISO2 codes, e.g. ['BD','US'])
     * @param string $billing_country WooCommerce checkout country (ISO2 code)
     * @return array Validation result
     */
    public function validate_number($phone, $allowed_countries = [], $billing_country = '') {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phone     = trim($phone);

        // Normalize country code to uppercase
        $billing_country = strtoupper($billing_country);
        $allowed_countries = array_map('strtoupper', $allowed_countries);

        // Rule 1: If billing country not in admin-allowed list
        if (! in_array($billing_country, $allowed_countries, true)) {
            return [
                'valid'     => false,
                'formatted' => '',
                'country'   => $billing_country,
                'type'      => '',
                'message'   => __('This country is not allowed for phone number validation.', 'wp-phone-validator'),
            ];
        }

        try {
            // Parse phone against billing country
            $proto = $phoneUtil->parse($phone, $billing_country);

            // Rule 2: Must be valid for selected billing country
            if (! $phoneUtil->isValidNumberForRegion($proto, $billing_country)) {
                return [
                    'valid'     => false,
                    'formatted' => '',
                    'country'   => $billing_country,
                    'type'      => '',
                    'message'   => __('Phone number does not match the selected country.', 'wp-phone-validator'),
                ];
            }

            // Rule 3: Must be mobile or fixed-line mobile
            $numberType = $phoneUtil->getNumberType($proto);
            if ($numberType === PhoneNumberType::MOBILE || $numberType === PhoneNumberType::FIXED_LINE_OR_MOBILE) {
                return [
                    'valid'     => true,
                    'formatted' => $phoneUtil->format($proto, PhoneNumberFormat::E164),
                    'country'   => $billing_country,
                    'type'      => $numberType,
                    'message'   => '',
                ];
            }

            return [
                'valid'     => false,
                'formatted' => '',
                'country'   => $billing_country,
                'type'      => $numberType,
                'message'   => __('Phone number is not a valid mobile number.', 'wp-phone-validator'),
            ];
        } catch (NumberParseException $e) {
            return [
                'valid'     => false,
                'formatted' => '',
                'country'   => $billing_country,
                'type'      => '',
                'message'   => __('Invalid phone number format.', 'wp-phone-validator'),
            ];
        }
    }
}
