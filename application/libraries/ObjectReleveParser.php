<?php

/**
 * Classe pour parser le fichier CSV des relevés bancaires
 * 
 * Parsing des relevés bancaires au format CSV, extraction des opérations
 * 
 */
class ObjectReleveParser {
    private $data = [];


    /**
     * Checks if a string or at least one element of an array of strings is found within another string
     * 
     * @param string|array $pattern The string or array of strings to search for
     * @param string $string The string to search in
     * @return bool Returns true if pattern(s) found, false otherwise
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
     * Attempt to determine the operation type. 
     * Warning: this function may rely a lot on the bank conventions
     * and so be fragile if the bank does not stick to the format.
     */
    function operation_type($operation) {

        if ($this->found_in(['FACTURES CARTES PAYEES'], $operation["Libellé interbancaire"])) {
            return 'paiement_cb';
        } elseif ($this->found_in(['FACTURES CARTES REMISES'], $operation["Libellé interbancaire"])) {
            return 'encaissement_cb';
        } elseif ($this->found_in(['VERSEMENTS ESPECES'], $operation["Libellé interbancaire"])) {
            return 'remise_especes';
        } elseif ($this->found_in(['CHEQUES PAYES'], $operation["Libellé interbancaire"])) {
            return 'cheque_debite';
        } elseif ($this->found_in(['COMMISSIONS ET FRAIS DIVERS'], $operation["Libellé interbancaire"])) {
            return 'frais_bancaire';
        } elseif ($this->found_in('REMISES DE CHEQUES', $operation["Libellé interbancaire"])) {
            return 'remise_cheque';
        } elseif ($this->found_in('ANNULATIONS ET REGULARISATIONS', $operation["Libellé interbancaire"])) {
            return 'regularisation_frais';
        } elseif ($this->found_in('PRELEVEMENTS EUROPEENS EMIS', $operation["Libellé interbancaire"])) {
            return 'prelevement';
        } elseif ($this->found_in('AUTRES VIREMENTS RECUS', $operation["Libellé interbancaire"])) {
            return 'virement_recu';
        }

        if ($this->found_in(['VIR INST RE'], $operation["Nature de l'opération"])) {
            return 'virement_recu';
        } elseif ($this->found_in(['VIR EUROPEEN EMIS', 'VIR INSTANTANE EMIS'], $operation["Nature de l'opération"])) {
            return 'virement_emis';
        } elseif ($this->found_in('FACTURATION PROGELIANCE', $operation["Nature de l'opération"])) {
            return 'frais_bancaire';
        } elseif ($this->found_in('ECHEANCE PRET', $operation["Nature de l'opération"])) {
            return 'prelevement_pret';
        } else {
            return 'inconnu';
        }
    }

    /**
     * Adds an operation to the data array
     *
     * @param array &$data Reference to the data array that will store the operation
     * @param array $operation Operation details to be added
     * @return void
     */
    function add_operation(&$data, $operation) {
        if (!isset($data['operations'])) {
            $data['operations'] = [];
        }
        $type = $this->operation_type($operation);
        if ($type) {
            if ($type == "inconnu") {
                echo '<pre>' . print_r($operation, true) . '</pre><br>';
            }
            $operation['type'] = $type;
        }
        $data['operations'][] = $operation;
    }

    /**
     * Parse le fichier CSV du relevé
     * 
     * @param string $filePath Chemin vers le fichier CSV
     * @return array Structure de données parsée
     */
    public function parse($filePath) {
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
                continue;
            }

            if ($lineNumber === 3) {
                // Skip the third line, which is a header
                continue;
            }

            if ($lineNumber === 4) {
                if ($fields[0] != 'Solde au') {
                    throw new Exception("Le format du fichier CSV est incorrect à la ligne $lineNumber." . $line);
                }
                $data['date_solde'] = $fields[1];
                continue;
            }

            if ($lineNumber === 5) {
                if ($fields[0] != 'Solde') {
                    throw new Exception("Le format du fichier CSV est incorrect à la ligne $lineNumber." . $line);
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
        $data['start_date'] = $data['operations'][0]['Date'] ?? '';
        $data['end_date'] = end($data['operations'])['Date'] ?? '';
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
     * Retourne les données parsées sous forme de JSON
     */
    public function toJson() {
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
