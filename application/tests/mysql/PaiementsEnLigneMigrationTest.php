<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Migration 097 : tables paiements_en_ligne et paiements_en_ligne_config
 *
 * Teste :
 * - up() crée les deux tables avec les bons champs, index et contrainte FK
 * - down() supprime les tables sans erreur
 */
class PaiementsEnLigneMigrationTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/097_paiements_en_ligne.php';

        // Ensure clean state before each test
        $this->db->query("DROP TABLE IF EXISTS `paiements_en_ligne`");
        $this->db->query("DROP TABLE IF EXISTS `paiements_en_ligne_config`");
    }

    protected function tearDown(): void
    {
        // Always restore clean state
        $this->db->query("DROP TABLE IF EXISTS `paiements_en_ligne`");
        $this->db->query("DROP TABLE IF EXISTS `paiements_en_ligne_config`");
    }

    private function tableExists($table)
    {
        $t = $this->db->escape_str($table);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function indexExists($table, $index)
    {
        $t = $this->db->escape_str($table);
        $i = $this->db->escape_str($index);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$i'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function fkExists($table, $constraint)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($constraint);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'
             AND CONSTRAINT_NAME = '$c' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    // ── up() ─────────────────────────────────────────────────────────────────

    public function testUpCreatesPaiementsEnLigneTable()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $this->assertTrue($migration->up(), 'Migration up() should succeed');
        $this->assertTrue($this->tableExists('paiements_en_ligne'), 'Table paiements_en_ligne should exist');
    }

    public function testUpCreatesPaiementsEnLigneConfigTable()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();
        $this->assertTrue($this->tableExists('paiements_en_ligne_config'), 'Table paiements_en_ligne_config should exist');
    }

    public function testPaiementsEnLigneHasRequiredColumns()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();

        $required = array(
            'id', 'user_id', 'montant', 'plateforme', 'transaction_id',
            'ecriture_id', 'statut', 'date_demande', 'date_paiement',
            'metadata', 'commission', 'club',
            'created_at', 'updated_at', 'created_by', 'updated_by'
        );

        foreach ($required as $col) {
            $this->assertTrue(
                $this->columnExists('paiements_en_ligne', $col),
                "Column paiements_en_ligne.$col should exist"
            );
        }
    }

    public function testPaiementsEnLigneConfigHasRequiredColumns()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();

        $required = array(
            'id', 'plateforme', 'param_key', 'param_value', 'club',
            'created_at', 'updated_at', 'created_by', 'updated_by'
        );

        foreach ($required as $col) {
            $this->assertTrue(
                $this->columnExists('paiements_en_ligne_config', $col),
                "Column paiements_en_ligne_config.$col should exist"
            );
        }
    }

    public function testPaiementsEnLigneHasIndexes()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();

        $this->assertTrue($this->indexExists('paiements_en_ligne', 'uq_transaction_id'), 'Unique index on transaction_id should exist');
        $this->assertTrue($this->indexExists('paiements_en_ligne', 'idx_user_id'), 'Index on user_id should exist');
        $this->assertTrue($this->indexExists('paiements_en_ligne', 'idx_statut'), 'Index on statut should exist');
        $this->assertTrue($this->indexExists('paiements_en_ligne', 'idx_date_paiement'), 'Index on date_paiement should exist');
    }

    public function testPaiementsEnLigneHasForeignKeyOnEcritureId()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();

        $this->assertTrue(
            $this->fkExists('paiements_en_ligne', 'fk_pel_ecriture'),
            'Foreign key fk_pel_ecriture (ecriture_id → ecritures.id) should exist'
        );
    }

    // ── down() ───────────────────────────────────────────────────────────────

    public function testDownDropsTables()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();

        $this->assertTrue($migration->down(), 'Migration down() should succeed');
        $this->assertFalse($this->tableExists('paiements_en_ligne'), 'Table paiements_en_ligne should be dropped');
        $this->assertFalse($this->tableExists('paiements_en_ligne_config'), 'Table paiements_en_ligne_config should be dropped');
    }

    public function testUpIsIdempotent()
    {
        $migration = new Migration_Paiements_En_Ligne();
        $migration->up();
        // Second call should not fail (IF NOT EXISTS)
        $this->assertTrue($migration->up(), 'Migration up() called twice should succeed (IF NOT EXISTS)');
    }
}
