<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Secret_box — chiffrement symétrique des secrets conservés en base
 * (jetons de renouvellement Google).
 *
 * Chiffrement authentifié sodium (XSalsa20-Poly1305). La clé est propre à
 * l'installation : elle est générée à la première utilisation dans
 * application/config/agenda_google.php (fichier hors dépôt, droits restreints).
 * Un texte chiffré avec une autre clé est refusé au déchiffrement.
 *
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md (D5)
 */
class Secret_box
{
    const KEY_NAME = 'agenda_google_cle';

    /** @var string chemin du fichier de clé */
    private $key_file;

    /** @var string|null clé binaire chargée */
    private $key = null;

    /**
     * @param array $params 'key_file' : chemin du fichier de clé (tests)
     */
    public function __construct($params = array())
    {
        $this->key_file = !empty($params['key_file'])
            ? $params['key_file']
            : APPPATH . 'config/agenda_google.php';
    }

    /**
     * @param string $plain
     * @return string texte chiffré, encodé en base64
     * @throws RuntimeException si la clé ne peut être ni lue ni créée
     */
    public function encrypt($plain)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox((string) $plain, $nonce, $this->key()));
    }

    /**
     * @param string $blob texte rendu par encrypt()
     * @return string|false le texte clair, FALSE si le texte est altéré ou
     *                      chiffré avec une autre clé
     */
    public function decrypt($blob)
    {
        $raw = base64_decode((string) $blob, TRUE);
        if ($raw === FALSE || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return FALSE;
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key());
        return ($plain === FALSE) ? FALSE : $plain;
    }

    /**
     * Clé de l'installation, créée si elle n'existe pas encore.
     */
    private function key()
    {
        if ($this->key !== null) {
            return $this->key;
        }
        $key = $this->read_key();
        if ($key === null) {
            $this->create_key();
            $key = $this->read_key();
        }
        if ($key === null) {
            throw new RuntimeException('Secret_box: clé de chiffrement illisible (' . basename($this->key_file) . ')');
        }
        return $this->key = $key;
    }

    private function read_key()
    {
        if (!is_file($this->key_file)) {
            return null;
        }
        $content = (string) file_get_contents($this->key_file);
        if (!preg_match('/\$config\[\'' . self::KEY_NAME . '\'\]\s*=\s*\'([A-Za-z0-9+\/=]+)\'/', $content, $m)) {
            return null;
        }
        $key = base64_decode($m[1], TRUE);
        return ($key !== FALSE && strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) ? $key : null;
    }

    /**
     * Crée le fichier de clé. Ne remplace jamais un fichier existant : une clé
     * perdue rendrait illisibles tous les jetons déjà enregistrés.
     */
    private function create_key()
    {
        $content = "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');\n"
            . "/*\n * Clé de chiffrement des jetons Google Agenda, propre à cette installation.\n"
            . " * Générée automatiquement par GVV. Ne pas la modifier ni la publier : sans elle,\n"
            . " * les membres doivent relier à nouveau leur agenda.\n */\n"
            . "\$config['" . self::KEY_NAME . "'] = '" . base64_encode(sodium_crypto_secretbox_keygen()) . "';\n";
        $old_umask = umask(0027);
        $handle = @fopen($this->key_file, 'x');  // échoue si le fichier existe déjà
        umask($old_umask);
        if ($handle === FALSE) {
            return;
        }
        fwrite($handle, $content);
        fclose($handle);
        @chmod($this->key_file, 0640);
    }
}
