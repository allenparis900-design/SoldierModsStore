<?php
/**
 * Plugin Name: SecurePass Manager
 * Plugin URI: https://securepassmanager.com
 * Description: Professional password manager for WordPress with team collaboration, 2FA, and breach alerts
 * Version: 1.0.0
 * Author: SoldierModsStore
 * Author URI: https://soldiermodsstore.com
 * License: GPL2
 * Text Domain: securepass-manager
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('SECUREPASS_VERSION', '1.0.0');
define('SECUREPASS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SECUREPASS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SECUREPASS_DB_VERSION', 1);

// Activation hook
register_activation_hook(__FILE__, 'securepass_activate');
function securepass_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Passwords table
    $passwords_table = $wpdb->prefix . 'securepass_passwords';
    $sql_passwords = "CREATE TABLE IF NOT EXISTS $passwords_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        password_title VARCHAR(255) NOT NULL,
        encrypted_password LONGTEXT NOT NULL,
        website_url VARCHAR(255),
        username VARCHAR(255),
        notes LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY user_id (user_id)
    ) $charset_collate;";

    // Team members table
    $team_table = $wpdb->prefix . 'securepass_team_members';
    $sql_team = "CREATE TABLE IF NOT EXISTS $team_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        password_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        access_level VARCHAR(50) DEFAULT 'view',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY password_id (password_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // 2FA table
    $twofa_table = $wpdb->prefix . 'securepass_2fa';
    $sql_twofa = "CREATE TABLE IF NOT EXISTS $twofa_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        secret_key VARCHAR(255) NOT NULL,
        enabled BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Licenses table
    $licenses_table = $wpdb->prefix . 'securepass_licenses';
    $sql_licenses = "CREATE TABLE IF NOT EXISTS $licenses_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        license_key VARCHAR(255) UNIQUE NOT NULL,
        plan_type VARCHAR(50),
        status VARCHAR(50) DEFAULT 'active',
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_passwords);
    dbDelta($sql_team);
    dbDelta($sql_twofa);
    dbDelta($sql_licenses);

    update_option('securepass_db_version', SECUREPASS_DB_VERSION);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'securepass_deactivate');
function securepass_deactivate() {
    // Clean up transients and options if needed
}

// Load plugin files
require_once SECUREPASS_PLUGIN_DIR . 'includes/class-securepass-encryption.php';
require_once SECUREPASS_PLUGIN_DIR . 'includes/class-securepass-admin.php';
require_once SECUREPASS_PLUGIN_DIR . 'includes/class-securepass-api.php';
require_once SECUREPASS_PLUGIN_DIR . 'includes/class-securepass-license.php';

// Initialize plugin
add_action('plugins_loaded', 'securepass_init');
function securepass_init() {
    // Load text domain
    load_plugin_textdomain('securepass-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize classes
    new SecurePass_Admin();
    new SecurePass_API();
    new SecurePass_License();
}

// Admin menu
add_action('admin_menu', 'securepass_add_admin_menu');
function securepass_add_admin_menu() {
    add_menu_page(
        'SecurePass Manager',
        'SecurePass Manager',
        'manage_options',
        'securepass-dashboard',
        'securepass_dashboard_page',
        'dashicons-lock',
        25
    );
}

// Admin page
function securepass_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>🔐 SecurePass Manager</h1>
        <div class="securepass-dashboard">
            <p>Welcome to SecurePass Manager - Your professional password management solution.</p>
            
            <div class="securepass-cards">
                <div class="card">
                    <h3>📊 Your Statistics</h3>
                    <p>Active Passwords: <strong id="password-count">-</strong></p>
                    <p>Team Members: <strong id="team-count">-</strong></p>
                    <p>Your Plan: <strong id="plan-type">Free</strong></p>
                </div>
                
                <div class="card">
                    <h3>🎯 Quick Actions</h3>
                    <button class="button button-primary" onclick="openPasswordModal()">+ Add Password</button>
                    <button class="button" onclick="location.href='#manage-passwords'">Manage Passwords</button>
                </div>
                
                <div class="card">
                    <h3>💎 Upgrade to Premium</h3>
                    <p>Get team sharing, 2FA, breach alerts, and more!</p>
                    <button class="button button-primary" onclick="location.href='#upgrade'">Upgrade Now - $9.99/month</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        .securepass-dashboard { margin-top: 20px; }
        .securepass-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; }
        .card h3 { margin-top: 0; color: #667eea; }
    </style>
    <?php
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'securepass_admin_enqueue_scripts');
function securepass_admin_enqueue_scripts() {
    wp_enqueue_style('securepass-admin', SECUREPASS_PLUGIN_URL . 'css/admin.css');
    wp_enqueue_script('securepass-admin', SECUREPASS_PLUGIN_URL . 'js/admin.js', array('jquery'), SECUREPASS_VERSION);
    
    wp_localize_script('securepass-admin', 'securepass_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('securepass_nonce')
    ));
}

// REST API routes
add_action('rest_api_init', 'securepass_register_rest_routes');
function securepass_register_rest_routes() {
    register_rest_route('securepass/v1', '/passwords', array(
        'methods' => 'GET',
        'callback' => 'securepass_get_passwords',
        'permission_callback' => 'is_user_logged_in'
    ));
    
    register_rest_route('securepass/v1', '/passwords', array(
        'methods' => 'POST',
        'callback' => 'securepass_create_password',
        'permission_callback' => 'is_user_logged_in'
    ));
}

function securepass_get_passwords() {
    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'securepass_passwords';
    
    $passwords = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d",
        $user_id
    ));
    
    return new WP_REST_Response($passwords, 200);
}

function securepass_create_password($request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $params = $request->get_json_params();
    
    $encryption = new SecurePass_Encryption();
    $encrypted = $encryption->encrypt($params['password']);
    
    $wpdb->insert(
        $wpdb->prefix . 'securepass_passwords',
        array(
            'user_id' => $user_id,
            'password_title' => sanitize_text_field($params['title']),
            'encrypted_password' => $encrypted,
            'website_url' => esc_url($params['url'] ?? ''),
            'username' => sanitize_text_field($params['username'] ?? ''),
            'notes' => sanitize_textarea_field($params['notes'] ?? '')
        )
    );
    
    return new WP_REST_Response(array('success' => true, 'id' => $wpdb->insert_id), 201);
}
