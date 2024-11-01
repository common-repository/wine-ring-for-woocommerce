<?php
/**
 * @package WineRingForWooCommerce
 */


class WineRingForWooCommerceApiHandler
{
    private static $token;

    public static function getLabelFromWineRing($product_id) {
        $wr4wc_options = get_option( 'wr4wc_plugin_options' );
        if ( !array_key_exists( "channel_id", $wr4wc_options) || !($wr4wc_options['channel_id']>0) ){
            return false;
        }
        $channel_id = $wr4wc_options["channel_id"];

        $method = "GET";
        $url_endpoint = "vintagemapping.json?";
        $url_endpoint .= implode ("&", array("channel_id=".$channel_id, "value=".$product_id));

        if ( !$response = self::callWineRingApi($method, $url_endpoint) ) {
            return false;
        }

        if ( is_array($response) && array_key_exists("path", $response) ) {
            if ( strlen($response["path"]) > 3 ) {
                return $response["path"];
            }
        }
        return null;
    }

    private static function getToken() {

        if ( strlen(self::$token) > 0 ) {
            return self::$token;
        }

        $wr4wc_options = get_option( 'wr4wc_plugin_options' );
        if ( !array_key_exists( "api_token", $wr4wc_options) || !(strlen($wr4wc_options['api_token'])>0 ) ) {
            return false;
        }
        self::$token = $wr4wc_options["api_token"];
        return self::$token;
    }

    private static function callWineRingApi($method, $url_endpoint, $data=array(), $headers = array())
    {
        if ( $method != "GET" ) {
            die("only doing GET right now...");
        }

        if ( !$token = self::getToken() ) {
            return false;
        }

        $headers = array_merge($headers, array('Content-Type'=>'application/json'));
        $headers = array_merge($headers, array('Authorization'=>'Bearer ' . $token));

        $wr4wc_options = get_option( 'wr4wc_plugin_options' );;
        if ( array_key_exists( "client_interface", $wr4wc_options) && strlen($wr4wc_options['client_interface'])>0 ){
            $headers = array_merge($headers, array("client_interface"=> $wr4wc_options["client_interface"]));
        } elseif ( is_string(get_site_url()) && strlen(get_site_url()) > 0 ) {
            $headers = array_merge($headers, array("client_interface"=> get_site_url() ));
        }
        $headers = array_merge($headers, array("client_interface_version"=> WR4WC_PLUGIN_VERSION));

        $url = "https://api.winering.com/api/5.0/". $url_endpoint;

        $args = array();
        $args['headers'] = $headers;
        $args['timeout'] = 5;
        $request = wp_remote_get( $url, $args );

        if( is_wp_error( $request ) ) {
            return false; // Bail early
        }
        $result_encoded = wp_remote_retrieve_body( $request );

        if ( $result = json_decode($result_encoded, true) ) {
            return $result;
        }

        return false;
    }

}