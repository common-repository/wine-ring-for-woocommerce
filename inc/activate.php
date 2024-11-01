<?php
/**
 * @package WineRingForWooCommerce
 */

class WineRingForWooCommerceActivate
{

    public static function activate($wr4wc_db_version) {
        // Set default option values if not already set.
        $required_keys_with_default_values = self::getRequiredKeysWithDefaultValues();

        $wr4wc_options = get_option( 'wr4wc_plugin_options', array() );
        foreach ( $required_keys_with_default_values AS $required_key=>$required_value ) {
            if ( !array_key_exists($required_key, $wr4wc_options) ) {
                $wr4wc_options[$required_key] = $required_value;
                update_option('wr4wc_plugin_options', $wr4wc_options);
            }
        }

        $wr4wc_options["last_updated_at"] = date('Y-m-d H:i:s');
        update_option('wr4wc_plugin_options', $wr4wc_options);

        update_option( "wr4wc_db_version", $wr4wc_db_version );

        flush_rewrite_rules();
    }

    public static function updateDB($wr4wc_db_version) {
        self::activate($wr4wc_db_version);
    }

    private static function getRequiredKeysWithDefaultValues() {
        $required_keys_with_default_values = array(
            "api_feed_key" => "",
            "api_feed_custom_field_slugs" => "",
            "api_feed_category_ids" => "",
            "api_token" => "",
            'channel_id' => "SET_CHANNEL_#_HERE",
            'placeholder_ids' => "",
            'client_interface' => get_site_url(),
            'crop_fill_hex_color' => '#FFFFFF',
            'force_square_images' => "0",
            'default_max_image_height' => 600,
            'default_max_image_width' => 600,
            'label_whitelisted_category_ids' => "",
            'unique_identifier_method' => "product_id"
        );

        return $required_keys_with_default_values;
    }
}