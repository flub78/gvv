<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_files
 *
 * Stockage des fichiers de la veille hors de l'espace servi directement par
 * le serveur web : versions successives des brouillons d'une édition, avec
 * leurs images, et pièces jointes des réponses des relecteurs. Les fichiers
 * ne sont accessibles qu'au travers des contrôleurs.
 *
 * Arborescence (racine : $config['newsletter_storage_path'],
 * par défaut application/storage/newsletter/, protégé par application/.htaccess) :
 *   editions/<id>/v<N>/brouillon.md
 *   editions/<id>/v<N>/img/<image>
 *   reponses/<id remarque>/<pièces jointes>
 *
 * Fournit aussi les traitements déterministes du texte : découpage en blocs,
 * insertion des commentaires de l'administrateur, retrait des notes
 * internes, repérage des mentions entre crochets, différences entre versions.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_files {

    const DRAFT_FILE = 'brouillon.md';

    /** Extensions d'images acceptées dans un brouillon ou une réponse */
    const IMAGE_EXTENSIONS = array('png', 'jpg', 'jpeg', 'gif', 'svg', 'webp');

    private $base;

    /**
     * @param array $params  base_path : racine de stockage (newsletter_storage_path) ;
     *                       vide → application/storage/newsletter/
     */
    public function __construct($params = array()) {
        // Chemin absolu : il est enregistré en base et ne dépend pas du
        // répertoire courant du processus.
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

    private function write($path, $content) {
        if (!$this->ensure_dir(dirname($path))) return FALSE;
        if (file_put_contents($path, $content) === FALSE) {
            log_message('error', 'Newsletter_files: écriture impossible ' . $path);
            return FALSE;
        }
        @chmod($path, 0664);
        return TRUE;
    }

    // ── Versions des brouillons d'une édition ─────────────────────────────

    public function edition_dir($newsletter_id) {
        return $this->base . 'editions/' . (int) $newsletter_id . '/';
    }

    public function version_dir($newsletter_id, $version) {
        return $this->edition_dir($newsletter_id) . 'v' . (int) $version . '/';
    }

    /**
     * Versions d'une édition, triées par numéro croissant.
     *
     * @return array  [['version' => int, 'path' => string, 'mtime' => int], …]
     */
    public function list_versions($newsletter_id) {
        $versions = array();
        foreach (glob($this->edition_dir($newsletter_id) . 'v*/' . self::DRAFT_FILE) ?: array() as $path) {
            $n = self::version_of($path);
            if ($n) $versions[] = array('version' => $n, 'path' => $path, 'mtime' => filemtime($path));
        }
        usort($versions, function ($a, $b) { return $a['version'] - $b['version']; });
        return $versions;
    }

    /**
     * Numéro de version d'un chemin de brouillon (0 si inconnu).
     */
    public static function version_of($path) {
        return ($path && preg_match('#/v(\d+)/' . preg_quote(self::DRAFT_FILE, '#') . '$#', $path, $m)) ? (int) $m[1] : 0;
    }

    /**
     * Enregistre une version du brouillon d'une édition et ses images.
     *
     * @param int    $newsletter_id
     * @param int    $version
     * @param string $content  Markdown
     * @param array  $images   [nom de fichier => contenu], rangées sous img/
     * @return string|false    Chemin du Markdown ; FALSE si la version existe déjà ou en cas d'erreur
     */
    public function save_version($newsletter_id, $version, $content, array $images = array()) {
        $dir = $this->version_dir($newsletter_id, $version);
        if (is_dir($dir)) return FALSE;
        foreach ($images as $name => $data) {
            if (!$this->write($dir . 'img/' . self::safe_name($name), $data)) {
                $this->rrmdir($dir);
                return FALSE;
            }
        }
        if (!$this->write($dir . self::DRAFT_FILE, $content)) {
            $this->rrmdir($dir);
            return FALSE;
        }
        return $dir . self::DRAFT_FILE;
    }

    public function delete_version($newsletter_id, $version) {
        $this->rrmdir($this->version_dir($newsletter_id, $version));
    }

    /**
     * Chemin d'une image d'une version, ou NULL si elle n'existe pas.
     */
    public function image_path($newsletter_id, $version, $name) {
        $path = $this->version_dir($newsletter_id, $version) . 'img/' . self::safe_name($name);
        return is_file($path) ? $path : NULL;
    }

    /**
     * Images d'une version : [nom => chemin].
     */
    public function list_images($newsletter_id, $version) {
        $out = array();
        foreach (glob($this->version_dir($newsletter_id, $version) . 'img/*') ?: array() as $path) {
            $out[basename($path)] = $path;
        }
        return $out;
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
     * Pièces jointes d'une réponse de relecteur (rangées par remarque, qui
     * peut changer d'édition lorsqu'elle est reportée).
     */
    public function reply_dir($review_id) {
        return $this->base . 'reponses/' . (int) $review_id . '/';
    }

    public function store_reply_file($review_id, $name, $content) {
        $path = $this->reply_dir($review_id) . self::safe_name($name);
        return $this->write($path, $content) ? $path : FALSE;
    }

    public function list_reply_files($review_id) {
        return array_map('basename', glob($this->reply_dir($review_id) . '*') ?: array());
    }

    public function delete_reply_files($review_id) {
        $this->rrmdir($this->reply_dir($review_id));
    }

    private function rrmdir($dir) {
        $dir = rtrim($dir, '/');
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), array('.', '..')) as $f) {
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    /**
     * Nom de fichier sans chemin ni caractère spécial.
     */
    public static function safe_name($name) {
        $name = preg_replace('/[^\w.\-]+/u', '_', basename((string) $name));
        return trim($name, '.') !== '' ? $name : 'fichier';
    }

    public static function is_image($name) {
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, TRUE);
    }

    /**
     * Archive zip en mémoire : un fichier Markdown et des images sous img/.
     *
     * @param string $md_name
     * @param string $markdown
     * @param array  $images   [nom dans img/ => chemin du fichier]
     * @return string|false    Contenu de l'archive
     */
    public static function make_zip($md_name, $markdown, array $images) {
        $tmp = tempnam(sys_get_temp_dir(), 'nlzip');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== TRUE) {
            @unlink($tmp);
            return FALSE;
        }
        $zip->addFromString($md_name, $markdown);
        foreach ($images as $name => $path) {
            $zip->addFile($path, 'img/' . $name);
        }
        $zip->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    // ── Traitements déterministes du texte ────────────────────────────────

    /**
     * Découpe un Markdown en blocs séparés par des lignes vides ; un bloc de
     * code délimité n'est jamais coupé.
     *
     * @return string[]
     */
    public static function split_blocks($markdown) {
        $blocks = array();
        $current = array();
        $in_fence = FALSE;
        foreach (preg_split('/\R/', (string) $markdown) as $line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) $in_fence = !$in_fence;
            if (!$in_fence && trim($line) === '') {
                if ($current) $blocks[] = implode("\n", $current);
                $current = array();
                continue;
            }
            $current[] = $line;
        }
        if ($current) $blocks[] = implode("\n", $current);
        return $blocks;
    }

    /**
     * Citation Markdown d'un commentaire de l'administrateur.
     */
    public static function annotation_markdown($label, $content) {
        $lines = preg_split('/\R/', trim((string) $content));
        $out = '> **' . trim($label) . ' :** ' . array_shift($lines);
        foreach ($lines as $l) $out .= "\n> " . $l;
        return $out;
    }

    /**
     * Brouillon annoté : chaque commentaire est inséré sous son bloc.
     *
     * @param string $markdown
     * @param array  $annotations  lignes de newsletter_annotations (block_index, label, content)
     */
    public static function annotate($markdown, array $annotations) {
        if (!$annotations) return $markdown;
        $by_block = array();
        foreach ($annotations as $a) $by_block[(int) $a['block_index']][] = $a;
        $blocks = self::split_blocks($markdown);
        $out = array();
        $last = count($blocks) - 1;
        foreach ($blocks as $i => $block) {
            $out[] = $block;
            // Un commentaire au-delà du dernier bloc reste visible, en fin de document
            $extra = ($i === $last) ? array_filter($by_block, function ($k) use ($last) { return $k > $last; }, ARRAY_FILTER_USE_KEY) : array();
            foreach (array_merge($by_block[$i] ?? array(), ...array_values($extra)) as $a) {
                $out[] = self::annotation_markdown($a['label'], $a['content']);
            }
        }
        return implode("\n\n", $out) . "\n";
    }

    /**
     * Retire les notes internes d'un brouillon validé :
     * - les zones délimitées par <!-- BROUILLON --> … <!-- /BROUILLON --> et
     *   <!-- INTERNE --> … <!-- /INTERNE --> ;
     * - la section dont le titre contient « SECTION INTERNE », jusqu'au titre
     *   suivant de même niveau ou supérieur (ou la fin du document) ;
     * - les citations d'avertissement de brouillon (« > **BROUILLON … ») ;
     * - les notes de rédaction entre doubles accolades ({{ … }}) ;
     * - les commentaires HTML restants.
     */
    public static function remove_internal_notes($markdown) {
        $markdown = preg_replace('/<!--\s*(BROUILLON|INTERNE)\s*-->.*?<!--\s*\/\1\s*-->/su', '', $markdown);
        $markdown = self::strip_editorial_notes($markdown);
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
        $text = self::strip_html_comments(implode("\n", $out));
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        // Séparateurs horizontaux laissés orphelins en fin de document
        $text = preg_replace('/(\s*\n(-{3,}|\*{3,})\s*)+$/', '', $text);
        return rtrim($text) . "\n";
    }

    /**
     * Retire les notes de rédaction entre doubles accolades ({{ … }}).
     */
    public static function strip_editorial_notes($markdown) {
        return preg_replace('/\{\{.*?\}\}/su', '', (string) $markdown);
    }

    /**
     * Ajoute la mention de validation en fin de newsletter.
     */
    public static function append_validation($markdown, $mention) {
        return rtrim((string) $markdown) . "\n\n*" . trim($mention) . "*\n";
    }

    public static function strip_html_comments($markdown) {
        return preg_replace('/<!--.*?-->/s', '', (string) $markdown);
    }

    /**
     * Références d'images d'un Markdown (hors URL absolues et données).
     *
     * @return string[]  Chemins tels qu'écrits
     */
    public static function image_refs($markdown) {
        $refs = array();
        if (preg_match_all('/!\[[^\]]*\]\(\s*<?([^)\s>]+)>?(?:\s+"[^"]*")?\s*\)/u', (string) $markdown, $m)) {
            foreach ($m[1] as $ref) {
                if (!preg_match('#^([a-z][a-z0-9+.-]*:|/)#i', $ref)) $refs[] = $ref;
            }
        }
        return array_values(array_unique($refs));
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
     * Mentions entre crochets restant à compléter (hors liens, images,
     * commentaires HTML et notes de rédaction, retirés à la publication).
     *
     * @return string[]
     */
    public static function find_open_mentions($markdown) {
        $mentions = array();
        if (preg_match_all('/(!?)\[([^\[\]\n]*)\](\()?/u', self::strip_editorial_notes(self::strip_html_comments($markdown)), $all, PREG_SET_ORDER)) {
            foreach ($all as $m) {
                $is_link = isset($m[3]) && $m[3] === '(';
                if ($m[1] === '!' || $is_link) continue;
                $mentions[] = '[' . $m[2] . ']';
            }
        }
        return $mentions;
    }
}
