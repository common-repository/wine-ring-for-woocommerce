<?php
/**
 * @package WineRingForWooCommerce
 */

require_once "wr-api.php";

class WineRingForWooCommerceLabelHandler
{
    public function updatePostForLabel($post_object) {
        if ( !$post_id = $post_object->ID ) {
            return $post_object;
        }
        if ( !in_array(get_post_type( $post_object ), array( 'product') ) ) {
            return $post_object;
        }

        $thumb_id = null;
        if ( has_post_thumbnail ($post_object ) ) {
            if ( $thumb_id = get_post_meta($post_id, "_thumbnail_id", true) ) {
                // Check if placeholder label.
                $is_placeholder_image = false;
                $wr4wc_options = get_option( 'wr4wc_plugin_options' );
                if ( array_key_exists("placeholder_ids", $wr4wc_options) ) {
                    $placeholder_ids_string = $wr4wc_options['placeholder_ids'];
                    $placeholder_ids_array = explode(',', $placeholder_ids_string );
                    $placeholder_ids_array = array_map('trim',$placeholder_ids_array);
                    $placeholder_ids_array = array_map('intval', $placeholder_ids_array);
                    if ( in_array($thumb_id, $placeholder_ids_array) ) {
                        $is_placeholder_image = true;
                    }
                }
                if ( $is_placeholder_image ) {
                    $thumb_id = null; // Treat placeholders as not existing.
                } elseif ( !in_array( get_post_type( $thumb_id ), array( 'wine-ring-label') ) ) {
                    // it's a valid image that is not WR...move along.
                    return $post_object;
                }
            }
        }

        // check timestamps.
        $checked_at_timestamp = get_post_meta( $post_id, '_wine_ring_label_checked_at', true );
        $checked_at_timestamp = strtotime($checked_at_timestamp);

        if ( $thumb_id > 0 ) {
            $time_delta = strtotime("-7 days"); // label exists...check less often.
        } else {
            $time_delta = strtotime("-1 days"); // no label exists...check more often.
        }

        $force_update_label = false;

        if ( $checked_at_timestamp && $time_delta > $checked_at_timestamp ) {
            $force_update_label = true;
        }

        $wr4wc_options = get_option( 'wr4wc_plugin_options' );
        if ( !$force_update_label && array_key_exists("last_updated_at", $wr4wc_options)) {
            $options_last_updated_at = $wr4wc_options["last_updated_at"];
            $options_last_updated_at = strtotime($options_last_updated_at);
            if ( !$checked_at_timestamp || $options_last_updated_at > $checked_at_timestamp ) {
                // Force update regardless of time_delta, but to prevent the server from becoming overloaded and/or
                // poor user experience, we attempt to spread the updates over 30 minutes. The more frequently a
                // label is called the more likely it will be updated in the first 30 minutes.
                $elapsed_seconds_since_options_last_updated_at = strtotime("now") - $options_last_updated_at;

                $seconds_for_images_to_force_convert = 1800;
                if ($elapsed_seconds_since_options_last_updated_at > $seconds_for_images_to_force_convert) { // 30 minutes
                    $force_update_label = true;
                } elseif( rand(0,1000) * $seconds_for_images_to_force_convert < 1000 * $elapsed_seconds_since_options_last_updated_at  ) {
                    // Condition is a refactor of the following without division.
                    // rand(0,1000) / 1000 < $elapsed_seconds_since_options_last_updated_at / $seconds_for_images_to_force_convert
                    // The right term goes to 1 as $elapsed_seconds_since_options_last_updated_at approaches the $seconds_for_images_to_force_convert.
                    // The 1000 is arbitrary, but they must be consistent.
                    $force_update_label = true;
                }
            }
        }

        if( !$force_update_label ) {
            return $post_object;
        }
        $current_timestamp = date('Y-m-d H:i:s');
        update_post_meta($post_id, "_wine_ring_label_checked_at", $current_timestamp);

        $unique_identifier_method = "product_id";
        if ( array_key_exists("unique_identifier_method", $wr4wc_options) ) {
            $unique_identifier_method = $wr4wc_options['unique_identifier_method'];
        }
        $label_object_id = $this->getLabelObjectIdForProduct($post_object, $thumb_id,$unique_identifier_method);
        if ( $label_object_id <> $thumb_id ) {
            update_post_meta($post_id, "_thumbnail_id", $label_object_id);
        }

        return $post_object;
    }

    private function getLabelObjectIdForProduct($post_object, $existing_label_id, $unique_identifier_method) {

        if ( !$this->verifyPostCategoriesAreValidForLabels($post_object) ) {
            return false;
        }

        switch ( $unique_identifier_method ) {
            case ("product_sku"):
                try {
                        $product = new WC_Product($post_object->ID);
                        $unique_identifier = $product->get_sku();
                        if ( !(strlen($unique_identifier) > 0 ) ) {
                            return false;
                        }
                } catch (Exception $e) {
                    return false;
                }
                break;
            case ("product_id"):
            default:
                $unique_identifier = $post_object->ID;
                break;
        }

        $label_url = WineRingForWooCommerceApiHandler::getLabelFromWineRing($unique_identifier);

        if ( $label_url && $existing_label_id ) {
            if ( !$label_object = get_post($existing_label_id) ) {
                return false; // should not happen.
            }
            $label_object_id = $existing_label_id;
        } elseif ( $label_url ) {
            $label_post = array(
                'post_author'           => '',
                'post_content'          => '',
                'post_content_filtered' => '',
                'post_title'            => "woocommerce_id=".$post_object->ID,
                'post_excerpt'          => '',
                'post_status'           => 'publish',
                'post_type'             => 'wine-ring-label',
                'comment_status'        => '',
                'ping_status'           => '',
                'post_password'         => '',
                'to_ping'               => '',
                'pinged'                => '',
                'post_parent'           => 0,
                'menu_order'            => 0,
                'guid'                  => $label_url,
                'import_id'             => 0,
                'context'               => '',
                'post_mime_type'        => 'image/jpeg'
            );
            if ( !$label_object_id  = wp_insert_post( $label_post ) ) {
                return false;
            }
        } else {
            // (Case A) No label, but existing label exists and needs to be cleared out.
            // OR (Case B) no label url, so no label ID to return either way.
            // This is done in another function.
            return false;
        }
        update_post_meta($label_object_id, "_wine_ring_label_url", $label_url);
        update_post_meta($label_object_id, "_wp_attached_file", $label_url);
        update_post_meta($label_object_id, "_wp_attachment_metadata", [
            'width'=>600,
            'height'=> 800,
            'file'=> 'wine-ring-labels/path/to/file.jpg',
            'sizes'=> ['original' => ['file' => 'wine-ring-labels/path/to/file.jpg', 'width'=> 600, 'height'=> 800, 'mime-type'=> 'image/jpeg']]]);
        
        $timestamp = date('Y-m-d H:i:s');
        update_post_meta($label_object_id, "_wine_ring_label_expires_at", $timestamp); // Only for debugging.
        update_post_meta($label_object_id, "_wine_ring_label_checked_at", $timestamp);

        return $label_object_id;
    }

    private function verifyPostCategoriesAreValidForLabels($post_object) {

        $wr4wc_options = get_option( 'wr4wc_plugin_options' );

        $whitelisted_category_ids = array();
        if  ( array_key_exists("label_whitelisted_category_ids", $wr4wc_options) && strlen($wr4wc_options["label_whitelisted_category_ids"])>0) {
            $whitelisted_categories_string = $wr4wc_options["label_whitelisted_category_ids"];
            $whitelisted_category_ids = explode(",", $whitelisted_categories_string);
            $whitelisted_category_ids = array_map('trim',$whitelisted_category_ids);
            $whitelisted_category_ids = array_map('intval', $whitelisted_category_ids);
        }

        if ( !(count($whitelisted_category_ids) > 0 ) ) {
            return true; // no whitelisted categories, so product is assumed valid.
        }

        $post_categories = get_the_terms( $post_object->ID, 'product_cat' );

        foreach ( $post_categories AS $post_category ) {
            if ( $this->verifyPostCategoryIsValidForLabels($whitelisted_category_ids, $post_category) ) {
                return true;
            }
        }
        return false;
    }

    private function verifyPostCategoryIsValidForLabels($whitelisted_category_ids, $post_category) {
        $post_category_id = $post_category->term_id;
        if ( in_array($post_category_id, $whitelisted_category_ids) ) {
            return true;
        }
        // check parent categories.
        if( $post_category->parent > 0 ) {
            $parent_category = get_term($post_category->parent, 'product_cat' );
            if ( $parent_category && $this->verifyPostCategoryIsValidForLabels($whitelisted_category_ids, $parent_category) ) {
                return true;
            }
        }
        return false;
    }

}
