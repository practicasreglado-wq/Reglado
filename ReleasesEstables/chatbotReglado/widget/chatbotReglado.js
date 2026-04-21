/**
 * Función: Script principal del widget de chatbot. Inyecta los estilos CSS y crea la interfaz de usuario flotante en el navegador, además de manejar la lógica de interacción y la comunicación con el backend.
 * se crea dinamicamente en el DOM.
 */
(function initChatbotReglado() {
  if (window.__chatbotRegladoLoaded__) return;
  window.__chatbotRegladoLoaded__ = true;

  function resolveRobotUrl() {
    try {
      const script = document.currentScript || Array.from(document.scripts).find((s) => s.src && s.src.includes('chatbotReglado.js'));
      if (!script || !script.src) return '';
      const scriptUrl = new URL(script.src, window.location.href);
      return new URL('./assets/imagechatbot.png', scriptUrl).toString();
    } catch (error) {
      return '';
    }
  }

  const defaultConfig = {
    apiUrl: 'https://chatbot.regladogroup.com/chat',
    title: 'Asistente Inteligente',
    greeting: '¡Hola! Soy tu asistente. ¿Cómo puedo ayudarte hoy?',
    primaryColor: '#2563eb', /* Azul corporativo moderno */
    textColor: '#1e293b',
    position: 'bottom-right',
    robotImage: resolveRobotUrl(),
    placeholder: 'Escribe tu mensaje...',
    sendButtonLabel: 'Enviar'
  };

  const config = Object.assign({}, defaultConfig, window.ChatbotRegladoConfig || {});

  function getSessionId() {
    let sid = localStorage.getItem('cr_session_id');
    if (!sid) {
      sid = 'cr_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
      localStorage.setItem('cr_session_id', sid);
    }
    return sid;
  }
  const sessionId = getSessionId();

  injectStyles(config);
  const elements = createWidget(config);
  bindEvents(elements, config, sessionId);
  addMessage(elements.messages, config.greeting, 'bot');

  // Si recargamos la página, tal vez la conversión sigue en curso con un agente. Podemos hacer polling inicial.
  iniciarPolling(sessionId, config, elements);

  function iniciarPolling(sessionId, options, elements) {
      if (window.crPollingInterval) return;
      
      window.crPollingInterval = setInterval(async () => {
        try {
          const params = new URLSearchParams({ sessionId });
          const url = `${options.apiUrl.replace('/chat', '/api/poll_messages')}?${params.toString()}`;
          const response = await fetch(url);
          if (!response.ok) return;
          const data = await response.json();
          
          actualizarUIEstado(elements, data.estado);

          if (data.mensajes && data.mensajes.length > 0) {
              data.mensajes.forEach(m => {
                  addMessage(elements.messages, m.texto, 'bot', '👨‍💼 '); // agente symbol
                  if (window.crMessageHistory) {
                    window.crMessageHistory.push({ role: 'assistant', content: m.texto });
                  }
              });
          }

          // Desactivar polling si vuelve a la IA (ej. despues de /finalizar)
          if (data.estado === 'IA') {
              clearInterval(window.crPollingInterval);
              window.crPollingInterval = null;
          }
        } catch (err) {
          // Silent catch on poll
        }
      }, 3500);
  }

  function actualizarUIEstado(elements, estado) {
      const subtitle = elements.windowEl.querySelector('.cr-chat-subtitle');
      if (!subtitle) return;
      if (estado === 'IA') {
          subtitle.innerHTML = 'En línea (IA)';
          subtitle.style.color = 'inherit';
          elements.attachBtn.style.display = '';
          elements.attachBtn.disabled = false;
      } else if (estado === 'WAITING_HUMAN') {
          subtitle.innerHTML = 'Buscando agente...';
          subtitle.style.color = '#f59e0b';
          elements.attachBtn.style.display = 'none';
      } else if (estado === 'HUMAN') {
          subtitle.innerHTML = 'Agente conectado';
          subtitle.style.color = '#10b981';
          elements.attachBtn.style.display = 'none';
      }
  }

  function injectStyles(options) {
    const style = document.createElement('style');
    style.id = 'cr-chatbot-styles';
    style.textContent = `
      /* Base */
      .cr-chatbot-shell, .cr-chatbot-shell * { box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
      .cr-chatbot-shell { position: fixed; right: 28px; bottom: 90px; z-index: 2147483000; display: flex; flex-direction: column; align-items: flex-end; }
      .cr-chatbot-shell.cr-left { right: auto; left: 28px; align-items: flex-start; }
      
      /* Toggle Button (The user image) */
      .cr-chat-toggle { position: relative; width: 58px; height: 58px; border: none;  background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; outline: none; transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0s ease; }
      .cr-chat-toggle:hover { transform: translateY(-4px) scale(1.05); }
      .cr-chat-toggle:active { transform: translateY(0) scale(0.95); }
      .cr-chat-toggle img { width: 120%; height: 120%; object-fit: cover;  image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; transform: translateZ(0); filter: drop-shadow(-4px 4px 8px rgba(0,0,0,0.9)); }
      .cr-chat-toggle .fallback-emoji { font-size: 32px; }
      
      /* Tooltip (Viñeta en Hover) */
      .cr-chat-toggle::before { content: '¡Hola!, Soy el Asistente Virtual\\A de Grupo Reglado'; position: absolute; right: calc(100% + 14px); top: 50%; transform: translateY(-50%) translateX(10px) scale(0.95); opacity: 0; pointer-events: none; background: #ffffff; color: #1e293b; font-size: 14px; font-weight: 500; font-family: 'Inter', sans-serif; padding: 12px 18px; border-radius: 20px 20px 4px 20px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0,0,0,0.04); white-space: pre; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); z-index: -1; }
      .cr-chatbot-shell.cr-left .cr-chat-toggle::before { right: auto; left: calc(100% + 14px); text-align: left; transform-origin: left center; border-radius: 20px 20px 20px 4px; transform: translateY(-50%) translateX(-10px) scale(0.95); }
      
      .cr-chat-toggle:hover::before { opacity: 1; transform: translateY(-50%) translateX(0) scale(1); z-index: 10; }

      /* Main Chat Window */
      .cr-chat-window { width: min(400px, calc(100vw - 32px)); height: min(625px, calc(100vh - 120px)); margin-bottom: 20px; background: #ffffff; border-radius: 28px; overflow: hidden; display: none; flex-direction: column; transform-origin: bottom right; animation: crFadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 20px 40px -5px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0,0,0,0.03); }
      .cr-chatbot-shell.cr-left .cr-chat-window { transform-origin: bottom left; }
      .cr-chat-window.cr-open { display: flex; }

      /* Header with SVG Icon */
      .cr-chat-header { display: flex; align-items: center; gap: 16px; padding: 24px 28px; color: #ffffff; background: ${options.primaryColor}; position: relative; overflow: hidden; }
      .cr-chat-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.05) 100%); pointer-events: none; }
      
      .cr-chat-ai-icon { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.15); border-radius: 14px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.3), 0 4px 12px rgba(0,0,0,0.1); flex-shrink: 0; z-index: 1; }
      .cr-chat-ai-icon svg { width: 26px; height: 26px; }
      
      .cr-chat-header-text { display: flex; flex-direction: column; min-width: 0; z-index: 1; }
      .cr-chat-title { font-size: 17px; font-weight: 700; letter-spacing: -0.01em; line-height: 1.2; }
      
      .cr-chat-subtitle { font-size: 13px; font-weight: 500; opacity: 0.9; margin-top: 4px; display: flex; align-items: center; gap: 6px; }
      .cr-chat-subtitle::before { content: ''; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; display: inline-block; box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.3); }
      
      .cr-chat-close { margin-left: auto; width: 36px; height: 36px; border: none; border-radius: 50%; color: #ffffff; background: rgba(255,255,255,0.1); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 1; }
      .cr-chat-close:hover { background: rgba(255,255,255,0.25); transform: rotate(90deg); }
      .cr-chat-close svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; }

      /* Messages Area */
      .cr-chat-messages { flex: 1; padding: 28px 24px; background: #f8fafc; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; scroll-behavior: smooth; }
      .cr-chat-row { display: flex; opacity: 0; transform: translateY(10px); animation: crMsgIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
      .cr-chat-row.user { justify-content: flex-end; }
      .cr-chat-row.bot { justify-content: flex-start; }
      
      .cr-chat-bubble { max-width: 85%; padding: 14px 18px; border-radius: 20px; font-size: 15px; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }
      .cr-chat-row.bot .cr-chat-bubble { color: ${options.textColor}; background: #ffffff; border-top-left-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.03), 0 1px 2px rgba(0,0,0,0.02); }
      .cr-chat-row.user .cr-chat-bubble { color: #ffffff; background: ${options.primaryColor}; border-top-right-radius: 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }

      /* Footer / Input form */
      .cr-chat-footer { padding: 16px 24px 24px; background: #ffffff; border-top: 1px solid #f1f5f9; }
      .cr-chat-form { display: flex; align-items: flex-end; gap: 12px; }
      
      .cr-chat-input-wrapper { flex: 1; display: flex; align-items: flex-end; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 24px; padding: 0 16px; transition: all 0.2s ease; overflow: hidden; }
      .cr-chat-input-wrapper:focus-within { border-color: ${options.primaryColor}; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
      
      .cr-chat-input { flex: 1; resize: none; min-height: 48px; max-height: 160px; padding: 14px 0; border: none; background: transparent; outline: none; font-size: 15px; color: ${options.textColor}; line-height: 1.4; }
      .cr-chat-input::placeholder { color: #94a3b8; }

      .cr-chat-attach-btn { background: none; border: none; padding: 12px 4px; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; transition: color 0.2s; }
      .cr-chat-attach-btn:hover { color: ${options.primaryColor}; }
      .cr-chat-attach-btn svg { width: 22px; height: 22px; }
      
      .cr-chat-submit { min-width: 84px; height: 48px; border: none; border-radius: 16px; color: #ffffff; cursor: pointer; background: linear-gradient(135deg, ${options.primaryColor}, #1d4ed8); flex-shrink: 0; display: flex; align-items: center; justify-content: center; padding: 0 20px; font-weight: 600; font-size: 15px; transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
      .cr-chat-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35); filter: brightness(1.05); }
      .cr-chat-submit:active:not(:disabled) { transform: translateY(0) scale(0.97); }
      .cr-chat-submit[disabled] { opacity: 0.5; cursor: not-allowed; box-shadow: none; transform: none; }

      /* Visual typing indicator */
      .cr-chat-typing { display: inline-flex; gap: 4px; padding: 6px 4px; align-items: center; }
      .cr-chat-typing span { width: 6px; height: 6px; border-radius: 50%; background: ${options.primaryColor}; opacity: 0.5; display: inline-block; animation: crBounce 1.4s infinite cubic-bezier(0.4, 0, 0.2, 1); }
      .cr-chat-typing span:nth-child(1) { animation-delay: 0s; }
      .cr-chat-typing span:nth-child(2) { animation-delay: 0.2s; }
      .cr-chat-typing span:nth-child(3) { animation-delay: 0.4s; }
      
      /* Animations */
      @keyframes crPop { 0% { transform: scale(0); opacity: 0; } 80% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
      @keyframes crBounce { 0%, 100% { transform: translateY(0); opacity: 0.5; } 50% { transform: translateY(-4px); opacity: 1; } }
      @keyframes crFadeInUp { from { opacity: 0; transform: translateY(24px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
      @keyframes crMsgIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

      .cr-chat-toggle.cr-toggle-off { display: none; }
      .cr-chat-toggle img { animation: crPulse 6s ease-in-out infinite; }
      .cr-chat-toggle:hover img { animation-play-state: paused; transform: scale(1.0); }
      @keyframes crPulse {
        0%, 77%  { transform: scale(1.0); }
        82%      { transform: scale(1.22); }
        86%      { transform: scale(0.93); }
        90%      { transform: scale(1.12); }
        94%, 100%{ transform: scale(1.0); }
      }
      
      /* Mobile layout */
      @media (max-width: 640px) { 
        .cr-chatbot-shell, .cr-chatbot-shell.cr-left { right: 16px; left: 16px; bottom: 16px; } 
        .cr-chat-window { width: 100%; height: min(80vh, 600px); border-radius: 28px 28px 20px 20px; } 
      }
    `;
    document.head.appendChild(style);
  }

  function createWidget(options) {
    const shell = document.createElement('div');
    shell.className = `cr-chatbot-shell ${options.position === 'bottom-left' ? 'cr-left' : ''}`;

    const toggle = document.createElement('button');
    toggle.className = 'cr-chat-toggle';
    toggle.setAttribute('type', 'button');
    toggle.setAttribute('aria-label', 'Abrir chatbot');
    toggle.innerHTML = options.robotImage ? `<img src="${options.robotImage}" alt="Chatbot" />` : `<span class="fallback-emoji">💬</span>`;

    const windowEl = document.createElement('section');
    windowEl.className = 'cr-chat-window';
    windowEl.setAttribute('aria-live', 'polite');

    // Icono SVG Viñeta / Burbuja Chat elegante
    const aiIconSvg = `
      <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M14 4C8.47715 4 4 8.02944 4 13C4 15.6599 5.30948 18.0468 7.37877 19.6481L6.15579 22.9126C5.93922 23.4907 6.45607 24.0664 7.05048 23.9082L10.866 22.8916C11.8654 23.1558 12.9189 23.3 14 23.3C19.5228 23.3 24 19.2706 24 14.2706C24 9.27056 19.5228 4 14 4ZM10 12C9.44772 12 9 12.4477 9 13C9 13.5523 9.44772 14 10 14H18C18.5523 14 19 13.5523 19 13C19 12.4477 18.5523 12 18 12H10Z" fill="#FFFFFF"/>
      </svg>
    `;

    windowEl.innerHTML = `
      <div class="cr-chat-header">
        <div class="cr-chat-ai-icon">
          ${aiIconSvg}
        </div>
        <div class="cr-chat-header-text">
          <div class="cr-chat-title">${escapeHtml(options.title)}</div>
          <div class="cr-chat-subtitle">En linea</div>
        </div>
        <button class="cr-chat-close" type="button" aria-label="Cerrar" title="Cerrar">
          <svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="cr-chat-messages"></div>
      <div class="cr-chat-footer">
        <form class="cr-chat-form">
          <div class="cr-chat-input-wrapper">
            <button type="button" class="cr-chat-attach-btn" title="Adjuntar archivo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path></svg>
            </button>
            <input type="file" class="cr-chat-file-input" style="display:none" accept=".jpg,.jpeg,.png,.pdf">
            <textarea class="cr-chat-input" rows="1" placeholder="${escapeHtml(options.placeholder)}"></textarea>
          </div>
          <button class="cr-chat-submit" type="submit" title="Enviar mensaje">
            ${escapeHtml(options.sendButtonLabel)}
          </button>
        </form>
      </div>
    `;

    shell.appendChild(toggle);
    shell.appendChild(windowEl);
    document.body.appendChild(shell);

    return {
      shell, toggle, windowEl,
      close: windowEl.querySelector('.cr-chat-close'),
      messages: windowEl.querySelector('.cr-chat-messages'),
      form: windowEl.querySelector('.cr-chat-form'),
      input: windowEl.querySelector('.cr-chat-input'),
      submit: windowEl.querySelector('.cr-chat-submit'),
      attachBtn: windowEl.querySelector('.cr-chat-attach-btn'),
      fileInput: windowEl.querySelector('.cr-chat-file-input')
    };
  }

  function bindEvents(elements, options, sessionId) {
    const openChat = () => { elements.windowEl.classList.add('cr-open'); elements.toggle.classList.add('cr-toggle-off'); setTimeout(() => elements.input.focus(), 80); };
    const closeChat = () => { elements.windowEl.classList.remove('cr-open'); elements.toggle.classList.remove('cr-toggle-off');  };

    // Historial de mensajes para mantener el contexto con la IA
    window.crMessageHistory = [{ role: 'assistant', content: options.greeting }];

    elements.toggle.addEventListener('click', () => {
      if (elements.windowEl.classList.contains('cr-open')) closeChat(); else openChat();
    });
    elements.close.addEventListener('click', closeChat);

    document.addEventListener('click', (e) => {
      if (elements.windowEl.classList.contains('cr-open') && !elements.windowEl.contains(e.target) && !elements.toggle.contains(e.target)) {
        closeChat();
      }
    });
    elements.input.addEventListener('input', () => { elements.input.style.height = 'auto'; elements.input.style.height = `${Math.min(elements.input.scrollHeight, 120)}px`; });
    elements.input.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); elements.form.requestSubmit(); } });

    // Lógica de Subida de Archivos
    elements.attachBtn.addEventListener('click', () => {
      if (!window.ChatbotRegladoSession || !window.ChatbotRegladoSession.nombre) {
        addMessage(elements.messages, "Por seguridad, debe facilitar antes sus datos al chatbot para asociar el archivo a sus datos personales.", 'bot');
        
        // Disparamos un mensaje invisible o sugerencia para que el bot pida los datos
        setTimeout(() => {
            const msg = "Me gustaría facilitar mis datos para poder subir archivos.";
            addMessage(elements.messages, msg, 'user');
            window.crMessageHistory.push({ role: 'user', content: msg });
            
            // Llamar al backend para que el bot responda pidiendo los datos
            enviarMensajeAlBot(msg);
        }, 600);
        return;
      }
      elements.fileInput.click();
    });

    async function enviarMensajeAlBot(messageText) {
      setLoading(elements, true);
      const typingId = addTyping(elements.messages);
      try {
        const response = await fetch(options.apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            messages: window.crMessageHistory.slice(-10),
            domain: window.location.hostname,
            sessionId: sessionId
          })
        });
        const data = await response.json();
        removeTyping(typingId);
        if (data.reply) {
          addMessage(elements.messages, data.reply, 'bot');
          window.crMessageHistory.push({ role: 'assistant', content: data.reply });
        }
        if (data.userContext) {
          window.ChatbotRegladoSession = data.userContext;
        }
      } catch (e) {
        removeTyping(typingId);
        addMessage(elements.messages, "Error al conectar con el servidor.", 'bot');
      } finally {
        setLoading(elements, false);
      }
    }

    elements.fileInput.addEventListener('change', async () => {
      const file = elements.fileInput.files[0];
      if (!file) return;

      if (file.size > 100 * 1024 * 1024) {
        addMessage(elements.messages, 'El archivo supera el límite de 100 MB. Por favor, sube un archivo más pequeño.', 'bot');
        elements.fileInput.value = '';
        return;
      }

      setLoading(elements, true);
      addMessage(elements.messages, `Subiendo archivo: ${file.name}...`, 'user');

      const formData = new FormData();
      formData.append('archivo', file);
      formData.append('sessionId', sessionId);
      
      // Pasar datos de sesión si existen para asociar el archivo en el backend
      if (window.ChatbotRegladoSession) {
        formData.append('nombre', window.ChatbotRegladoSession.nombre || '');
        formData.append('email', window.ChatbotRegladoSession.email || '');
        formData.append('telefono', window.ChatbotRegladoSession.telefono || '');
        formData.append('usuarioId', window.ChatbotRegladoSession.id || '');
      }

      try {
        const response = await fetch(options.apiUrl.replace('/chat', '/api/upload'), {
          method: 'POST',
          body: formData
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.error || 'Error al subir');

        // Check visual tipo imagen enviada
        showSuccessCheck(elements);
        
        const botReply = data.mensajeConfirmacion || `✅ Archivo subido: ${file.name}`;
        addMessage(elements.messages, botReply, 'bot');
        
        // Añadimos al historial para que la IA sepa que se ha subido un archivo
        window.crMessageHistory.push({ role: 'user', content: `[Archivo Adjunto]: ${file.name} (URL: ${data.url})` });
        window.crMessageHistory.push({ role: 'assistant', content: botReply });

      } catch (error) {
        addMessage(elements.messages, `❌ Error al subir: ${error.message}`, 'bot');
      } finally {
        setLoading(elements, false);
        elements.fileInput.value = '';
      }
    });

    elements.form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const message = elements.input.value.trim();
      if (!message) return;

      addMessage(elements.messages, message, 'user');
      window.crMessageHistory.push({ role: 'user', content: message });

      elements.input.value = '';
      elements.input.style.height = '46px';
      setLoading(elements, true);
      const typingId = addTyping(elements.messages);

      try {
        const response = await fetch(options.apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            messages: window.crMessageHistory.slice(-10), // Enviamos los últimos 10 mensajes para contexto
            domain: window.location.hostname,
            sessionId: sessionId
          })
        });
        const data = await response.json();
        removeTyping(typingId);
        if (!response.ok) throw new Error(data?.detail || data?.error || 'No se pudo obtener respuesta del servidor.');

        const botReply = data.reply;
        
        if (data.estado) {
            actualizarUIEstado(elements, data.estado);
            if (data.estado === 'WAITING_HUMAN' || data.estado === 'HUMAN') {
                if (typeof iniciarPolling === 'function') {
                    // Call the polling function from outer scope
                    iniciarPolling(sessionId, options, elements);
                }
            }
        }

        // Capturar datos de sesión si el servidor nos los envía (tras agendar cita)
        if (data.userContext) {
          window.ChatbotRegladoSession = data.userContext;
        }

        // Si el bot nos confirma la cita, activamos el check verde
        if (botReply && (botReply.includes('agendada') || botReply.includes('agendado') || botReply.includes('Perfecto'))) {
          showSuccessCheck(elements);
        }

        if (botReply) {
            addMessage(elements.messages, botReply, 'bot');
            window.crMessageHistory.push({ role: 'assistant', content: botReply });
        }

      } catch (error) {
        removeTyping(typingId);
        addMessage(elements.messages, `Lo siento, ha ocurrido un error: ${error.message}`, 'bot');
      } finally {
        setLoading(elements, false);
      }
    });
  }

  function showSuccessCheck(elements) {
    const checkDiv = document.createElement('div');
    checkDiv.style.display = 'flex';
    checkDiv.style.justifyContent = 'center';
    checkDiv.style.margin = '12px 0';
    checkDiv.innerHTML = `
      <div style="background: #2ecc71; color: white; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); animation: crPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
    `;
    elements.messages.appendChild(checkDiv);
    elements.messages.scrollTop = elements.messages.scrollHeight;
  }

  function addMessage(container, text, role, prefix = '') {
    const row = document.createElement('div');
    row.className = `cr-chat-row ${role}`;
    const bubble = document.createElement('div');
    bubble.className = 'cr-chat-bubble';
    bubble.textContent = prefix + text;
    row.appendChild(bubble);
    container.appendChild(row);
    container.scrollTop = container.scrollHeight;
  }

  function addTyping(container) {
    const id = `cr-typing-${Date.now()}`;
    const row = document.createElement('div');
    row.className = 'cr-chat-row bot';
    row.dataset.typingId = id;
    row.innerHTML = '<div class="cr-chat-bubble"><div class="cr-chat-typing"><span></span><span></span><span></span></div></div>';
    container.appendChild(row);
    container.scrollTop = container.scrollHeight;
    return id;
  }

  function removeTyping(id) {
    const item = document.querySelector(`[data-typing-id="${id}"]`);
    if (item) item.remove();
  }

  function setLoading(elements, value) {
    elements.submit.disabled = value;
    elements.input.disabled = value;
  }

  function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
})();
