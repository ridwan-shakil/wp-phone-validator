<?php

namespace WPPhoneValidator\Admin;

if (! defined('ABSPATH')) {
    exit;
}

class Settings2 {

    /**
     * Option key used in wp_options.
     * @var string
     */
    private $option_key = 'wp_phone_validator_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Phone Validator Settings', 'wp-phone-validator'),
            __('Phone Validator', 'wp-phone-validator'),
            'manage_options',
            'wp-phone-validator',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting(
            'wp_phone_validator_group',
            $this->option_key,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => ['countries' => []],
            ]
        );
        // register_setting( $option_group:string, $option_name:string, $args:array )


        add_settings_section(
            'wp_phone_validator_section',
            __('General', 'wp-phone-validator'),
            '__return_false',
            'wp-phone-validator'
        );
        // add_settings_section( $id:string, $title:string, $callback:callable, $page:string, $args:array )


        add_settings_field(
            'countries',
            __('Allowed Countries', 'wp-phone-validator'),
            [$this, 'render_countries_field'],
            'wp-phone-validator',
            'wp_phone_validator_section',
            [
                'label_for' => 'wp-phone-validator-countries',
            ]
        );
        // add_settings_field( $id:string, $title:string, $callback:callable, $page:string, $section:string, $args:array )
    }


    /**
     * Load SelectWoo / Select2 only on our settings page and init it.
     * Prefers WC's selectWoo -> WP select2 -> vendor/select2 (installed via composer).
     * Clears the typed search text after selecting an item so the input looks like tag-style UX.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page.
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (! $screen || 'woocommerce_page_wp-phone-validator' !== $screen->id) {
                return;
            }
        }

        $init_options = [
            'placeholder'   => __('Type to search countries…', 'wp-phone-validator'),
            'allowClear'    => true,
            'closeOnSelect' => false,
            'width'         => '400px',
        ];
        $init_json = wp_json_encode($init_options);

        // Use nowdoc to avoid PHP variable interpolation inside JS (prevents warnings like "Undefined variable $search").
        $inline_init_js = <<<'JS'
jQuery(function($){
    var $el = $('#wp-phone-validator-countries');
    if (!$el.length) return;

    var initSelect = function() {
        attachClearHandler($el);
    };

    var attachClearHandler = function($sel) {
        $sel.on('select2:select', function(e) {
            var instance = $sel.data('select2');
            if ( instance ) {
                var search = (instance.dropdown && instance.dropdown.$search) || (instance.selection && instance.selection.$search);
                if ( search && search.length ) {
                    search.val('').trigger('input');
                } else {
                    $('.select2-container--open .select2-search__field').val('').trigger('input');
                }
            } else {
                $('.select2-container--open .select2-search__field').val('').trigger('input');
            }
        });

        $sel.on('select2:close', function() {
            $('.select2-container--open .select2-search__field').val('').trigger('input');
        });
    };

    if ( typeof $.fn.selectWoo === 'function' ) {
        $el.selectWoo(%INIT_JSON%);
        attachClearHandler($el);
        return;
    }

    if ( typeof $.fn.select2 === 'function' ) {
        if (!$el.hasClass('select2-hidden-accessible')) {
            $el.select2(%INIT_JSON%);
        }
        attachClearHandler($el);
        return;
    }

    // If neither exists, vendor fallback initialization will be added after vendor script loads.
});
JS;

        // Replace placeholder with actual JSON (safe insertion)
        $inline_init_js = str_replace('%INIT_JSON%', $init_json, $inline_init_js);

        // 1) Prefer WC selectWoo if registered
        if (wp_script_is('selectWoo', 'registered')) {
            wp_enqueue_script('selectWoo');
            if (wp_style_is('select2', 'registered')) {
                wp_enqueue_style('select2');
            }
            wp_add_inline_script('selectWoo', $inline_init_js);
            wp_add_inline_style('select2', '.wp-phone-validator-wrap .select2-container{ width:400px !important; }');
            return;
        }

        // 2) Next prefer WP-registered select2 if present
        if (wp_script_is('select2', 'registered')) {
            wp_enqueue_script('select2');
            if (wp_style_is('select2', 'registered')) {
                wp_enqueue_style('select2');
            }
            wp_add_inline_script('select2', $inline_init_js);
            wp_add_inline_style('select2', '.wp-phone-validator-wrap .select2-container{ width:400px !important; }');
            return;
        }

        // 3) Final fallback: use composer-installed select2 under vendor/select2/select2/dist
        $vendor_js_path  = WP_PHONE_VALIDATOR_PATH . 'vendor/select2/select2/dist/js/select2.min.js';
        $vendor_css_path = WP_PHONE_VALIDATOR_PATH . 'vendor/select2/select2/dist/css/select2.min.css';

        if (file_exists($vendor_js_path) && file_exists($vendor_css_path)) {
            $handle_js  = 'wp-pv-select2-vendor';
            $handle_css = 'wp-pv-select2-vendor-css';

            wp_register_script($handle_js, WP_PHONE_VALIDATOR_URL . 'vendor/select2/select2/dist/js/select2.min.js', ['jquery'], '4.0.13', true);
            wp_register_style($handle_css, WP_PHONE_VALIDATOR_URL . 'vendor/select2/select2/dist/css/select2.min.css', [], '4.0.13');

            wp_enqueue_script($handle_js);
            wp_enqueue_style($handle_css);

            // Vendor init: use nowdoc and replace JSON placeholder as well
            $vendor_init_js = <<<'VJS'
jQuery(function($){
    var $el = $('#wp-phone-validator-countries');
    if (!$el.length) return;
    if (!$el.hasClass('select2-hidden-accessible')) {
        $el.select2(%INIT_JSON%);
    }
    $el.on('select2:select', function() {
        var instance = $el.data('select2');
        var search = (instance && instance.dropdown && instance.dropdown.$search) || (instance && instance.selection && instance.selection.$search);
        if (search && search.length) {
            search.val('').trigger('input');
        } else {
            $('.select2-container--open .select2-search__field').val('').trigger('input');
        }
    });
    $el.on('select2:close', function() {
        $('.select2-container--open .select2-search__field').val('').trigger('input');
    });
});
VJS;

            $vendor_init_js = str_replace('%INIT_JSON%', $init_json, $vendor_init_js);
            wp_add_inline_script($handle_js, $vendor_init_js);

            wp_add_inline_style($handle_css, '.wp-phone-validator-wrap .select2-container{ width:400px !important; }');
            return;
        }

        // Nothing available — enqueue jquery and print console warning for debugging
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "console.warn('SelectWoo/select2 not found and vendor/select2 not present.');");
    }




    /**
     * Render the countries field as a tags-like multi-select.
     */
    public function render_countries_field() {
        $options       = get_option($this->option_key, []);
        $selected      = ! empty($options['countries']) ? (array) $options['countries'] : [];
        $all_countries = $this->get_countries();

        echo '<div class="wp-phone-validator-wrap">';
        // wc-enhanced-select helps Woo's admin auto-init in some contexts
        echo '<select id="wp-phone-validator-countries" name="' . esc_attr($this->option_key) . '[countries][]" multiple="multiple" class="wc-enhanced-select">';
        foreach ($all_countries as $code => $name) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($code),
                selected(in_array($code, $selected, true), true, false),
                esc_html($name)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Start typing to search, then click a country to add. Selected countries appear as removable tags.', 'wp-phone-validator') . '</p>';
        echo '</div>';
    }

    public function render_settings_page() {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Phone Validator Settings', 'wp-phone-validator'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_phone_validator_group');
                do_settings_sections('wp-phone-validator');
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    /**
     * Keep settings safe: accepts only valid ISO2 codes from the available list.
     */
    public function sanitize_settings($input) {
        $clean = ['countries' => []];

        $countries = isset($input['countries']) && is_array($input['countries']) ? $input['countries'] : [];

        $valid_codes = array_keys($this->get_countries());

        // Intersect and normalize case (keep uppercase codes)
        $filtered = array_values(array_unique(array_map('strtoupper', array_intersect($countries, $valid_codes))));

        $clean['countries'] = $filtered;
        return $clean;
    }

    /**
     * Get countries list, prefer WooCommerce's list, fallback to a minimal set.
     *
     * @return array
     */
    private function get_countries() {
        if (class_exists('\WC_Countries')) {
            $wc_countries = new \WC_Countries();
            return $wc_countries->get_countries();
        }

        return [
            'BD' => __('Bangladesh', 'wp-phone-validator'),
            'IN' => __('India', 'wp-phone-validator'),
            'US' => __('United States', 'wp-phone-validator'),
            'GB' => __('United Kingdom', 'wp-phone-validator'),
            'PK' => __('Pakistan', 'wp-phone-validator'),
        ];
    }
}
