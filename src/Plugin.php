<?php
// src/Plugin.php
declare(strict_types=1);

namespace Ksfraser\WP\EstatePlanning;

use Ksfraser\WP\EstatePlanning\Endpoints\EstateTaxController;
use Ksfraser\WP\EstatePlanning\Models\EstatePlanningScenario;

class Plugin
{
    public static function activate(): void
    {
        // Create custom tables
        self::createTables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function init(): void
    {
        // Register REST endpoints
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        
        // Register Gutenberg blocks
        add_action('init', [$this, 'registerBlocks']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        
        // Register shortcodes
        add_shortcode('ksf_estate_calculator', [$this, 'shortcodeCalculator']);
        add_shortcode('ksf_estate_scenarios', [$this, 'shortcodeScenarios']);
    }

    public function registerEndpoints(): void
    {
        $controller = new EstateTaxController();
        
        register_rest_route('ksf-ep/v1', '/calculate-tax', [
            'methods' => 'POST',
            'callback' => [$controller, 'calculateTax'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('ksf-ep/v1', '/scenarios', [
            'methods' => 'GET',
            'callback' => [$controller, 'getScenarios'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
        
        register_rest_route('ksf-ep/v1', '/scenarios/(?P<id>\d+)', [
            'methods' => ['GET', 'PUT', 'DELETE'],
            'callback' => [$controller, 'handleScenario'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    public function registerBlocks(): void
    {
        if (function_exists('register_block_type')) {
            register_block_type(KSF_EP_PATH . '/build/blocks/estate-calculator', [
                'render_callback' => [$this, 'renderCalculatorBlock'],
            ]);
        }
    }

    public function enqueueAdminScripts(): void
    {
        wp_enqueue_script(
            'ksf-ep-admin',
            KSF_EP_URL . 'build/admin.js',
            ['wp-element', 'wp-editor', 'wp-api-fetch'],
            KSF_EP_VERSION,
            true
        );
        
        wp_localize_script('ksf-ep-admin', 'ksfEpData', [
            'apiUrl' => rest_url('ksf-ep/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function shortcodeCalculator($atts): string
    {
        $atts = shortcode_atts([
            'default_province' => 'ON',
            'default_year' => date('Y'),
        ], $atts);
        
        return '<div id="ksf-ep-calculator" data-province="' . esc_attr($atts['default_province']) . '" data-year="' . esc_attr($atts['default_year']) . '"></div>';
    }

    public function shortcodeScenarios($atts): string
    {
        return '<div id="ksf-ep-scenarios"></div>';
    }

    public function renderCalculatorBlock($attributes, $content): string
    {
        return '<div class="ksf-ep-calculator-block"></div>';
    }

    private static function createTables(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        $tables = [
            "CREATE TABLE {$wpdb->prefix}ksf_estate_scenarios (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                scenario_name VARCHAR(100) NOT NULL,
                data JSON DEFAULT NULL,
                status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id)
            ) $charset;",
            
            "CREATE TABLE {$wpdb->prefix}ksf_estate_beneficiaries (
                beneficiary_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                scenario_id BIGINT(20) UNSIGNED NOT NULL,
                beneficiary_name VARCHAR(150) NOT NULL,
                relationship VARCHAR(50) DEFAULT NULL,
                allocation_pct DECIMAL(5,2) DEFAULT 0.00,
                is_contingent BOOLEAN DEFAULT FALSE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (beneficiary_id),
                KEY scenario_id (scenario_id)
            ) $charset;",
        ];
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }
}