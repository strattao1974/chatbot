jQuery(document).ready(function($) {
    const trigger = $('#chatbot-trigger');
    const widget = $('#chatbot-widget');
    const messages = $('.chatbot-messages');
    const input = $('.chatbot-input input');
    const sendBtn = $('.chatbot-input button');
    const minimizeBtn = $('.chatbot-minimize');
    
    // Show/hide chat widget
    trigger.on('click', function() {
        widget.toggleClass('active');
        if (widget.hasClass('active') && messages.children().length === 0) {
            appendMessage('bot', "Hello! I'm here to help you learn about our blacksmithing courses. What would you like to know?");
        }
    });
    
    // Minimize chat
    minimizeBtn.on('click', function(e) {
        e.preventDefault();
        widget.removeClass('active');
    });
    
    // Send message
    function sendMessage(message) {
        appendMessage('user', message);
        
        // Show typing indicator
        const typingIndicator = $('<div class="message bot-message">Typing...</div>');
        messages.append(typingIndicator);
        messages.scrollTop(messages[0].scrollHeight);
        
        $.ajax({
            url: chatbotVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'chatbot_message',
                nonce: chatbotVars.nonce,
                message: message
            },
            success: function(response) {
                typingIndicator.remove();
                if (response.success) {
                    appendMessage('bot', response.data.response);
                } else {
                    appendMessage('bot', 'Sorry, I encountered an error. Please try again.');
                }
            },
            error: function() {
                typingIndicator.remove();
                appendMessage('bot', 'Sorry, I encountered an error. Please try again.');
            }
        });
    }
    
    // Append message to chat
    function appendMessage(sender, text) {
        const messageDiv = $('<div>')
            .addClass('message')
            .addClass(sender === 'user' ? 'user-message' : 'bot-message')
            .html(text);
        messages.append(messageDiv);
        messages.scrollTop(messages[0].scrollHeight);
    }
    
    // Handle send button click
    sendBtn.on('click', function() {
        const message = input.val().trim();
        if (message) {
            sendMessage(message);
            input.val('');
        }
    });
    
    // Handle enter key
    input.on('keypress', function(e) {
        if (e.which === 13) {
            sendBtn.click();
        }
    });
});
