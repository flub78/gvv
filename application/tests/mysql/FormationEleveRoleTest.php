<?php

use PHPUnit\Framework\TestCase;

/**
 * Rôle élève attribué à l'ouverture d'une formation et retiré à sa clôture
 * ou à son abandon, sauf autre formation en cours dans la section.
 *
 * Toutes les données sont créées par le test et supprimées en fin de test.
 *
 * @see application/models/formation_inscription_model.php
 */
class FormationEleveRoleTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Formation_inscription_model */
    private $model;

    const PILOT = 'zzelevepil';

    private $user_id;
    private $section_id;
    private $programme_ids = array();
    private $inscription_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('formation_inscription_model');
        $this->model = $this->CI->formation_inscription_model;

        if (!$this->CI->db->where('nom', 'eleve')->count_all_results('types_roles')) {
            $this->markTestSkipped('Rôle eleve absent (migration 177)');
        }
        $this->section_id = (int) $this->CI->db->query("SELECT id FROM sections ORDER BY id LIMIT 1")->row_array()['id'];
        $this->cleanup();

        $this->CI->db->insert('membres', array('mlogin' => self::PILOT, 'mnom' => 'Test', 'mprenom' => 'Eleve', 'actif' => 1));
        $this->CI->db->insert('users', array('username' => self::PILOT, 'password' => 'x',
            'email' => self::PILOT . '@example.invalid', 'last_ip' => '127.0.0.1'));
        $this->user_id = (int) $this->CI->db->insert_id();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        foreach ($this->inscription_ids as $id) {
            $db->where('id', $id)->delete('formation_inscriptions');
        }
        foreach ($this->programme_ids as $id) {
            $db->where('id', $id)->delete('formation_programmes');
        }
        $this->inscription_ids = $this->programme_ids = array();
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id WHERE u.username = '" . self::PILOT . "'");
        $db->query("DELETE FROM users WHERE username = '" . self::PILOT . "'");
        $db->query("DELETE FROM membres WHERE mlogin = '" . self::PILOT . "'");
    }

    private function programme($section_id)
    {
        $this->CI->db->insert('formation_programmes', array(
            'code' => 'ZZELEVE' . count($this->programme_ids), 'titre' => 'Programme test élève',
            'contenu_markdown' => '# Test', 'section_id' => $section_id, 'date_creation' => date('Y-m-d H:i:s'),
        ));
        $this->programme_ids[] = (int) $this->CI->db->insert_id();
        return end($this->programme_ids);
    }

    private function open($programme_id)
    {
        $id = $this->model->ouvrir(array('pilote_id' => self::PILOT, 'programme_id' => $programme_id));
        $this->assertNotEmpty($id);
        $this->inscription_ids[] = (int) $id;
        return (int) $id;
    }

    /** Sections où le pilote détient le rôle élève */
    private function eleveSections()
    {
        $rows = $this->CI->db->query(
            "SELECT urps.section_id FROM user_roles_per_section urps
             JOIN types_roles tr ON tr.id = urps.types_roles_id
             WHERE urps.user_id = {$this->user_id} AND tr.nom = 'eleve' AND urps.revoked_at IS NULL
             ORDER BY urps.section_id"
        )->result_array();
        return array_map('intval', array_column($rows, 'section_id'));
    }

    public function test_ouverture_puis_cloture(): void
    {
        $inscription = $this->open($this->programme($this->section_id));
        $this->assertEquals(array($this->section_id), $this->eleveSections());

        $this->model->cloturer($inscription, 'cloturee');
        $this->assertEquals(array(), $this->eleveSections());
    }

    public function test_autre_formation_en_cours_garde_le_role(): void
    {
        $first = $this->open($this->programme($this->section_id));
        $second = $this->open($this->programme($this->section_id));
        $this->assertEquals(array($this->section_id), $this->eleveSections(), 'Pas de doublon');

        $this->model->suspendre($second);
        $this->model->cloturer($first, 'abandonnee');
        $this->assertEquals(array($this->section_id), $this->eleveSections(), 'Formation suspendue toujours en cours');

        $this->model->cloturer($second, 'cloturee');
        $this->assertEquals(array(), $this->eleveSections());
    }

    public function test_programme_sans_section_couvre_toutes_les_sections(): void
    {
        $all = array_map('intval', array_column($this->CI->db->query("SELECT id FROM sections ORDER BY id")->result_array(), 'id'));
        $inscription = $this->open($this->programme(null));
        $this->assertEquals($all, $this->eleveSections());

        $this->model->cloturer($inscription, 'cloturee');
        $this->assertEquals(array(), $this->eleveSections());
    }

    public function test_retrait_manuel_respecte_jusqu_au_prochain_evenement(): void
    {
        $inscription = $this->open($this->programme($this->section_id));
        $this->CI->db->where('user_id', $this->user_id)->update('user_roles_per_section',
            array('revoked_at' => date('Y-m-d H:i:s')));

        $this->model->suspendre($inscription);
        $this->model->reactiver($inscription);
        $this->assertEquals(array(), $this->eleveSections(), 'Suspension et réactivation ne redonnent pas le rôle');
    }
}
