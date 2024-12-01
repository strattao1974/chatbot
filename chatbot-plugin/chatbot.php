<?php
/*
Plugin Name: Blacksmith Course Chatbot
Description: Custom chatbot for blacksmithing courses using OpenAI. Use shortcode [blacksmith_chatbot] to display the chatbot.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Add menu item to WordPress admin
function blacksmith_chatbot_menu() {
  add_menu_page(
    'Blacksmith Chatbot Settings',
    'Chatbot Settings',
    'manage_options',
    'blacksmith-chatbot',
    'blacksmith_chatbot_settings_page',
    'dashicons-format-chat',
    30
  );
}
add_action('admin_menu', 'blacksmith_chatbot_menu');

// Register settings
function blacksmith_chatbot_settings_init() {
  register_setting('blacksmith_chatbot', 'blacksmith_chatbot_options');

  add_settings_section(
    'blacksmith_chatbot_section',
    'API Settings',
    'blacksmith_chatbot_section_callback',
    'blacksmith-chatbot'
  );

  add_settings_field(
    'openai_api_key',
    'OpenAI API Key',
    'blacksmith_chatbot_api_field_callback',
    'blacksmith-chatbot',
    'blacksmith_chatbot_section'
  );
}
add_action('admin_init', 'blacksmith_chatbot_settings_init');

// Section callback function
function blacksmith_chatbot_section_callback() {
  echo '<p>Enter your OpenAI API settings below:</p>';
  echo '<p>Use the shortcode <code>[blacksmith_chatbot]</code> to display the chatbot on any page or post.</p>';
}

// Field callback function
function blacksmith_chatbot_api_field_callback() {
  $options = get_option('blacksmith_chatbot_options');
  $api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
  ?>
  <input type='text' 
         name='blacksmith_chatbot_options[openai_api_key]' 
         value='<?php echo esc_attr($api_key); ?>' 
         style='width: 300px;'
  />
  <p class="description">Enter your OpenAI API key here. You can get one from <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI's website</a>.</p>
  <?php
}

// Create the settings page
function blacksmith_chatbot_settings_page() {
  if (!current_user_can('manage_options')) {
    return;
  }
  ?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
      <?php
      settings_fields('blacksmith_chatbot');
      do_settings_sections('blacksmith-chatbot');
      submit_button('Save Settings');
      ?>
    </form>
  </div>
  <?php
}

// Get API key helper function
function get_openai_api_key() {
  $options = get_option('blacksmith_chatbot_options');
  return isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
}

// Enqueue scripts and styles
function blacksmith_chatbot_enqueue_scripts() {
  global $post;
  // Only enqueue if the shortcode is present
  if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'blacksmith_chatbot')) {
    wp_enqueue_style('blacksmith-chatbot-style', plugins_url('css/chatbot.css', __FILE__));
    wp_enqueue_script('blacksmith-chatbot-script', plugins_url('js/chatbot.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('blacksmith-chatbot-script', 'chatbotAjax', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('chatbot-nonce')
    ));
  }
}
add_action('wp_enqueue_scripts', 'blacksmith_chatbot_enqueue_scripts');

// Shortcode implementation
function blacksmith_chatbot_shortcode() {
  ob_start();
  ?>
  <button id="chatbot-trigger">Chat with Us</button>
  <div id="blacksmith-chatbot">
    <div class="chat-header">
      <h3>Course Assistant</h3>
      <button class="minimize-chat">×</button>
    </div>
    <div class="chat-messages"></div>
    <div class="chat-input">
      <input type="text" placeholder="Ask about our courses...">
      <button type="submit">Send</button>
    </div>
  </div>
  <?php
  return ob_get_clean();
}
add_shortcode('blacksmith_chatbot', 'blacksmith_chatbot_shortcode');

// Handle AJAX requests
function handle_chatbot_query() {
  check_ajax_nonce('chatbot-nonce', 'nonce');
  $query = sanitize_text_field($_POST['query']);
  $api_key = get_openai_api_key();
  
  if (!$api_key) {
    wp_send_json_error(array('message' => 'OpenAI API key not configured'));
    return;
  }

  $system_prompt = "You are a helpful assistant for Artisan Estate, a blacksmithing school in Australia. " .
                   "You help people learn about blacksmithing classes and provide information about courses. " .
                   "Keep responses friendly and concise. If you're not sure about specific details, " .
                   "recommend visiting the website or contacting the school directly.";

  $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
    'headers' => array(
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ),
    'body' => json_encode(array(
      'model' => 'gpt-3.5-turbo',
      'messages' => array(
        array('role' => 'system', 'content' => $system_prompt),
        array('role' => 'user', 'content' => $query)
      ),
      'max_tokens' => 150,
      'temperature' => 0.7,
    )),
    'timeout' => 15,
    'sslverify' => false // Add this line to bypass SSL verification if needed
  ));

  if (is_wp_error($response)) {
    wp_send_json_error(array('message' => 'Failed to connect to OpenAI: ' . $response->get_error_message()));
    return;
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);
  
  if (isset($body['choices'][0]['message']['content'])) {
    wp_send_json_success(array(
      'response' => $body['choices'][0]['message']['content']
    ));
  } else {
    wp_send_json_error(array(
      'message' => 'Invalid response from OpenAI',
      'debug' => $body // Add this for debugging
    ));
  }
}
add_action('wp_ajax_chatbot_query', 'handle_chatbot_query');
add_action('wp_ajax_nopriv_chatbot_query', 'handle_chatbot_query');
