/* García Smester — Chatbot widget */
(function () {
    'use strict';

    const API_URL      = '/api/chatbot.php';
    const COT_URL      = '/api/cotizacion.php';
    const IMAGEGEN_URL = '/api/imagegen.php';
    const STORAGE_KEY  = 'gs_chat_sesion';
    const HISTORY_KEY  = 'gs_chat_history';

    // ── Estado ──────────────────────────────────────────────────
    let sesionId = sessionStorage.getItem(STORAGE_KEY) || '';
    let isOpen   = false;
    let isTyping = false;

    // Estado del flujo de cotización
    // steps: nombre → telefono → servicio → imagen → confirmar
    let cotFlow = null; // null = sin flujo activo
    // Estado del subpaso de imagen
    let imagenState = { activo: false, url: '', reintentos: 0 };

    let history = [];
    try { history = JSON.parse(sessionStorage.getItem(HISTORY_KEY) || '[]'); } catch (e) { history = []; }
    history = history.filter(function (h) { return h && h.texto !== 'Error de conexión. Intenta de nuevo.'; });
    saveHistory();

    // ── Crear DOM ────────────────────────────────────────────────
    const widget = document.createElement('div');
    widget.id = 'gs-chat-widget';
    widget.innerHTML = `
        <button class="gs-chat-btn" id="gs-chat-toggle" aria-label="Abrir chat" aria-expanded="false">
            <svg class="gs-chat-btn__icon gs-chat-btn__icon--open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <svg class="gs-chat-btn__icon gs-chat-btn__icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            <span class="gs-chat-badge" id="gs-chat-badge" hidden>1</span>
        </button>

        <div class="gs-chat-panel" id="gs-chat-panel" hidden aria-label="Chat de soporte">
            <div class="gs-chat-header">
                <div class="gs-chat-header__info">
                    <span class="gs-chat-header__dot"></span>
                    <div>
                        <strong>García Smester</strong>
                        <small id="gs-chat-status">Disponible</small>
                    </div>
                </div>
                <button class="gs-chat-header__close" id="gs-chat-close" aria-label="Cerrar chat">✕</button>
            </div>

            <div class="gs-chat-messages" id="gs-chat-messages" role="log" aria-live="polite"></div>

            <form class="gs-chat-form" id="gs-chat-form" autocomplete="off">
                <input type="text" id="gs-chat-input" class="gs-chat-input"
                       placeholder="Escribe tu mensaje…"
                       maxlength="300" aria-label="Mensaje">
                <button type="submit" class="gs-chat-send" aria-label="Enviar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </form>
        </div>
    `;
    document.body.appendChild(widget);

    const toggle   = document.getElementById('gs-chat-toggle');
    const panel    = document.getElementById('gs-chat-panel');
    const close    = document.getElementById('gs-chat-close');
    const messages = document.getElementById('gs-chat-messages');
    const form     = document.getElementById('gs-chat-form');
    const input    = document.getElementById('gs-chat-input');
    const badge    = document.getElementById('gs-chat-badge');
    const status   = document.getElementById('gs-chat-status');

    // ── Helpers ──────────────────────────────────────────────────
    function escapeHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                  .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function scrollBottom() { messages.scrollTop = messages.scrollHeight; }

    function saveHistory() {
        if (history.length > 40) history = history.slice(-40);
        sessionStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    }

    function renderMessage(tipo, texto, botones, acciones) {
        const wrap = document.createElement('div');
        wrap.className = 'gs-msg gs-msg--' + tipo;

        const bubble = document.createElement('div');
        bubble.className = 'gs-msg__bubble';
        bubble.textContent = texto;
        wrap.appendChild(bubble);

        // Botones de URL (links normales)
        if (botones && botones.length) {
            const btns = document.createElement('div');
            btns.className = 'gs-msg__buttons';
            botones.forEach(function (b) {
                const a = document.createElement('a');
                a.href = escapeHtml(b.url || '#');
                a.textContent = b.texto || '';
                a.className = 'gs-msg__btn';
                if ((b.url || '').startsWith('http')) a.target = '_blank';
                btns.appendChild(a);
            });
            wrap.appendChild(btns);
        }

        // Botones de acción JS (para flujo cotización)
        if (acciones && acciones.length) {
            const btns = document.createElement('div');
            btns.className = 'gs-msg__buttons';
            acciones.forEach(function (ac) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = ac.texto;
                btn.className = 'gs-msg__btn';
                btn.addEventListener('click', ac.fn);
                btns.appendChild(btn);
            });
            wrap.appendChild(btns);
        }

        messages.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function botMsg(texto, botones, acciones) {
        renderMessage('bot', texto, botones || [], acciones || []);
        history.push({ tipo: 'bot', texto: texto, botones: botones || [] });
        saveHistory();
    }

    function showTyping() {
        if (isTyping) return;
        isTyping = true;
        const el = document.createElement('div');
        el.className = 'gs-msg gs-msg--bot gs-msg--typing';
        el.id = 'gs-typing';
        el.innerHTML = '<div class="gs-msg__bubble"><span></span><span></span><span></span></div>';
        messages.appendChild(el);
        scrollBottom();
    }

    function hideTyping() {
        isTyping = false;
        const el = document.getElementById('gs-typing');
        if (el) el.remove();
    }

    function setStatus(texto) { if (status) status.textContent = texto; }

    function setInputPlaceholder(txt) { input.placeholder = txt; }

    function connectionErrorButtons() {
        return [
            { texto: 'WhatsApp', url: (document.querySelector('.whatsapp-btn') || {}).href || '/contacto/' },
            { texto: 'Contacto', url: '/contacto/' },
        ];
    }

    function renderHistory() {
        history.forEach(function (h) { renderMessage(h.tipo, h.texto, h.botones || []); });
    }

    // ── Flujo cotización ─────────────────────────────────────────
    // steps: 0=nombre 1=telefono 2=servicio 3=imagen(oferta) 4=confirmar
    // imagenState.activo=true cuando esperamos el prompt de imagen del usuario

    function iniciarCotizacion() {
        cotFlow     = { step: 0, datos: { nombre: '', telefono: '', servicio: '', imagenUrl: '' } };
        imagenState = { activo: false, url: '', reintentos: 0 };
        setInputPlaceholder('Tu nombre completo…');
        botMsg('¡Claro! Voy a tomarte los datos para la cotización. ¿Cuál es tu nombre?');
    }

    function cancelarCotizacion() {
        cotFlow     = null;
        imagenState = { activo: false, url: '', reintentos: 0 };
        setInputPlaceholder('Escribe tu mensaje…');
        botMsg('Cotización cancelada. ¿En qué más puedo ayudarte?');
    }

    function mostrarConfirmacion() {
        const d = cotFlow.datos;
        let resumen = '¿Confirmamos la solicitud?\n\nNombre: ' + d.nombre +
                      '\nTeléfono: ' + d.telefono +
                      '\nServicio: ' + d.servicio;
        if (d.imagenUrl) resumen += '\n🖼 Imagen adjunta: Sí';
        botMsg(resumen, [], [
            { texto: '✓ Confirmar', fn: enviarCotizacion },
            { texto: '✗ Cancelar',  fn: cancelarCotizacion },
        ]);
    }

    function renderImagenEnChat(url, altText) {
        const wrap = document.createElement('div');
        wrap.className = 'gs-msg gs-msg--bot';
        const img = document.createElement('img');
        img.src   = url;
        img.alt   = altText || 'Visualización generada por IA';
        img.style.cssText = 'max-width:100%;border-radius:10px;margin-top:6px;display:block';
        img.loading = 'lazy';
        wrap.appendChild(img);
        messages.appendChild(wrap);
        scrollBottom();
    }

    function subirImagenUsuario(file) {
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            botMsg('La imagen no debe superar 5 MB. Elige otra o continúa sin imagen.', [], [
                { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } }
            ]);
            return;
        }
        setStatus('Subiendo imagen…');
        const fd = new FormData();
        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        fd.append('imagen', file);
        fetch('/api/upload-imagen.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setStatus('Disponible');
                if (data.ok && data.imagen_url) {
                    cotFlow.datos.imagenUrl = data.imagen_url;
                    imagenState.url         = data.imagen_url;
                    renderImagenEnChat(data.imagen_url, 'Imagen subida por el usuario');
                    botMsg('¿Usamos esta imagen para la cotización?', [], [
                        { texto: '✓ Sí, usar esta imagen',   fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } },
                        { texto: '✗ Continuar sin imagen',   fn: function() { cotFlow.datos.imagenUrl = ''; cotFlow.step = 4; mostrarConfirmacion(); } },
                    ]);
                } else {
                    botMsg(data.error || 'No se pudo subir la imagen.', [], [
                        { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } }
                    ]);
                }
            })
            .catch(function() {
                setStatus('Disponible');
                botMsg('Error de conexión al subir la imagen.', [], [
                    { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } }
                ]);
            });
    }

    async function generarImagen(promptUsuario) {
        imagenState.activo = false;
        setInputPlaceholder('Escribe tu mensaje…');
        showTyping();
        setStatus('Generando imagen…');

        try {
            const res  = await fetch(IMAGEGEN_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ prompt: promptUsuario, sesion_id: sesionId }),
            });
            hideTyping();
            setStatus('Disponible');

            if (res.status === 429) {
                botMsg('Alcanzaste el límite de imágenes por ahora. Puedes continuar la cotización sin imagen.', [], [
                    { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } },
                    { texto: '✗ Cancelar',           fn: cancelarCotizacion },
                ]);
                return;
            }

            const data = await res.json();

            if (data.ok && data.imagen_url) {
                cotFlow.datos.imagenUrl = data.imagen_url;
                imagenState.url        = data.imagen_url;

                renderImagenEnChat(data.imagen_url);

                const puedeReintentar = imagenState.reintentos < 2;
                const accs = [
                    { texto: '✓ Usar esta imagen',   fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } },
                    { texto: '✗ Continuar sin imagen',fn: function() { cotFlow.datos.imagenUrl = ''; cotFlow.step = 4; mostrarConfirmacion(); } },
                ];
                if (puedeReintentar) {
                    accs.splice(1, 0, { texto: '🔄 Intentar de nuevo', fn: function() {
                        imagenState.reintentos++;
                        cotFlow.datos.imagenUrl = '';
                        imagenState.activo = true;
                        setInputPlaceholder('Describe de nuevo el espacio…');
                        botMsg('Describe el espacio de otra forma y generaré una nueva imagen.');
                    }});
                }
                botMsg('¡Aquí está tu visualización! ¿La usamos en la cotización?', [], accs);
            } else {
                botMsg(data.error || 'No pude generar la imagen. ¿Continuamos sin ella?', [], [
                    { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } },
                    { texto: '✗ Cancelar',           fn: cancelarCotizacion },
                ]);
            }
        } catch (err) {
            hideTyping();
            setStatus('Error temporal');
            botMsg('Error de conexión al generar la imagen. ¿Continuamos sin ella?', [], [
                { texto: 'Continuar sin imagen', fn: function() { cotFlow.step = 4; mostrarConfirmacion(); } },
                { texto: '✗ Cancelar',           fn: cancelarCotizacion },
            ]);
        }
    }

    async function procesarCotizacion(valor) {
        // Si estamos esperando el prompt de imagen del usuario
        if (imagenState.activo) {
            await generarImagen(valor);
            return;
        }

        const step = cotFlow.step;

        if (step === 0) {
            if (valor.length < 2) { botMsg('Por favor escribe tu nombre completo.'); return; }
            cotFlow.datos.nombre = valor;
            cotFlow.step = 1;
            setInputPlaceholder('Ej: 809-555-1234');
            botMsg('Perfecto, ' + valor + '. ¿Cuál es tu número de teléfono?');

        } else if (step === 1) {
            const tel = valor.replace(/\s/g, '');
            if (tel.length < 7) { botMsg('Por favor escribe un número de teléfono válido.'); return; }
            cotFlow.datos.telefono = valor;
            cotFlow.step = 2;
            setInputPlaceholder('Ej: pisos hospitalarios, terrazzopoxy…');
            botMsg('¿Qué tipo de piso o servicio te interesa?');

        } else if (step === 2) {
            cotFlow.datos.servicio = valor;
            cotFlow.step = 3;
            setInputPlaceholder('Escribe tu mensaje…');
            // Ofrecer imagen (generar con IA o subir una)
            var fileInput = document.getElementById('gs-file-input');
            if (!fileInput) {
                fileInput = document.createElement('input');
                fileInput.type   = 'file';
                fileInput.id     = 'gs-file-input';
                fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
                fileInput.style.display = 'none';
                fileInput.addEventListener('change', function() {
                    if (fileInput.files && fileInput.files[0]) {
                        subirImagenUsuario(fileInput.files[0]);
                    }
                    fileInput.value = '';
                });
                document.body.appendChild(fileInput);
            }
            botMsg(
                '¿Quieres incluir una imagen del espacio en la cotización?',
                [],
                [
                    { texto: '🤖 Generar con IA', fn: function() {
                        imagenState.activo = true;
                        setInputPlaceholder('Ej: sala de operaciones, piso blanco liso, luz fría…');
                        botMsg('Describe brevemente el espacio (máx. 400 caracteres):');
                    }},
                    { texto: '📤 Subir una imagen', fn: function() {
                        fileInput.click();
                    }},
                    { texto: 'No, continuar', fn: function() {
                        cotFlow.step = 4;
                        mostrarConfirmacion();
                    }},
                ]
            );
        }
        // step 3 (imagen) y step 4 (confirmar) se manejan con botones de acción, no con input de texto
    }

    async function enviarCotizacion() {
        if (!cotFlow) return;
        const d    = cotFlow.datos;
        cotFlow    = null;
        imagenState = { activo: false, url: '', reintentos: 0 };
        setInputPlaceholder('Escribe tu mensaje…');

        showTyping();
        setStatus('Enviando…');

        try {
            const res = await fetch(COT_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    nombre:     d.nombre,
                    telefono:   d.telefono,
                    servicio:   d.servicio,
                    imagen_url: d.imagenUrl || '',
                    sesion_id:  sesionId,
                }),
            });

            hideTyping();
            setStatus('Disponible');

            const data = await res.json();

            if (data.ok && data.wa_redirect) {
                botMsg('¡Perfecto! Te llevamos a WhatsApp con tu información lista para enviar.', [
                    { texto: '💬 Abrir WhatsApp', url: data.wa_redirect },
                ]);
                window.open(data.wa_redirect, '_blank', 'noopener');
            } else if (data.ok) {
                botMsg(data.mensaje, data.botones || []);
            } else {
                botMsg('Hubo un problema al enviar. Contáctanos directamente.', connectionErrorButtons());
            }
        } catch (err) {
            hideTyping();
            setStatus('Error temporal');
            botMsg('No pudimos enviar la solicitud. Contáctanos por WhatsApp.', connectionErrorButtons());
        }
    }

    // ── Bienvenida ───────────────────────────────────────────────
    function showWelcome() {
        if (history.length === 0) {
            const bienvenida = '¡Hola! Soy el asistente de García Smester 👋 ¿En qué puedo ayudarte?';
            renderMessage('bot', bienvenida, [], [
                { texto: '📋 Solicitar cotización', fn: iniciarCotizacion },
                { texto: '🏥 Pisos hospitalarios',  fn: function () { window.location.href = '/pisos-hospitalarios-asepticos/'; } },
                { texto: '📞 Contacto',             fn: function () { window.location.href = '/contacto/'; } },
            ]);
            history.push({ tipo: 'bot', texto: bienvenida });
            saveHistory();
        }
    }

    // ── Abrir / cerrar ───────────────────────────────────────────
    function openChat() {
        isOpen = true;
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        badge.hidden = true;
        if (history.length === 0) {
            renderHistory();
            showWelcome();
        } else if (messages.children.length === 0) {
            renderHistory();
        }
        input.focus();
    }

    function closeChat() {
        isOpen = false;
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () { isOpen ? closeChat() : openChat(); });
    close.addEventListener('click', closeChat);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isOpen) closeChat(); });

    // ── Enviar mensaje ───────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const texto = input.value.trim();
        if (!texto || isTyping) return;

        input.value = '';
        renderMessage('usuario', texto, []);
        history.push({ tipo: 'usuario', texto: texto });
        saveHistory();

        // Si hay un flujo de cotización activo, procesarlo
        if (cotFlow !== null) {
            await procesarCotizacion(texto);
            return;
        }

        // Detectar intención de cotizar aunque no haya flujo activo
        const lower = texto.toLowerCase();
        const quiereCotizar = /cotiz|presupuest|precio|cuanto cuesta|cuánto cuesta|quiero pedir|solicitar/.test(lower);
        if (quiereCotizar) {
            iniciarCotizacion();
            return;
        }

        // Flujo normal del chatbot
        showTyping();
        setStatus('Respondiendo...');

        try {
            const res = await fetch(API_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ mensaje: texto, sesion_id: sesionId, pagina: window.location.pathname }),
            });

            hideTyping();

            if (!res.ok) {
                setStatus('Error temporal');
                renderMessage('bot', 'No pudimos conectar con el asistente. Intenta de nuevo o contáctanos por WhatsApp.', connectionErrorButtons());
                return;
            }

            const data = await res.json();
            setStatus('Disponible');

            if (data.sesion_id) {
                sesionId = data.sesion_id;
                sessionStorage.setItem(STORAGE_KEY, sesionId);
            }

            // Si la respuesta del bot incluye botón de cotizar como acción
            const botones = (data.botones || []).filter(b => b.url);
            renderMessage('bot', data.respuesta || 'Sin respuesta.', botones);
            history.push({ tipo: 'bot', texto: data.respuesta || '', botones: botones });
            saveHistory();

        } catch (err) {
            hideTyping();
            setStatus('Error temporal');
            renderMessage('bot', 'No pudimos conectar con el asistente. Intenta de nuevo o contáctanos por WhatsApp.', connectionErrorButtons());
        }
    });

    if (!isOpen && history.length > 0) { badge.hidden = false; }
})();
