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
        $this->lang->load('rapprochements');
        $this->load->model('associations_ecriture_model');
        $this->load->model('associations_releve_model');
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

            $this->session->set_userdata('filter_active', false);

            $filename = $config['upload_path'] . $data['file_name'];
            $this->session->set_userdata('file_releve', $filename);
            $this->import_releve_from_file($filename);
        }
    }

    /**
     * Import a CSV listing from a file
     */
    public function import_releve_from_file($filename = "", $status = "") {

        if ($filename == "") {
            $filename = $this->session->userdata('file_releve');
        }
        $this->load->library('ReleveParser');

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        $endDate = $this->session->userdata('endDate');
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

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
            $type_hash = ["all" => "Tous les types"];
            $recognized_types = $parser->recognized_types();
            $type_hash = array_merge($type_hash, $recognized_types);
            $data['type_dropdown'] = dropdown_field(
                'type_selector',
                $type_selector,
                $type_hash,
                'class="form-control big_select"'
            );

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

            // D'abbort les opérations
            $data['section'] = $this->sections_model->section();
            $ot = $this->operation_table($releve, $recognized_types);
            $data['html_tables'] = $this->tables2Html($ot['tables']);

            // echo '<pre>' . print_r($header, true) . '</pre>';   
            // echo '<pre>' . print_r($releve, true) . '</pre>';

            $header[] = ["Section: ",  $releve['section'], 'Fichier', $basename];
            $header[] = ["Date de solde: ",  $releve['date_solde'], "Solde: ", euro($releve['solde'])];
            $header[] = ["Date de début: ",  $releve['start_date'], "Date de fin: ",  $releve['end_date']];
            $rap = $ot['count_rapproches'] . ", Choix: " . $ot['count_choices'] . ", Uniques: " . $ot['count_uniques'];
            $header[] = [
                'Nombre opérations: ',
                $ot['count_selected'] . ' / ' . count($releve['operations']),
                'Rapprochées:',
                $rap
            ];
            $data['header'] = $header;

            $data['filter_active'] = $filter_active;
            $data['startDate'] = $startDate;
            $data['endDate'] = $endDate;
            $data['filter_type'] = $filter_type;
            $data['type_selector'] = $type_selector;
            $data['maxDays'] = $this->session->userdata('rapprochement_delta') ?? 5;
            if (!$data['maxDays']) {
                $data['maxDays'] = 5; // Default delta value
            }
            $data['smartMode'] = $this->session->userdata('rapprochement_smart_mode') ?? false;

            $data['count_selected'] = $ot['count_selected'];

            load_last_view('rapprochements/tableRapprochements', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
        }
    }

    /**
     * compute a unique string to identify an operation
     * from the fields
     * the result is safe to be passed as a post parameter
     * $res[] = [
                $op['Date'],
                $op["Nature de l'opération"],
                $op['Débit'],
                $op['Crédit'],
                $op['Devise'],
                $op['Date de valeur'],
                $op['Libellé interbancaire']
            ];
     */
    function str_releve($op) {
        $parts = [
            $op['Date'],
            $op["Nature de l'opération"],
            $op['Débit'],
            $op['Crédit'],
            $op['Devise'],
            $op['Date de valeur'],
            $op['Libellé interbancaire']
        ];

        // Remove any problematic characters and join with a delimiter
        $parts = array_map(function ($str) {
            return preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
        }, $parts);

        return implode('__', $parts);
    }

    /**
     * Format the operation table for all bank statements
     *
     * @param array $releve The bank statement data
     * @param bool $with_gvv_info Whether to include GVV information
     * @return array The generated operation table
     */
    function operation_table($releve, $recognized_types = null, $with_gvv_info = true) {
        /**
         * Pour chaque ligne du relevé on affiche les informations du relevé.
         * On y ajoute les informations de rapprochement si elles existent ou des
         * éléments de formulaire pour les ajouter.
         * 
         * Chaque ligne non ajouté contient
         * une checkbox identifié par "cb_" + numéro de ligne
         * un champ caché avec l'identité unique de l'écriture si elle est unique
         * un selecteur si il y a plusieurs écritures possibles
         * un champ caché avec la chaine de caractères qui identifie l'opération dans le relevé
         */

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        $endDate = $this->session->userdata('endDate');
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

        $res = [];
        $tables = [];
        // $complete_table = [];
        $count_rapproches = 0;
        $count_choices = 0;
        $count_uniques = 0;
        $count_selected = 0;

        foreach ($releve['operations'] as $op) {

            // $complete_table = array_merge($complete_table, $res);
            if ($res) {
                $tables[] = $res;
            }
            $res = [];

            // D'abord on va chercher les informations sur l'operation
            $string_releve = $this->str_releve($op);
            $rapproches = $this->associations_ecriture_model->get_by_string_releve($string_releve);
            if ($rapproches) {
                $count_rapproches++;
            }

            if ($filter_active) {
                $op_date = date_ht2db($op['Date de valeur']);
                if ($startDate) {
                    // les dates sont au format "yyyy-mm-dd" 
                    // si $op_date < $startDate
                    if ($op_date < $startDate) {
                        continue; // Skip this operation
                    }
                }
                if ($endDate) {
                    // les dates sont au format "yyyy-mm-dd" 
                    // si $op_date > $endDate
                    if ($op_date > $endDate) {
                        continue; // Skip this operation
                    }
                }
                if ($type_selector && ($type_selector != "all")) {
                    if ($type_selector != $op["type"]) {
                        continue;
                    }
                }
                if ($filter_type && ($filter_type != "display_all")) {
                    if (($filter_type == "filter_unmatched") && $rapproches) {
                        continue;
                    }
                    if (($filter_type == "filter_matched") && ! $rapproches) {
                        continue;
                    }
                }
            }
            $count_selected++;

            // Puis on génère la table
            // ligne de titre
            $res[] = $releve['titles'];
            // ligne de valeurs de la ligne de relevé
            $res[] = [
                $op['Date'],
                $op["Nature de l'opération"],
                $op['Débit'],
                $op['Crédit'],
                $op['Devise'],
                $op['Date de valeur'],
                $op['Libellé interbancaire']
            ];
            // commentaires multiligne
            foreach ($op['comments'] as $comment) {
                $res[] = ['', $comment, '', '', '', '', ''];
            }

            // informations sur le rapprochement
            if ($with_gvv_info) {

                if ($releve['gvv_bank'] == null) {
                    $sel = '';
                } else {
                    $sel = $this->ecriture_selector($releve['start_date'], $releve['end_date'], $releve['gvv_bank'], $op);
                };

                $count = $op['selector_count'] ?? 0;
                $count_choices += $count;

                $status = '';

                $hidden = '<input type="hidden" name="string_releve_' . $op['line'] . '" value="' . $this->str_releve($op) . '">';

                $button = '';
                $checkbox = '';
                if ($count == 1) {
                    // $button = ' <button type="button" class="btn btn-primary">Rapprocher</button>';
                    $hidden .= '<input type="hidden" name="op_' . $op['line'] . '" value="' . $op['unique_id'] . '">';

                    $ecriture_gvv = '<span class="text-success">' . ($op['unique_image'] ?? '') . '</span>';

                    $checkbox = '<input type="checkbox" class="unique" name="cb_' . $op['line'] . '" value="1" onchange="toggleRowSelection(this)">';

                    $status = $checkbox . $hidden;
                    $count_uniques++;
                } elseif ($count == 0) {
                    $ecriture_gvv = '<span class="text-danger">Aucune écriture trouvée</span>';
                } else {

                    $checkbox = '<input type="checkbox" name="cb_' . $op['line'] . '" value="1" onchange="toggleRowSelection(this)">';
                    $ecriture_gvv = $sel;

                    $status = $checkbox . $hidden;
                }

                if ($rapproches) {
                    $status = '<input type="checkbox" name="cbdel_' . $op['line'] . '" value="1" onchange="toggleRowSelection(this)">';
                    $status .= '<button class="btn btn-success btn-sm ms-2" disabled>Rapproché</button>';
                    $status .= $hidden;
                    // Ajout d'un bouton de suppression

                    // image de l'écriture
                    $id_ecriture_gvv = $rapproches[0]['id_ecriture_gvv'] ?? '';
                    $ecriture_gvv = '<span class="text-primary">' . $this->ecritures_model->image($id_ecriture_gvv) . '</span>';
                    $ecriture_gvv = anchor_ecriture($id_ecriture_gvv);
                    // gvv_dump($rapproches);
                } else {
                    // $status .= $checkbox;
                    $status .= '<button type="button" class="btn btn-danger btn-sm ms-2" onclick="rapproche(' . $op['line'] . ')">Non rapproché</button>';
                    //$status .= $hidden;
                }

                $count_str = ($rapproches) ? "" : "Choix: $count.";
                if ($recognized_types) {
                    $str_type = $recognized_types[$op['type']] ?? $op['type'];
                } else {
                    $str_type = $op['type'];
                }
                $res[] = [$status, $ecriture_gvv, $str_type, '', $count_str, "Ligne:" . $op['line'], 'Ecriture GVV'];
            }
            // $complete_table = array_merge($complete_table, $res);
            $tables[] = $res;
            $res = [];
        }

        return [
            'tables' => $tables,
            'count_selected' => $count_selected,
            'count_rapproches' => $count_rapproches,
            'count_choices' => $count_choices,
            'count_uniques' => $count_uniques
        ];
    }

    /**
     * Convertit les tables en HTML
     */
    function tables2Html($tables) {
        $html = '';
        foreach ($tables as $table) {
            // table table-striped table-bordered
            $html .= '<table class="table rapprochement table-striped table-bordered border border-dark rounded mb-3 w-100 operations">';
            $line_cnt = 0;
            $row_count = 0;

            foreach ($table as $row) {
                if ($line_cnt == 0) {
                    // echo "thead";
                    $row_count = count($row);
                    $html .= '<thead>';
                    $html .= '<tr class="compte row_title">';
                    foreach ($row as $cell) {
                        $html .= '<th>' . $cell . '</th>';
                    }
                    $html .= '</tr>';
                    $html .= '</thead>';
                    $html .= '<tbody>';
                } else {
                    $html .= '<tr>';
                    $cnt = 0;
                    foreach ($row as $cell) {
                        $html .= '<td>' . $cell . '</td>';
                        $cnt++;
                    }
                    while ($cnt < $row_count) {
                        $html .= '<td></td>';
                        $cnt++;
                    }
                    $html .= '</tr>';
                }
                $line_cnt++;
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }
        return $html;
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
        $reference_date = date_ht2db($op['Date de valeur']) ?? null;

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
        $delta = $this->session->userdata('rapprochement_delta');
        if (! $delta) {
            $delta = 5; // Default delta value
        }
        $slct = $this->ecritures_model->ecriture_selector($start_date, $end_date, $montant, $compte1, $compte2, $reference_date, $delta);

        $sel = $slct['selector'];

        $smart_mode = $this->session->userdata('rapprochement_smart_mode') ?? false;
        if ($smart_mode) {
            // Smart mode: filter out entries that are too unlikely to match
            $sel = $this->smart_ajust($sel, $op);
            if (count($sel) == 1) {
                // $op['unique_id'] = array_keys($sel)[0];
                $op['unique_id'] = key($sel[0]);
                $op['unique_image'] = $this->ecritures_model->image($op['unique_id']);
            }
        } else {
            if ($slct['unique_id']) {
                $op['unique_id'] = $slct['unique_id'];
                $op['unique_image'] = $slct['unique_image'];
            } else {
                unset($op['unique_id']);
                unset($op['unique_image']);
            }
        }



        $op['selector_count'] = count($sel);

        // Attention, il peut y avoir plusieurs opérations identiques dans le relevé.
        // même date, même type, même nature de l'opération, même libellé interbancaire

        // Il faut donc associer le numéro d’occurrence de la ligne à la date donnée.
        // Même si on importe depuis des relevés de comptes différents, hebdomadaire, mensuel, annuel on importe toujours des journées entières.

        $attrs = 'class="form-control big_select" ';
        $dropdown = dropdown_field(
            "op_" . $op['line'],
            "",
            $sel,
            $attrs
        );
        return $dropdown;
    }

    public function rapprochez() {
        // Rapproche les écritures sélectionnées

        $post = $this->input->post();
        $counts = [];
        $operations = [];

        if ($post['button'] == 'Supprimer les rapprochements') {
            // On supprime les rapprochements
            $this->delete_rapprochement();
            return;
        }

        // Process valid selections
        foreach ($post as $key => $value) {
            if (strpos($key, 'cb_') === 0) {
                $line = str_replace('cb_', '', $key);

                if (isset($post['string_releve_' . $line])) {
                    // echo "string_releve_$line => " . $post['string_releve_' . $line] . "<br>";
                    $operations[$line] = ['string_releve' => $post['string_releve_' . $line]];
                } else {
                    gvv_dump('string_releve_' . $line . "not defined");
                }

                if (isset($post['op_' . $line]) && $post['op_' . $line] !== '') {
                    // echo "op_$line => " . $post['op_' . $line] . "<br>";
                    $op = $post['op_' . $line];
                    // Count occurrences of this operation ID
                    if (!isset($counts[$op])) {
                        $counts[$op] = 0;
                    }
                    $counts[$op]++;
                    $operations[$line]['ecriture'] = $post['op_' . $line];
                } else {
                    gvv_dump('op_' . $line . "not defined");
                }
            }
        }

        $errors = [];

        foreach ($counts as $key => $value) {
            if ($value > 1) {
                $image = $this->ecritures_model->image($key);
                $errors[] = "L'écriture $image a été sélectionnée $value fois";
            }
        }
        if ($errors) {
            $data['errors'] = $errors;
            load_last_view('rapprochements/tableRapprochements', $data);
        }

        // process valid operations
        foreach ($operations as $key => $ope) {
            $this->associations_ecriture_model->create([
                'string_releve' => $ope['string_releve'],
                'id_ecriture_gvv' => $ope['ecriture']
            ]);
        }
        $filename = $this->session->userdata('file_releve');
        $this->import_releve_from_file($filename);
    }

    /**
     * Supprime les rapprochements sélectionnés
     */
    function delete_rapprochement() {
        // Supprime les rapprochements sélectionnés
        $post = $this->input->post();
        $operations = [];

        // Process valid selections
        foreach ($post as $key => $value) {
            if (strpos($key, 'cbdel_') === 0) {
                $line = str_replace('cbdel_', '', $key);
                if (isset($post['string_releve_' . $line])) {

                    $operation = $post['string_releve_' . $line];
                    $this->associations_ecriture_model->delete_by_string_releve($operation);
                } else {
                    gvv_dump('string_releve_' . $line . "not defined");
                }
            }
        }

        $filename = $this->session->userdata('file_releve');
        $this->import_releve_from_file($filename);
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

    /**
     * Filtrage des opérations
     *     [startDate] => 2025-02-01
    [endDate] => 2025-01-31
    [filter_type] => unmatched
    [type_selector] => paiement_cb
    [button] => Filtrer
     */
    public function filter() {
        // Redirection vers la page de sélection du relevé
        $post = $this->input->post();
        // gvv_dump($post);
        $button = $post['button'] ?? '';
        if ($button == 'Filtrer') {
            // On filtre les opérations
            $start_date = $post['startDate'] ?? '';
            $end_date = $post['endDate'] ?? '';
            $filter_type = $post['filter_type'] ?? '';
            $type_selector = $post['type_selector'] ?? '';

            $this->session->set_userdata('startDate', $start_date);
            $this->session->set_userdata('endDate', $end_date);
            $this->session->set_userdata('filter_type', $filter_type);
            $this->session->set_userdata('type_selector', $type_selector);
            $this->session->set_userdata('filter_active', true);
        } else {
            $this->session->unset_userdata('startDate');
            $this->session->unset_userdata('endDate');
            $this->session->unset_userdata('filter_type');
            $this->session->unset_userdata('type_selector');
            $this->session->set_userdata('filter_active', false);
        }

        $filename = $this->session->userdata('file_releve');
        $this->import_releve_from_file($filename);
    }

    /**
     * Change le nombre de jours maximum pour le rapprochement
     */
    public function max_days_change() {
        // Change le nombre de jours maximum pour le rapprochement
        $delta = $this->input->get('maxDays');
        $this->session->set_userdata('rapprochement_delta', $delta);

        $json = json_encode(['success' => true]);
        gvv_debug("max_days_change($delta)" . $json);
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    /**
     * Change le mode de rapprochement intelligent
     */
    public function smart_mode_change() {
        // Change le mode de rapprochement intelligent
        $smartMode = $this->input->get('smartMode') === 'true';
        $this->session->set_userdata('rapprochement_smart_mode', $smartMode);

        $json = json_encode(['success' => true]);
        gvv_debug("smart_mode_change(" . ($smartMode ? 'true' : 'false') . ")" . $json);
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    /**
     * Calcule le coefficient de corrélation entre les écritures et l'opération
     *
     * @param array $sel Sélecteur d'écritures
     * @param array $op Opération du relevé bancaire
     * @return array Coefficient de corrélation pour chaque écriture
     */
    function corelation($key, $ecriture, $op) {
        // Calcule le coefficient de corrélation entre les écritures et l'opération 
        $correlation = 0.5;

        // Call appropriate correlation function based on type
        switch ($op['type']) {
            case 'cheque_debite':
                $correlation = $this->correlateCheque($key, $ecriture, $op);
                break;

            case 'frais_bancaire':
                $correlation = $this->correlateFraisBancaire($key, $ecriture, $op);
                break;

            case 'paiement_cb':
                $correlation = $this->correlatePaiementCB($key, $ecriture, $op);
                break;

            case 'prelevement':
            case 'prelevement_pret':
                $correlation = $this->correlatePrelevement($key, $ecriture, $op);
                break;

            case 'virement_emis':
                $correlation = $this->correlateVirementEmis($key, $ecriture, $op);
                break;

            case 'virement_recu':
                $correlation = $this->correlateVirementRecu($key, $ecriture, $op);
                break;

            case 'encaissement_cb':
                $correlation = 0.7;
                break;

            case 'remise_cheque':
                $correlation = 0.8;
                break;

            case 'remise_especes':
                $correlation = 0.9;
                break;

            case 'regularisation_frais':
                $correlation = 0.5;
                break;

            case 'inconnu':
                $correlation = 0.3;
                break;

            default:
                $correlation = 0.5;
                break;
        }

        return $correlation;
    }

    // Helper functions for each case
    function correlateCheque($key, $ecriture, $op) {
        if (preg_match('/cheque.*?(\d+)/i', $op['Libellé interbancaire'], $matches)) {
            return 0.9; // Higher if check number found
        }
        return 0.7;
    }

    function correlateFraisBancaire($key, $ecriture, $op) {
        if (
            stripos($op['Nature de l\'opération'], 'frais') !== false ||
            stripos($op['Libellé interbancaire'], 'frais') !== false
        ) {
            return 0.8;
        }
        return 0.6;
    }

    function correlatePaiementCB($key, $ecriture, $op) {
        if (stripos($op['Nature de l\'opération'], 'carte') !== false) {
            return 0.8;
        }
        return 0.5;
    }

    function correlatePrelevement($key, $ecriture, $op) {
        if (stripos($op['Nature de l\'opération'], 'prelevement') !== false) {
            return 0.8;
        }
        return 0.6;
    }

    function correlateVirementRecu($key, $ecriture, $op) {
        $date = date_ht2db($op['Date de valeur']);
        $nature = $op['Nature de l\'opération'] ?? '';
        $nature = strtolower($nature);
        $nature = str_replace(['vir inst re', 'vir recu'], '', $nature);
        $nature = trim($nature);

        // On cherche une écriture qui correspond à la nature de l'opération
        if (stripos(strtolower($ecriture), $nature) !== false) {
            return 0.95; // Corrélation élevée si la nature et la date correspondent
        }


        return 0.01;
    }

    function correlateVirementEmis($key, $ecriture, $op) {
        if (stripos($op['Nature de l\'opération'], 'virement') !== false) {
            return 0.8;
        }
        return 0.6;
    }

    /**
     * Ajuste le sélecteur pour ne garder que les écritures qui ont un coefficient de corrélation supérieur au seuil
     *
     * @param array $sel Sélecteur d'écritures
     * @param array $op Opération du relevé bancaire
     * @return array Le sélecteur ajusté
     */
    function smart_ajust($sel, $op) {

        $threshold = 0.5;
        $filtered_sel = [];

        echo '<pre>';
        print_r($op);
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $this->corelation($key, $ecriture, $op);
            echo "$key => $ecriture : $correlation<br>";

            if ($correlation >= $threshold) {
                // Si le coefficient de corrélation est supérieur au seuil, on garde l'écriture
                $filtered_sel[] = [$key => $ecriture];
            } else {
                // Sinon, on l'ignore
                // echo "Ignored: $ecriture<br>";
            }
        }

        echo '</pre>';
        echo '<hr style="border: 1px solid #ccc; margin: 20px 0;">';
        return $filtered_sel;
    }
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */