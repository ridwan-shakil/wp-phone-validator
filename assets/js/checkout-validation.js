jQuery(function ($) {
    const phoneInput = $('#billing_phone');
    const countryInput = $('#billing_country');
    const errorContainerId = 'phone-validation-error';

    function showError(message) {
        let errorEl = $('#' + errorContainerId);
        if (!errorEl.length) {
            errorEl = $('<div/>', {
                id: errorContainerId,
                class: 'woocommerce-error',
                css: { 'margin-top': '5px', 'font-size': '0.9em', 'color': 'red' }
            });
            phoneInput.after(errorEl);
        }
        errorEl.text(message).show();
    }

    function clearError() {
        $('#' + errorContainerId).remove();
    }

    function validatePhone() {
        clearError();

        const phone = phoneInput.val().trim();
        const country = countryInput.val() ? countryInput.val().toUpperCase() : '';
        const allowed = wpPhoneValidator.allowedCountries.map(c => c.toUpperCase());

        if (!phone) {
            return true; // Let WooCommerce handle empty field if required
        }

        if (!allowed.includes(country)) {
            showError(wpPhoneValidator.messages.countryNotAllowed);
            return false;
        }

        try {
            const parsed = libphonenumber.parsePhoneNumber(phone, country);

            if (!parsed.isValid()) {
                showError(wpPhoneValidator.messages.invalidForCountry);
                return false;
            }

            if (parsed.getType() !== 'MOBILE' && parsed.getType() !== 'FIXED_LINE_OR_MOBILE') {
                showError(wpPhoneValidator.messages.notMobile);
                return false;
            }

            // Auto-format number in E.164 format
            phoneInput.val(parsed.number);
            return true;

        } catch (e) {
            showError(wpPhoneValidator.messages.invalidFormat);
            return false;
        }
    }

    // Validate on blur and on checkout submit
    phoneInput.on('blur', validatePhone);
    $('form.checkout').on('checkout_place_order', validatePhone);
});
