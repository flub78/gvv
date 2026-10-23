<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Newsletter_imap
 *
 * Accès en lecture seule à une boîte IMAP via PHP cURL (extension imap_*
 * non requise). Utilisé pour la collecte des sources email de l'agent IA
 * et pour la page de validation d'infrastructure.
 *
 * Toutes les méthodes retournent FALSE en cas d'erreur ; l'erreur est
 * disponible via last_error().
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_imap {

    private $host;
    private $port;
    private $user;
    private $password;
    private $ssl;
    private $mailbox;
    private $last_error = '';

    const TIMEOUT_CONNECT = 10;
    const TIMEOUT_TOTAL   = 30;

    public function __construct($config = array()) {
        if (!empty($config)) {
            $this->init($config);
        }
    }

    public function init($config) {
        $this->host     = $config['newsletter_imap_host']    ?? '';
        $this->port     = (int) ($config['newsletter_imap_port']    ?? 993);
        $this->user     = $config['newsletter_imap_user']    ?? '';
        $this->password = self::decode_password($config['newsletter_imap_password'] ?? '');
        $this->ssl      = (bool) ($config['newsletter_imap_ssl']     ?? true);
        $this->mailbox  = $config['newsletter_imap_mailbox'] ?? 'INBOX';
    }

    public function last_error() {
        return $this->last_error;
    }

    // ── Encodage du mot de passe (obfuscation base64) ─────────────────────

    public static function encode_password($plain) {
        return base64_encode($plain);
    }

    public static function decode_password($encoded) {
        if (empty($encoded)) return '';
        $decoded = base64_decode($encoded, true);
        return $decoded !== false ? $decoded : $encoded;
    }

    // ── URL de base ───────────────────────────────────────────────────────

    private function base_url($uid = null) {
        $scheme = $this->ssl ? 'imaps' : 'imap';
        $url = "{$scheme}://{$this->host}:{$this->port}/{$this->mailbox}";
        if ($uid !== null) {
            $url .= ";UID={$uid}";
        }
        return $url;
    }

    // ── Requête cURL IMAP ─────────────────────────────────────────────────

    private function curl($url, $command = null) {
        if (!function_exists('curl_init')) {
            $this->last_error = 'Extension cURL non disponible.';
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_USERNAME,        $this->user);
        curl_setopt($ch, CURLOPT_PASSWORD,        $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  self::TIMEOUT_CONNECT);
        curl_setopt($ch, CURLOPT_TIMEOUT,         self::TIMEOUT_TOTAL);
        if ($command !== null) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $command);
        }
        $output = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($output === false) {
            $this->last_error = $error ?: 'Erreur cURL inconnue.';
            return false;
        }
        return $output;
    }

    // ── API publique ──────────────────────────────────────────────────────

    /**
     * Teste la connexion et l'authentification.
     *
     * @return bool
     */
    public function test_connection() {
        if (empty($this->host) || empty($this->user)) {
            $this->last_error = 'Hôte ou utilisateur non configuré.';
            return false;
        }
        // Une requête sans commande retourne la liste des messages (ou vide)
        // et échoue si l'authentification est incorrecte.
        $result = $this->curl($this->base_url());
        return $result !== false;
    }

    /**
     * Retourne le nombre de messages reçus depuis $date.
     *
     * @param  string $date  Format 'd-M-Y' IMAP, ex. '01-Jan-2026'
     * @return int|false
     */
    public function count_since($date) {
        // UID SEARCH retourne de vrais UIDs (pas des numéros de séquence)
        $result = $this->curl($this->base_url(), 'UID SEARCH SINCE ' . $date);
        if ($result === false) return false;
        $uids = $this->parse_search_uids($result);
        return count($uids);
    }

    /**
     * Retourne les UIDs des messages reçus depuis $date, limités à $limit.
     *
     * @param  string $date
     * @param  int    $limit
     * @return int[]|false
     */
    public function search_since($date, $limit = 50) {
        $result = $this->curl($this->base_url(), 'UID SEARCH SINCE ' . $date);
        if ($result === false) return false;
        $uids = $this->parse_search_uids($result);
        return array_slice(array_reverse($uids), 0, $limit); // plus récents en premier
    }

    /**
     * Retourne les entêtes (sujet, expéditeur, date) d'une liste de UIDs.
     * Filtre optionnel sur le sujet (insensible à la casse, sans accents).
     *
     * @param  int[]  $uids
     * @param  string $subject_pattern  Chaîne à chercher dans le sujet (vide = tous)
     * @return array[]|false  Chaque entrée : ['uid', 'subject', 'from', 'date']
     */
    public function fetch_headers(array $uids, $subject_pattern = '') {
        if (empty($uids)) return array();
        $results = array();
        foreach ($uids as $uid) {
            // Utiliser la forme URL native libcurl ;SECTION=HEADER
            // (CURLOPT_CUSTOMREQUEST ne retourne pas le contenu des littéraux IMAP)
            $raw = $this->curl($this->base_url($uid) . ';SECTION=HEADER');
            if ($raw === false) continue;
            $header = $this->parse_header_fields($raw);
            $header['uid'] = $uid;
            if (!empty($subject_pattern)) {
                $haystack = strtolower($this->remove_accents($header['subject']));
                $needle   = strtolower($this->remove_accents($subject_pattern));
                if (strpos($haystack, $needle) === false) continue;
            }
            $results[] = $header;
        }
        return $results;
    }

    /**
     * Retourne le corps texte d'un message (BODY[TEXT] ou BODY[1]).
     *
     * @param  int $uid
     * @return string|false
     */
    public function fetch_body($uid) {
        // ;SECTION=TEXT retourne directement le corps, sans enveloppe IMAP
        $raw = $this->curl($this->base_url($uid) . ';SECTION=TEXT');
        if ($raw === false) return false;
        return $raw;
    }

    /**
     * Retourne la structure MIME d'un message (liste des parties avec nom et taille).
     *
     * @param  int $uid
     * @return array[]|false  Chaque entrée : ['part', 'name', 'encoding', 'size']
     */
    public function fetch_structure($uid) {
        $raw = $this->curl($this->base_url(), 'UID FETCH ' . $uid . ' BODYSTRUCTURE');
        if ($raw === false) return false;
        return $this->parse_bodystructure($raw);
    }

    /**
     * Retourne le contenu binaire d'une partie MIME (ex. "2" pour la 2e partie).
     *
     * @param  int    $uid
     * @param  string $part  Numéro de partie IMAP (ex. "1", "2", "1.1")
     * @return string|false
     */
    public function fetch_part($uid, $part) {
        // ;SECTION=2 retourne directement le contenu de la partie MIME
        $raw = $this->curl($this->base_url($uid) . ';SECTION=' . urlencode($part));
        if ($raw === false) return false;
        // Les pièces jointes binaires sont base64-encodées dans IMAP ;
        // on tente le décodage (les données déjà binaires sont laissées intactes).
        $decoded = base64_decode(str_replace(array("\r", "\n"), '', $raw), true);
        return ($decoded !== false && strlen($decoded) > 0) ? $decoded : $raw;
    }

    // ── Parsers internes ──────────────────────────────────────────────────

    /**
     * Extrait les UIDs d'une réponse IMAP SEARCH.
     * Format attendu : "* SEARCH 1 2 3 ..."
     */
    private function parse_search_uids($raw) {
        if (preg_match('/\* SEARCH([\d\s]*)/i', $raw, $m)) {
            $nums = preg_split('/\s+/', trim($m[1]));
            return array_filter(array_map('intval', $nums));
        }
        return array();
    }

    /**
     * Extrait Subject, From, Date d'une réponse FETCH HEADER.FIELDS.
     */
    private function parse_header_fields($raw) {
        $subject = '';
        $from    = '';
        $date    = '';
        foreach (explode("\n", $raw) as $line) {
            $line = rtrim($line);
            if (stripos($line, 'Subject:') === 0) {
                $subject = trim(substr($line, 8));
                $subject = $this->decode_mime_header($subject);
            } elseif (stripos($line, 'From:') === 0) {
                $from = $this->decode_mime_header(trim(substr($line, 5)));
            } elseif (stripos($line, 'Date:') === 0) {
                $date = trim(substr($line, 5));
            }
        }
        return array('subject' => $subject, 'from' => $from, 'date' => $date);
    }

    /**
     * Décode un en-tête encodé en RFC 2047 (=?charset?encoding?text?=).
     */
    private function decode_mime_header($str) {
        if (strpos($str, '=?') === false) return $str;
        if (function_exists('iconv_mime_decode')) {
            return iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        }
        // Décodage minimal sans iconv
        return preg_replace_callback('/=\?([^?]+)\?([BbQq])\?([^?]*)\?=/', function($m) {
            $charset  = $m[1];
            $encoding = strtoupper($m[2]);
            $text     = $m[3];
            if ($encoding === 'B') {
                $text = base64_decode($text);
            } elseif ($encoding === 'Q') {
                $text = quoted_printable_decode(str_replace('_', ' ', $text));
            }
            if (strtoupper($charset) !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $charset);
            }
            return $text;
        }, $str);
    }

    /**
     * Parse minimalement une réponse BODYSTRUCTURE pour en extraire les pièces jointes.
     * Retourne les parties avec un nom de fichier (pièces jointes).
     */
    private function parse_bodystructure($raw) {
        $parts = array();
        // RFC 3501 : "name" "filename.pdf" (les deux tokens entre guillemets)
        // Certains serveurs envoient : name "filename.pdf" (token nu)
        // On capture les deux formes :
        //   "?name"?\s+"filename"  →  avec ou sans guillemets autour de "name"
        //   "?filename"?\s+"value" →  même chose pour "filename"
        $patterns = array(
            '/"name"\s+"([^"]+)"/i',       // forme RFC : "name" "valeur"
            '/\bname\s+"([^"]+)"/i',        // forme nue : name "valeur"
            '/"filename"\s+"([^"]+)"/i',    // forme RFC : "filename" "valeur"
            '/\bfilename\s+"([^"]+)"/i',    // forme nue : filename "valeur"
        );
        $names = array();
        foreach ($patterns as $pat) {
            if (preg_match_all($pat, $raw, $m)) {
                $names = array_merge($names, $m[1]);
            }
            if (!empty($names)) break; // premier pattern qui trouve quelque chose
        }
        // Dédoublonner (certains serveurs répètent le nom dans name ET filename)
        $names = array_values(array_unique($names));
        foreach ($names as $i => $name) {
            // Numéro de partie : la première pièce jointe est souvent la partie 2
            // (partie 1 = corps texte/html), les suivantes sont 3, 4, …
            $parts[] = array(
                'part' => (string) ($i + 2),
                'name' => $name,
            );
        }
        return $parts;
    }

    private function remove_accents($str) {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) ?: $str;
    }
}
