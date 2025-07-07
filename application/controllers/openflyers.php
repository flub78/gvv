<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource welcome.php
 * @package controllers
 *          Page d'acceuil
 */
class OpenFlyers extends CI_Controller {

    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        $this->dx_auth->check_login();

        $this->load->helper('validation');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');
        // $this->load->model('comptes_model');
        // $this->load->model('ecritures_model');

    }

    /**
     * Selection du fichier journal
     */
    function select_operations() {
        $data = array();
        $data['title'] = $this->lang->line("welcome_nyi_title");
        $data['text'] = $this->lang->line("welcome_nyi_text");

        load_last_view('openflyers/select_operations', $data);
    }

    /**
     * Selection des soldes de compte clients
     */
    function select_soldes() {
        $data = array();
        $data['title'] = $this->lang->line("welcome_nyi_title");
        $data['text'] = $this->lang->line("welcome_nyi_text");

        load_last_view('openflyers/select_soldes', $data);
    }

    /**
     * Import a CSV journal
     */
    public function import_operations() {
        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path)) {
                die("Cannot create " . $upload_path);
            }
        }

        // delete all files in the uploads/restore directory
        // these files are temporary, there is no need to keep them
        // I only keep the last one for debugging
        $files = glob($upload_path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size'] = '1500'; // in kilobytes (KB)
        $config['overwrite'] = TRUE;

        $this->load->library('upload', $config);


        if (! $this->upload->do_upload()) {
            // On a pas réussi à recharger la sauvegarde
            $error = array(
                'error' => $this->upload->display_errors()
            );
            load_last_view('openflyers/select_file', $error);
        } else {

            // on a chargé le fichier
            $data = $this->upload->data();

            // $this->load->library('unzip');
            $filename = $config['upload_path'] . $data['file_name'];

            // $file_content = file_get_contents($filename);
            // echo $file_content;

            $this->load->library('GrandLivreParser');

            try {
                $parser = new GrandLivreParser();
                $grand_journal = $parser->parseGrandLivre($filename);

                // Afficher un résumé
                echo "=== RÉSUMÉ DU GRAND LIVRE ===\n";
                $summary = $parser->getSummary();
                echo "Nombre de comptes: " . $summary['nombre_comptes'] . "\n";
                echo "Total des mouvements: " . $summary['total_mouvements'] . "\n\n";

                // Afficher les comptes
                echo "=== COMPTES ===\n";
                foreach ($summary['comptes_resume'] as $compte) {
                    echo "- {$compte['nom']} (OF: {$compte['numero_of']}) - {$compte['nb_mouvements']} mouvements\n";
                }

                // Sauvegarder en JSON
                file_put_contents('grand_livre_parsed.json', $parser->toJson());
                echo "\nDonnées sauvegardées dans grand_livre_parsed.json\n";
            } catch (Exception $e) {
                echo "Erreur: " . $e->getMessage() . "\n";
            }

            exit;

            load_last_view('admin/restore_success', $data);
        }
    }

    /**
     * Import les soldes en CSV
     */
    public function import_soldes() {
        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path)) {
                die("Cannot create " . $upload_path);
            }
        }

        // delete all files in the uploads/restore directory
        // these files are temporary, there is no need to keep them
        // I only keep the last one for debugging
        $files = glob($upload_path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size'] = '1500'; // in kilobytes (KB)
        $config['overwrite'] = TRUE;

        $this->load->library('upload', $config);


        if (! $this->upload->do_upload()) {
            // On a pas réussi à recharger la sauvegarde
            $error = array(
                'error' => $this->upload->display_errors()
            );
            load_last_view('openflyers/select_file', $error);
        } else {

            // on a chargé le fichier
            $data = $this->upload->data();

            $filename = $config['upload_path'] . $data['file_name'];

            // $file_content = file_get_contents($filename);
            // echo $file_content;
            // exit;

            $this->load->library('SoldesParser');

            try {
                $parser = new SoldesParser();
                $soldes = $parser->parse($filename);
                $soldes_html = $parser->arrayWithControls($soldes);

                $line = 0;
                foreach ($soldes as $row) {
                    $this->session->set_userdata('soldes_' . $line, $row);
                    $line++;
                }

                // // Sauvegarder en JSON
                // file_put_contents('grand_livre_parsed.json', $parser->toJson());
                // echo "\nDonnées sauvegardées dans grand_livre_parsed.json\n";
            } catch (Exception $e) {
                echo "Erreur: " . $e->getMessage() . "\n";
            }

            $data['soldes'] = $soldes_html;

            load_last_view('openflyers/tableSoldes', $data);
        }
    }

    /**
     * Génère une écriture d'initialisation de solde pilote
     */
    public function solde_init($compte_gvv, $solde) {

        // Get club info from compte_gvv
        // $compte = $this->comptes_model->get_by_id('id', $compte_gvv);
        // if (!$compte) {
        //     throw new Exception("Compte GVV $compte_gvv non trouvé");
        // }
        
        // // Find fonds associatif account for this section
        // $fonds_associatif = $this->comptes_model->get_by_section_and_codec($compte->club, '102');
        // if (!$fonds_associatif) {
        //     throw new Exception("Compte de fonds associatif non trouvé pour la section " . $compte->id_section);
        // }

        // Generate accounting entries
        return;
        // $data = array(
        //     'annee_exercise' => "2025",
        //     'date_op' => "2025-01-01",
        //     'club' => $compte->id_section,
        //     'compte1' => $compte_gvv,
        //     'compte2' => '',
        //     'libelle' => 'Initialisation du solde',
        //     'id_exercice' => get_current_exercice()
        // );
        
        // $id_ecriture = $this->ecritures_model->insert($data);
        
        // // First line - debit/credit depends on solde sign
        // $this->ecritures_model->insert_ligne(array(
        //     'id_ecriture' => $id_ecriture,
        //     'id_compte' => $compte_gvv,
        //     'debit' => $solde > 0 ? abs($solde) : 0,
        //     'credit' => $solde < 0 ? abs($solde) : 0
        // ));
        
        // // Second line - opposite of first line
        // $this->ecriture_model->insert_ligne(array(
        //     'id_ecriture' => $id_ecriture,
        //     'id_compte' => $fonds_associatif->id,
        //     'debit' => $solde < 0 ? abs($solde) : 0,
        //     'credit' => $solde > 0 ? abs($solde) : 0
        // ));
    }

    /**
     * Scan les parametres post et génère les écritures d'initialisation de solde
     */
    public function creates_soldes() {

        $posts = $this->input->post();
        foreach ($posts as $key => $value) {
            // echo "$key => $value<br>";
            if (strpos($key, 'cb_') === 0) {
                // Key starts with "cb_"
                $line = str_replace("cb_", "", $key);
                $compte_key = "compte_" . $line;
                $compte_value = $posts[$compte_key ];

                $row = $this->session->userdata('soldes_' . $line);
                $id_of = $row[0];
                $nom_of = $row[1];
                $profil = $row[2];
                $type = $row[3];
                $solde = $row[4];

                echo "id_of=$id_of, nom_of=$nom_of, profil=$profil, type=$type, solde=$solde" . "<br>";
                $this->solde_init($compte_value, $solde);
            }
        }
        // print_r($this->input->post());
        // var_dump($_POST);

        exit;
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */