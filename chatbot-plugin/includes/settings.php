<?php
function chatbot_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chatbot_settings'])) {
        update_option('chatbot_settings', array(
            'welcome_message' => sanitize_text_field($_POST['welcome_message']),
            'enable_analytics' => isset($_POST['enable_analytics']),
            'enable_file_preview' => isset($_POST['enable_file_preview'])
        ));
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    $settings = get_option('chatbot_settings');
    ?>
    <div class="wrap">
        <h1>Chatbot Settings</h1>

        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="welcome_message">Welcome Message</label></th>
                    <td>
                        <input type="text" name="welcome_message" id="welcome_message" 
                               value="<?php echo esc_attr($settings['welcome_message']); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>Features</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_analytics" 
                                   <?php checked($settings['enable_analytics']); ?>>
                            Enable Analytics
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="enable_file_preview" 
                                   <?php checked($settings['enable_file_preview']); ?>>
                            Enable File Preview
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}
