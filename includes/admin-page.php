<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// 🛠️ Add Admin Menu
add_action('admin_menu', 'gradyzer_settings_menu');
function gradyzer_settings_menu() {
    add_menu_page(
        'Gradyzer Instructions',
        'Gradyzer Settings',
        'manage_options',
        'gradyzer_settings',
        'gradyzer_render_settings_page',
        'dashicons-email-alt',
        100
    );
}

// 📘 Render Settings Page
function gradyzer_render_settings_page() {
    ?>
    <div class="wrap" style="max-width:800px;">
        <h1 style="font-size:26px; margin-bottom:10px;">🖼️ Plugin Interface Preview</h1>
        <div class="gradyzer-slider">
            <?php
            $images = array(
                array('Product View', GRADYZER_MSG_URL . 'assets/screenshot1.png'),
                array('Inbox Overview', GRADYZER_MSG_URL . 'assets/screenshot2.png'),
                array('Thread Conversation', GRADYZER_MSG_URL . 'assets/screenshot3.png'),
                array('My Account Panel', GRADYZER_MSG_URL . 'assets/screenshot4.png')
            );
            foreach ($images as $img) {
                echo '<div class="gradyzer-slide">';
                echo '<img src="' . esc_url($img[1]) . '" alt="' . esc_attr($img[0]) . '" />';
                echo '<p class="gradyzer-caption">' . esc_html($img[0]) . '</p>';
                echo '<div class="gradyzer-fullview-trigger">Full View</div>';
                echo '</div>';
            }
            ?>
            <button class="gradyzer-prev">←</button>
            <button class="gradyzer-next">→</button>
        </div>

        <div class="gradyzer-fullscreen" style="display:none;">
            <div class="gradyzer-exit">Exit ✕</div>
            <img class="gradyzer-fullscreen-img" src="" alt="Full View" />
        </div>

        <h2 style="margin-top:40px;">📘 How It Works</h2>
        <?php
        $instructions = array(
            '📍 Add Message Box to Product Page' => 'To let customers message product authors directly, insert the shortcode <code>[gradyzer_product_message]</code> inside your single product template. You can place it below the price, within a custom tab, or wherever you prefer.',
            '📥 Inbox Page' => 'Create a page titled <strong>Inbox</strong>. The plugin automatically inserts the shortcode <code>[gradyzer_inbox]</code> to display threaded message history.',
            '🔔 Notification Bubble in Navigation' => 'Logged-in users automatically see a notification bubble in your navigation menu showing the unread count.',
            '🗂️ My Account → Inbox Tab' => 'Each user has an Inbox tab in their WooCommerce account dashboard. The plugin handles this automatically — no configuration required.'
        );
        foreach ($instructions as $title => $desc) {
            echo '<div style="margin-bottom:15px; padding:12px; background:#f9f9f9; border:1px solid #ddd; border-radius:8px;">';
            echo '<strong>' . esc_html($title) . '</strong>';
            echo '<p style="margin-top:6px;">' . $desc . '</p>';
            echo '</div>';
        }
        ?>
    </div>
    <?php
}
