<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// 📥 Unified Inbox Shortcode
add_shortcode('gradyzer_inbox', function () {
    if (!is_user_logged_in()) return '<p>Please log in to view your inbox.</p>';

    $user_id = get_current_user_id();

    // ✅ Get latest message per sender
    $messages = get_posts([
        'post_type' => 'gradyzer_message',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            ['key' => 'receiver_id', 'value' => $user_id]
        ]
    ]);

    $threads = [];
    foreach ($messages as $msg) {
        $sender_id = get_post_meta($msg->ID, 'sender_id', true);
        if (!isset($threads[$sender_id])) {
            $threads[$sender_id] = $msg; // ✅ First match is now the latest
        }
    }

    ob_start();
    ?>
    <div class="gradyzer-body-wrapper gradyzer-inbox-page">
        <div class="gradyzer-inbox-columns">
            <div class="gradyzer-inbox-left">
                <ul class="gradyzer-thread-list">
                    <?php foreach ($threads as $sender_id => $msg): 
                        // ✅ Check if any message from this sender is unread
                        $unread = get_posts([
                            'post_type' => 'gradyzer_message',
                            'numberposts' => 1,
                            'meta_query' => [
                                ['key' => 'receiver_id', 'value' => $user_id],
                                ['key' => 'sender_id', 'value' => $sender_id],
                                ['key' => 'is_read', 'value' => '0']
                            ]
                        ]);
                        $is_unread = !empty($unread);
                        ?>
                        <li class="inbox-item <?php echo $is_unread ? 'unread' : 'read'; ?>" 
                            data-id="<?php echo esc_attr($msg->ID); ?>" 
                            data-user="<?php echo esc_attr($sender_id); ?>">
                            <strong><?php echo get_the_author_meta('display_name', $sender_id); ?></strong><br>
                            <span><?php echo wp_trim_words($msg->post_content, 12); ?></span><br>
                            <em><?php echo get_the_date('', $msg); ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="gradyzer-inbox-right">
                <div class="message-content"></div>
                <form class="reply-form" style="display:none;">
                    <textarea rows="4" placeholder="Type your reply..."></textarea>
                    <button type="submit">Send Reply</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// 🔔 Notification Bubble Shortcode
add_shortcode('gradyzer_notification', function () {
    if (!is_user_logged_in()) return '';

    $user_id = get_current_user_id();

    // ✅ Count total unread messages
    $messages = get_posts([
        'post_type' => 'gradyzer_message',
        'numberposts' => -1,
        'meta_query' => [
            ['key' => 'receiver_id', 'value' => $user_id],
            ['key' => 'is_read', 'value' => '0']
        ]
    ]);

    $count = count($messages);
    $label = "📨 " . $count;

    return '<a href="' . esc_url(site_url('/inbox')) . '" id="gradyzer-bubble" class="gradyzer-bubble-link">' . $label . '</a>';
});
