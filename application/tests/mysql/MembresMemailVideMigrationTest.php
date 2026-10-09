<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL test for migration 176 : les emails vides ('') des membres sont remis à NULL,
 * ce qui libère l'index unique idx_membres_memail.
 *
 * Les membres qui avaient déjà '' avant le test sont restaurés à l'identique,
 * le membre de test est supprimé, y compris en cas d'échec.
 *
 * Les écritures passent par query() : insert()/update() du wrapper de test
 * convertissent '' en NULL, ce qui rendrait le test inopérant.
 */
class MembresMemailVideMigrationTest extends TestCase
{
    private $db;
    private $test_login;
    private $logins_vides = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/176_membres_memail_vide_null.php';

        foreach ($this->db->query("SELECT mlogin FROM membres WHERE memail = ''")->result_array() as $row) {
            $this->logins_vides[] = $row['mlogin'];
        }
        $this->test_login = 'tst_m176_' . substr(uniqid(), -8);
    }

    protected function tearDown(): void
    {
        $this->db->query("DELETE FROM membres WHERE mlogin = " . $this->db->escape($this->test_login));
        // Un seul '' est possible à cause de l'index unique : c'est l'état d'origine
        foreach ($this->logins_vides as $login) {
            $this->db->query("UPDATE membres SET memail = '' WHERE mlogin = " . $this->db->escape($login));
        }
    }

    public function testUpConvertsEmptyEmailsToNull()
    {
        // Libère la valeur '' pour pouvoir créer le membre de test avec un email vide
        $this->db->query("UPDATE membres SET memail = NULL WHERE memail = ''");
        $this->db->query("INSERT INTO membres (mlogin, mnom, mprenom, memail) VALUES ("
            . $this->db->escape($this->test_login) . ", 'Test', 'M176', '')");
        $before = $this->db->query("SELECT memail FROM membres WHERE mlogin = "
            . $this->db->escape($this->test_login))->row_array();
        $this->assertSame('', $before['memail'], 'Le membre de test doit partir avec un email vide');

        $migration = new Migration_Membres_memail_vide_null();
        $this->assertTrue($migration->up(), 'Migration 176 up() should succeed');

        $row = $this->db->query("SELECT memail FROM membres WHERE mlogin = "
            . $this->db->escape($this->test_login))->row_array();
        $this->assertNull($row['memail']);
        $count = $this->db->query("SELECT COUNT(*) AS cnt FROM membres WHERE memail = ''")->row_array();
        $this->assertSame(0, (int) $count['cnt']);
    }

    public function testDownIsNoOp()
    {
        $migration = new Migration_Membres_memail_vide_null();
        $this->assertTrue($migration->down());
    }
}
