<?php

/**
 * Classe pour représenter une opération de relevé bancaire
 * 
 * Cette classe encapsule les données d'une opération bancaire
 * et fournit des méthodes pour accéder et manipuler ces données.
 */
class StatementOperation {
    private $CI;
    private $parser_info;
    private $gvv_bank_account; // Compte bancaire GVV
    private $reconciliated = []; // list of ReconciliationLine
    private $proposals = []; // list of ProposalLine objects
    private $multiple_proposals = []; // list of MultiProposalCombination objects 

    // required to interpret the type field, so injected in constructor
    private $recognized_types = [];

    /**
     * $reconciliated : référence les écritures GVV qui ont été rapprochées avec cette opération.
     * il peut y en avoir plusieurs. Invariant: La somme des écritures rapprochées doit correspondre au montant dans le relevé.
     * 
     * $proposals : Propose des écritures GVV qui pourraient être rapprochées avec cette opération. Ces écritures on un montant qui correspond au montant dans le relevé.
     * 
     * $multiple_proposals : Propose des ensembles d'écritures GVV qui pourraient être rapprochées avec cette opération. La somme des montants des écritures dans chaque ensemble doit correspondre au montant dans le relevé. Il peut y avoir plusieurs ensembles proposés. L'ensemble doit être rapproché globalement.
     */

    /**
     * Constructeur de la classe
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        $this->CI->load->library('rapprochements/ReconciliationLine');
        $this->CI->load->library('rapprochements/ProposalLine');
        $this->CI->load->library('rapprochements/MultiProposalCombination');
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('associations_ecriture_model');

        $this->parser_info = $data['parser_info'];
        $this->gvv_bank_account = isset($data['gvv_bank_account']) ? $data['gvv_bank_account'] : null;
        $this->recognized_types = isset($data['recognized_types']) ? $data['recognized_types'] : [];
        $this->reconciliate();
        // $this->dump("constructor", false);
    }

    /**
     * Génère une représentation HTML de l'opération
     * 
     * @return string Représentation HTML de l'opération
     */
    public function to_HTML() {
        $html = "";

        $html .= '<table class="table rapprochement table-striped table-bordered border border-dark rounded mb-3 w-100 operations">';
        $html .= '<thead>';

        $html .= '<tr class="compte row_title">';
        $html .= '<th>Date</th>';
        $type = $this->type_string();
        $ligne = $this->line();
        $html .= "<th>Nature de l'opération: $type, ligne: $ligne</th>";
        $html .= "<th>Débit</th>";
        $html .= "<th>Crédit</th>";
        $html .= "<th>Devise</th>";
        $html .= "<th>Date de valeur</th>";
        $html .= "<th>Libellé interbancaire</th>";
        $html .= '</tr>';

        $html .= '</thead>';

        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($this->local_date()) . '</td>';
        $html .= '<td>' . htmlspecialchars($this->nature()) . '</td>';
        $html .= '<td>' . ($this->debit() ? euro($this->debit()) : '') . '</td>';
        $html .= '<td>' . ($this->credit() ? euro($this->credit()) : '') . '</td>';
        $html .= '<td>' . htmlspecialchars($this->currency()) . '</td>';
        $html .= '<td>' . htmlspecialchars($this->local_value_date()) . '</td>';
        $html .= '<td>' . htmlspecialchars($this->interbank_label()) . '</td>';
        $html .= '</tr>';

        foreach ($this->comments() as $comment) {
            $html .= '<tr>';
            $html .= '<td></td>';
            $html .= '<td>' . htmlspecialchars($comment) . '</td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';

            $html .= '</tr>';
        }

        $html .= $this->rapprochements_to_html($this);

        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    private function rapprochements_to_html() {
        $html = "";

        if ($this->is_rapproched()) {
            $html .= '<tr class="table-secondary">';
            $html .= '<td colspan="7" class="text-start">Ecritures rapprochées</td>';
            $html .= '</tr>';
            foreach ($this->reconciliated() as $reconciliation) {
                $html .= $reconciliation->to_HTML();
            }
        } elseif ($this->choices_count() == 1) {
            // Une seule proposition unique - afficher avec checkbox et champ caché
            $html .= '<tr class="table-secondary">';
            $html .= '<td colspan="7" class="text-start">Proposition de rapprochements</td>';
            $html .= '</tr>';
            $html .= $this->unique_proposal_html();
        } elseif ($this->choices_count() > 1) {
            // Plusieurs propositions - afficher avec checkbox et dropdown
            $html .= '<tr class="table-secondary">';
            $html .= '<td colspan="7" class="text-start">Choix de rapprochements</td>';
            $html .= '</tr>';
            $html .= $this->multiple_proposals_html();
        } elseif ($this->is_multiple_combination()) {
            // multiple proposals avec combinaisons multiples
            $html .= '<td colspan="7" class="text-start">Proposition de rapprochements multiples</td>';

            foreach ($this->multiple_proposals as $combination) {
                $html .= $combination->to_HTML();
                // separator between combinations (only if there are several)
                if (count($this->multiple_proposals) > 1 && $combination !== end($this->multiple_proposals)) {
                    $html .= '<tr class="table-secondary">';
                    $html .= '<td colspan="7" class="text-center">Autre combinaison possible</td>';
                    $html .= '</tr>';
                }
            }
        } else {
            // nothing found
            $html .= $this->no_proposal_html();
        }
        return $html;
    }

    public function no_proposal_html() {
        $html = "";
        $html .= '<tr>';

        // Colonne 1: Badge "Non rapproché" avec champ caché
        $line_number = $this->line();
        $str_releve = $this->str_releve();
        $badge = '<div class="badge bg-danger text-white rounded-pill ms-1">Non rapproché</div>';
        $hidden = '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';

        $html .= '<td>' . $badge . $hidden . '</td>';

        // Colonne 2: Message d'erreur
        $html .= '<td><span class="text-danger">Aucune écriture trouvée</span></td>';

        // Colonnes 3-5: vides
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';

        // Colonne 6: Numéro de ligne
        $html .= '<td>Ligne:' . $line_number . '</td>';

        // Colonne 7: Type d'opération
        $html .= '<td>' . $this->type_string() . '</td>';

        $html .= '</tr>';
        return $html;
    }

    public function unique_proposal_html() {
        $html = "";
        $html .= '<tr>';

        // Colonne 1: Checkbox avec champ caché contenant l'ID unique
        $line_number = $this->line();
        $str_releve = $this->str_releve();
        $checkbox = '<input type="checkbox" class="unique" name="cb_' . $line_number . '" value="1">';
        $hidden = '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';

        // Récupérer la première (et seule) proposition
        $first_proposal = reset($this->proposals);
        if ($first_proposal) {
            $hidden .= '<input type="hidden" name="op_' . $line_number . '" value="' . $first_proposal->ecriture . '">';
        }

        // Vérifier s'il y a déjà un rapprochement pour cette opération
        $is_already_reconciled = !empty($this->reconciliated);
        
        // Créer le bouton approprié selon l'état du rapprochement
        if ($is_already_reconciled) {
            // Opération déjà rapprochée - bouton pour supprimer le rapprochement
            $button = '<button type="button" class="badge bg-success text-white rounded-pill ms-1 border-0 auto-unreconcile-btn" 
                       data-string-releve="' . htmlspecialchars($str_releve) . '" 
                       data-line="' . $line_number . '"
                       title="Cliquer pour supprimer le rapprochement">
                       Rapproché
                       </button>';
        } else {
            // Opération non rapprochée - bouton pour rapprocher automatiquement
            $button = '<button type="button" class="badge bg-primary text-white rounded-pill ms-1 border-0 auto-reconcile-btn" 
                       data-string-releve="' . htmlspecialchars($str_releve) . '" 
                       data-ecriture-id="' . ($first_proposal ? $first_proposal->ecriture : '') . '" 
                       data-line="' . $line_number . '"
                       title="Cliquer pour rapprocher automatiquement">
                       Rapprocher
                       </button>';
        }
        
        $status = $checkbox . $hidden . $button;

        $html .= '<td>' . $status . '</td>';

        // Colonne 2: Description de l'écriture unique (en vert pour proposition unique)
        if ($first_proposal) {
            $html .= '<td><span class="text-success">' . $first_proposal->image . '</span></td>';
        } else {
            $html .= '<td></td>';
        }

        // Colonnes 3-7: vides
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';

        $html .= '</tr>';
        return $html;
    }

    public function multiple_proposals_html() {
        $html = "";
        $html .= '<tr>';

        // Colonne 1: Checkbox avec champ caché et bouton de rapprochement
        $line_number = $this->line();
        $str_releve = $this->str_releve();
        $checkbox = '<input type="checkbox" name="cb_' . $line_number . '" value="1">';
        $hidden = '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';
        
        // Bouton pour rapprocher avec le choix sélectionné dans le dropdown
        $button = '<button type="button" class="badge bg-primary text-white rounded-pill ms-1 border-0 auto-reconcile-multiple-btn" 
                   data-string-releve="' . htmlspecialchars($str_releve) . '" 
                   data-line="' . $line_number . '"
                   title="Cliquer pour rapprocher avec le choix sélectionné">
                   Rapprocher
                   </button>';

        $status = $checkbox . $hidden . $button;
        $html .= '<td>' . $status . '</td>';

        // Colonne 2: Dropdown avec les propositions multiples
        $html .= '<td>';

        // Créer le tableau d'options pour le dropdown
        $options = [];
        foreach ($this->proposals as $proposal) {
            $options[$proposal->ecriture] = $proposal->image;
        }

        $attrs = 'class="form-control big_select big_select_large select2-hidden-accessible" tabindex="-1" aria-hidden="true"';
        $dropdown = dropdown_field("op_" . $line_number, "", $options, $attrs);

        $html .= $dropdown;
        $html .= '</td>';

        // Colonnes 3-5: vides
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';

        $html .= '</tr>';
        return $html;
    }

    /**
     * Affiche un dump pour le débogage
     * 
     * @param string $title Titre du dump
     * @param bool $exit Indique si le script doit s'arrêter après le dump
     */
    public function dump($title = "", $exit = false) {
        $tab = "    ";
        $bt = debug_backtrace();
        $caller = $bt[0];
        echo "<pre>";
        echo "StatementOperation $title $exit:\n";
        echo "from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";

        echo $tab . "date: " . $this->date() . "\n";
        echo $tab . "local_date: " . $this->local_date() . "\n";
        echo $tab . "value_date: " . $this->value_date() . "\n";
        echo $tab . "local_value_date: " . $this->local_value_date() . "\n";
        echo $tab . "nature: " . $this->nature() . "\n";
        echo $tab . "debit: " . $this->debit() . "\n";
        echo $tab . "credit: " . $this->credit() . "\n";
        echo $tab . "amount: " . $this->amount() . "\n";
        echo $tab . "interbank_label: " . $this->interbank_label() . "\n";
        echo $tab . "comments: " . "\n";
        foreach ($this->comments() as $comment) {
            echo $tab . "    " . $comment . "\n";
        }
        echo $tab . "line: " . $this->line() . "\n";
        echo $tab . "type: " . $this->type() . "\n";

        if ($this->is_rapproched()) {
            $reconciliation_count = count($this->reconciliated);
            echo $tab . "Reconciliated ($reconciliation_count):\n";
            foreach ($this->reconciliated as $index => $reconciliation) {
                echo $tab . "  [$index] => ";
                $reconciliation->dump("rapprochement", false);
            }
        } else {
            echo "Not reconciliated\n";
        }

        $choices_count = $this->choices_count();
        if ($choices_count) {
            echo "Proposals ($choices_count):\n";
            foreach ($this->proposals as $index => $proposal) {
                // echo $tab . "  [$index] => ";
                $proposal->dump("proposal", false);
            }
        }

        if ($this->multiple_proposals) {
            $multiple_count = count($this->multiple_proposals);
            echo $tab . "Multiple Proposals ($multiple_count):\n";
            if ($multiple_count > 0) {
                foreach ($this->multiple_proposals as $index => $combination) {
                    echo $tab . "combinaison: " . ($index + 1) . " (ID: " . $combination->combinationId . ")\n";
                    echo $tab . "  total: " . number_format($combination->totalAmount, 2) . " €\n";
                    echo $tab . "  confidence: " . $combination->confidence . "%\n";
                    echo $tab . "  ecritures count: " . count($combination->combination_data) . "\n";
                    foreach ($combination->combination_data as $ecriture_index => $ecriture_data) {
                        echo $tab . "    [$ecriture_index] " . $ecriture_data['ecriture'] . " => " . $ecriture_data['image'] . "\n";
                    }
                }
            }
        } else {
            echo "No multiple proposals\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    private function reconciliate() {

        // Les informations scalaires sont définies
        // Maintenant il faut voir si l'objet est raprochable avec une ou
        // plusieurs écritures comptables

        $this->get_reconciliated();

        if (empty($this->reconciliated)) {
            // Look for proposals
            $this->get_proposals();

            if (empty($this->proposals) && empty($this->multiple_proposals)) {
                // try to split into multiple
                $this->get_multiple_proposals();
            }
        }
    }

    public function date() {
        return isset($this->parser_info->date) ? $this->parser_info->date : null;
    }

    public function local_date() {
        return date_db2ht($this->date());
    }

    public function value_date() {
        return isset($this->parser_info->value_date) ? $this->parser_info->value_date : null;
    }

    public function local_value_date() {
        return date_db2ht($this->value_date());
    }

    public function nature() {
        return isset($this->parser_info->nature) ? $this->parser_info->nature : null;
    }

    public function debit() {
        return isset($this->parser_info->debit) ? $this->parser_info->debit : null;
    }

    public function credit() {
        return isset($this->parser_info->credit) ? $this->parser_info->credit : null;
    }

    public function amount() {
        if (!empty($this->debit())) {
            $amount = $this->debit();
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        } elseif (!empty($this->credit())) {
            $amount = $this->credit();
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        }
        return null;
    }

    public function currency() {
        return isset($this->parser_info->currency) ? $this->parser_info->currency : null;
    }

    public function interbank_label() {
        return isset($this->parser_info->interbank_label) ? $this->parser_info->interbank_label : null;
    }

    public function comments() {
        return isset($this->parser_info->comments) ? $this->parser_info->comments : null;
    }

    public function line() {
        return isset($this->parser_info->line) ? $this->parser_info->line : null;
    }

    public function type() {
        // todo move the treatment to here
        return isset($this->parser_info->type) ? $this->parser_info->type : null;
    }

    public function type_string() {
        if (isset($this->parser_info->type)) {
            if (isset($this->recognized_types[$this->parser_info->type])) {
                return $this->recognized_types[$this->parser_info->type];
            } else {
                return "autre";
            }
        }
        return null;
    }

    public function is_rapproched() {
        return !empty($this->reconciliated);
    }

    public function is_unique() {
        return isset($this->proposals) ? (count($this->proposals) == 1) : 0;
    }

    public function choices_count() {
        return isset($this->proposals) ? count($this->proposals) : 0;
    }

    public function multiple_count() {
        return isset($this->multiple_proposals) ? count($this->multiple_proposals) : 0;
    }

    /**
     * True if there are multiple proposals
     */
    public function is_multiple_combination() {
        if (!isset($this->multiple_proposals)) {
            return false;
        }
        if (count($this->multiple_proposals) == 0) {
            return false;
        }
        return true;
    }

    public function is_multiple() {
        return isset($this->proposals) ? (count($this->proposals) > 1) : false;
    }

    public function reconciliated() {
        return $this->reconciliated;
    }

    public function nothing_found() {
        return empty($this->reconciliated) && empty($this->proposals) && empty($this->multiple_proposals);
    }

    /**
     * Récupère les lignes de rapprochement associées à cette opération.
     * @return array
     */
    public function get_reconciliated() {
        $lines = [];

        // si il y a déjà une ou plusieurs écritures associées
        // On crée une ligne de rapprochement par écriture
        $string_releve = $this->str_releve();

        $gvv_ecritures_list = $this->CI->associations_ecriture_model->get_by_string_releve($string_releve);
        foreach ($gvv_ecritures_list as $gvv_ecriture) {
            // Enrichir les données avec les informations nécessaires pour le HTML
            $gvv_ecriture['line'] = $this->line();
            $gvv_ecriture['str_releve'] = $string_releve;
            $gvv_ecriture['type_string'] = $this->type_string();

            $line = new ReconciliationLine(['rapprochements' => $gvv_ecriture]);
            $lines[] = $line;
        }

        $this->reconciliated = $lines;
    }

    /**
     * les lignes de suggestions
     * @return array
     */
    private function get_proposals() {
        $lines = [];
        // todo generate a multiple_combination
        if ($this->type() === 'prelevement_pret' && false) {
            // split the amount into two parts
            // Extract capital amorti and interest amounts from comments
            $capital = 0.0;
            $interets = 0.0;

            $comments = $this->comments();

            // Check if we have comments and the first one matches 'CAPITAL AMORTI'
            if (!empty($comments[0]) && strpos($comments[0], 'CAPITAL AMORTI') !== false) {
                // Extract the numeric value after ': '
                $parts = explode(': ', $comments[0]);
                if (count($parts) == 2) {
                    $capital = str_replace(',', '.', trim($parts[1]));
                }

                // Check for interest amount in comments
                if (!empty($comments[1]) && strpos($comments[1], 'INTERETS') !== false) {
                    $parts = explode(': ', $comments[1]);
                    if (count($parts) == 2) {
                        $interets = str_replace(',', '.', trim($parts[1]));
                    }

                    $capital_lines = $this->get_proposals_for_amount($capital);
                    $interets_lines = $this->get_proposals_for_amount($interets);

                    $lines = array_merge($capital_lines, $interets_lines);

                    foreach ($lines as $line) {
                        $line->dump();
                    }
                } else {
                    $this->proposals = $this->get_proposals_for_amount($this->amount());
                }
            } else {
                // It is a loan payment but we cannot split it
                $this->proposals = $this->get_proposals_for_amount($this->amount());
            }
        } else {
            $this->proposals = $this->get_proposals_for_amount($this->amount());
        }

        // Si on sait proposer une ou plusieurs écritures à rapprocher
        // Avec le montant global
        // On crée les objets et retourne la liste
        return $lines;
    }

    private function get_proposals_for_amount($amount) {
        $proposal_lines = [];
        // Logic to get proposals for a specific amount
        // On utilise le modèle ecritures_model pour obtenir les écritures
        // qui correspondent à l'opération du relevé bancaire
        $delta = $this->CI->session->userdata('rapprochement_delta');
        if (!$delta) {
            $delta = 5; // Default delta value
        }

        if ($this->debit()) {
            $compte1 = null;
            $compte2 = $this->gvv_bank_account;
        } else {
            $compte1 = $this->gvv_bank_account;
            $compte2 = null;
        }

        // todo: parametres à supprimer
        $start_date = "2000-01-01";
        $end_date = "2100-01-01";
        $reference_date = $this->value_date();

        $lines = $this->CI->ecritures_model->ecriture_selector($start_date, $end_date, $amount, $compte1, $compte2, $reference_date, $delta);

        $smart_mode = $this->CI->session->userdata('rapprochement_smart_mode') ?? false;
        if ($smart_mode) {
            $this->CI->load->library('rapprochements/SmartAdjustor');
            $smart = new SmartAdjustor();
            $lines = $smart->smart_adjust($lines, $this);
        }

        // Convertir le hash d'écritures en objets ProposalLine
        foreach ($lines as $ecriture_id => $image) {
            $proposal_data = [
                'ecriture_hash' => [$ecriture_id => $image],
                'line_number' => $this->line(),
                'str_releve' => $this->str_releve(),
                'choices_count' => count($lines),
                'type_string' => $this->type_string()
            ];
            $proposal_lines[] = new ProposalLine($proposal_data);
        }

        return $proposal_lines;
    }

    /**
     * 
     */
    public function get_multiple_proposals() {
        $amount = $this->amount();
        $reference_date = $this->value_date();
        $delta = $this->CI->session->userdata('rapprochement_delta');
        if (!$delta) {
            $delta = 5; // Default delta value
        }
        if ($this->debit()) {
            $compte1 = null;
            $compte2 = $this->gvv_bank_account;
        } else {
            $compte1 = $this->gvv_bank_account;
            $compte2 = null;
        }
        $lines = $this->CI->ecritures_model->ecriture_selector_lower_than($amount, $compte1, $compte2, $reference_date, $delta);

        $sequence = [];
        foreach ($lines as $key => $line) {
            $line['ecriture'] = $key;
            $sequence[] = $line;
        }
        $combinations_array = $this->search_combinations($sequence, $amount);

        // Convertir les combinaisons en objets MultiProposalCombination
        $this->multiple_proposals = [];
        if ($combinations_array) {
            foreach ($combinations_array as $combination_data) {
                $multi_proposal_data = [
                    'combination_data' => $combination_data,
                    'line_number' => $this->line(),
                    'str_releve' => $this->str_releve(),
                    'multiple_count' => count($combinations_array),
                    'type_string' => $this->type_string()
                ];
                $this->multiple_proposals[] = new MultiProposalCombination($multi_proposal_data);
            }
            // gvv_dump($this->multiple_proposals, false, "multiple proposals objects created");
        }
    }


    /**
     * Recherche récursive de combinaisons d'écritures dont la somme des montants est égale à $target_amount.
     * 
     * @param array $lines Liste séquentielle des écritures
     *              une écriture est un tableau associatif avec au moins une clé 'montant'
     * @param string $target_amount Montant cible à atteindre
     * 
     * @return array|false Retourne une liste de combinaisons d'écritures ou false si aucune combinaison n'est trouvée.
     *                     Une combinaison est une liste d'écritures.
     */
    private function search_combinations($lines, $target_amount) {

        // gvv_dump($lines, false, "search_combinations, target=" . $target_amount);

        $res = []; // by default an empty list

        foreach ($lines as $line) {
            $diff = abs(floatval($line['montant']) - floatval($target_amount));
            // echo "diff = $diff\n";
            if ($diff < 0.01) {
                // echo "found a combination\n";
                $res[] =  [$line];
            }
        }

        $current_list = $lines;
        while ($current_list) {
            $elt = array_shift($current_list);
            $current_montant = $elt['montant'];

            $search = $this->search_combinations($current_list, $target_amount - $current_montant);
            if ($search) {
                foreach ($search as $combi) {
                    $combi[] = $elt;
                    $res[] = $combi;
                }
            }
        }
        return $res;
    }

    /**
     * Génère une chaîne unique représentant l'opération de relevé bancaire.
     * 
     * Cette chaîne est utilisée pour identifier de façon unique une opération,
     * notamment lors du rapprochement avec des écritures comptables.
     * Elle concatène plusieurs champs clés de l'opération, séparés par des underscores,
     * puis remplace tous les caractères non alphanumériques par des underscores.
     * 
     * @return string Chaîne unique représentant l'opération
     */
    public function str_releve() {
        $str = "";
        $str .= $this->date() . "_";
        $str .= $this->nature() . "_";
        $str .= $this->amount() . "_";
        $str .= $this->currency() . "_";
        $str .= $this->value_date() . "_";
        $str .= $this->interbank_label() . "_";
        $str .= implode(" ", $this->comments()) . "_";
        $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
        return $str;
    }
}
