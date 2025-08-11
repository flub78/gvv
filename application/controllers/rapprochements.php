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
        $this->load->model('associations_releve_model');
        $this->load->library('SoldesParser');
        $this->lang->load('rapprochements');
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
            $this->import_releve_from_file($filename);
        }
    }

    /**
     * Import a CSV listing from a file
     */
    public function import_releve_from_file($filename, $status = "") {

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

            $basename = basename($filename);
            $header = [];
            $header[] = ["Banque: ",  $releve['bank'], '', ''];

            $bank_account = $this->associations_releve_model->get_gvv_account($releve['iban']);
            if ($bank_account) {
                $compte_bank_gvv = anchor_compte($bank_account);
                $header[] = ["IBAN: ",  $releve['iban'], 'Compte GVV:', $compte_bank_gvv,];
                $releve['gvv_bank'] = $bank_account;
            } else {
                // On affiche un sélecteur
                $compte_selector = $this->comptes_model->selector_with_null(['codec' => 512], TRUE);
                $attrs = 'class="form-control big_select" onchange="associateAccount(this, \''
                    . $releve['iban']  . '\')"';
                $compte_bank_gvv = dropdown_field(
                    "compte_bank",
                    $associated_gvv,
                    $compte_selector,
                    $attrs
                );
                $header[] = ["IBAN: ",  $releve['iban'], 'Compte GVV:', $compte_bank_gvv];
            }

            $header[] = ["Section: ",  $releve['section'], '', ''];
            $header[] = ["Date de solde: ",  $releve['date_solde'], "Solde: ", euro($releve['solde'])];
            $header[] = ["Date de début: ",  $releve['start_date'], "Date de fin: ",  $releve['end_date']];
            $header[] = ["Fichier: ",  $basename, 'Nombre opérations: ', count($releve['operations'])];
            $data['header'] = $header;

            // echo '<pre>' . print_r($header, true) . '</pre>';   
            // echo '<pre>' . print_r($releve, true) . '</pre>';

            $data['section'] = $this->sections_model->section();
            $data['operations'] = $this->operation_table($releve);
            load_last_view('rapprochements/tableRapprochements', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Format the operation table for all bank statements
     *
     * @param array $releve The bank statement data
     * @param bool $with_gvv_info Whether to include GVV information
     * @return array The generated operation table
     */
    function operation_table($releve, $with_gvv_info = true) {
        $res = [];
        foreach ($releve['operations'] as $op) {
            $res[] = $releve['titles'];
            $res[] = [
                $op['Date'],
                $op["Nature de l'opération"],
                $op['Débit'],
                $op['Crédit'],
                $op['Devise'],
                $op['Date de valeur'],
                $op['Libellé interbancaire']
            ];
            foreach ($op['comments'] as $comment) {
                $res[] = ['', $comment, '', '', '', '', ''];
            }

            if ($with_gvv_info) {

                if ($releve['gvv_bank'] == null) {
                    $sel = '';
                } else {
                    $sel = $this->ecriture_selector($releve['start_date'], $releve['end_date'], $releve['gvv_bank'], $op);
                };

                $count = $op['selector_count'] ?? 0;
                $button = '';
                if ($count == 1) {
                    $button = ' <button type="button" class="btn btn-primary">Rapprocher</button>';
                    $compte_gvv = ($op['unique_image'] ?? '');
                } elseif ($count == 0) {
                    $compte_gvv = '<span class="text-danger">Aucune écriture trouvée</span>';
                } else {
                    // $button = ' <button type="button" class="btn btn-primary">Rapprocher</button>';
                    $compte_gvv = $sel;
                }
                $res[] = ['Ecriture GVV:', $op['type'], $compte_gvv, $button, "Ligne:" . $op['line'], "nb: $count",  ''];
                $res[] = ['===========', '===========', '===========', '===========', '===========', '===========', '==========='];
            }
        }
        return $res;
    }

    /**
     * Selector for financial entries matching specific criteria
     *
     * @param string $start_date Starting date for the selection period (Y-m-d format)
     * @param string $end_date Ending date for the selection period (Y-m-d format)P
     * @param string $op Operation type filter
     * @return void
     */
    function ecriture_selector($start_date, $end_date, $bank, &$op) {

        $start_date = date_ht2db($start_date);
        $end_date = date_ht2db($end_date);

        if ($op['Débit']) {
            $compte1 = null;
            $compte2 = $bank;
            $montant = abs(str_replace([' ', ','], ['', '.'], $op['Débit']));
        } else {
            $compte1 = $bank;
            $compte2 = null;
            $montant = abs(str_replace([' ', ','], ['', '.'], $op['Crédit']));
        }

        // On utilise le modèle ecritures_model pour obtenir les écritures
        // qui correspondent à l'opération du relevé bancaire
        $slct = $this->ecritures_model->ecriture_selector($start_date, $end_date, $montant, $compte1, $compte2);

        $sel = $slct['selector'];
        if ($slct['unique_id']) {
            $op['unique_id'] = $slct['unique_id'];
            $op['unique_image'] = $slct['unique_image'];
        } else {
            unset($op['unique_id']);
            unset($op['unique_image']);
        }
        $op['selector_count'] = count($sel);

        // Attention, il peut y avoir plusieurs opérations identiques dans le relevé.
        // même date, même type, même nature de l'opération, même libellé interbancaire

        // Il faut donc associer le numéro d’occurrence de la ligne à la date donnée.
        // Même si on importe depuis des relevés de comptes différents, hebdomadaire, mensuel, annuel on importe toujours des journées entières.

        $string_releve = $op['type'] . ' ' . $op['Date'] . ' ' . $op['Nature de l\'opération'] . ' ' . $op['Libellé interbancaire'];

        $attrs = 'class="form-control big_select" onchange="associateEcriture(this, \''
            . $string_releve  . '\')"';
        $dropdown = dropdown_field(
            "op_" . $op['line'],
            "",
            $sel,
            $attrs
        );
        return $dropdown;
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