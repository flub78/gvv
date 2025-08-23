<?php

/**
 *   Eléments for managing bank statements
 * 
 *   These objects are the abstract layer of bank statements. They can be created
 *   by parser recognizing different formats but they have no specificities.
 */
class ReleveOperation {
    public $date;              // Date of the operation in database format (YYYY-MM-DD)
    public $nature;            // Nature of the operation
    public $debit;             // Debit amount
    public $credit;            // Credit amount
    public $currency;          // Currency of the operation (e.g., EUR, USD)
    public $value_date;        // Value date of the operation
    public $interbank_label;   // libellé interbancaire
    public $comments = [];     // Array of comments associated with the operation
    public $line;              // Line number in the original statement (if applicable)
    public $type;              // Type of operation (e.g., cheque_debite, frais_bancaire, paiement_cb, etc.)

    public $unique_id;         // Unique identifier for the operation
    public $unique_image;      // Unique image associated with the operation
    public $selector;          // Selector of potentially matching gvv operations
    // todo: for the moment it is an html select, replace by a hash
    public $selector_count;    // Count of selectors associated with the operation
    public $rapproches = [];
    public $gvv_matches = []; // Matches found in the gvv accounting lines
    // $gvv_matches is an array of hashes with
    // [   'id' => 123,
    //     'image' => '35233: 04/02/2025 100.00€ DELAIRE OLIVIER - Virement - RE 553499071737'
    // ]

    public function __construct($operation = null) {
        // Fill fields from $data array (returned by parser) if provided
        if ($operation !== null) {
            foreach ($operation as $key => $value) {
                if ($key === 'Date') {
                    $this->set_local_date($value);
                } elseif ($key === "Nature de l'opération") {
                    $this->nature = $value;
                } elseif ($key === 'Débit') {
                    $this->debit = $value;
                } elseif ($key === 'Crédit') {
                    $this->credit = $value;
                } elseif ($key === 'Devise') {
                    $this->currency = $value;
                } elseif ($key === 'Date de valeur') {
                    $this->value_date = date_ht2db($value);
                } elseif ($key === 'Libellé interbancaire') {
                    $this->interbank_label = $value;
                } elseif ($key === 'comments') {
                    if (is_array($value)) {
                        $this->comments = $value;
                    }
                } elseif ($key === 'line') {
                    $this->line = $value;
                } elseif ($key === 'type') {
                    $this->type = $value;
                }
            }
            $this->associate();
        }
    }

    public function set_date($date) {
        $this->date = $date;
    }
    public function set_local_date($date) {
        $this->date = date_ht2db($date);
    }
    public function date() {
        return $this->date;
    }
    public function local_date() {
        return date_db2ht($this->date);
    }
    public function local_value_date() {
        return date_db2ht($this->value_date);
    }
    /**
     * Get the amount of the operation in php decimal
     */
    public function montant() {
        if (!empty($this->debit)) {
            $amount = $this->debit;
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        } elseif (!empty($this->credit)) {
            $amount = $this->credit;
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        }
        return null;
    }

    public function str_releve() {

        $str = "";
        $str .= $this->date() . "_";
        $str .= $this->nature . "_";
        $str .= $this->montant() . "_";
        $str .= $this->currency . "_";
        $str .= $this->value_date . "_";
        $str .= $this->interbank_label . "_";
        $str .= implode(" ", $this->comments) . "_";
        $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
        return $str;
    }

    function rapproches() {
        $CI = &get_instance();
        $CI->load->model('associations_ecriture_model');
        if (empty($this->rapproches)) {
            $this->rapproches = $CI->associations_ecriture_model->get_by_string_releve($this->str_releve());
        }
        return $this->rapproches;
    }

    /**
     * Associate this operation with a gvv operation
     * $this->rapproches() already returns a correct answer
     */
    public function associate() {
        if ($this->rapproches()) {
            // gvv_dump($this->rapproches());
        }
        //  gvv_dump($this, false);
    }

    /**
     * This function print a string at the following format
     */
    public function print() {
        $output = "Array\n(\n";

        // Get all object properties using reflection
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $key = $property->getName();
            $value = $property->getValue($this);

            if (is_array($value)) {
                $output .= "    [$key] => Array\n    (\n";
                foreach ($value as $subKey => $subValue) {
                    $output .= "        [$subKey] => $subValue\n";
                }
                $output .= "    )\n";
            } else {
                $output .= "    [$key] => $value\n";
            }
        }
        $output .= ")\n";
        return $output;
    }

    /**
     * Calculates the correlation coefficient between the account entry and the operation
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient (0.0 to 1.0)
     */
    public function correlation($key, $ecriture) {
        // Base correlation
        $correlation = 0.5;

        // Call appropriate correlation function based on type
        switch ($this->type) {
            case 'cheque_debite':
                $correlation = $this->correlateCheque($key, $ecriture);
                break;

            case 'frais_bancaire':
                $correlation = $this->correlateFraisBancaire($key, $ecriture);
                break;

            case 'paiement_cb':
                $correlation = $this->correlatePaiementCB($key, $ecriture);
                break;

            case 'prelevement':
            case 'prelevement_pret':
                $correlation = $this->correlatePrelevement($key, $ecriture);
                break;

            case 'virement_emis':
                $correlation = $this->correlateVirementEmis($key, $ecriture);
                break;

            case 'virement_recu':
                $correlation = $this->correlateVirementRecu($key, $ecriture);
                break;

            case 'encaissement_cb':
                $correlation = $this->correlateEncaissementCB($key, $ecriture);
                break;

            case 'remise_cheque':
                $correlation = $this->correlateRemiseCheque($key, $ecriture);
                break;

            case 'remise_especes':
                $correlation = $this->correlateRemiseEspeces($key, $ecriture);
                break;

            case 'regularisation_frais':
                $correlation = $this->correlateRegularisationFrais($key, $ecriture);
                break;

            case 'inconnu':
                $correlation = $this->correlateInconnu($key, $ecriture);
                break;

            default:
                $correlation = 0.5;
                break;
        }

        return $correlation;
    }

    /**
     * Correlate check (cheque) operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateCheque($key, $ecriture) {
        $libelle = $this->interbank_label;

        // Look for check number in the interbankary label
        if (preg_match('/cheque.*?(\d+)/i', $libelle, $matches)) {
            return 0.9; // Higher if check number found
        }

        // Check if the entry description contains check-related terms
        if (stripos($ecriture, 'cheque') !== false || stripos($ecriture, 'chèque') !== false) {
            return 0.8;
        }

        return 0.7;
    }

    /**
     * Correlate bank fees operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateFraisBancaire($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor
        $libelle = $this->interbank_label; // Assuming libelle is set in the constructor

        // High correlation if "frais" found in operation nature or interbankary label
        if (stripos($nature, 'frais') !== false || stripos($libelle, 'frais') !== false) {
            return 0.8;
        }

        // Check if the entry description contains fee-related terms
        if (stripos($ecriture, 'frais') !== false || stripos($ecriture, 'commission') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Correlate credit card payment operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlatePaiementCB($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor

        // High correlation if "carte" found in operation nature
        if (stripos($nature, 'carte') !== false) {
            return 0.8;
        }

        // Check if the entry description contains card-related terms
        if (stripos($ecriture, 'carte') !== false || stripos($ecriture, 'cb') !== false) {
            return 0.7;
        }

        return 0.5;
    }

    /**
     * Correlate direct debit operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlatePrelevement($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor

        // High correlation if "prelevement" found in operation nature
        if (stripos($nature, 'prelevement') !== false || stripos($nature, 'prélèvement') !== false) {
            return 0.8;
        }

        // Check if the entry description contains direct debit related terms
        if (stripos($ecriture, 'prelevement') !== false || stripos($ecriture, 'prélèvement') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Correlate outgoing transfer operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateVirementEmis($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor

        // High correlation if "virement" found in operation nature
        if (stripos($nature, 'virement') !== false) {
            return 0.8;
        }

        // Check if the entry description contains transfer related terms
        if (stripos($ecriture, 'virement') !== false || stripos($ecriture, 'transfer') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Correlate incoming transfer operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateVirementRecu($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor
        $comments = $this->comments; // Assuming comments is set in the constructor

        // Clean up the operation nature
        $nature = strtolower($nature);
        $nature = str_replace(['vir inst re', 'vir recu'], '', $nature);
        $nature = trim($nature);

        // Convert ecriture to ASCII for better matching
        $ecriture_ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $ecriture);

        // Look for operation nature in the entry description
        if (!empty($nature) && stripos(strtolower($ecriture_ascii), $nature) !== false) {
            return 0.96; // High correlation if reference found
        }

        // Analyze the sender information from comments
        if (!empty($comments[0])) {
            $from = strtolower($comments[0]);
            $from = str_replace('.', ' ', $from);

            // Remove multiple spaces
            while (strpos($from, '  ') !== false) {
                $from = str_replace('  ', ' ', $from);
            }

            // Clean common prefixes
            $from = str_replace([
                'de: m ou mme',
                'de: mr ou mme',
                'de: mr',
                'de: monsieur',
                'de: m ',
                'de: mme',
                'ou m',
                'de:',
                'epoux'
            ], '', $from);

            $from = trim($from);
            $from_list = explode(' ', $from);
            $score = 0;
            $word_count = 0;

            foreach ($from_list as $word) {
                $word = trim($word);
                if (strlen($word) > 2) { // Only consider words longer than 2 characters
                    $word_count++;
                    if (stripos($ecriture_ascii, $word) !== false) {
                        $score++;
                    }
                }
            }

            if ($word_count > 0) {
                $match_ratio = $score / $word_count;
                return $match_ratio * 0.8;
            }
        }

        // Basic correlation for transfer operations
        if (stripos($nature, 'virement') !== false || stripos($nature, 'vir') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Correlate credit card receipt operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateEncaissementCB($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor

        // Check for card-related terms in operation nature
        if (stripos($nature, 'carte') !== false || stripos($nature, 'cb') !== false) {
            return 0.8;
        }

        // Check if the entry description contains card receipt terms
        if (
            stripos($ecriture, 'encaissement') !== false &&
            (stripos($ecriture, 'carte') !== false || stripos($ecriture, 'cb') !== false)
        ) {
            return 0.8;
        }

        return 0.7;
    }

    /**
     * Correlate check deposit operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateRemiseCheque($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor
        $libelle = $this->interbank_label; // Assuming libelle is set in the constructor

        // High correlation if remise or cheque found
        if (
            stripos($nature, 'remise') !== false || stripos($libelle, 'remise') !== false ||
            stripos($nature, 'cheque') !== false || stripos($libelle, 'cheque') !== false
        ) {
            return 0.9;
        }

        // Check entry description for check deposit terms
        if (
            stripos($ecriture, 'remise') !== false &&
            (stripos($ecriture, 'cheque') !== false || stripos($ecriture, 'chèque') !== false)
        ) {
            return 0.8;
        }

        return 0.8;
    }

    /**
     * Correlate cash deposit operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateRemiseEspeces($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor
        $libelle = $this->interbank_label; // Assuming libelle is set in the constructor

        // High correlation if cash deposit terms found
        if (
            stripos($nature, 'espèces') !== false || stripos($libelle, 'espèces') !== false ||
            stripos($nature, 'remise') !== false || stripos($libelle, 'remise') !== false
        ) {
            return 0.9;
        }

        // Check entry description for cash deposit terms
        if (stripos($ecriture, 'espèces') !== false || stripos($ecriture, 'liquide') !== false) {
            return 0.8;
        }

        return 0.9;
    }

    /**
     * Correlate fee regularization operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateRegularisationFrais($key, $ecriture) {
        $nature = $this->nature; // Assuming nature is set in the constructor
        $libelle = $this->interbank_label; // Assuming libelle is set in the constructor

        // Check for regularization or fee terms
        if (
            stripos($nature, 'regularisation') !== false || stripos($libelle, 'regularisation') !== false ||
            stripos($nature, 'régularisation') !== false || stripos($libelle, 'régularisation') !== false
        ) {
            return 0.8;
        }

        // Check entry description for regularization terms
        if (stripos($ecriture, 'regularisation') !== false || stripos($ecriture, 'régularisation') !== false) {
            return 0.7;
        }

        return 0.5;
    }

    /**
     * Correlate unknown operations
     *
     * @param string $key of the gvv accounting line
     * @param mixed $ecriture image
     * @return float correlation coefficient
     */
    private function correlateInconnu($key, $ecriture) {
        // For unknown operations, we have low confidence
        // but we can still try to match based on amount and date proximity
        return 0.3;
    }

    /**
     * Retorune capital et interets pour les prets
     */
    function remboursement() {
        // Check if the operation is a reimbursement

        if ($this->type === 'prelevement_pret') {

            // Extract capital amorti and interest amounts from comments
            $capital = 0.0;
            $interets = 0.0;

            // Check if we have comments and the first one matches 'CAPITAL AMORTI'
            if (!empty($this->comments[0]) && strpos($this->comments[0], 'CAPITAL AMORTI') !== false) {
                // Extract the numeric value after ': '
                $parts = explode(': ', $this->comments[0]);
                if (count($parts) == 2) {
                    $capital = str_replace(',', '.', trim($parts[1]));
                }

                // Check for interest amount in comments
                if (!empty($this->comments[1]) && strpos($this->comments[1], 'INTERETS') !== false) {
                    $parts = explode(': ', $this->comments[1]);
                    if (count($parts) == 2) {
                        $interets = str_replace(',', '.', trim($parts[1]));
                    }

                    // Return an array with capital and interest amounts
                    return [
                        'capital' => $capital,
                        'interets' => $interets
                    ];
                }
            }
        }
        return [];
    }

    function fetch_gvv_matches($start_date, $end_date, $bank) {
        $CI = &get_instance();
        $CI->load->model('ecritures_model');


        // On utilise le modèle ecritures_model pour obtenir les écritures
        // qui correspondent à l'opération du relevé bancaire
        $delta = $CI->session->userdata('rapprochement_delta');
        if (!$delta) {
            $delta = 5; // Default delta value
        }

        // dans certains cas on cherche le montant exact, dans d'autre il pourra être découpé entre plusieurs valeurs

        $remboursement = $this->remboursement();
        if ($remboursement) {
            $list_montant = [$remboursement['capital'], $remboursement['interets']]; // For reimbursement, we use the exact amount
        } else {
            $list_montant = [$this->montant()];
        }

        if ($op->debit) {
            $compte1 = null;
            $compte2 = $bank;
        } else {
            $compte1 = $bank;
            $compte2 = null;
        }

        foreach ($list_montant as $montant) {
            $select = $CI->ecritures_model->ecriture_selector($start_date, $end_date, $montant, $compte1, $compte2, $this->date, $delta);
        }
    }
}
