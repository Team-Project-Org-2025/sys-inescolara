const CHATBOT = (() => {
  const CHATBOT_API_URL = 'https://api.vivero.com/chat';
  const CHATBOT_SESSION_ID = 'chat_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);

  let state = {
    messages: [],
    isProcessing: false,
    hasGreeted: false,
  };

  const BOT_RESPONSES = {
    stock: 'Actualmente tenemos 15,234 plantas en stock, distribuidas en 48 lotes activos. Las especies con mayor disponibilidad son: Heliconia Caribaea (450 unidades), Palma Areca (380 unidades) y Croton Variegado (320 unidades).',
    ventas: 'Las ventas de esta semana suman Bs. 2,450,000, un incremento del 12% respecto a la semana anterior. Los productos mas vendidos son plantas ornamentales para jardines residenciales.',
    bajo_stock: 'Hay 5 especies con stock critico: Orquidea Cattleya (8 unidades), Helecho Boston (12 unidades), Palma Kentia (15 unidades), Rosa del Desierto (18 unidades) y Planta de Jade (20 unidades).',
    proyectos: 'Hay 3 proyectos institucionales activos: Reforestacion Parque del Este (entrega: 15/02), Jardineria Alcaldia (entrega: 28/02) y Escuelas Verdes (entrega: 10/03). Total comprometido: 2,500 plantas.',
    cuadrillas: 'Las cuadrillas tienen 12 tareas pendientes para hoy: 5 de riego, 4 de trasplante y 3 de fumigacion. El equipo A esta asignado al sector de ornamentales y el equipo B al sector forestal.',
  };

  const QUICK_REPLIES = [
    { label: 'Stock de plantas', query: 'stock' },
    { label: 'Ventas recientes', query: 'ventas' },
    { label: 'Stock critico', query: 'bajo stock' },
    { label: 'Proyectos activos', query: 'proyectos' },
    { label: 'Tareas pendientes', query: 'cuadrillas' },
  ];

  const SVG_PLANT = [
    '<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">',
    '  <ellipse cx="20" cy="35" rx="10" ry="3" fill="#8d6e63" opacity="0.3"/>',
    '  <path d="M12 34 L11 24 L29 24 L28 34 Z" fill="#c48f2a"/>',
    '  <rect x="12" y="24" width="16" height="2" rx="1" fill="#a0762a"/>',
    '  <path d="M20 24 C20 24 20 14 20 12" stroke="#8d6e63" stroke-width="2.5" stroke-linecap="round"/>',
    '  <path d="M20 16 C20 16 26 12 28 9" stroke="#8d6e63" stroke-width="1.5" stroke-linecap="round"/>',
    '  <path d="M20 18 C20 18 14 14 12 11" stroke="#8d6e63" stroke-width="1.5" stroke-linecap="round"/>',
    '  <circle cx="28" cy="8" r="3.5" fill="#e5a835" opacity="0.9"/>',
    '  <circle cx="27" cy="10" r="2.5" fill="#f9c74f" opacity="0.85"/>',
    '  <circle cx="12" cy="10" r="3" fill="#e5a835" opacity="0.9"/>',
    '  <circle cx="13" cy="12" r="2.5" fill="#f9c74f" opacity="0.85"/>',
    '  <circle cx="20" cy="10" r="3.5" fill="#e5a835" opacity="0.9"/>',
    '  <circle cx="20" cy="8" r="2.5" fill="#ffeb3b" opacity="0.85"/>',
    '  <circle r="1.2" cx="26" cy="6" fill="#e5a835" opacity="0.4"/>',
    '  <circle r="1" cx="16" cy="6" fill="#f9c74f" opacity="0.35"/>',
    '</svg>',
  ].join('\n');

  const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

  function getTime() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    return h + ':' + m;
  }

  function sendWelcomeMessage() {
    if (state.hasGreeted) return;
    state.hasGreeted = true;

    setTimeout(() => {
      addMessage('bot', '¡Hola! Soy el asistente virtual de INECOLARA. Puedo ayudarte con informacion sobre inventario, ventas, proyectos y mas. ¿En que te puedo ayudar hoy?', true);
      renderQuickReplies(QUICK_REPLIES);
    }, 500);
  }

  function addMessage(role, content, skipStore) {
    const container = document.getElementById('chatMessages');
    if (!container) return;

    if (!skipStore) {
      state.messages.push({ role, content });
    }

    const div = document.createElement('div');
    div.className = 'message message-' + role;

    if (role === 'bot') {
      div.innerHTML = [
        '<div class="message-sender"><i class="fas fa-leaf" style="font-size:10px"></i> Asistente</div>',
        '<div class="message-content">' + escapeHtml(content) + '</div>',
        '<div class="message-time">' + getTime() + '</div>',
      ].join('\n');
    } else {
      div.innerHTML = [
        '<div class="message-content">' + escapeHtml(content) + '</div>',
        '<div class="message-time">' + getTime() + '</div>',
      ].join('\n');
    }

    container.appendChild(div);
    removeTypingIndicator();
    scrollToBottom();
  }

  function renderQuickReplies(replies) {
    const container = document.getElementById('chatQuickReplies');
    if (!container) return;
    container.innerHTML = '';

    const wrapper = document.createElement('div');
    wrapper.className = 'quick-replies';

    replies.forEach(r => {
      const btn = document.createElement('button');
      btn.className = 'quick-reply-btn';
      btn.textContent = r.label;
      btn.addEventListener('click', () => {
        container.innerHTML = '';
        handleUserMessage(r.query);
      });
      wrapper.appendChild(btn);
    });

    container.appendChild(wrapper);
  }

  function showTypingIndicator() {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    removeTypingIndicator();

    const indicator = document.createElement('div');
    indicator.className = 'typing-indicator';
    indicator.id = 'typingIndicator';
    indicator.innerHTML = [
      '<div class="typing-dots">',
      '  <span class="typing-dot"></span>',
      '  <span class="typing-dot"></span>',
      '  <span class="typing-dot"></span>',
      '</div>',
      '<span class="typing-text">Escribiendo...</span>',
    ].join('\n');

    container.appendChild(indicator);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    const el = document.getElementById('typingIndicator');
    if (el) el.remove();
  }

  function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    if (container) {
      container.scrollTop = container.scrollHeight;
    }
  }

  function handleSend() {
    const input = document.getElementById('chatInput');
    if (!input) return;
    const text = input.value.trim();
    if (!text || state.isProcessing) return;
    input.value = '';
    input.style.height = 'auto';
    handleUserMessage(text);
  }

  async function handleUserMessage(text) {
    if (state.isProcessing) return;
    state.isProcessing = true;

    addMessage('user', text);

    const quickRepliesContainer = document.getElementById('chatQuickReplies');
    if (quickRepliesContainer) quickRepliesContainer.innerHTML = '';

    showTypingIndicator();

    try {
      const botResponse = await sendToAPI(text);
      addMessage('bot', botResponse.text);
      if (botResponse.quickReplies && botResponse.quickReplies.length) {
        renderQuickReplies(botResponse.quickReplies);
      } else {
        renderQuickReplies(QUICK_REPLIES);
      }
    } catch {
      const simulated = await simulateResponse(text);
      addMessage('bot', simulated.text);
      if (simulated.quickReplies && simulated.quickReplies.length) {
        renderQuickReplies(simulated.quickReplies);
      } else {
        renderQuickReplies(QUICK_REPLIES);
      }
    } finally {
      state.isProcessing = false;
    }
  }

  async function sendToAPI(message) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);

    try {
      const response = await fetch(CHATBOT_API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: message,
          session_id: CHATBOT_SESSION_ID,
          context: {
            platform: 'vivero_inescolara',
            language: 'es',
          },
        }),
        signal: controller.signal,
      });

      clearTimeout(timeout);

      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }

      const data = await response.json();
      return {
        text: data.message || data.response || data.text || 'Gracias por tu consulta.',
        quickReplies: data.quick_replies || null,
      };
    } catch (err) {
      clearTimeout(timeout);
      throw err;
    }
  }

  function matchQuery(message) {
    const lower = message.toLowerCase();
    if (lower.includes('stock') || lower.includes('inventario') || lower.includes('planta')) return 'stock';
    if (lower.includes('venta') || lower.includes('vendido') || lower.includes('ingreso')) return 'ventas';
    if (lower.includes('bajo') || lower.includes('critico') || lower.includes('falta') || lower.includes('escaso')) return 'bajo_stock';
    if (lower.includes('proyecto') || lower.includes('institucional') || lower.includes('entrega')) return 'proyectos';
    if (lower.includes('cuadrilla') || lower.includes('tarea') || lower.includes('equipo') || lower.includes('trabajador')) return 'cuadrillas';
    if (lower.includes('hola') || lower.includes('buenos') || lower.includes('saludos') || lower.includes('hey')) return 'saludo';
    if (lower.includes('gracias') || lower.includes('ayuda') || lower.includes('graci')) return 'gracias';
    return null;
  }

  async function simulateResponse(message) {
    await delay(600 + Math.random() * 800);
    const match = matchQuery(message);

    if (match === 'stock') return { text: BOT_RESPONSES.stock, quickReplies: null };
    if (match === 'ventas') return { text: BOT_RESPONSES.ventas, quickReplies: null };
    if (match === 'bajo_stock') return { text: BOT_RESPONSES.bajo_stock, quickReplies: null };
    if (match === 'proyectos') return { text: BOT_RESPONSES.proyectos, quickReplies: null };
    if (match === 'cuadrillas') return { text: BOT_RESPONSES.cuadrillas, quickReplies: null };
    if (match === 'saludo') return { text: '¡Hola! Encantado de saludarte. Puedo ayudarte con informacion sobre inventario, ventas, proyectos, cuadrillas y mas. ¿Sobre que deseas consultar?', quickReplies: QUICK_REPLIES };
    if (match === 'gracias') return { text: '¡De nada! Estoy aqui para ayudarte. Si tienes mas preguntas, no dudes en escribirme.', quickReplies: QUICK_REPLIES };

    return {
      text: 'Lo siento, no tengo informacion especifica sobre eso. Puedo ayudarte con consultas sobre stock, ventas, lotes con bajo stock, proyectos activos o tareas de cuadrillas. ¿Sobre cual deseas saber?',
      quickReplies: QUICK_REPLIES,
    };
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function buildChatHTML() {
    return [
      '<div class="chat-panel" id="chatPanel">',
      '  <div class="chat-panel-inner">',
      '    <div class="chat-panel-header" id="chatPanelHeader">',
      '      <div class="header-avatar">' + SVG_PLANT + '</div>',
      '      <div class="header-info">',
      '        <p class="header-title">Asistente IA</p>',
      '        <p class="header-subtitle"><span class="status-dot"></span> En linea</p>',
      '      </div>',
      '    </div>',
      '    <div class="chat-panel-messages" id="chatMessages"></div>',
      '    <div class="chat-quick-replies" id="chatQuickReplies"></div>',
      '    <div class="chat-input-area">',
      '      <textarea class="chat-input" id="chatInput" rows="1" placeholder="Escribe un mensaje..." aria-label="Mensaje"></textarea>',
      '      <button class="chat-send-btn" id="chatSendBtn" aria-label="Enviar mensaje">',
      '        <i class="fas fa-paper-plane"></i>',
      '      </button>',
      '    </div>',
      '  </div>',
      '</div>',
    ].join('\n');
  }

  function init() {
    let root = document.getElementById('chatbotRoot');
    if (!root) {
      root = document.createElement('div');
      root.className = 'chatbot-root';
      root.id = 'chatbotRoot';
      document.body.appendChild(root);
    }

    root.innerHTML = buildChatHTML();

    document.getElementById('chatSendBtn').addEventListener('click', handleSend);
    document.getElementById('chatInput').addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
      }
      autoResizeInput(e.target);
    });

    setTimeout(() => sendWelcomeMessage(), 500);
  }

  function autoResizeInput(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
  }

  return { init };
})();

document.addEventListener('DOMContentLoaded', () => {
  CHATBOT.init();
});
