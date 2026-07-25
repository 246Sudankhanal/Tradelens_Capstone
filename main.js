function toggleChatBox() {
    const chatBox = document.getElementById("aiChatBox");

    if (chatBox.style.display === "block") {
        chatBox.style.display = "none";
    } else {
        chatBox.style.display = "block";
    }
}

function sendChatMessage() {
    const input = document.getElementById("aiUserInput");
    const messages = document.getElementById("aiChatMessages");

    const userMessage = input.value.trim();

    if (userMessage === "") {
        return;
    }

    const userDiv = document.createElement("div");
    userDiv.className = "ai-user-message";
    userDiv.textContent = userMessage;
    messages.appendChild(userDiv);

    const botDiv = document.createElement("div");
    botDiv.className = "ai-bot-message";
    botDiv.textContent = "AI response will be connected in Week 6.";
    messages.appendChild(botDiv);

    input.value = "";
    messages.scrollTop = messages.scrollHeight;
}