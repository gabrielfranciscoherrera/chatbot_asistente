# Chatbot de diccionario — García Smester

Widget de chat embebible para sitios PHP + MySQL. Sin IA externa, sin API de terceros.
El servidor busca el mensaje del usuario en un diccionario configurable en BD y devuelve
la respuesta configurada. El panel admin permite gestionar todo el contenido sin tocar código.

---

## Índice

1. [Requisitos](#requisitos)
2. [Estructura de archivos](#estructura-de-archivos)
3. [Instalación paso a paso](#instalación-paso-a-paso)
4. [Adaptar a otro proyecto](#adaptar-a-otro-proyecto)
5. [Base de datos](#base-de-datos)
6. [API](#api)
7. [Lógica de matching](#lógica-de-matching)
8. [Frontend (widget)](#frontend-widget)
9. [Panel admin](#panel-admin)
10. [Flujo de cotización (opcional)](#flujo-de-cotización-opcional)
11. [Seguridad](#seguridad)
12. [Personalización visual](#personalización-visual)

---

## Requisitos

| Tecnología | Mínimo |
|-----------|--------|
| PHP | 8.1+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Extensión PHP `intl` | para `transliterator_transliterate` (normalización de acentos) |
| PDO + driver `pdo_mysql` | para acceso a BD |

---

## Estructura de archivos

```
chatbot/
├── README.md                  ← este documento
│
├── sql/
│   ├── 01_tablas.sql          ← CREATE TABLE de las 4 tablas
│   └── 02_seed_garcia_smester.sql  ← datos de ejemplo (García Smester)
│
├── backend/
│   ├── chatbot.php            ← endpoint público: POST /api/chatbot.php
│   └── chatbot_matcher.php    ← lógica de normalización y matching
│
├── frontend/
│   ├── chatbot.js             ← widget vanilla JS (auto-inyecta DOM)
│   └── chatbot.css            ← estilos del widget
│
└── panel/
    ├── chatbot-list.php       ← CRUD del diccionario (listado)
    ├── chatbot-form.php       ← formulario nueva/editar entrada
    └── chatbot-stats.php      ← estadísticas de uso
```

---

## Instalación paso a paso

### 1. Crear tablas en BD

```bash
mysql -u usuario -p nombre_bd < chatbot/sql/01_tablas.sql
```

Opcionalmente cargar los datos de ejemplo:

```bash
mysql -u usuario -p nombre_bd < chatbot/sql/02_seed_garcia_smester.sql
```

### 2. Copiar archivos backend

```
private/includes/chatbot_matcher.php   ← desde backend/chatbot_matcher.php
public_html/api/chatbot.php            ← desde backend/chatbot.php
```

### 3. Copiar archivos frontend

```
public_html/assets/js/chatbot.js       ← desde frontend/chatbot.js
public_html/assets/css/chatbot.css     ← desde frontend/chatbot.css
```

### 4. Incluir en el HTML de cada página

```html
<!-- En el <head> -->
<link rel="stylesheet" href="/assets/css/chatbot.css">

<!-- Antes de </body> -->
<script src="/assets/js/chatbot.js"></script>
```

El widget se inyecta solo al DOM. No hay nada más que agregar al HTML.

### 5. Copiar panel admin

```
public_html/tu-panel/chatbot-list.php   ← desde panel/chatbot-list.php
public_html/tu-panel/chatbot-form.php
public_html/tu-panel/chatbot-stats.php
```

---

## Adaptar a otro proyecto

Los archivos copiados tienen dependencias específicas del proyecto García Smester.
Aquí los puntos exactos a cambiar:

### `backend/chatbot.php`

| Línea | Qué cambiar |
|-------|-------------|
| `require_once dirname(__FILE__, 3) . '/private/config.php'` | Ajusta la ruta al `config.php` del nuevo proyecto |
| `require_once PRIVATE_PATH . '/includes/db.php'` | Función `db()` que devuelve un `PDO` |
| `require_once PRIVATE_PATH . '/includes/useragent.php'` | Puedes eliminar este include si no lo usas |
| `GS_WHATSAPP` | Constante con el número de WhatsApp (ej: `18095622566`) |
| `GS_TEL_1` | Constante con el teléfono principal |
| `SITE_URL` | URL base del sitio (ej: `https://tusitio.com`) |

Alternativa: reemplaza las constantes directamente con strings:

```php
// Antes
'url' => 'https://wa.me/' . GS_WHATSAPP
// Después
'url' => 'https://wa.me/18091234567'
```

### `backend/chatbot_matcher.php`

No tiene dependencias externas. Se puede copiar tal cual.

### `frontend/chatbot.js`

| Constante | Valor actual | Qué hacer |
|-----------|-------------|-----------|
| `API_URL` | `/api/chatbot.php` | Cambiar si el endpoint está en otra ruta |
| `COT_URL` | `/api/cotizacion.php` | Solo relevante si usas el flujo de cotización |
| `IMAGEGEN_URL` | `/api/imagegen.php` | Solo relevante si usas generación de imágenes con IA |
| Texto de bienvenida | `¡Hola! Soy el asistente de García Smester 👋` | Editar en la función `showWelcome()` |
| Botones de bienvenida | URLs a `/pisos-hospitalarios-asepticos/` etc. | Cambiar en `showWelcome()` |
| Color `#26e089` | Verde García Smester | Ver sección [Personalización visual](#personalización-visual) |

### `panel/*.php`

Los archivos del panel dependen de:
- `PRIVATE_PATH . '/includes/db.php'` → función `db(): PDO`
- `PRIVATE_PATH . '/includes/auth.php'` → funciones `authRequerida()`, `csrfValidar()`, `csrfToken()`
- `SITE_URL` → URL base
- `$_SESSION['admin_usuario']` → nombre del usuario logueado
- Partial `partials/sidebar.php` → sidebar del panel
- CSS `panel.min.css` y JS `panel.js` → estilos del panel

Si el nuevo proyecto tiene un sistema de auth diferente, reemplaza las llamadas
a `authRequerida()` y `csrfValidar()` / `csrfToken()` por el equivalente del proyecto.

---

## Base de datos

### `chatbot_entradas` — el diccionario

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT UNSIGNED PK | Auto increment |
| `categoria` | ENUM | `servicios`, `contacto`, `proyectos`, `precios`, `general` |
| `pregunta_tipo` | VARCHAR(200) | Etiqueta interna, no visible al usuario |
| `palabras_clave` | TEXT | CSV de claves: `precio,costo,cuánto,valor` |
| `respuesta` | TEXT | Texto que ve el usuario |
| `tiene_botones` | TINYINT(1) | 1 si incluye botones de acción |
| `botones_json` | JSON | `[{"texto":"Ver más","url":"/pagina/"}]` |
| `prioridad` | TINYINT UNSIGNED | 1=más alta, 10=más baja |
| `activo` | TINYINT(1) | 0=desactivada, no se consulta |

### `chatbot_conversaciones` — sesiones

Cada vez que un visitante abre el chat se crea una conversación con un `sesion_id` único
generado server-side (`bin2hex(random_bytes(32))`). El cliente lo guarda en `sessionStorage`
y lo reenvía en cada mensaje.

### `chatbot_mensajes` — historial

Cada mensaje (usuario o bot) se guarda con referencia a la conversación y a la entrada
del diccionario que activó la respuesta (`NULL` si fue fallback).

### `chatbot_rate` — rate limiting

Tabla efímera para limitar 30 mensajes/minuto por IP. Se limpia automáticamente.

---

## API

**Endpoint:** `POST /api/chatbot.php`  
**Content-Type:** `application/json`

### Request

```json
{ "mensaje": "cuánto cuesta el piso epóxico", "sesion_id": "abc123..." }
```

- `sesion_id` es **opcional** en la primera llamada. El servidor genera uno nuevo si no viene o es inválido.
- El cliente guarda el `sesion_id` devuelto en `sessionStorage` y lo reenvía en los mensajes siguientes.
- El cliente **nunca** genera ni elige el `sesion_id`.

Parámetro adicional opcional:

```json
{ "mensaje": "...", "sesion_id": "...", "pagina": "/contacto/" }
```

`pagina` registra en qué URL del sitio estaba el usuario cuando escribió el mensaje.

### Response — coincidencia encontrada

```json
{
  "sesion_id": "abc123...",
  "respuesta": "Los pisos EpoGloss™ y EpoQuartz™ resisten ácidos y tráfico pesado...",
  "botones": [
    { "texto": "Ver EpoGloss", "url": "/pisos-industriales-epoxicos/" },
    { "texto": "Cotizar",      "url": "/contacto/" }
  ],
  "fallback": false
}
```

### Response — sin coincidencia (fallback)

```json
{
  "sesion_id": "abc123...",
  "respuesta": "No encontré información sobre eso. Puedes contactarnos directamente...",
  "botones": [
    { "texto": "WhatsApp", "url": "https://wa.me/18095622566" },
    { "texto": "Contacto", "url": "/contacto/" }
  ],
  "fallback": true
}
```

### Códigos de error

| Código | Motivo |
|--------|--------|
| 400 | JSON inválido o mensaje vacío / > 500 caracteres |
| 405 | Método distinto de POST |
| 429 | Rate limit superado (>30 mensajes/min desde la misma IP) |

---

## Lógica de matching

Implementada en `chatbot_matcher.php`. Tres pasos en orden:

### 1. Normalización

```php
function normalizarTexto(string $texto): string
```

- Lowercase
- Elimina acentos con `transliterator_transliterate('Any-Latin; Latin-ASCII', ...)`
- Elimina caracteres no alfanuméricos excepto espacios
- Colapsa espacios múltiples

Resultado: `"¿Cuánto cuesta el EpoGloss™?"` → `"cuanto cuesta el epogloss"`

### 2. Búsqueda

Las entradas se cargan ordenadas por `prioridad ASC` (menor número = más específica).
Para cada entrada se evalúan sus claves CSV una a una hasta encontrar coincidencia.

### 3. Comparación por token (`palabraCoincide`)

Para cada clave:

1. **Coincidencia exacta de frase** con word boundary (`\b`): evita falsos positivos como "piso" dentro de "episodio".
2. **Multi-token AND**: para claves de 2+ palabras (ej: `"zona franca"`) todos los tokens deben coincidir.
3. **Coincidencia parcial**: si la clave tiene ≥5 caracteres y el token del texto la contiene (o viceversa).
4. **Levenshtein con umbral**: tolera 1 error tipográfico en palabras cortas, 2 en palabras de 6+ caracteres.
5. **Transposición de 2 letras**: detecta `"hoas"` vs `"hola"` (mismo conjunto de letras, 2 posiciones distintas).

Este sistema maneja errores ortográficos comunes sin depender de IA.

---

## Frontend (widget)

`chatbot.js` — vanilla JS, sin dependencias, ~350 líneas.

### Lo que hace al cargar

- Inyecta el DOM completo del widget (botón flotante + panel) en `document.body`
- Restaura el historial de la sesión desde `sessionStorage`
- Muestra el badge de notificación si hay mensajes previos

### Al abrir el chat

- Si no hay historial: muestra el mensaje de bienvenida con botones rápidos de acción
- Si hay historial: re-renderiza las burbujas

### Al enviar un mensaje

1. Renderiza la burbuja del usuario inmediatamente
2. Comprueba si el texto activa el flujo de cotización (regex: `cotiz|presupuest|precio|quiero pedir|solicitar`)
3. Si no, hace `POST /api/chatbot.php` con `fetch()`
4. Muestra indicador "escribiendo…" mientras espera
5. Renderiza la respuesta con sus botones

### Sesión

- `sesion_id` guardado en `sessionStorage` (se pierde al cerrar pestaña, intencionalmente)
- Historial de hasta 40 mensajes en `sessionStorage`
- Sin cookies, sin localStorage, sin tracking

---

## Panel admin

Tres páginas PHP independientes. Requieren autenticación (`authRequerida()`).

### `chatbot-list.php` — Diccionario

- Tabla completa de entradas con filtro por categoría
- Toggle activo/inactivo por fila (POST inmediato)
- Botón eliminar con confirmación
- Enlace a formulario de edición

### `chatbot-form.php` — Nueva / editar entrada

- Campos: etiqueta interna, categoría, palabras clave (CSV), respuesta, prioridad, activo
- Toggle para habilitar botones + textarea de JSON
- Validación server-side: JSON de botones parseado y verificado antes de guardar

### `chatbot-stats.php` — Estadísticas

- Tarjetas: conversaciones, mensajes, sin respuesta (últimos 30 días)
- Top 10 entradas más activadas
- **Top 10 mensajes con fallback**: la herramienta más útil para expandir el diccionario

---

## Flujo de cotización (opcional)

El widget incluye un flujo guiado de cotización en 5 pasos activado por palabras clave
(`cotizar`, `precio`, `presupuesto`, etc.) o por el botón de bienvenida.

```
paso 0: nombre completo
paso 1: teléfono
paso 2: servicio de interés
paso 3: imagen (generar con IA / subir archivo / saltar)
paso 4: confirmación → enviar a /api/cotizacion.php
```

`/api/cotizacion.php` y `/api/imagegen.php` **no están incluidos** en este paquete ya que
son específicos del proyecto (envían a WhatsApp y llaman a la API de generación de imágenes
respectivamente). Para usarlos en otro proyecto, crear esos endpoints con la misma firma:

**`POST /api/cotizacion.php`** — recibe `{nombre, telefono, servicio, imagen_url, sesion_id}`,
devuelve `{ok: true, wa_redirect: "https://wa.me/..."}` o `{ok: true, mensaje: "..."}`.

**`POST /api/imagegen.php`** — recibe `{prompt, sesion_id}`,
devuelve `{ok: true, imagen_url: "..."}` o `{ok: false, error: "..."}`.

Para deshabilitar este flujo completamente, eliminar de `chatbot.js`:
- La función `iniciarCotizacion()` y todas las que dependen de ella
- El bloque `if (quiereCotizar)` en el listener del form
- El botón `📋 Solicitar cotización` en `showWelcome()`

---

## Seguridad

| Medida | Dónde |
|--------|-------|
| Solo acepta POST | `chatbot.php` línea ~10 |
| Longitud máxima 500 chars | `chatbot.php` validación de `$mensaje` |
| Rate limit 30 req/min por IP | tabla `chatbot_rate` |
| `sesion_id` generado server-side | cliente no puede elegirlo |
| Prepared statements en todas las queries | `chatbot.php` y panel |
| `htmlspecialchars` en todas las salidas del panel | panel/*.php |
| `escapeHtml()` en el widget JS | `chatbot.js` |
| `JSON_UNESCAPED_UNICODE` en respuestas JSON | sin XSS por encoding |
| No expone datos internos de BD | solo `respuesta`, `botones`, `fallback`, `sesion_id` |

---

## Personalización visual

El color principal es `#26e089` (verde). Para cambiarlo busca y reemplaza en `chatbot.css`:

```bash
sed -i 's/#26e089/TU_COLOR/g' frontend/chatbot.css
```

Variables de tamaño en `chatbot.css`:

| Selector | Propiedad | Valor por defecto |
|----------|-----------|-------------------|
| `.gs-chat-panel` | `width` | `340px` |
| `.gs-chat-panel` | `max-height` | `520px` |
| `#gs-chat-widget` | `bottom` / `right` | `1.5rem` |

Para cambiar el nombre "García Smester" en el header editar el HTML generado
en la función IIFE de `chatbot.js` (busca `<strong>García Smester</strong>`).
