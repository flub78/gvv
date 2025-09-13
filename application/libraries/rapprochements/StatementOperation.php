<?php

/**
 * Individual bank statement operation with reconciliation capabilities
 * 
 * Represents a single operation from a bank statement and provides comprehensive
 * reconciliation functionality including automatic matching suggestions, manual
 * reconciliation support, and HTML rendering for user interfaces.
 * 
 * Core Functionality:
 * - Parses and validates bank operation data
 * - Generates reconciliation suggestions (single, multiple, combinations)
 * - Tracks reconciliation status and history  
 * - Provides HTML rendering for display and interaction
 * - Supports smart matching algorithms with correlation scoring
 * 
 * Reconciliation Types:
 * - Single proposals: One-to-one matches with GVV entries
 * - Multiple proposals: One-to-many choices for manual selection
 * - Combination proposals: Many-to-one matches totaling operation amount
 * - Manual reconciliation: User-directed matching interface
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

    private $status = "";

    /**
     * $reconciliated : référence les écritures GVV qui ont été rapprochées avec cette opération.
     * il peut y en avoir plusieurs. Invariant: La somme des écritures rapprochées doit correspondre au montant dans le relevé.
     * 
     * $proposals : Propose des écritures GVV qui pourraient être rapprochées avec cette opération. Ces écritures on un montant qui correspond au montant dans le relevé.
     * 
     * $multiple_combinations : Propose des ensembles d'écritures GVV qui pourraient être rapprochées avec cette opération. La somme des montants des écritures dans chaque ensemble doit correspondre au montant dans le relevé. Il peut y avoir plusieurs ensembles proposés. L'ensemble doit être rapproché globalement.
     */

    /**
     * Initialize statement operation with parsed bank data and reconciliation context
     * 
     * Creates a StatementOperation instance from parsed bank statement data and
     * immediately begins reconciliation analysis. Loads required libraries for
     * reconciliation processing and smart matching algorithms.
     * 
     * Expected data structure:
     * - 'parser_info': Raw bank operation data from ReleveParser
     * - 'gvv_bank_account': Associated GVV chart of accounts ID
     * - 'recognized_types': Array mapping operation types to display labels
     * 
     * @param array|null $data Configuration array with operation data and context
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
    }

    /**
     * Store correlation score for a specific GVV accounting entry
     * 
     * Used by smart matching algorithms to store correlation coefficients
     * between this bank operation and potential GVV accounting entry matches.
     * Higher correlations indicate better matching likelihood.
     * 
     * @param int $ecriture_id GVV accounting entry ID
     * @param float $correlation Correlation coefficient (typically 0.0-1.0)
     * @param string $image Human-readable description of the accounting entry
     * @return void
     */
    public function set_correlation($ecriture_id, $correlation, $image) {
        $this->correlations[$ecriture_id] = ['correlation' => $correlation, 'image' => $image];
    }

    /**
     * Generate comprehensive HTML representation for reconciliation interface
     * 
     * Creates a complete HTML table showing the bank statement operation details,
     * current reconciliation status, matching suggestions, and available actions.
     * Includes interactive elements for reconciliation selection and manual matching.
     * 
     * Output includes:
     * - Operation details (date, amount, type, description)
     * - Current reconciliation status and associations
     * - Automatic matching suggestions with selection controls
     * - Manual reconciliation button (when applicable)
     * - Multiple combination suggestions for complex matches
     * 
     * @param bool $non_manual Whether to show manual reconciliation button (default: true)
     * @return string Complete HTML representation for display in reconciliation interface
     */
    public function to_HTML($non_manual = true) {
        $html = "";

        $html .= '<table class="table rapprochement table-striped table-bordered border border-dark rounded mb-3 w-100 operations">';
        $html .= '<thead>';

        $html .= '<tr class="compte row_title">';
        $html .= '<th>Date</th>';
        $type = $this->type_string();
        $ligne = $this->line();
        $status = $this->status;
        $html .= "<th>Nature de l'opération: $type, ligne: $ligne $status</th>";
        $html .= "<th>Débit</th>";
        $html .= "<th>Crédit</th>";
        $html .= "<th>Devise</th>";
        $html .= "<th>Date de valeur</th>";
        $html .= "<th>Libellé interbancaire</th>";
        $html .= '</tr>';

        $html .= '</thead>';

        $html .= '<tbody>';


        $html .= '<tr>';
        // Date avec bouton rapprochement manuel (seulement si pas rapproché et si show_manual_buttons est true)
        $html .= '<td>' . htmlspecialchars($this->local_date());
        if (!$this->is_rapproched() && $non_manual) {
            $line_number = $this->line();
            $html .= ' <button type="button" class="badge bg-primary text-white rounded-pill ms-2 border-0" 
                         onclick="window.location.href=\'' . site_url('rapprochements/rapprochement_manuel?line=' . $line_number) . '\'" 
                         title="Rapprochement manuel">
                         Rapprochement manuel
                      </button>';
        }
        $html .= '</td>';
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

        if ($non_manual) {
            $html .= $this->rapprochements_to_html();
        }

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
    /**
     * Generate HTML for operations with no matching suggestions
     * 
     * Creates HTML display for bank statement operations where no automatic
     * reconciliation suggestions could be found. Shows operation details with
     * indication that manual intervention is required.
     * 
     * @return string HTML for operations requiring manual reconciliation
     */
    public function no_proposal_html() {
        $html = "";
        $html .= '<tr>';

        // Colonne 1: Badge "Non rapproché" avec champ caché - pas de bouton manuel ici
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
        $html .= '<td></td>';
        $html .= '<td></td>';

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
    /**
     * Generate HTML for operations with a single reconciliation suggestion
     * 
     * Creates HTML display for bank statement operations where exactly one
     * potential GVV accounting entry match was found. Includes auto-selection
     * checkbox and match confidence indicators.
     * 
     * @return string HTML for operations with unique reconciliation suggestions
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
            // Créer un lien vers l'écriture
            $ecriture_url = site_url('compta/edit/' . $first_proposal->ecriture);

            // Récupérer le coefficient de corrélation pour afficher en tooltip
            $tooltip = '';
            if (isset($this->correlations[$first_proposal->ecriture])) {
                $correlation = $this->correlations[$first_proposal->ecriture]['correlation'];
                $confidence_percent = round($correlation * 100, 1);
                $tooltip = ' title="Indice de confiance: ' . $confidence_percent . '%"';
            }

            $html .= '<td><a href="' . $ecriture_url . '" class="text-decoration-none"' . $tooltip . '><span class="text-success">' . $first_proposal->image . '</span></a></td>';
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
    /**
     * Generate HTML for operations with multiple combination reconciliation options
     * 
     * Creates HTML display for bank statement operations where multiple GVV
     * accounting entries can be combined to match the operation amount. Shows
     * each combination as a selectable group with individual entry details.
     * 
     * @return string HTML for operations with multiple combination suggestions
     */
    public function multiple_combinations_html() {
        $html = "";
        $html .= '<tr>';

        // Colonne 1: Checkbox avec champ caché et bouton de rapprochement - pas de bouton manuel
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

                // Récupérer le coefficient de corrélation pour afficher en tooltip
                $tooltip = '';
                if (isset($this->correlations[$option_id])) {
                    $correlation = $this->correlations[$option_id]['correlation'];
                    $confidence_percent = round($correlation * 100, 1);
                    $tooltip = ' title="Indice de confiance: ' . $confidence_percent . '%"';
                }

                $html .= '<a href="' . $ecriture_url . '" class="text-decoration-none"' . $tooltip . '>';
                $html .= $option_label;
                $html .= '</a>';

                $html .= '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            // Utiliser le dropdown pour 5 options ou plus
            // Modifier les options pour inclure les indices de confiance
            $options_with_confidence = [];
            foreach ($options as $option_id => $option_label) {
                $confidence_text = '';
                if (isset($this->correlations[$option_id])) {
                    $correlation = $this->correlations[$option_id]['correlation'];
                    $confidence_percent = round($correlation * 100, 1);
                    $confidence_text = " (Confiance: {$confidence_percent}%)";
                }
                $options_with_confidence[$option_id] = $option_label . $confidence_text;
            }

            $attrs = 'class="form-control big_select big_select_large select2-hidden-accessible" tabindex="-1" aria-hidden="true"';
            $dropdown = dropdown_field("op_" . $line_number, "", $options_with_confidence, $attrs);
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
    /**
     * Debug output of complete operation state and reconciliation data
     * 
     * Provides comprehensive debug information including operation details,
     * reconciliation status, matching proposals, correlation scores, and
     * internal state for troubleshooting and analysis.
     * 
     * @param string $title Debug output title (default: "")
     * @param bool $exit Whether to terminate execution after dump (default: false)
     * @return void Outputs debug information
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

        gvv_debug("Rapprochement: looking for reconciliated for operation line " . $this->line());
        $this->get_reconciliated();

        if (empty($this->reconciliated)) {
            // Look for proposals
            gvv_debug("Rapprochement: Looking for proposals for operation line " . $this->line());
            $this->get_proposals();

            $empty_proposals = empty($this->proposals);
            $empty_combinations = empty($this->multiple_combinations);

            if ($empty_proposals && $empty_combinations) {
                // try to split into multiple
                gvv_debug("Rapprochement: Looking for combinations for operation line " . $this->line());
                $this->get_multiple_combinations();
            }
        }
    }

    /**
     * Get operation date in database format (YYYY-MM-DD)
     * 
     * Returns the transaction date as recorded in the bank statement,
     * formatted for database storage and date comparisons.
     * 
     * @return string Operation date in YYYY-MM-DD format
     */
    public function date() {
        return isset($this->parser_info->date) ? $this->parser_info->date : null;
    }

    /**
     * Get operation date in localized display format
     * 
     * Returns the transaction date formatted for display in user interfaces
     * according to local date format preferences (typically DD/MM/YYYY).
     * 
     * @return string Operation date in localized format
     */
    public function local_date() {
        return date_db2ht($this->date());
    }

    /**
     * Get value date in database format (YYYY-MM-DD)
     * 
     * Returns the value date (effective date) when the transaction was actually
     * processed by the bank, which may differ from the operation date.
     * 
     * @return string Value date in YYYY-MM-DD format
     */
    public function value_date() {
        return isset($this->parser_info->value_date) ? $this->parser_info->value_date : null;
    }

    /**
     * Get value date in localized display format
     * 
     * Returns the value date formatted for display in user interfaces
     * according to local date format preferences (typically DD/MM/YYYY).
     * 
     * @return string Value date in localized format
     */
    public function local_value_date() {
        return date_db2ht($this->value_date());
    }

    /**
     * Get operation nature/description from bank statement
     * 
     * Returns the raw nature or description field from the bank statement
     * that categorizes or describes the type of transaction.
     * 
     * @return string|null Operation nature or null if not available
     */
    public function nature() {
        return isset($this->parser_info->nature) ? $this->parser_info->nature : null;
    }

    /**
     * Get debit amount for the operation
     * 
     * Returns the debit amount if this is a debit transaction, 
     * or null if this is a credit transaction.
     * 
     * @return float|null Debit amount or null if not a debit
     */
    public function debit() {
        return isset($this->parser_info->debit) ? $this->parser_info->debit : null;
    }

    /**
     * Get credit amount for the operation
     * 
     * Returns the credit amount if this is a credit transaction,
     * or null if this is a debit transaction.
     * 
     * @return float|null Credit amount or null if not a credit
     */
    public function credit() {
        return isset($this->parser_info->credit) ? $this->parser_info->credit : null;
    }

    /**
     * Get absolute amount for reconciliation matching
     * 
     * Returns the absolute value of the operation amount, whether it's
     * a debit or credit. Used for finding matching GVV accounting entries
     * regardless of transaction direction.
     * 
     * @return float Absolute amount of the operation
     */
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

    /**
     * Get currency code for the operation
     * 
     * Returns the ISO currency code (e.g., 'EUR', 'USD') for this
     * bank statement operation.
     * 
     * @return string|null Currency code or null if not specified
     */
    public function currency() {
        return isset($this->parser_info->currency) ? $this->parser_info->currency : null;
    }

    /**
     * Get interbank label/description for the operation
     * 
     * Returns the standardized interbank communication label that
     * provides additional transaction details and routing information.
     * 
     * @return string|null Interbank label or null if not available
     */
    public function interbank_label() {
        return isset($this->parser_info->interbank_label) ? $this->parser_info->interbank_label : null;
    }

    /**
     * Get additional comments/details for the operation
     * 
     * Returns an array of additional comment fields that provide
     * supplementary information about the bank statement operation.
     * 
     * @return array Array of comment strings
     */
    public function comments() {
        return isset($this->parser_info->comments) ? $this->parser_info->comments : null;
    }

    /**
     * Get line number of operation in bank statement
     * 
     * Returns the sequential line number of this operation within the
     * original bank statement file, used for identification and navigation.
     * 
     * @return int Line number in the bank statement
     */
    public function line() {
        return isset($this->parser_info->line) ? $this->parser_info->line : null;
    }

    /**
     * Get operation type code from bank statement
     * 
     * Returns the bank-specific operation type code that categorizes
     * the transaction (e.g., 'cheque_debite', 'virement_recu', etc.).
     * 
     * @return string|null Operation type code or null if not available
     */
    public function type() {
        // todo move the treatment to here
        return isset($this->parser_info->type) ? $this->parser_info->type : null;
    }

    /**
     * Get human-readable operation type description
     * 
     * Returns the localized, human-readable description for the operation
     * type, suitable for display in user interfaces. Maps type codes to
     * descriptive French labels.
     * 
     * @return string|null Operation type description or 'autre' if unknown
     */
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

    /**
     * Check if operation has been reconciled with GVV accounting entries
     * 
     * Returns true if this bank statement operation has been successfully
     * matched and reconciled with one or more GVV accounting entries.
     * 
     * @return bool True if reconciled, false otherwise
     */
    public function is_rapproched() {
        return !empty($this->reconciliated);
    }

    /**
     * Check if operation has exactly one reconciliation suggestion
     * 
     * Returns true if exactly one potential GVV accounting entry match
     * was found, indicating high confidence for automatic reconciliation.
     * 
     * @return bool True if exactly one proposal exists
     */
    public function is_unique() {
        return isset($this->proposals) ? (count($this->proposals) == 1) : 0;
    }

    /**
     * Get count of individual reconciliation suggestions
     * 
     * Returns the number of single GVV accounting entries that could
     * potentially match this bank statement operation's amount.
     * 
     * @return int Number of individual reconciliation proposals
     */
    public function choices_count() {
        return isset($this->proposals) ? count($this->proposals) : 0;
    }

    /**
     * Get count of multiple combination reconciliation suggestions
     * 
     * Returns the number of combination sets where multiple GVV accounting
     * entries together total the bank statement operation amount.
     * 
     * @return int Number of multiple combination proposals
     */
    public function multiple_count() {
        return isset($this->multiple_combinations) ? count($this->multiple_combinations) : 0;
    }

    /**
     * Check if operation has multiple combination reconciliation options
     * 
     * Returns true if one or more combination sets of GVV accounting entries
     * were found that together match the bank statement operation amount.
     * 
     * @return bool True if multiple combinations are available
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

    /**
     * Check if operation has multiple individual reconciliation choices
     * 
     * Returns true if more than one potential GVV accounting entry match
     * was found, requiring user selection for reconciliation.
     * 
     * @return bool True if multiple individual proposals exist
     */
    public function is_multiple() {
        return isset($this->proposals) ? (count($this->proposals) > 1) : false;
    }

    /**
     * Get existing reconciliation associations for this operation
     * 
     * Returns the array of ReconciliationLine objects representing
     * current reconciliations between this operation and GVV entries.
     * 
     * @return ReconciliationLine[] Array of current reconciliation associations
     */
    public function reconciliated() {
        return $this->reconciliated;
    }

    /**
     * Check if no reconciliation suggestions were found
     * 
     * Returns true if no reconciliation options exist for this operation:
     * no existing reconciliations, no individual proposals, and no
     * multiple combination suggestions.
     * 
     * @return bool True if no reconciliation options are available
     */
    public function nothing_found() {
        return empty($this->reconciliated) && empty($this->proposals) && empty($this->multiple_combinations);
    }

    /**
     * Retrieve existing reconciliation associations from database
     * 
     * Fetches current reconciliation associations for this bank statement
     * operation from the database and converts them to ReconciliationLine
     * objects for display and management.
     * 
     * @return ReconciliationLine[] Array of current reconciliation lines
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
     * Find and analyze multiple GVV entry combinations matching operation amount
     * 
     * Performs recursive search to identify all possible combinations of GVV
     * accounting entries that together equal this bank statement operation's amount.
     * Creates MultiProposalCombination objects for each valid combination found.
     * 
     * Handles performance optimization:
     * - Limits recursion depth to prevent server overload
     * - Integrates smart matching correlations when enabled
     * - Converts single-entry combinations to individual proposals
     * 
     * @return void Updates internal multiple_combinations array
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
        /**
         * Etude de cas:
         * un virement de 300 par X (qui a un compte client)
         * retourne 13 écritures d'un montant inférieur dans la fenêtre de 5 jours
         * Il y a 8191 combinaisons possibles
         */
        $lines = $this->CI->ecritures_model->ecriture_selector_lower_than($amount, $compte1, $compte2, $reference_date, $delta);
        // gvv_dump($lines);

        // Calculer les corrélations en mode smart pour les combinaisons multiples aussi
        $smart_mode = $this->CI->session->userdata('rapprochement_smart_mode') ?? false;
        if ($smart_mode) {
            // Calculer les corrélations pour TOUTES les écritures, même celles qui seront filtrées
            $operation_type = $this->type();
            foreach ($lines as $key => $ecriture) {
                // Calcule le coefficient de corrélation entre l'écriture et l'opération
                $correlation = $this->smart_agent->correlation($this, $key, $ecriture, $operation_type);
                $this->set_correlation($key, $correlation, $ecriture);
            }
        }

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
                    'type_string' => $this->type_string(),
                    'correlations' => $this->correlations // Passer les corrélations
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

        /**
         * A priori les montants négatifs n'existent pas (à confirmer) au premier
         * niveau . Ils sont néanmoins possibles en cours de récursion. Par exemple,
         * on utilise un avoir pour payer des factures. Donc il faut les 
         * prendre en compte.
         */

        /**
         * On essaye de stopper la récursion au plus tôt
         * Sans ce test : entre 1,86 et 1,9 secondes
         * Avec ce test : 1 seconde
         */
        $total_list = 0;
        foreach ($lines as $line) {
            $total_list += $line['montant'];
        }
        if (abs($total_list) + 0.01 < abs($target_amount)) {
            return false;
        }

        if (count($lines) == 0) {
            return false;
        }

        $res = []; // by default an empty list

        foreach ($lines as $line) {
            $diff = abs(floatval($line['montant']) - floatval($target_amount));
            if ($diff < 0.01) {
                // echo "found a combination\n";
                $res[] =  [$line];
            }
        }

        $current_list = $lines;
        if (count($current_list) > 15) {
            $msg = "Dépassement de capacité serveur (" . count($current_list) . ")";
            gvv_info("Rapprochements: " . $msg);
            $this->status = $msg;
            return []; // limit the recursion depth
        }
        while ($current_list) {
            $elt = array_shift($current_list);
            $current_montant = $elt['montant'];

            $search = $this->search_combinations($current_list, $target_amount - $current_montant);
            if ($search) {
                // on a une sous-combinaison
                foreach ($search as $combi) {
                    // on ajoute l'élément courant 
                    $combi[] = $elt;
                    $res[] = $combi;
                }
            }
        }
        return $res;
    }

    /**
     * Generate unique identifier string for this bank statement operation
     * 
     * Creates a unique string by concatenating key operation fields (date, nature,
     * amount, currency, value date, interbank label, and comments) and normalizing
     * to alphanumeric characters. Used as a stable identifier for reconciliation
     * associations across database operations.
     * 
     * @return string Unique alphanumeric identifier for this operation
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
