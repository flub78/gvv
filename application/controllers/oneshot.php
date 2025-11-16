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
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $total_montant = 0;
            foreach ($ecritures as $ecriture) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($ecriture['id']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['date_op']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['compte1_codec']) . " - " . htmlspecialchars($ecriture['compte1_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['compte2_codec']) . " - " . htmlspecialchars($ecriture['compte2_nom']) . "</td>";
                echo "<td>" . htmlspecialchars($ecriture['description']) . "</td>";
                echo "<td style='text-align: right;'>" . number_format($ecriture['montant'], 2, ',', ' ') . " €</td>";
                echo "<td>" . htmlspecialchars($ecriture['num_cheque']) . "</td>";
                echo "<td>" . ($ecriture['gel'] ? 'Oui' : 'Non') . "</td>";
                echo "</tr>";

                $total_montant += $ecriture['montant'];
            }

            echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
            echo "<td colspan='5' style='text-align: right;'>TOTAL:</td>";
            echo "<td style='text-align: right;'>" . number_format($total_montant, 2, ',', ' ') . " €</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";

            echo "</tbody>";
            echo "</table>";

            echo "<hr>";
            echo "<h3>Détails complets (var_dump):</h3>";
            echo "<pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
            var_dump($ecritures);
            echo "</pre>";
        } else {
            echo "<p><strong>Aucune écriture trouvée entre les comptes " . $compte_id1 . " et " . $compte_id2 . ".</strong></p>";
        }

        echo "<hr>";
        echo "<p><a href='" . base_url() . "index.php/oneshot'>Retour à la liste des opérations</a></p>";
        echo "<p><a href='" . base_url() . "'>Retour à l'accueil</a></p>";
    }

}
