<?php

/**
 * Bank reconciliation engine for matching statement operations with GVV entries
 * 
 * This class manages the complete bank reconciliation process including statement
 * parsing, operation filtering, automatic matching suggestions, and reconciliation
 * status tracking. It supports multiple filter types, date ranges, and operation
 * categories while maintaining reconciliation statistics.
 * 
 * Supports filtering by:
 * - Date ranges (start/end dates)
 * - Reconciliation status (matched/unmatched)
 * - Operation types (various bank operation categories)
 * - Matching complexity (unique/choices/multiple/no suggestions)
 */
class Reconciliator {
    private $CI;
    private $parser_result;
    private $gvv_bank_account;
    private $operations = [];
    private $filename = "";

    private $rapproched_operations_count = 0;
    private $unique_count = 0;
    private $choices_count = 0;
    private $multiple_count = 0;
    private $no_suggestion_count = 0;

    /**
     * Initialize reconciliation engine with parsed bank statement data
     * 
     * Creates a new Reconciliator instance and processes the parsed bank statement
     * data to create StatementOperation objects, apply session filters, and generate
     * reconciliation suggestions. Loads required models and libraries for processing.
     * 
     * @param array|null $parser_result Parsed bank statement data from ReleveParser
     */
    public function __construct($parser_result = null) {
        $this->CI = &get_instance();
        // Chargement des modèles et bibliothèques nécessaires
        $this->CI->load->model('associations_releve_model');
        $this->CI->load->library('rapprochements/StatementOperation');

        // Only run reconciliation if we have parser result
        if ($parser_result == null) {
            return;
        }

        $this->parser_result = $parser_result;
        $this->reconciliate();
    }

    /**
     * Set the source filename for the bank statement
     * 
     * Stores the full path of the uploaded bank statement file for reference
     * in display headers and logging operations.
     * 
     * @param string $filename Full path to the bank statement file
     * @return void
     */
    public function set_filename($filename) {
        $this->filename = $filename;
    }

    /**
     * Get the full path of the bank statement source file
     * 
     * Returns the complete filesystem path to the uploaded bank statement file
     * that was processed to create this reconciliation session.
     * 
     * @return string Full path to the bank statement file
     */
    public function filename() {
        return $this->filename;
    }

    /**
     * Get the filename without path of the bank statement source file
     * 
     * Returns only the filename portion (without directory path) of the uploaded
     * bank statement file for display in user interfaces.
     * 
     * @return string Filename without path
     */
    public function basename() {
        return basename($this->filename);
    }

    /**
     * Debug dump of raw parser result data
     * 
     * Outputs the complete parsed bank statement data structure for debugging
     * purposes. Shows the raw data before reconciliation processing.
     * 
     * @param bool $exit Whether to terminate script execution after dump (default: true)
     * @return void Outputs debug information
     */
    public function dump_parser_result($exit = true) {
        gvv_dump($this->parser_result, $exit);
    }

    /**
     * Debug dump of processed reconciliation data
     * 
     * Outputs comprehensive reconciliation information including bank details,
     * account mappings, date ranges, operation counts, and individual statement
     * operations for debugging and analysis.
     * 
     * @param string $title Title for the debug output (default: "")
     * @param bool $exit Whether to terminate script execution after dump (default: true)
     * @return void Outputs debug information
     */
    public function dump($title = "", $exit = true) {
        echo "<pre>";
        echo "$title:\n";
        echo "bank: " . $this->bank() . "\n";
        echo "iban: " . $this->iban() . "\n";
        echo "section: " . $this->section() . "\n";
        echo "gvv_bank_account: " . $this->gvv_bank_account() . "\n";
        echo "date_solde: " . $this->date_solde() . "\n";
        echo "titles: ";
        print_r($this->titles());
        echo "total operations: " . $this->total_operation_count() . "\n";
        echo "filtered operations: " . count($this->operations) . "\n";
        foreach ($this->operations as $op) {
            $op->dump("StatementOperation", false);
        }
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    /**
     * Execute the core reconciliation processing logic
     * 
     * Processes the parsed bank statement data by:
     * 1. Looking up GVV account associations for the bank IBAN
     * 2. Applying session filters (date range, operation type, reconciliation status)
     * 3. Creating StatementOperation objects with matching suggestions
     * 4. Maintaining reconciliation statistics for display
     * 
     * This method applies complex filtering logic including:
     * - Date-based filtering using session startDate/endDate
     * - Type-based filtering using operation categories
     * - Status-based filtering (matched/unmatched operations)
     * - Complexity-based filtering (unique/choices/multiple suggestions)
     * 
     * @return void Updates internal operations array and statistics counters
     */
    private function reconciliate() {
        if ($this->parser_result === null) {
            $this->dump("No parser result to reconciliate", false);
            return;
        }

        $this->gvv_bank_account = $this->CI->associations_releve_model->get_gvv_account($this->iban());

        $filter_active = $this->CI->session->userdata('filter_active');
        $startDate = $this->CI->session->userdata('startDate');
        $endDate = $this->CI->session->userdata('endDate');
        $filter_type = $this->CI->session->userdata('filter_type');
        $type_selector = $this->CI->session->userdata('type_selector');

        foreach ($this->parser_result['ops'] as $op) {
            // D'abord on filtre sur la date et le type. Ce sont les informations de filtrage indépendantes 
            // du rapprochement
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
            }

            // Entrée de relevé à traiter, elle sera peut-être éliminé quand même en fonction de ses
            // caractéristiques, mais on commence l'analyse
            $statement_operation = new StatementOperation([
                'parser_info' => $op,
                'gvv_bank_account' => $this->gvv_bank_account(),
                'recognized_types' => $this->recognized_types()
            ]);

            if ($filter_active) {

                if ($filter_type && ($filter_type != "display_all")) {
                    if ($filter_type == 'filter_unmatched') {
                        if ($statement_operation->is_rapproched()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_matched') {
                        if (!$statement_operation->is_rapproched()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_0') {
                        if (!$statement_operation->nothing_found()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_1') {
                        if (!$statement_operation->is_unique()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_choices') {
                        if ($statement_operation->is_rapproched() || 
                            !$statement_operation->is_multiple()) {
                            // On élimine cette opération car elle ne correspond pas au filtre "plusieurs choix"
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_multi') {
                        if (!$statement_operation->is_multiple_combination()) {
                            // $statement_operation->dump("Not multiple combination", false);
                            // On élimine cette opération
                            continue;
                        } else {    
                            // $statement_operation->dump("Is multiple combination", false);
                        }
                    }
                }

                if ($type_selector && ($type_selector != "all")) {
                    if ($type_selector != $statement_operation->type()) {
                        // On élimine cette opération
                        continue;
                    }
                }
            }

            $this->operations[] = $statement_operation;

            if ($statement_operation->is_rapproched()) {
                $this->rapproched_operations_count++;
            } elseif ($statement_operation->is_unique()) {
                $this->unique_count++;
            } elseif ($statement_operation->choices_count()) {
                $this->choices_count++;
            } elseif ($statement_operation->multiple_count()) {
                $this->multiple_count++;
            } else {
                $this->no_suggestion_count++;
            }
        }

        // $this->dump("Reconciliator", false);
    }

    /**
     * Get bank name from statement header
     * 
     * Returns the name of the bank that issued the statement, as parsed from
     * the statement file header information.
     * 
     * @return string|null Bank name or null if not available
     */
    public function bank() {
        return $this->parser_result['bank'] ?? null;
    }

    /**
     * Get IBAN account number from statement header
     * 
     * Returns the International Bank Account Number (IBAN) for the account
     * that this bank statement represents.
     * 
     * @return string|null IBAN account number or null if not available
     */
    public function iban() {
        return $this->parser_result['iban'] ?? null;
    }

    /**
     * Get section/club identifier from statement header
     * 
     * Returns the section or club identifier associated with this bank account,
     * used for multi-club installations to separate financial data.
     * 
     * @return string|null Section identifier or null if not available
     */
    public function section() {
        return $this->parser_result['section'] ?? null;
    }

    /**
     * Get account balance from statement header
     * 
     * Returns the final account balance as of the statement end date,
     * typically used for balance verification after reconciliation.
     * 
     * @return float|null Account balance or null if not available
     */
    public function solde() {
        return $this->parser_result['solde'] ?? null;
    }

    /**
     * Get associated GVV chart of accounts ID for this bank account
     * 
     * Returns the GVV accounting system account ID that corresponds to this
     * bank account, used for automatic account assignment during reconciliation.
     * 
     * @return string|null GVV account ID or null if no association exists
     */
    public function gvv_bank_account() {
        return $this->gvv_bank_account;
    }

    /**
     * Get balance date in localized format
     * 
     * Returns the date when the account balance was calculated, formatted
     * for display in the local date format preference.
     * 
     * @return string|null Balance date in local format or null if not available
     */
    public function date_solde() {
        return $this->parser_result['date_solde'] ?? null;
    }

    /**
     * Get statement period start date in database format
     * 
     * Returns the start date of the bank statement period in YYYY-MM-DD format
     * for database queries and date comparisons.
     * 
     * @return string|null Start date in database format or null if not available
     */
    public function start_date() {
        return $this->parser_result['start_date'] ?? null;
    }

    /**
     * Get statement period end date in database format
     * 
     * Returns the end date of the bank statement period in YYYY-MM-DD format
     * for database queries and date comparisons.
     * 
     * @return string|null End date in database format or null if not available
     */
    public function end_date() {
        return $this->parser_result['end_date'] ?? null;
    }

    /**
     * Get statement period start date in localized format
     * 
     * Returns the start date of the bank statement period formatted for
     * display in user interfaces using local date format preferences.
     * 
     * @return string Start date in localized format
     */
    public function local_start_date() {
        return date_db2ht($this->start_date());
    }

    /**
     * Get statement period end date in localized format
     * 
     * Returns the end date of the bank statement period formatted for
     * display in user interfaces using local date format preferences.
     * 
     * @return string End date in localized format
     */
    public function local_end_date() {
        return date_db2ht($this->end_date());
    }

    /**
     * Get column titles from parsed statement data
     * 
     * Returns the column headers from the bank statement file, typically
     * used for validation or debugging of the parsing process.
     * 
     * @return array|null Array of column titles or null if not available
     */
    public function titles() {
        return $this->parser_result['titles'] ?? null;
    }

    /**
     * Get total number of operations in the original bank statement
     * 
     * Returns the count of all operations in the parsed bank statement data
     * before any filtering is applied. Used for statistics display.
     * 
     * @return int Total operation count
     */
    public function total_operation_count() {
        if (isset($this->parser_result['ops']) && is_array($this->parser_result['ops'])) {
            return count($this->parser_result['ops']);
        }
        return 0;
    }

    /**
     * Get number of operations after filtering
     * 
     * Returns the count of bank statement operations that remain visible
     * after applying all active filters (date, type, reconciliation status).
     * 
     * @return int Filtered operation count
     */
    public function filtered_operations_count() {
        return count($this->operations);
    }

    /**
     * Get number of already reconciled operations
     * 
     * Returns the count of bank statement operations that have been successfully
     * matched with GVV accounting entries in previous reconciliation sessions.
     * 
     * @return int Count of reconciled operations
     */
    public function rapproched_operations_count() {
        return $this->rapproched_operations_count;
    }

    /**
     * Get filtered statement operations as StatementOperation objects
     * 
     * Returns the array of StatementOperation objects that passed all filtering
     * criteria and are available for display and reconciliation.
     * 
     * @return StatementOperation[] Array of filtered statement operations
     */
    public function get_operations() {
        return $this->operations;
    }

    /**
     * Find statement operation by line number
     * 
     * Searches through the filtered operations to find a StatementOperation
     * with the specified line number. Used for manual reconciliation interfaces
     * where operations are identified by their position in the statement.
     * 
     * @param int $line_number Line number of the operation to find
     * @return StatementOperation|null Found operation or null if not found
     */
    public function get_operation_by_line($line_number) {
        foreach ($this->operations as $operation) {
            if ($operation->line() == $line_number) {
                return $operation;
            }
        }
        return null;
    }

    /**
     * Generate formatted header information for reconciliation display
     * 
     * Creates a structured array containing bank statement metadata, account
     * associations, date ranges, reconciliation statistics, and file information
     * for display in the reconciliation interface header.
     * 
     * @return array Multi-dimensional array of header information for display
     */
    public function header() {
        $header = [];
        $header[] = ["Banque: ",  $this->bank(), '', ''];
        $gvv_bank_acount = $this->gvv_bank_account();

        if ($gvv_bank_acount) {
            $compte_bank_gvv = anchor_compte($gvv_bank_acount);
            $header[] = ["IBAN: ",  $this->iban(), 'Compte GVV:', $compte_bank_gvv];
        } else {
            // On affiche un sélecteur
            $compte_selector = $this->CI->comptes_model->selector_with_null(['codec' => 512], TRUE);
            $attrs = 'class="form-control big_select" onchange="associateAccount(this, \''
                . $this->iban()  . '\')"';
            $compte_bank_gvv = dropdown_field(
                "compte_bank",
                "",
                $compte_selector,
                $attrs
            );
            $header[] = ["IBAN: ",  $this->iban(), 'Compte GVV:', $compte_bank_gvv];
        }

        $header[] = ["Section: ",  $this->section(), 'Fichier', $this->basename()];

        $header[] = ["Date de solde: ",  $this->date_solde(), "Solde: ", euro($this->solde())];
        $header[] = ["Date de début: ",  $this->local_start_date(), "Date de fin: ",  $this->local_end_date()];

        // $rap = $ot['count_rapproches'] . ", Choix: " . $ot['count_choices'] . ", Uniques: " . $ot['count_uniques'];
        $header[] = [
            'Nombre opérations: ',
            $this->filtered_operations_count() 
            . ' / ' . $this->total_operation_count(),

            'Rapprochées / Uniques / Choix / Combinaisons / sans suggestion:',

            $this->rapproched_operations_count() 
            . ' / ' . $this->unique_count 
            . ' / ' . $this->choices_count 
            . ' / ' . $this->multiple_count
            . ' / ' . $this->no_suggestion_count
        ];
        return $header;
    }

    /**
     * Generate HTML output for all filtered statement operations
     * 
     * Converts all filtered StatementOperation objects to their HTML representation
     * for display in the reconciliation interface. Each operation includes its
     * matching suggestions, reconciliation status, and action controls.
     * 
     * @return string Concatenated HTML for all statement operations
     */
    public function to_HTML() {
        $res = "";
        
        foreach ($this->operations as $op) {
            $res .= $op->to_HTML();
        }
        return $res;
    }

    /**
     * Get recognized bank operation types with localized labels
     * 
     * Returns an associative array mapping operation type codes to their
     * human-readable French labels. Used for operation categorization,
     * filtering, and display purposes throughout the reconciliation system.
     * 
     * @return array Associative array of operation types and their labels
     */
    function recognized_types() {
        return  [
            "cheque_debite"       => "Chèque débité",
            "frais_bancaire"      => "Frais bancaire",
            "paiement_cb"         => "Paiement en carte bancaire",
            "prelevement"         => "Prélèvement",
            "prelevement_pret"    => "Prélèvement prêt",
            "virement_emis"       => "Virement émis",
            "encaissement_cb"     => "Encaissement carte bancaire",
            "remise_cheque"       => "Remise de chèque",
            "remise_especes"      => "Remise d'espèces",
            "regularisation_frais" => "Régularisation de frais",
            "virement_recu"       => "Virement reçu",
            "inconnu"             => "Opération inconnue"
        ];
    }
}
