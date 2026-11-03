<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Parametres_store
 *
 * Accès aux paramètres déclarés dans config/parametres_registry.php :
 * droits selon le profil, lecture de la valeur en vigueur, validation et écriture
 * dans le bon support (table configuration ou fichier de configuration).
 * L'utilisateur de la page Configuration n'a pas à savoir où un paramètre est stocké.
 *
 * @see doc/prds/configuration_unifiee_prd.md
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Parametres_store {

    /** Rang des profils : un profil a les droits de ceux de rang inférieur. */
    const RANKS = array('ca' => 1, 'club-admin' => 2, 'dev' => 3);

    /** Langues proposées pour les paramètres saisis par langue. */
    const LANGUAGES = array('french', 'english', 'dutch');

    /** Langue de repli quand une valeur n'existe pas dans la langue demandée. */
    const FALLBACK_LANGUAGE = 'french';

    protected $CI;
    protected $registry;
    protected $tabs;

    /** Rang forcé (tests) ; NULL = calculé d'après l'utilisateur connecté. */
    protected $forced_rank = NULL;

    /** Rang calculé, mémorisé pour la durée de la requête. */
    protected $cached_rank = NULL;

    public function __construct($params = array()) {
        $this->CI = &get_instance();
        $this->CI->config->load('parametres_registry', TRUE);
        $this->registry = isset($params['registry'])
            ? $params['registry'] : $this->CI->config->item('parametres_registry', 'parametres_registry');
        $this->tabs = isset($params['tabs'])
            ? $params['tabs'] : $this->CI->config->item('parametres_tabs', 'parametres_registry');
        $this->CI->load->library('Config_file_writer');
    }

    // ------------------------------------------------------------------
    // Registre
    // ------------------------------------------------------------------

    public function registry() {
        return $this->registry;
    }

    public function tabs() {
        return $this->tabs;
    }

    public function definition($key) {
        return isset($this->registry[$key]) ? $this->registry[$key] : NULL;
    }

    /** Clés des paramètres d'un onglet, dans l'ordre du registre. */
    public function tab_keys($tab) {
        $keys = array();
        foreach ($this->registry as $key => $def) {
            if ($def['tab'] === $tab) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    // ------------------------------------------------------------------
    // Profils et droits
    // ------------------------------------------------------------------

    /** Force le rang de l'utilisateur (tests). NULL rétablit le calcul normal. */
    public function set_rank($rank) {
        $this->forced_rank = $rank;
    }

    /**
     * Rang de l'utilisateur connecté : 0 (aucun accès), 1 (CA), 2 (club-admin), 3 (dev_user).
     * Les profils se cumulent : on retient le plus élevé.
     */
    public function user_rank() {
        if ($this->forced_rank !== NULL) {
            return $this->forced_rank;
        }
        if ($this->cached_rank === NULL) {
            $this->cached_rank = $this->_compute_rank();
        }
        return $this->cached_rank;
    }

    /**
     * Calcul du rang (requêtes d'autorisation) ; fait une seule fois par requête.
     */
    private function _compute_rank() {
        if (!$this->CI->dx_auth->is_logged_in()) {
            return 0;
        }
        if (is_dev_user()) {
            return self::RANKS['dev'];
        }
        if (has_global_role('club-admin')) {
            return self::RANKS['club-admin'];
        }
        if (has_global_role('ca')) {
            return self::RANKS['ca'];
        }
        return 0;
    }

    private function _rank_of($profile) {
        return isset(self::RANKS[$profile]) ? self::RANKS[$profile] : PHP_INT_MAX;
    }

    public function can_read($key) {
        $def = $this->definition($key);
        return $def !== NULL && $this->user_rank() >= $this->_rank_of($def['read']);
    }

    public function can_write($key) {
        $def = $this->definition($key);
        return $def !== NULL && $this->user_rank() >= $this->_rank_of($def['write']);
    }

    /** Onglets dont l'utilisateur peut lire au moins un paramètre, dans l'ordre déclaré. */
    public function visible_tabs() {
        $visible = array();
        foreach ($this->tabs as $tab => $info) {
            foreach ($this->tab_keys($tab) as $key) {
                if ($this->can_read($key)) {
                    $visible[$tab] = $info;
                    break;
                }
            }
        }
        return $visible;
    }

    public function can_write_tab($tab) {
        foreach ($this->tab_keys($tab) as $key) {
            if ($this->can_write($key)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    // ------------------------------------------------------------------
    // Lecture
    // ------------------------------------------------------------------

    /**
     * Valeur en vigueur d'un paramètre.
     *
     * @param string      $key
     * @param string|null $lang langue, pour un paramètre saisi par langue
     * @return array value, is_default (rien n'est défini), updated_at, updated_by
     */
    public function get($key, $lang = NULL) {
        $def = $this->definition($key);
        $default = ($def && isset($def['default'])) ? $def['default'] : '';
        $result = array('value' => $default, 'is_default' => TRUE, 'updated_at' => NULL, 'updated_by' => NULL);
        if (!$def) {
            return $result;
        }

        if ($def['store'] === 'db') {
            $row = $this->_db_row($key, empty($def['per_lang']) ? NULL : $lang);
            if ($row) {
                $result = array(
                    'value'      => (string) $row['valeur'],
                    'is_default' => FALSE,
                    'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : NULL,
                    'updated_by' => isset($row['updated_by']) ? $row['updated_by'] : NULL,
                );
            }
            return $result;
        }

        $file = $this->_file_path($def['store']);
        $value = $this->CI->config_file_writer->read($file, $key, NULL);
        if ($value !== NULL) {
            $result['value'] = $value;
            $result['is_default'] = FALSE;
        }
        return $result;
    }

    /**
     * Valeur pour une langue, avec repli sur la langue par défaut.
     * Rend une chaîne vide si rien n'est défini.
     */
    public function get_with_fallback($key, $lang) {
        $res = $this->get($key, $lang);
        if ($res['is_default'] && $lang !== self::FALLBACK_LANGUAGE) {
            $res = $this->get($key, self::FALLBACK_LANGUAGE);
        }
        return $res;
    }

    /**
     * État d'un onglet : 'incomplete' si un paramètre obligatoire manque,
     * 'optional' si rien n'y est défini, 'complete' sinon.
     */
    public function tab_status($tab) {
        $any_defined = FALSE;
        foreach ($this->tab_keys($tab) as $key) {
            $def = $this->definition($key);
            $langs = empty($def['per_lang']) ? array(NULL) : self::LANGUAGES;
            $defined = FALSE;
            foreach ($langs as $lang) {
                $res = $this->get($key, $lang);
                if (!$res['is_default'] && $res['value'] !== '') {
                    $defined = TRUE;
                    break;
                }
            }
            if (!empty($def['required']) && !$defined) {
                return 'incomplete';
            }
            $any_defined = $any_defined || $defined;
        }
        return $any_defined ? 'complete' : 'optional';
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    /**
     * Valide et enregistre un paramètre.
     *
     * Pour un paramètre en base, une valeur vide supprime la valeur saisie :
     * le paramètre revient à sa valeur par défaut.
     *
     * @return array status ('saved', 'unchanged', 'error'), error (clé de langue), detail
     */
    public function save($key, $value, $lang = NULL) {
        $def = $this->definition($key);
        if (!$def) {
            return $this->_error('parametres_err_unknown');
        }
        if (!$this->can_write($key)) {
            return $this->_error('parametres_err_forbidden');
        }
        if (!empty($def['per_lang'])) {
            if (!in_array($lang, self::LANGUAGES, TRUE)) {
                return $this->_error('parametres_err_language');
            }
        } else {
            $lang = NULL;
        }

        $value = $this->_normalize($def, $value);

        $current = $this->get($key, $lang);
        if (!empty($def['secret']) && $value === '') {
            return array('status' => 'unchanged', 'error' => '', 'detail' => '');
        }
        if ((string) $current['value'] === (string) $value && !($current['is_default'] && $value !== '')) {
            return array('status' => 'unchanged', 'error' => '', 'detail' => '');
        }

        $error = $this->validate($def, $value);
        if ($error !== '') {
            return $this->_error($error);
        }

        if ($def['store'] === 'db') {
            $ok = $this->_db_write($key, $lang, $value);
            return $ok ? array('status' => 'saved', 'error' => '', 'detail' => '')
                       : $this->_error('parametres_err_db');
        }

        $file = $this->_file_path($def['store']);
        if (!$this->CI->config_file_writer->write($file, $key, $value)) {
            return $this->_error('parametres_err_file_' . $this->CI->config_file_writer->error,
                str_replace(FCPATH, '', $file));
        }
        $this->CI->config->set_item($key, $value);
        return array('status' => 'saved', 'error' => '', 'detail' => '');
    }

    /**
     * Contrôle une valeur selon le type et les règles du paramètre.
     *
     * @return string clé de langue du message d'erreur, ou chaîne vide si la valeur est valide
     */
    public function validate($def, $value) {
        $rules = isset($def['rules']) && $def['rules'] !== '' ? explode('|', $def['rules']) : array();
        if (!empty($def['required'])) {
            $rules[] = 'required';
        }
        if ($def['type'] === 'email') {
            $rules[] = 'valid_email';
        } elseif ($def['type'] === 'url') {
            $rules[] = 'valid_url';
        } elseif ($def['type'] === 'int') {
            $rules[] = 'integer';
        }

        $str = is_bool($value) ? ($value ? '1' : '') : (string) $value;
        foreach ($rules as $rule) {
            $arg = NULL;
            if (preg_match('/^(\w+)\[(.*)\]$/', $rule, $m)) {
                $rule = $m[1];
                $arg = $m[2];
            }
            switch ($rule) {
                case 'required':
                    if (trim($str) === '') return 'parametres_err_required';
                    break;
                case 'max_length':
                    if (mb_strlen($str) > (int) $arg) return 'parametres_err_max_length';
                    break;
                case 'valid_email':
                    if ($str !== '' && !filter_var($str, FILTER_VALIDATE_EMAIL)) return 'parametres_err_email';
                    break;
                case 'valid_url':
                    if ($str !== '' && !preg_match('#^https?://[^\s]+$#i', $str)) return 'parametres_err_url';
                    break;
                case 'integer':
                    if ($str !== '' && !preg_match('/^-?\d+$/', $str)) return 'parametres_err_integer';
                    break;
            }
        }
        return '';
    }

    // ------------------------------------------------------------------
    // Interne
    // ------------------------------------------------------------------

    private function _error($error, $detail = '') {
        return array('status' => 'error', 'error' => $error, 'detail' => $detail);
    }

    private function _normalize($def, $value) {
        if ($def['type'] === 'bool') {
            return (bool) $value;
        }
        if ($def['type'] === 'int') {
            $value = trim((string) $value);
            return preg_match('/^-?\d+$/', $value) ? (int) $value : $value;
        }
        $value = (string) $value;
        // Les textes longs gardent leurs espaces internes ; fins de ligne normalisées.
        if ($def['type'] === 'markdown' || $def['type'] === 'textarea') {
            return rtrim(str_replace("\r\n", "\n", $value));
        }
        return trim($value);
    }

    private function _file_path($store) {
        $name = substr($store, strlen('file:'));
        return APPPATH . 'config/' . basename($name) . '.php';
    }

    private function _db_row($key, $lang) {
        $this->CI->db->from('configuration')->where('cle', $key)->where('club IS NULL', NULL, FALSE);
        if ($lang === NULL) {
            $this->CI->db->where('lang IS NULL', NULL, FALSE);
        } else {
            $this->CI->db->where('lang', $lang);
        }
        $query = $this->CI->db->order_by('id', 'ASC')->limit(1)->get();
        return ($query && $query->num_rows() > 0) ? $query->row_array() : NULL;
    }

    private function _db_write($key, $lang, $value) {
        $row = $this->_db_row($key, $lang);
        $now = date('Y-m-d H:i:s');
        $user = $this->CI->dx_auth->is_logged_in() ? $this->CI->dx_auth->get_username() : NULL;

        if ($value === '') {
            return $row ? (bool) $this->CI->db->where('id', $row['id'])->delete('configuration') : TRUE;
        }
        if ($row) {
            return (bool) $this->CI->db->where('id', $row['id'])->update('configuration', array(
                'valeur' => $value, 'updated_at' => $now, 'updated_by' => $user,
            ));
        }
        return (bool) $this->CI->db->insert('configuration', array(
            'cle' => $key, 'valeur' => $value, 'lang' => $lang, 'club' => NULL,
            'categorie' => 'parametres',
            'created_at' => $now, 'updated_at' => $now, 'created_by' => $user, 'updated_by' => $user,
        ));
    }
}

/* End of file Parametres_store.php */
