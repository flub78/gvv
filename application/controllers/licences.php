<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource avion.php *
 * Playwright tests:
 *   - npx playwright test tests/licences-checkbox.spec.js * @package controllers
 * Controleur de gestion des avions.
 */
include ('./application/libraries/Gvv_Controller.php');
class Licences extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'licences';
    protected $model = 'licences_model';
    protected $modification_level = 'ca'; // Legacy authorization for non-migrated users
    protected $use_new_auth = FALSE; // Use legacy authorization system
    protected $rules = array ();


    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        }
    }

    /**
     * Licences par année
     */
    public function per_year() {
        $this->push_return_url("Tarifs");

        $data ['controller'] = $this->controller;
        $data ['year'] = $this->session->userdata('year');
        $data ['year_selector'] = $this->gvv_model->getYearSelector("date");
        $data ['type'] = $this->session->userdata('licence_type');

        // Gestion de la plage d'années
        $current_year = (int)date("Y");
        $min_year_data = $this->gvv_model->get_min_year();
        $max_year_data = $this->gvv_model->get_max_year();

        // Initialiser les valeurs par défaut si pas encore définies
        if (!$this->session->userdata('licence_year_max')) {
            // Utiliser l'année max de la base de données
            $this->session->set_userdata('licence_year_max', $max_year_data);
        }
        if (!$this->session->userdata('licence_year_min')) {
            // Année de début = année de fin - 5
            $this->session->set_userdata('licence_year_min', $max_year_data - 5);
        }
        if (!$this->session->userdata('licence_member_status')) {
            $this->session->set_userdata('licence_member_status', 'active');
        }
        if (!$this->session->userdata('licence_section_id')) {
            $this->session->set_userdata('licence_section_id', 'all');
        }

        $year_min = (int)$this->session->userdata('licence_year_min');
        $year_max = (int)$this->session->userdata('licence_year_max');
        $member_status = $this->session->userdata('licence_member_status');
        $section_id = $this->session->userdata('licence_section_id');

        // Charger la liste des sections
        $this->load->model('sections_model');
        $sections = $this->sections_model->section_list();

        // Passer les données à la vue
        $data['year_min'] = $year_min;
        $data['year_max'] = $year_max;
        $data['min_year_data'] = $min_year_data;
        $data['max_year_data'] = $max_year_data;
        $data['current_year'] = $current_year;
        $data['member_status'] = $member_status;
        $data['section_id'] = $section_id;
        $data['sections'] = $sections;

        // Récupérer les données et le total séparément
        $result = $this->gvv_model->per_year($data ['type'], $year_min, $year_max, $member_status, $section_id);
        $data['table'] = $result['data'];
        $data['total'] = $result['total'];

        load_last_view('licences/TablePerYear', $data);
    }

    /**
     * Active la licence pour le pilote et pour l'année
     *
     * @param unknown_type $pilote
     * @param unknown_type $year
     * @param unknown_type $type
     */
    public function set($pilote, $year, $type = 0) {
        // Détection AJAX - vérifier le header directement car is_ajax_request() peut échouer
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        log_message('debug', "Licences::set - pilote=$pilote, year=$year, type=$type, ajax=" . ($is_ajax ? 'YES' : 'NO'));

        $row = array (
                'pilote' => $pilote,
                'year' => $year,
                'type' => $type,
                'date' => "$year-01-01",
                'comment' => ''  // Valeur par défaut pour le champ comment
        );

        // Activer le mode strict des erreurs DB pour cette opération
        $this->db->db_debug = FALSE;

        $result = $this->gvv_model->create($row);

        // Vérifier s'il y a une erreur de base de données (CodeIgniter 2.x)
        $db_error_msg = $this->db->_error_message();
        $db_error_num = $this->db->_error_number();

        if (!empty($db_error_msg) || !empty($db_error_num)) {
            $error_text = "Error #$db_error_num: $db_error_msg";
            log_message('error', "Database error creating licence: " . $error_text);

            if ($is_ajax) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Erreur de base de données: ' . $db_error_msg
                ));
                exit();
            } else {
                show_error('Erreur lors de la création de la licence: ' . $error_text);
            }
        }

        // Si c'est une requête AJAX, retourner JSON
        if ($is_ajax) {
            // Nettoyer tout output précédent
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(array('success' => true, 'data' => $row));
            exit(); // Important : arrêter l'exécution pour éviter tout output supplémentaire
        } else {
            $this->per_year();
        }
    }

    /**
     * Desactive la licence pour le pilote et pour l'année
     *
     * @param unknown_type $pilote
     * @param unknown_type $year
     * @param unknown_type $type
     */
    public function switch_it($pilote, $year, $type = 0) {
        // Détection AJAX - vérifier le header directement car is_ajax_request() peut échouer
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        log_message('debug', "Licences::switch_it - pilote=$pilote, year=$year, type=$type, ajax=" . ($is_ajax ? 'YES' : 'NO'));

        // Activer le mode strict des erreurs DB pour cette opération
        $this->db->db_debug = FALSE;

        $result = $this->gvv_model->delete(array (
                'pilote' => $pilote,
                'year' => $year,
                'type' => $type
        ));

        // Vérifier s'il y a une erreur de base de données (CodeIgniter 2.x)
        $db_error_msg = $this->db->_error_message();
        $db_error_num = $this->db->_error_number();

        if (!empty($db_error_msg) || !empty($db_error_num)) {
            $error_text = "Error #$db_error_num: $db_error_msg";
            log_message('error', "Database error deleting licence: " . $error_text);

            if ($is_ajax) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Erreur de base de données: ' . $db_error_msg
                ));
                exit();
            } else {
                show_error('Erreur lors de la suppression de la licence: ' . $error_text);
            }
        }

        // Si c'est une requête AJAX, retourner JSON
        if ($is_ajax) {
            // Nettoyer tout output précédent
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(array('success' => true));
            exit(); // Important : arrêter l'exécution pour éviter tout output supplémentaire
        } else {
            $this->per_year();
        }
    }

    /**
     * Active le type de licence par défaut
     *
     * @param unknown_type $type
     */
    public function switch_to($type) {
        $this->session->set_userdata('licence_type', $type);
        redirect(controller_url("licences/per_year"));
    }


    /**
     * Met à jour la plage d'années pour l'affichage
     *
     * @param int $year_min Année de début
     * @param int $year_max Année de fin
     */
    public function set_year_range($year_min, $year_max) {
        $year_min = (int)$year_min;
        $year_max = (int)$year_max;

        // Validation : ne pas permettre le croisement
        if ($year_min > $year_max) {
            $temp = $year_min;
            $year_min = $year_max;
            $year_max = $temp;
        }

        $this->session->set_userdata('licence_year_min', $year_min);
        $this->session->set_userdata('licence_year_max', $year_max);

        // Si c'est une requête AJAX, retourner JSON
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($is_ajax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'year_min' => $year_min,
                'year_max' => $year_max
            ));
            exit();
        } else {
            redirect(controller_url("licences/per_year"));
        }
    }

    /**
     * Met à jour le statut des membres à afficher
     *
     * @param string $status Statut ('all', 'active', 'inactive')
     */
    public function set_member_status($status) {
        // Validation : seulement les valeurs autorisées
        $allowed = array('all', 'active', 'inactive');
        if (!in_array($status, $allowed)) {
            $status = 'active';
        }

        $this->session->set_userdata('licence_member_status', $status);

        // Si c'est une requête AJAX, retourner JSON
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($is_ajax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'member_status' => $status
            ));
            exit();
        } else {
            redirect(controller_url("licences/per_year"));
        }
    }

    /**
     * Met à jour la section à afficher
     *
     * @param string $section_id ID de la section ou 'all'
     */
    public function set_section($section_id) {
        // Validation : 'all' ou un ID numérique
        if ($section_id !== 'all' && !is_numeric($section_id)) {
            $section_id = 'all';
        }

        $this->session->set_userdata('licence_section_id', $section_id);

        // Si c'est une requête AJAX, retourner JSON
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($is_ajax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'section_id' => $section_id
            ));
            exit();
        } else {
            redirect(controller_url("licences/per_year"));
        }
    }

}