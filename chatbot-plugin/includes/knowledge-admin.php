<?php
function chatbot_knowledge_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_knowledge';
    $message = '';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_entry'])) {
            // Handle manual entry
            $wpdb->insert(
                $table_name,
                array(
                    'topic' => sanitize_text_field($_POST['topic']),
                    'keywords' => sanitize_text_field($_POST['keywords']),
                    'response' => wp_kses_post($_POST['response']),
                    'url' => esc_url_raw($_POST['url'])
                ),
                array('%s', '%s', '%s', '%s')
            );
            $message = '<div class="notice notice-success"><p>Entry added successfully!</p></div>';
        }
        elseif (isset($_FILES['knowledge_file'])) {
            // Handle file upload
            $file = $_FILES['knowledge_file'];
            if ($file['type'] === 'text/plain') {
                $content = file_get_contents($file['tmp_name']);
                $entries = parse_knowledge_file($content);
                
                foreach ($entries as $entry) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'topic' => sanitize_text_field($entry['topic']),
                            'keywords' => sanitize_text_field($entry['keywords']),
                            'response' => wp_kses_post($entry['response']),
                            'url' => isset($entry['url']) ? esc_url_raw($entry['url']) : ''
                        ),
                        array('%s', '%s', '%s', '%s')
                    );
                }
                $message = '<div class="notice notice-success"><p>Knowledge base updated successfully!</p></div>';
            } else {
                $message = '<div class="notice notice-error"><p>Please upload a text file.</p></div>';
            }
        }
    }

    // Get existing entries
    $entries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY topic ASC");
    ?>
    <div class="wrap">
        <h1>Knowledge Base Management</h1>
        <?php echo $message; ?>

        <!-- Upload Section -->
        <div class="card">
            <h2>Upload Knowledge Base</h2>
            <form method="post" enctype="multipart/form-data">
                <p>Upload a text file containing knowledge base entries.</p>
                <input type="file" name="knowledge_file" accept=".txt" required>
                <?php submit_button('Upload File', 'primary', 'upload_knowledge'); ?>
            </form>
        </div>

        <!-- Manual Entry Section -->
        <div class="card">
            <h2>Add New Entry</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="topic">Topic/Course Name</label></th>
                        <td><input type="text" name="topic" id="topic" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="keywords">Keywords (comma-separated)</label></th>
                        <td><input type="text" name="keywords" id="keywords" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="response">Response</label></th>
                        <td><textarea name="response" id="response" rows="5" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="url">URL (optional)</label></th>
                        <td><input type="url" name="url" id="url" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('Add Entry', 'primary', 'add_entry'); ?>
            </form>
        </div>

        <!-- Existing Entries -->
        <div class="card">
            <h2>Existing Entries</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Keywords</th>
                        <th>Response</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->topic); ?></td>
                        <td><?php echo esc_html($entry->keywords); ?></td>
                        <td><?php echo wp_kses_post($entry->response); ?></td>
                        <td><?php echo make_clickable($entry->url); ?></td>
                        <td>
                            <button class="button delete-entry" data-id="<?php echo $entry->id; ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function parse_knowledge_file($content) {
    $entries = array();
    $current_entry = array();
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) continue;
        
        if ($line === '---') {
            if (!empty($current_entry)) {
                $entries[] = $current_entry;
                $current_entry = array();
            }
            continue;
        }
        
        if (preg_match('/^(TOPIC|KEYWORDS|RESPONSE|URL):\s*(.*)$/i', $line, $matches)) {
            $key = strtolower($matches[1]);
            $value = trim($matches[2]);
            $current_entry[$key] = $value;
        }
    }
    
    if (!empty($current_entry)) {
        $entries[] = $current_entry;
    }
    
    return $entries;
}
