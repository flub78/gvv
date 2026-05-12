<?php
/**
 * GVV — Assistant d'installation
 * Bootstrap 5, multi-étapes, PHP pur (sans CodeIgniter)
 */
session_start();

define('ROOT',       realpath(dirname(__DIR__)));
define('CFG_DIR',    ROOT . '/application/config');
define('INSTALL_DIR',__DIR__);
define('LOCK_FILE',  __DIR__ . '/installed.lock');

// ── Verrou d'installation ──────────────────────────────────────────────────
if (file_exists(LOCK_FILE)) {
    $app_url = '';
    $cfg = CFG_DIR . '/config.php';
    if (file_exists($cfg)) {
        $c = file_get_contents($cfg);
        if (preg_match('/\$config\[\'base_url\'\]\s*=\s*\'([^\']+)\'\s*;/', $c, $m))
            $app_url = $m[1];
    }
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GVV — Déjà installé</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>body{background:#f0f4f8}.card{border:none;border-radius:1rem}</style>
</head>
<body>
<nav class="navbar navbar-dark py-3 mb-4" style="background:linear-gradient(90deg,#1a237e,#1565c0)">
  <div class="container">
    <span class="navbar-brand fw-bold fs-4"><i class="fas fa-plane me-2"></i>GVV — Installation</span>
  </div>
</nav>
<div class="container" style="max-width:620px">
  <div class="card shadow-sm p-4 text-center">
    <div class="text-success mb-3" style="font-size:3rem"><i class="fas fa-lock"></i></div>
    <h2 class="fw-bold mb-2">Application déjà installée</h2>
    <p class="text-muted mb-4">
      Le fichier <code>install/installed.lock</code> est présent.<br>
      L'assistant d'installation est désactivé pour protéger la configuration.
    </p>
    <?php if ($app_url): ?>
    <a href="<?= htmlspecialchars($app_url, ENT_QUOTES) ?>" class="btn btn-success btn-lg mb-3">
      <i class="fas fa-plane me-2"></i>Accéder à GVV
    </a>
    <?php endif; ?>
    <p class="text-muted small">
      Pour relancer l'installation, supprimez le fichier <code>install/installed.lock</code> depuis le serveur.
    </p>
  </div>
</div>
</body></html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════
// Fonctions utilitaires — config files
// ═══════════════════════════════════════════════════════════

/** Lit une valeur depuis database.php ($db['default']['key']) */
function db_cfg_read(string $key, string $default = ''): string {
    $file = CFG_DIR . '/database.php';
    if (!file_exists($file)) {
        $file = CFG_DIR . '/database.example.php';
    }
    if (!file_exists($file)) return $default;
    $c = file_get_contents($file);
    $pat = '/\$db\[\'default\'\]\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/';
    if (preg_match($pat, $c, $m)) return stripslashes($m[1]);
    $pat2 = '/\$db\[\'default\'\]\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*"((?:[^"\\\\]|\\\\.)*)"\s*;/';
    if (preg_match($pat2, $c, $m)) return stripslashes($m[1]);
    return $default;
}

/** Écrit une valeur dans database.php ($db['default']['key']) */
function db_cfg_write(string $file, string $key, string $value): bool {
    if (!file_exists($file)) return false;
    $c       = file_get_contents($file);
    $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    $new     = "\$db['default']['" . $key . "'] = '" . $escaped . "';";
    $pat = '/\$db\[\'default\'\]\[\'' . preg_quote($key, '/') . '\'\]\s*=[^;]+;/';
    $out = preg_replace($pat, $new, $c, 1, $n);
    if ($n === 0) $out = rtrim($c) . "\n" . $new . "\n";
    return file_put_contents($file, $out) !== false;
}

/** Lit une valeur string depuis un fichier de config PHP */
function cfg_read(string $file, string $key, ?string $default = null): ?string {
    if (!file_exists($file)) return $default;
    $c = file_get_contents($file);
    // single-quoted — ancré début de ligne pour ignorer les commentaires (# $config[...])
    if (preg_match('/^\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/m', $c, $m))
        return stripslashes($m[1]);
    // double-quoted
    if (preg_match('/^\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*"((?:[^"\\\\]|\\\\.)*)"\s*;/m', $c, $m))
        return stripslashes($m[1]);
    return $default;
}

/** Lit un booléen depuis un fichier de config PHP */
function cfg_read_bool(string $file, string $key, bool $default = false): bool {
    if (!file_exists($file)) return $default;
    $c = file_get_contents($file);
    if (preg_match('/\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*(true|false|TRUE|FALSE)\s*;/', $c, $m))
        return strtolower($m[1]) === 'true';
    return $default;
}

/** Écrit / remplace une valeur string dans un fichier de config PHP */
function cfg_write(string $file, string $key, string $value): bool {
    if (!file_exists($file)) return false;
    $c       = file_get_contents($file);
    $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    $new     = "\$config['" . $key . "'] = '" . $escaped . "';";
    // Regex qui délimite les strings quotées pour éviter les faux ;
    // Reconnaît : 'val', "val", true/false, entier
    $pat = '/^\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*'
         . "(?:'(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\"|[^;]*)"
         . ';/m';
    $out = preg_replace($pat, $new, $c, 1, $n);
    if ($n === 0) $out = rtrim($c) . "\n" . $new . "\n";
    return file_put_contents($file, $out) !== false;
}

/** Écrit / remplace un booléen dans un fichier de config PHP */
function cfg_write_bool(string $file, string $key, bool $value): bool {
    if (!file_exists($file)) return false;
    $c   = file_get_contents($file);
    $new = "\$config['" . $key . "'] = " . ($value ? 'true' : 'false') . ";";
    $pat = '/^\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*'
         . "(?:'(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\"|[^;]*)"
         . ';/m';
    $out = preg_replace($pat, $new, $c, 1, $n);
    if ($n === 0) $out = rtrim($c) . "\n" . $new . "\n";
    return file_put_contents($file, $out) !== false;
}

/** Copie le .example.php vers .php s'il n'existe pas encore */
function ensure_example(string $basename): void {
    $src  = CFG_DIR . '/' . $basename . '.example.php';
    $dest = CFG_DIR . '/' . $basename . '.php';
    if (!file_exists($dest) && file_exists($src))
        copy($src, $dest);
}

/** Lit en priorité le fichier de prod, sinon l'exemple */
function pre_read(string $basename, string $key, string $default = ''): string {
    $prod    = CFG_DIR . '/' . $basename . '.php';
    $example = CFG_DIR . '/' . $basename . '.example.php';
    $v = cfg_read($prod, $key, null);
    if ($v === null) $v = cfg_read($example, $key, $default);
    return (string)$v;
}

function pre_read_bool(string $basename, string $key, bool $default = false): bool {
    $prod    = CFG_DIR . '/' . $basename . '.php';
    $example = CFG_DIR . '/' . $basename . '.example.php';
    if (file_exists($prod)) return cfg_read_bool($prod, $key, $default);
    return cfg_read_bool($example, $key, $default);
}

// ═══════════════════════════════════════════════════════════
// Fonctions utilitaires — système
// ═══════════════════════════════════════════════════════════

function dx_hash_password(string $password): string {
    $majorsalt = '';
    for ($i = 0; $i < strlen($password); $i++)
        $majorsalt .= md5($password[$i]);
    $salt = '$1$' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . '$';
    return crypt(md5($majorsalt), $salt);
}

function test_db(string $h, string $u, string $p, string $d): array {
    $c = @mysqli_connect($h, $u, $p, $d);
    if (!$c) return ['ok' => false, 'error' => mysqli_connect_error()];
    mysqli_close($c);
    return ['ok' => true];
}

function mysql_import_file(string $filename, \mysqli $db): array {
    $errors = [];
    $q = '';
    foreach (file($filename) as $line) {
        $t = trim($line);
        if ($t === '' || strncmp($t, '--', 2) === 0) continue;
        $q .= $line;
        if (substr(rtrim($q), -1) === ';') {
            if (!mysqli_query($db, $q))
                $errors[] = mysqli_error($db);
            $q = '';
        }
    }
    return $errors;
}

function ensure_dir(string $path, int $mode = 0775): bool {
    if (!is_dir($path)) return @mkdir($path, $mode, true);
    return true;
}

function detect_base_url(): string {
    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/install/index.php';
    $parts  = explode('/', $script);
    array_pop($parts); // index.php
    array_pop($parts); // install
    return $proto . '://' . $host . implode('/', $parts) . '/';
}

function db_from_session(): array {
    return [
        'host' => $_SESSION['install']['db_host'] ?? db_cfg_read('hostname', 'localhost'),
        'user' => $_SESSION['install']['db_user'] ?? db_cfg_read('username', ''),
        'pass' => $_SESSION['install']['db_pass'] ?? db_cfg_read('password', ''),
        'name' => $_SESSION['install']['db_name'] ?? db_cfg_read('database', ''),
    ];
}

// ═══════════════════════════════════════════════════════════
// État de la session
// ═══════════════════════════════════════════════════════════
if (!isset($_SESSION['install'])) $_SESSION['install'] = [];

$STEPS = [
    1  => ['icon' => 'fa-check-circle',  'label' => 'Prérequis'],
    2  => ['icon' => 'fa-database',      'label' => 'Base de données'],
    3  => ['icon' => 'fa-globe',         'label' => 'URL'],
    4  => ['icon' => 'fa-building',      'label' => 'Club'],
    5  => ['icon' => 'fa-lock',          'label' => 'Authentification'],
    6  => ['icon' => 'fa-sliders',       'label' => 'Fonctionnalités'],
    7  => ['icon' => 'fab fa-google',    'label' => 'Google'],
    8  => ['icon' => 'fa-table',         'label' => 'Base — init'],
    9  => ['icon' => 'fa-folder',        'label' => 'Répertoires'],
    10 => ['icon' => 'fa-flag-checkered','label' => 'Terminé'],
];

$step = isset($_SESSION['install']['step']) ? (int)$_SESSION['install']['step'] : 1;
if (isset($_GET['step'])) $step = (int)$_GET['step'];
$step = max(1, min(10, $step));

$errors  = [];
$notices = [];

// ═══════════════════════════════════════════════════════════
// Traitement POST
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = (int)($_POST['posted_step'] ?? 1);
    $action = $_POST['action'] ?? 'next';

    if ($action === 'prev') {
        $step = max(1, $posted - 1);
    } else {
        switch ($posted) {

            // ── Étape 1 : pas de validation, juste avancer
            case 1:
                $step = 2;
                break;

            // ── Étape 2 : base de données
            case 2:
                $h = trim($_POST['db_host'] ?? 'localhost');
                $u = trim($_POST['db_user'] ?? '');
                $p = trim($_POST['db_pass'] ?? '');
                $d = trim($_POST['db_name'] ?? '');
                if (!$h || !$u || !$d) {
                    $errors[] = 'Hôte, utilisateur et nom de base sont requis.';
                } else {
                    $r = test_db($h, $u, $p, $d);
                    if (!$r['ok']) {
                        $errors[] = 'Connexion impossible : ' . htmlspecialchars($r['error']);
                    } else {
                        ensure_example('database');
                        $f = CFG_DIR . '/database.php';
                        db_cfg_write($f, 'hostname', $h);
                        db_cfg_write($f, 'username', $u);
                        db_cfg_write($f, 'password', $p);
                        db_cfg_write($f, 'database', $d);
                        $_SESSION['install'] = array_merge($_SESSION['install'],
                            ['db_host'=>$h,'db_user'=>$u,'db_pass'=>$p,'db_name'=>$d]);
                        $step = 3;
                    }
                }
                break;

            // ── Étape 3 : URL / config.php
            case 3:
                $base_url = trim($_POST['base_url'] ?? '');
                $language = trim($_POST['language'] ?? 'french');
                if (!$base_url) {
                    $errors[] = 'L\'URL de base est requise.';
                } else {
                    if (substr($base_url, -1) !== '/') $base_url .= '/';
                    ensure_example('config');
                    $f = CFG_DIR . '/config.php';
                    // Remplace le bloc auto-detect ou la valeur simple
                    $c = file_get_contents($f);
                    // Supprimer le bloc if/else auto-detect s'il existe
                    $c = preg_replace('/\/\/ Auto-detect base URL.*?}\s*else\s*\{[^}]*\}\s*/s', '', $c);
                    file_put_contents($f, $c);
                    cfg_write($f, 'base_url', $base_url);
                    cfg_write($f, 'index_page', '');
                    cfg_write($f, 'uri_protocol', 'REQUEST_URI');
                    cfg_write($f, 'language', $language);
                    // Copie point.htaccess → .htaccess si point.htaccess existe
                    $src_ht  = ROOT . '/point.htaccess';
                    $dest_ht = ROOT . '/.htaccess';
                    if (file_exists($src_ht)) {
                        if (!copy($src_ht, $dest_ht)) {
                            $notices[] = 'Impossible de créer <code>.htaccess</code> automatiquement — copiez manuellement <code>point.htaccess</code>.';
                        }
                    }
                    $step = 4;
                }
                break;

            // ── Étape 4 : club.php
            case 4:
                ensure_example('club');
                $f = CFG_DIR . '/club.php';
                cfg_write($f, 'sigle_club',    trim($_POST['sigle_club']   ?? ''));
                cfg_write($f, 'email_club',    trim($_POST['email_club']   ?? ''));
                cfg_write($f, 'url_club',      trim($_POST['url_club']     ?? ''));
                cfg_write($f, 'ffvv_product',  trim($_POST['ffvv_product'] ?? ''));
                cfg_write($f, 'copie_a',       trim($_POST['copie_a']      ?? ''));
                cfg_write($f, 'banner_color',  trim($_POST['banner_color'] ?? 'green'));
                // mod : multi-ligne, remplacement spécifique
                $mod     = trim($_POST['mod'] ?? '');
                $escaped = str_replace(["\\","'"],["\\\\","\\'"], $mod);
                $c = file_get_contents($f);
                $pat = '/\$config\[\'mod\'\]\s*=\s*(?:"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')\s*;/s';
                $rep = "\$config['mod'] = '" . $escaped . "';";
                $c2 = preg_replace($pat, $rep, $c, 1, $n);
                if ($n === 0) $c2 = rtrim($c) . "\n" . $rep . "\n";
                file_put_contents($f, $c2);
                $step = 5;
                break;

            // ── Étape 5 : dx_auth.php
            case 5:
                ensure_example('dx_auth');
                $f = CFG_DIR . '/dx_auth.php';
                cfg_write($f, 'DX_website_name',   trim($_POST['dx_website'] ?? ''));
                cfg_write($f, 'DX_webmaster_email', trim($_POST['dx_email']   ?? ''));
                $step = 6;
                break;

            // ── Étape 6 : program.php
            case 6:
                ensure_example('program');
                $f = CFG_DIR . '/program.php';
                cfg_write($f, 'program_title', trim($_POST['program_title'] ?? 'GVV'));
                cfg_write($f, 'banner_color',  trim($_POST['banner_color2'] ?? 'green'));
                cfg_write($f, 'copie_a',       trim($_POST['copie_a2']      ?? ''));
                $flags = ['gestion_tickets','gestion_pompes','gestion_vd','gestion_of',
                          'gestion_formations','gestion_paiements','gestion_reservations',
                          'gestion_documentaire','gestion_ulm','auto_planchiste'];
                foreach ($flags as $flag)
                    cfg_write_bool($f, $flag, isset($_POST[$flag]));
                $step = 7;
                break;

            // ── Étape 7 : Google (optionnel)
            case 7:
                if (!isset($_POST['skip'])) {
                    ensure_example('google');
                    $f = CFG_DIR . '/google.php';
                    cfg_write($f, 'client_id',     trim($_POST['google_client_id']     ?? ''));
                    cfg_write($f, 'client_secret', trim($_POST['google_client_secret'] ?? ''));
                    cfg_write($f, 'api_key',       trim($_POST['google_api_key']       ?? ''));
                }
                $step = 8;
                break;

            // ── Étape 8 : Initialisation de la base
            case 8:
                $db = db_from_session();
                $conn = @mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
                if (!$conn) {
                    $errors[] = 'Connexion à la base impossible. Vérifiez l\'étape 2.';
                } else {
                    $res = mysqli_query($conn,
                        "SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = '"
                        . mysqli_real_escape_string($conn, $db['name']) . "'");
                    $cnt = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
                    $force = isset($_POST['force_reinit']);

                    if ($cnt >= 22 && !$force) {
                        $notices[] = "$cnt tables détectées — base déjà initialisée, aucune action.";
                        $step = 9;
                    } else {
                        // Validation des champs admin avant import
                        $adm_user  = trim($_POST['adm_username'] ?? '');
                        $adm_email = trim($_POST['adm_email']    ?? '');
                        $adm_pass  = $_POST['adm_password']      ?? '';
                        $adm_pass2 = $_POST['adm_password2']     ?? '';

                        if (!$adm_user)  $errors[] = 'L\'identifiant administrateur est requis.';
                        if (!$adm_email) $errors[] = 'L\'email administrateur est requis.';
                        if (!$adm_pass)  $errors[] = 'Le mot de passe administrateur est requis.';
                        if ($adm_pass && $adm_pass !== $adm_pass2)
                            $errors[] = 'Les mots de passe ne correspondent pas.';
                        if (strlen($adm_pass) < 8)
                            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';

                        if (empty($errors)) {
                            if ($force && $cnt > 0) {
                                mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');
                                $t = mysqli_query($conn, 'SHOW TABLES');
                                while ($row = mysqli_fetch_row($t))
                                    mysqli_query($conn, "DROP TABLE IF EXISTS `{$row[0]}`");
                                mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');
                            }
                            $sql_file = isset($_POST['use_test_db'])
                                ? INSTALL_DIR . '/dusk_tests.sql'
                                : INSTALL_DIR . '/gvv_init.sql';
                            $errs = mysql_import_file($sql_file, $conn);
                            if (empty($errs)) {
                                $notices[] = 'Base initialisée avec succès.';
                                // Suppression des utilisateurs de test
                                $test_users = ['testadmin','testuser','testplanchiste',
                                               'asterix','abraracourcix','panoramix','goudurix'];
                                $in = implode(',', array_map(
                                    fn($u) => "'" . mysqli_real_escape_string($conn, $u) . "'",
                                    $test_users));
                                mysqli_query($conn, "DELETE FROM membres WHERE mlogin IN ($in)");
                                mysqli_query($conn, "DELETE FROM users   WHERE username IN ($in)");
                                // Insertion de l'administrateur dans users (role_id=2)
                                $hashed = dx_hash_password($adm_pass);
                                $now    = date('Y-m-d H:i:s');
                                $u_esc  = mysqli_real_escape_string($conn, $adm_user);
                                $h_esc  = mysqli_real_escape_string($conn, $hashed);
                                $e_esc  = mysqli_real_escape_string($conn, $adm_email);
                                mysqli_query($conn,
                                    "INSERT INTO users (role_id, username, password, email, last_ip, last_login, created)
                                     VALUES (2, '$u_esc', '$h_esc', '$e_esc', '127.0.0.1', '$now', '$now')");
                                $notices[] = "Administrateur <strong>" . htmlspecialchars($adm_user) . "</strong> créé.";
                                $step = 9;
                            } else {
                                foreach (array_slice($errs, 0, 8) as $e)
                                    $errors[] = htmlspecialchars($e);
                            }
                        }
                    }
                    mysqli_close($conn);
                }
                break;

            // ── Étape 9 : Répertoires
            case 9:
                $dirs = [
                    ROOT . '/uploads',
                    ROOT . '/uploads/restore',
                    ROOT . '/uploads/attachments',
                    ROOT . '/uploads/configuration',
                    ROOT . '/uploads/documents',
                    ROOT . '/uploads/formation',
                    ROOT . '/uploads/email_lists',
                    ROOT . '/assets/images',
                    ROOT . '/application/logs',
                    ROOT . '/captcha',
                ];
                foreach ($dirs as $d) {
                    ensure_dir($d, 0775);
                    @chmod($d, 0775);
                }
                $step = 10;
                break;
        }
    }

    if (empty($errors)) {
        $_SESSION['install']['step'] = $step;
        header('Location: index.php?step=' . $step);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════
// Données à afficher (pré-remplissage)
// ═══════════════════════════════════════════════════════════
$d_db = [
    'host' => $_SESSION['install']['db_host'] ?? db_cfg_read('hostname', 'localhost'),
    'user' => $_SESSION['install']['db_user'] ?? db_cfg_read('username', ''),
    'pass' => $_SESSION['install']['db_pass'] ?? db_cfg_read('password', ''),
    'name' => $_SESSION['install']['db_name'] ?? db_cfg_read('database', ''),
];
$d_url = [
    'base_url' => pre_read('config','base_url', detect_base_url()),
    'language'  => pre_read('config','language','french'),
];
// base_url : si c'est encore la valeur par défaut de l'exemple, auto-détecter
if (in_array($d_url['base_url'], ['http://localhost/gvv2/', ''])) {
    $d_url['base_url'] = detect_base_url();
}
$d_club = [
    'sigle_club'   => pre_read('club','sigle_club','Mon club'),
    'email_club'   => pre_read('club','email_club',''),
    'url_club'     => pre_read('club','url_club',''),
    'mod'          => pre_read('club','mod',''),
    'ffvv_product' => pre_read('club','ffvv_product',''),
    'copie_a'      => pre_read('club','copie_a',''),
    'banner_color' => pre_read('club','banner_color','green'),
];
$d_auth = [
    'website' => pre_read('dx_auth','DX_website_name',''),
    'email'   => pre_read('dx_auth','DX_webmaster_email',''),
];
$d_prog = [
    'title'        => pre_read('program','program_title','GVV'),
    'banner_color' => pre_read('program','banner_color','green'),
    'copie_a'      => pre_read('program','copie_a',''),
];
$flags_list = ['gestion_tickets','gestion_pompes','gestion_vd','gestion_of',
               'gestion_formations','gestion_paiements','gestion_reservations',
               'gestion_documentaire','gestion_ulm','auto_planchiste'];
$flags_labels = [
    'gestion_tickets'       => 'Tickets de vol',
    'gestion_pompes'        => 'Pompes carburant',
    'gestion_vd'            => 'Vols de découverte',
    'gestion_of'            => 'Offres de formation',
    'gestion_formations'    => 'Formations',
    'gestion_paiements'     => 'Paiements HelloAsso',
    'gestion_reservations'  => 'Réservations',
    'gestion_documentaire'  => 'Gestion documentaire',
    'gestion_ulm'           => 'ULM',
    'auto_planchiste'       => 'Planchiste automatique',
];
$d_flags = [];
foreach ($flags_list as $f)
    $d_flags[$f] = pre_read_bool('program', $f, false);

$d_google = [
    'client_id'     => pre_read('google','client_id',''),
    'client_secret' => pre_read('google','client_secret',''),
    'api_key'       => pre_read('google','api_key',''),
];

// ── Prérequis (calculés pour l'affichage étape 1)
$php_ok     = version_compare(PHP_VERSION, '7.4.0', '>=');
$ext_ok     = array_map(fn($e) => extension_loaded($e),
                ['mysqli','json','mbstring','gd','openssl']);
$ext_names  = ['mysqli','json','mbstring','gd','openssl'];
$cfg_write_ok = is_writable(CFG_DIR);
$overall_ok = $php_ok && !in_array(false, $ext_ok) && $cfg_write_ok;

// ── Répertoires à vérifier (pour l'affichage étape 10)
$required_dirs = [
    ROOT.'/uploads'                  => 'uploads/',
    ROOT.'/uploads/restore'          => 'uploads/restore/',
    ROOT.'/uploads/attachments'      => 'uploads/attachments/',
    ROOT.'/uploads/configuration'    => 'uploads/configuration/',
    ROOT.'/uploads/documents'        => 'uploads/documents/',
    ROOT.'/uploads/formation'        => 'uploads/formation/',
    ROOT.'/uploads/email_lists'      => 'uploads/email_lists/',
    ROOT.'/assets/images'            => 'assets/images/',
    ROOT.'/application/logs'         => 'application/logs/',
    ROOT.'/captcha'                  => 'captcha/',
];

// ═══════════════════════════════════════════════════════════
// HTML
// ═══════════════════════════════════════════════════════════

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function chk(bool $b): string { return $b ? ' checked' : ''; }

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation GVV — Étape <?= $step ?>/<?= count($STEPS) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f0f4f8; }
.wizard-nav .step-item {
    display: flex; flex-direction: column; align-items: center;
    flex: 1; font-size: .75rem; color: #adb5bd; position: relative;
}
.wizard-nav .step-item::before {
    content: ''; position: absolute; top: 18px; left: -50%;
    width: 100%; height: 2px; background: #dee2e6; z-index: 0;
}
.wizard-nav .step-item:first-child::before { display: none; }
.wizard-nav .step-item .bubble {
    width: 36px; height: 36px; border-radius: 50%;
    background: #dee2e6; color: #6c757d;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; position: relative; z-index: 1;
    margin-bottom: 4px;
}
.wizard-nav .step-item.done .bubble   { background: #198754; color: #fff; }
.wizard-nav .step-item.active .bubble { background: #0d6efd; color: #fff; }
.wizard-nav .step-item.active        { color: #0d6efd; font-weight: 600; }
.wizard-nav .step-item.done::before  { background: #198754; }
.card { border: none; border-radius: 1rem; }
.step-title { font-size: 1.3rem; font-weight: 700; color: #212529; }
.badge-req { font-size: .85rem; padding: .4em .8em; }
.flag-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
</style>
</head>
<body>

<nav class="navbar navbar-dark py-3 mb-4" style="background:linear-gradient(90deg,#1a237e,#1565c0)">
  <div class="container">
    <span class="navbar-brand fw-bold fs-4"><i class="fas fa-plane me-2"></i>GVV — Installation</span>
    <span class="text-white-50 small">Étape <?= $step ?> / <?= count($STEPS) ?></span>
  </div>
</nav>

<div class="container" style="max-width:820px">

  <!-- Barre de progression -->
  <div class="d-flex wizard-nav mb-4 overflow-auto pb-2">
  <?php foreach ($STEPS as $n => $info):
    $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
  ?>
    <div class="step-item <?= $cls ?>">
      <div class="bubble">
        <?php if ($n < $step): ?>
          <i class="fas fa-check"></i>
        <?php else: ?>
          <i class="fas <?= $info['icon'] ?>"></i>
        <?php endif; ?>
      </div>
      <span class="d-none d-md-block text-center" style="line-height:1.2"><?= $info['label'] ?></span>
    </div>
  <?php endforeach; ?>
  </div>

  <!-- Erreurs -->
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

  <?php if ($notices): ?>
  <div class="alert alert-info mb-3">
    <?php foreach ($notices as $n): ?><div><?= $n ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Carte principale -->
  <div class="card shadow">
  <div class="card-body p-4 p-md-5">

  <form method="post" action="index.php">
  <input type="hidden" name="posted_step" value="<?= $step ?>">

  <?php switch ($step):

  // ════════════════════════════════════════════════════════
  case 1: // Prérequis
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-4"><i class="fas fa-check-circle text-primary me-2"></i>Prérequis</h2>

  <table class="table table-bordered align-middle">
  <thead class="table-light"><tr><th>Élément</th><th class="text-center" style="width:120px">Statut</th></tr></thead>
  <tbody>
  <tr>
    <td>PHP ≥ 7.4 <span class="text-muted">(actuel : <?= PHP_VERSION ?>)</span></td>
    <td class="text-center">
      <span class="badge badge-req <?= $php_ok?'bg-success':'bg-danger' ?>">
        <?= $php_ok?'OK':'KO' ?>
      </span>
    </td>
  </tr>
  <?php foreach ($ext_names as $i => $ext): ?>
  <tr>
    <td>Extension <code><?= $ext ?></code></td>
    <td class="text-center">
      <span class="badge badge-req <?= $ext_ok[$i]?'bg-success':'bg-danger' ?>">
        <?= $ext_ok[$i]?'OK':'Manquante' ?>
      </span>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr>
    <td>Répertoire <code>application/config/</code> accessible en écriture</td>
    <td class="text-center">
      <span class="badge badge-req <?= $cfg_write_ok?'bg-success':'bg-danger' ?>">
        <?= $cfg_write_ok?'OK':'KO' ?>
      </span>
    </td>
  </tr>
  </tbody>
  </table>

  <?php if (!$overall_ok): ?>
  <div class="alert alert-danger">
    <i class="fas fa-ban me-2"></i>Certains prérequis ne sont pas satisfaits. Corrigez-les avant de continuer.
  </div>
  <?php else: ?>
  <div class="alert alert-success"><i class="fas fa-check me-2"></i>Tous les prérequis sont satisfaits.</div>
  <?php endif; ?>

  <div class="d-flex justify-content-end mt-4">
    <button type="submit" name="action" value="next" class="btn btn-primary" <?= $overall_ok?'':'disabled' ?>>
      Suivant <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 2: // Base de données
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-database text-primary me-2"></i>Base de données</h2>
  <p class="text-muted mb-4">Connexion MySQL — la base doit exister, l'utilisateur doit avoir tous les droits.</p>

  <div class="mb-3">
    <label class="form-label fw-semibold">Hôte <span class="text-danger">*</span></label>
    <input type="text" name="db_host" class="form-control" value="<?= h($d_db['host']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Nom de la base <span class="text-danger">*</span></label>
    <input type="text" name="db_name" class="form-control" value="<?= h($d_db['name']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Utilisateur <span class="text-danger">*</span></label>
    <input type="text" name="db_user" class="form-control" value="<?= h($d_db['user']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Mot de passe</label>
    <div class="input-group">
      <input type="password" name="db_pass" id="db_pass" class="form-control" value="<?= h($d_db['pass']) ?>">
      <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
        <i class="fas fa-eye" id="eye-icon"></i>
      </button>
    </div>
  </div>

  <div class="alert alert-info small">
    <i class="fas fa-info-circle me-1"></i>
    La connexion est testée avant de passer à l'étape suivante.
    Le fichier <code>application/config/database.php</code> sera créé ou mis à jour.
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      Tester &amp; continuer <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <script>
  function togglePass(){
    var i=document.getElementById('db_pass'),e=document.getElementById('eye-icon');
    i.type=i.type==='password'?'text':'password';
    e.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';
  }
  </script>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 3: // URL
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-globe text-primary me-2"></i>URL de l'application</h2>
  <p class="text-muted mb-4">Configure <code>application/config/config.php</code>.</p>

  <div class="mb-3">
    <label class="form-label fw-semibold">URL de base <span class="text-danger">*</span></label>
    <input type="url" name="base_url" class="form-control" value="<?= h($d_url['base_url']) ?>" required>
    <div class="form-text">Doit se terminer par <code>/</code>. Exemple : <code>https://monclub.fr/gvv/</code></div>
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Langue par défaut</label>
    <select name="language" class="form-select" style="max-width:200px">
      <?php foreach (['french'=>'Français','english'=>'English','dutch'=>'Nederlands'] as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $d_url['language']===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php
  $htaccess_src  = ROOT . '/point.htaccess';
  $htaccess_dest = ROOT . '/.htaccess';
  $htaccess_content = file_exists($htaccess_src) ? file_get_contents($htaccess_src) : '';
  $htaccess_exists  = file_exists($htaccess_dest);
  ?>

  <div class="mt-4 mb-3">
    <h6 class="fw-semibold mb-1"><i class="fas fa-file-code text-primary me-2"></i>Réécriture d'URL — <code>.htaccess</code></h6>
    <p class="text-muted small mb-2">
      Le fichier <code>index_page</code> est mis à vide pour activer les URL propres.
      <code>point.htaccess</code> sera recopié automatiquement en <code>.htaccess</code> à la validation.
      <?= $htaccess_exists
          ? 'Un fichier <code>.htaccess</code> existe déjà et sera écrasé.'
          : 'Aucun <code>.htaccess</code> détecté — il sera créé.' ?>
    </p>
    <div class="position-relative">
      <pre id="htaccess-content" class="bg-dark text-light rounded p-3 small mb-1" style="white-space:pre-wrap;max-height:220px;overflow-y:auto"><?= h($htaccess_content) ?></pre>
      <button type="button" onclick="copyHtaccess()" class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" id="copy-btn">
        <i class="fas fa-copy me-1"></i>Copier
      </button>
    </div>
  </div>
  <script>
  function copyHtaccess() {
    var text = document.getElementById('htaccess-content').innerText;
    navigator.clipboard.writeText(text).then(function() {
      var btn = document.getElementById('copy-btn');
      btn.innerHTML = '<i class="fas fa-check me-1"></i>Copié !';
      setTimeout(function(){ btn.innerHTML = '<i class="fas fa-copy me-1"></i>Copier'; }, 2000);
    });
  }
  </script>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      Suivant <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 4: // Club
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-building text-primary me-2"></i>Informations du club</h2>
  <p class="text-muted mb-4">Configure <code>application/config/club.php</code>.</p>

  <div class="row g-3">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Nom du club / sigle</label>
    <input type="text" name="sigle_club" class="form-control" value="<?= h($d_club['sigle_club']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Email du club</label>
    <input type="email" name="email_club" class="form-control" value="<?= h($d_club['email_club']) ?>">
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold">URL du site du club</label>
    <input type="text" name="url_club" class="form-control" value="<?= h($d_club['url_club']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Produit cotisation FFVV</label>
    <input type="text" name="ffvv_product" class="form-control" value="<?= h($d_club['ffvv_product']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Couleur de la bannière <span class="text-muted small">(CSS)</span></label>
    <div class="input-group">
      <input type="color" id="bcolor" class="form-control form-control-color"
             value="<?= preg_match('/^#/',$d_club['banner_color'])?h($d_club['banner_color']):'#008080' ?>"
             oninput="document.getElementById('bc_text').value=this.value">
      <input type="text" name="banner_color" id="bc_text" class="form-control" value="<?= h($d_club['banner_color']) ?>"
             oninput="try{document.getElementById('bcolor').value=this.value}catch(e){}">
    </div>
    <div class="form-text">Ex : <code>green</code>, <code>#1a237e</code>, <code>darkblue</code></div>
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold">Copie systématique des emails (<code>copie_a</code>)</label>
    <input type="text" name="copie_a" class="form-control" value="<?= h($d_club['copie_a']) ?>"
           placeholder="president@club.fr; secretaire@club.fr">
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold">Message du jour</label>
    <textarea name="mod" class="form-control" rows="4"><?= h($d_club['mod']) ?></textarea>
    <div class="form-text">Supporte le Markdown. Affiché sur la page d'accueil.</div>
  </div>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      Suivant <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 5: // Authentification
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-lock text-primary me-2"></i>Authentification</h2>
  <p class="text-muted mb-4">Configure <code>application/config/dx_auth.php</code>.</p>

  <div class="mb-3">
    <label class="form-label fw-semibold">URL du site</label>
    <input type="text" name="dx_website" class="form-control" value="<?= h($d_auth['website']) ?>">
    <div class="form-text">Utilisée dans les emails envoyés aux membres (réinitialisation de mot de passe…)</div>
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Email de l'administrateur (webmaster)</label>
    <input type="email" name="dx_email" class="form-control" value="<?= h($d_auth['email']) ?>">
    <div class="form-text">Adresse expéditrice des emails système.</div>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      Suivant <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 6: // Fonctionnalités
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-sliders text-primary me-2"></i>Fonctionnalités</h2>
  <p class="text-muted mb-4">Configure <code>application/config/program.php</code>.</p>

  <div class="row g-3 mb-3">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Titre de l'application</label>
    <input type="text" name="program_title" class="form-control" value="<?= h($d_prog['title']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Couleur de bannière <span class="text-muted small">(CSS)</span></label>
    <div class="input-group">
      <input type="color" id="bcolor2" class="form-control form-control-color"
             value="<?= preg_match('/^#/',$d_prog['banner_color'])?h($d_prog['banner_color']):'#008080' ?>"
             oninput="document.getElementById('bc2_text').value=this.value">
      <input type="text" name="banner_color2" id="bc2_text" class="form-control"
             value="<?= h($d_prog['banner_color']) ?>"
             oninput="try{document.getElementById('bcolor2').value=this.value}catch(e){}">
    </div>
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold">Copie systématique des emails</label>
    <input type="text" name="copie_a2" class="form-control" value="<?= h($d_prog['copie_a']) ?>"
           placeholder="president@club.fr">
  </div>
  </div>

  <label class="form-label fw-semibold">Modules actifs</label>
  <div class="flag-grid mb-3">
  <?php foreach ($flags_list as $flag): ?>
  <div class="form-check">
    <input class="form-check-input" type="checkbox" name="<?= $flag ?>" id="<?= $flag ?>"<?= chk($d_flags[$flag]) ?>>
    <label class="form-check-label" for="<?= $flag ?>"><?= $flags_labels[$flag] ?></label>
  </div>
  <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      Suivant <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 7: // Google
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fab fa-google text-primary me-2"></i>Intégration Google</h2>
  <p class="text-muted mb-4">Optionnel — pour la synchronisation du calendrier Google. Configure <code>application/config/google.php</code>.</p>

  <div class="mb-3">
    <label class="form-label fw-semibold">Client ID</label>
    <input type="text" name="google_client_id" class="form-control font-monospace" value="<?= h($d_google['client_id']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">Client Secret</label>
    <input type="text" name="google_client_secret" class="form-control font-monospace" value="<?= h($d_google['client_secret']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label fw-semibold">API Key</label>
    <input type="text" name="google_api_key" class="form-control font-monospace" value="<?= h($d_google['api_key']) ?>">
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <div>
      <button type="submit" name="skip" value="1" class="btn btn-outline-secondary me-2">
        Passer <i class="fas fa-forward ms-1"></i>
      </button>
      <button type="submit" name="action" value="next" class="btn btn-primary">
        Enregistrer &amp; continuer <i class="fas fa-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 8: // Init base
  // ════════════════════════════════════════════════════════
  $db9 = db_from_session();
  $conn9 = @mysqli_connect($db9['host'], $db9['user'], $db9['pass'], $db9['name']);
  $cnt9 = 0;
  if ($conn9) {
      $r9 = mysqli_query($conn9,
          "SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = '"
          . mysqli_real_escape_string($conn9, $db9['name']) . "'");
      $cnt9 = (int)(mysqli_fetch_assoc($r9)['cnt'] ?? 0);
      mysqli_close($conn9);
  }
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-table text-primary me-2"></i>Initialisation de la base</h2>
  <p class="text-muted mb-4">Création des tables GVV depuis <code>install/gvv_init.sql</code>.</p>

  <div class="alert <?= $cnt9 >= 22 ? 'alert-warning' : 'alert-info' ?>">
    <i class="fas <?= $cnt9 >= 22 ? 'fa-exclamation-triangle' : 'fa-info-circle' ?> me-2"></i>
    <?php if ($cnt9 >= 22): ?>
      <strong><?= $cnt9 ?> tables</strong> détectées dans <code><?= h($db9['name']) ?></code>.
      La base semble déjà initialisée.
    <?php else: ?>
      <strong><?= $cnt9 ?> table(s)</strong> dans <code><?= h($db9['name']) ?></code> — initialisation nécessaire.
    <?php endif; ?>
  </div>

  <?php if ($cnt9 >= 22): ?>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="force_reinit" id="force_reinit">
    <label class="form-check-label text-danger fw-semibold" for="force_reinit">
      <i class="fas fa-exclamation-triangle me-1"></i>
      Forcer la réinitialisation (supprime toutes les données !)
    </label>
  </div>
  <?php endif; ?>

  <div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="use_test_db" id="use_test_db">
    <label class="form-check-label" for="use_test_db">
      Utiliser la base de test (<code>dusk_tests.sql</code>) à la place de la base vide
    </label>
  </div>

  <?php if ($cnt9 < 22): ?>
  <div id="admin-section" class="border rounded p-3 mb-4 bg-light">
    <h6 class="fw-bold mb-3"><i class="fas fa-user-shield text-primary me-2"></i>Compte administrateur</h6>
    <p class="text-muted small mb-3">
      Les utilisateurs de test seront supprimés. Créez ici le premier compte administrateur.
    </p>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Identifiant <span class="text-danger">*</span></label>
        <input type="text" name="adm_username" class="form-control" value="<?= h($_POST['adm_username'] ?? '') ?>" required autocomplete="off">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" name="adm_email" class="form-control" value="<?= h($_POST['adm_email'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Mot de passe <span class="text-danger">*</span></label>
        <input type="password" name="adm_password" class="form-control" autocomplete="new-password" required minlength="8">
        <div class="form-text">8 caractères minimum.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Confirmer le mot de passe <span class="text-danger">*</span></label>
        <input type="password" name="adm_password2" class="form-control" autocomplete="new-password" required minlength="8">
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between mt-2">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      <?= $cnt9 >= 22 ? 'Vérifier &amp; continuer' : 'Initialiser la base' ?>
      <i class="fas fa-arrow-right ms-1"></i>
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 9: // Répertoires
  // ════════════════════════════════════════════════════════
  ?>
  <h2 class="step-title mb-1"><i class="fas fa-folder text-primary me-2"></i>Répertoires &amp; droits</h2>
  <p class="text-muted mb-4">Vérification et création des répertoires nécessaires à GVV.</p>

  <table class="table table-bordered align-middle">
  <thead class="table-light">
    <tr><th>Répertoire</th><th class="text-center" style="width:110px">Existe</th><th class="text-center" style="width:110px">Écriture</th></tr>
  </thead>
  <tbody>
  <?php foreach ($required_dirs as $abs => $rel):
    $exists = is_dir($abs);
    $writable = $exists && is_writable($abs);
  ?>
  <tr>
    <td><code><?= h($rel) ?></code></td>
    <td class="text-center">
      <span class="badge <?= $exists?'bg-success':'bg-warning text-dark' ?>">
        <?= $exists?'Oui':'À créer' ?>
      </span>
    </td>
    <td class="text-center">
      <span class="badge <?= $writable?'bg-success':($exists?'bg-danger':'bg-secondary') ?>">
        <?= $writable?'OK':($exists?'Non':'—') ?>
      </span>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  </table>

  <div class="alert alert-info small">
    <i class="fas fa-info-circle me-1"></i>
    Cliquez sur <strong>Créer &amp; corriger</strong> pour créer les répertoires manquants et appliquer les droits (<code>0775</code>).
  </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Précédent
    </button>
    <button type="submit" name="action" value="next" class="btn btn-primary">
      <i class="fas fa-folder-plus me-1"></i>Créer &amp; corriger les droits
    </button>
  </div>

  <?php break;
  // ════════════════════════════════════════════════════════
  case 10: // Terminé
  // ════════════════════════════════════════════════════════
  $app_url = pre_read('config','base_url', detect_base_url());
  $files_created = [];
  foreach (['database','config','club','dx_auth','program','google'] as $b) {
      if (file_exists(CFG_DIR . '/' . $b . '.php'))
          $files_created[] = 'application/config/' . $b . '.php';
  }
  // Création du verrou
  $lock_created = false;
  if (!file_exists(LOCK_FILE)) {
      $lock_created = (file_put_contents(LOCK_FILE, date('Y-m-d H:i:s')) !== false);
  }
  ?>
  <div class="text-center mb-4">
    <div class="display-1 text-success mb-3"><i class="fas fa-flag-checkered"></i></div>
    <h2 class="step-title">Installation terminée !</h2>
    <p class="text-muted">GVV est prêt à être utilisé.</p>
  </div>

  <div class="card bg-light mb-4">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Fichiers de configuration créés / mis à jour</h6>
    <ul class="mb-0 small font-monospace">
    <?php foreach ($files_created as $f): ?>
    <li><?= h($f) ?></li>
    <?php endforeach; ?>
    </ul>
  </div>
  </div>

  <?php if ($lock_created): ?>
  <div class="alert alert-success">
    <i class="fas fa-lock me-2"></i>
    <strong>Assistant verrouillé.</strong>
    Le fichier <code>install/installed.lock</code> a été créé — l'assistant d'installation
    est désormais inaccessible. Pour le relancer, supprimez ce fichier depuis le serveur.
  </div>
  <?php else: ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Attention :</strong> impossible de créer <code>install/installed.lock</code>.
    Créez ce fichier manuellement ou supprimez le répertoire <code>install/</code>
    pour protéger la configuration.
  </div>
  <?php endif; ?>

  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Sécurité :</strong>
    Changez les mots de passe des utilisateurs de test (<code>testuser</code>, <code>testadmin</code>)
    dès votre première connexion.
  </div>

  <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
    <a href="<?= h($app_url) ?>" class="btn btn-success btn-lg px-5">
      <i class="fas fa-plane me-2"></i>Accéder à GVV
    </a>
    <a href="index.php?step=1" class="btn btn-outline-secondary btn-lg">
      Recommencer l'installation
    </a>
  </div>

  <?php
  // Reset session
  unset($_SESSION['install']);
  break;
  endswitch; ?>

  </form>
  </div><!-- card-body -->
  </div><!-- card -->

  <div class="text-center text-muted small mt-4 pb-4">
    GVV — Gestion Vol à Voile &nbsp;|&nbsp; PHP <?= PHP_VERSION ?>
  </div>
</div><!-- container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
