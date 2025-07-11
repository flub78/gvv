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
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
        $this->load->model('sections_model');
        $this->load->library('SoldesParser');
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

            $this->import_operations_from_files($filename);
        }
    }

    /**
     * Import a CSV journal from a file
     */
    public function import_operations_from_files($filename) {

        // $file_content = file_get_contents($filename);
        // echo $file_content;

        $this->load->library('GrandLivreParser');

        try {

            $parser = new GrandLivreParser();
            $grand_journal = $parser->parseGrandLivre($filename);

            $data['titre'] = $grand_journal['header']['titre'];
            $data['date_edition'] = $grand_journal['header']['date_edition'];

            $comptes_html = $parser->OperationsTableToHTML($grand_journal);
            $data['comptes_html'] = $comptes_html;
            $data['section'] = $this->sections_model->section();

            // Sauvegarder en JSON
            file_put_contents('grand_livre_parsed.json', $parser->toJson());
            // echo "\nDonnées sauvegardées dans grand_livre_parsed.json\n";

            // // Afficher un résumé
            // echo "=== RÉSUMÉ DU GRAND LIVRE ===\n";
            // $summary = $parser->getSummary();
            // echo "Nombre de comptes: " . $summary['nombre_comptes'] . "\n";
            // echo "Total des mouvements: " . $summary['total_mouvements'] . "\n\n";

            // // Afficher les comptes
            // echo "=== COMPTES ===\n";
            // foreach ($summary['comptes_resume'] as $compte) {
            //     echo "- {$compte['nom']} (OF: {$compte['numero_of']}) - {$compte['nb_mouvements']} mouvements\n" . '<br>';
            // }

            load_last_view('openflyers/tableOperations', $data);
        } catch (Exception $e) {
            echo "Erreur: " . $e->getMessage() . "\n";
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
            // On a pas réussi à charger le fichier
            $error = array(
                'error' => $this->upload->display_errors()
            );
            load_last_view('openflyers/select_file', $error);
        } else {

            // on a chargé le fichier
            $data = $this->upload->data();
            $filename = $config['upload_path'] . $data['file_name'];
            $this->session->set_userdata('file_soldes', $filename);
            $this->import_soldes_from_file($filename);
        }
    }

    /**
     * Imports account balances from a specified file
     *
     * @param string $filename Path to the file containing account balance data
     * @throws Exception If there are parsing or processing errors during import
     */
    public function import_soldes_from_file($filename) {

        try {
            $parser = new SoldesParser();
            $soldes = $parser->parse($filename);
        } catch (Exception $e) {
            echo "Erreur: " . $e->getMessage() . "\n";
        }

        $soldes_html = $parser->arrayWithControls($soldes);
        $data['soldes'] = $soldes_html;

        load_last_view('openflyers/tableSoldes', $data);
    }

    /**
     * Génère une écriture d'initialisation de solde pilote
     */
    public function solde_init($compte_gvv, $solde, $date = "2025-01-01") {

        // Get club info from compte_gvv
        $compte = $this->comptes_model->get_by_id('id', $compte_gvv);
        if (!$compte) {
            throw new Exception("Compte GVV $compte_gvv non trouvé");
        }

        // // Find fonds associatif account for this section
        $fonds_associatif = $this->comptes_model->get_by_section_and_codec($compte['club'], '102');
        if (!$fonds_associatif) {
            throw new Exception("Compte de fonds associatif non trouvé pour la section " . $compte->id_section);
        }

        // $fonds_associatif['id']
        // Generate accounting entries
        $data = array(
            'annee_exercise' => date('Y', strtotime($date)),
            'date_op' => $date,
            'date_creation' => date("Y-m-d"),
            'club' => $compte['club'],
            'compte2' => $compte_gvv,
            'compte1' => $fonds_associatif['id'],
            'montant' => $solde,
            'description' => 'Initialisation du solde',
            'saisie_par' => $this->dx_auth->get_username()
        );
        if ($solde < 0) {
            // On inverse 
            $data['compte1'] = $compte_gvv;
            $data['compte2'] = $fonds_associatif['id'];
            $data['montant'] = -$solde;
        }

        // var_dump($data);

        $ecriture = $this->ecritures_model->create($data);
        if (!$ecriture) {
            throw new Exception("Erreur pendant le passage d'écriture de solde:");
        }
    }

    /**
     * Inserts a movement with the given parameters
     *
     * @param array $params Associative array of movement parameters
     * 
... date => 2025-02-19
... intitule => ASSELIN Philippe - Virement - hdv avion (virt par erreur CG)
... description => 60997
... debit => 0.00
... credit => 156.00
... compte1 => 1119
... compte2 => 679
     */
    public function insert_movement(array $params) {

        // Quel est la section courante?
        $section = $this->sections_model->section();

        // Il faut une section active pour importer les écritures
        if (!$section) return;

        $section_id = ($section) ? $section['id'] : 0;

        echo "<br>mouvement:<br>";
        foreach ($params as $mkey => $mvalue) {
            echo "... $mkey => $mvalue<br>";
        }

        $montant = 0;
        $data = array(
            'annee_exercise' => date('Y', $params['date']),
            'date_op' => $params['date'],
            'date_creation' => date("Y-m-d"),
            'club' => $section['id'],
            'compte1' => $params['compte1'],
            'compte2' => $params['compte2'],
            'montant' => $montant,
            'description' => $params['intitule'],
            'num_cheque' => $params['description'],
            'saisie_par' => $this->dx_auth->get_username()
        );

        // if ($solde < 0) {
        //     // On inverse 
        //     $data['compte1'] = $compte_gvv;
        //     $data['compte2'] = $fonds_associatif['id'];
        //     $data['montant'] = -$solde;
        // }

        // var_dump($data);

        // Si elle existe détruit l'écriture avec le même numéro de flux OpenFlyers

        // Insert l'écriture

        // $ecriture = $this->ecritures_model->create($data);
        // if (!$ecriture) {
        //     throw new Exception("Erreur pendant le passage d'écriture de solde:");
        // }        
    }

    /**
     * Scan les parametres post et génère les écritures d'initialisation de solde
     */
    public function create_soldes() {

        $file_soldes = $this->session->userdata('file_soldes');
        try {
            $parser = new SoldesParser();
            $soldes = $parser->parse($file_soldes);

            $import_date = $this->input->post("import_date");
            if (!$import_date) {
                $soldes_html = $parser->arrayWithControls($soldes);
                $data["error"] = "Date d'import non définie";
                $data['soldes'] = $soldes_html;
                load_last_view('openflyers/tableSoldes', $data);
                return;
            }
            $date = date_ht2db($import_date);
            $posts = $this->input->post();
            foreach ($posts as $key => $value) {
                // echo "$key => $value<br>";
                if (strpos($key, 'cb_') === 0) {
                    // Key starts with "cb_"
                    $line = str_replace("cb_", "", $key);
                    $compte_key = "compte_" . $line;
                    $compte_value = $posts[$compte_key];

                    $row = $soldes[$line];

                    // $id_of = $row[0];
                    // $nom_of = $row[1];
                    // $profil = $row[2];
                    // $type = $row[3];
                    $solde = $row[4];

                    // echo "id_of=$id_of, nom_of=$nom_of, profil=$profil, type=$type, solde=$solde" . "<br>";
                    $this->solde_init($compte_value, $solde, $date);
                }
            }

            $this->import_soldes_from_file($file_soldes);
        } catch (Exception $e) {
            echo "Erreur: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Scan les paramètres post et génère les écritures d'import d'écritures
     * 
     * Les écritures qui n'existent pas sont crée
     * Les écritures qui existent sont remplacées
     * 
     * Si toutes les écritures 411 pour une section sont fournies entre deux dates,
     * on peut les supprimer pour garantir la synchronisation.
     */
    function create_operations() {
        $posts = $this->input->post();
        foreach ($posts as $key => $value) {
            // echo "$key => $value<br>";
            if (strpos($key, 'cb_') === 0) {
                // Key starts with "cb_"
                $line = str_replace("cb_", "", $key);
                $import_key = "import_" . $line;
                $import_params = html_entity_decode($posts[$import_key]);
                $params = json_decode($import_params, true);

                $this->insert_movement($params);
            }
        }
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */