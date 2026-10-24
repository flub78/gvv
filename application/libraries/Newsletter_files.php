<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_files
 *
 * Stockage des fichiers de la veille hors de l'espace servi directement par
 * le serveur web : versions successives des brouillons d'une édition et
 * paquets d'exécution de l'agent (brouillon, rapport d'activité, inventaire).
 * Les fichiers ne sont accessibles qu'au travers des contrôleurs.
 *
 * Arborescence (racine : $config['newsletter_storage_path'],
 * par défaut application/storage/newsletter/, protégé par application/.htaccess) :
 *   editions/<id>/brouillon_v<N>.md
 *   reponses/<id suggestion>/<pièces jointes>
 *   runs/<run_id>/{brouillon.md, rapport.md, inventaire.csv}
 *
 * Fournit aussi les traitements déterministes du texte : retrait des notes
 * internes et repérage des mentions entre crochets restant à compléter.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_files {

    const DRAFT_FILE     = 'brouillon.md';
    const REPORT_FILE    = 'rapport.md';
    const INVENTORY_FILE = 'inventaire.csv';

    private $base;

    /**
     * @param array $params  base_path : racine de stockage (newsletter_storage_path) ;
     *                       vide → application/storage/newsletter/
     */
    public function __construct($params = array()) {
        // Chemin absolu : il est enregistré en base (paquets d'exécution) et
        // relu par d'autres processus que le serveur web.
        $base = !empty($params['base_path']) ? $params['base_path']
                                             : rtrim(realpath(APPPATH) ?: APPPATH, '/') . '/storage/newsletter/';
        $this->base = rtrim($base, '/') . '/';
    }

    public function base_path() {
        return $this->base;
    }

    /**
     * Crée un répertoire et ses parents, modifiables par le groupe : les
     * fichiers écrits par le serveur web restent gérables par les comptes du
     * groupe (maintenance, tests), quel que soit le masque de création.
     */
    private function ensure_dir($dir) {
        $dir = rtrim($dir, '/');
        if (is_dir($dir)) return TRUE;
        if (!$this->ensure_dir(dirname($dir))) return FALSE;
        if (!@mkdir($dir, 0775) && !is_dir($dir)) {
            log_message('error', 'Newsletter_files: impossible de créer ' . $dir);
            return FALSE;
        }
        @chmod($dir, 02775);
        return TRUE;
    }

    // ── Versions des brouillons d'une édition ─────────────────────────────

    public function edition_dir($newsletter_id) {
        return $this->base . 'editions/' . (int) $newsletter_id . '/';
    }

    /**
     * Versions d'une édition, triées par numéro croissant.
     *
     * @return array  [['version' => int, 'path' => string, 'mtime' => int], …]
     */
    public function list_versions($newsletter_id) {
        $versions = array();
        foreach (glob($this->edition_dir($newsletter_id) . 'brouillon_v*.md') ?: array() as $path) {
            if (preg_match('/brouillon_v(\d+)\.md$/', $path, $m)) {
                $versions[] = array('version' => (int) $m[1], 'path' => $path, 'mtime' => filemtime($path));
            }
        }
        usort($versions, function ($a, $b) { return $a['version'] - $b['version']; });
        return $versions;
    }

    /**
     * Numéro de version d'un chemin de brouillon (0 si inconnu).
     */
    public static function version_of($path) {
        return ($path && preg_match('/brouillon_v(\d+)\.md$/', $path, $m)) ? (int) $m[1] : 0;
    }

    /**
     * Enregistre une nouvelle version du brouillon d'une édition.
     *
     * @return array|false  ['version' => int, 'path' => string]
     */
    public function save_version($newsletter_id, $content) {
        $dir = $this->edition_dir($newsletter_id);
        if (!$this->ensure_dir($dir)) return FALSE;
        $versions = $this->list_versions($newsletter_id);
        $n = $versions ? end($versions)['version'] + 1 : 1;
        $path = $dir . 'brouillon_v' . $n . '.md';
        if (file_put_contents($path, $content) === FALSE) {
            log_message('error', 'Newsletter_files: écriture impossible ' . $path);
            return FALSE;
        }
        @chmod($path, 0664);
        return array('version' => $n, 'path' => $path);
    }

    /**
     * Lit un fichier stocké, à condition qu'il soit sous la racine de stockage.
     */
    public function read($path) {
        $real = $path ? realpath($path) : FALSE;
        $root = realpath($this->base);
        if (!$real || !$root || strpos($real, $root . '/') !== 0 || !is_file($real)) {
            return FALSE;
        }
        return file_get_contents($real);
    }

    /**
     * Supprime les fichiers d'une édition (édition supprimée à l'état brouillon).
     */
    public function delete_edition($newsletter_id) {
        $this->rrmdir($this->edition_dir($newsletter_id));
    }

    /**
     * Pièces jointes d'une réponse de relecteur (rangées par suggestion, qui
     * peut changer d'édition lorsqu'elle est reportée).
     */
    public function reply_dir($review_id) {
        return $this->base . 'reponses/' . (int) $review_id . '/';
    }

    public function store_reply_file($review_id, $name, $content) {
        $dir = $this->reply_dir($review_id);
        if (!$this->ensure_dir($dir)) return FALSE;
        $path = $dir . basename($name);
        if (file_put_contents($path, $content) === FALSE) return FALSE;
        @chmod($path, 0664);
        return $path;
    }

    public function list_reply_files($review_id) {
        return array_map('basename', glob($this->reply_dir($review_id) . '*') ?: array());
    }

    // ── Paquets d'exécution ───────────────────────────────────────────────

    public function run_dir($run_id) {
        return $this->base . 'runs/' . preg_replace('/[^A-Za-z0-9_-]/', '', $run_id) . '/';
    }

    /**
     * Enregistre un fichier du paquet d'une exécution.
     *
     * @param string $run_id
     * @param string $name     DRAFT_FILE, REPORT_FILE ou INVENTORY_FILE
     * @param string $content
     * @return string|false    Chemin écrit
     */
    public function store_run_file($run_id, $name, $content) {
        if (!in_array($name, array(self::DRAFT_FILE, self::REPORT_FILE, self::INVENTORY_FILE), TRUE)) {
            return FALSE;
        }
        $dir = $this->run_dir($run_id);
        if (!$this->ensure_dir($dir)) return FALSE;
        $path = $dir . $name;
        if (file_put_contents($path, $content) === FALSE) return FALSE;
        @chmod($path, 0664);
        return $path;
    }

    public function run_file($run_id, $name) {
        $path = $this->run_dir($run_id) . $name;
        return is_file($path) ? $path : NULL;
    }

    public function delete_run($run_id) {
        $this->rrmdir($this->run_dir($run_id));
    }

    private function rrmdir($dir) {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), array('.', '..')) as $f) {
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    // ── Traitements déterministes du texte ────────────────────────────────

    /**
     * Retire les notes internes d'un brouillon validé :
     * - la section dont le titre contient « SECTION INTERNE », jusqu'au titre
     *   suivant de même niveau ou supérieur (ou la fin du document) ;
     * - les citations d'avertissement de brouillon (« > **BROUILLON … »).
     */
    public static function remove_internal_notes($markdown) {
        $lines = preg_split('/\R/', $markdown);
        $out = array();
        $skip_level = 0;
        $in_draft_quote = FALSE;
        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s/', $line, $m)) {
                $level = strlen($m[1]);
                if ($skip_level && $level <= $skip_level) {
                    $skip_level = 0;
                }
                if (!$skip_level && stripos($line, 'SECTION INTERNE') !== FALSE) {
                    $skip_level = $level;
                }
            }
            if ($skip_level) continue;

            if (preg_match('/^>\s*\*\*BROUILLON/u', $line)) {
                $in_draft_quote = TRUE;
                continue;
            }
            if ($in_draft_quote) {
                if (preg_match('/^>/', $line)) continue;
                $in_draft_quote = FALSE;
            }
            $out[] = $line;
        }
        $text = implode("\n", $out);
        // Séparateurs horizontaux laissés orphelins en fin de document
        $text = preg_replace('/(\s*\n(-{3,}|\*{3,})\s*)+$/', '', $text);
        return rtrim($text) . "\n";
    }

    /**
     * Différences ligne à ligne entre deux textes (plus longue sous-suite commune).
     *
     * @return array  [['op' => ' '|'-'|'+', 'line' => string], …]
     */
    public static function diff_lines($old, $new) {
        $a = preg_split('/\R/', (string) $old);
        $b = preg_split('/\R/', (string) $new);
        // Préfixe et suffixe communs retirés pour limiter la table
        $pre = 0;
        while ($pre < count($a) && $pre < count($b) && $a[$pre] === $b[$pre]) $pre++;
        $suf = 0;
        while ($suf < count($a) - $pre && $suf < count($b) - $pre
               && $a[count($a) - 1 - $suf] === $b[count($b) - 1 - $suf]) $suf++;
        $ma = array_slice($a, $pre, count($a) - $pre - $suf);
        $mb = array_slice($b, $pre, count($b) - $pre - $suf);

        $n = count($ma);
        $m = count($mb);
        $l = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $l[$i][$j] = ($ma[$i] === $mb[$j]) ? $l[$i + 1][$j + 1] + 1 : max($l[$i + 1][$j], $l[$i][$j + 1]);
            }
        }

        $out = array();
        for ($k = 0; $k < $pre; $k++) $out[] = array('op' => ' ', 'line' => $a[$k]);
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($ma[$i] === $mb[$j]) { $out[] = array('op' => ' ', 'line' => $ma[$i]); $i++; $j++; }
            elseif ($l[$i + 1][$j] >= $l[$i][$j + 1]) { $out[] = array('op' => '-', 'line' => $ma[$i]); $i++; }
            else { $out[] = array('op' => '+', 'line' => $mb[$j]); $j++; }
        }
        for (; $i < $n; $i++) $out[] = array('op' => '-', 'line' => $ma[$i]);
        for (; $j < $m; $j++) $out[] = array('op' => '+', 'line' => $mb[$j]);
        for ($k = count($a) - $suf; $k < count($a); $k++) $out[] = array('op' => ' ', 'line' => $a[$k]);
        return $out;
    }

    /**
     * Mentions entre crochets restant à compléter (hors liens et images Markdown).
     *
     * @return string[]
     */
    public static function find_open_mentions($markdown) {
        $mentions = array();
        if (preg_match_all('/(!?)\[([^\[\]\n]*)\](\()?/u', $markdown, $all, PREG_SET_ORDER)) {
            foreach ($all as $m) {
                $is_link = isset($m[3]) && $m[3] === '(';
                if ($m[1] === '!' || $is_link) continue;
                $mentions[] = '[' . $m[2] . ']';
            }
        }
        return $mentions;
    }
}
