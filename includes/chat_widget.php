
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
    transition: opacity 0.22s ease, transform 0.22s ease, width 0.22s ease, height 0.22s ease, inset 0.22s ease;
    overflow: hidden;
}
.chat-panel.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: all;
}
.chat-panel.expanded {
    inset: 16px;
    bottom: 16px;
    right: 16px;
    left: 16px;
    top: 16px;
    width: auto;
    height: auto;
    max-width: none;
    max-height: none;
    border-radius: 16px;
    z-index: 1100;
}
.chat-panel.expanded .chat-header {
    border-radius: 16px 16px 0 0;
}
body.chat-expanded .chat-fab { display: none; }

.chat-body {
    display: flex;
    flex: 1;
    min-height: 0;
}

.chat-history {
    width: 0;
    overflow: hidden;
    border-right: 0;
    background: var(--bg-secondary);
    transition: width 0.2s ease;
    display: flex;
    flex-direction: column;
}
.chat-panel.history-open .chat-history,
.chat-panel.expanded .chat-history {
    width: 240px;
    border-right: 1px solid var(--border);
}
.chat-history-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 12px 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.chat-history-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 8px 12px;
}
.chat-history-item {
    width: 100%;
    text-align: left;
    background: transparent;
    border: none;
    border-radius: 8px;
    padding: 10px 10px;
    color: var(--text-secondary);
    cursor: pointer;
    font-family: inherit;
    margin-bottom: 4px;
}
.chat-history-item:hover { background: var(--bg-card-alt); color: var(--text-primary); }
.chat-history-item.active { background: var(--accent-glow); color: var(--accent); }
.chat-history-item .hist-title {
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-history-item .hist-meta {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 3px;
    display: flex;
    justify-content: space-between;
    gap: 8px;
}
.chat-history-item .hist-del {
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0 2px;
}
.chat-history-item .hist-del:hover { color: var(--red); }
.chat-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
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
    .chat-panel.expanded { inset: 8px; }
    .chat-panel.history-open .chat-history,
    .chat-panel.expanded .chat-history { width: 180px; }
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
            <div class="chat-header-status" id="chat-header-status">Online — powered by OpenAI</div>
        </div>
        <div class="chat-header-actions">
            <button class="chat-header-btn" onclick="toggleChatHistory()" title="Chat history">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </button>
            <button class="chat-header-btn" onclick="newChatThread()" title="New chat">
                <i class="fa-solid fa-plus"></i>
            </button>
            <button class="chat-header-btn" onclick="toggleChatExpand()" title="Full screen">
                <i class="fa-solid fa-expand" id="chat-expand-icon"></i>
            </button>
            <button class="chat-header-btn" onclick="toggleChat()" title="Close">
                <i class="fa-solid fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="chat-body">
        <aside class="chat-history" id="chat-history">
            <div class="chat-history-head">
                <span>History</span>
                <button class="chat-header-btn" onclick="newChatThread()" title="New chat"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="chat-history-list" id="chat-history-list">
                <div class="text-muted text-sm" style="padding:8px">No saved chats yet.</div>
            </div>
        </aside>
        <div class="chat-main">
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
    </div>
</div>

<script>
(function () {
    const BASE = '<?= BASE_URL ?>';
    const USER_INITIAL = '<?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>';

    let chatHistory  = [];
    let conversationId = 0;
    let isOpen       = false;
    let isExpanded   = false;
    let historyOpen  = false;
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

    window.toggleChat = function () {
        isOpen = !isOpen;
        document.getElementById('chat-panel').classList.toggle('open', isOpen);
        document.getElementById('chat-fab').classList.toggle('open', isOpen);
        document.getElementById('chat-fab').classList.remove('has-notif');
        if (!isOpen) {
            isExpanded = false;
            applyExpand();
        }
        if (isOpen && !hasGreeted && chatHistory.length === 0) {
            hasGreeted = true;
            showWelcome();
            loadConversationList();
        }
        if (isOpen) {
            setTimeout(() => document.getElementById('chat-input').focus(), 200);
        }
    };

    window.toggleChatExpand = function () {
        if (!isOpen) toggleChat();
        isExpanded = !isExpanded;
        if (isExpanded) historyOpen = true;
        applyExpand();
        loadConversationList();
    };

    window.toggleChatHistory = function () {
        if (!isOpen) toggleChat();
        historyOpen = !historyOpen;
        document.getElementById('chat-panel').classList.toggle('history-open', historyOpen);
        loadConversationList();
    };

    function applyExpand() {
        const panel = document.getElementById('chat-panel');
        panel.classList.toggle('expanded', isExpanded);
        document.body.classList.toggle('chat-expanded', isExpanded);
        const icon = document.getElementById('chat-expand-icon');
        if (icon) icon.className = isExpanded ? 'fa-solid fa-compress' : 'fa-solid fa-expand';
        panel.classList.toggle('history-open', historyOpen || isExpanded);
    }

    window.newChatThread = function () {
        conversationId = 0;
        chatHistory = [];
        hasGreeted = true;
        document.getElementById('chat-messages').innerHTML = '';
        document.getElementById('chat-suggestions').innerHTML = '';
        document.getElementById('chat-header-status').textContent = 'New conversation';
        showWelcome();
        loadConversationList();
    };

    window.clearChat = window.newChatThread;

    function showWelcome() {
        addAiMessage("Hi! I'm your TradeLens AI assistant. I can see your trade history and stats — ask about win rate, P&L, emotions, or how to improve. Past chats are saved in History.", false, true);
        renderSuggestions();
    }

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

    async function loadConversationList() {
        const list = document.getElementById('chat-history-list');
        try {
            const res = await fetch(BASE + '/api/chat_history.php?action=list');
            const json = await res.json();
            if (!json.success) return;
            const rows = json.data.conversations || [];
            if (!rows.length) {
                list.innerHTML = '<div class="text-muted text-sm" style="padding:8px">No saved chats yet.</div>';
                return;
            }
            list.innerHTML = rows.map(c => {
                const active = Number(c.id) === Number(conversationId) ? ' active' : '';
                const preview = (c.preview || '').slice(0, 48);
                const when = c.updated_at ? new Date(c.updated_at.replace(' ', 'T')).toLocaleString('en-US', { month: 'short', day: 'numeric' }) : '';
                return `<div class="chat-history-item${active}" data-id="${c.id}">
                    <div class="hist-title">${escHtml(c.title || 'Chat')}</div>
                    <div class="hist-meta">
                        <span>${escHtml(preview)}</span>
                        <span>
                            ${escHtml(when)}
                            <button class="hist-del" data-del="${c.id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </span>
                    </div>
                </div>`;
            }).join('');
            list.querySelectorAll('.chat-history-item').forEach(el => {
                el.addEventListener('click', (e) => {
                    if (e.target.closest('.hist-del')) return;
                    openConversation(Number(el.dataset.id));
                });
            });
            list.querySelectorAll('.hist-del').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteConversation(Number(btn.dataset.del));
                });
            });
        } catch (e) {}
    }

    async function openConversation(id) {
        try {
            const res = await fetch(BASE + '/api/chat_history.php?action=messages&id=' + id);
            const json = await res.json();
            if (!json.success) return;
            conversationId = id;
            hasGreeted = true;
            chatHistory = [];
            document.getElementById('chat-messages').innerHTML = '';
            document.getElementById('chat-suggestions').innerHTML = '';
            (json.data.messages || []).forEach(m => {
                if (m.role === 'user') {
                    addUserMessage(m.content);
                    chatHistory.push({ role: 'user', content: m.content });
                } else {
                    addAiMessage(m.content, false, true);
                    chatHistory.push({ role: 'assistant', content: m.content });
                }
            });
            document.getElementById('chat-header-status').textContent = json.data.conversation?.title || 'Saved chat';
            loadConversationList();
        } catch (e) {}
    }

    async function deleteConversation(id) {
        const data = new FormData();
        data.append('action', 'delete');
        data.append('id', id);
        await fetch(BASE + '/api/chat_history.php', { method: 'POST', body: data });
        if (Number(conversationId) === Number(id)) newChatThread();
        else loadConversationList();
    }

    window.sendMessage = async function () {
        const input = document.getElementById('chat-input');
        const msg   = input.value.trim();
        if (!msg || isTyping) return;

        document.getElementById('chat-suggestions').innerHTML = '';
        input.value = '';
        input.style.height = '';
        document.getElementById('chat-send-btn').disabled = true;

        addUserMessage(msg);
        showTyping();

        try {
            const res  = await fetch(BASE + '/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg, history: chatHistory, conversation_id: conversationId }),
            });
            const json = await res.json();
            hideTyping();

            if (json.success) {
                const reply = json.data.reply;
                if (json.data.conversation_id) conversationId = json.data.conversation_id;
                chatHistory.push({ role: 'user',      content: msg   });
                chatHistory.push({ role: 'assistant', content: reply });
                if (chatHistory.length > 40) chatHistory = chatHistory.slice(-40);
                addAiMessage(reply);
                loadConversationList();
            } else {
                addAiMessage('Sorry, I ran into an issue: ' + escHtml(json.message), true);
            }
        } catch (e) {
            hideTyping();
            addAiMessage('Connection error. Please check your network and try again.', true);
        } finally {
            document.getElementById('chat-send-btn').disabled = document.getElementById('chat-input').value.trim() === '';
        }
    };

    window.chatKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
        if (e.key === 'Escape' && isExpanded) {
            isExpanded = false;
            applyExpand();
        }
    };

    window.autoResizeInput = function (el) {
        el.style.height = '';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
        document.getElementById('chat-send-btn').disabled = el.value.trim() === '';
    };

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

    function addAiMessage(text, isError = false, skipNotif = false) {
        isTyping = false;
        const el  = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'chat-msg ai-msg';
        div.innerHTML = `
            <div class="msg-avatar"><i class="fa-solid fa-robot" style="font-size:12px"></i></div>
            <div class="msg-bubble" style="${isError ? 'border-color:var(--red-border);color:var(--red)' : ''}">${formatAiText(text)}</div>
        `;
        el.appendChild(div);
        scrollToBottom();
        if (!isOpen && !skipNotif) {
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

    function formatAiText(text) {
        let formatted = String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        return '<p>' + formatted + '</p>';
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    document.getElementById('chat-input').addEventListener('input', function () {
        document.getElementById('chat-send-btn').disabled = this.value.trim() === '';
    });

})();
</script>
