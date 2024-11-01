<?php
/**
 * @package WineRingForWooCommerce
 */


class WineRingForWooCommerceDeactivate
{
    public static function deactivate() {
        flush_rewrite_rules();
    }
}