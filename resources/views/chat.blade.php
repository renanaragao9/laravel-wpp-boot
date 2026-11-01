<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atendimento - {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f4f4f5; --card: #ffffff; --text: #18181b; --muted: #71717a; --border: #e4e4e7;
            --primary: #d97706; --primary-text: #ffffff; --mine: #fef3c7; --theirs: #f4f4f5; --error: #dc2626;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0b0c; --card: #18181b; --text: #f4f4f5; --muted: #a1a1aa; --border: #27272a;
                --primary: #f59e0b; --primary-text: #18181b; --mine: #3b2f0b; --theirs: #27272a; --error: #f87171;
            }
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; padding: 16px; }
        .chat { width: 100%; max-width: 520px; height: min(720px, 100%); background: var(--card); border: 1px solid var(--border); border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; }
        header { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        header .dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; }
        header h1 { font-size: 16px; margin: 0; }
        header small { color: var(--muted); display: block; font-size: 12px; }
        .view { flex: 1; min-height: 0; display: none; flex-direction: column; }
        .view.active { display: flex; }
        form.start { padding: 18px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto; }
        label { font-size: 13px; color: var(--muted); display: flex; flex-direction: column; gap: 4px; }
        input, textarea { font: inherit; color: var(--text); background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; width: 100%; }
        input:focus, textarea:focus { outline: 2px solid var(--primary); outline-offset: -1px; }
        textarea { resize: none; }
        button { font: inherit; font-weight: 600; background: var(--primary); color: var(--primary-text); border: 0; border-radius: 10px; padding: 10px 16px; cursor: pointer; }
        button:disabled { opacity: .5; cursor: default; }
        button.secondary { background: transparent; color: var(--text); border: 1px solid var(--border); }
        button.small { font-size: 12px; padding: 6px 10px; }
        .error { color: var(--error); font-size: 13px; min-height: 1em; }
        .intro { font-size: 14px; color: var(--muted); margin: 0; }
        #messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }
        .bubble { max-width: 82%; padding: 9px 12px; border-radius: 14px; font-size: 14px; line-height: 1.4; white-space: pre-wrap; word-wrap: break-word; }
        .bubble time { display: block; font-size: 11px; color: var(--muted); margin-top: 3px; }
        .bubble.mine { align-self: flex-end; background: var(--mine); border-bottom-right-radius: 4px; }
        .bubble.theirs { align-self: flex-start; background: var(--theirs); border-bottom-left-radius: 4px; }
        form.send { padding: 12px; border-top: 1px solid var(--border); display: flex; gap: 8px; align-items: flex-end; }
        form.send textarea { max-height: 120px; }
    </style>
</head>
<body>
<main class="chat">
    <header>
        <span class="dot"></span>
        <div style="flex:1">
            <h1>Atendimento</h1>
            <small>{{ config('app.name') }}</small>
        </div>
        <button type="button" class="secondary small" id="new-chat" hidden>Nova conversa</button>
    </header>

    <section id="start-view" class="view active">
        <form class="start" id="start-form" novalidate>
            <p class="intro">Informe seus dados para iniciar o atendimento. Você escolhe o assunto pelo menu e nossa equipe responde por aqui mesmo.</p>
            <label>Seu nome *
                <input type="text" name="name" maxlength="120" required autocomplete="name">
            </label>
            <label>E-mail (opcional)
                <input type="email" name="email" maxlength="255" autocomplete="email">
            </label>
            <label>Telefone (opcional)
                <input type="tel" name="phone" maxlength="30" autocomplete="tel">
            </label>
            <div class="error" id="start-error" role="alert"></div>
            <button type="submit" id="start-button">Iniciar atendimento</button>
        </form>
    </section>

    <section id="chat-view" class="view">
        <div id="messages" aria-live="polite"></div>
        <form class="send" id="send-form">
            <textarea id="send-input" rows="1" maxlength="2000" placeholder="Digite o número da opção ou sua mensagem..." required></textarea>
            <button type="submit" id="send-button">Enviar</button>
        </form>
        <div class="error" id="send-error" role="alert" style="padding:0 16px 10px"></div>
    </section>
</main>

<script>
(() => {
    const STORAGE_KEY = 'wpp-boot-chat-token';
    const POLL_MS = 3000;
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

    const show = (view) => {
        startView.classList.toggle('active', view === 'start');
        chatView.classList.toggle('active', view === 'chat');
    };

    const firstError = (body) => {
        if (body && body.errors) return Object.values(body.errors)[0][0];
        return (body && body.message) || 'Não foi possível concluir. Tente novamente.';
    };

    const render = (message) => {
        if (messagesBox.querySelector('[data-id="' + message.id + '"]')) return;
        const bubble = document.createElement('div');
        bubble.className = 'bubble ' + (message.direction === 'inbound' ? 'mine' : 'theirs');
        bubble.dataset.id = message.id;
        bubble.textContent = message.body;
        const time = document.createElement('time');
        time.textContent = new Date(message.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        bubble.appendChild(time);
        messagesBox.appendChild(bubble);
        lastId = Math.max(lastId, message.id);
    };

    const append = (messages) => {
        const atBottom = messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 80;
        messages.forEach(render);
        if (messages.length && atBottom) messagesBox.scrollTop = messagesBox.scrollHeight;
    };

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

    const startPolling = () => { stopPolling(); timer = setInterval(poll, POLL_MS); };
    const stopPolling = () => { if (timer) clearInterval(timer); timer = null; };

    const openChat = async (savedToken) => {
        token = savedToken;
        localStorage.setItem(STORAGE_KEY, token);
        show('chat');
        el('new-chat').hidden = false;
        await poll();
        if (token) startPolling();
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
                body: JSON.stringify({
                    name: form.name.value, email: form.email.value || null,
                    phone: form.phone.value || null,
                }),
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

    sendForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = sendInput.value.trim();
        if (!text || !token) return;
        const error = el('send-error');
        error.textContent = '';
        sendButton.disabled = true;
        try {
            const response = await api('/' + token + '/messages', { method: 'POST', body: JSON.stringify({ message: text }) });
            const body = await response.json();
            if (!response.ok) { error.textContent = firstError(body); return; }
            sendInput.value = '';
            while (polling) await new Promise((resolve) => setTimeout(resolve, 50));
            await poll();
            messagesBox.scrollTop = messagesBox.scrollHeight;
        } catch (e) {
            error.textContent = 'Sem conexão. Tente novamente.';
        } finally {
            sendButton.disabled = false;
            sendInput.focus();
        }
    });

    sendInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendForm.requestSubmit();
        }
    });

    el('new-chat').addEventListener('click', reset);

    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) openChat(saved);
})();
</script>
</body>
</html>
