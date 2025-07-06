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

            // $this->load->library('unzip');
            $filename = $config['upload_path'] . $data['file_name'];

            // $file_content = file_get_contents($filename);
            // echo $file_content;
            // exit;

            $this->load->library('SoldesParser');

            try {
                $parser = new SoldesParser();
                $soldes = $parser->arrayWithControls($filename);
                // // Sauvegarder en JSON
                // file_put_contents('grand_livre_parsed.json', $parser->toJson());
                // echo "\nDonnées sauvegardées dans grand_livre_parsed.json\n";
            } catch (Exception $e) {
                echo "Erreur: " . $e->getMessage() . "\n";
            }

            $data['soldes'] = $soldes;
            load_last_view('openflyers/tableSoldes', $data);
        }
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */