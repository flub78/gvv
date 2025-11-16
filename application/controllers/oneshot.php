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
        echo "</ul>";

        echo "<hr>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
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

        echo "<h2>Nombre d'écritures trouvées: " . count($ecritures) . "</h2>";
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

                echo "</tr>";

                $total_montant += $ecriture['montant'];
            }

            echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
            echo "<td colspan='5' style='text-align: right;'>TOTAL:</td>";
            echo "<td style='text-align: right;'>" . number_format($total_montant, 2, ',', ' ') . " €</td>";
            echo "<td colspan='3'></td>";
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
        // Extraire le pattern de recherche (nom/prénom) en minuscules
        $pattern = $this->extraire_pattern_recherche($description);
        if (empty($pattern)) {
            return null;
        }

        // Découper le pattern en mots
        $pattern_words = preg_split('/\s+/', $pattern);

        // Chercher une correspondance dans les comptes 411
        foreach ($comptes_411 as $compte) {
            // Normaliser le nom du compte (minuscules + sans accents)
            $nom_normalized = mb_strtolower($compte['nom'], 'UTF-8');
            $nom_normalized = $this->remove_accents($nom_normalized);

            // Vérifier si tous les mots du pattern sont dans le nom du compte
            $all_words_found = true;
            foreach ($pattern_words as $word) {
                if (!empty($word) && strpos($nom_normalized, $word) === false) {
                    $all_words_found = false;
                    break;
                }
            }

            if ($all_words_found) {
                return $compte;
            }
        }

        return null;
    }

    /**
     * Extraire le pattern de recherche de la description
     *
     * @param string $description Description de l'écriture
     * @return string Pattern de recherche
     */
    private function extraire_pattern_recherche($description) {
        // 1. Supprimer "Cotisation" ou "Cotisations"
        $pattern = preg_replace('/cotisations?/i', '', $description);

        // 2. Supprimer l'année (4 chiffres)
        $pattern = preg_replace('/\b\d{4}\b/', '', $pattern);

        // 3. Supprimer "payé le ..." ou "payée le ..." jusqu'à la fin
        $pattern = preg_replace('/payée?\s+le\s+.*/i', '', $pattern);

        // 4. Nettoyer les espaces multiples et trim
        $pattern = preg_replace('/\s+/', ' ', $pattern);
        $pattern = trim($pattern);

        // 5. Convertir en minuscules
        $pattern = mb_strtolower($pattern, 'UTF-8');

        // 6. Normaliser les accents
        return $this->remove_accents($pattern);
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
