<?php

use PHPUnit\Framework\TestCase;

/**
 * Vérifie que parse_duplicate_entry() reconnaît le message réel du serveur
 * (MySQL ou MariaDB) pour une violation de l'index unique idx_membres_memail.
 *
 * Les deux membres de test sont supprimés, y compris en cas d'échec.
 */
class DuplicateEntryMessageMySqlTest extends TestCase
{
    private $db;
    private $logins = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        require_once APPPATH . 'helpers/validation_helper.php';

        $suffix = substr(uniqid(), -8);
        $this->logins = array('tst_dup_' . $suffix . 'a', 'tst_dup_' . $suffix . 'b');
    }

    protected function tearDown(): void
    {
        foreach ($this->logins as $login) {
            $this->db->query("DELETE FROM membres WHERE mlogin = " . $this->db->escape($login));
        }
    }

    public function testServerDuplicateMessageIsParsed()
    {
        $email = $this->logins[0] . '@example.com';
        $message = null;
        foreach ($this->logins as $login) {
            try {
                $this->db->query("INSERT INTO membres (mlogin, mnom, mprenom, memail) VALUES ("
                    . $this->db->escape($login) . ", 'Test', 'Dup', " . $this->db->escape($email) . ")");
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        }

        $this->assertNotNull($message, 'Le second INSERT doit violer idx_membres_memail');
        $this->assertSame(1062, $this->db->_error_number());
        $this->assertEquals(
            array('value' => $email, 'key' => 'idx_membres_memail'),
            parse_duplicate_entry($this->db->_error_message())
        );
    }
}
