<style>
/* ============================================
   TradeLens AI Chat Widget
   ============================================ */

.chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #7c3aed);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(79,142,247,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
    z-index: 1000;
    transition: transform 0.2s, box-shadow 0.2s;
}
.chat-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 28px rgba(79,142,247,0.6);
}
.chat-fab .fab-icon-open  { display: flex; }
.chat-fab .fab-icon-close { display: none; }
.chat-fab.open .fab-icon-open  { display: none; }
.chat-fab.open .fab-icon-close { display: flex; }

/* Notification dot */
.chat-fab .notif-dot {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 10px;
    height: 10px;
    background: var(--green);
    border-radius: 50%;
    border: 2px solid var(--bg-primary);
    display: none;
}
.chat-fab.has-notif .notif-dot { display: block; }

/* Panel */
.chat-panel {
    position: fixed;
    bottom: 96px;
    right: 28px;
    width: 370px;
    max-width: calc(100vw - 40px);
    height: 520px;
    max-height: calc(100vh - 120px);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    display: flex;
    flex-direction: column;
    z-index: 999;
    opacity: 0;
    transform: translateY(18px) scale(0.96);
    pointer-events: none;
    transition: opacity 0.22s ease, transform 0.22s ease;
}
.chat-panel.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: all;
}

/* Panel Header */
.chat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-card-alt);
    border-radius: var(--radius) var(--radius) 0 0;
}
.chat-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: white;
    flex-shrink: 0;
}
.chat-header-info { flex: 1; min-width: 0; }
.chat-header-name { font-size: 14px; font-weight: 600; }
.chat-header-status {
    font-size: 11px;
    color: var(--green);
    display: flex;
    align-items: center;
    gap: 4px;
}
.chat-header-status::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--green);
    border-radius: 50%;
    display: inline-block;
}
.chat-header-actions { display: flex; gap: 4px; }
.chat-header-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: var(--transition);
}
.chat-header-btn:hover { background: var(--bg-secondary); color: var(--text-primary); }

/* Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    scroll-behavior: smooth;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-track { background: transparent; }
.chat-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

/* Message bubbles */
.chat-msg {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    max-width: 100%;
}
.chat-msg.user-msg { flex-direction: row-reverse; }

.msg-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}
.chat-msg.ai-msg .msg-avatar {
    background: linear-gradient(135deg, var(--accent), #7c3aed);
    color: white;
}
.chat-msg.user-msg .msg-avatar {
    background: var(--bg-card-alt);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

.msg-bubble {
    padding: 10px 13px;
    border-radius: 14px;
    font-size: 13.5px;
    line-height: 1.55;
    max-width: calc(100% - 42px);
    word-wrap: break-word;
}
.chat-msg.ai-msg .msg-bubble {
    background: var(--bg-card-alt);
    border: 1px solid var(--border);
    border-bottom-left-radius: 4px;
    color: var(--text-primary);
}
.chat-msg.user-msg .msg-bubble {
    background: var(--accent);
    color: white;
    border-bottom-right-radius: 4px;
}

/* Typing indicator */
.chat-typing {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.typing-bubble {
    background: var(--bg-card-alt);
    border: 1px solid var(--border);
    border-radius: 14px;
    border-bottom-left-radius: 4px;
    padding: 12px 16px;
    display: flex;
    gap: 4px;
    align-items: center;
}
.typing-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--text-muted);
    animation: typingPulse 1.2s infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingPulse {
    0%, 80%, 100% { transform: scale(0.7); opacity: 0.5; }
    40%           { transform: scale(1);   opacity: 1; }
}

/* Suggestions */
.chat-suggestions {
    padding: 10px 16px 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.suggestion-chip {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.suggestion-chip:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-glow);
}

/* Input */
.chat-input-area {
    padding: 12px 14px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.chat-input {
    flex: 1;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 9px 14px;
    color: var(--text-primary);
    font-size: 13.5px;
    font-family: inherit;
    outline: none;
    resize: none;
    max-height: 100px;
    transition: var(--transition);
    line-height: 1.4;
}
.chat-input:focus { border-color: var(--accent); }
.chat-input::placeholder { color: var(--text-muted); }

.chat-send-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: var(--accent);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    transition: var(--transition);
}
.chat-send-btn:hover:not(:disabled) { background: var(--accent-hover); transform: scale(1.05); }
.chat-send-btn:disabled { background: var(--border); cursor: not-allowed; }

/* API key warning */
.chat-setup-banner {
    margin: 16px;
    padding: 14px;
    background: var(--yellow-bg);
    border: 1px solid rgba(251,191,36,0.3);
    border-radius: var(--radius-sm);
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.5;
}
.chat-setup-banner a { color: var(--accent); }

/* Responsive */
@media (max-width: 480px) {
    .chat-panel { right: 12px; bottom: 80px; width: calc(100vw - 24px); }
    .chat-fab { right: 20px; bottom: 20px; }
}

/* Markdown-like formatting in AI messages */
.msg-bubble strong { font-weight: 700; color: inherit; }
.msg-bubble em     { font-style: italic; }
.msg-bubble code {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
    padding: 1px 5px;
    font-family: monospace;
    font-size: 12px;
}
.msg-bubble ul, .msg-bubble ol {
    padding-left: 18px;
    margin: 6px 0;
}
.msg-bubble li { margin-bottom: 3px; }
.msg-bubble p  { margin-bottom: 6px; }
.msg-bubble p:last-child { margin-bottom: 0; }
</style>

<!-- ====== FAB Button ====== -->
<button class="chat-fab" id="chat-fab" onclick="toggleChat()" title="Ask TradeLens AI">
    <span class="fab-icon-open"><i class="fa-solid fa-robot"></i></span>
    <span class="fab-icon-close"><i class="fa-solid fa-xmark"></i></span>
    <span class="notif-dot"></span>
</button>

<!-- ====== Chat Panel ====== -->
<div class="chat-panel" id="chat-panel">

    <div class="chat-header">
        <div class="chat-avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="chat-header-info">
            <div class="chat-header-name">TradeLens AI</div>
            <div class="chat-header-status">Online — powered by Ollama (local)</div>
        </div>
        <div class="chat-header-actions">
            <button class="chat-header-btn" onclick="clearChat()" title="Clear chat">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <button class="chat-header-btn" onclick="toggleChat()" title="Close">
                <i class="fa-solid fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="chat-messages" id="chat-messages"></div>

    <div class="chat-suggestions" id="chat-suggestions"></div>

    <div class="chat-input-area">
        <textarea
            class="chat-input"
            id="chat-input"
            placeholder="Ask about your trades..."
            rows="1"
            onkeydown="chatKeydown(event)"
            oninput="autoResizeInput(this)"
        ></textarea>
        <button class="chat-send-btn" id="chat-send-btn" onclick="sendMessage()" disabled>
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= BASE_URL ?>';
    const USER_INITIAL = '<?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>';
    const AI_CONFIGURED = true; // Ollama needs no API key;

    let chatHistory  = [];   // [{role, content}]
    let isOpen       = false;
    let isTyping     = false;
    let hasGreeted   = false;

    const SUGGESTIONS = [
        'How is my win rate?',
        'Analyze my P&L',
        'What emotion patterns do I have?',
        'How can I improve?',
        'What was my best trade?',
        'Tips to reduce losses',
    ];

    // ---- Toggle ----
    window.toggleChat = function () {
        isOpen = !isOpen;
        document.getElementById('chat-panel').classList.toggle('open', isOpen);
        document.getElementById('chat-fab').classList.toggle('open', isOpen);
        document.getElementById('chat-fab').classList.remove('has-notif');

        if (isOpen && !hasGreeted) {
            hasGreeted = true;
            if (!AI_CONFIGURED) {
                showSetupBanner();
            } else {
                showWelcome();
            }
        }

        if (isOpen) {
            setTimeout(() => document.getElementById('chat-input').focus(), 200);
        }
    };

    window.clearChat = function () {
        chatHistory = [];
        hasGreeted  = false;
        document.getElementById('chat-messages').innerHTML = '';
        document.getElementById('chat-suggestions').innerHTML = '';
        showWelcome();
    };

    // ---- Setup Banner ----
    function showSetupBanner() {
        const el = document.getElementById('chat-messages');
        el.innerHTML = `
            <div class="chat-setup-banner">
                <strong style="color:var(--yellow)"><i class="fa-solid fa-triangle-exclamation"></i> Ollama Not Running</strong><br><br>
                Start Ollama to use TradeLens AI:<br>
                1. Download Ollama at <a href="https://ollama.com" target="_blank">ollama.com</a><br>
                2. Open Terminal and run: <code>ollama pull llama3.2</code><br>
                3. Then run: <code>ollama serve</code><br>
                4. Refresh this page and try again
            </div>
        `;
    }

    // ---- Welcome message ----
    function showWelcome() {
        addAiMessage("Hi! I'm your TradeLens AI assistant. I can see your trade history and stats, so feel free to ask me anything — your win rate, P&L analysis, emotional patterns, or how to improve your trading. What would you like to know?");
        renderSuggestions();
    }

    // ---- Suggestions ----
    function renderSuggestions() {
        const el = document.getElementById('chat-suggestions');
        el.innerHTML = SUGGESTIONS.map(s =>
            `<button class="suggestion-chip" onclick="useSuggestion(this, '${s}')">${s}</button>`
        ).join('');
    }

    window.useSuggestion = function (btn, text) {
        document.getElementById('chat-suggestions').innerHTML = '';
        document.getElementById('chat-input').value = text;
        sendMessage();
    };

    // ---- Send ----
    window.sendMessage = async function () {
        const input = document.getElementById('chat-input');
        const msg   = input.value.trim();
        if (!msg || isTyping) return;

        // Clear suggestions
        document.getElementById('chat-suggestions').innerHTML = '';

        input.value = '';
        input.style.height = '';
        document.getElementById('chat-send-btn').disabled = true;

        addUserMessage(msg);

        if (!AI_CONFIGURED) {
            showSetupBanner();
            return;
        }

        showTyping();

        try {
            const res  = await fetch(BASE + '/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg, history: chatHistory }),
            });
            const json = await res.json();
            hideTyping();

            if (json.success) {
                const reply = json.data.reply;
                chatHistory.push({ role: 'user',      content: msg   });
                chatHistory.push({ role: 'assistant', content: reply });
                // Keep history bounded
                if (chatHistory.length > 40) chatHistory = chatHistory.slice(-40);
                addAiMessage(reply);
            } else {
                addAiMessage('Sorry, I ran into an issue: ' + escHtml(json.message), true);
            }
        } catch (e) {
            hideTyping();
            addAiMessage('Connection error. Please check your network and try again.', true);
        }
    };

    window.chatKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    window.autoResizeInput = function (el) {
        el.style.height = '';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
        document.getElementById('chat-send-btn').disabled = el.value.trim() === '';
    };

    // ---- Render helpers ----
    function addUserMessage(text) {
        const el = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'chat-msg user-msg';
        div.innerHTML = `
            <div class="msg-avatar">${USER_INITIAL}</div>
            <div class="msg-bubble">${escHtml(text)}</div>
        `;
        el.appendChild(div);
        scrollToBottom();
    }

    function addAiMessage(text, isError = false) {
        isTyping = false;
        const el  = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'chat-msg ai-msg';
        div.innerHTML = `
            <div class="msg-avatar"><i class="fa-solid fa-robot" style="font-size:12px"></i></div>
            <div class="msg-bubble${isError ? ' ' : ''}" style="${isError ? 'border-color:var(--red-border);color:var(--red)' : ''}">${formatAiText(text)}</div>
        `;
        el.appendChild(div);
        scrollToBottom();

        // Notify if panel is closed
        if (!isOpen) {
            document.getElementById('chat-fab').classList.add('has-notif');
        }
    }

    function showTyping() {
        isTyping = true;
        const el  = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'chat-msg ai-msg';
        div.id        = 'typing-indicator';
        div.innerHTML = `
            <div class="msg-avatar"><i class="fa-solid fa-robot" style="font-size:12px"></i></div>
            <div class="typing-bubble">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
        el.appendChild(div);
        scrollToBottom();
    }

    function hideTyping() {
        const el = document.getElementById('typing-indicator');
        if (el) el.remove();
        isTyping = false;
    }

    function scrollToBottom() {
        const el = document.getElementById('chat-messages');
        el.scrollTop = el.scrollHeight;
    }

    // Basic markdown-like formatting for AI responses
    function formatAiText(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            // Bold: **text**
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Italic: *text*
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            // Inline code: `code`
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // Bullet lines: "- item" or "• item"
            .replace(/^[-•]\s+(.+)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            // Numbered lists: "1. item"
            .replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>')
            // Double newline → paragraph break
            .replace(/\n\n/g, '</p><p>')
            // Single newline → <br>
            .replace(/\n/g, '<br>')
            // Wrap in paragraphs
            .replace(/^(.+)$/, '<p>$1</p>');
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Enable send button on input
    document.getElementById('chat-input').addEventListener('input', function () {
        document.getElementById('chat-send-btn').disabled = this.value.trim() === '';
    });

})();
</script>
