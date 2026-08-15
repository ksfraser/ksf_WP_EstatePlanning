<?php
/**
 * Plugin Name: KSF Estate Planning
 * Description: Estate Planning integration for WordPress
 * Version: 1.0.0
 * Author: KSF Fraser
 * Text Domain: ksf-estate-planning
 */

defined('ABSPATH') || exit;

const KSF_EP_VERSION = '1.0.0';
const KSF_EP_PATH = __DIR__;
const KSF_EP_URL = plugin_dir_url(__FILE__);

require_once KSF_EP_PATH . '/vendor/autoload.php';

register_activation_hook(__FILE__, ['Ksfraser\WP\EstatePlanning\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Ksfraser\WP\EstatePlanning\Plugin', 'deactivate']);

add_action('plugins_loaded', function () {
    $plugin = new Ksfraser\WP\EstatePlanning\Plugin();
    $plugin->init();
});