<?php
function chatbot_dashboard_page() {
    global $wpdb;
    
    // Get chat history
    $recent_chats = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}chatbot_chat_history 
         ORDER BY created_at DESC LIMIT 20"
    );
    ?>
    <div class="wrap">
        <h1>Chatbot Dashboard</h1>
        
        <div class="card">
            <h2>Recent Conversations</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User Message</th>
                        <th>Bot Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_chats as $chat): ?>
                    <tr>
                        <td><?php echo esc_html(wp_date('Y-m-d H:i:s', strtotime($chat->created_at))); ?></td>
                        <td><?php echo esc_html($chat->user_message); ?></td>
                        <td><?php echo wp_kses_post($chat->bot_response); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
