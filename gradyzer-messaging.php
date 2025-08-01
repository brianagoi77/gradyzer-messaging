<?php
/**
 * Plugin Name: Gradyzer Messaging
 * Description: Threaded messaging plugin for WooCommerce product authors and customers.
 * Version: 3.1.0
 * Author: Brian Agoi
 * Requires at least: 6.6
 * Tested up to: 6.8.1
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// 🔧 Plugin Constants
define('GRADYZER_MSG_PATH', plugin_dir_path(__FILE__));
define('GRADYZER_MSG_URL', plugin_dir_url(__FILE__));

// 🚀 Initialize Plugin
add_action('plugins_loaded', 'gradyzer_initialize_plugin', 5);
function gradyzer_initialize_plugin() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>Gradyzer Messaging:</strong> This plugin requires WooCommerce to be installed and activated.</p></div>';
        });
        return;
    }

    require_once GRADYZER_MSG_PATH . 'includes/class-message-controller.php';
    require_once GRADYZER_MSG_PATH . 'includes/shortcodes.php';
    require_once GRADYZER_MSG_PATH . 'includes/hooks.php';
    require_once GRADYZER_MSG_PATH . 'includes/admin-page.php';

    add_action('init', 'gradyzer_register_post_type');
    add_action('init', 'gradyzer_register_endpoint');
    add_action('wp_enqueue_scripts', 'gradyzer_enqueue_assets');
    add_action('admin_enqueue_scripts', 'gradyzer_admin_assets');
    add_action('admin_post_nopriv_gradyzer_send_message', 'gradyzer_send_message');
    add_action('admin_post_gradyzer_send_message', 'gradyzer_send_message');
}

// 🧱 Register Custom Post Type
function gradyzer_register_post_type() {
    register_post_type('gradyzer_message', [
        'label' => 'Messages',
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-email-alt'
    ]);
}

// 🔗 Register WooCommerce Endpoint
function gradyzer_register_endpoint() {
    add_rewrite_endpoint('gradyzer-inbox', EP_ROOT | EP_PAGES);
}

// 📩 Handle Message Submission
function gradyzer_send_message() {
    if (!is_user_logged_in()) return;
    if (!isset($_POST['receiver_id'], $_POST['message'])) return;

    wp_insert_post([
        'post_type' => 'gradyzer_message',
        'post_title' => 'Message from User ' . get_current_user_id(),
        'post_content' => sanitize_textarea_field($_POST['message']),
        'post_status' => 'publish',
        'meta_input' => [
            'sender_id' => get_current_user_id(),
            'receiver_id' => intval($_POST['receiver_id']),
            'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
            'is_read' => '0'
        ]
    ]);

    wp_redirect(add_query_arg('message_sent', '1', wp_get_referer()));
    exit;
}

// 🎨 Enqueue Frontend Assets
function gradyzer_enqueue_assets() {
    wp_enqueue_style('gradyzer-msg-style', GRADYZER_MSG_URL . 'css/style.css', [], null);
    wp_enqueue_script('gradyzer-msg-script', GRADYZER_MSG_URL . 'js/script.js', ['jquery'], null, true);
    wp_localize_script('gradyzer-msg-script', 'GradyzerSettings', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restRoot' => rest_url('gradyzer/v2/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'avatar' => get_avatar_url(get_current_user_id())
    ]);
}

// 🛠️ Enqueue Admin Assets
function gradyzer_admin_assets($hook) {
    if ($hook === 'toplevel_page_gradyzer_settings') {
        wp_enqueue_style('gradyzer-msg-style-admin', GRADYZER_MSG_URL . 'css/style.css', [], null);
        wp_enqueue_script('gradyzer-msg-script-admin', GRADYZER_MSG_URL . 'js/script.js', ['jquery'], null, true);
        wp_localize_script('gradyzer-msg-script-admin', 'GradyzerSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restRoot' => rest_url('gradyzer/v2/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'avatar' => get_avatar_url(get_current_user_id())
        ]);
    }
}

// 🧠 Create Inbox Page on Activation
register_activation_hook(__FILE__, 'gradyzer_create_inbox_page');
function gradyzer_create_inbox_page() {
    $slug = 'inbox';
    $title = 'Inbox';
    $shortcode = '[gradyzer_inbox]';

    $existing = get_page_by_path($slug);
    if ($existing) {
        wp_update_post([
            'ID' => $existing->ID,
            'post_content' => $shortcode
        ]);
    } else {
        wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $shortcode,
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }
}
