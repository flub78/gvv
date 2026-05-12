<?php
/**
 * GVV — Réinitialisation de la base de données
 * Bootstrap 5, PHP pur (sans CodeIgniter)
 */
define('ROOT',      realpath(dirname(__DIR__)));
define('CFG_DIR',   ROOT . '/application/config');
define('LOCK_FILE', __DIR__ . '/installed.lock');

// ── Verrou d'installation ──────────────────────────────────────────────────
if (file_exists(LOCK_FILE)) {
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GVV — Réinitialisation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>body{background:#f0f4f8}.card{border:none;border-radius:1rem}</style>
</head>
<body>
<nav class="navbar navbar-dark py-3 mb-4" style="background:linear-gradient(90deg,#7b1fa2,#c2185b)">
  <div class="container">
    <span class="navbar-brand fw-bold fs-4"><i class="fas fa-plane me-2"></i>GVV — Réinitialisation</span>
  </div>
</nav>
<div class="container" style="max-width:620px">
  <div class="card shadow-sm p-4 text-center">
    <div class="text-danger mb-3" style="font-size:3rem"><i class="fas fa-lock"></i></div>
    <h2 class="fw-bold mb-2">Réinitialisation désactivée</h2>
    <p class="text-muted mb-4">
      Le fichier <code>install/installed.lock</code> est présent.<br>
      La réinitialisation de la base est bloquée pour protéger les données.
    </p>
    <p class="text-muted small">
      Pour autoriser une réinitialisation, supprimez d'abord le fichier
      <code>install/installed.lock</code> depuis le serveur.
    </p>
  </div>
</div>
</body></html>
    <?php
    exit;
}

// ── Lecture des paramètres DB depuis database.php ──────────────────────────

function db_read(string $key, string $default = ''): string {
    $file = CFG_DIR . '/database.php';
    if (!file_exists($file)) return $default;
    $c = file_get_contents($file);
    // $db['default']['KEY'] = 'VALUE';
    if (preg_match('/\$db\[\'default\'\]\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/', $c, $m))
        return stripslashes($m[1]);
    if (preg_match('/\$db\[\'default\'\]\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*"((?:[^"\\\\]|\\\\.)*)"\s*;/', $c, $m))
        return stripslashes($m[1]);
    return $default;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

// ── Détection URL du site ──────────────────────────────────────────────────

function detect_install_url(): string {
    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir    = dirname($script);  // /install
    return $proto . '://' . $host . $dir;
}

// ── État ───────────────────────────────────────────────────────────────────

$db_cfg_exists = file_exists(CFG_DIR . '/database.php');
$hostname = db_read('hostname', 'localhost');
$username = db_read('username', '');
$password = db_read('password', '');
$database = db_read('database', '');

$errors   = [];
$success  = false;
$tables_dropped = [];
$db_error = '';

// ── POST : exécuter la réinitialisation ────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = trim($_POST['confirm'] ?? '');

    if ($confirm !== 'RESET') {
        $errors[] = 'Vous devez saisir exactement le mot <strong>RESET</strong> pour confirmer.';
    } elseif (!$db_cfg_exists) {
        $errors[] = 'Fichier <code>application/config/database.php</code> introuvable. Lancez d\'abord l\'assistant d\'installation.';
    } else {
        $db = @mysqli_connect($hostname, $username, $password, $database);
        if (!$db) {
            $errors[] = 'Impossible de se connecter à la base de données : ' . mysqli_connect_error();
        } else {
            // Désactiver les contraintes FK
            mysqli_query($db, 'SET FOREIGN_KEY_CHECKS=0');

            // Lister les tables
            $res = mysqli_query($db, "SHOW TABLES FROM `" . mysqli_real_escape_string($db, $database) . "`");
            $tables = [];
            if ($res) {
                while ($row = mysqli_fetch_row($res)) {
                    $tables[] = $row[0];
                }
            }

            // Supprimer chaque table
            foreach ($tables as $tbl) {
                $ok = mysqli_query($db, "DROP TABLE IF EXISTS `" . $tbl . "`");
                $tables_dropped[] = ['name' => $tbl, 'ok' => ($ok !== false)];
            }

            mysqli_query($db, 'SET FOREIGN_KEY_CHECKS=1');
            mysqli_close($db);
            $success = true;
        }
    }
}

// ── Lister les tables existantes (pour affichage avant confirmation) ───────

$existing_tables = [];
if ($db_cfg_exists && !$success) {
    $db_preview = @mysqli_connect($hostname, $username, $password, $database);
    if ($db_preview) {
        $res = mysqli_query($db_preview, "SHOW TABLES FROM `" . mysqli_real_escape_string($db_preview, $database) . "`");
        if ($res) {
            while ($row = mysqli_fetch_row($res)) $existing_tables[] = $row[0];
        }
        mysqli_close($db_preview);
    }
}

$install_url = detect_install_url() . '/index.php';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GVV — Réinitialisation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f0f4f8; }
.card { border: none; border-radius: 1rem; }
.step-title { font-size: 1.3rem; font-weight: 700; color: #212529; }
.table-list { column-count: 3; column-gap: 1rem; font-size: .85rem; }
@media (max-width: 576px) { .table-list { column-count: 1; } }
</style>
</head>
<body>

<nav class="navbar navbar-dark py-3 mb-4" style="background:linear-gradient(90deg,#7b1fa2,#c2185b)">
  <div class="container">
    <span class="navbar-brand fw-bold fs-4"><i class="fas fa-plane me-2"></i>GVV — Réinitialisation</span>
    <a href="<?= h($install_url) ?>" class="btn btn-outline-light btn-sm">
      <i class="fas fa-tools me-1"></i>Assistant d'installation
    </a>
  </div>
</nav>

<div class="container" style="max-width:720px">

<?php if ($success): ?>

  <!-- ── Succès ── -->
  <div class="card shadow-sm mb-4 p-4">
    <div class="text-center mb-4">
      <div class="text-success mb-3" style="font-size:3rem"><i class="fas fa-check-circle"></i></div>
      <h2 class="step-title">Base de données réinitialisée</h2>
      <p class="text-muted">Toutes les tables ont été supprimées.</p>
    </div>

    <?php $ok_count = count(array_filter($tables_dropped, fn($t) => $t['ok']));
          $err_count = count($tables_dropped) - $ok_count; ?>

    <div class="alert alert-success">
      <i class="fas fa-check me-2"></i><?= $ok_count ?> table(s) supprimée(s) avec succès.
      <?php if ($err_count > 0): ?>
        <br><i class="fas fa-exclamation-triangle me-2 text-warning"></i><?= $err_count ?> échec(s).
      <?php endif; ?>
    </div>

    <?php if ($tables_dropped): ?>
    <details class="mb-3">
      <summary class="text-muted small" style="cursor:pointer">Détail des tables</summary>
      <div class="table-list mt-2">
        <?php foreach ($tables_dropped as $t): ?>
          <div>
            <?php if ($t['ok']): ?>
              <i class="fas fa-check text-success me-1"></i>
            <?php else: ?>
              <i class="fas fa-times text-danger me-1"></i>
            <?php endif; ?>
            <code><?= h($t['name']) ?></code>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="<?= h($install_url) ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-tools me-2"></i>Relancer l'installation
      </a>
    </div>
  </div>

<?php else: ?>

  <!-- ── Erreurs ── -->
  <?php if ($errors): ?>
  <div class="alert alert-danger mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php if (count($errors) === 1): ?>
      <?= $errors[0] ?>
    <?php else: ?>
      <ul class="mb-0 mt-1"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── Formulaire de confirmation ── -->
  <div class="card shadow-sm mb-4 p-4">

    <div class="text-center mb-4">
      <div class="text-danger mb-3" style="font-size:3rem"><i class="fas fa-exclamation-triangle"></i></div>
      <h2 class="step-title">Réinitialisation de la base de données</h2>
    </div>

    <div class="alert alert-danger">
      <strong><i class="fas fa-skull-crossbones me-2"></i>Action irréversible</strong><br>
      Ce script va <strong>supprimer toutes les tables</strong> de la base de données
      <code><?= h($database ?: '(non configurée)') ?></code>.
      Toutes les données seront définitivement perdues.
    </div>

    <?php if (!$db_cfg_exists): ?>
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-circle me-2"></i>
      Fichier <code>application/config/database.php</code> introuvable.
      Lancez d'abord <a href="<?= h($install_url) ?>">l'assistant d'installation</a>.
    </div>
    <?php else: ?>

    <!-- Paramètres DB -->
    <div class="mb-4">
      <h6 class="text-muted fw-semibold mb-2"><i class="fas fa-database me-1"></i>Base de données cible</h6>
      <table class="table table-sm table-bordered mb-0">
        <tr><th class="table-light" style="width:140px">Serveur</th><td><code><?= h($hostname) ?></code></td></tr>
        <tr><th class="table-light">Utilisateur</th><td><code><?= h($username) ?></code></td></tr>
        <tr><th class="table-light">Base</th><td><code><?= h($database) ?></code></td></tr>
      </table>
    </div>

    <!-- Tables existantes -->
    <?php if ($existing_tables): ?>
    <div class="mb-4">
      <h6 class="text-muted fw-semibold mb-2">
        <i class="fas fa-table me-1"></i><?= count($existing_tables) ?> table(s) qui seront supprimées
      </h6>
      <div class="table-list bg-light border rounded p-3">
        <?php foreach ($existing_tables as $tbl): ?>
          <div><code><?= h($tbl) ?></code></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif (empty($existing_tables)): ?>
    <div class="alert alert-info mb-4">
      <i class="fas fa-info-circle me-2"></i>
      La base de données est déjà vide (aucune table trouvée).
    </div>
    <?php endif; ?>

    <!-- Formulaire confirmation -->
    <form method="post">
      <div class="mb-3">
        <label class="form-label fw-semibold">
          Saisissez <code>RESET</code> pour confirmer la suppression :
        </label>
        <input type="text" name="confirm" class="form-control form-control-lg <?= $errors ? 'is-invalid' : '' ?>"
               placeholder="RESET" autocomplete="off" autofocus>
        <?php if ($errors): ?>
          <div class="invalid-feedback">Vous devez saisir exactement <strong>RESET</strong>.</div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger btn-lg flex-grow-1">
          <i class="fas fa-trash-alt me-2"></i>Réinitialiser la base de données
        </button>
        <a href="<?= h($install_url) ?>" class="btn btn-outline-secondary btn-lg">
          <i class="fas fa-times me-1"></i>Annuler
        </a>
      </div>
    </form>

    <?php endif; // db_cfg_exists ?>
  </div>

<?php endif; // success ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
