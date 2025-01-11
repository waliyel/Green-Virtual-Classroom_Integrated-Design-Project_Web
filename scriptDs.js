// Show Chatbox when Discussions Link is Clicked
document.getElementById('discussionLink').addEventListener('click', function (e) {
    e.preventDefault();
    const chatbox = document.getElementById('chatboxContainer');
    chatbox.style.display = 'block'; // Display the chatbox
    loadMessages(); // Load existing messages
});

// Send Message Logic
document.getElementById('messageForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'send_message');

    fetch('chat.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMessages(); // Reload messages after sending
            document.getElementById('messageInput').value = '';
            document.getElementById('fileInput').value = '';
        } else {
            alert('Message failed to send');
        }
    });
});

// Load Messages Logic
function loadMessages() {
    fetch('chat.php?action=get_messages')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('messageContainer');
            container.innerHTML = '';

            data.messages.forEach(msg => {
                const messageElement = document.createElement('div');
                messageElement.innerHTML = `
                    <p><strong>${msg.authorID}:</strong> ${msg.content}</p>
                    ${msg.fileName ? `<a href="chat.php?action=download_file&id=${msg.messageID}">📎 ${msg.fileName}</a>` : ''}
                `;
                container.appendChild(messageElement);
            });
        });
}
