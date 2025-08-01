<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// 📥 Render Inbox Endpoint in My Account
add_action('woocommerce_account_gradyzer-inbox_endpoint', 'gradyzer_render_inbox');
function gradyzer_render_inbox() {
    echo do_shortcode('[gradyzer_inbox]');
}

// 🗂️ Add Inbox Tab to My Account Menu
add_filter('woocommerce_account_menu_items', 'gradyzer_account_tab');
function gradyzer_account_tab($items) {
    $user_id = get_current_user_id();
    $messages = get_posts([
        'post_type' => 'gradyzer_message',
        'numberposts' => -1,
        'meta_query' => [
            ['key' => 'receiver_id', 'value' => $user_id],
            ['key' => 'is_read', 'value' => '0']
        ]
    ]);
    $senders = [];
    foreach ($messages as $msg) {
        $senders[get_post_meta($msg->ID, 'sender_id', true)] = true;
    }
    $count = count($senders);
    $label = "Inbox ($count)";
    $new = [];
    foreach ($items as $key => $val) {
        $new[$key] = $val;
        if ($key === 'dashboard') {
            $new['gradyzer-inbox'] = $label;
        }
    }
    return $new;
}

// 🔔 Inject Notification Bubble into Navigation Menu
add_filter('wp_nav_menu_items', 'gradyzer_inject_notification_bubble', 10, 2);
function gradyzer_inject_notification_bubble($items, $args) {
    if (!is_user_logged_in()) return $items;

    $bubble = do_shortcode('[gradyzer_notification]');
    return $bubble ? $items . '<li class="menu-item gradyzer-bubble-menu">' . $bubble . '</li>' : $items;
}

// 🧠 Mark messages as read when viewed in admin
add_action('save_post_gradyzer_message', 'gradyzer_mark_admin_read', 10, 3);
function gradyzer_mark_admin_read($post_id, $post, $update) {
    if (is_admin() && current_user_can('edit_post', $post_id)) {
        $receiver_id = get_post_meta($post_id, 'receiver_id', true);
        if (get_current_user_id() == $receiver_id && get_post_meta($post_id, 'is_read', true) === '0') {
            update_post_meta($post_id, 'is_read', '1');
        }
    }
}
