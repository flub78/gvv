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
 * File: config.php
 * config controller.
 */
class Config extends MY_Controller {

    // Définition des caractéristiques des champs à afficher.
    // L'identité du club (sigle, nom, adresse, e-mail, site, logo, contact RGPD)
    // se modifie dans l'onglet Club de la page Configuration (parametres/index/club),
    // où chaque modification est tracée : elle n'est plus modifiable ici.
    private $fields = array (
            'calendar_id' => array (
                    'label' => 'Calendrier Google',
                    'rules' => 'trim'
            ),

            'club' => array (
                    'label' => 'Type de facturation'
            ),

            'url_gcalendar' => array (
                    'label' => 'URL Google calendar',
                    'rules' => 'trim'
            ),

            'url_planche_auto' => array (
                    'label' => 'URL Planche Automatique',
                    'rules' => 'trim'
            ),

            'ffvv_id' => array (
                    'label' => 'Identifiant de connexion FFVV',
                    'rules' => ''
            ),
            'ffvv_pwd' => array (
                    'label' => 'Mot de passe FFVV',
                    'rules' => ''
            ),
            'ffvv_product' => array (
                    'label' => 'Produit pour la facturation des licences',
                    'rules' => ''
            ),
						'gesasso' => array (
                    'label' => 'Export vers Gesasso',
                    'rules' => ''
            ),

            'maintenance_message' => array (
                    'label' => 'Message de maintenance',
                    'rules' => 'trim'
            )
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        if (! $this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }
        $this->dx_auth->check_uri_permissions();

        $this->require_roles(['club-admin']);

        $this->config->load('club');
        $this->load->helper('file');
        $this->load->helper('validation_helper');

        // Bouton retour → tableau de bord Administration du club
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    /**
     * Methode par défaut du controleur
     * Affiche le formulaire de configuration
     */
    public function index() {
        foreach ( $this->fields as $field => $value ) {
            $data [$field] = $this->config->item($field);
            if (is_array($data [$field])) {
                $data [$field] = join(",", $data [$field]);
            }
        }
        $data ['fields'] = $this->fields;
        $data ['controller'] = 'config';
        $data ['action'] = "modification";

        $this->load->model('produits_model');

        $data['product_selector'] = $this->produits_model->selector();
        load_last_view('configView', $data);
    }

    /**
     * Validates the configuration changes
     */
    public function formValidation() {
        // Validates the form entries
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        $data = array ();
        foreach ( $this->fields as $field => $value ) {
            $rules = array_key_exists('rules', $value) ? $value ['rules'] : '';
            $label = array_key_exists('label', $value) ? $value ['label'] : $field;
            $this->form_validation->set_rules($field, $label, $rules);
            $data [$field] = $this->input->post($field);
        }

        if ($this->form_validation->run()) {
            gvv_debug("config validée");
            $this->saveConfig("./application/config/club.php", $data, $this->fields);

            $data ['title'] = 'Configuration club';
            $data ['text'] = 'Configuration modifiée avec succès.';
            load_last_view('message', $data);
            return;
        } else {
            echo validation_errors();
        }
        // display it again
        $data ['fields'] = $this->fields;
        $data ['controller'] = 'config';
        $data ['action'] = "modification";
        load_last_view('configView', $data);
    }

    /**
     * Sauvegarde les nouvelles valeurs de configuration.
     *
     * Seules les clés de ce formulaire sont réécrites, une à une : les autres clés
     * du fichier (contact RGPD, thème, clés écrites par l'installeur ou par la page
     * Configuration) sont conservées.
     */
    private function saveConfig($filename, $data, $fields) {
        $this->load->library('Config_file_writer');
        foreach ( $fields as $key => $value ) {
            if (!isset($data [$key])) {
                continue;
            }
            // Case non cochée : input->post() rend FALSE, enregistré comme chaîne vide.
            $val = is_array($data [$key]) ? join(",", $data [$key]) : (string) $data [$key];
            if (! $this->config_file_writer->write($filename, $key, $val)) {
                echo "error writing $filename ($key: " . $this->config_file_writer->error
                    . "), check that it is writable by your WEB server" . br();
                return;
            }
            $this->config->set_item($key, $val);
        }
    }
}
