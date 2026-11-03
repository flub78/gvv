<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Config_file_writer
 *
 * Lit et modifie une clé $config['...'] d'un fichier de configuration PHP sans
 * toucher au reste du fichier (autres clés, commentaires).
 * Reprend la méthode de l'installeur (install/index.php, cfg_read/cfg_write).
 * Corrections par rapport à celui-ci : la valeur ne peut pas être interprétée comme
 * référence arrière par preg_replace, une clé ajoutée l'est avant une éventuelle
 * balise de fermeture « ?> », et le fichier produit est vérifié syntaxiquement
 * avant d'être écrit.
 *
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Config_file_writer {

    /** Dernière erreur rencontrée (chaîne vide si aucune). */
    public $error = '';

    /**
     * Expression d'une affectation de la clé, ancrée en début de ligne pour
     * ignorer les lignes commentées.
     */
    private function _pattern($key) {
        return '/^\$config\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*'
            . "('(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\"|[^;]*)"
            . '\s*;/m';
    }

    /**
     * Valeur de la clé, ou $default si le fichier ou la clé n'existent pas.
     * Les chaînes sont rendues décodées, les booléens en bool, les entiers en int.
     *
     * @param string $file
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function read($file, $key, $default = NULL) {
        if (!is_file($file)) {
            return $default;
        }
        return $this->read_content(file_get_contents($file), $key, $default);
    }

    /**
     * Comme read(), sur le contenu d'un fichier déjà lu (lecture de plusieurs clés
     * sans relire le fichier).
     *
     * @param string $content
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function read_content($content, $key, $default = NULL) {
        if (!preg_match($this->_pattern($key), (string) $content, $m)) {
            return $default;
        }
        $raw = trim($m[1]);
        if ($raw === '') {
            return $default;
        }
        if ($raw[0] === "'") {
            return strtr(substr($raw, 1, -1), array("\\\\" => "\\", "\\'" => "'"));
        }
        if ($raw[0] === '"') {
            return stripcslashes(substr($raw, 1, -1));
        }
        $lower = strtolower($raw);
        if ($lower === 'true' || $lower === 'false') {
            return $lower === 'true';
        }
        if (preg_match('/^-?\d+$/', $raw)) {
            return (int) $raw;
        }
        return $raw;
    }

    /**
     * Représentation PHP d'une valeur : chaîne entre guillemets simples, booléen ou entier.
     */
    public function literal($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        return "'" . str_replace(array("\\", "'"), array("\\\\", "\\'"), (string) $value) . "'";
    }

    /**
     * Crée un fichier de configuration vide (en-tête CodeIgniter seul) s'il n'existe
     * pas, pour qu'une première clé puisse y être écrite. Le fichier n'est pas
     * copié depuis son modèle *.example.php, dont les valeurs d'exemple
     * changeraient le comportement de GVV (par exemple le mode d'envoi des e-mails).
     *
     * @return bool TRUE si le fichier existe ou a été créé, FALSE avec le motif dans $this->error
     */
    public function create($file) {
        $this->error = '';
        if (is_file($file)) {
            return TRUE;
        }
        if (!is_writable(dirname($file))) {
            $this->error = "missing_file";
            return FALSE;
        }
        $content = "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');\n"
            . "/* Créé par la page Configuration de GVV. */\n";
        if (file_put_contents($file, $content, LOCK_EX) === FALSE) {
            $this->error = "write_failed";
            return FALSE;
        }
        @chmod($file, 0664);
        return TRUE;
    }

    /**
     * Écrit la clé : remplace son affectation si elle existe, l'ajoute sinon.
     *
     * @param string $file
     * @param string $key
     * @param mixed  $value  chaîne, booléen ou entier
     * @return bool FALSE en cas d'échec, avec le motif dans $this->error
     */
    public function write($file, $key, $value) {
        $this->error = '';
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $key)) {
            $this->error = "invalid_key";
            return FALSE;
        }
        if (!is_file($file)) {
            $this->error = "missing_file";
            return FALSE;
        }
        if (!is_writable($file)) {
            $this->error = "not_writable";
            return FALSE;
        }

        $content = file_get_contents($file);
        $line = "\$config['" . $key . "'] = " . $this->literal($value) . ";";

        $count = 0;
        $out = preg_replace_callback($this->_pattern($key), function () use ($line) {
            return $line;
        }, $content, 1, $count);

        if ($count === 0) {
            $out = $this->_append($content, $line);
        }

        if (!$this->_valid_php($out)) {
            $this->error = "syntax_error";
            return FALSE;
        }

        if (file_put_contents($file, $out, LOCK_EX) === FALSE) {
            $this->error = "write_failed";
            return FALSE;
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, TRUE);
        }
        return TRUE;
    }

    /**
     * Ajoute une ligne en fin de fichier, avant une éventuelle balise « ?> » finale.
     */
    private function _append($content, $line) {
        if (preg_match('/\?>\s*$/', $content)) {
            return preg_replace_callback('/\?>\s*$/', function () use ($line) {
                return $line . "\n?>\n";
            }, $content, 1);
        }
        return rtrim($content) . "\n" . $line . "\n";
    }

    /**
     * Vérifie qu'un contenu est du PHP syntaxiquement valide.
     */
    private function _valid_php($content) {
        try {
            token_get_all($content, TOKEN_PARSE);
            return TRUE;
        } catch (\ParseError $e) {
            return FALSE;
        }
    }
}

/* End of file Config_file_writer.php */
