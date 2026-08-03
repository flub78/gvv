<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Migration 099 : colonne is_cotisation sur la table tarifs
 *
 * Teste :
 * - up() ajoute la colonne is_cotisation avec DEFAULT 0
 * - down() supprime la colonne
 *
 * Migration 099 elle-même reste inchangée (historique) mais son up() fait
 * `ADD COLUMN is_cotisation ... AFTER type_ticket` : depuis le refactoring
 * produits/tarifs (migration 149, doc/plans/refactoring_produits_tarifs_plan.md),
 * `type_ticket` (comme `is_cotisation`) a été déplacée sur `produits` et
 * n'existe plus sur `tarifs` — up() échouerait en SQL ("Unknown column
 * type_ticket"). Toute la classe est sautée sur le schéma actuel plutôt que
 * cassée.
 */
class TarifsIsCotisationMigrationTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/099_tarifs_is_cotisation.php';

        if (!$this->columnExists('tarifs', 'type_ticket')) {
            $this->markTestSkipped('type_ticket déplacée sur produits par le refactoring produits/tarifs (migration 149) — migration 099 non rejouable telle quelle sur ce schéma');
        }

        // État propre avant le test : supprimer la colonne si elle existe
        if ($this->columnExists('tarifs', 'is_cotisation')) {
            $this->db->query("ALTER TABLE `tarifs` DROP COLUMN `is_cotisation`");
        }
    }

    protected function tearDown(): void
    {
        // Rien à restaurer sur le schéma actuel (type_ticket absente de tarifs,
        // cf. skip en setUp()) — AFTER `type_ticket` échouerait aussi ici.
        if (!$this->columnExists('tarifs', 'type_ticket')) {
            return;
        }

        // Restaurer l'état : remettre la colonne si elle a été supprimée par down()
        if (!$this->columnExists('tarifs', 'is_cotisation')) {
            $this->db->query(
                "ALTER TABLE `tarifs` ADD COLUMN `is_cotisation` TINYINT(1) NOT NULL DEFAULT 0
                 COMMENT 'Produit de cotisation' AFTER `type_ticket`"
            );
        }
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

    public function testUpAddsIsCotisationColumn()
    {
        $this->assertFalse($this->columnExists('tarifs', 'is_cotisation'),
            'La colonne ne doit pas exister avant up()');

        $migration = new Migration_Tarifs_Is_Cotisation();
        $result = $migration->up();

        $this->assertTrue($result, 'up() doit retourner true');
        $this->assertTrue($this->columnExists('tarifs', 'is_cotisation'),
            'La colonne is_cotisation doit exister après up()');

        // Vérifier la valeur par défaut
        $col = $this->db->query(
            "SELECT COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tarifs' AND COLUMN_NAME = 'is_cotisation'"
        )->row_array();
        $this->assertEquals('0', $col['COLUMN_DEFAULT'], 'DEFAULT doit être 0');
        $this->assertEquals('tinyint', $col['DATA_TYPE']);
    }

    public function testDownRemovesIsCotisationColumn()
    {
        // Appliquer up() pour avoir la colonne
        $migration = new Migration_Tarifs_Is_Cotisation();
        $migration->up();
        $this->assertTrue($this->columnExists('tarifs', 'is_cotisation'));

        $result = $migration->down();

        $this->assertTrue($result, 'down() doit retourner true');
        $this->assertFalse($this->columnExists('tarifs', 'is_cotisation'),
            'La colonne is_cotisation doit être supprimée après down()');
    }
}
