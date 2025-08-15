<?php

/**
 *   Eléments for managing bank statements
 */
class ReleveOperation {
    public $bank;
    public $iban;
    public $section;
    public $date_solde;
    public $solde;
    public $start_date;
    public $end_date;
    public $titles;
    public $operations = [];

    public function __construct($data) {
        // Fill fields from $data array (returned by parser)
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    // Add helper methods as needed
    public function getOperations() {
        return $this->operations;
    }
}