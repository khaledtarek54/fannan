document.addEventListener("DOMContentLoaded", function () {
    const chatContainer = document.getElementById('chatContainer');
    console.log("Chat Container", chatContainer);
    if (chatContainer) {
        console.log("chatContainer.scrollHeight", chatContainer.scrollHeight)
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
});
