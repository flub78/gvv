<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_subscribers_model
 *
 * Gestion des abonnés externes à la newsletter de veille.
 * Indépendant du cycle de génération automatique.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_subscribers_model extends CI_Model {

    public $table = 'newsletter_subscribers';
    protected $primary_key = 'id';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    private function generate_token() {
        return bin2hex(random_bytes(32));
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Inscrit un nouvel abonné (non confirmé).
     * Si l'email est déjà inscrit et non confirmé, renouvelle le token.
     * Si déjà inscrit et confirmé, retourne FALSE (déjà abonné).
     *
     * @param array $data  email, first_name, last_name
     * @return array|false  ['subscriber_id' => int, 'confirmation_token' => string] ou FALSE
     */
    public function subscribe($data) {
        $email = strtolower(trim($data['email']));
        $existing = $this->get_by_email($email);

        if ($existing) {
            if ($existing['confirmed'] && !$existing['unsubscribed_at']) {
                return FALSE; // déjà abonné actif
            }
            // réabonnement ou relance de confirmation
            $token = $this->generate_token();
            $now   = $this->now();
            $this->db->where('id', $existing['id'])->update($this->table, array(
                'first_name'          => $data['first_name'],
                'last_name'           => $data['last_name'],
                'confirmed'           => 0,
                'confirmation_token'  => $token,
                'unsubscribed_at'     => NULL,
                'confirmed_at'        => NULL,
                'updated_at'          => $now,
                'updated_by'          => 'public',
            ));
            return array('subscriber_id' => $existing['id'], 'confirmation_token' => $token);
        }

        $token = $this->generate_token();
        $now   = $this->now();
        $row   = array(
            'email'              => $email,
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'confirmed'          => 0,
            'confirmation_token' => $token,
            'unsubscribe_token'  => $this->generate_token(),
            'created_at'         => $now,
            'updated_at'         => $now,
            'created_by'         => 'public',
            'updated_by'         => 'public',
        );
        if ($this->db->insert($this->table, $row)) {
            return array('subscriber_id' => $this->db->insert_id(), 'confirmation_token' => $token);
        }
        log_message('error', 'newsletter_subscribers_model::subscribe — ' . $this->db->_error_message());
        return FALSE;
    }

    /**
     * Abonné en attente de confirmation pour ce token, ou NULL.
     */
    public function get_by_confirmation_token($token) {
        if ((string) $token === '') return NULL;
        return $this->db->where('confirmation_token', $token)
                        ->where('confirmed', 0)
                        ->where('unsubscribed_at IS NULL')
                        ->get($this->table)->row_array() ?: NULL;
    }

    /**
     * Confirme l'abonnement via le token envoyé par email.
     *
     * @param string $token
     * @return bool
     */
    public function confirm($token) {
        $subscriber = $this->db->where('confirmation_token', $token)
                               ->where('confirmed', 0)
                               ->where('unsubscribed_at IS NULL')
                               ->get($this->table)->row_array();
        if (!$subscriber) return FALSE;

        $now = $this->now();
        $this->db->where('id', $subscriber['id'])->update($this->table, array(
            'confirmed'           => 1,
            'confirmation_token'  => NULL,
            'confirmed_at'        => $now,
            'updated_at'          => $now,
            'updated_by'          => 'public',
        ));
        return $this->db->affected_rows() > 0;
    }

    /**
     * Désinscrit un abonné via son token de désinscription.
     *
     * @param string $token
     * @return bool
     */
    public function unsubscribe_by_token($token) {
        $subscriber = $this->db->where('unsubscribe_token', $token)
                               ->where('unsubscribed_at IS NULL')
                               ->get($this->table)->row_array();
        if (!$subscriber) return FALSE;

        $now = $this->now();
        $this->db->where('id', $subscriber['id'])->update($this->table, array(
            'unsubscribed_at' => $now,
            'updated_at'      => $now,
            'updated_by'      => 'public',
        ));
        return $this->db->affected_rows() > 0;
    }

    /**
     * Supprime un abonnement (admin).
     */
    public function delete($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Retourne un abonné par email.
     */
    public function get_by_email($email) {
        return $this->db->where('email', strtolower(trim($email)))
                        ->get($this->table)->row_array();
    }

    /**
     * Retourne un abonné par son ID.
     */
    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array();
    }

    /**
     * Retourne tous les abonnés actifs confirmés (pour l'envoi).
     */
    public function get_active() {
        return $this->db->where('confirmed', 1)
                        ->where('unsubscribed_at IS NULL')
                        ->order_by('last_name', 'ASC')
                        ->get($this->table)->result_array();
    }

    /**
     * Retourne tous les abonnés pour l'administration (actifs et désinscrits).
     */
    public function get_all() {
        return $this->db->order_by('created_at', 'DESC')->get($this->table)->result_array();
    }

    /**
     * Enregistre un envoi pour éviter les doublons.
     *
     * @param int $newsletter_id
     * @param int $subscriber_id
     * @return bool
     */
    public function record_sending($newsletter_id, $subscriber_id) {
        $now = $this->now();
        $nl  = (int) $newsletter_id;
        $sub = (int) $subscriber_id;
        $this->db->query(
            "INSERT INTO newsletter_sendings
                (newsletter_id, subscriber_id, sent_at, created_at, updated_at, created_by, updated_by)
             VALUES ($nl, $sub, '$now', '$now', '$now', 'system', 'system')
             ON DUPLICATE KEY UPDATE sent_at='$now', updated_at='$now'"
        );
        return $this->db->affected_rows() > 0;
    }

    /**
     * Vérifie si un envoi a déjà été fait pour cet abonné/édition.
     */
    public function already_sent($newsletter_id, $subscriber_id) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('subscriber_id', (int) $subscriber_id)
                        ->count_all_results('newsletter_sendings') > 0;
    }

    /**
     * Abonnés actifs qui n'ont pas encore reçu une newsletter.
     *
     * @param int         $newsletter_id
     * @param string|null $published_at  Si fourni, seuls les abonnés déjà
     *                                   confirmés à cette date : une newsletter
     *                                   n'est pas envoyée aux abonnés ultérieurs
     */
    public function get_not_sent($newsletter_id, $published_at = NULL) {
        $this->db->where('confirmed', 1)
                 ->where('unsubscribed_at IS NULL')
                 ->where('id NOT IN (SELECT subscriber_id FROM newsletter_sendings WHERE newsletter_id = '
                         . (int) $newsletter_id . ')', NULL, FALSE);
        if ($published_at) {
            $this->db->where('(confirmed_at IS NULL OR confirmed_at <= ' . $this->db->escape($published_at) . ')', NULL, FALSE);
        }
        return $this->db->order_by('last_name', 'ASC')->get($this->table)->result_array();
    }
}
