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
    private $multiple_combinations = []; // list of MultiProposalCombination objects 

    // required to interpret the type field, so injected in constructor
    private $recognized_types = [];

    private $smart_agent;

    private $correlations = [];

    /**
     * $reconciliated : référence les écritures GVV qui ont été rapprochées avec cette opération.
     * il peut y en avoir plusieurs. Invariant: La somme des écritures rapprochées doit correspondre au montant dans le relevé.
     * 
     * $proposals : Propose des écritures GVV qui pourraient être rapprochées avec cette opération. Ces écritures on un montant qui correspond au montant dans le relevé.
     * 
     * $multiple_combinations : Propose des ensembles d'écritures GVV qui pourraient être rapprochées avec cette opération. La somme des montants des écritures dans chaque ensemble doit correspondre au montant dans le relevé. Il peut y avoir plusieurs ensembles proposés. L'ensemble doit être rapproché globalement.
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
        $this->CI->load->library('rapprochements/SmartAdjustor');

        $this->smart_agent = new SmartAdjustor();

        $this->parser_info = $data['parser_info'];
        $this->gvv_bank_account = isset($data['gvv_bank_account']) ? $data['gvv_bank_account'] : null;
        $this->recognized_types = isset($data['recognized_types']) ? $data['recognized_types'] : [];
        $this->reconciliate();
        // $this->dump("constructor", false);
    }

    public function set_correlation($ecriture_id, $correlation, $image) {
        $this->correlations[$ecriture_id] = ['correlation' => $correlation, 'image' => $image];
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

    /**
     * Génère le HTML pour les rapprochements, propositions et combinaisons.
     *
     * Cette méthode affiche, selon l'état de l'opération :
     * - Les écritures déjà rapprochées
     * - Une proposition unique de rapprochement
     * - Plusieurs propositions (sous forme de liste déroulante)
     * - Des combinaisons multiples d'écritures
     * - Ou un message indiquant qu'aucune écriture n'a été trouvée
     *
     * @return string HTML généré pour la section rapprochements/propositions
     */
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
            $html .= $this->multiple_combinations_html();
        } elseif ($this->is_multiple_combination()) {
            // Propositions de combinaisons multiples d'écritures
            $html .= '<td colspan="7" class="text-start">Proposition de combinaisons</td>';

            foreach ($this->multiple_combinations as $combination) {
                $html .= $combination->to_HTML();
                // Séparateur entre les combinaisons (s'il y en a plusieurs)
                if (count($this->multiple_combinations) > 1 && $combination !== end($this->multiple_combinations)) {
                    $html .= '<tr class="table-secondary">';
                    $html .= '<td colspan="7" class="text-center">Autre combinaison possible</td>';
                    $html .= '</tr>';
                }
            }
        } else {
            // Aucune écriture trouvée
            $html .= $this->no_proposal_html();
        }
        return $html;
    }

    /**
     * Generates HTML output indicating that there are no proposals available.
     *
     * This method is typically used to display a message or placeholder in the UI
     * when no matching proposals are found for a given statement operation.
     *
     * @return string The HTML content to display when no proposals are present.
     */
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

    /**
     * Generates and returns the HTML representation for a unique proposal.
     *
     * This method is responsible for creating the HTML output that represents
     * a unique proposal within the context of statement operations. The specific
     * structure and content of the HTML will depend on the implementation details.
     *
     * @return string The HTML markup for the unique proposal.
     */
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

    /**
     * Generates the HTML output for displaying multiple proposals.
     *
     * This method is responsible for creating and returning the HTML
     * representation of multiple proposals, typically used in the context
     * of statement operations or rapprochements.
     *
     * @return string The generated HTML for multiple proposals.
     */
    public function multiple_combinations_html() {
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

        // Colonne 2: Dropdown ou radio buttons selon le nombre d'options
        $html .= '<td>';

        // Créer le tableau d'options pour le dropdown/radio
        $options = [];
        foreach ($this->proposals as $proposal) {
            $options[$proposal->ecriture] = $proposal->image;
        }

        $nb_options = count($options);
        
        if ($nb_options < 5) {
            // Utiliser des radio buttons pour moins de 5 options avec des liens
            $html .= '<div class="radio-group">';
            foreach ($options as $option_id => $option_label) {
                $html .= '<div class="form-check mb-1">';
                $html .= '<input class="form-check-input" type="radio" name="op_' . $line_number . '" id="op_' . $line_number . '_' . $option_id . '" value="' . $option_id . '">';
                $html .= '<label class="form-check-label" for="op_' . $line_number . '_' . $option_id . '">';
                
                // Créer un lien vers l'écriture, mais rester sur la fenêtre courante
                $ecriture_url = site_url('compta/edit/' . $option_id);
                $html .= '<a href="' . $ecriture_url . '" class="text-decoration-none">';
                $html .= $option_label;
                $html .= '</a>';
                
                $html .= '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            // Utiliser le dropdown pour 5 options ou plus
            $attrs = 'class="form-control big_select big_select_large select2-hidden-accessible" tabindex="-1" aria-hidden="true"';
            $dropdown = dropdown_field("op_" . $line_number, "", $options, $attrs);
            $html .= $dropdown;
        }

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

        if ($this->multiple_combinations) {
            $multiple_count = count($this->multiple_combinations);
            echo $tab . "Multiple Proposals ($multiple_count):\n";
            if ($multiple_count > 0) {
                foreach ($this->multiple_combinations as $index => $combination) {
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

        if ($this->correlations) {
            echo "Correlations:\n";
            foreach ($this->correlations as $ecriture_id => $data) {
                echo $tab . "Ecriture ID: $ecriture_id, Correlation: " . $data['correlation'] . ", Image: " . $data['image'] . "\n";
            }
        } else {
            echo "No correlations\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    /**
     * Attempts to reconcile statement operations.
     *
     * This private method performs the reconciliation process for statement operations.
     * It matches and processes relevant data to ensure consistency between statements
     * and operations, updating internal state as necessary.
     *
     * @return void
     */
    private function reconciliate() {

        // Les informations scalaires sont définies
        // Maintenant il faut voir si l'objet est raprochable avec une ou
        // plusieurs écritures comptables

        gvv_debug("Rapprochement: ooking for reconciliated for operation line " . $this->line());
        $this->get_reconciliated();

        if (empty($this->reconciliated)) {
            // Look for proposals
            gvv_debug("Rapprochement: Looking for proposals for operation line " . $this->line());
            $this->get_proposals();

            if (empty($this->proposals) && empty($this->multiple_combinations)) {
                // try to split into multiple
                gvv_debug("Rapprochement: Looking for combinations for operation line " . $this->line());
                $this->get_multiple_combinations();
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
        return isset($this->multiple_combinations) ? count($this->multiple_combinations) : 0;
    }

    /**
     * True if there are multiple proposals
     */
    public function is_multiple_combination() {
        if (!isset($this->multiple_combinations)) {
            return false;
        }
        if (count($this->multiple_combinations) == 0) {
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
        return empty($this->reconciliated) && empty($this->proposals) && empty($this->multiple_combinations);
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

        $this->proposals = $this->get_proposals_for_amount($this->amount());


        // Si on sait proposer une ou plusieurs écritures à rapprocher
        // Avec le montant global
        // On crée les objets et retourne la liste
        return $lines;
    }
    /**
     * [Function Name]
     *
     * [Brief description of what the function does.]
     *
     * @param [type] $[parameter] [Description of the parameter.]
     * @return [type] [Description of the return value.]
     */
    private function get_proposals_for_amount($amount, $smart = true) {
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
        if ($smart_mode && $smart) {
            $lines = $this->smart_agent->smart_adjust($lines, $this);
        }

        // $this->dump();

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
     * Recherche des combinaisons multiples d'écritures dont la somme des montants est égale au montant de l'opération.
     * 
     * Cette méthode utilise une recherche récursive pour identifier toutes les combinaisons possibles
     * d'écritures qui, lorsqu'elles sont additionnées, correspondent au montant spécifié dans l'opération
     * de relevé bancaire. Les combinaisons trouvées sont ensuite stockées sous forme d'objets MultiProposalCombination.
     * 
     * @return void
     */
    public function get_multiple_combinations() {
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
        $this->multiple_combinations = [];
        if ($combinations_array) {
            foreach ($combinations_array as $combination_data) {
                $multi_proposal_data = [
                    'combination_data' => $combination_data,
                    'line_number' => $this->line(),
                    'str_releve' => $this->str_releve(),
                    'multiple_count' => count($combinations_array),
                    'type_string' => $this->type_string()
                ];
                $this->multiple_combinations[] = new MultiProposalCombination($multi_proposal_data);
            }
            // gvv_dump($this->multiple_combinations, false, "multiple proposals objects created");
        }

        if (count($this->multiple_combinations) == 1) {
            $combi = $this->multiple_combinations[0];

            if ($combi->ecritures_count() == 1) {
                $montant = $combi->combination_data[0]['montant'];
                if ($montant == $this->amount()) {
                    // this is not a multiple combination, but a single proposal
                    $this->proposals = $this->get_proposals_for_amount($amount, false);
                    $this->multiple_combinations = [];
                }
            }
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

        if (count($lines) == 0 || $target_amount <= 0 || $target_amount > 10000) {
            return false;
        }

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
        if (count($current_list) > 9) {
            return []; // limit the recursion depth
        }
        while ($current_list) {
            $elt = array_shift($current_list);
            $current_montant = $elt['montant'];

            gvv_debug("Rapprochements: search_combinations, target=" . $target_amount
                . ", lines count=" . count($current_list));
            // gvv_dump($current_list, false);

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
