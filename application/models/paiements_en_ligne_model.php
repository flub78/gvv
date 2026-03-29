<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Paiements En Ligne Model
 *
 * Gère les transactions de paiement en ligne (HelloAsso, etc.)
 * et la lecture de la configuration par section.
 *
 * Tables : paiements_en_ligne, paiements_en_ligne_config
 *
 * PHPUnit tests:
 *   phpunit --configuration phpunit_mysql.xml application/tests/mysql/PaiementsEnLigneModelTest.php
 */
class Paiements_en_ligne_model extends CI_Model {

    public $table = 'paiements_en_ligne';
    private $sensitive_config_keys = array('client_secret', 'webhook_secret');

    public function __construct() {
        parent::__construct();
    }

    // =========================================================================
    // Transactions
    // =========================================================================

    /**
     * Crée une nouvelle transaction en statut "pending".
     *
     * @param  array $data  Champs : user_id, montant, plateforme, club,
     *                      transaction_id (optionnel), ecriture_id (optionnel),
     *                      metadata (JSON string, optionnel)
     * @return int|false    ID inséré ou false en cas d'erreur
     */
    public function create_transaction(array $data) {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'user_id'        => (int) $data['user_id'],
            'montant'        => (float) $data['montant'],
            'plateforme'     => $data['plateforme'],
            'club'           => (int) $data['club'],
            'statut'         => 'pending',
            'date_demande'   => $now,
            'transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
            'ecriture_id'    => isset($data['ecriture_id'])    ? (int) $data['ecriture_id'] : null,
            'metadata'       => isset($data['metadata'])       ? $data['metadata'] : null,
            'commission'     => isset($data['commission'])     ? (float) $data['commission'] : 0.00,
            'created_at'     => $now,
            'updated_at'     => $now,
            'created_by'     => isset($data['created_by'])     ? $data['created_by'] : null,
            'updated_by'     => isset($data['created_by'])     ? $data['created_by'] : null,
        );

        $this->db->insert($this->table, $row);
        $id = $this->db->insert_id();
        return $id ? $id : false;
    }

    /**
     * Met à jour le statut d'une transaction identifiée par son ID externe HelloAsso.
     *
     * @param  string $transaction_id  ID HelloAsso
     * @param  string $status          'pending'|'completed'|'failed'|'cancelled'
     * @param  string $metadata        JSON supplémentaire (optionnel)
     * @return bool
     */
    public function update_transaction_status($transaction_id, $status, $metadata = null) {
        $allowed = array('pending', 'completed', 'failed', 'cancelled');
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $update = array(
            'statut'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($status === 'completed') {
            $update['date_paiement'] = date('Y-m-d H:i:s');
        }
        if ($metadata !== null) {
            $update['metadata'] = $metadata;
        }

        $this->db->where('transaction_id', $transaction_id)->update($this->table, $update);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Récupère une transaction par son ID externe (HelloAsso order_id).
     *
     * @param  string $transaction_id
     * @return array|false
     */
    public function get_by_transaction_id($transaction_id) {
        $row = $this->db
            ->where('transaction_id', $transaction_id)
            ->get($this->table)
            ->row_array();
        return $row ?: false;
    }

    /**
     * Récupère une transaction par son ID interne GVV.
     *
     * @param  int $id
     * @return array|false
     */
    public function get_by_id($id) {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
        return $row ?: false;
    }

    /**
     * Liste des transactions avec filtres optionnels.
     *
     * @param  array $filters  Clés : user_id, statut, club, date_from, date_to
     * @return array
     */
    public function get_transactions(array $filters = array()) {
        $this->db->from($this->table);

        if (!empty($filters['user_id'])) {
            $this->db->where('user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['statut'])) {
            $this->db->where('statut', $filters['statut']);
        }
        if (!empty($filters['club'])) {
            $this->db->where('club', (int) $filters['club']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('date_demande >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('date_demande <=', $filters['date_to']);
        }

        $this->db->order_by('date_demande', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Retourne les transactions "pending" créées il y a plus de $minutes minutes.
     * Utilisé par les éventuels processus de nettoyage.
     *
     * @param  int $minutes
     * @return array
     */
    public function get_pending_transactions($minutes = 30) {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        return $this->db
            ->where('statut', 'pending')
            ->where('date_demande <', $threshold)
            ->order_by('date_demande', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Lit une valeur de configuration pour une plateforme et une section.
     *
     * @param  string $plateforme  ex. 'helloasso'
     * @param  string $key
     * @param  int    $club_id
     * @return string|false        La valeur ou false si absente
     */
    public function get_config($plateforme, $key, $club_id) {
        $row = $this->db
            ->select('param_value')
            ->where('plateforme', $plateforme)
            ->where('param_key', $key)
            ->where('club', (int) $club_id)
            ->get('paiements_en_ligne_config')
            ->row_array();
        if (!$row) {
            return false;
        }

        $value = $row['param_value'];
        if (!$this->_is_sensitive_key($key)) {
            return $value;
        }

        $decrypted = $this->_decrypt_sensitive_value($value);
        if ($decrypted === false) {
            return false;
        }

        // Migration progressive: réécrire en chiffré si la valeur est encore en clair.
        if (!$this->_is_encrypted_value($value) && $decrypted !== '') {
            $this->upsert_config($plateforme, $key, $decrypted, (int) $club_id, 'system');
        }

        return $decrypted;
    }

    /**
     * Retourne toutes les clés de configuration d'une plateforme/section.
     * Les clés sensibles sont déchiffrées avant retour.
     *
     * @param string $plateforme
     * @param int    $club_id
     * @return array
     */
    public function get_all_config($plateforme, $club_id) {
        $query = $this->db
            ->where('plateforme', $plateforme)
            ->where('club', (int) $club_id)
            ->get('paiements_en_ligne_config');

        $config = array();
        foreach ($query->result_array() as $row) {
            $key = $row['param_key'];
            $value = $row['param_value'];

            if ($this->_is_sensitive_key($key)) {
                $decrypted = $this->_decrypt_sensitive_value($value);
                if ($decrypted === false) {
                    $decrypted = '';
                }
                $config[$key] = $decrypted;

                if (!$this->_is_encrypted_value($value) && $decrypted !== '') {
                    $this->upsert_config($plateforme, $key, $decrypted, (int) $club_id, 'system');
                }
                continue;
            }

            $config[$key] = $value;
        }

        return $config;
    }

    /**
     * Insert ou update une clé de configuration (upsert).
     * Chiffre automatiquement les clés sensibles avant stockage.
     *
     * @param string $plateforme
     * @param string $key
     * @param string $value
     * @param int    $club_id
     * @param string $username
     * @return bool
     */
    public function upsert_config($plateforme, $key, $value, $club_id, $username) {
        $stored_value = (string) $value;

        if ($this->_is_sensitive_key($key) && $stored_value !== '') {
            $encrypted = $this->_encrypt_sensitive_value($stored_value);
            if ($encrypted === false) {
                return false;
            }
            $stored_value = $encrypted;
        }

        $exists = $this->db
            ->where('plateforme', $plateforme)
            ->where('club', (int) $club_id)
            ->where('param_key', $key)
            ->count_all_results('paiements_en_ligne_config');

        $now = date('Y-m-d H:i:s');

        if ($exists) {
            $this->db
                ->where('plateforme', $plateforme)
                ->where('club', (int) $club_id)
                ->where('param_key', $key)
                ->update('paiements_en_ligne_config', array(
                    'param_value' => $stored_value,
                    'updated_at'  => $now,
                    'updated_by'  => $username,
                ));
            return true;
        }

        $this->db->insert('paiements_en_ligne_config', array(
            'plateforme'  => $plateforme,
            'club'        => (int) $club_id,
            'param_key'   => $key,
            'param_value' => $stored_value,
            'created_at'  => $now,
            'updated_at'  => $now,
            'created_by'  => $username,
            'updated_by'  => $username,
        ));
        return true;
    }

    private function _is_sensitive_key($key) {
        return in_array($key, $this->sensitive_config_keys, true);
    }

    private function _is_encrypted_value($value) {
        return is_string($value) && strpos($value, 'enc:v1:') === 0;
    }

    private function _encrypt_sensitive_value($plaintext) {
        $key = $this->_get_crypto_key();
        if ($key === false) {
            log_message('error', 'Paiements_en_ligne_model: missing helloasso crypto key');
            return false;
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''
        );

        if ($ciphertext === false) {
            log_message('error', 'Paiements_en_ligne_model: openssl_encrypt failed');
            return false;
        }

        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    private function _decrypt_sensitive_value($stored_value) {
        if ($stored_value === '' || $stored_value === null) {
            return '';
        }

        if (!$this->_is_encrypted_value($stored_value)) {
            return $stored_value;
        }

        $payload = base64_decode(substr($stored_value, 7), true);
        if ($payload === false || strlen($payload) < 28) {
            log_message('error', 'Paiements_en_ligne_model: invalid encrypted payload');
            return false;
        }

        $key = $this->_get_crypto_key();
        if ($key === false) {
            log_message('error', 'Paiements_en_ligne_model: missing helloasso crypto key for decrypt');
            return false;
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $ciphertext = substr($payload, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''
        );

        if ($plaintext === false) {
            log_message('error', 'Paiements_en_ligne_model: openssl_decrypt failed');
            return false;
        }

        return $plaintext;
    }

    private function _get_crypto_key() {
        $env_key = getenv('GVV_HELLOASSO_CRYPTO_KEY');
        if (!empty($env_key)) {
            return hash('sha256', $env_key, true);
        }

        $config_file = APPPATH . 'config/helloasso_crypto.php';
        if (file_exists($config_file)) {
            $this->load->config('helloasso_crypto');
            $cfg_key = $this->config->item('helloasso_crypto_key');
            if (!empty($cfg_key)) {
                return hash('sha256', $cfg_key, true);
            }
        }

        return false;
    }
}
