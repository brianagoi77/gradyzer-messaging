<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('gradyzer/v2', '/thread/(?P<user_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'gradyzer_get_thread',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);

    register_rest_route('gradyzer/v2', '/messages/(?P<message_id>\d+)/read', [
        'methods' => 'POST',
        'callback' => 'gradyzer_mark_as_read',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);

    register_rest_route('gradyzer/v2', '/messages/(?P<message_id>\d+)/reply', [
        'methods' => 'POST',
        'callback' => 'gradyzer_send_reply',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);
});

function gradyzer_get_thread($request) {
    $current_user = get_current_user_id();
    $other_user = intval($request['user_id']);

    $messages = get_posts([
        'post_type' => 'gradyzer_message',
        'numberposts' => -1,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'sender_id',
                'value' => $current_user
            ],
            [
                'key' => 'receiver_id',
                'value' => $current_user
            ]
        ]
    ]);

    $thread = [];
    $product = null;

    foreach ($messages as $msg) {
        $sender = get_post_meta($msg->ID, 'sender_id', true);
        $receiver = get_post_meta($msg->ID, 'receiver_id', true);

        if (($sender == $current_user && $receiver == $other_user) || ($sender == $other_user && $receiver == $current_user)) {
            // ✅ Mark as read if current user is receiver
            if ($receiver == $current_user && get_post_meta($msg->ID, 'is_read', true) === '0') {
                update_post_meta($msg->ID, 'is_read', '1');
            }

            $thread[] = [
                'id' => $msg->ID,
                'sender_id' => $sender,
                'sender_name' => get_the_author_meta('display_name', $sender),
                'content' => $msg->post_content,
                'avatar' => get_avatar_url($sender),
                'timestamp' => get_the_date('', $msg)
            ];

            if (!$product && $pid = get_post_meta($msg->ID, 'product_id', true)) {
                $product = [
                    'title' => get_the_title($pid),
                    'link' => get_permalink($pid),
                    'thumb' => get_the_post_thumbnail_url($pid, 'thumbnail'),
                    'price' => wc_price(get_post_meta($pid, '_price', true))
                ];
            }
        }
    }

    return [
        'messages' => $thread,
        'product' => $product
    ];
}

function gradyzer_mark_as_read($request) {
    $message_id = intval($request['message_id']);
    if (get_current_user_id() === intval(get_post_meta($message_id, 'receiver_id', true))) {
        update_post_meta($message_id, 'is_read', '1');
        return ['success' => true];
    }
    return ['success' => false];
}

function gradyzer_send_reply($request) {
    $message_id = intval($request['message_id']);
    $original = get_post($message_id);
    if (!$original) return ['success' => false];

    $receiver_id = get_post_meta($message_id, 'sender_id', true);
    $product_id = get_post_meta($message_id, 'product_id', true);
    $content = sanitize_text_field($request->get_param('message'));

    $new = wp_insert_post([
        'post_type' => 'gradyzer_message',
        'post_title' => 'Reply from User ' . get_current_user_id(),
        'post_content' => $content,
        'post_status' => 'publish',
        'meta_input' => [
            'sender_id' => get_current_user_id(),
            'receiver_id' => $receiver_id,
            'product_id' => $product_id,
            'is_read' => '0'
        ]
    ]);

    return ['success' => $new ? true : false];
}
