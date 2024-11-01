<?php
/**
 * @package WineRingForWooCommerce
 */

require_once "wr-api.php";

class WineRingForWooCommerceFeedHandler
{
    public function getFeedDataArray( $limit = 100, $offset = 0 ) {



        $wr4wc_options = get_option( 'wr4wc_plugin_options' );

        $api_feed_category_ids = array();
        if  ( array_key_exists("api_feed_category_ids", $wr4wc_options) && strlen($wr4wc_options["api_feed_category_ids"])>0) {
            $api_feed_categories_string = $wr4wc_options["api_feed_category_ids"];
            $api_feed_category_ids = explode(",", $api_feed_categories_string);
            $api_feed_category_ids = array_map('trim',$api_feed_category_ids);
            $api_feed_category_ids = array_map('intval', $api_feed_category_ids);
        }

        $custom_field_slugs = array();
        if  ( array_key_exists("api_feed_custom_field_slugs", $wr4wc_options) && strlen($wr4wc_options["api_feed_custom_field_slugs"])>0) {
            $api_feed_custom_fields_slugs_string = $wr4wc_options["api_feed_custom_field_slugs"];
            $custom_field_slugs = explode(",", $api_feed_custom_fields_slugs_string);
            $custom_field_slugs = array_map('trim',$custom_field_slugs);
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'offset'        => $offset
        );

        if ( count($api_feed_category_ids) > 0 ) {
            // $args['cat'] = implode(",",$api_feed_category_ids);
            $args['tax_query'] = array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                    'terms'         => $api_feed_category_ids
//                    'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
                )
            );
//            $args['category__in'] = array(16); // $api_feed_category_ids;
//            $args['cat'] = "16";
        }
        $query = new WP_Query($args);

        $data = array();
        if($query->have_posts()):
            while($query->have_posts()):
                $query->the_post();
                $product_data = array();
                $woocommerce_id = get_the_ID();
                $product_data['woocommerce_id'] = $woocommerce_id;

                $product = wc_get_product( $woocommerce_id );

                $product->get_id();

                $product_data['url'] = get_permalink( $product->get_id() );

                $product_data['product_type'] = $product->get_type();
                $product_data['name'] = $product->get_name();
                $product_data['slug'] = $product->get_slug();
                $product_data['date_created'] = $product->get_date_created();
                $product_data['date_modified'] = $product->get_date_modified();
                $product_data['status'] = $product->get_status();
                $product_data['featured'] = $product->get_featured();
                $product_data['catalog_visibility'] = $product->get_catalog_visibility();
                $product_data['description'] = $product->get_description();
                $product_data['short_description'] = $product->get_short_description();
                $product_data['sku'] = $product->get_sku();
                // $product_data['menu_order'] = $product->get_menu_order();
                $product_data['virtual'] = $product->get_virtual();

                $product_data['price'] = $product->get_price();
                $product_data['regular_price'] = $product->get_regular_price();
                $product_data['sale_price'] = $product->get_sale_price();

                $product_data['manage_stock'] = $product->get_manage_stock();
                $product_data['stock_quantity'] = $product->get_stock_quantity();
                $product_data['backorders'] = $product->get_backorders();
                // $product_data['sold_individually'] = $product->get_sold_individually();
                // $product_data['purchase_note'] = $product->get_purchase_note();
                // $product_data['shipping_class_id'] = $product->get_shipping_class_id();

                /*
                // Get Product Variations and Attributes
                $product->get_children(); // get variations
                $product->get_attributes();
                $product->get_default_attributes();
                $product->get_attribute( 'attributeid' ); //get specific attribute value
                */

                // Get Product Taxonomies

                $product_data['category_ids'] = $product->get_category_ids();
                $product_data['categories'] = array();
                $category_terms = get_the_terms( $product->get_id(), 'product_cat' );
                foreach ( $category_terms  as $category_term  ) {
                    $product_data['categories'][] = $category_term->name;
                }

                $product_data['tag_ids'] = $product->get_tag_ids();

                $product_data['image_id'] = $product->get_image_id();
                if ( $product_data['image_id'] > 0 ) {
                    if ( $image_data = wp_get_attachment_image_src($product_data['image_id'], 'full') ) {
                        if ( is_array($image_data) && array_key_exists(0, $image_data) ) {
                            $product_data['image_url'] = $image_data[0];
                        }
                    }
                }
                // $product_data['gallery_image_ids'] = $product->get_gallery_image_ids();

                foreach ( $custom_field_slugs AS $slug ) {
                    $product_data['custom_field_' . $slug] = get_post_meta($product->get_id(), $slug, true);
                }

                $data[] = $product_data;
            endwhile;
        endif;
        return $data;

    }



    public function getFeedCustomerDataArray( $limit = 100, $offset = 0 ) {

        $wr4wc_options = get_option( 'wr4wc_plugin_options' );

        $api_feed_category_ids = array();
        if  ( array_key_exists("api_feed_category_ids", $wr4wc_options) && strlen($wr4wc_options["api_feed_category_ids"])>0) {
            $api_feed_categories_string = $wr4wc_options["api_feed_category_ids"];
            $api_feed_category_ids = explode(",", $api_feed_categories_string);
            $api_feed_category_ids = array_map('trim',$api_feed_category_ids);
            $api_feed_category_ids = array_map('intval', $api_feed_category_ids);
        }

        $args = array(
            'post_type' => 'customer',
            'posts_per_page' => $limit,
            'offset'        => $offset
        );

        /*
        $query = new WC_Order_Query();
        $query->set( 'customer', 'woocommerce@woocommerce.com' );
        $orders = $query->get_orders();
        */

        $orders = wc_get_orders(array(
            'offset' => $offset,
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
//            'date_paid' => '2016-01-01...2021-12-31',
        ));

        $data = array();
        // Loop through each WC_Order object
        foreach( $orders as $order ){
            $order_data = $order->get_data(); // The Order data
            $datum['order_id'] = $order_data['id'];
            $datum['order_status'] = $order_data['status'];
            $datum['order_currency'] = $order_data['currency'];
            if ( $order_data['date_created'] ) {
                $datum['order_date_created'] = $order_data['date_created']->date('Y-m-d H:i:s');
            }
            if ( $order_data['date_modified'] ) {
                $datum['order_date_modified'] = $order_data['date_modified']->date('Y-m-d H:i:s');
            }
            $datum['order_version'] = $order_data['version'];
            $datum['order_total'] = $order_data['total'];
            $datum['order_customer_id'] = $order_data['customer_id'];
            if ( $order_data['billing']) {
                $datum['order_billing_first_name'] = $order_data['billing']['first_name'];
                $datum['order_billing_last_name'] = $order_data['billing']['last_name'];
                $datum['order_billing_email'] = $order_data['billing']['email'];
            }
            // $order_parent_id = $order_data['parent_id'];

            $items = $order->get_items();
            foreach ( $items AS $item ) {


                $item_datum = array();
                $item_datum['product_category_ids'] = implode(",",$item->get_product()->get_category_ids());
                if ( count($api_feed_category_ids) > 0 ) {
                    $valid_cat = false;
                    foreach ( $item_datum['product_category_ids'] AS $cat_id ) {
                        if ( in_array( $cat_id, $api_feed_category_ids ) ) {
                            $valid_cat = true;
                        }
                    }
                    if ( !$valid_cat ) {
                        continue;
                    }
                }

                $item = $item->get_data();
                $item_datum['product_wc_id'] = $item['id'];
                $item_datum['product_product_id'] = $item['product_id'];
                $item_datum['product_variation_id'] = $item['variation_id'];
                $item_datum['product_name'] = $item['name'];
                $item_datum['product_quantity'] = $item['quantity'];
                $item_datum['product_total'] = $item['total'];

                $data[] = array_merge($datum, $item_datum);
            }
        }
        return $data;
    }

}
