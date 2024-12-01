<?php
function chatbot_resources_page() {
    global $wpdb;
    $message = '';
    
    // Add nonce verification and capability check
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
        if (!check_admin_referer('upload_resource')) {
            wp_die(__('Security check failed'));
        }

        // Define allowed file types
        $allowed_types = array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $upload = wp_handle_upload($_FILES['resource_file'], array('test_form' => false));
            
        if (!isset($upload['error']) && in_array($upload['type'], $allowed_types)) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_resources',
                array(
                    'title' => sanitize_text_field($_POST['title']),
                    'file_url' => $upload['url'],
                    'file_type' => $upload['type'],
                    'description' => sanitize_textarea_field($_POST['description'])
                ),
                array('%s', '%s', '%s', '%s')
            );
            $message = '<div class="notice notice-success"><p>Resource uploaded successfully!</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>Upload failed: ' . 
                      (isset($upload['error']) ? esc_html($upload['error']) : 'Invalid file type') . '</p></div>';
        }
    }

    // Get resources
    $resources = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}chatbot_resources ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Resource Management', 'chatbot-plugin'); ?></h1>
        <?php echo wp_kses_post($message); ?>

        <!-- Upload form section -->
        <div class="card">
            <h2><?php echo esc_html__('Upload New Resource', 'chatbot-plugin'); ?></h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('upload_resource'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="title">Title</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea name="description" id="description" rows="4" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="resource_file">File</label></th>
                        <td>
                            <input type="file" name="resource_file" id="resource_file" required>
                            <p class="description">Allowed types: jpg, jpeg, png, gif, pdf, doc, docx</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Upload Resource'); ?>
            </form>
        </div>

        <!-- Resources list section -->
        <div class="card">
            <h2><?php echo esc_html__('Uploaded Resources', 'chatbot-plugin'); ?></h2>
            <?php if (empty($resources)): ?>
                <p><?php echo esc_html__('No resources found.', 'chatbot-plugin'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Views</th>
                            <th>Downloads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td><?php echo esc_html($resource->title); ?></td>
                            <td><?php echo esc_html($resource->file_type); ?></td>
                            <td><?php echo absint($resource->views); ?></td>
                            <td><?php echo absint($resource->downloads); ?></td>
                            <td>
                                <?php
                                $actions = sprintf(
                                    '<a href="%1$s" class="button" target="_blank">%2$s</a> ',
                                    esc_url($resource->file_url),
                                    esc_html__('View', 'chatbot-plugin')
                                );
                                $actions .= sprintf(
                                    '<button class="button delete-resource" data-id="%d" data-nonce="%s">%s</button>',
                                    absint($resource->id),
                                    wp_create_nonce('delete_resource_' . $resource->id),
                                    esc_html__('Delete', 'chatbot-plugin')
                                );
                                echo $actions;
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
