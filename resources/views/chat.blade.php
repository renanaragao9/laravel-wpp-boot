<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Atendimento - {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #eceff3; --card: #ffffff; --chat-bg: #f6f7f9; --text: #18181b; --muted: #71717a; --border: #e4e4e7;
            --primary: #d97706; --primary-dark: #b45309; --primary-soft: #fef3c7; --on-primary: #ffffff;
            --bot: #ffffff; --mine: #fde7b0; --error: #dc2626; --shadow: 0 10px 40px rgba(24, 24, 27, .12);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b; --card: #18181b; --chat-bg: #101012; --text: #f4f4f5; --muted: #a1a1aa; --border: #2a2a2e;
                --primary: #f59e0b; --primary-dark: #b45309; --primary-soft: #3b2f0b; --on-primary: #18181b;
                --bot: #232327; --mine: #4a3a0f; --error: #f87171; --shadow: 0 10px 40px rgba(0, 0, 0, .5);
            }
        }
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--bg); color: var(--text); display: flex; justify-content: center; align-items: center; padding: 20px;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased;
        }
        .chat {
            width: 100%; max-width: 480px; height: min(740px, 100%); background: var(--card); border: 1px solid var(--border);
            border-radius: 22px; box-shadow: var(--shadow); display: flex; flex-direction: column; overflow: hidden;
        }
        header {
            display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }
        .avatar { width: 42px; height: 42px; border-radius: 50%; background: rgba(255, 255, 255, .2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .avatar svg { width: 22px; height: 22px; }
        header .info { flex: 1; min-width: 0; }
        header h1 { font-size: 16px; font-weight: 700; margin: 0; line-height: 1.2; }
        header .status { display: flex; align-items: center; gap: 6px; font-size: 12px; opacity: .92; margin-top: 2px; }
        header .status i { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 0 3px rgba(74, 222, 128, .3); }
        .view { flex: 1; min-height: 0; display: none; flex-direction: column; }
        .view.active { display: flex; }
        button { font: inherit; cursor: pointer; }
        button:disabled { cursor: default; }
        .btn { font-weight: 600; background: var(--primary); color: var(--on-primary); border: 0; border-radius: 12px; padding: 12px 18px; transition: filter .15s, transform .05s; }
        .btn:hover:not(:disabled) { filter: brightness(1.06); }
        .btn:active:not(:disabled) { transform: translateY(1px); }
        .btn:disabled { opacity: .55; }
        .btn.ghost { background: rgba(255, 255, 255, .18); color: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
        .btn.ghost:hover { background: rgba(255, 255, 255, .28); }
        form.start { padding: 22px 20px 24px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; }
        form.start h2 { margin: 0; font-size: 20px; }
        .intro { margin: 0; font-size: 14px; line-height: 1.5; color: var(--muted); }
        label { font-size: 13px; font-weight: 600; display: flex; flex-direction: column; gap: 6px; }
        label small { font-weight: 400; color: var(--muted); }
        input, textarea { font: inherit; color: var(--text); background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 11px 13px; width: 100%; transition: border-color .15s, box-shadow .15s; }
        input:focus, textarea:focus { outline: 0; border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 25%, transparent); }
        .error { color: var(--error); font-size: 13px; min-height: 1.1em; }
        #messages { flex: 1; overflow-y: auto; padding: 18px 14px; display: flex; flex-direction: column; gap: 10px; background: var(--chat-bg); scroll-behavior: smooth; }
        .row { display: flex; gap: 8px; align-items: flex-end; max-width: 100%; }
        .row.mine { justify-content: flex-end; }
        .row .mini { width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: var(--on-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .row .mini svg { width: 15px; height: 15px; }
        .bubble { max-width: 84%; padding: 10px 14px 7px; border-radius: 18px; font-size: 14.5px; line-height: 1.5; overflow-wrap: anywhere; box-shadow: 0 1px 2px rgba(24, 24, 27, .08); animation: pop .18s ease-out; }
        .bubble.theirs { background: var(--bot); border: 1px solid var(--border); border-bottom-left-radius: 5px; }
        .bubble.mine { background: var(--mine); border-bottom-right-radius: 5px; }
        .bubble .text { white-space: pre-wrap; }
        .bubble strong { font-weight: 700; }
        .bubble code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .92em; background: rgba(127, 127, 127, .18); padding: 1px 5px; border-radius: 5px; }
        .bubble .footnote { white-space: pre-wrap; font-size: 12.5px; color: var(--muted); margin-top: 10px; }
        .bubble time { display: block; font-size: 11px; color: var(--muted); margin-top: 5px; text-align: right; }
        .options { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
        .option { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; background: var(--card); color: var(--text); border: 1px solid var(--border); border-radius: 12px; padding: 9px 12px; font-size: 14px; transition: border-color .15s, background .15s; }
        .option .num { min-width: 26px; height: 26px; border-radius: 8px; background: var(--primary-soft); color: var(--primary-dark); font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; }
        @media (prefers-color-scheme: dark) { .option .num { color: var(--primary); } }
        .option:hover:not(:disabled) { border-color: var(--primary); background: var(--primary-soft); }
        .option:disabled { opacity: .5; }
        form.send { display: flex; gap: 8px; align-items: flex-end; padding: 12px; border-top: 1px solid var(--border); background: var(--card); }
        form.send textarea { flex: 1; resize: none; max-height: 120px; border-radius: 22px; padding: 11px 16px; line-height: 1.4; }
        form.send .go { width: 44px; height: 44px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        form.send .go svg { width: 20px; height: 20px; }
        #send-error { padding: 0 16px 10px; background: var(--card); min-height: 0; }
        @keyframes pop { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @media (max-width: 560px) {
            body { padding: 0; }
            .chat { max-width: none; height: 100dvh; border-radius: 0; border: 0; box-shadow: none; }
            .bubble { max-width: 88%; }
        }
    </style>
</head>
<body>
<main class="chat">
    <header>
        <div class="avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-5H5a2 2 0 0 0-2 2z"/><path d="M18 17v1a3 3 0 0 1-3 3h-2"/></svg>
        </div>
        <div class="info">
            <h1>Atendimento</h1>
            <div class="status"><i></i> Online · {{ config('app.name') }}</div>
        </div>
        <button type="button" class="btn ghost" id="new-chat" hidden>Nova conversa</button>
    </header>

    <section id="start-view" class="view active">
        <form class="start" id="start-form" novalidate>
            <h2>Olá! 👋</h2>
            <p class="intro">Informe seus dados para iniciar o atendimento. Você escolhe o assunto pelo menu e nossa equipe responde por aqui mesmo.</p>
            <label><span>Seu nome</span>
                <input type="text" name="name" maxlength="120" required autocomplete="name" placeholder="Como podemos te chamar?">
            </label>
            <label><span>E-mail <small>(opcional)</small></span>
                <input type="email" name="email" maxlength="255" autocomplete="email" placeholder="voce@exemplo.com">
            </label>
            <label><span>Telefone <small>(opcional)</small></span>
                <input type="tel" name="phone" maxlength="30" autocomplete="tel" placeholder="(00) 00000-0000">
            </label>
            <div class="error" id="start-error" role="alert"></div>
            <button type="submit" class="btn" id="start-button">Iniciar atendimento</button>
        </form>
    </section>

    <section id="chat-view" class="view">
        <div id="messages" aria-live="polite"></div>
        <div class="error" id="send-error" role="alert"></div>
        <form class="send" id="send-form">
            <textarea id="send-input" rows="1" maxlength="2000" placeholder="Digite sua mensagem..." required></textarea>
            <button type="submit" class="btn go" id="send-button" aria-label="Enviar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
            </button>
        </form>
    </section>
</main>

<script>
(() => {
    const STORAGE_KEY = 'wpp-boot-chat-token';
    const POLL_MS = 3000;
    const BOT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/></svg>';

    const api = (path, options = {}) => fetch('/api/chat' + path, {
        ...options,
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });

    const el = (id) => document.getElementById(id);
    const startView = el('start-view'), chatView = el('chat-view');
    const messagesBox = el('messages');
    const sendForm = el('send-form'), sendInput = el('send-input'), sendButton = el('send-button');

    let token = null;
    let lastId = 0;
    let timer = null;
    let polling = false;

    const FORMAT = /(?<![\p{L}\p{N}])(?:\*([^\s*](?:[^*\n]*[^\s*])?)\*|_([^\s_](?:[^_\n]*[^\s_])?)_|~([^\s~](?:[^~\n]*[^\s~])?)~|`([^`\n]+)`)(?![\p{L}\p{N}])/gu;
    const TAGS = ['strong', 'em', 's', 'code'];

    const renderInline = (text, parent) => {
        let cursor = 0;
        for (const match of text.matchAll(FORMAT)) {
            if (match.index > cursor) parent.appendChild(document.createTextNode(text.slice(cursor, match.index)));
            const group = [1, 2, 3, 4].find((i) => match[i] !== undefined);
            const node = document.createElement(TAGS[group - 1]);
            if (group === 4) node.textContent = match[group];
            else renderInline(match[group], node);
            parent.appendChild(node);
            cursor = match.index + match[0].length;
        }
        if (cursor < text.length) parent.appendChild(document.createTextNode(text.slice(cursor)));
        return parent;
    };

    const parseMenu = (body) => {
        const before = [], options = [], after = [];
        let phase = 'before';
        for (const line of body.split('\n')) {
            const option = line.match(/^\s*(\d{1,2})\s+-\s+(.+?)\s*$/);
            if (option && phase !== 'after') {
                phase = 'options';
                options.push({ number: option[1], label: option[2] });
            } else if (phase === 'options') {
                phase = 'after';
                after.push(line);
            } else {
                (phase === 'before' ? before : after).push(line);
            }
        }
        return options.length >= 2 ? { before: before.join('\n').trim(), options, after: after.join('\n').trim() } : null;
    };

    const textBlock = (className, text) => {
        const div = document.createElement('div');
        div.className = className;
        return renderInline(text, div);
    };

    const renderBody = (bubble, message) => {
        const menu = message.direction === 'outbound' ? parseMenu(message.body) : null;
        if (!menu) {
            bubble.appendChild(textBlock('text', message.body));
            return;
        }
        if (menu.before) bubble.appendChild(textBlock('text', menu.before));
        const list = document.createElement('div');
        list.className = 'options';
        menu.options.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'option';
            button.dataset.number = item.number;
            const number = document.createElement('span');
            number.className = 'num';
            number.textContent = item.number;
            const label = document.createElement('span');
            renderInline(item.label, label);
            button.append(number, label);
            list.appendChild(button);
        });
        bubble.appendChild(list);
        if (menu.after) bubble.appendChild(textBlock('footnote', menu.after));
    };

    const refreshOptions = () => {
        const rows = [...messagesBox.querySelectorAll('.row')];
        const last = rows[rows.length - 1];
        rows.forEach((row) => row.querySelectorAll('.option').forEach((button) => { button.disabled = row !== last; }));
    };

    const render = (message) => {
        if (messagesBox.querySelector('[data-id="' + message.id + '"]')) return;
        const mine = message.direction === 'inbound';
        const row = document.createElement('div');
        row.className = 'row ' + (mine ? 'mine' : 'theirs');
        row.dataset.id = message.id;
        if (!mine) {
            const mini = document.createElement('div');
            mini.className = 'mini';
            mini.innerHTML = BOT_ICON;
            row.appendChild(mini);
        }
        const bubble = document.createElement('div');
        bubble.className = 'bubble ' + (mine ? 'mine' : 'theirs');
        renderBody(bubble, message);
        const time = document.createElement('time');
        time.textContent = new Date(message.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        bubble.appendChild(time);
        row.appendChild(bubble);
        messagesBox.appendChild(row);
        lastId = Math.max(lastId, message.id);
    };

    const append = (messages) => {
        const atBottom = messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 120;
        messages.forEach(render);
        refreshOptions();
        if (messages.length && atBottom) messagesBox.scrollTop = messagesBox.scrollHeight;
    };

    const show = (view) => {
        startView.classList.toggle('active', view === 'start');
        chatView.classList.toggle('active', view === 'chat');
    };

    const firstError = (body) => {
        if (body && body.errors) return Object.values(body.errors)[0][0];
        return (body && body.message) || 'Não foi possível concluir. Tente novamente.';
    };

    const stopPolling = () => { if (timer) clearInterval(timer); timer = null; };
    const startPolling = () => { stopPolling(); timer = setInterval(poll, POLL_MS); };

    const reset = () => {
        stopPolling();
        localStorage.removeItem(STORAGE_KEY);
        token = null; lastId = 0;
        messagesBox.innerHTML = '';
        el('new-chat').hidden = true;
        show('start');
    };

    const poll = async () => {
        if (!token || polling || document.hidden) return;
        polling = true;
        try {
            const response = await api('/' + token + '/messages?after=' + lastId);
            if (response.status === 404) return reset();
            if (!response.ok) return;
            const body = await response.json();
            append(body.data);
        } catch (e) {
        } finally {
            polling = false;
        }
    };

    const openChat = async (savedToken) => {
        token = savedToken;
        localStorage.setItem(STORAGE_KEY, token);
        show('chat');
        el('new-chat').hidden = false;
        await poll();
        if (token) startPolling();
        sendInput.focus();
    };

    const sendMessage = async (text) => {
        const value = text.trim();
        if (!value || !token) return;
        const error = el('send-error');
        error.textContent = '';
        sendButton.disabled = true;
        try {
            const response = await api('/' + token + '/messages', { method: 'POST', body: JSON.stringify({ message: value }) });
            const body = await response.json();
            if (!response.ok) { error.textContent = firstError(body); return; }
            sendInput.value = '';
            sendInput.style.height = 'auto';
            while (polling) await new Promise((resolve) => setTimeout(resolve, 50));
            await poll();
            messagesBox.scrollTop = messagesBox.scrollHeight;
        } catch (e) {
            error.textContent = 'Sem conexão. Tente novamente.';
        } finally {
            sendButton.disabled = false;
            sendInput.focus();
        }
    };

    el('start-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        const button = el('start-button'), error = el('start-error');
        error.textContent = '';
        button.disabled = true;
        try {
            const response = await api('', {
                method: 'POST',
                body: JSON.stringify({ name: form.name.value, email: form.email.value || null, phone: form.phone.value || null }),
            });
            const body = await response.json();
            if (!response.ok) { error.textContent = firstError(body); return; }
            form.reset();
            lastId = 0;
            messagesBox.innerHTML = '';
            await openChat(body.token);
        } catch (e) {
            error.textContent = 'Sem conexão. Tente novamente.';
        } finally {
            button.disabled = false;
        }
    });

    sendForm.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(sendInput.value);
    });

    sendInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendForm.requestSubmit();
        }
    });

    sendInput.addEventListener('input', () => {
        sendInput.style.height = 'auto';
        sendInput.style.height = Math.min(sendInput.scrollHeight, 120) + 'px';
    });

    messagesBox.addEventListener('click', (event) => {
        const option = event.target.closest('.option');
        if (option && !option.disabled) sendMessage(option.dataset.number);
    });

    el('new-chat').addEventListener('click', reset);

    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) openChat(saved);
})();
</script>
</body>
</html>
