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
 * Rapprochements bancaires
 */
class Rapprochements extends CI_Controller {

    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        $this->dx_auth->check_login();

        $this->load->helper('validation');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
        $this->load->model('sections_model');
        $this->load->library('SoldesParser');
    }

    /**
     * Page de selection du fichier journal OpenFLyers
     */
    function select_releve() {
        $data = array();

        $bank_selector = $this->comptes_model->selector_with_null([
            "codec >=" => "5",
            'codec <' => "6"
        ], TRUE);
        $this->gvvmetadata->set_selector('bank_selector', $bank_selector);
        $data['bank_selector'] = $bank_selector;
        $data['bank_account'] = ''; // Default value for the dropdown
        load_last_view('rapprochements/select_releve', $data);
    }

    /**
     * Import a CSV journal 
     */
    public function import_releve() {
        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path, 0755)) {
                die("Cannot create " . $upload_path . " with proper permissions.");
            }
        }

        // delete all files in the uploads/restore directory
        // these files are temporary, there is no need to keep them
        // I only keep the last one for debugging
        $files = glob($upload_path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                if (!unlink($file)) {
                    gvv_error("Failed to delete file: $file");
                }
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size'] = '1500'; // in kilobytes (KB)
        $config['overwrite'] = TRUE;

        $this->load->library('upload', $config);


        if (! $this->upload->do_upload()) {
            // On a pas réussi à recharger le fichier
            // On affiche le message d'erreur
            $error = array(
                'error' => $this->upload->display_errors()
            );
            load_last_view('rapprochements/select_releve', $error);
        } else {

            // on a chargé le fichier
            $data = $this->upload->data();

            $filename = $config['upload_path'] . $data['file_name'];
            $this->session->set_userdata('file_operations', $filename);
            $this->import_releve_from_files($filename);
        }
    }

    /**
     * Import a CSV listing from a file
     */
    public function import_releve_from_files($filename, $status = "") {

        $this->load->library('ReleveParser');

        try {

            $parser = new ReleveParser();

            try {
                $parser->parse($filename);
            } catch (Exception $e) {
                $msg = "Erreur: " . $e->getMessage() . "\n";
                gvv_error($msg);

                $error = array(
                    'error' => $msg
                );
                load_last_view('rapprochements/select_releve', $error);
                return;
            }
            $releve = $parser->parse($filename);

            echo "\nDonnées importées depuis le fichier: $filename\n";
            echo '<pre>' . print_r($releve, true) . '</pre>';
            exit;

            $data['titre'] = $grand_journal['header']['titre'];
            $data['date_edition'] = $grand_journal['header']['date_edition'];

            $comptes_html = $parser->OperationsTableToHTML($grand_journal);
            $data['comptes_html'] = $comptes_html;
            $data['section'] = $this->sections_model->section();
            $data['status'] = $status;
            // Sauvegarder en JSON
            // file_put_contents('grand_livre_parsed.json', $parser->toJson());
            // echo "\nDonnées sauvegardées dans grand_livre_parsed.json\n";

            load_last_view('openflyers/tableOperations', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
        }
    }



    /**
     * Inserts a movement with the given parameters
     *
     * @param array $params Associative array of movement parameters
     * @throws Exception If there is an error during the insertion of the movement 
     */
    public function insert_movement(array $params) {

        // Quel est la section courante?
        $section = $this->sections_model->section();

        // Il faut une section active pour importer les écritures
        if (!$section) return false;

        $montant = 0;
        $num_cheque = "OpenFlyers : " . $params['description'];
        $data = array(
            'annee_exercise' => date('Y', $params['date']),
            'date_op' => $params['date'],
            'date_creation' => date("Y-m-d"),
            'club' => $section['id'],
            'compte1' => $params['compte1'],
            'compte2' => $params['compte2'],
            'montant' => $montant,
            'description' => $params['intitule'],
            'num_cheque' => $num_cheque,
            'saisie_par' => $this->dx_auth->get_username()
        );

        if ($params['debit'] != "0.00") {
            $data['montant'] = $params['debit'];
        } else {
            $data['montant'] = $params['credit'];
            $data['compte1'] = $params['compte2'];
            $data['compte2'] = $params['compte1'];
        }

        // Si elle existe détruit l'écriture avec le même numéro de flux OpenFlyers
        $this->ecritures_model->delete_all(["club" => $data['club'], 'num_cheque' =>  $data['num_cheque']]);

        // Insert l'écriture
        $ecriture = $this->ecritures_model->create($data);

        if (!$ecriture) {
            throw new Exception("Erreur pendant le passage d'écriture de solde:");
        } else {
            return true;
        }
    }
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */