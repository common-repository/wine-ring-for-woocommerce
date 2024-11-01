<?php
/*
Plugin Name: Wine Ring For WooCommerce
Plugin URI: https://www.winering.com
Description: Integrate Wine Ring functionality into your WooCommerce store.
Version: 2.3
Author: support@winering.com
License: The 3-Clause BSD License
License URI: https://opensource.org/licenses/BSD-3-Clause
*/

/*
Copyright (c) 2021 RingIT, Inc. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following
disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote
products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

defined('ABSPATH') || exit;
define("WR4WC_PLUGIN_VERSION", "2.3"); // increment when deploying new versions of the plugin to the public
define("WR4WC_DB_VERSION", "1.4"); // increment when there are changes to the DB (and update ~/inc/activate.php defaults)

class WineRingForWooCommerce
{
    public $plugin_name;

    function __construct()
    {
        $this->plugin_name = plugin_basename(__FILE__);
        add_action('init', array($this, 'setup_post_type'));
    }

    function register()
    {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        $base_name = add_filter('plugin_action_links_' . $this->plugin_name, array($this, 'settings_link'));
    }

    function activate()
    {
        $this->setup_post_type();
        require_once plugin_dir_path(__FILE__) . "/inc/activate.php";
        WineRingForWooCommerceActivate::activate(WR4WC_DB_VERSION);
    }

    function deactivate()
    {
        require_once plugin_dir_path(__FILE__) . "/inc/deactivate.php";
        WineRingForWooCommerceDeactivate::deactivate();
    }

    function add_admin_pages()
    {
        add_menu_page("Wine Ring for WooCommerce", "Wine Ring", "manage_options", "wr4wc-plugin", array($this, "admin_index"), "", 110);
        require_once plugin_dir_path(__FILE__) . "inc/admin.php";
    }

    function admin_index()
    {
        require_once plugin_dir_path(__FILE__) . "templates/admin.php";
    }

    function settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=wr4wc-plugin">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }

    function setup_post_type()
    {
        $args = array(
            'public'              => false, // bool (default is FALSE)
            'publicly_queryable'  => false, // bool (defaults to 'public').
            'exclude_from_search' => true, // bool (defaults to 'public')
            'show_in_nav_menus'   => false, // bool (defaults to 'public')
            'show_ui'             => false, // bool (defaults to 'public')
            'show_in_menu'        => false, // bool (defaults to 'show_ui')
            'show_in_admin_bar'   => false, // bool (defaults to 'show_in_menu')
            'menu_position'       => 30, // int (defaults to 25 - below comments)
            'menu_icon'           => null, // string (defaults to use the post icon)
            'can_export'          => false, // bool (defaults to TRUE)
            'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
            'hierarchical'        => false, // bool (defaults to FALSE)
            'has_archive'         => 'false', // bool|string (defaults to FALSE)
            'query_var'           => 'wine-ring-label', // bool|string (defaults to TRUE - post type name)
            'rewrite' => false,
            'supports' => array(
                'title',
                'custom-fields'
            )
        );

        /* Register the post type. */
        register_post_type(
            'wine-ring-label', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
            $args      // Arguments for post type.
        );
        require_once plugin_dir_path(__FILE__) . "/inc/wr-label-post-type.php";
    }

}

if (class_exists('WineRingForWooCommerce')) {
    $wineRingForWooCommerce = new WineRingForWooCommerce();
    $wineRingForWooCommerce->register();

    register_activation_hook(__FILE__, array($wineRingForWooCommerce, 'activate'));
    register_deactivation_hook(__FILE__, array($wineRingForWooCommerce, 'deactivate'));
}

add_filter( 'wp_get_attachment_image_src','wr4wc_change_product_image_link', 50, 4 );
function wr4wc_change_product_image_link( $image, $attachment_id, $size, $icon ){
    $wr4wc_options = get_option( 'wr4wc_plugin_options' );;
    if ( array_key_exists( "crop_fill_hex_color", $wr4wc_options) && strlen($wr4wc_options['crop_fill_hex_color'])>0 ){
        $color_hex_without_hashtag = str_replace("#", "", $wr4wc_options["crop_fill_hex_color"]);
    } else {
        $color_hex_without_hashtag = "FFFFFF";
    }

    if ( array_key_exists( "wr4wc_setting_default_max_image_width", $wr4wc_options) && strlen($wr4wc_options['wr4wc_setting_default_max_image_width'])>0 ){
        $width = $wr4wc_options["wr4wc_setting_default_max_image_width"];
    } else {
        $width = 500;
    }

    if ( array_key_exists( "wr4wc_setting_default_max_image_height", $wr4wc_options) && strlen($wr4wc_options['wr4wc_setting_default_max_image_height'])>0 ){
        $height = $wr4wc_options["wr4wc_setting_default_max_image_height"];
    } else {
        $height = 500;
    }

    $crop = false;
    if ( array_key_exists( "force_square_images", $wr4wc_options) && $wr4wc_options['force_square_images']>0 ){
        // $force_square = true;
        $crop = true;
    } else {
        // $force_square = false;
    }

    if ( is_array($size) && $size[0]>0 && $size[1]>0) {
        $width = $size[0];
        $height = $size[1];
        if ( !$crop && array_key_exists(2, $size) ) {
            $crop = $size[2];
        }
    } elseif ( is_string($size) ) {
        $image_size = wr4wc_get_image_sizes($size);
        if ( is_array($image_size) && array_key_exists("width", $image_size) ) {
            $width = $image_size["width"];
        }
        if ( is_array($image_size) && array_key_exists("height", $image_size) ) {
            $height = $image_size["height"];
        }
        if ( !$crop && is_array($image_size) && array_key_exists("crop", $image_size) ) {
            $crop = $image_size["crop"];
        }
    }
    if ( $crop ) {
        /*
        if ( $width == 0 || $height == 0 ) {
            $width = max($height, $width);
            $height = max($height, $width);
        }
        */
        $width = min($width, $height);
        $height = min($width, $height);
    }
    /*
    var_dump("size:".$width."x".$height . "&crop=". $crop);
    echo "<br/><br/>";
    */
    if ( $post = get_post( $attachment_id ) ) {
        if ( in_array(get_post_type( $post ), array( 'wine-ring-label') ) ) {
            if ($width + $height == 0) {
                if ($w = get_post_meta($attachment_id, '_width', true)) {
                    $width = $w;
                }
                if ($h = get_post_meta($attachment_id, '_height', true)) {
                    $height = $h;
                }
            }

            if ($path = get_post_meta($attachment_id, '_wp_attached_file', true)) {
                if ( $crop ) {
                    $path = str_replace("800x800/", "800x800/filters:fill(".$color_hex_without_hashtag.")/", $path);
                }
                if ( $height + $width > 0 ) {
                    $path = str_replace("800x800", $width . "x" . $height, $path);
                }
                $width = $width;
                $height = $height;
                $image = array($path, $width, $height);
            }
        }
    }
    //   var_dump($image);
    // wp_get_attachment_url

    return $image;
}

/**
 * Get information about available image sizes
 */
function wr4wc_get_image_sizes( $size = '' ) {
    if  ( $size == "full" ) {
        return array( 'width' => 800, 'height' => 800, 'crop' => true);
    }
    $wp_additional_image_sizes = wp_get_additional_image_sizes();

    $sizes = array();
    $get_intermediate_image_sizes = get_intermediate_image_sizes();

    // Create the full array with sizes and crop info
    foreach( $get_intermediate_image_sizes as $_size ) {
        if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
            $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
            $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
            $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );
        } elseif ( isset( $wp_additional_image_sizes[ $_size ] ) ) {
            $sizes[ $_size ] = array(
                'width' => $wp_additional_image_sizes[ $_size ]['width'],
                'height' => $wp_additional_image_sizes[ $_size ]['height'],
                'crop' =>  $wp_additional_image_sizes[ $_size ]['crop']
            );
        }
    }

    // Get only 1 size if found
    if ( $size ) {
        if( isset( $sizes[ $size ] ) ) {
            return $sizes[ $size ];
        } else {
            return false;
        }
    }
    return $sizes;
}

function wr4wc_add_image_to_the_post_action( $post_object ) {
    require_once plugin_dir_path(__FILE__) . "inc/wr-label.php";
    $label_handler = new WineRingForWooCommerceLabelHandler();
    $post_object = $label_handler->updatePostForLabel( $post_object );
    return $post_object;
}
add_action( 'the_post', 'wr4wc_add_image_to_the_post_action' );


function wr4wc_permissions_check(WP_REST_Request $request) {
    // ensure valid key exists.
    $system_feed_hash = get_option( 'wr4wc_feed_hash' );
    if ( !(strlen($system_feed_hash)>=12 ) ) {
        return new WP_Error( 'no_key_set', 'Key needs to be properly setup on admin page prior to using API (at least 12-character randomized string)', array( 'status' => 404 ) );
    }

    $provided_feed_key = $request->get_param( 'key' );
    $provided_feed_hash = hash("sha256", "salt4winering:" . $_SERVER['HTTP_HOST'] . $provided_feed_key);
    if ( $provided_feed_hash <> $system_feed_hash ) {
        return new WP_Error( 'invalid_key', 'Invalid or no key supplied', array( 'status' => 403 ) );
    }
    return true;
}

function wr4wc_add_data_feed_action( WP_REST_Request $request ) {
    require_once plugin_dir_path(__FILE__) . "inc/wr-feed.php";
    $feed_handler = new WineRingForWooCommerceFeedHandler();

    $limit = $request->get_param( 'limit' );
    if ( ! ( $limit > 0 ) || ! ( $limit < 10000 ) ) {
        return new WP_Error( 'no_offset', 'Invalid or no offset supplied', array( 'status' => 404 ) );
    }
    $offset = $request->get_param( 'offset' );
    if ( ! ( $offset >= 0 ) || ! ( $offset < 1000000 ) ) {
        return new WP_Error( 'no_offset', 'Invalid or no offset supplied', array( 'status' => 404 ) );
    }
    $offset = (int)$offset;

    $feed_array = $feed_handler->getFeedDataArray( $limit, $offset );

    if ( is_array($feed_array) ) {
        echo( json_encode($feed_array));
        die();
    }

    return new WP_Error( 'unknown_error', 'Unknown error', array( 'status' => 404 ) );
}

add_action('rest_api_init', function () {
    // https://example.com/wp-json/wr4wc/v1/datafeed(?P\d+).
    register_rest_route('wr4wc/v1', '/datafeed', array(
        'methods' => 'GET',
        'callback' => 'wr4wc_add_data_feed_action',
        'permission_callback' => 'wr4wc_permissions_check',
    ));
});

function wr4wc_add_customer_data_feed_action( WP_REST_Request $request ) {
    require_once plugin_dir_path(__FILE__) . "inc/wr-feed.php";
    $feed_handler = new WineRingForWooCommerceFeedHandler();

    $limit = $request->get_param( 'limit' );
    if ( ! ( $limit > 0 ) || ! ( $limit < 10000 ) ) {
        return new WP_Error( 'no_offset', 'Invalid or no offset supplied', array( 'status' => 404 ) );
    }
    $offset = $request->get_param( 'offset' );
    if ( ! ( $offset >= 0 ) || ! ( $offset < 1000000 ) ) {
        return new WP_Error( 'no_offset', 'Invalid or no offset supplied', array( 'status' => 404 ) );
    }
    $offset = (int)$offset;

    $feed_array = $feed_handler->getFeedCustomerDataArray( $limit, $offset );

    if ( is_array($feed_array) ) {
        echo( json_encode($feed_array));
        die();
    }

    return new WP_Error( 'unknown_error', 'Unknown error', array( 'status' => 404 ) );
}


add_action('rest_api_init', function () {
    // https://example.com/wp-json/wr4wc/v1/customers(?P\d+).
    register_rest_route('wr4wc/v1', '/customers', array(
        'methods' => 'GET',
        'callback' => 'wr4wc_add_customer_data_feed_action',
        'permission_callback' => 'wr4wc_permissions_check',
    ));
});



function wr4wc_update_db_check() {
    if ( get_site_option( 'wr4wc_db_version' ) != WR4WC_DB_VERSION ) {
        require_once plugin_dir_path(__FILE__) . "inc/activate.php";
        $activate_handler = new WineRingForWooCommerceActivate();
        $activate_handler->updateDB(WR4WC_DB_VERSION);
    }
}
add_action( 'plugins_loaded', 'wr4wc_update_db_check' );