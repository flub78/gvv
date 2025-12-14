<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright(C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource oneshot.php
 * @packages controllers
 *
 * Contrôleur pour les opérations one-shot sur la base de données
 * Réservé aux administrateurs pour les modifications ponctuelles
 *
 * ATTENTION: Ce contrôleur contient des méthodes qui ne doivent être exécutées qu'une seule fois
 * pour analyser ou corriger des données. Ne pas utiliser en production régulière.
 */

class Oneshot extends CI_Controller {

    /**
     * Constructor - Vérification des droits
     */
    function __construct() {
        parent::__construct();

        // Check if user is logged in or not
        $this->dx_auth->check_login();

        // Load necessary models
        $this->load->model('ecritures_model');
        $this->load->model('comptes_model');
    }

    /**
     * Page d'index - Liste des méthodes disponibles
     */
    function index() {
        echo "<h1>Opérations One-Shot</h1>";
        echo "<p>Utilisateur connecté: " . htmlspecialchars($this->dx_auth->get_username()) . "</p>";
        echo "<hr>";

        echo "<h2>Méthodes disponibles:</h2>";
        echo "<ul>";
        echo "<li><a href='" . base_url() . "index.php/oneshot/cotisations'>cotisations</a> - Écritures de cotisations (775-766)</li>";
        echo "<li><a href='" . base_url() . "index.php/oneshot/comptes_manquants'>comptes_manquants</a> - Pilotes avec compte 411 section 1/2/3 mais sans compte section 4</li>";
        echo "<li><a href='" . base_url() . "index.php/oneshot/creer_comptes_section4'>creer_comptes_section4</a> - Créer les comptes 411 manquants en section 4</li>";
        echo "<li><a href='" . base_url() . "index.php/oneshot/regulariser_initialisations_2024'>regulariser_initialisations_2024</a> - Régulariser les écritures d'initialisation 2024 (ULM/Avion/CG)</li>";
        echo "</ul>";

        echo "<hr>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
    }

    /**
     * Régulariser les écritures d'initialisation 2024 (sections 2,3,4)
     * - Remplacer 102 par 512 (30/12/2024)
     * - Passer compensation 512/102 (30/12/2024)
     * - Clôturer le compte 6xx/7xx contre 102 (31/12/2024)
     * - Supprimer l'écriture initiale
     */
    public function regulariser_initialisations_2024() {
        echo "<h1>Régularisation des écritures d'initialisation 2024 (sections ULM/Avion/CG)</h1>";
        echo "<p>Utilisateur connecté: " . htmlspecialchars($this->dx_auth->get_username()) . "</p>";
        echo "<hr>";

        $mode_exec = $this->input->post('executer') === 'oui';

        if ($mode_exec) {
            $this->executer_regularisation_initialisations_2024();
        } else {
            $this->previsualiser_regularisation_initialisations_2024();
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
    }

    /**
     * Prévisualisation : lister les écritures candidates et afficher totaux / comptes 512
     */
    private function previsualiser_regularisation_initialisations_2024() {
        $sections = array(2 => 'ULM', 3 => 'Avion', 4 => 'Services généraux');
        $ecritures = $this->charger_ecritures_initiales_2024();

        if (count($ecritures) === 0) {
            echo "<p><strong>Aucune écriture d'initialisation 2024 trouvée pour les sections 2/3/4.</strong></p>";
            return;
        }

        // Comptes 512 par section
        $comptes_512 = array();
        foreach ($sections as $club_id => $label) {
            $comptes_512[$club_id] = $this->get_compte_512($club_id);
        }

        // Regrouper par section
        $par_section = array();
        foreach ($ecritures as $e) {
            $club = (int)$e['club'];
            if (!isset($par_section[$club])) {
                $par_section[$club] = array('label' => $sections[$club], 'items' => array(), 'total' => 0);
            }
            $par_section[$club]['items'][] = $e;
            $par_section[$club]['total'] += (float)$e['montant'];
        }

        echo "<form method='post' action='" . base_url() . "index.php/oneshot/regulariser_initialisations_2024'>";

        foreach ($par_section as $club => $data) {
            echo "<h2>Section " . htmlspecialchars($data['label']) . " (club " . (int)$club . ")</h2>";
            $compte512 = $comptes_512[$club];
            if ($compte512) {
                echo "<p>Compte 512 détecté: ID " . (int)$compte512['id'] . " - " . htmlspecialchars($compte512['nom']) . "</p>";
            } else {
                echo "<p style='color:red;font-weight:bold;'>Aucun compte 512 trouvé pour cette section - exécution impossible.</p>";
            }

            echo "<p>Total montant: " . number_format($data['total'], 2, ',', ' ') . " € - " . count($data['items']) . " écritures</p>";

            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; margin-bottom: 10px;'>";
            echo "<thead><tr style='background:#f0f0f0;'><th>ID</th><th>Date op</th><th>Compte1</th><th>Compte2</th><th>Description</th><th>Montant</th></tr></thead><tbody>";
            foreach ($data['items'] as $e) {
                echo "<tr>";
                echo "<td>" . (int)$e['id'] . "</td>";
                echo "<td>" . htmlspecialchars($e['date_op']) . "</td>";
                echo "<td>" . htmlspecialchars($e['compte1_codec']) . " - " . htmlspecialchars($e['compte1_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($e['compte2_codec']) . " - " . htmlspecialchars($e['compte2_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($e['description']) . "</td>";
                echo "<td style='text-align:right;'>" . number_format($e['montant'], 2, ',', ' ') . " €</td>";
                echo "</tr>";
                echo "<input type='hidden' name='ecritures_ids[]' value='" . (int)$e['id'] . "'>";
            }
            echo "</tbody></table>";
        }

        $manquants = array();
        foreach ($sections as $club_id => $label) {
            if (!$comptes_512[$club_id]) {
                $manquants[] = $label;
            }
        }

        if (count($manquants) > 0) {
            echo "<p style='color:red;font-weight:bold;'>Exécution désactivée : compte 512 manquant pour " . implode(', ', $manquants) . "</p>";
        } else {
            echo "<p style='font-weight:bold; color:red;'>Cette opération va créer des écritures (30/12/2024 et 31/12/2024) et supprimer les écritures initiales. Confirmer ?</p>";
            echo "<button type='submit' name='executer' value='oui' style='background:#28a745;color:#fff;padding:8px 14px;border:none;cursor:pointer;'>OUI - Exécuter</button>";
        }

        echo "</form>";
    }

    /**
     * Exécuter la régularisation : créer E1/E2/E3 puis supprimer l'initiale
     */
    private function executer_regularisation_initialisations_2024() {
        $ids = $this->input->post('ecritures_ids');
        if (empty($ids) || !is_array($ids)) {
            echo "<p style='color:red;'>Aucune écriture sélectionnée.</p>";
            return;
        }

        $ecritures = $this->charger_ecritures_initiales_2024($ids);
        if (count($ecritures) === 0) {
            echo "<p style='color:red;'>Aucune écriture éligible trouvée.</p>";
            return;
        }

        $sections = array(2 => 'ULM', 3 => 'Avion', 4 => 'Services généraux');
        $comptes_512 = array();
        $comptes_102 = array();
        foreach ($sections as $club_id => $label) {
            $comptes_512[$club_id] = $this->get_compte_512($club_id);
            $comptes_102[$club_id] = $this->get_compte_102($club_id);
        }

        $resultats = array();
        $succes = 0;
        $echecs = 0;

        foreach ($ecritures as $ecriture) {
            $club = (int)$ecriture['club'];
            if (!isset($comptes_512[$club]) || !$comptes_512[$club]) {
                $resultats[] = array('id' => $ecriture['id'], 'status' => 'KO', 'message' => 'Compte 512 manquant');
                $echecs++;
                continue;
            }
            if (!isset($comptes_102[$club]) || !$comptes_102[$club]) {
                $resultats[] = array('id' => $ecriture['id'], 'status' => 'KO', 'message' => 'Compte 102 manquant');
                $echecs++;
                continue;
            }

            try {
                gvv_info("Oneshot: Traitement écriture " . $ecriture['id']);
                $detail = $this->traiter_ecriture_initialisation($ecriture, $comptes_512[$club]['id'], $comptes_102[$club]['id']);
                $resultats[] = array('id' => $ecriture['id'], 'status' => 'OK', 'message' => $detail);
                $succes++;
            } catch (Exception $ex) {
                gvv_error("Oneshot: Exception écriture " . $ecriture['id'] . " - " . $ex->getMessage());
                $resultats[] = array('id' => $ecriture['id'], 'status' => 'KO', 'message' => $ex->getMessage());
                $echecs++;
            }
        }

        echo "<h2>Résultats</h2>";
        echo "<p>Succès: $succes | Échecs: $echecs</p>";

        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
        echo "<thead><tr style='background:#f0f0f0;'><th>ID initiale</th><th>Statut</th><th>Détails</th></tr></thead><tbody>";
        foreach ($resultats as $r) {
            $color = ($r['status'] === 'OK') ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . (int)$r['id'] . "</td>";
            echo "<td style='color:" . $color . ";font-weight:bold;'>" . htmlspecialchars($r['status']) . "</td>";
            echo "<td>" . htmlspecialchars($r['message']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    /**
     * Charger les écritures initiales 2024, optionnellement filtrées par IDs
     */
    private function charger_ecritures_initiales_2024($ids = null) {
        $this->db->select('e.*, c1.nom as compte1_nom, c1.codec as compte1_codec, c2.nom as compte2_nom, c2.codec as compte2_codec');
        $this->db->from('ecritures e');
        $this->db->join('comptes as c1', 'e.compte1 = c1.id', 'left');
        $this->db->join('comptes as c2', 'e.compte2 = c2.id', 'left');
        $this->db->where('e.date_op', '2024-12-31');
        $this->db->where('e.date_creation >=', '2025-12-01');
        $this->db->where('e.date_creation <', '2026-01-01');
        $this->db->where_in('e.club', array(2, 3, 4));
        $this->db->where("(c1.codec = '102' OR c2.codec = '102')", null, false);
        $this->db->where("(c1.codec LIKE '6%' OR c1.codec LIKE '7%' OR c2.codec LIKE '6%' OR c2.codec LIKE '7%')", null, false);
        if ($ids && is_array($ids)) {
            $ids_int = array_map('intval', $ids);
            $this->db->where_in('e.id', $ids_int);
        }
        $this->db->order_by('e.id', 'ASC');

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Traitement d'une écriture : E1/E2/E3 + suppression de l'initiale
     * E3 clôture vers 120 (déficit) ou 129 (bénéfice) selon le type de compte 6xx/7xx
     */
    private function traiter_ecriture_initialisation($ecriture, $compte_512_id, $compte_102_id) {
        $is_c1_102 = ($ecriture['compte1_codec'] === '102');
        $compte_resultat_id = $is_c1_102 ? $ecriture['compte2'] : $ecriture['compte1'];
        $compte_resultat_codec = $is_c1_102 ? $ecriture['compte2_codec'] : $ecriture['compte1_codec'];
        $club = (int)$ecriture['club'];

        $username = $this->dx_auth->get_username();
        $today = date('Y-m-d');

        gvv_info("Oneshot: Début traitement écriture " . $ecriture['id'] . " (montant=" . $ecriture['montant'] . ")");

        // Déterminer le compte de résultat (120 ou 129) selon le type de compte
        // Compte 6xx (charges) -> 120 (déficit)
        // Compte 7xx (produits) -> 129 (bénéfice)
        $compte_resultat_final_id = null;
        if (substr($compte_resultat_codec, 0, 1) === '6') {
            // Charges -> déficit (120)
            $compte_resultat_final = $this->get_compte_by_codec('120', $club);
            if (!$compte_resultat_final) {
                throw new Exception("Compte 120 (déficit) non trouvé pour section " . $club);
            }
            $compte_resultat_final_id = $compte_resultat_final['id'];
            gvv_debug("Oneshot: Charge détectée -> utilisation compte 120 (déficit) ID=" . $compte_resultat_final_id);
        } else if (substr($compte_resultat_codec, 0, 1) === '7') {
            // Produits -> bénéfice (129)
            $compte_resultat_final = $this->get_compte_by_codec('129', $club);
            if (!$compte_resultat_final) {
                throw new Exception("Compte 129 (bénéfice) non trouvé pour section " . $club);
            }
            $compte_resultat_final_id = $compte_resultat_final['id'];
            gvv_debug("Oneshot: Produit détecté -> utilisation compte 129 (bénéfice) ID=" . $compte_resultat_final_id);
        } else {
            throw new Exception("Compte de résultat invalide: " . $compte_resultat_codec);
        }

        // Préparer les champs communs
        $base = array(
            'annee_exercise' => 2024,
            'date_creation' => $today,
            'saisie_par' => $username,
            'gel' => 0,
            'club' => $ecriture['club'],
            'categorie' => isset($ecriture['categorie']) ? $ecriture['categorie'] : null,
            'type' => isset($ecriture['type']) ? $ecriture['type'] : null,
            'num_cheque' => isset($ecriture['num_cheque']) ? $ecriture['num_cheque'] : null,
            'achat' => isset($ecriture['achat']) ? $ecriture['achat'] : null,
            'quantite' => isset($ecriture['quantite']) ? $ecriture['quantite'] : null,
            'prix' => isset($ecriture['prix']) ? $ecriture['prix'] : null
        );

        $this->db->trans_begin();
        gvv_debug("Oneshot: Transaction BEGIN");

        try {
            // E1 : remplace 102 par 512 (30/12/2024)
            $e1 = $base;
            $e1['date_op'] = '2024-12-30';
            $e1['description'] = '[Régul 2024] Simulation 512 - Ecriture ' . $ecriture['id'];
            $e1['montant'] = $ecriture['montant'];
            if ($is_c1_102) {
                $e1['compte1'] = $compte_512_id;
                $e1['compte2'] = $compte_resultat_id;
            } else {
                $e1['compte1'] = $compte_resultat_id;
                $e1['compte2'] = $compte_512_id;
            }
            gvv_debug("Oneshot: Création E1 - compte1=" . $e1['compte1'] . " compte2=" . $e1['compte2'] . " montant=" . $e1['montant']);
            $id_e1 = $this->create_ecriture_raw($e1);
            if (!$id_e1) {
                throw new Exception("E1: create_ecriture_raw retourné FALSE ou 0");
            }
            gvv_info("Oneshot: E1 créée ID=" . $id_e1);

            // E2 : compensation 512/102 (30/12/2024)
            $e2 = $base;
            $e2['date_op'] = '2024-12-30';
            $e2['description'] = '[Régul 2024] Comp 512/102 - Ecriture ' . $ecriture['id'];
            $e2['montant'] = $ecriture['montant'];
            if ($is_c1_102) {
                // 512 a été débité, on le crédite : débit 102 / crédit 512
                $e2['compte1'] = $compte_102_id;
                $e2['compte2'] = $compte_512_id;
            } else {
                // 512 a été crédité, on le débite : débit 512 / crédit 102
                $e2['compte1'] = $compte_512_id;
                $e2['compte2'] = $compte_102_id;
            }
            gvv_debug("Oneshot: Création E2 - compte1=" . $e2['compte1'] . " compte2=" . $e2['compte2'] . " montant=" . $e2['montant']);
            $id_e2 = $this->create_ecriture_raw($e2);
            if (!$id_e2) {
                throw new Exception("E2: create_ecriture_raw retourné FALSE ou 0");
            }
            gvv_info("Oneshot: E2 créée ID=" . $id_e2);

            // E3 : clôture du compte de résultat vers 120 ou 129 (31/12/2024)
            $e3 = $base;
            $e3['date_op'] = '2024-12-31';
            $e3['description'] = '[Régul 2024] Clôture ' . $compte_resultat_codec;
            $e3['montant'] = $ecriture['montant'];
            if ($is_c1_102) {
                // Compte résultat crédité en E1 -> on le débite contre 120/129
                $e3['compte1'] = $compte_resultat_id;
                $e3['compte2'] = $compte_resultat_final_id;
            } else {
                // Compte résultat débité en E1 -> on le crédite contre 120/129
                $e3['compte1'] = $compte_resultat_final_id;
                $e3['compte2'] = $compte_resultat_id;
            }
            gvv_debug("Oneshot: Création E3 - compte1=" . $e3['compte1'] . " compte2=" . $e3['compte2'] . " montant=" . $e3['montant']);
            $id_e3 = $this->create_ecriture_raw($e3);
            if (!$id_e3) {
                throw new Exception("E3: create_ecriture_raw retourné FALSE ou 0");
            }
            gvv_info("Oneshot: E3 créée ID=" . $id_e3);

            // Supprimer l'écriture initiale et annuler son impact
            gvv_debug("Oneshot: Suppression écriture initiale " . $ecriture['id']);
            $this->supprimer_ecriture_initiale($ecriture);
            gvv_info("Oneshot: Écriture initiale " . $ecriture['id'] . " supprimée");

            // Vérifier le statut avant le commit
            if ($this->db->trans_status() === FALSE) {
                $error_num = $this->db->_error_number();
                $error_msg = $this->db->_error_message();
                throw new Exception("Transaction en erreur avant commit - DB Error #" . $error_num . ": " . $error_msg);
            }

            $this->db->trans_commit();
            gvv_info("Oneshot: Transaction COMMIT pour écriture " . $ecriture['id']);

            return 'E1=' . $id_e1 . ', E2=' . $id_e2 . ', E3=' . $id_e3 . ' supprimé=' . $ecriture['id'];

        } catch (Exception $ex) {
            gvv_error("Oneshot: Exception - " . $ex->getMessage());
            $error_num = $this->db->_error_number();
            $error_msg = $this->db->_error_message();
            if ($error_num || $error_msg) {
                gvv_error("Oneshot: DB Error #" . $error_num . ": " . $error_msg);
            }
            $this->db->trans_rollback();
            gvv_info("Oneshot: Transaction ROLLBACK");
            throw $ex;
        }
    }

    /**
     * Création d'une écriture sans transaction imbriquée
     */
    private function create_ecriture_raw($data) {
        $compte1 = $data['compte1'];
        $compte2 = $data['compte2'];
        $montant = $data['montant'];

        try {
            gvv_debug("Oneshot: maj_comptes compte1=" . $compte1 . " compte2=" . $compte2 . " montant=" . $montant);
            $this->comptes_model->maj_comptes($compte1, $compte2, $montant);
            
            gvv_debug("Oneshot: insert ecritures - " . var_export($data, true));
            $result = $this->db->insert('ecritures', $data);
            
            if (!$result) {
                $error_num = $this->db->_error_number();
                $error_msg = $this->db->_error_message();
                throw new Exception("DB insert failed - #" . $error_num . ": " . $error_msg . " SQL: " . $this->db->last_query());
            }
            
            $insert_id = $this->db->insert_id();
            gvv_info("Oneshot: insert successful, ID=" . $insert_id);
            return $insert_id;
        } catch (Exception $ex) {
            gvv_error("Oneshot: create_ecriture_raw error - " . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Suppression de l'écriture initiale en annulant son impact
     */
    private function supprimer_ecriture_initiale($ecriture) {
        try {
            gvv_debug("Oneshot: maj_comptes (reversal) compte1=" . $ecriture['compte1'] . " compte2=" . $ecriture['compte2'] . " montant=-" . $ecriture['montant']);
            $this->comptes_model->maj_comptes($ecriture['compte1'], $ecriture['compte2'], -$ecriture['montant']);
            
            gvv_debug("Oneshot: delete ecritures ID=" . $ecriture['id']);
            $result = $this->db->delete('ecritures', array('id' => $ecriture['id']));
            if ($result === FALSE) {
                $error_num = $this->db->_error_number();
                $error_msg = $this->db->_error_message();
                throw new Exception("DB delete failed - #" . $error_num . ": " . $error_msg);
            }
            gvv_info("Oneshot: delete successful");
            
            // Nettoyer les rapprochements éventuels
            gvv_debug("Oneshot: delete rapprochements pour écriture " . $ecriture['id']);
            $this->load->model('associations_ecriture_model');
            $this->associations_ecriture_model->delete_rapprochements($ecriture['id']);
        } catch (Exception $ex) {
            gvv_error("Oneshot: supprimer_ecriture_initiale error - " . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Trouver un compte par codec et section
     */
    private function get_compte_by_codec($codec, $club_id) {
        return $this->db
            ->select('id, nom')
            ->from('comptes')
            ->where('codec', $codec)
            ->where('club', (int)$club_id)
            ->get()
            ->row_array();
    }

    /**
     * Trouver le compte 512 d'une section
     */
    private function get_compte_512($club_id) {
        return $this->get_compte_by_codec('512', $club_id);
    }

    /**
     * Trouver le compte 102 d'une section
     */
    private function get_compte_102($club_id) {
        return $this->get_compte_by_codec('102', $club_id);
    }

    /**
     * Affiche les écritures de cotisations (comptes 775 et 766)
     * URL: /oneshot/cotisations
     *
     * Méthode one-shot pour analyser les écritures de cotisations
     */
    function cotisations() {
        $this->afficher_ecritures_entre_comptes(775, 766, "Écritures de cotisations (775-766)");
    }

    /**
     * Traiter une cotisation : modifier l'écriture et créer une licence
     * URL: /oneshot/process_cotisation (POST)
     */
    function process_cotisation() {
        // Vérifier que c'est une requête POST
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée', 405);
            return;
        }

        $ecriture_id = $this->input->post('ecriture_id');
        if (!$ecriture_id) {
            $this->session->set_flashdata('error', 'ID d\'écriture manquant');
            redirect('oneshot/cotisations');
            return;
        }

        // Charger l'écriture
        $this->db->select('ecritures.*, c1.codec as compte1_codec, c2.codec as compte2_codec');
        $this->db->from('ecritures');
        $this->db->join('comptes as c1', 'ecritures.compte1 = c1.id', 'left');
        $this->db->join('comptes as c2', 'ecritures.compte2 = c2.id', 'left');
        $this->db->where('ecritures.id', $ecriture_id);
        $query = $this->db->get();
        $ecriture = $query->row_array();

        if (!$ecriture) {
            $this->session->set_flashdata('error', 'Écriture non trouvée');
            redirect('oneshot/cotisations');
            return;
        }

        // Charger tous les comptes 411
        $this->db->select('id, nom, pilote');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where('club', 4);
        $query_comptes = $this->db->get();
        $comptes_411 = $query_comptes->result_array();

        // Trouver le compte 411 correspondant
        $matched_compte = $this->trouver_compte_411($ecriture['description'], $comptes_411);
        if (!$matched_compte) {
            $this->session->set_flashdata('error', 'Aucun compte 411 correspondant trouvé');
            redirect('oneshot/cotisations');
            return;
        }

        // Vérifier que le pilote existe dans le compte 411
        if (empty($matched_compte['pilote'])) {
            $this->session->set_flashdata('error', 'Le compte 411 n\'a pas de pilote associé');
            redirect('oneshot/cotisations');
            return;
        }

        // Déterminer quel compte est 708 (ou 766)
        $compte_cotisation_id = null;
        $compte_411_position = null; // 1 ou 2

        if ($ecriture['compte1_codec'] == '708' || $ecriture['compte1_codec'] == '766') {
            $compte_cotisation_id = $ecriture['compte1'];
            $compte_411_position = 1;
        } elseif ($ecriture['compte2_codec'] == '708' || $ecriture['compte2_codec'] == '766') {
            $compte_cotisation_id = $ecriture['compte2'];
            $compte_411_position = 2;
        }

        if (!$compte_cotisation_id) {
            $this->session->set_flashdata('error', 'Aucun compte 708 ou 766 trouvé dans cette écriture');
            redirect('oneshot/cotisations');
            return;
        }

        // Extraire l'année de la description (chercher un pattern 20XX)
        $year = $ecriture['annee_exercise']; // valeur par défaut
        if (preg_match('/\b(202[0-9])\b/', $ecriture['description'], $matches)) {
            $year = intval($matches[1]);
        }

        // Commencer une transaction
        $this->db->trans_start();

        try {
            // 1. Modifier l'écriture actuelle : remplacer le compte 766 par le compte 411
            $update_data = array();
            if ($compte_411_position == 1) {
                $update_data['compte1'] = $matched_compte['id'];
            } else {
                $update_data['compte2'] = $matched_compte['id'];
            }
            $this->db->where('id', $ecriture_id);
            $this->db->update('ecritures', $update_data);

            // 2. Créer une nouvelle écriture avec compte1=411, compte2=708/766
            $new_ecriture = array(
                'annee_exercise' => $ecriture['annee_exercise'],
                'date_creation' => date('Y-m-d'),
                'date_op' => $ecriture['date_op'],
                'compte1' => $matched_compte['id'],
                'compte2' => $compte_cotisation_id,
                'montant' => $ecriture['montant'],
                'description' => $ecriture['description'],
                'type' => $ecriture['type'],
                'num_cheque' => $ecriture['num_cheque'],
                'saisie_par' => $this->dx_auth->get_username(),
                'gel' => 0,
                'club' => $ecriture['club'],
                'categorie' => $ecriture['categorie']
            );
            $this->db->insert('ecritures', $new_ecriture);

            // 3. Créer une licence
            $licence_data = array(
                'pilote' => $matched_compte['pilote'],
                'type' => 0,
                'year' => $year,
                'date' => date('Y-m-d'),
                'comment' => 'Cotisation traitée automatiquement depuis écriture #' . $ecriture_id
            );
            $this->db->insert('licences', $licence_data);

            // Valider la transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->session->set_flashdata('error', 'Erreur lors du traitement de la cotisation');
            } else {
                $this->session->set_flashdata('success', 'Cotisation traitée avec succès pour ' . $matched_compte['nom'] . ' (année ' . $year . ')');
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Erreur: ' . $e->getMessage());
        }

        redirect('oneshot/cotisations');
    }

    /**
     * Affiche les pilotes avec compte 411 dans sections 1/2/3 mais sans compte dans section 4
     * URL: /oneshot/comptes_manquants
     *
     * Méthode one-shot pour identifier les comptes 411 manquants en section Services généraux
     */
    function comptes_manquants() {
        echo "<h1>Pilotes sans compte 411 en section Services généraux</h1>";
        echo "<p>Utilisateur connecté: " . htmlspecialchars($this->dx_auth->get_username()) . "</p>";
        echo "<hr>";

        // Récupérer tous les comptes 411 des sections 1, 2, 3 (Planeur, ULM, Avion)
        $this->db->select('id, nom, pilote, club');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where_in('club', array(1, 2, 3));
        $this->db->order_by('pilote', 'ASC');
        $query_sections = $this->db->get();
        $comptes_sections = $query_sections->result_array();

        // Récupérer tous les comptes 411 de la section 4 (Services généraux)
        $this->db->select('pilote');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where('club', 4);
        $query_gen = $this->db->get();
        $comptes_gen = $query_gen->result_array();

        // Créer un tableau des pilotes ayant un compte en section 4
        $pilotes_avec_compte_gen = array();
        foreach ($comptes_gen as $compte) {
            if (!empty($compte['pilote'])) {
                $pilotes_avec_compte_gen[$compte['pilote']] = true;
            }
        }

        // Trouver les pilotes qui n'ont PAS de compte en section 4
        $pilotes_manquants = array();
        foreach ($comptes_sections as $compte) {
            $pilote = $compte['pilote'];
            if (!empty($pilote) && !isset($pilotes_avec_compte_gen[$pilote])) {
                // Ajouter seulement une fois par pilote
                if (!isset($pilotes_manquants[$pilote])) {
                    $pilotes_manquants[$pilote] = $compte;
                }
            }
        }

        echo "<h2>Nombre de pilotes sans compte Services généraux: " . count($pilotes_manquants) . "</h2>";
        echo "<hr>";

        if (count($pilotes_manquants) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
            echo "<thead>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Pilote (login)</th>";
            echo "<th>Nom</th>";
            echo "<th>ID compte section</th>";
            echo "<th>Section</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $section_names = array(1 => 'Planeur', 2 => 'ULM', 3 => 'Avion');

            foreach ($pilotes_manquants as $pilote => $compte) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($pilote) . "</td>";
                echo "<td>" . htmlspecialchars($compte['nom']) . "</td>";
                echo "<td>" . htmlspecialchars($compte['id']) . "</td>";
                echo "<td>" . htmlspecialchars($section_names[$compte['club']]) . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p><strong>Tous les pilotes des sections 1/2/3 ont un compte en section Services généraux.</strong></p>";
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
    }

    /**
     * Créer les comptes 411 manquants en section 4
     * URL: /oneshot/creer_comptes_section4
     *
     * Méthode one-shot pour créer automatiquement les comptes 411 en section Services généraux
     */
    function creer_comptes_section4() {
        echo "<h1>Créer les comptes 411 manquants en section Services généraux</h1>";
        echo "<p>Utilisateur connecté: " . htmlspecialchars($this->dx_auth->get_username()) . "</p>";
        echo "<hr>";

        // Vérifier si on est en mode confirmation
        $confirme = $this->input->post('confirme');

        if ($confirme === 'oui') {
            // Mode exécution
            $this->executer_creation_comptes_section4();
        } else {
            // Mode prévisualisation
            $this->previsualiser_creation_comptes_section4();
        }
    }

    /**
     * Prévisualiser les comptes qui seront créés
     */
    private function previsualiser_creation_comptes_section4() {
        // Récupérer les comptes 411 manquants en section 4
        $comptes_a_creer = $this->get_comptes_a_creer_section4();

        echo "<h2>Prévisualisation: " . count($comptes_a_creer) . " comptes 411 disponibles pour création en section 4</h2>";
        echo "<hr>";

        if (count($comptes_a_creer) > 0) {
            echo "<form method='post' action='" . base_url() . "index.php/oneshot/creer_comptes_section4'>";

            echo "<p>";
            echo "<button type='button' onclick='selectAll()' style='padding: 5px 10px; margin-right: 10px;'>Tout sélectionner</button>";
            echo "<button type='button' onclick='deselectAll()' style='padding: 5px 10px;'>Tout désélectionner</button>";
            echo "</p>";

            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
            echo "<thead>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Créer</th>";
            echo "<th>Pilote</th>";
            echo "<th>Nom</th>";
            echo "<th>Compte source (ID)</th>";
            echo "<th>Section source</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $section_names = array(1 => 'Planeur', 2 => 'ULM', 3 => 'Avion');

            foreach ($comptes_a_creer as $index => $compte) {
                echo "<tr>";
                echo "<td style='text-align: center;'>";
                echo "<input type='checkbox' name='comptes_selectionnes[]' value='" . $index . "' class='compte-checkbox' checked>";
                echo "</td>";
                echo "<td>" . htmlspecialchars($compte['pilote']) . "</td>";
                echo "<td>" . htmlspecialchars($compte['nom']) . "</td>";
                echo "<td>" . htmlspecialchars($compte['id']) . "</td>";
                echo "<td>" . htmlspecialchars($section_names[$compte['club']]) . "</td>";

                // Stocker les données du compte en champs cachés
                echo "<input type='hidden' name='compte_data[" . $index . "][id]' value='" . htmlspecialchars($compte['id']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][nom]' value='" . htmlspecialchars($compte['nom']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][pilote]' value='" . htmlspecialchars($compte['pilote']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][desc]' value='" . htmlspecialchars($compte['desc']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][actif]' value='" . htmlspecialchars($compte['actif']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][club]' value='" . htmlspecialchars($compte['club']) . "'>";
                echo "<input type='hidden' name='compte_data[" . $index . "][masked]' value='" . htmlspecialchars($compte['masked']) . "'>";

                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";

            echo "<hr>";
            echo "<p style='font-weight: bold; color: red;'>ATTENTION: Cette opération va créer les comptes 411 sélectionnés en section Services généraux.</p>";
            echo "<p>Voulez-vous continuer ?</p>";
            echo "<button type='submit' name='confirme' value='oui' style='background-color: #28a745; color: white; padding: 10px 20px; font-size: 16px; cursor: pointer;'>OUI - Créer les comptes sélectionnés</button>";
            echo " ";
            echo "<a href='" . base_url() . "index.php/oneshot' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;'>NON - Annuler</a>";
            echo "</form>";

            // JavaScript pour sélectionner/désélectionner tous les comptes
            echo "<script>";
            echo "function selectAll() {";
            echo "  var checkboxes = document.querySelectorAll('.compte-checkbox');";
            echo "  checkboxes.forEach(function(checkbox) { checkbox.checked = true; });";
            echo "}";
            echo "function deselectAll() {";
            echo "  var checkboxes = document.querySelectorAll('.compte-checkbox');";
            echo "  checkboxes.forEach(function(checkbox) { checkbox.checked = false; });";
            echo "}";
            echo "</script>";
        } else {
            echo "<p><strong>Aucun compte à créer. Tous les pilotes ont déjà un compte en section Services généraux.</strong></p>";
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
    }

    /**
     * Exécuter la création des comptes
     */
    private function executer_creation_comptes_section4() {
        // Récupérer les comptes sélectionnés
        $comptes_selectionnes = $this->input->post('comptes_selectionnes');
        $compte_data = $this->input->post('compte_data');

        if (empty($comptes_selectionnes)) {
            echo "<h2 style='color: red;'>Aucun compte sélectionné</h2>";
            echo "<p>Veuillez retourner et sélectionner au moins un compte à créer.</p>";
            echo "<hr>";
            echo "<p><a href='" . base_url() . "index.php/oneshot/creer_comptes_section4'>Retour</a></p>";
            return;
        }

        echo "<h2>Création de " . count($comptes_selectionnes) . " comptes en cours...</h2>";
        echo "<hr>";

        $nb_crees = 0;
        $erreurs = array();

        foreach ($comptes_selectionnes as $index) {
            if (!isset($compte_data[$index])) {
                $erreurs[] = "Données manquantes pour l'index " . $index;
                continue;
            }

            $compte_source = $compte_data[$index];

            try {
                // Préparer les données du nouveau compte
                $nouveau_compte = array(
                    'nom' => $compte_source['nom'],
                    'pilote' => $compte_source['pilote'],
                    'desc' => $compte_source['desc'],
                    'codec' => '411',
                    'actif' => $compte_source['actif'],
                    'debit' => 0.00,
                    'credit' => 0.00,
                    'saisie_par' => $this->dx_auth->get_username(),
                    'club' => 4,  // Section Services généraux
                    'masked' => $compte_source['masked']
                );

                // Insérer le nouveau compte
                $this->db->insert('comptes', $nouveau_compte);
                $nouveau_id = $this->db->insert_id();

                if ($nouveau_id) {
                    $nb_crees++;
                    echo "<p style='color: green;'>✓ Compte créé pour " . htmlspecialchars($compte_source['pilote']) . " (" . htmlspecialchars($compte_source['nom']) . ") - ID: " . $nouveau_id . "</p>";
                } else {
                    $erreurs[] = "Erreur lors de la création du compte pour " . htmlspecialchars($compte_source['pilote']);
                }
            } catch (Exception $e) {
                $erreurs[] = "Exception pour " . htmlspecialchars($compte_source['pilote']) . ": " . $e->getMessage();
            }
        }

        echo "<hr>";
        echo "<h2 style='color: green;'>Résumé: " . $nb_crees . " comptes créés avec succès</h2>";

        if (count($erreurs) > 0) {
            echo "<h3 style='color: red;'>Erreurs rencontrées:</h3>";
            echo "<ul>";
            foreach ($erreurs as $erreur) {
                echo "<li>" . htmlspecialchars($erreur) . "</li>";
            }
            echo "</ul>";
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot/comptes_manquants'>Vérifier les comptes manquants</a></p>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
    }

    /**
     * Récupérer la liste des comptes à créer en section 4
     */
    private function get_comptes_a_creer_section4() {
        // Récupérer tous les comptes 411 des sections 1, 2, 3
        $this->db->select('id, nom, pilote, desc, codec, actif, club, masked');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where_in('club', array(1, 2, 3));
        $this->db->order_by('pilote', 'ASC');
        $query_sections = $this->db->get();
        $comptes_sections = $query_sections->result_array();

        // Récupérer tous les pilotes ayant déjà un compte en section 4
        $this->db->select('pilote');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where('club', 4);
        $query_gen = $this->db->get();
        $comptes_gen = $query_gen->result_array();

        $pilotes_avec_compte_gen = array();
        foreach ($comptes_gen as $compte) {
            if (!empty($compte['pilote'])) {
                $pilotes_avec_compte_gen[$compte['pilote']] = true;
            }
        }

        // Filtrer pour garder uniquement ceux sans compte en section 4
        $comptes_a_creer = array();
        foreach ($comptes_sections as $compte) {
            $pilote = $compte['pilote'];
            if (!empty($pilote) && !isset($pilotes_avec_compte_gen[$pilote])) {
                // Ajouter seulement une fois par pilote
                if (!isset($comptes_a_creer[$pilote])) {
                    $comptes_a_creer[$pilote] = $compte;
                }
            }
        }

        return array_values($comptes_a_creer);
    }

    /**
     * Méthode utilitaire pour afficher les écritures entre deux comptes
     *
     * @param int $compte_id1 ID du premier compte
     * @param int $compte_id2 ID du second compte
     * @param string $titre Titre de la page
     */
    private function afficher_ecritures_entre_comptes($compte_id1, $compte_id2, $titre) {
        echo "<h1>" . htmlspecialchars($titre) . "</h1>";
        echo "<p>Utilisateur connecté: " . htmlspecialchars($this->dx_auth->get_username()) . "</p>";

        // Afficher les messages de succès ou d'erreur
        if ($this->session->flashdata('success')) {
            echo "<div style='padding: 10px; margin: 10px 0; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px;'>";
            echo htmlspecialchars($this->session->flashdata('success'));
            echo "</div>";
        }
        if ($this->session->flashdata('error')) {
            echo "<div style='padding: 10px; margin: 10px 0; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;'>";
            echo htmlspecialchars($this->session->flashdata('error'));
            echo "</div>";
        }

        echo "<hr>";

        // Récupérer les écritures où compte1 = $compte_id1 et compte2 = $compte_id2 (ou inversement)
        $this->db->select('ecritures.*,
            c1.nom as compte1_nom, c1.codec as compte1_codec,
            c2.nom as compte2_nom, c2.codec as compte2_codec');
        $this->db->from('ecritures');
        $this->db->join('comptes as c1', 'ecritures.compte1 = c1.id', 'left');
        $this->db->join('comptes as c2', 'ecritures.compte2 = c2.id', 'left');

        // Condition: (compte1=$compte_id1 ET compte2=$compte_id2) OU (compte1=$compte_id2 ET compte2=$compte_id1)
        $this->db->where('(ecritures.compte1 = ' . (int)$compte_id1 . ' AND ecritures.compte2 = ' . (int)$compte_id2 . ') OR (ecritures.compte1 = ' . (int)$compte_id2 . ' AND ecritures.compte2 = ' . (int)$compte_id1 . ')', NULL, FALSE);

        $this->db->order_by('ecritures.date_op', 'DESC');

        $query = $this->db->get();
        $ecritures = $query->result_array();

        // Charger tous les comptes 411 de la section Services généraux (club = 4)
        $this->db->select('id, nom, pilote');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where('club', 4);
        $query_comptes = $this->db->get();
        $comptes_411 = $query_comptes->result_array();

        // Compter les entrées correspondantes et non-correspondantes
        $nb_matches = 0;
        $nb_non_matches = 0;
        foreach ($ecritures as $ecriture) {
            $matched_compte = $this->trouver_compte_411($ecriture['description'], $comptes_411);
            if ($matched_compte) {
                $nb_matches++;
            } else {
                $nb_non_matches++;
            }
        }

        echo "<h2>Nombre d'écritures trouvées: " . count($ecritures) . "</h2>";
        echo "<p style='font-size: 1.1em;'>";
        echo "<strong style='color: green;'>Correspondances trouvées: " . $nb_matches . "</strong> | ";
        echo "<strong style='color: red;'>Non trouvées: " . $nb_non_matches . "</strong>";
        echo "</p>";
        echo "<hr>";

        if (count($ecritures) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
            echo "<thead>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>ID</th>";
            echo "<th>Date</th>";
            echo "<th>Compte 1</th>";
            echo "<th>Compte 2</th>";
            echo "<th>Description</th>";
            echo "<th>Montant</th>";
            echo "<th>Référence</th>";
            echo "<th>Gel</th>";
            echo "<th>Compte 411 correspondant</th>";
            echo "<th>Action</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $total_montant = 0;
            foreach ($ecritures as $ecriture) {
                // Rechercher le compte 411 correspondant
                $matched_compte = $this->trouver_compte_411($ecriture['description'], $comptes_411);
                echo "<tr>";
                echo "<td>" . htmlspecialchars($ecriture['id']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['date_op']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['compte1_codec']) . " - " . htmlspecialchars($ecriture['compte1_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['compte2_codec']) . " - " . htmlspecialchars($ecriture['compte2_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['description']) . "</td>";
                echo "<td style='text-align: right;'>" . number_format($ecriture['montant'], 2, ',', ' ') . " €</td>";
                echo "<td>" . htmlspecialchars($ecriture['num_cheque']) . "</td>";
                echo "<td>" . ($ecriture['gel'] ? 'Oui' : 'Non') . "</td>";

                // Afficher le compte 411 correspondant
                if ($matched_compte) {
                    echo "<td>" . htmlspecialchars($matched_compte['nom']) . " (ID: " . $matched_compte['id'] . ")</td>";
                } else {
                    $pattern = $this->extraire_pattern_recherche($ecriture['description']);
                    echo "<td style='color: red; font-weight: bold;'>Non trouvé - Pattern: " . htmlspecialchars($pattern) . "</td>";
                }

                // Afficher le bouton d'action
                echo "<td>";
                if ($matched_compte) {
                    echo "<form method='post' action='" . base_url() . "index.php/oneshot/process_cotisation' style='margin: 0;'>";
                    echo "<input type='hidden' name='ecriture_id' value='" . $ecriture['id'] . "'>";
                    echo "<button type='submit' style='padding: 5px 10px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 3px;'>Traiter</button>";
                    echo "</form>";
                } else {
                    echo "-";
                }
                echo "</td>";

                echo "</tr>";

                $total_montant += $ecriture['montant'];
            }

            echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
            echo "<td colspan='5' style='text-align: right;'>TOTAL:</td>";
            echo "<td style='text-align: right;'>" . number_format($total_montant, 2, ',', ' ') . " €</td>";
            echo "<td colspan='4'></td>";
            echo "</tr>";

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p><strong>Aucune écriture trouvée entre les comptes " . $compte_id1 . " et " . $compte_id2 . ".</strong></p>";
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
    }

    /**
     * Trouver le compte 411 correspondant à la description
     *
     * @param string $description Description de l'écriture
     * @param array $comptes_411 Liste des comptes 411
     * @return array|null Compte trouvé ou null
     */
    private function trouver_compte_411($description, $comptes_411) {
        // Extraire le pattern de recherche AVANT normalisation pour détecter le nom de famille
        $pattern_original = $this->extraire_pattern_recherche($description, false);
        if (empty($pattern_original)) {
            return null;
        }

        // Détecter le nom de famille (mot en MAJUSCULES avec au moins 2 caractères)
        $pattern_original_clean = preg_replace('/[^\w\s]/u', ' ', $pattern_original);
        $pattern_original_clean = preg_replace('/\s+/', ' ', $pattern_original_clean);
        $pattern_original_clean = trim($pattern_original_clean);
        $original_words = preg_split('/\s+/', $pattern_original_clean);

        $nom_famille_index = -1;
        for ($i = 0; $i < count($original_words); $i++) {
            $word = $original_words[$i];
            // Nom de famille = mot en MAJUSCULES avec au moins 2 caractères
            if (strlen($word) >= 2 && $word === mb_strtoupper($word, 'UTF-8') && ctype_alpha($word)) {
                $nom_famille_index = $i;
                break;
            }
        }

        // Extraire le pattern normalisé (minuscules, sans accents)
        $pattern = $this->extraire_pattern_recherche($description, true);
        if (empty($pattern)) {
            return null;
        }

        // Supprimer la ponctuation du pattern (points, virgules, points d'interrogation, etc.)
        $pattern = preg_replace('/[^\w\s]/u', ' ', $pattern);
        $pattern = preg_replace('/\s+/', ' ', $pattern);
        $pattern = trim($pattern);

        // Découper le pattern en mots
        $pattern_words = preg_split('/\s+/', $pattern);
        if (empty($pattern_words)) {
            return null;
        }

        // Identifier le mot qui correspond au nom de famille dans le pattern normalisé
        $nom_famille_word = ($nom_famille_index >= 0 && $nom_famille_index < count($pattern_words))
            ? $pattern_words[$nom_famille_index]
            : null;

        // Chercher une correspondance dans les comptes 411
        foreach ($comptes_411 as $compte) {
            // Normaliser le nom du compte (minuscules + sans accents + sans ponctuation)
            $nom_normalized = mb_strtolower($compte['nom'], 'UTF-8');
            $nom_normalized = $this->remove_accents($nom_normalized);
            $nom_normalized = preg_replace('/[^\w\s]/u', ' ', $nom_normalized);
            $nom_normalized = preg_replace('/\s+/', ' ', $nom_normalized);
            $nom_normalized = trim($nom_normalized);

            // Stratégie de matching flexible (ordre agnostique + fuzzy):
            // - Le nom de famille (détecté en MAJUSCULES) DOIT correspondre
            // - Au moins un prénom doit correspondre
            // - Utiliser fuzzy matching pour gérer les variations d'orthographe

            $compte_words = preg_split('/\s+/', $nom_normalized);

            // Vérifier d'abord le nom de famille s'il a été détecté
            $nom_famille_matched = false;
            if ($nom_famille_word) {
                // Vérifier correspondance exacte
                if (strpos($nom_normalized, $nom_famille_word) !== false) {
                    $nom_famille_matched = true;
                } else {
                    // Vérifier avec fuzzy matching
                    foreach ($compte_words as $compte_word) {
                        if (empty($compte_word)) {
                            continue;
                        }
                        $distance = levenshtein($nom_famille_word, $compte_word);
                        if ($distance <= 1 && strlen($nom_famille_word) >= 3 && strlen($compte_word) >= 3) {
                            $nom_famille_matched = true;
                            break;
                        }
                    }
                }

                // Si le nom de famille ne correspond pas, ce n'est pas la bonne personne
                if (!$nom_famille_matched) {
                    continue;
                }
            }

            // Compter combien d'autres mots (prénoms) correspondent
            $prenoms_found = 0;
            foreach ($pattern_words as $idx => $pattern_word) {
                // Ignorer le nom de famille déjà vérifié
                if ($nom_famille_word && $idx === $nom_famille_index) {
                    continue;
                }

                if (empty($pattern_word)) {
                    continue;
                }

                $word_matched = false;

                // Vérifier correspondance exacte
                if (strpos($nom_normalized, $pattern_word) !== false) {
                    $word_matched = true;
                } else {
                    // Vérifier avec fuzzy matching
                    foreach ($compte_words as $compte_word) {
                        if (empty($compte_word)) {
                            continue;
                        }
                        $distance = levenshtein($pattern_word, $compte_word);
                        if ($distance <= 1 && strlen($pattern_word) >= 3 && strlen($compte_word) >= 3) {
                            $word_matched = true;
                            break;
                        }
                    }
                }

                if ($word_matched) {
                    $prenoms_found++;
                }
            }

            // Décision de matching:
            // - Si nom de famille détecté: il doit matcher + au moins un prénom
            // - Sinon: au moins N-1 mots doivent matcher (ancien comportement)
            if ($nom_famille_word) {
                // Nom de famille obligatoire + au moins un prénom
                if ($nom_famille_matched && $prenoms_found >= 1) {
                    return $compte;
                }
            } else {
                // Pas de nom de famille détecté: utiliser l'ancienne logique
                $total_matched = $prenoms_found;
                $pattern_count = count($pattern_words);
                $min_required = ($pattern_count == 2) ? 2 : max(1, $pattern_count - 1);

                if ($total_matched >= $min_required) {
                    return $compte;
                }
            }
        }

        return null;
    }

    /**
     * Extraire le pattern de recherche de la description
     *
     * @param string $description Description de l'écriture
     * @param bool $normalize Si true, convertit en minuscules et supprime les accents
     * @return string Pattern de recherche
     */
    private function extraire_pattern_recherche($description, $normalize = true) {
        // 1. Supprimer "Cotisation" ou "Cotisations"
        $pattern = preg_replace('/cotisations?/i', '', $description);

        // 2. Supprimer l'année (4 chiffres)
        $pattern = preg_replace('/\b\d{4}\b/', '', $pattern);

        // 3. Supprimer le contenu entre parenthèses
        $pattern = preg_replace('/\([^)]*\)/', '', $pattern);

        // 4. Supprimer "payé le ..." ou "payée le ..." jusqu'à la fin
        $pattern = preg_replace('/payée?\s+le\s+.*/i', '', $pattern);

        // 5. Supprimer les titres de civilité (Mr, Mme, M., Mlle, etc.)
        $pattern = preg_replace('/\b(mr|mme|m\.|mlle|monsieur|madame|mademoiselle)\b\.?/i', '', $pattern);

        // 6. Nettoyer les espaces multiples et trim
        $pattern = preg_replace('/\s+/', ' ', $pattern);
        $pattern = trim($pattern);

        // 7. Normaliser si demandé
        if ($normalize) {
            // Convertir en minuscules
            $pattern = mb_strtolower($pattern, 'UTF-8');

            // Normaliser les accents
            $pattern = $this->remove_accents($pattern);
        }

        return $pattern;
    }

    /**
     * Supprimer les accents d'une chaîne
     *
     * @param string $string Chaîne avec accents
     * @return string Chaîne sans accents
     */
    private function remove_accents($string) {
        $accents = array(
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c',
            'œ' => 'oe', 'æ' => 'ae'
        );
        return strtr($string, $accents);
    }

}
