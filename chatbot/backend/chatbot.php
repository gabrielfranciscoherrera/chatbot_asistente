<?php
declare(strict_types=1);
require_once dirname(__FILE__, 3) . '/private/config.php';
require_once PRIVATE_PATH . '/includes/db.php';
require_once PRIVATE_PATH . '/includes/chatbot_matcher.php';
require_once PRIVATE_PATH . '/includes/useragent.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

// ── Rate limiting por IP: máx 30 mensajes por minuto ──────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

try {
    $pdo = db();
} catch (Exception $e) {
    $sesionId = bin2hex(random_bytes(32));
    echo json_encode([
        'respuesta' => 'Ahora mismo no puedo consultar el asistente. Puedes escribirnos por WhatsApp o dejarnos tus datos en contacto.',
        'botones'   => [
            ['texto' => 'WhatsApp', 'url' => 'https://wa.me/' . GS_WHATSAPP],
            ['texto' => 'Contacto', 'url' => SITE_URL . '/contacto/'],
        ],
        'fallback'  => true,
        'sesion_id' => $sesionId,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tabla de rate limiting del chatbot (separada de login_intentos)
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS chatbot_rate (
        ip        VARCHAR(45) NOT NULL,
        ventana   INT UNSIGNED NOT NULL,
        contador  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        PRIMARY KEY (ip, ventana)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$ventana  = (int) floor(time() / 60); // ventana de 1 minuto
$pdo->prepare(
    "INSERT INTO chatbot_rate (ip, ventana, contador) VALUES (?, ?, 1)
     ON DUPLICATE KEY UPDATE contador = contador + 1"
)->execute([$ip, $ventana]);

$rateStmt = $pdo->prepare("SELECT contador FROM chatbot_rate WHERE ip = ? AND ventana = ?");
$rateStmt->execute([$ip, $ventana]);
$contador = (int) ($rateStmt->fetchColumn() ?: 0);

// Limpiar ventanas antiguas (best-effort, no bloquea si falla)
try {
    $pdo->prepare("DELETE FROM chatbot_rate WHERE ventana < ?")->execute([$ventana - 2]);
} catch (Exception $e) { /* ignorar */ }

if ($contador > 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Demasiadas solicitudes. Espere un momento.']);
    exit;
}

// ── Leer y validar input ──────────────────────────────────────
$body = (string) file_get_contents('php://input');
$data = json_decode($body, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud inválida.']);
    exit;
}

$mensaje   = trim((string) ($data['mensaje']   ?? ''));
$sesionId  = trim((string) ($data['sesion_id'] ?? ''));

if ($mensaje === '' || mb_strlen($mensaje) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje inválido.']);
    exit;
}

// Validar o crear sesion_id: solo hex de 64 chars
if (!preg_match('/^[0-9a-f]{64}$/', $sesionId)) {
    $sesionId = bin2hex(random_bytes(32));
}

// ── Conversación ──────────────────────────────────────────────
$userAgent   = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$paginaOrigen = mb_substr((string) ($data['pagina'] ?? ''), 0, 255);

// Buscar o crear conversación
$convStmt = $pdo->prepare("SELECT id FROM chatbot_conversaciones WHERE sesion_id = ? LIMIT 1");
$convStmt->execute([$sesionId]);
$convId = $convStmt->fetchColumn();

if (!$convId) {
    $pdo->prepare(
        "INSERT INTO chatbot_conversaciones (sesion_id, ip, user_agent, pagina_origen)
         VALUES (?, ?, ?, ?)"
    )->execute([$sesionId, $ip, $userAgent, $paginaOrigen]);
    $convId = (int) $pdo->lastInsertId();
} else {
    $pdo->prepare(
        "UPDATE chatbot_conversaciones SET ultimo_mensaje = NOW() WHERE id = ?"
    )->execute([$convId]);
}

// ── Buscar respuesta ──────────────────────────────────────────
$entrada = buscarRespuesta($pdo, $mensaje);

// Guardar mensaje del usuario
$pdo->prepare(
    "INSERT INTO chatbot_mensajes (conversacion_id, tipo, mensaje, entrada_id)
     VALUES (?, 'usuario', ?, ?)"
)->execute([$convId, $mensaje, $entrada ? $entrada['id'] : null]);

// ── Construir respuesta ───────────────────────────────────────
if ($entrada) {
    $respuesta = $entrada['respuesta'];
    $botones   = [];

    if ($entrada['tiene_botones'] && $entrada['botones_json']) {
        $decoded = json_decode($entrada['botones_json'], true);
        if (is_array($decoded)) $botones = $decoded;
    }

    $fallback = false;
} else {
    $respuesta = 'No encontré información sobre eso. Puedes contactarnos directamente al ' . GS_TEL_1 . ' o por WhatsApp.';
    $botones   = [
        ['texto' => 'WhatsApp', 'url' => 'https://wa.me/' . GS_WHATSAPP],
        ['texto' => 'Contacto', 'url' => SITE_URL . '/contacto/'],
    ];
    $fallback = true;
}

// Guardar respuesta del bot
$pdo->prepare(
    "INSERT INTO chatbot_mensajes (conversacion_id, tipo, mensaje, entrada_id)
     VALUES (?, 'bot', ?, ?)"
)->execute([$convId, $respuesta, $entrada ? $entrada['id'] : null]);

echo json_encode([
    'respuesta'  => $respuesta,
    'botones'    => $botones,
    'fallback'   => $fallback,
    'sesion_id'  => $sesionId,
], JSON_UNESCAPED_UNICODE);
