<?php

/**
 *   Eléments for managing bank statements
 * 
 *   These objects are the abstract layer of bank statements. They can be created
 *   by parser recognizing different formats but they have no specificities.
 */
class ReleveOperation {
    public $date;
    public $nature;
    public $debit;
    public $credit;
    public $currency;
    public $value_date;
    public $interbank_label;
    public $comments = [];
    public $line;
    public $type;

    public $rapproches = [];

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
    public function montant() {
        if (!empty($this->debit)) {
            return $this->debit;
        } elseif (!empty($this->credit)) {
            return $this->credit;
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
        $CI =& get_instance();
        $CI->load->model('associations_ecriture_model');
        if (empty($this->rapproches)) {
            $this->rapproches = $CI->associations_ecriture_model->get_by_string_releve($this->str_releve());
        }
        return $this->rapproches;
    }

    /**
     * Associate this operation with a gvv operation
     */
    public function associate() {
        // $this->rapproches = $this->associations_ecriture_model->get_by_string_releve($this->str_releve());
        // gvv_dump($this);
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
