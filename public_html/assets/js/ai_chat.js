/**
 * Nyitott Múlt – AI Történész Aszinkron Csevegő Kezelő
 */

document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const submitBtn = document.getElementById('chatSubmitBtn');

    if (!chatForm || !chatInput || !chatMessages) return;

    function sendQuestion(text) {
        text = (text || '').trim();
        if (!text) return;

        // Felhasználói buborék hozzáadása
        appendMessage('user', text);
        chatInput.value = '';
        chatInput.disabled = true;
        if (submitBtn) submitBtn.disabled = true;

        // Várakozási állapot
        const loadingId = appendLoading();

        // API hívás
        fetch('/api/v1/ai/ask', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: text })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(j => { throw new Error(j.error || ('HTTP ' + res.status)); }).catch(e => {
                    throw new Error('Szerverhiba: ' + res.status);
                });
            }
            return res.json();
        })
        .then(data => {
            removeLoading(loadingId);
            chatInput.disabled = false;
            if (submitBtn) submitBtn.disabled = false;
            chatInput.focus();

            appendMessage('bot', data.answer || 'Hiba történt a válasz generálásakor.', data.sources || []);
        })
        .catch(err => {
            console.error(err);
            removeLoading(loadingId);
            chatInput.disabled = false;
            if (submitBtn) submitBtn.disabled = false;
            appendMessage('bot', 'Hálózati vagy szerverhiba történt a válasz lekérésekor (' + err.message + '). Kérjük próbálja újra!');
        });
    }

    // Minta kérdések kattintása
    document.querySelectorAll('.sample-q-btn').forEach(btn => {
        btn.setAttribute('type', 'button');
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const text = this.getAttribute('data-question') || this.textContent.trim();
            if (chatInput) {
                chatInput.value = text;
            }
            sendQuestion(text);
        });
    });

    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (text) {
            sendQuestion(text);
        }
    });

    function appendMessage(sender, text, sources = []) {
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.gap = '0.75rem';
        row.style.maxWidth = '85%';

        if (sender === 'user') {
            row.style.marginLeft = 'auto';
            row.style.flexDirection = 'row-reverse';
            row.innerHTML = `
                <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: #64748b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                    👤
                </div>
                <div style="background: #1e3a8a; color: #ffffff; border-radius: var(--radius); padding: 0.85rem 1.15rem; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-size: 0.9rem; line-height: 1.6;">
                    ${escapeHtml(text)}
                </div>
            `;
        } else {
            let sourcesHtml = '';
            if (sources && sources.length > 0) {
                sourcesHtml = '<div style="margin-top: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">'
                    + '<span style="font-size: 0.75rem; font-weight: 700; color: #64748b; width: 100%;">Levéltári Források:</span>';
                sources.forEach(s => {
                    sourcesHtml += `<span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-family: monospace;">📄 ${escapeHtml(s.archive_code)} (${s.page_number}. o.)</span>`;
                });
                sourcesHtml += '</div>';
            }

            row.innerHTML = `
                <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: #1e3a8a; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                    🤖
                </div>
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 0.85rem 1.15rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 0.9rem; line-height: 1.6; color: #1e293b;">
                    ${formatTextWithQuotes(text)}
                    ${sourcesHtml}
                </div>
            `;
        }

        chatMessages.appendChild(row);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function appendLoading() {
        const id = 'loading-' + Date.now();
        const row = document.createElement('div');
        row.id = id;
        row.style.display = 'flex';
        row.style.gap = '0.75rem';
        row.style.maxWidth = '80%';
        row.innerHTML = `
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: #1e3a8a; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                🤖
            </div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 0.75rem 1rem; font-size: 0.85rem; color: #64748b; font-style: italic;">
                Levéltári források átvizsgálása és idézetek ellenőrzése...
            </div>
        `;
        chatMessages.appendChild(row);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function removeLoading(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatTextWithQuotes(str) {
        return escapeHtml(str)
            .replace(/\n\n/g, '<br><br>')
            .replace(/\[([^\]]+)\]/g, '<span style="background: #f1f5f9; color: #0284c7; padding: 1px 4px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">[$1]</span>');
    }
});
