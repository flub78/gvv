<?php

/**
 * Migration 136 — Corrections champs volsa
 *
 * Bug 2 : essence passe de INT(11) à DECIMAL(8,2)
 *         → accepte les litres avec décimales (ex : 5.50 L)
 *
 * Bug 3 : vanumvi passe de VARCHAR(20) à VARCHAR(64)
 *         → numéro de vol de découverte peut dépasser 20 caractères
 */
class Migration_Fix_essence_decimal_vanumvi_length extends CI_Migration {

    public function up() {
        $this->db->query(
            "ALTER TABLE volsa MODIFY COLUMN essence DECIMAL(8,2) NULL DEFAULT 0"
        );
        $this->db->query(
            "ALTER TABLE volsa MODIFY COLUMN vanumvi VARCHAR(64) NULL DEFAULT NULL COMMENT 'Numéro du vol d\'initiation'"
        );
    }

    public function down() {
        $this->db->query(
            "ALTER TABLE volsa MODIFY COLUMN essence INT(11) NULL DEFAULT 0"
        );
        $this->db->query(
            "ALTER TABLE volsa MODIFY COLUMN vanumvi VARCHAR(20) NULL DEFAULT NULL COMMENT 'Numéro du vol d\'initiation'"
        );
    }
}
