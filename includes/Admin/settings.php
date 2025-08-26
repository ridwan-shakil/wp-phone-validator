<?php

namespace WPPhoneValidator\Admin;

// Settings Page: Phone Validation
class settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'wph_create_settings'));
        add_action('admin_init', array($this, 'wph_setup_sections'));
        add_action('admin_init', array($this, 'wph_setup_fields'));
    }

    public function wph_create_settings() {
        $page_title = 'Allow Orders From Below Countries';
        $menu_title = 'Valid Phone Countries';
        $capability = 'manage_options';
        $slug       = 'wph_custom';
        $callback   = array($this, 'wph_settings_content');

        add_options_page($page_title, $menu_title, $capability, $slug, $callback);
    }

    public function wph_settings_content() { ?>
        <div class="wrap">
            <h1><?php esc_html_e('Allow Orders From Below Countries', 'wp-phone-validator'); ?></h1>
            <?php //settings_errors(); ?>
            <form method="POST" action="options.php">
                <?php
                settings_fields('wph_custom');
                do_settings_sections('wph_custom');
                submit_button();
                ?>
            </form>
        </div>
<?php }

    public function wph_setup_sections() {
        add_settings_section(
            'wph_custom_section',
            __('Orders will only be accepted from your selected countries', 'wp-phone-validator'),
            '__return_false',
            'wph_custom'
        );
    }

    public function wph_setup_fields() {
        $fields = array(
            array(
                'label'   => __('Allow Orders From', 'wp-phone-validator'),
                'id'      => 'allowordersfrom_multiselect',
                'type'    => 'multiselect',
                'section' => 'wph_custom_section',
                'options' => $this->get_countries()
            ),
        );

        foreach ($fields as $field) {
            add_settings_field(
                $field['id'],
                $field['label'],
                array($this, 'wph_field_callback'),
                'wph_custom',
                $field['section'],
                $field
            );

            register_setting(
                'wph_custom',
                $field['id'],
                array(
                    'sanitize_callback' => array($this, 'sanitize_array')
                )
            );
        }
    }

    public function wph_field_callback($field) {
        $value       = get_option($field['id']);
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';

        switch ($field['type']) {
            case 'select':
            case 'multiselect':
                if (! empty($field['options']) && is_array($field['options'])) {
                    $attr    = '';
                    $options = '';

                    foreach ($field['options'] as $key => $label) {
                        $selected = '';
                        if (is_array($value) && in_array($key, $value)) {
                            $selected = 'selected="selected"';
                        } elseif ($value == $key) {
                            $selected = 'selected="selected"';
                        }

                        $options .= sprintf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($key),
                            $selected,
                            esc_html($label)
                        );
                    }

                    if ($field['type'] === 'multiselect') {
                        $attr = ' multiple="multiple" ';
                    }

                    printf(
                        '<select name="%1$s%4$s" id="%1$s" %2$s>%3$s</select>',
                        esc_attr($field['id']),
                        $attr,
                        $options,
                        ($field['type'] === 'multiselect' ? '[]' : '')
                    );
                }
                break;

            default:
                printf(
                    '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />',
                    esc_attr($field['id']),
                    esc_attr($field['type']),
                    esc_attr($placeholder),
                    esc_attr($value)
                );
        }

        if (isset($field['desc']) && $field['desc']) {
            printf('<p class="description">%s</p>', esc_html($field['desc']));
        }
    }

    /**
     * Sanitize array values properly
     */
    public function sanitize_array($input) {
        if (is_array($input)) {
            return array_map('sanitize_text_field', $input);
        }
        return sanitize_text_field($input);
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
