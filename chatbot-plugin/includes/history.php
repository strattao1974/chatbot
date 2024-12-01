<?php
function chatbot_history_page() {
    global $wpdb;
    
    // Handle deletion of chat history
    if (isset($_POST['clear_history']) && check_admin_referer('clear_chat_history')) {
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}chatbot_chat_history");
        echo '<div class="notice notice-success"><p>Chat history cleared successfully!</p></div>';
    }
    
    // Get chat history with pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chatbot_chat_history");
    $total_pages = ceil($total_items / $per_page);
    
    $chats = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}chatbot_chat_history 
         ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
    ?>
    <div class="wrap">
        <h1>Chat History</h1>

        <div class="card">
            <form method="post">
                <?php wp_nonce_field('clear_chat_history'); ?>
                <input type="submit" name="clear_history" class="button" 
                       value="Clear History" 
                       onclick="return confirm('Are you sure you want to clear all chat history?');">
            </form>
        </div>

        <div class="card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User Message</th>
                        <th>Bot Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chats as $chat): ?>
                    <tr>
                        <td><?php echo esc_html(wp_date('Y-m-d H:i:s', strtotime($chat->created_at))); ?></td>
                        <td><?php echo esc_html($chat->user_message); ?></td>
                        <td><?php echo esc_html($chat->bot_response); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
