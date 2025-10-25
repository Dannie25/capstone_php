// Simple Floating Chatbot for common questions
(function () {
  let faqs = [
    { q: 'What are your store hours?', a: 'Our online store is open 24/7. Support hours: Mon–Fri, 9am–6pm.' },
    { q: 'How long is shipping?', a: 'Standard shipping typically takes 3–7 business days depending on your location.' },
    { q: 'Do you offer returns?', a: 'Yes. Returns are accepted within 7 days of delivery if items are unworn and with tags.' },
    { q: 'How can I track my order?', a: 'Go to My Orders page after logging in to see your order status and tracking if available.' },
    { q: 'Do you have Cash on Delivery?', a: 'Yes, COD may be available on select locations. See checkout for options.' }
  ];

  function el(id) { return document.getElementById(id); }

  async function appendMessage(container, text, who) {
    const row = document.createElement('div');
    row.className = 'cb-msg ' + (who === 'bot' ? 'cb-bot' : 'cb-user');
    row.innerText = text;
    container.appendChild(row);
    container.scrollTop = container.scrollHeight;
    // Save to DB
    try {
      await fetch('save_chatbot_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'sender=' + encodeURIComponent(who) + '&message=' + encodeURIComponent(text)
      });
    } catch (e) {/* ignore */}
  }

  function renderFaqChips(chipsWrap, onClick) {
    chipsWrap.innerHTML = '';
    const list = document.createElement('ul');
    list.className = 'cb-suggestions';
    list.setAttribute('role', 'listbox');
    faqs.forEach((item, idx) => {
      const li = document.createElement('li');
      li.className = 'cb-suggestion';
      li.setAttribute('role', 'option');
      li.setAttribute('tabindex', '0');
      li.textContent = item.q;
      li.addEventListener('click', () => onClick(item));
      li.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onClick(item); }});
      list.appendChild(li);
    });
    chipsWrap.appendChild(list);
  }

  async function loadFaqs() {
    try {
      const res = await fetch('get_chatbot_faqs.php', { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.success && Array.isArray(data.faqs) && data.faqs.length) {
        // Normalize items: expected format { q, a }
        const cleaned = data.faqs
          .filter(it => it && (it.q || it.question) && (it.a || it.answer))
          .map(it => ({ q: it.q || it.question, a: it.a || it.answer }));
        if (cleaned.length) faqs = cleaned;
      }
    } catch (e) {
      // Silently ignore and keep defaults
    }
  }

  async function loadChatHistory(msgs) {
    try {
      const res = await fetch('get_chatbot_history.php');
      const data = await res.json();
      if (data && data.success && Array.isArray(data.history)) {
        msgs.innerHTML = '';
        data.history.forEach(row => {
          const div = document.createElement('div');
          div.className = 'cb-msg ' + (row.sender === 'bot' ? 'cb-bot' : 'cb-user');
          div.innerText = row.message;
          msgs.appendChild(div);
        });
        msgs.scrollTop = msgs.scrollHeight;
      }
    } catch (e) {/* ignore */}
  }

  async function pollChatbotMessages() {
    const msgs = el('chatbot-messages');
    if (!msgs) return;
    try {
      const res = await fetch('get_chatbot_history.php');
      const data = await res.json();
      if (data && data.success && Array.isArray(data.history)) {
        msgs.innerHTML = '';
        data.history.forEach(row => {
          const div = document.createElement('div');
          div.className = 'cb-msg ' + (row.sender === 'bot' ? 'cb-bot' : 'cb-user');
          div.innerText = row.message;
          msgs.appendChild(div);
        });
        msgs.scrollTop = msgs.scrollHeight;
      }
    } catch (e) {/* ignore */}
  }
  setInterval(pollChatbotMessages, 2000);

  async function init() {
    await loadFaqs();
    const toggle = el('chatbot-toggle');
    const wnd = el('chatbot-window');
    const closeBtn = el('chatbot-close');
    const msgs = el('chatbot-messages');
    const chips = el('chatbot-chips');
    const input = el('chatbot-input');
    const sendBtn = el('chatbot-send');

    if (!toggle || !wnd || !msgs || !chips) return;

    // Load chat history on open
    await loadChatHistory(msgs);

    // Try to load FAQs from CMS

    // Open/close handlers
    function open() {
      wnd.style.display = 'flex';
      toggle.setAttribute('aria-expanded', 'true');
      msgs.scrollTop = msgs.scrollHeight;
      // Show greeting only if backend says so and window is empty
      if (!msgs.hasChildNodes()) {
        fetch('get_chatbot_greeting_flag.php')
          .then(res => res.json())
          .then(data => {
            if (data && data.show) {
              appendMessage(msgs, 'Hi! I\'m here to help. Choose a question below or type your own.', 'bot');
            }
          });
      }
    }
    function close() {
      wnd.style.display = 'none';
      toggle.setAttribute('aria-expanded', 'false');
    }

    // Restore toggle button handler
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (wnd.style.display === 'flex') close(); else open();
    });

    // Add close button handler
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        close();
      });
    } else {
      console.warn('Chatbot close button not found');
    }

    // Render FAQ chips
    function hideSuggestions() {
      chips.style.display = 'none';
      chips.setAttribute('aria-hidden', 'true');
      toggleSuggestions?.setAttribute('aria-expanded', 'false');
    }
    function showSuggestions() {
      chips.style.display = 'block';
      chips.setAttribute('aria-hidden', 'false');
      toggleSuggestions?.setAttribute('aria-expanded', 'true');
    }

    renderFaqChips(chips, (item) => {
      appendMessage(msgs, item.q, 'user');
      setTimeout(() => appendMessage(msgs, item.a, 'bot'), 300);
      hideSuggestions();
    });

    function handleSend() {
      const val = (input?.value || '').trim();
      if (!val) return;
      appendMessage(msgs, val, 'user');
      input.value = '';
      let waitTimeout;
      // Poll for admin reply for 30s
      function pollForAdminReply(startTime) {
        fetch('get_chatbot_history.php')
          .then(r => r.json())
          .then(data => {
            if (data && data.admin_last_reply && Date.parse(data.admin_last_reply) > startTime) {
              // Admin replied, do nothing
              clearTimeout(waitTimeout);
            } else {
              // No admin reply yet
              const now = Date.now();
              if (now - startTime >= 10000) {
                appendMessage(msgs, 'Wala pang admin na sumasagot sa ngayon. Mangyaring maghintay, at sasagutin ng admin ang iyong concern sa lalong madaling panahon.', 'bot');
                clearTimeout(waitTimeout);
              } else {
                waitTimeout = setTimeout(() => pollForAdminReply(startTime), 2000);
              }
            }
          });
      }
      // For typed questions, never auto-answer from FAQ
      fetch('get_chatbot_history.php')
        .then(r => r.json())
        .then(data => {
          if (data && Array.isArray(data.history)) {
            // Find last user message and last admin message
            const lastUserMsg = [...data.history].reverse().find(m => m.sender === 'user');
            const lastAdminMsg = [...data.history].reverse().find(m => m.sender === 'bot');
            if (lastUserMsg && lastAdminMsg && Date.parse(lastAdminMsg.created_at) > Date.parse(lastUserMsg.created_at)) {
              setTimeout(() => appendMessage(msgs, lastAdminMsg.message, 'bot'), 350);
              return;
            }
          }
          // No new admin reply yet, start polling
          pollForAdminReply(Date.now());
        });
    }

    if (sendBtn) sendBtn.addEventListener('click', handleSend);
    if (input) input.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
    e.preventDefault();
    handleSend();
  }
});

    // Toggle button for suggestions
    const toggleSuggestions = el('chatbot-toggle-suggestions');
    toggleSuggestions?.addEventListener('click', (e) => {
      e.stopPropagation();
      if (chips.style.display === 'block') hideSuggestions(); else showSuggestions();
    });

    // Close suggestions on outside click
    document.addEventListener('click', (e) => {
      if (chips.style.display === 'block') {
        const inside = chips.contains(e.target) || toggleSuggestions?.contains(e.target) || input?.contains?.(e.target);
        if (!inside) hideSuggestions();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
