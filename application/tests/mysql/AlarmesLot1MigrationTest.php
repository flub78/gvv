<?php

use PHPUnit\Framework\TestCase;

/**
 * Migration 177 : tables des alarmes, rôle élève, documents obligatoires par
 * rôle, catalogue initial.
 *
 * L'aller-retour down()/up() supprime les tables : il n'est exécuté que si
 * aucune donnée d'alarme réelle n'existe (abonnements, réglages, historique,
 * élèves), pour ne jamais détruire de données.
 *
 * @see application/migrations/177_alarmes_lot1.php
 */
class AlarmesLot1MigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/177_alarmes_lot1.php';
    }

    private function up()
    {
        $migration = new Migration_Alarmes_lot1();
        $this->assertTrue($migration->up(), 'Migration 177 up() doit réussir');
    }

    private function countSql($sql)
    {
        $row = $this->db->query($sql)->row_array();
        return (int) array_shift($row);
    }

    private function eleveRoleId()
    {
        return $this->countSql("SELECT COALESCE(MAX(id), 0) FROM types_roles WHERE nom = 'eleve'");
    }

    public function testUpCreatesTablesRoleAndColumn()
    {
        $this->up();

        foreach (array('alarm_definitions', 'alarm_subscriptions', 'alarm_subscription_optouts',
                       'alarm_member_settings', 'notification_history') as $table) {
            $this->assertTrue($this->db->table_exists($table), "Table $table");
        }
        $this->assertGreaterThan(0, $this->eleveRoleId(), 'Rôle eleve');
        $this->assertTrue($this->db->field_exists('required_role_id', 'document_types'));
        $this->assertTrue($this->db->field_exists('alarm_enabled', 'document_types'));
    }

    public function testExistingDocumentTypesAllowAlarms()
    {
        $this->up();

        $this->assertEquals(0, $this->countSql("SELECT COUNT(*) FROM document_types WHERE alarm_enabled IS NULL"));
        $column = $this->db->query("SHOW COLUMNS FROM document_types LIKE 'alarm_enabled'")->row_array();
        $this->assertEquals('1', $column['Default'], 'Alarmes possibles par défaut');
    }

    public function testUpIsIdempotent()
    {
        $this->up();
        $definitions = $this->countSql("SELECT COUNT(*) FROM alarm_definitions");
        $roles = $this->countSql("SELECT COUNT(*) FROM types_roles WHERE nom = 'eleve'");

        $this->up();

        $this->assertEquals($definitions, $this->countSql("SELECT COUNT(*) FROM alarm_definitions"));
        $this->assertEquals(1, $roles);
        $this->assertEquals(1, $this->countSql("SELECT COUNT(*) FROM types_roles WHERE nom = 'eleve'"));
    }

    public function testCatalogueCoversExpiringDocumentTypes()
    {
        $subscriptions = $this->db->table_exists('alarm_subscriptions')
            ? $this->countSql("SELECT COUNT(*) FROM alarm_subscriptions") : 0;
        $this->up();

        $types = $this->db->query(
            "SELECT id, scope FROM document_types WHERE active = 1 AND has_expiration = 1 AND alarm_enabled = 1"
        )->result_array();
        foreach ($types as $type) {
            $this->assertEquals(1, $this->countSql(
                "SELECT COUNT(*) FROM alarm_definitions WHERE code = 'document_{$type['id']}'"
            ), "Une alarme pour le type {$type['id']}");
        }
        $this->assertEquals(1, $this->countSql(
            "SELECT COUNT(*) FROM alarm_definitions WHERE source = 'missing_document'"
        ), 'Une alarme pour les documents manquants');
        $medical = $this->countSql("SELECT COALESCE(MAX(id), 0) FROM document_types WHERE code = 'medical' AND scope = 'pilot'");
        if ($medical) {
            $this->assertEquals($this->eleveRoleId(), $this->countSql(
                "SELECT target_role_id FROM alarm_definitions WHERE code = 'document_{$medical}_eleve'"
            ), 'Visites médicales des élèves');
        }
        $this->assertEquals($subscriptions, $this->countSql("SELECT COUNT(*) FROM alarm_subscriptions"),
            'Aucun abonnement créé par la migration');
    }

    public function testMedicalRequiredForStudentsOnly()
    {
        $this->up();

        $medical = $this->db->query(
            "SELECT required, required_role_id FROM document_types WHERE code = 'medical' AND scope = 'pilot' LIMIT 1"
        )->row_array();
        if (!$medical) {
            $this->markTestSkipped('Pas de type de document medical');
        }
        $this->assertEquals(1, (int) $medical['required'], 'La visite médicale est obligatoire');
        $this->assertEquals($this->eleveRoleId(), (int) $medical['required_role_id'], 'Pour les élèves seulement');
    }

    public function testDownUpRoundtrip()
    {
        $this->up();

        $user_data = $this->countSql("SELECT COUNT(*) FROM alarm_subscriptions")
            + $this->countSql("SELECT COUNT(*) FROM alarm_member_settings")
            + $this->countSql("SELECT COUNT(*) FROM notification_history")
            + $this->countSql("SELECT COUNT(*) FROM user_roles_per_section WHERE types_roles_id = " . $this->eleveRoleId());
        if ($user_data > 0) {
            $this->markTestSkipped('Des données d\'alarme réelles existent : aller-retour down/up non exécuté');
        }

        $types_before = $this->db->query(
            "SELECT id, required FROM document_types ORDER BY id"
        )->result_array();
        // Sauvegarde pour restauration à l'identique : catalogue complet (y compris
        // les alarmes des lots suivants, dans la même table) et colonnes ajoutées
        // à document_types ; le rôle élève est recréé avec un autre identifiant
        $old_eleve = $this->eleveRoleId();
        $definitions = $this->db->get('alarm_definitions')->result_array();
        $type_columns = $this->db->query(
            "SELECT id, alarm_enabled, required_role_id FROM document_types ORDER BY id"
        )->result_array();

        $migration = new Migration_Alarmes_lot1();
        try {
            $this->assertTrue($migration->down(), 'Migration 177 down() doit réussir');
            $this->assertFalse($this->db->table_exists('alarm_definitions'));
            $this->assertFalse($this->db->field_exists('required_role_id', 'document_types'));
            $this->assertFalse($this->db->field_exists('alarm_enabled', 'document_types'));
            $this->assertEquals(0, $this->eleveRoleId());
        } finally {
            $this->up();
            $this->restore($definitions, $type_columns, $old_eleve);
        }

        $this->assertEquals($types_before, $this->db->query(
            "SELECT id, required FROM document_types ORDER BY id"
        )->result_array(), 'document_types identique après down/up');
        $this->assertEquals($this->remapped($type_columns, $old_eleve), $this->db->query(
            "SELECT id, alarm_enabled, required_role_id FROM document_types ORDER BY id"
        )->result_array(), 'alarm_enabled et required_role_id restaurés');
        $this->assertEquals(count($definitions), $this->countSql("SELECT COUNT(*) FROM alarm_definitions"));
    }

    /** Restaure le catalogue (par code) et les colonnes de document_types */
    private function restore($definitions, $type_columns, $old_eleve)
    {
        $new_eleve = $this->eleveRoleId();
        $map = function ($role_id) use ($old_eleve, $new_eleve) {
            return ($role_id !== null && (int) $role_id === $old_eleve) ? $new_eleve : $role_id;
        };
        foreach ($type_columns as $row) {
            $this->db->where('id', $row['id'])->update('document_types', array(
                'alarm_enabled' => $row['alarm_enabled'], 'required_role_id' => $map($row['required_role_id'])));
        }
        foreach ($definitions as $row) {
            unset($row['id']);
            $row['target_role_id'] = $map($row['target_role_id']);
            if ($this->db->where('code', $row['code'])->count_all_results('alarm_definitions')) {
                $this->db->where('code', $row['code'])->update('alarm_definitions', $row);
            } else {
                $this->db->insert('alarm_definitions', $row);
            }
        }
    }

    /** Colonnes attendues après restauration : rôle élève remappé */
    private function remapped($type_columns, $old_eleve)
    {
        $new_eleve = (string) $this->eleveRoleId();
        foreach ($type_columns as &$row) {
            if ($row['required_role_id'] !== null && (int) $row['required_role_id'] === $old_eleve) {
                $row['required_role_id'] = $new_eleve;
            }
        }
        return $type_columns;
    }
}
