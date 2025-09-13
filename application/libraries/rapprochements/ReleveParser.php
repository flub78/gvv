<?php

/**
 * Bank statement CSV file parser for French banking formats
 * 
 * Parses bank statement CSV files following French banking conventions and
 * extracts structured data including account information, balance details,
 * and individual operations. Handles multi-line operation entries with
 * additional comments and provides automatic operation type classification.
 * 
 * Supported CSV Structure:
 * - Line 1: Bank name
 * - Line 2: IBAN and section information  
 * - Line 3: Column headers (skipped)
 * - Line 4: Balance date information
 * - Line 5: Balance amount
 * - Line 7: Operation column titles
 * - Line 8+: Operation data with potential multi-line comments
 * 
 * Operation Type Detection:
 * - Automatically categorizes operations based on French banking labels
 * - Supports card payments, transfers, checks, fees, and other common types
 * - Falls back to 'inconnu' (unknown) for unrecognized operations
 */
class ReleveParser {

    private $CI;

    /**
     * Initialize parser with required dependencies
     * 
     * Sets up the CSV parser with necessary CodeIgniter resources and
     * loads the ReleveOperation library for creating operation objects.
     */
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library('rapprochements/ReleveOperation');
    }
    private $data = [];

    /**
     * Search for pattern(s) within a string with array support
     * 
     * Utility method that checks if a string contains a specific pattern or any
     * pattern from an array of patterns. Used extensively for operation type
     * detection based on banking terminology.
     * 
     * @param string|array $pattern Single pattern string or array of pattern strings
     * @param string $string The string to search within
     * @return bool True if any pattern is found, false otherwise
     */
    function found_in($pattern, $string) {
        if (is_array($pattern)) {
            foreach ($pattern as $p) {
                if (strpos($string, $p) !== false) {
                    return true;
                }
            }
            return false;
        }
        return strpos($string, $pattern) !== false;
    }

    /**
     * Determine operation type from bank statement data
     * 
     * Analyzes operation fields (interbank label and operation nature) to classify
     * the transaction type according to French banking conventions. Uses pattern
     * matching on standard banking terminology to categorize operations.
     * 
     * Supported Operation Types:
     * - paiement_cb: Card payments
     * - encaissement_cb: Card receivables  
     * - remise_especes: Cash deposits
     * - cheque_debite: Check payments
     * - frais_bancaire: Bank fees
     * - remise_cheque: Check deposits
     * - prelevement: Direct debits
     * - virement_recu/emis: Transfers received/sent
     * - inconnu: Unknown/unrecognized operations
     * 
     * Warning: Relies heavily on specific bank formatting conventions and may
     * require updates if bank changes their labeling system.
     * 
     * @param array $operation Operation data array with required fields
     * @return string Operation type code for categorization
     */
    function operation_type($operation) {

        if ($this->found_in(['FACTURES CARTES PAYEES', 'PAIEMENT CB'], $operation["Libellé interbancaire"])) {
            return 'paiement_cb';

        } elseif ($this->found_in(['FACTURES CARTES REMISES'], $operation["Libellé interbancaire"])) {
            return 'encaissement_cb';

        } elseif ($this->found_in(['VERSEMENTS ESPECES'], $operation["Libellé interbancaire"])) {
            return 'remise_especes';

        } elseif ($this->found_in(['CHEQUES PAYES'], $operation["Libellé interbancaire"])) {
            return 'cheque_debite';

        } elseif ($this->found_in(['COMMISSIONS ET FRAIS DIVERS'], $operation["Libellé interbancaire"])) {
            return 'frais_bancaire';

        } elseif ($this->found_in(['REMISES DE CHEQUES', 'REMISE CHEQUE'], $operation["Libellé interbancaire"])) {
            return 'remise_cheque';

        } elseif ($this->found_in('ANNULATIONS ET REGULARISATIONS', $operation["Libellé interbancaire"])) {
            return 'regularisation_frais';

        } elseif ($this->found_in('PRELEVEMENTS EUROPEENS EMIS', $operation["Libellé interbancaire"])) {
            return 'prelevement';

        } elseif ($this->found_in('AUTRES VIREMENTS RECUS', $operation["Libellé interbancaire"])) {
            return 'virement_recu';

        } elseif ($this->found_in('AUTRES VIREMENTS EMIS', $operation["Libellé interbancaire"])) {
            return 'virement_emis';
        }

        if ($this->found_in(['VIR INST RE', 'VIR RECU'], $operation["Nature de l'opération"])) {
            return 'virement_recu';

        } elseif ($this->found_in(['VIR EUROPEEN EMIS', 'VIR INSTANTANE EMIS', 'AUTRES VIREMENTS EMIS'], $operation["Nature de l'opération"])) {
            return 'virement_emis';

        } elseif ($this->found_in('FACTURATION PROGELIANCE', $operation["Nature de l'opération"])) {
            return 'frais_bancaire';

        } elseif ($this->found_in('PRELEVEMENT EUROPEEN', $operation["Nature de l'opération"])) {
            return 'prelevement';

        } elseif ($this->found_in(['ECHEANCE PRET'], $operation["Nature de l'opération"])) {
            return 'prelevement_pret';

        } else {
            return 'inconnu';
        }
    }

    /**
     * Add processed operation to the data structure
     * 
     * Converts raw operation data into a ReleveOperation object and adds it
     * to the operations array. Handles type detection and data normalization
     * for consistent processing by the reconciliation system.
     * 
     * @param array &$data Reference to the main data structure being built
     * @param array $operation Raw operation data from CSV parsing
     * @return void Modifies the data array by reference
     */
    function add_operation(&$data, $operation) {
        if (!isset($data['ops'])) {
            //  $data['operations'] = [];
            $data['ops'] = []; // list of ReleveOperations
        }
        $type = $this->operation_type($operation);
        if ($type) {
            if ($type == "inconnu") {
                echo "<pre> Type d'opération inconnu: " . print_r($operation, true) . "</pre><br>";
            }
            $operation['type'] = $type;
            $op = new ReleveOperation($operation);
            $data['ops'][] = $op;
        }
    }

    /**
     * Parse le fichier CSV du relevé
     * 
     * @param string $filePath Chemin vers le fichier CSV
     * @return array Structure de données parsée
     */
    /**
     * Parse bank statement CSV file and extract structured data
     * 
     * Main parsing method that processes a bank statement CSV file following
     * French banking format conventions. Extracts header information, account
     * details, balance data, and all transaction operations with their comments.
     * 
     * File Structure Expected:
     * 1. Bank name
     * 2. IBAN and section
     * 3. Headers (skipped)
     * 4. Balance date
     * 5. Balance amount
     * 6. Empty line
     * 7. Column titles
     * 8+. Operations with potential multi-line comments
     * 
     * @param string $filePath Full path to the CSV file to parse
     * @return array Structured data array with bank info, operations, and metadata
     * @throws Exception If file doesn't exist, can't be opened, or has invalid format
     */
    public function parse($filePath) {
        $CI = &get_instance();
        $CI->load->model('associations_releve_model');

        if (!file_exists($filePath)) {
            throw new Exception("Le fichier {$filePath} n'existe pas.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Impossible d'ouvrir le fichier {$filePath}.");
        }

        $data = [];
        $data['operations'] = [];
        $current_operation = null;

        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            // in the loop lines are numbered starting from 1
            $lineNumber++;
            $line = trim($line);

            // Ignorer les lignes vides
            if (empty($line)) {
                continue;
            }

            $fields = $this->parseCsvLine($line);

            if ($lineNumber === 1) {
                $data['bank'] = $fields[0];
                continue;
            }

            if ($lineNumber === 2) {
                $data['iban'] = $fields[0];
                $data['section'] = $fields[1];

                // Todo, remove whence it is done by the reconciliator
                $bank_account = $CI->associations_releve_model->get_gvv_account($data['iban']);
                if ($bank_account) {
                    $data['gvv_bank'] = $bank_account;
                }                 
                continue;
            }

            if ($lineNumber === 3) {
                // Skip the third line, which is a header
                continue;
            }

            if ($lineNumber === 4) {
                if ($fields[0] != 'Solde au') {
                    $basename = basename($filePath);
                    throw new Exception("Le format du fichier CSV \"$basename\" est incorrect à la ligne $lineNumber." . $line);
                }
                $data['date_solde'] = $fields[1];
                continue;
            }

            if ($lineNumber === 5) {
                if ($fields[0] != 'Solde') {
                    $basename = basename($filePath);
                    throw new Exception("Le format du fichier CSV \"$basename\" est incorrect à la ligne $lineNumber." . $line);
                }
                $data['solde'] = $fields[1];
                continue;
            }

            if ($lineNumber === 7) {
                $data['titles'] = $fields;
                continue;
            }

            // start to process operations lines
            $date = $fields[0];

            if ($date) {
                // check if the date is in the correct format
                $control_date = date_create_from_format('d/m/Y', $date);
                if (!$control_date) {
                    throw new Exception("La date à la ligne $lineNumber n'est pas au format 'd/m/Y': $fields[0]");
                }

                // start a new opération
                if ($current_operation) {
                    // save the previous operation
                    $this->add_operation($data, $current_operation);
                }

                $i = 0;
                foreach ($data['titles'] as $title) {
                    $current_operation[$title] = $fields[$i];
                    $i++;
                }
                $current_operation['comments'] = [];
                $current_operation['line'] = $lineNumber;
                continue;
            }

            $comment = $fields[1] ?? '';
            if ($comment) {
                // If the comment is not empty, add it to the current operation
                $current_operation['comments'][] = $comment;

                continue;
            }

            // This point should never be reached
            if (!$current_operation) {
                throw new Exception("La ligne $lineNumber ne correspond à aucune opération.");
            }
            echo "<br>$lineNumber => $line\n<br>";
            print_r($fields);
        }

        if ($current_operation) {
            // save the current operation
            $this->add_operation($data, $current_operation);
        }
        $data['start_date'] = $data['ops'][0]->date ?? '';
        $data['end_date'] = end($data['ops'])->date ?? '';
        fclose($handle);
        return $data;
    }


    /**
     * Parse une ligne CSV en tenant compte des points-virgules
     */
    private function parseCsvLine($line) {
        $fields = array_map('trim', explode(';', $line));
        // Remove quotes around fields
        return array_map(function ($field) {
            return trim($field, '"\'');
        }, $fields);
    }


    /**
     * Get recognized operation types with localized labels
     * 
     * Returns the complete mapping of operation type codes to their French
     * language labels. Used for display purposes and type validation throughout
     * the reconciliation system.
     * 
     * Todo: Consider moving this to the Reconciliator class for better separation
     * of concerns.
     * 
     * @return array Associative array mapping type codes to French labels
     */
    function recognized_types() {
        return $operations = [
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
    /**
     * Export parsed data as formatted JSON string
     * 
     * Converts the internal parsed data structure to a pretty-printed JSON
     * representation with Unicode support. Useful for debugging, data export,
     * or API responses.
     * 
     * @return string JSON representation of parsed bank statement data
     */
    public function toJson() {
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
