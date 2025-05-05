document.addEventListener('DOMContentLoaded', function() {
    const chatbotForm = document.getElementById('chatbot-form');
    const userInput = document.getElementById('user-input');
    const chatbotMessages = document.getElementById('chatbot-messages');

    if (chatbotForm) {
        chatbotForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = userInput.value.trim();
            if (!message) return;

            // Add user message to chat
            addMessage(message, 'user');
            userInput.value = '';

            try {
                // Show typing indicator
                const typingIndicator = addTypingIndicator();

                // Send message to backend
                const response = await fetch('/api/chatbot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                });

                // Remove typing indicator
                typingIndicator.remove();

                const data = await response.json();
                
                if (data.error) {
                    addMessage('Désolé, une erreur est survenue. Veuillez réessayer.', 'bot error');
                } else {
                    addMessage(data.response, 'bot');
                }
            } catch (error) {
                console.error('Error:', error);
                addMessage('Désolé, une erreur est survenue. Veuillez réessayer.', 'bot error');
            }
        });
    }

    function addMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = message;
        
        messageDiv.appendChild(contentDiv);
        chatbotMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function addTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot typing';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
        
        typingDiv.appendChild(contentDiv);
        chatbotMessages.appendChild(typingDiv);
        
        // Scroll to bottom
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        
        return typingDiv;
    }
}); 