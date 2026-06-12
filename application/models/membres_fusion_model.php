<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Modèle — Fusion de deux comptes membres en doublon.
 *
 * Toute la réaffectation est exécutée dans une seule transaction atomique.
 * Aucune modification n'est effectuée si la transaction échoue.
 */
class Membres_fusion_model extends CI_Model {

    // Champs de la fiche membre copiés du source vers la destination quand la destination est vide.
    // mlogin et mnumero sont toujours conservés côté destination.
    private $merge_fields = array(
        'mnom', 'mprenom', 'memail', 'memailparent', 'madresse', 'cp', 'ville', 'pays',
        'mtelf', 'mtelm', 'mdaten', 'm25ans', 'mlieun', 'msexe',
        'club', 'ext', 'actif', 'username', 'photo', 'compte', 'comment', 'trigramme',
        'categorie', 'profession', 'inst_glider', 'inst_airplane', 'licfed',
        'place_of_birth', 'inscription_date', 'validation_date', 'membre_payeur',
    );

    // Tables et colonnes à réaffecter (source → destination).
    // unique_key : colonnes formant la contrainte d'unicité à vérifier pour détecter les conflits.
    private $reassign_map = array(
        array('table' => 'events',                        'columns' => array('emlogin')),
        array('table' => 'volsa',                          'columns' => array('vapilid')),
        array('table' => 'volsp',                          'columns' => array('vppilid')),
        array('table' => 'tickets',                       'columns' => array('pilote')),
        array('table' => 'achats',                        'columns' => array('pilote')),
        array('table' => 'pompes',                        'columns' => array('ppilid')),
        array('table' => 'calendar',                      'columns' => array('mlogin')),
        array('table' => 'reservations',                  'columns' => array('pilot_member_id', 'instructor_member_id')),
        array('table' => 'formation_seances',             'columns' => array('pilote_id', 'instructeur_id')),
        array('table' => 'formation_inscriptions',        'columns' => array('pilote_id', 'instructeur_referent_id')),
        array('table' => 'formation_autorisations_solo',  'columns' => array('eleve_id', 'instructeur_id')),
        array('table' => 'formation_seances_participants','columns' => array('pilote_id'),
              'unique_key' => array('seance_id', 'pilote_id')),
        array('table' => 'acceptance_records',            'columns' => array('user_login', 'linked_pilot_login', 'linked_by')),
        array('table' => 'acceptance_items',              'columns' => array('created_by')),
        array('table' => 'archived_documents',            'columns' => array('pilot_login')),
        array('table' => 'email_list_members',            'columns' => array('membre_id')),
    );

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('validation');
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
    }

    /**
     * Retourne la fiche membre complète ou null si inexistant.
     */
    public function get_membre($mlogin) {
        $row = $this->db->get_where('membres', array('mlogin' => $mlogin))->row_array();
        return $row ?: null;
    }

    /**
     * Retourne tous les membres actifs pour les sélecteurs.
     */
    public function get_all_membres() {
        return $this->db
            ->select('mlogin, mnom, mprenom')
            ->order_by('mnom, mprenom')
            ->get('membres')
            ->result_array();
    }

    /**
     * Analyse complète de la fusion source → destination.
     * Aucune modification n'est effectuée.
     *
     * @param string $source      mlogin du membre source
     * @param string $destination mlogin du membre destination
     * @return array Rapport structuré
     */
    public function analyse($source, $destination) {
        $src  = $this->get_membre($source);
        $dst  = $this->get_membre($destination);

        // Comparaison champ par champ de la fiche membre
        $fields_comparison = array();
        foreach ($this->merge_fields as $field) {
            $src_val = isset($src[$field]) ? $src[$field] : null;
            $dst_val = isset($dst[$field]) ? $dst[$field] : null;
            $will_copy = (!$this->_is_empty($dst_val)) ? false : !$this->_is_empty($src_val);
            $fields_comparison[] = array(
                'field'     => $field,
                'src_val'   => $src_val,
                'dst_val'   => $dst_val,
                'will_copy' => $will_copy,
            );
        }

        // Comptage des enregistrements à réaffecter par table/colonne
        $references = array();
        foreach ($this->reassign_map as $entry) {
            foreach ($entry['columns'] as $col) {
                $count = $this->db->where($col, $source)->count_all_results($entry['table']);
                if ($count > 0) {
                    $references[] = array(
                        'table'   => $entry['table'],
                        'column'  => $col,
                        'count'   => $count,
                    );
                }
            }
        }

        // Comptes pilotes source et destination (toutes sections)
        $comptes_src = $this->_get_comptes_411($source);
        $comptes_dst = $this->_get_comptes_411($destination);

        // Soldes par section
        $soldes = $this->_compute_soldes($source, $destination, $comptes_src, $comptes_dst);

        // Conflits d'unicité (formation_seances_participants)
        $conflicts = $this->_detect_conflicts($source, $destination);

        // Compte dx_auth source
        $auth_source = $this->db->get_where('users', array('username' => $source))->row_array();

        // membres tiers avec membre_payeur = source
        $count_payeur = $this->db
            ->where('membre_payeur', $source)
            ->where('mlogin !=', $source)
            ->count_all_results('membres');

        return array(
            'source'             => $src,
            'destination'        => $dst,
            'fields_comparison'  => $fields_comparison,
            'references'         => $references,
            'comptes_src'        => $comptes_src,
            'comptes_dst'        => $comptes_dst,
            'soldes'             => $soldes,
            'conflicts'          => $conflicts,
            'auth_source'        => $auth_source ?: null,
            'count_payeur'       => $count_payeur,
        );
    }

    /**
     * Exécute la fusion dans une transaction atomique.
     *
     * @param string $source
     * @param string $destination
     * @return array ['success' => bool, 'log' => array, 'error' => string]
     */
    public function fusionner($source, $destination) {
        $log   = array();
        $error = '';

        $src = $this->get_membre($source);
        $dst = $this->get_membre($destination);
        if (!$src || !$dst) {
            return array('success' => false, 'log' => $log, 'error' => 'Membre source ou destination introuvable.');
        }

        $this->db->trans_start();

        // 1. Fusion des champs de la fiche membre
        $updates = array();
        $email_to_copy = null;
        foreach ($this->merge_fields as $field) {
            $src_val = isset($src[$field]) ? $src[$field] : null;
            $dst_val = isset($dst[$field]) ? $dst[$field] : null;
            if ($this->_is_empty($dst_val) && !$this->_is_empty($src_val)) {
                if ($field === 'memail') {
                    // Traitement différé : vider d'abord sur source pour éviter le conflit UNIQUE
                    $email_to_copy = $src_val;
                } else {
                    $updates[$field] = $src_val;
                }
            }
        }

        // Vider memail sur source si on doit le copier
        if ($email_to_copy !== null) {
            $this->db->where('mlogin', $source)->update('membres', array('memail' => null));
            $updates['memail'] = $email_to_copy;
        }

        if (!empty($updates)) {
            $this->db->where('mlogin', $destination)->update('membres', $updates);
            $log[] = 'Fiche membre : ' . count($updates) . ' champ(s) copié(s) depuis la source.';
        }

        // 2. Membres tiers avec membre_payeur = source → destination
        $nb = $this->db->where('membre_payeur', $source)->where('mlogin !=', $source)->count_all_results('membres');
        if ($nb > 0) {
            $this->db->where('membre_payeur', $source)->where('mlogin !=', $source)
                     ->update('membres', array('membre_payeur' => $destination));
            $log[] = "membres.membre_payeur : $nb mise(s) à jour.";
        }

        // 3. Gestion des comptes 411 (merge écritures si conflit de section)
        $this->_merge_comptes($source, $destination, $log);

        // 4. Conflits d'unicité : supprimer les enregistrements source en doublon avant UPDATE
        $conflicts = $this->_detect_conflicts($source, $destination);
        foreach ($conflicts as $conflict) {
            foreach ($conflict['conflict_ids'] as $id) {
                $this->db->where('id', $id)->delete($conflict['table']);
            }
            $log[] = $conflict['table'] . ' : ' . count($conflict['conflict_ids']) . ' doublon(s) supprimé(s).';
        }

        // 5. Réaffectation des références dans toutes les tables
        foreach ($this->reassign_map as $entry) {
            foreach ($entry['columns'] as $col) {
                $count = $this->db->where($col, $source)->count_all_results($entry['table']);
                if ($count > 0) {
                    $this->db->where($col, $source)->update($entry['table'], array($col => $destination));
                    $log[] = $entry['table'] . '.' . $col . ' : ' . $count . ' enregistrement(s) réaffecté(s).';
                }
            }
        }

        // 6. Suppression de la fiche membre source
        $this->db->where('mlogin', $source)->delete('membres');
        $log[] = "Fiche membre '$source' supprimée.";

        // 7. Désactivation du compte dx_auth source si présent
        $auth = $this->db->get_where('users', array('username' => $source))->row_array();
        if ($auth) {
            $this->db->where('username', $source)->update('users', array(
                'banned'     => 1,
                'ban_reason' => 'Compte fusionné dans ' . $destination,
            ));
            $log[] = "Compte dx_auth '$source' désactivé (banned=1).";
        }

        $this->db->trans_complete();

        $success = ($this->db->trans_status() !== FALSE);

        if ($success) {
            $by = get_instance()->dx_auth->get_username();
            log_message('info', "GVV fusion_membres: $by a fusionné '$source' dans '$destination'. " . implode(' | ', $log));
        } else {
            $error = 'La transaction a échoué. Aucune modification n\'a été appliquée.';
            log_message('error', "GVV fusion_membres: échec de la fusion '$source' → '$destination'.");
        }

        return array('success' => $success, 'log' => $log, 'error' => $error);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    private function _is_empty($val) {
        return ($val === null || $val === '' || $val === '0' || $val === 0);
    }

    /**
     * Retourne les comptes 411 d'un membre (toutes sections, indexés par club id).
     */
    private function _get_comptes_411($mlogin) {
        $rows = $this->db
            ->select('comptes.id, comptes.club, comptes.nom, comptes.pilote, sections.nom as section_nom')
            ->from('comptes')
            ->join('sections', 'comptes.club = sections.id', 'left')
            ->where('comptes.pilote', $mlogin)
            ->where('comptes.codec', '411')
            ->get()->result_array();

        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$row['club']] = $row;
        }
        return $indexed;
    }

    /**
     * Calcule les soldes source et destination par section.
     */
    private function _compute_soldes($source, $destination, $comptes_src, $comptes_dst) {
        $soldes = array();
        $all_clubs = array_unique(array_merge(array_keys($comptes_src), array_keys($comptes_dst)));

        foreach ($all_clubs as $club) {
            $solde_src = 0;
            $solde_dst = 0;
            if (isset($comptes_src[$club])) {
                $s = $this->ecritures_model->solde_compte($comptes_src[$club]['id']);
                $solde_src = is_array($s) ? ($s[0] - $s[1]) : (float)$s;
            }
            if (isset($comptes_dst[$club])) {
                $s = $this->ecritures_model->solde_compte($comptes_dst[$club]['id']);
                $solde_dst = is_array($s) ? ($s[0] - $s[1]) : (float)$s;
            }
            $section_nom = isset($comptes_src[$club]['section_nom'])
                ? $comptes_src[$club]['section_nom']
                : (isset($comptes_dst[$club]['section_nom']) ? $comptes_dst[$club]['section_nom'] : "Section $club");
            $soldes[] = array(
                'section'    => $section_nom,
                'solde_src'  => $solde_src,
                'solde_dst'  => $solde_dst,
                'solde_apres'=> $solde_src + $solde_dst,
            );
        }
        return $soldes;
    }

    /**
     * Gère le merge des comptes 411 dans la transaction.
     * Si les deux membres ont un compte 411 dans la même section : déplace les écritures.
     * Sinon : met à jour comptes.pilote.
     */
    private function _merge_comptes($source, $destination, &$log) {
        $comptes_src = $this->_get_comptes_411($source);
        $comptes_dst = $this->_get_comptes_411($destination);

        foreach ($comptes_src as $club => $compte_src) {
            if (isset($comptes_dst[$club])) {
                // Conflit : les deux ont un compte 411 dans la même section → déplacer les écritures
                $compte_dst = $comptes_dst[$club];
                $n1 = $this->db->where('compte1', $compte_src['id'])->count_all_results('ecritures');
                $n2 = $this->db->where('compte2', $compte_src['id'])->count_all_results('ecritures');
                if ($n1 > 0) {
                    $this->db->where('compte1', $compte_src['id'])
                             ->update('ecritures', array('compte1' => $compte_dst['id']));
                }
                if ($n2 > 0) {
                    $this->db->where('compte2', $compte_src['id'])
                             ->update('ecritures', array('compte2' => $compte_dst['id']));
                }
                $this->db->where('id', $compte_src['id'])->delete('comptes');
                $log[] = "comptes 411 section {$compte_src['section_nom']} : " . ($n1 + $n2) . " écriture(s) déplacée(s), compte source supprimé.";
            } else {
                // Pas de conflit : re-pointer le compte vers la destination
                $this->db->where('id', $compte_src['id'])
                         ->update('comptes', array('pilote' => $destination));
                $log[] = "comptes 411 section {$compte_src['section_nom']} : re-pointé vers '$destination'.";
            }
        }
    }

    /**
     * Détecte les doublons qui violeraient une contrainte d'unicité après UPDATE.
     * Retourne les IDs des enregistrements source à supprimer.
     */
    private function _detect_conflicts($source, $destination) {
        $conflicts = array();

        foreach ($this->reassign_map as $entry) {
            if (empty($entry['unique_key'])) continue;

            $table      = $entry['table'];
            $col        = $entry['columns'][0];
            $unique_key = $entry['unique_key'];

            // Colonnes de la clé unique autres que celle qu'on va modifier
            $other_cols = array_diff($unique_key, array($col));

            // Pour chaque enregistrement source, vérifier si destination a le même enregistrement
            $src_rows = $this->db->where($col, $source)->get($table)->result_array();
            $ids_to_delete = array();

            foreach ($src_rows as $src_row) {
                $where = array($col => $destination);
                foreach ($other_cols as $oc) {
                    $where[$oc] = $src_row[$oc];
                }
                $exists = $this->db->where($where)->count_all_results($table);
                if ($exists > 0) {
                    $ids_to_delete[] = $src_row['id'];
                }
            }

            if (!empty($ids_to_delete)) {
                $conflicts[] = array(
                    'table'        => $table,
                    'column'       => $col,
                    'conflict_ids' => $ids_to_delete,
                    'count'        => count($ids_to_delete),
                );
            }
        }

        return $conflicts;
    }
}
