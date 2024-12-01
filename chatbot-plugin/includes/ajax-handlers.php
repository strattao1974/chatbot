<?php
// Constants for validation
define('MAX_MESSAGE_LENGTH', 500);
define('CACHE_EXPIRY', 3600); // 1 hour

// Get chatbot response
function get_chatbot_response($message) {
    // Input validation
    if (empty($message) || strlen($message) > MAX_MESSAGE_LENGTH) {
        return "Please enter a valid message between 1 and " . MAX_MESSAGE_LENGTH . " characters.";
    }
    
    $message = strtolower(trim($message));
    
    // Check cache first
    $cache_key = 'chatbot_response_' . md5($message);
    $cached_response = wp_cache_get($cache_key);
    if ($cached_response !== false) {
        return $cached_response;
    }

    // Keyword arrays for better matching
    $keywords = [
        'beginner' => ['beginner', 'start', 'new', 'basic'],
        'chef_knife' => ['chef', 'kitchen', 'cooking', 'culinary'],
        'bushcraft' => ['bush', 'outdoor', 'survival', 'camping'],
        'maker' => ['maker', 'comprehensive', 'advanced', 'master'],
        'location' => ['location', 'where', 'address', 'facility'],
        'pricing' => ['cost', 'price', 'fee', 'payment'],
        'booking' => ['book', 'register', 'enroll', 'sign']
    ];

    // Define website URLs with HTML links
    $links = array(
        'beginner' => '<a href="https://www.artisanestate.com.au/beginner-blacksmithing/" target="_blank">click here</a>',
        'chef_knife' => '<a href="https://www.artisanestate.com.au/chef-knife/" target="_blank">click here</a>',
        'bushcraft' => '<a href="https://www.artisanestate.com.au/bushcraft-knife/" target="_blank">click here</a>',
        'maker' => '<a href="https://www.artisanestate.com.au/become-the-maker/" target="_blank">click here</a>',
        'main' => '<a href="https://www.artisanestate.com.au/portfolio/blacksmithing/" target="_blank">click here</a>'
    );
    
    // Improved response matching
    foreach ($keywords as $type => $terms) {
        if (array_filter($terms, function($term) use ($message) {
            return strpos($message, $term) !== false;
        })) {
            $response = get_response_by_type($type, $links);
            wp_cache_set($cache_key, $response, '', CACHE_EXPIRY);
            return $response;
        }
    }

    // Default response
    $default_response = "I'm here to help you learn about our blacksmithing courses. To view all our courses, {$links['main']}. Feel free to ask about specific courses like our beginner classes, knife making, or comprehensive programs.";
    wp_cache_set($cache_key, $default_response, '', CACHE_EXPIRY);
    return $default_response;
}

// Helper function to get response by type
function get_response_by_type($type, $links) {
    $responses = [
        'beginner' => "Our Beginner Blacksmithing course is perfect for those starting out. You'll learn basic techniques and create your first projects. To learn more, {$links['beginner']}.",
        'chef_knife' => "In our Chef Knife Making course, you'll create your own professional kitchen knife from start to finish. For course details, {$links['chef_knife']}.",
        'bushcraft' => "Our Bushcraft Knife Making course teaches you to forge your own outdoor knife. To find out more, {$links['bushcraft']}.",
        'maker' => "Become the Maker is our comprehensive course where you'll learn traditional blacksmithing skills and create multiple projects. To discover more about this extensive course, {$links['maker']}.",
        'location' => "We're located in Somersby NSW. The exact address will be provided when you book your course. For more information about our facility, {$links['main']}.",
        'pricing' => "You can find current course pricing and available dates on our website. To view course details and pricing, {$links['main']}.",
        'booking' => "You can book any of our courses through our website. To view all available courses, {$links['main']}"
    ];
    
    // Add default response if type not found
    $default = "I'm here to help you learn about our blacksmithing courses. To view all our courses, {$links['main']}. Feel free to ask about specific courses like our beginner classes, knife making, or comprehensive programs.";
    
    return isset($responses[$type]) ? $responses[$type] : $default;
}

// Handle chat messages with improved error handling
function handle_chat_message() {
    try {
        if (!check_ajax_referer('chatbot-nonce', 'nonce', false)) {
            throw new Exception('Invalid security token');
        }

        if (!isset($_POST['message'])) {
            throw new Exception('No message provided');
        }

        global $wpdb;
        $message = sanitize_text_field($_POST['message']);
        $response = get_chatbot_response($message);
        
        // Track analytics
        do_action('chatbot_message_processed', $message, $response);
        
        // Log the chat
        $result = $wpdb->insert(
            $wpdb->prefix . 'chatbot_chat_history',
            array(
                'user_message' => $message,
                'bot_response' => $response,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            throw new Exception('Failed to log chat message');
        }

        wp_send_json_success(array('response' => $response));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'An error occurred: ' . $e->getMessage()
        ));
    }
}
add_action('wp_ajax_chatbot_message', 'handle_chat_message');
add_action('wp_ajax_nopriv_chatbot_message', 'handle_chat_message');
