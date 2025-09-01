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

}
