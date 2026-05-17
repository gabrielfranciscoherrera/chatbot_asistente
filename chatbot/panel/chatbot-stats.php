<?php
declare(strict_types=1);
require_once dirname(__FILE__, 3) . '/private/config.php';
require_once PRIVATE_PATH . '/includes/db.php';
require_once PRIVATE_PATH . '/includes/auth.php';

authRequerida();

$pdo = db();

// Top 10 entradas más activadas
$topEntradas = $pdo->query(
    "SELECT e.pregunta_tipo, e.categoria, COUNT(m.id) AS veces
     FROM chatbot_mensajes m
     JOIN chatbot_entradas e ON e.id = m.entrada_id
     WHERE m.tipo = 'bot'
     GROUP BY m.entrada_id
     ORDER BY veces DESC
     LIMIT 10"
)->fetchAll();

// Top 10 mensajes de usuario sin respuesta (fallback)
$topFallbacks = $pdo->query(
    "SELECT mensaje, COUNT(*) AS veces
     FROM chatbot_mensajes
     WHERE tipo = 'usuario' AND entrada_id IS NULL
     GROUP BY mensaje
     ORDER BY veces DESC
     LIMIT 10"
)->fetchAll();

// Total conversaciones últimos 30 días
$stats30 = $pdo->query(
    "SELECT COUNT(*) FROM chatbot_conversaciones WHERE inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetchColumn();

// Total mensajes últimos 30 días
$msgs30 = $pdo->query(
    "SELECT COUNT(*) FROM chatbot_mensajes m
     JOIN chatbot_conversaciones c ON c.id = m.conversacion_id
     WHERE c.inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetchColumn();

$fallbacks30 = $pdo->query(
    "SELECT COUNT(*) FROM chatbot_mensajes m
     JOIN chatbot_conversaciones c ON c.id = m.conversacion_id
     WHERE m.tipo = 'usuario' AND m.entrada_id IS NULL
       AND c.inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estadísticas chatbot | Panel GS</title>
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
        <h1 class="panel-page-title">Estadísticas del chatbot</h1>
        <span class="panel-user">👤 <?= htmlspecialchars($_SESSION['admin_usuario'], ENT_QUOTES, 'UTF-8') ?></span>
    </header>

    <div class="panel-stats-grid" style="margin-bottom:2rem">
        <div class="stat-card">
            <span class="stat-card__num"><?= (int) $stats30 ?></span>
            <span class="stat-card__label">Conversaciones (30 días)</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__num"><?= (int) $msgs30 ?></span>
            <span class="stat-card__label">Mensajes (30 días)</span>
        </div>
        <div class="stat-card <?= (int)$fallbacks30 > 0 ? 'stat-card--alert' : '' ?>">
            <span class="stat-card__num"><?= (int) $fallbacks30 ?></span>
            <span class="stat-card__label">Sin respuesta (30 días)</span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

        <section class="panel-section">
            <h2 class="panel-section__title">Top 10 entradas activadas</h2>
            <?php if ($topEntradas): ?>
            <div class="panel-table-wrap">
<table class="panel-table">
                <thead>
                    <tr><th>Entrada</th><th>Categoría</th><th style="text-align:right">Veces</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($topEntradas as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['pregunta_tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge badge--yellow"><?= htmlspecialchars($t['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="text-align:right;font-weight:700;color:var(--panel-accent)"><?= (int) $t['veces'] ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
</div>
            <?php else: ?>
            <p style="color:var(--panel-muted);padding:1rem 0">Sin datos aún.</p>
            <?php endif ?>
        </section>

        <section class="panel-section">
            <h2 class="panel-section__title">Top 10 mensajes sin respuesta</h2>
            <?php if ($topFallbacks): ?>
            <div class="panel-table-wrap">
<table class="panel-table">
                <thead>
                    <tr><th>Mensaje del usuario</th><th style="text-align:right">Veces</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($topFallbacks as $f): ?>
                    <tr>
                        <td style="font-size:.85rem"><?= htmlspecialchars(mb_substr($f['mensaje'], 0, 60), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($f['mensaje']) > 60 ? '…' : '' ?></td>
                        <td style="text-align:right;font-weight:700;color:#ff6b70"><?= (int) $f['veces'] ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
</div>
            <p style="font-size:.75rem;color:var(--panel-muted);margin-top:.75rem">
                Use estos mensajes para agregar nuevas entradas al diccionario.
            </p>
            <?php else: ?>
            <p style="color:var(--panel-muted);padding:1rem 0">Sin fallbacks registrados. ¡Excelente!</p>
            <?php endif ?>
        </section>

    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/panel.js"></script>
</body>
</html>
