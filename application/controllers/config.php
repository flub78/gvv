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
class Config extends CI_Controller {

    // Définition des caractéristiques des champs à afficher
    private $fields = array (
            'sigle_club' => array (
                    'label' => 'Sigle',
                    'rules' => 'trim|required'
            ),

            'nom_club' => array (
                    'label' => 'Nom',
                    'rules' => 'trim|required'
            ),

            'code_club' => array (
                    'label' => "Code d'identification",
                    'rules' => 'trim'
            ),

            'adresse_club' => array (
                    'label' => 'Rue',
                    'rules' => 'trim|required'
            ),

            'cp_club' => array (
                    'label' => 'Code postal',
                    'rules' => 'trim|required'
            ),

            'ville_club' => array (
                    'label' => 'Ville',
                    'rules' => 'trim|required'
            ),

            'tel_club' => array (
                    'label' => 'Téléphone',
                    'rules' => 'trim|required'
            ),

            'email_club' => array (
                    'label' => 'E-mail',
                    'rules' => 'trim|required'
            ),

            'url_club' => array (
                    'label' => 'Site WEB',
                    'rules' => 'trim'
            ),

            'calendar_id' => array (
                    'label' => 'Calendrier Google',
                    'rules' => 'trim'
            ),

            'theme' => array (
                    'label' => 'Thème graphique'
            ),

            'palette' => array (
                    'label' => 'Palette de couleurs'
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

            'logo_club' => array (
                    'label' => 'Logo',
                    'rules' => ''
            ),

            'mod' => array (
                    'label' => 'Message du jour',
                    'rules' => ''
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

        $this->config->load('club');
        $this->load->helper('file');
        $this->load->helper('directory');
        $this->load->helper('validation_helper');

        $themes_dir = directory_map("./themes");
        foreach ( $themes_dir as $key => $values ) {
            $options [$key] = $key;
        }
        $this->fields ['theme'] ['options'] = $options;
    }

    private function color_list() {
        $color_list = array (
                'base',
                'black-tie',
                'blitzer',
                'cupertino',
                'dark-hive',
                'dot-luv',
                'eggplant',
                'excite-bike',
                'flick',
                'hot-sneaks',
                'humanity',
                'le-frog',
                'mint-choc',
                'overcast',
                'pepper-grinder',
                'redmond',
                'smoothness',
                'south-street',
                'start',
                'sunny',
                'swanky-purse',
                'trontastic',
                'ui-darkness',
                'ui-lightness',
                'vader'
        );
        $colors = array ();
        foreach ( $color_list as $color ) {
            $colors [$color] = $color;
        }
        return $colors;
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
        
        $this->load->model('tarifs_model');

        $data ['colors'] = $this->color_list();
        $data['product_selector'] = $this->tarifs_model->selector();
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
            $config ['upload_path'] = './uploads/';
            $config ['allowed_types'] = 'jpg|gif|png';
            $config ['max_size'] = '100';
            // $config['filename']= 'logo.png';
            $config ['encrypt_name'] = TRUE;

            $this->load->library('upload', $config);
            if ($this->upload->do_upload()) {
                $uploaded = $this->upload->data();
                // print_r($uploaded);
                // echo "file=" . $uploaded['file_name'] . "<br>";
                $logo_club = $this->config->item('logo_club');
                if (isset($logo_club) && file_exists($logo_club)) {
                    unlink($logo_club);
                }
                $data ['logo_club'] = './uploads/' . $uploaded ['file_name'];
            } else {
                gvv_debug("upload problem: " . getcwd() . " " . $this->upload->display_errors());
            }

            if (! $data ['logo_club']) {
                // unset($data['logo_club']);
                $data ['logo_club'] = $this->config->item('logo_club');
            }
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
        $data ['colors'] = $this->color_list();
        load_last_view('configView', $data);
    }

    /**
     * Sauvegarde les nouvelles valeurs de configuration
     */
    private function saveConfig($filename, $data, $fields) {
        // store the form content into configuration and rewrite the file
        $content = "<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Fichier de configuration club
|--------------------------------------------------------------------------
*/\n";

        foreach ( $fields as $key => $value ) {
            if (isset($data [$key])) {
                $val = $data [$key];

                $val = str_replace('"', '\"', $val); // escape double quotes

                $this->config->set_item($key, $val);
                $content .= "\$config['$key'] = \"$val\";\n";
            }
        }

        $content .= '
/* End of file club.php */
/* Location: .application/config/club.php */';

        // Store the configuration into file
        $filename = "./application/config/club.php";
        if (! $info = get_file_info($filename)) {
            echo "$filename non trouvé" . br();
        }

        if (! write_file($filename, $content)) {
            echo "error writing $filename, check that it is writable by your WEB server";
        }
    }
}
