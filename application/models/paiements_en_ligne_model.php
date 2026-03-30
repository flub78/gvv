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
 *   phpunit --configuration phpunit_mysql.xml application/tests/mysql/PaiementsEnLigneWebhookTest.php
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
     * Liste des transactions avec jointure membres (nom du pilote).
     * Utilisée par la vue liste trésorier (EF4).
     *
     * @param  array $filters  Clés : user_id, statut, club, plateforme, date_from, date_to
     * @return array           Chaque ligne ajoute : mprenom, mnom (depuis membres)
     */
    public function get_transactions_with_user(array $filters = array()) {
        $this->db
            ->select('p.*, m.mprenom, m.mnom, u.username')
            ->from($this->table . ' p')
            ->join('users u',     'u.id = p.user_id',     'left')
            ->join('membres m',   'm.mlogin = u.username', 'left');

        if (!empty($filters['user_id'])) {
            $this->db->where('p.user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['statut'])) {
            $this->db->where('p.statut', $filters['statut']);
        }
        if (!empty($filters['club'])) {
            $this->db->where('p.club', (int) $filters['club']);
        }
        if (!empty($filters['plateforme'])) {
            $this->db->where('p.plateforme', $filters['plateforme']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('p.date_demande >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('p.date_demande <=', $filters['date_to'] . ' 23:59:59');
        }

        $this->db->order_by('p.date_demande', 'DESC');
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
    // Traitement webhook HelloAsso (EF2)
    // =========================================================================

    /**
     * Traite un événement Order HelloAsso : idempotence, vérification du statut
     * de paiement, création des écritures comptables, mise à jour de la transaction.
     *
     * Doit être appelé APRÈS vérification de la signature HMAC (côté contrôleur).
     *
     * @param  array $order_data  Contenu de event['data'] (payload décodé)
     * @param  int   $club_id     Section cible
     * @return array [
     *   'ok'          => bool,
     *   'status'      => 'completed'|'already_completed'|'failed'|'error',
     *   'ecriture_id' => int|null,
     *   'transaction' => array|null,
     *   'error'       => string|null,
     * ]
     */
    public function process_order_event(array $order_data, $club_id)
    {
        // ── 1. Décoder les metadata ──────────────────────────────────────────
        $raw_meta = isset($order_data['metadata']) ? $order_data['metadata'] : null;
        if (is_string($raw_meta)) {
            $raw_meta = json_decode($raw_meta, true);
        }
        if (!is_array($raw_meta)) {
            return $this->_webhook_error('Metadata manquant ou invalide dans le payload HelloAsso');
        }

        $gvv_txid = isset($raw_meta['gvv_transaction_id'])
            ? (string) $raw_meta['gvv_transaction_id']
            : null;
        if (!$gvv_txid) {
            return $this->_webhook_error('gvv_transaction_id absent des metadata');
        }

        // ── 2. Charger la transaction GVV ────────────────────────────────────
        $transaction = $this->get_by_transaction_id($gvv_txid);
        if (!$transaction) {
            return $this->_webhook_error('Transaction introuvable : ' . $gvv_txid);
        }

        // ── 3. Idempotence ───────────────────────────────────────────────────
        if ($transaction['statut'] === 'completed') {
            return array(
                'ok'          => true,
                'status'      => 'already_completed',
                'ecriture_id' => $transaction['ecriture_id'],
                'transaction' => $transaction,
            );
        }

        // ── 4. Vérifier le statut de paiement HelloAsso ──────────────────────
        $payment_state = $this->_extract_payment_state($order_data);
        if ($payment_state !== 'Authorized') {
            $this->update_transaction_status($gvv_txid, 'failed');
            return array(
                'ok'          => true,
                'status'      => 'failed',
                'error'       => 'Paiement non autorisé : state=' . $payment_state,
                'transaction' => $transaction,
            );
        }

        // ── 5. Créer les écritures comptables ────────────────────────────────
        $type   = isset($raw_meta['type']) ? $raw_meta['type'] : '';
        $result = $this->_create_ecritures($transaction, $raw_meta, (int) $club_id, $type);

        if (!$result['ok']) {
            // Erreur de configuration ou DB : marquer failed, remonter 200 à HA
            $this->update_transaction_status($gvv_txid, 'failed');
            return array(
                'ok'          => true,
                'status'      => 'failed',
                'error'       => $result['error'],
                'transaction' => $transaction,
            );
        }

        // ── 6. Marquer completed ─────────────────────────────────────────────
        $ecriture_id = $result['ecriture_id'];
        $now = date('Y-m-d H:i:s');
        $this->db->where('transaction_id', $gvv_txid)->update($this->table, array(
            'statut'        => 'completed',
            'ecriture_id'   => $ecriture_id,
            'date_paiement' => $now,
            'metadata'      => json_encode(array_merge($raw_meta, array(
                'processed_at' => $now,
                'ecriture_id'  => $ecriture_id,
            ))),
            'updated_at'    => $now,
            'updated_by'    => 'helloasso_webhook',
        ));

        return array(
            'ok'          => true,
            'status'      => 'completed',
            'ecriture_id' => $ecriture_id,
            'transaction' => $transaction,
            'type'        => $type,
            'metadata'    => $raw_meta,
        );
    }

    // ── Helpers webhook (privés) ──────────────────────────────────────────────

    /**
     * Extrait l'état du premier paiement dans le payload HelloAsso Order.
     * Gère deux structures possibles : payments[] en haut ou items[].payments[].
     */
    private function _extract_payment_state(array $order_data)
    {
        if (!empty($order_data['payments'][0]['state'])) {
            return $order_data['payments'][0]['state'];
        }
        if (!empty($order_data['items'][0]['payments'][0]['state'])) {
            return $order_data['items'][0]['payments'][0]['state'];
        }
        return 'Unknown';
    }

    /**
     * Dispatch la création d'écriture selon le type de paiement.
     *
     * @return array ['ok' => bool, 'ecriture_id' => int|null, 'error' => string|null]
     */
    private function _create_ecritures(array $transaction, array $meta, $club_id, $type)
    {
        // S'assurer que les modèles sont chargés (patterns pour tests PHPUnit sans contrôleur)
        $CI = get_instance();
        $CI->load->model('ecritures_model');
        $CI->load->model('comptes_model');

        $montant     = (float) $transaction['montant'];
        $gvv_txid    = (string) $transaction['transaction_id'];
        $description = !empty($meta['description'])
            ? (string) $meta['description']
            : 'Paiement HelloAsso ' . $type;
        $num_cheque  = 'HelloAsso:' . $gvv_txid;
        $date        = date('Y-m-d');

        switch ($type) {
            case 'provisionnement':
            case 'credit_tresorier':
                return $this->_ecriture_provisionnement(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            case 'bar':
                return $this->_ecriture_bar(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            case 'bar_externe':
                return $this->_ecriture_bar_externe(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            case 'cotisation':
                return $this->_ecriture_cotisation(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            case 'decouverte':
                return $this->_ecriture_decouverte(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            case 'cotisation_tresorier':
                return $this->_ecriture_cotisation_tresorier(
                    $transaction, $meta, $club_id, $montant, $description, $num_cheque, $date);

            default:
                return array('ok' => false, 'error' => 'Type de paiement inconnu : ' . $type);
        }
    }

    // ── Écritures par type ────────────────────────────────────────────────────

    /** provisionnement / credit_tresorier : débit 467, crédit 411 pilote */
    private function _ecriture_provisionnement($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $cp = $this->_get_compte_passage($club_id);
        if (!$cp) {
            return array('ok' => false, 'error' => 'Compte de passage (467) introuvable pour club=' . $club_id);
        }
        $cpilote = $this->_get_compte_pilote($tx, $club_id);
        if (!$cpilote) {
            return array('ok' => false, 'error' => 'Compte pilote (411) introuvable pour user_id=' . $tx['user_id']);
        }

        $id = get_instance()->ecritures_model->create_ecriture(
            $this->_ecriture_data($cp['id'], $cpilote['id'], $montant, $desc, $cheque, $date, $club_id)
        );
        if ($id === false) {
            return array('ok' => false, 'error' => 'Erreur DB lors de la création de l\'écriture provisionnement');
        }
        return array('ok' => true, 'ecriture_id' => $id);
    }

    /** bar : débit 411 pilote, crédit compte bar 7xx */
    private function _ecriture_bar($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $cpilote = $this->_get_compte_pilote($tx, $club_id);
        if (!$cpilote) {
            return array('ok' => false, 'error' => 'Compte pilote (411) introuvable pour user_id=' . $tx['user_id']);
        }
        $cbar = $this->_get_bar_account($club_id);
        if (!$cbar) {
            return array('ok' => false, 'error' => 'Compte bar introuvable pour club=' . $club_id);
        }

        $id = get_instance()->ecritures_model->create_ecriture(
            $this->_ecriture_data($cpilote['id'], $cbar['id'], $montant, $desc, $cheque, $date, $club_id)
        );
        if ($id === false) {
            return array('ok' => false, 'error' => 'Erreur DB lors de la création de l\'écriture bar');
        }
        return array('ok' => true, 'ecriture_id' => $id);
    }

    /** bar_externe : débit 467, crédit compte bar 7xx — sans compte pilote */
    private function _ecriture_bar_externe($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $cp = $this->_get_compte_passage($club_id);
        if (!$cp) {
            return array('ok' => false, 'error' => 'Compte de passage (467) introuvable pour club=' . $club_id);
        }
        $cbar = $this->_get_bar_account($club_id);
        if (!$cbar) {
            return array('ok' => false, 'error' => 'Compte bar introuvable pour club=' . $club_id);
        }

        if (!empty($meta['payer_name'])) {
            $desc = (string) $meta['payer_name'] . ' — ' . $desc;
        }

        $id = get_instance()->ecritures_model->create_ecriture(
            $this->_ecriture_data($cp['id'], $cbar['id'], $montant, $desc, $cheque, $date, $club_id)
        );
        if ($id === false) {
            return array('ok' => false, 'error' => 'Erreur DB lors de la création de l\'écriture bar_externe');
        }
        return array('ok' => true, 'ecriture_id' => $id);
    }

    /** cotisation : débit 467, crédit 417 (depuis metadata ou premier 417 de la section) */
    private function _ecriture_cotisation($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $cp = $this->_get_compte_passage($club_id);
        if (!$cp) {
            return array('ok' => false, 'error' => 'Compte de passage (467) introuvable pour club=' . $club_id);
        }

        $CI = get_instance();
        if (!empty($meta['compte_cotisation_id'])) {
            $ccot = $CI->comptes_model->get_by_id('id', (int) $meta['compte_cotisation_id']);
        } else {
            $ccot = $CI->comptes_model->get_by_section_and_codec((int) $club_id, '417');
        }
        if (!$ccot) {
            return array('ok' => false, 'error' => 'Compte cotisation (417) introuvable pour club=' . $club_id);
        }

        $id = $CI->ecritures_model->create_ecriture(
            $this->_ecriture_data($cp['id'], $ccot['id'], $montant, $desc, $cheque, $date, $club_id)
        );
        if ($id === false) {
            return array('ok' => false, 'error' => 'Erreur DB lors de la création de l\'écriture cotisation');
        }
        return array('ok' => true, 'ecriture_id' => $id);
    }

    /** decouverte : débit 467, crédit compte destination (obligatoire dans metadata) */
    private function _ecriture_decouverte($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $cp = $this->_get_compte_passage($club_id);
        if (!$cp) {
            return array('ok' => false, 'error' => 'Compte de passage (467) introuvable pour club=' . $club_id);
        }

        if (empty($meta['compte_destination_id'])) {
            return array('ok' => false, 'error' => 'compte_destination_id absent des metadata pour type=decouverte');
        }
        $CI    = get_instance();
        $cdest = $CI->comptes_model->get_by_id('id', (int) $meta['compte_destination_id']);
        if (!$cdest) {
            return array('ok' => false, 'error' => 'Compte destination introuvable id=' . $meta['compte_destination_id']);
        }

        $id = $CI->ecritures_model->create_ecriture(
            $this->_ecriture_data($cp['id'], $cdest['id'], $montant, $desc, $cheque, $date, $club_id)
        );
        if ($id === false) {
            return array('ok' => false, 'error' => 'Erreur DB lors de la création de l\'écriture decouverte');
        }
        return array('ok' => true, 'ecriture_id' => $id);
    }

    /**
     * cotisation_tresorier : deux écritures atomiques.
     *   Écriture 1 : débit 411 pilote → crédit 417 cotisation  (enregistre la cotisation)
     *   Écriture 2 : débit 467 passage → crédit 411 pilote     (rembourse le solde pilote)
     * Effet net sur le solde pilote : 0 (les deux écritures s'annulent).
     */
    private function _ecriture_cotisation_tresorier($tx, $meta, $club_id, $montant, $desc, $cheque, $date)
    {
        $CI = get_instance();

        $cpilote = $this->_get_compte_pilote($tx, $club_id);
        if (!$cpilote) {
            return array('ok' => false, 'error' => 'Compte pilote (411) introuvable pour user_id=' . $tx['user_id']);
        }

        $cp = $this->_get_compte_passage($club_id);
        if (!$cp) {
            return array('ok' => false, 'error' => 'Compte de passage (467) introuvable pour club=' . $club_id);
        }

        if (!empty($meta['compte_cotisation_id'])) {
            $ccot = $CI->comptes_model->get_by_id('id', (int) $meta['compte_cotisation_id']);
        } else {
            $ccot = $CI->comptes_model->get_by_section_and_codec((int) $club_id, '417');
        }
        if (!$ccot) {
            return array('ok' => false, 'error' => 'Compte cotisation (417) introuvable pour club=' . $club_id);
        }

        // Transaction DB englobante — CI2 gère la profondeur d'imbrication
        $this->db->trans_start();

        // Écriture 1 : débit pilote 411, crédit cotisation 417
        $id1 = $CI->ecritures_model->create_ecriture(
            $this->_ecriture_data($cpilote['id'], $ccot['id'], $montant, $desc, $cheque, $date, $club_id)
        );

        // Écriture 2 : débit passage 467, crédit pilote 411
        $id2 = $CI->ecritures_model->create_ecriture(
            $this->_ecriture_data($cp['id'], $cpilote['id'], $montant,
                $desc . ' (remboursement compte pilote)', $cheque, $date, $club_id)
        );

        $committed = $this->db->trans_complete();

        if (!$committed || $id1 === false || $id2 === false) {
            return array('ok' => false, 'error' => 'Erreur DB : double écriture cotisation_tresorier non atomique');
        }
        return array('ok' => true, 'ecriture_id' => $id1, 'ecriture_id2' => $id2);
    }

    // ── Helpers lookup comptes ────────────────────────────────────────────────

    /**
     * Retourne le compte de passage configuré pour un club.
     * La config `compte_passage` stocke l'ID du compte (int).
     * Fallback : si la valeur stockée est un codec (ex. '467'), recherche par codec.
     */
    private function _get_compte_passage($club_id)
    {
        $value = $this->get_config('helloasso', 'compte_passage', (int) $club_id);
        $id = (int) $value;
        if ($id > 0) {
            return get_instance()->comptes_model->get_by_id('id', $id);
        }
        // Fallback pour les anciennes configs stockant un codec
        return get_instance()->comptes_model->get_by_section_and_codec((int) $club_id, '467');
    }

    /**
     * Retourne le compte pilote 411 d'une transaction (résout user_id → mlogin).
     */
    private function _get_compte_pilote(array $transaction, $club_id)
    {
        $user_id = (int) $transaction['user_id'];
        $row = $this->db
            ->select('username')
            ->from('users')
            ->where('id', $user_id)
            ->get()->row_array();
        if (!$row) {
            return null;
        }
        return get_instance()->comptes_model->compte_pilote($row['username'], (int) $club_id);
    }

    /**
     * Retourne le compte bar (7xx) configuré pour un club (via sections.bar_account_id).
     */
    private function _get_bar_account($club_id)
    {
        $row = $this->db
            ->select('bar_account_id')
            ->from('sections')
            ->where('id', (int) $club_id)
            ->get()->row_array();
        if (!$row || empty($row['bar_account_id'])) {
            return null;
        }
        return get_instance()->comptes_model->get_by_id('id', (int) $row['bar_account_id']);
    }

    /**
     * Construit le tableau de données d'une écriture comptable.
     * compte1 = débit, compte2 = crédit (convention GVV).
     */
    private function _ecriture_data($compte1_id, $compte2_id, $montant, $description, $num_cheque, $date, $club_id)
    {
        return array(
            'annee_exercise' => (int) date('Y', strtotime($date)),
            'date_creation'  => date('Y-m-d H:i:s'),
            'date_op'        => $date,
            'compte1'        => (int) $compte1_id,
            'compte2'        => (int) $compte2_id,
            'montant'        => $montant,
            'description'    => (string) $description,
            'num_cheque'     => (string) $num_cheque,
            'saisie_par'     => 'helloasso_webhook',
            'club'           => (int) $club_id,
        );
    }

    /** Retourne un tableau d'erreur uniforme pour les erreurs de traitement webhook. */
    private function _webhook_error($message)
    {
        return array('ok' => false, 'status' => 'error', 'error' => $message, 'transaction' => null);
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
    /**
     * Compte les transactions "pending" créées aujourd'hui par un utilisateur dans un club.
     * Utilisé pour limiter les demandes à 5 par jour.
     */
    public function count_pending_today($user_id, $club_id)
    {
        return $this->db
            ->where('user_id',          (int) $user_id)
            ->where('club',             (int) $club_id)
            ->where('statut',           'pending')
            ->where('date_demande >=',  date('Y-m-d') . ' 00:00:00')
            ->where('date_demande <=',  date('Y-m-d') . ' 23:59:59')
            ->count_all_results($this->table);
    }

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
