<?php
declare(strict_types=1);
require_once dirname(__FILE__, 3) . '/private/config.php';
require_once PRIVATE_PATH . '/includes/db.php';
require_once PRIVATE_PATH . '/includes/auth.php';

authRequerida();

$pdo = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    csrfValidar($_POST['csrf'] ?? '');
    $id = (int) $_POST['toggle_id'];
    $pdo->prepare("UPDATE chatbot_entradas SET activo = NOT activo WHERE id = ?")->execute([$id]);
    $msg = 'Estado actualizado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrfValidar($_POST['csrf'] ?? '');
    $id = (int) $_POST['delete_id'];
    $pdo->prepare("DELETE FROM chatbot_entradas WHERE id = ?")->execute([$id]);
    $msg = 'Entrada eliminada.';
}

$filtroCategoria = $_GET['cat'] ?? '';
$categorias = ['servicios', 'contacto', 'proyectos', 'precios', 'general'];

if ($filtroCategoria !== '' && in_array($filtroCategoria, $categorias, true)) {
    $stmt = $pdo->prepare(
        "SELECT * FROM chatbot_entradas WHERE categoria = ? ORDER BY prioridad ASC, id ASC"
    );
    $stmt->execute([$filtroCategoria]);
    $entradas = $stmt->fetchAll();
} else {
    $filtroCategoria = '';
    $entradas = $pdo->query(
        "SELECT * FROM chatbot_entradas ORDER BY categoria ASC, prioridad ASC, id ASC"
    )->fetchAll();
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diccionario chatbot | Panel GS</title>
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
        <h1 class="panel-page-title">Diccionario del chatbot</h1>
        <span class="panel-user">👤 <?= htmlspecialchars($_SESSION['admin_usuario'], ENT_QUOTES, 'UTF-8') ?></span>
    </header>

    <?php if ($msg): ?>
    <div class="panel-alert panel-alert--success"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif ?>

    <div class="panel-actions">
        <a href="chatbot-form.php" class="btn btn--primary">+ Nueva entrada</a>
        <span style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <a href="chatbot.php" class="btn btn--ghost btn-sm <?= $filtroCategoria === '' ? 'active' : '' ?>">Todas</a>
            <?php foreach ($categorias as $cat): ?>
            <a href="chatbot.php?cat=<?= $cat ?>" class="btn btn--ghost btn-sm <?= $filtroCategoria === $cat ? 'active' : '' ?>">
                <?= htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach ?>
        </span>
    </div>

    <section class="panel-section">
        <div class="panel-table-wrap">
<table class="panel-table">
            <thead>
                <tr>
                    <th>Etiqueta interna</th>
                    <th>Categoría</th>
                    <th>Palabras clave</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entradas as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['pregunta_tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge--yellow"><?= htmlspecialchars($e['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:.8rem;color:var(--panel-muted);max-width:220px;word-break:break-word">
                        <?= htmlspecialchars(mb_substr($e['palabras_clave'], 0, 80), ENT_QUOTES, 'UTF-8') ?>
                        <?= mb_strlen($e['palabras_clave']) > 80 ? '…' : '' ?>
                    </td>
                    <td style="text-align:center"><?= (int) $e['prioridad'] ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="toggle_id" value="<?= $e['id'] ?>">
                            <label class="toggle">
                                <input type="checkbox" onchange="this.form.submit()" <?= $e['activo'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </form>
                    </td>
                    <td>
                        <a href="chatbot-form.php?id=<?= $e['id'] ?>" class="btn btn--ghost btn-sm">Editar</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta entrada?')">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                            <button type="submit" class="btn btn--danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php if (!$entradas): ?>
                <tr><td colspan="6" style="color:var(--panel-muted);text-align:center;padding:2rem">Sin entradas.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
</div>
    </section>
</main>

<style>
.btn.active { background: rgba(38,224,137,.15); color: var(--panel-accent); }
</style>

<script src="<?= SITE_URL ?>/assets/js/panel.js"></script>
</body>
</html>
