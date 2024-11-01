<?php
/**
 * Auto-triggered during uninstall.
 *
 * @package WineRingForWooCommerce
 */

if ( !defined( "WP_UNINSTALL_PLUGIN") ) {
    die();
}

// Purge all the references to the now deleted wine-ring-label post type entries.
global $wpdb;
$sql = "DELETE FROM {$wpdb->prefix}postmeta WHERE `meta_key` = '_thumbnail_id' AND `meta_value` IN (SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_type`='wine-ring-label') ";
$wpdb->query($sql);


$wine_ring_labels = get_posts( array('post_type'=>'wine-ring-label', 'post_status' => 'any, trash, auto-draft', 'numberposts'=> -1 ) );
foreach ( $wine_ring_labels AS $wine_ring_label ) {
    wp_delete_post($wine_ring_label->ID, true);
}

/**
 * Delete Post Meta within WooCommerce Products.
 */
$post_meta_keys = array(
    "_wine_ring_label_checked_at",
    "_wine_ring_label_expires_at",
    "_wine_ring_label_url"
);
foreach ($post_meta_keys as $post_meta_key) {
    delete_post_meta_by_key($post_meta_key);
}

/**
 * Delete general options
 */
delete_option("wr4wc_plugin_options");
delete_site_option("wr4wc_plugin_options");





