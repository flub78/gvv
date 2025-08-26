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
        $this->load->helper('url');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
        $this->load->model('sections_model');
        // $this->load->library('SoldesParser');
        $this->lang->load('rapprochements');
        $this->load->model('associations_ecriture_model');
        $this->load->model('associations_releve_model');

        $this->load->library('Reconciliator');
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
        $this->load->library('ObjectReleveParser');

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        $endDate = $this->session->userdata('endDate');
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

        try {
            $parser2 = new ObjectReleveParser();
            try {
                $parser_result = $parser2->parse($filename);
                $reconciliator = new Reconciliator($parser_result);
                // $reconciliator->dump();
            } catch (Exception $e) {
                $msg = "Erreur: " . $e->getMessage() . "\n";
                gvv_error($msg);

                $error = array(
                    'error' => $msg
                );
                load_last_view('rapprochements/select_releve', $error);
                return;
            }
            $type_hash = ["all" => "Tous les types"];
            $recognized_types = $parser2->recognized_types();
            $type_hash = array_merge($type_hash, $recognized_types);
            $data['type_dropdown'] = dropdown_field(
                'type_selector',
                $type_selector,
                $type_hash,
                'class="form-control big_select"'
            );

            $basename = basename($filename);
            $header = [];
            $header[] = ["Banque: ",  $parser_result['bank'], '', ''];

            if ($parser_result['gvv_bank']) {
                $compte_bank_gvv = anchor_compte($parser_result['gvv_bank']);
                $header[] = ["IBAN: ",  $parser_result['iban'], 'Compte GVV:', $compte_bank_gvv];
            } else {
                // On affiche un sélecteur
                $compte_selector = $this->comptes_model->selector_with_null(['codec' => 512], TRUE);
                $attrs = 'class="form-control big_select" onchange="associateAccount(this, \''
                    . $parser_result['iban']  . '\')"';
                $compte_bank_gvv = dropdown_field(
                    "compte_bank",
                    $associated_gvv,
                    $compte_selector,
                    $attrs
                );
                $header[] = ["IBAN: ",  $parser_result['iban'], 'Compte GVV:', $compte_bank_gvv];
            }

            // d’abord les opérations
            $data['section'] = $this->sections_model->section();
            foreach ($parser_result['ops'] as $op) {
                $op->associate();
            }
            $ot = $this->operation_table2($parser_result, $recognized_types);
            $data['html_tables'] = $this->tables2Html($ot['tables']);

            $header[] = ["Section: ",  $parser_result['section'], 'Fichier', $basename];
            $header[] = ["Date de solde: ",  $parser_result['date_solde'], "Solde: ", euro($parser_result['solde'])];
            $header[] = ["Date de début: ",  $parser_result['start_date'], "Date de fin: ",  $parser_result['end_date']];
            $rap = $ot['count_rapproches'] . ", Choix: " . $ot['count_choices'] . ", Uniques: " . $ot['count_uniques'];
            $header[] = [
                'Nombre opérations: ',
                $ot['count_selected'] . ' / ' . count($parser_result['ops']),
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

            // data for the GVV tab
            $gvv_lines = $this->ecritures_model->select_ecritures_openflyers($data['startDate'], $data['endDate'], $parser_result['gvv_bank']);

            $cnt = 0;
            $filtered_lines = [];
            foreach ($gvv_lines as &$line) {
                $id = $line['id'];
                $rapproched = $this->associations_ecriture_model->get_rapproches($id);
                $line['rapproched'] = $rapproched;

                if ($filter_active) {
                    if ($filter_type == 'filter_matched' && $rapproched) {
                        $filtered_lines[] = $line;
                    }

                    if ($filter_type == 'filter_unmatched' && !$rapproched) {
                        $filtered_lines[] = $line;
                    }
                } else {
                    $filtered_lines[] = $line;
                }
                $cnt++;
            }

            $data['gvv_lines'] = $this->to_ecritures_table($filtered_lines);

            load_last_view('rapprochements/tableRapprochements', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
        }
    }

    /**
     * compute a unique string to identify an operation
     * from the fields
     * the result is safe to be passed as a post parameter
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
     * @param array $parser_result The bank statement data
     * @param bool $with_gvv_info Whether to include GVV information
     * @return array The generated operation table
     */
    function operation_table2($parser_result, $recognized_types = null) {
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
        $count_rapproches = 0;
        $count_choices = 0;
        $count_uniques = 0;
        $count_selected = 0;

        foreach ($parser_result['ops'] as $op) {

            // $complete_table = array_merge($complete_table, $res);
            if ($res) {
                $tables[] = $res;
            }
            $res = [];

            // D'abord on va chercher les informations sur l'operation
            $rapproches = $op->rapproches();
            if ($rapproches) {
                $count_rapproches++;
            }

            if ($parser_result['gvv_bank'] != null) {
                $this->fetch_gvv_matches($parser_result['start_date'], $parser_result['end_date'], $parser_result['gvv_bank'], $op);
            };

            if ($filter_active) {
                $op_date = $op->date;
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
                    if ($type_selector != $op->type) {
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
                    if (in_array($filter_type, ["filter_unmatched_0", "filter_unmatched_1", "filter_unmatched_multi"])) {
                        // On ne traite pas les opérations non rapprochées
                        if ($rapproches) {
                            continue;
                        }
                    }
                    $count = $op->selector_count ?? 0;
                    if (($filter_type == "filter_unmatched_0") && ($count != 0)) {
                        continue;
                    }
                    if (($filter_type == "filter_unmatched_1") && ($count != 1)) {
                        continue;
                    }
                    if (($filter_type == "filter_unmatched_multi") && ($count <= 1)) {
                        continue;
                    }
                }
            }

            // Puis on génère la table
            // ligne de titre
            $res[] = $parser_result['titles'];
            // ligne de valeurs de la ligne de relevé
            $res[] = [
                $op->local_date(),
                $op->nature,
                ($op->debit) ? euro($op->debit) : '',
                ($op->credit) ? euro($op->credit) : '',
                $op->currency,
                $op->local_value_date(),
                $op->interbank_label
            ];
            // commentaires multiligne
            foreach ($op->comments as $comment) {
                $res[] = ['', $comment, '', '', '', '', ''];
            }

            // informations sur le rapprochement
            $count_selected++;   // opérations non filtrées
            $count = $op->selector_count ?? 0;
            $count_choices += $count;

            $status = '';
            $hidden = '<input type="hidden" name="string_releve_' . $op->line . '" value="' . $op->str_releve() . '">';

            $checkbox = '';
            if ($count == 1) {
                // proposition unique
                $hidden .= '<input type="hidden" name="op_' . $op->line . '" value="' . $op->unique_id . '">';

                $ecriture_gvv = '<span class="text-success">' . ($op->unique_image ?? '') . '</span>';

                $checkbox = '<input type="checkbox" class="unique" name="cb_' . $op->line . '" value="1" >';

                $status = $checkbox . $hidden;
                $count_uniques++;
            } elseif ($count == 0) {
                // pas de proposition
                $ecriture_gvv = '<span class="text-danger">Aucune écriture trouvée</span>';
            } else {
                // plusieurs propositions
                $checkbox = '<input type="checkbox" name="cb_' . $op->line . '" value="1" >';
                $ecriture_gvv = $op->selector_dropdown;

                $status = $checkbox . $hidden;
            }

            if ($rapproches) {
                $status = '<input type="checkbox" name="cbdel_' . $op->line . '" value="1" >';
                $status .= '<div class="badge bg-success text-white rounded-pill ms-2" >Rapproché</div>';
                $status .= $hidden;
                // Ajout d'un bouton de suppression

                // image de l'écriture
                $id_ecriture_gvv = $rapproches[0]['id_ecriture_gvv'] ?? '';
                $ecriture_gvv = '<span class="text-primary">' . $this->ecritures_model->image($id_ecriture_gvv) . '</span>';
                $ecriture_gvv = anchor_ecriture($id_ecriture_gvv);
                // gvv_dump($rapproches);
            } else {
                $status .= '<div type="button" class="badge bg-danger text-white rounded-pill ms-1">Non rapproché</div>';
            }

            $count_str = ($rapproches) ? "" : "Choix: $count.";
            if ($recognized_types) {
                $str_type = $recognized_types[$op->type] ?? $op->type;
            } else {
                $str_type = $op->type;
            }
            $res[] = [$status, $ecriture_gvv, '', '', $count_str, "Ligne:" . $op->line, $str_type];

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

        $attrs = 'class="form-control big_select ecriture_select"';
        $dropdown = dropdown_field(
            "op_" . $op['line'],
            "",
            $sel,
            $attrs
        );
        return $dropdown;
    }

    /**
     * Selector for financial entries matching specific criteria
     *
     * @param string $start_date Starting date for the selection period (Y-m-d format)
     * @param string $end_date Ending date for the selection period (Y-m-d format)P
     * @param string $op Operation type filter
     * @return void
     *
     * Le rapprochement multiple sera géré par une méthode spécifique. Cependant,
     * dans certains cas spécieux d'opérations (remboursements d'emprunts on vas séparer en 2
     */
    function fetch_gvv_matches($start_date, $end_date, $bank, &$op) {

        $op->fetch_gvv_matches($start_date, $end_date, $bank);

        $reference_date = $op->date();

        if ($op->debit) {
            $compte1 = null;
            $compte2 = $bank;
        } else {
            $compte1 = $bank;
            $compte2 = null;
        }

        // On utilise le modèle ecritures_model pour obtenir les écritures
        // qui correspondent à l'opération du relevé bancaire
        $delta = $this->session->userdata('rapprochement_delta');
        if (! $delta) {
            $delta = 5; // Default delta value
        }
        $sel = $this->ecritures_model->ecriture_selector($start_date, $end_date, $op->montant(), $compte1, $compte2, $reference_date, $delta);

        // $sel = $slct['selector'];

        $smart_mode = $this->session->userdata('rapprochement_smart_mode') ?? false;
        if ($smart_mode) {
            // Smart mode: filter out entries that are too unlikely to match
            $sel = $this->smart_ajust2($sel, $op);
        }
        if (count($sel) == 1) {
            $unique_id = key($sel);
            $op->unique_id = $unique_id;
            $op->unique_image = $this->ecritures_model->image($unique_id);
        } else {
            unset($op->unique_id);
            unset($op->unique_image);
        }

        $op->selector_count = count($sel);
        $op->selector = $sel;

        // Attention, il peut y avoir plusieurs opérations identiques dans le relevé.
        // même date, même type, même nature de l'opération, même libellé interbancaire

        // Il faut donc associer le numéro d’occurrence de la ligne à la date donnée.
        // Même si on importe depuis des relevés de comptes différents, hebdomadaire, mensuel, annuel on importe toujours des journées entières.

        $attrs = 'class="form-control big_select big_select_large" ';
        $dropdown = dropdown_field(
            "op_" . $op->line,
            "",
            $sel,
            $attrs
        );
        $op->selector_dropdown = $dropdown;
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

        redirect('rapprochements/import_releve_from_file');
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
        redirect('rapprochements/import_releve_from_file');
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
     *       [endDate] => 2025-01-31
     *       [filter_type] => unmatched
     *       [type_selector] => paiement_cb
     *       [button] => Filtrer
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

        redirect('rapprochements/import_releve_from_file');
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
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }


    /**
     * Ajuste le sélecteur pour ne garder que les écritures qui ont un coefficient de corrélation supérieur au seuil
     *
     * @param array $sel Sélecteur d'écritures
     * @param array $op Opération du relevé bancaire
     * @return array Le sélecteur ajusté
     */
    function smart_ajust2($sel, $op) {

        $threshold = 0.5;
        $filtered_sel = [];
        $verbose = false;

        if ($verbose) {
            echo '<pre>';
            print_r($op);
        }

        // Première passe pour voir si on a une corrélation très forte
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $op->correlation($key, $ecriture);
            if ($correlation >= 0.9) {
                $threshold = 0.9;
                break;
            }
        }

        // Deuxième passe pour filtrer les écritures
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $op->correlation($key, $ecriture);

            if ($correlation >= $threshold) {
                // Si le coefficient de corrélation est supérieur au seuil, on garde l'écriture
                $filtered_sel[$key] = $ecriture;
                $ignored = "";
            } else {
                // Sinon, on l'ignore
                $ignored = "Ignored";
            }
            $msg = "Correlation: $key => $ecriture : $correlation $ignored<br>";
            gvv_debug($msg);
        }

        if ($verbose) {
            echo '</pre>';
            echo '<hr style="border: 1px solid #ccc; margin: 20px 0;">';
        }
        return $filtered_sel;
    }

    /**
     * Supprime tous les rapprochements ou les écritures
     */
    function delete_all() {

        $posts = $this->input->post();

        if (!isset($posts['button']) || $posts['button'] == 'Supprimez les rapprochements sélectionnés') {
            $rappro = true;
        } else {
            $rappro = false;
        }

        $status = "";
        foreach ($posts as $key => $value) {
            // echo "$key => $value<br>";
            if (strpos($key, 'cbdel_') === 0) {
                // Key starts with "cbdel_" ce sont les checkboxes actives
                $id = str_replace("cbdel_", "", $key);

                // supprimer les rapprochements
                $rapproched = $this->associations_ecriture_model->get_rapproches($id);
                foreach ($rapproched   as $r) {
                    $status .= "rapprochement " . $r['id'] . " supprimé<br>";
                }
                $this->associations_ecriture_model->delete_rapprochements($id);
                if (!$rappro) {
                    // les rapprochements et l'écriture
                    $image = $this->ecritures_model->image($id);
                    $this->ecritures_model->delete_ecriture($id);
                    $status .= "$image supprimée<br>";
                }
            }
        }

        $this->session->set_userdata('status', $status);
        redirect('rapprochements/import_releve_from_file');
    }

    /**
     * Converts GVV lines to a table format for display
     *
     * @param array $gvv_lines Array of GVV lines to convert
     * @return array Formatted array suitable for table display
     */
    function to_ecritures_table($gvv_lines) {
        // gvv_dump($gvv_lines);
        $res = [];
        foreach ($gvv_lines as $line) {
            $elt = [];

            if ($line['rapproched']) {
                $elt[] = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">'
                    . '<span class="bg-success badge text-white rounded-pill ms-1">' . $line['id'] . '</span>';
            } else {
                $elt[] = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">'
                    . '<span class="bg-danger badge text-white rounded-pill ms-1">' . $line['id'] . '</span>';
            }

            $elt[] = date_db2ht($line['date_op']);
            $elt[] = euro($line['montant']);
            $elt[] = $line['description'];
            $elt[] = $line['num_cheque'];

            $elt[] = anchor_compte($line['compte1']);
            $elt[] = anchor_compte($line['compte2']);
            $res[] = $elt;
        }
        return $res;
    }
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */