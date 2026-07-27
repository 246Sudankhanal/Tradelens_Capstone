<div class="ai-chat-widget">
    <button class="ai-chat-button" onclick="toggleChatBox()">🤖</button>

    <div class="ai-chat-box" id="aiChatBox">
        <div class="ai-chat-header">
            <strong>TradeLens AI</strong>
            <button onclick="toggleChatBox()">×</button>
        </div>

        <div class="ai-chat-messages" id="aiChatMessages">
            <div class="ai-bot-message">
                Hello! I am your TradeLens AI assistant. Ask me about your trading performance.
            </div>
        </div>

        <div class="ai-chat-input">
            <input type="text" id="aiUserInput" placeholder="Type your message...">
            <button onclick="sendChatMessage()">Send</button>
        </div>
    </div>
</div>