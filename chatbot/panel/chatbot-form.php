<?php
declare(strict_types=1);
require_once dirname(__FILE__, 3) . '/private/config.php';
require_once PRIVATE_PATH . '/includes/db.php';
require_once PRIVATE_PATH . '/includes/auth.php';

authRequerida();

$pdo = db();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$msg = '';
$err = '';

$categorias = ['servicios', 'contacto', 'proyectos', 'precios', 'general'];

$entrada = [
    'pregunta_tipo'  => '',
    'categoria'      => 'general',
    'palabras_clave' => '',
    'respuesta'      => '',
    'tiene_botones'  => 0,
    'botones_json'   => '',
    'prioridad'      => 5,
    'activo'         => 1,
];

if ($id > 0) {
    $row = $pdo->prepare("SELECT * FROM chatbot_entradas WHERE id = ? LIMIT 1");
    $row->execute([$id]);
    $found = $row->fetch();
    if (!$found) { header('Location: chatbot.php'); exit; }
    $entrada = $found;
    // botones_json viene como string JSON desde MySQL
    if ($entrada['botones_json'] === null) $entrada['botones_json'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidar($_POST['csrf'] ?? '');

    $pregunta_tipo  = trim($_POST['pregunta_tipo']  ?? '');
    $categoria      = $_POST['categoria']           ?? 'general';
    $palabras_clave = trim($_POST['palabras_clave'] ?? '');
    $respuesta      = trim($_POST['respuesta']      ?? '');
    $tiene_botones  = isset($_POST['tiene_botones']) ? 1 : 0;
    $botones_raw    = trim($_POST['botones_json']   ?? '');
    $prioridad      = max(1, min(10, (int) ($_POST['prioridad'] ?? 5)));
    $activo         = isset($_POST['activo']) ? 1 : 0;

    if (!in_array($categoria, $categorias, true)) $categoria = 'general';

    $botones_json = null;
    if ($tiene_botones && $botones_raw !== '') {
        $decoded = json_decode($botones_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $err = 'El JSON de botones no es válido: ' . json_last_error_msg();
        } else {
            $botones_json = $botones_raw;
        }
    }

    if ($err === '') {
        if ($pregunta_tipo === '' || $palabras_clave === '' || $respuesta === '') {
            $err = 'Etiqueta, palabras clave y respuesta son obligatorios.';
        } else {
            if ($id > 0) {
                $pdo->prepare(
                    "UPDATE chatbot_entradas
                     SET pregunta_tipo=?, categoria=?, palabras_clave=?, respuesta=?,
                         tiene_botones=?, botones_json=?, prioridad=?, activo=?
                     WHERE id=?"
                )->execute([
                    $pregunta_tipo, $categoria, $palabras_clave, $respuesta,
                    $tiene_botones, $botones_json, $prioridad, $activo, $id,
                ]);
            } else {
                $pdo->prepare(
                    "INSERT INTO chatbot_entradas
                     (pregunta_tipo, categoria, palabras_clave, respuesta, tiene_botones, botones_json, prioridad, activo)
                     VALUES (?,?,?,?,?,?,?,?)"
                )->execute([
                    $pregunta_tipo, $categoria, $palabras_clave, $respuesta,
                    $tiene_botones, $botones_json, $prioridad, $activo,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            $msg = 'Entrada guardada.';
            $row = $pdo->prepare("SELECT * FROM chatbot_entradas WHERE id = ? LIMIT 1");
            $row->execute([$id]);
            $entrada = $row->fetch();
            if ($entrada['botones_json'] === null) $entrada['botones_json'] = '';
        }
    } else {
        // Preservar lo que el usuario escribió para no perderlo
        $entrada = array_merge($entrada, [
            'pregunta_tipo'  => $pregunta_tipo,
            'categoria'      => $categoria,
            'palabras_clave' => $palabras_clave,
            'respuesta'      => $respuesta,
            'tiene_botones'  => $tiene_botones,
            'botones_json'   => $botones_raw,
            'prioridad'      => $prioridad,
            'activo'         => $activo,
        ]);
    }
}

$csrf = csrfToken();
$titulo_pagina = $id > 0 ? 'Editar entrada' : 'Nueva entrada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8') ?> | Panel GS</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/panel.min.css">
</head>
<body class="panel-body">

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="panel-main">
    <header class="panel-topbar">
        <button id="panel-hamburger" class="panel-hamburger" aria-label="Abrir menú" aria-controls="panel-sidebar" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <h1 class="panel-page-title"><?= htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="chatbot.php" class="btn btn--ghost btn-sm">← Volver</a>
    </header>

    <?php if ($msg): ?>
    <div class="panel-alert panel-alert--success"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif ?>
    <?php if ($err): ?>
    <div class="panel-alert panel-alert--error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif ?>

    <section class="panel-section">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <div class="panel-form-grid">
                <div class="form-group">
                    <label class="form-label" for="pregunta_tipo">Etiqueta interna *</label>
                    <input class="form-control" type="text" id="pregunta_tipo" name="pregunta_tipo"
                           value="<?= htmlspecialchars($entrada['pregunta_tipo'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej: precio pisos epóxicos" required>
                    <small style="color:var(--panel-muted);font-size:.75rem">Solo para identificación interna, no visible al usuario.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="categoria">Categoría</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat ?>" <?= $entrada['categoria'] === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="palabras_clave">Palabras clave (separadas por coma) *</label>
                    <input class="form-control" type="text" id="palabras_clave" name="palabras_clave"
                           value="<?= htmlspecialchars($entrada['palabras_clave'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="precio,costo,cuánto cuesta,valor" required>
                    <small style="color:var(--panel-muted);font-size:.75rem">El chatbot busca cada palabra como coincidencia de palabra completa en el mensaje del usuario.</small>
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="respuesta">Respuesta *</label>
                    <textarea class="form-control" id="respuesta" name="respuesta" rows="6" required><?= htmlspecialchars($entrada['respuesta'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prioridad">Prioridad (1=alta · 10=baja)</label>
                    <input class="form-control" type="number" id="prioridad" name="prioridad"
                           min="1" max="10" value="<?= (int) $entrada['prioridad'] ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;margin-top:.25rem">
                        <label class="toggle">
                            <input type="checkbox" name="activo" <?= $entrada['activo'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="font-size:.875rem">Activo</span>
                    </label>
                </div>

                <div class="form-group form-group--full">
                    <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;margin-bottom:.75rem">
                        <label class="toggle">
                            <input type="checkbox" name="tiene_botones" id="tiene_botones"
                                   onchange="document.getElementById('botones_wrap').style.display=this.checked?'block':'none'"
                                   <?= $entrada['tiene_botones'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="form-label" style="margin:0">¿Incluir botones de acción?</span>
                    </label>

                    <div id="botones_wrap" style="<?= $entrada['tiene_botones'] ? '' : 'display:none' ?>">
                        <label class="form-label" for="botones_json">JSON de botones</label>
                        <textarea class="form-control" id="botones_json" name="botones_json" rows="5"
                                  style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($entrada['botones_json'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small style="color:var(--panel-muted);font-size:.75rem">
                            Formato: <code>[{"texto":"Ver servicios","url":"/servicios/"},{"texto":"Contactar","url":"/contacto/"}]</code>
                        </small>
                    </div>
                </div>
            </div>

            <div style="margin-top:1.5rem">
                <button type="submit" class="btn btn--primary">Guardar entrada</button>
            </div>
        </form>
    </section>
</main>

<script src="<?= SITE_URL ?>/assets/js/panel.js"></script>
</body>
</html>
