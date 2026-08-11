<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL — Maintenance_access (Phase 6, Etape 6.1)
 *
 * Le mock Dx_auth du harnais de test (integration_bootstrap.php) fixe
 * user_id=1 et is_admin()=FALSE en permanence : ces tests verifient donc
 * le comportement non-admin de la bibliotheque, en attribuant/retirant
 * reellement des roles en base pour cet utilisateur dans une section de
 * test dediee (meme pattern que AuthorizationIntegrationTest). Le bypass
 * admin (dx_auth->is_admin()) est une ligne triviale deja couverte par le
 * meme mecanisme ailleurs dans la base (MY_Controller::user_has_role()).
 *
 * require_write() n'est teste que sur son chemin positif : son chemin de
 * refus appelle show_error(), qui termine le process (exit) -- non
 * testable en PHPUnit, verifie via Playwright a la place (cf.
 * playwright/tests/maintenance-roles-smoke.spec.js).
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 6.1)
 */
class MaintenanceAccessTest extends TestCase
{
    const TR_CA = 6;
    const TR_TRESORIER = 8;
    const TR_MECANO = 12;

    private $CI;
    private $auth;
    private $access;
    private $section_id;
    private $autre_section_id = null;
    private $user_id = 1; // fixe par MockDxAuth::get_user_id()
    private $created_user = false;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->library('Gvv_Authorization');
        $this->auth = $this->CI->gvv_authorization;

        $this->CI->load->library('Maintenance_access');
        $this->access = $this->CI->maintenance_access;

        // user_roles_per_section.user_id a une contrainte FK vers users.id ;
        // le mock Dx_auth des tests fixe get_user_id()=1, qui n'existe pas
        // forcement en base de test -- cree a la volee si besoin.
        $existing_user = $this->CI->db->where('id', $this->user_id)->get('users')->row_array();
        if (!$existing_user) {
            $this->CI->db->insert('users', array(
                'id' => $this->user_id, 'role_id' => 1,
                'username' => 'tst_mnt_acc_' . substr(uniqid(), -8),
                'password' => md5('test'), 'email' => 'test_maintenance_access@example.com',
                'banned' => 0, 'last_ip' => '127.0.0.1',
                'last_login' => date('Y-m-d H:i:s'), 'created' => date('Y-m-d H:i:s'),
            ));
            $this->created_user = true;
        }

        $this->CI->db->insert('sections', array('nom' => 'Test Maintenance_access ' . uniqid(), 'acronyme' => 'TMA'));
        $this->section_id = $this->CI->db->insert_id();

        $this->CI->session->set_userdata('section', $this->section_id);
    }

    protected function tearDown(): void
    {
        // Nettoyage centralise ici (plutot qu'en fin de corps de test) pour
        // rester sur, y compris quand une assertion echoue en cours de test
        // -- PHPUnit appelle toujours tearDown() apres un test en echec.
        $this->CI->db->where('user_id', $this->user_id)->delete('user_roles_per_section');
        $this->CI->db->where('id', $this->section_id)->delete('sections');
        if ($this->autre_section_id) {
            $this->CI->db->where('id', $this->autre_section_id)->delete('sections');
        }
        if ($this->created_user) {
            $this->CI->db->where('id', $this->user_id)->delete('users');
        }
        $this->auth->clear_cache($this->user_id);
        $this->CI->session->set_userdata('section', 1);
    }

    public function testAucunRoleNiEcritureNiHistoriqueMaisSynthese()
    {
        $this->assertFalse($this->access->is_mecano());
        $this->assertFalse($this->access->can_write());
        $this->assertFalse($this->access->can_view_historique());
        // Pilote (membre standard, aucun role specifique) : synthese seule (PRD EF8.4)
        $this->assertTrue($this->access->can_view_synthese());
    }

    public function testMecanoEcritureEtHistoriqueDansSaSection()
    {
        $this->auth->grant_role($this->user_id, self::TR_MECANO, $this->section_id, $this->user_id);

        $this->assertTrue($this->access->is_mecano());
        $this->assertTrue($this->access->can_write());
        $this->assertTrue($this->access->can_view_historique());
        $this->assertTrue($this->access->can_view_synthese());

        // require_write() ne bloque pas quand l'ecriture est autorisee.
        $this->access->require_write();
        $this->assertTrue(true);
    }

    public function testCaHistoriqueSeulLectureSansEcriture()
    {
        $this->auth->grant_role($this->user_id, self::TR_CA, $this->section_id, $this->user_id);

        $this->assertFalse($this->access->is_mecano());
        $this->assertFalse($this->access->can_write());
        $this->assertTrue($this->access->can_view_historique());
        $this->assertTrue($this->access->can_view_synthese());
    }

    public function testTresorierHistoriqueSeulLectureSansEcriture()
    {
        $this->auth->grant_role($this->user_id, self::TR_TRESORIER, $this->section_id, $this->user_id);

        $this->assertFalse($this->access->can_write());
        $this->assertTrue($this->access->can_view_historique());
    }

    public function testRoleScopeSurUneAutreSectionNeDonneAucunDroitIci()
    {
        $this->CI->db->insert('sections', array('nom' => 'Test Maintenance_access autre ' . uniqid(), 'acronyme' => 'TMB'));
        $this->autre_section_id = $this->CI->db->insert_id();

        // Role mecano accorde ailleurs, pas dans la section courante de session -> sans effet
        $this->auth->grant_role($this->user_id, self::TR_MECANO, $this->autre_section_id, $this->user_id);

        $this->assertFalse($this->access->is_mecano());
        $this->assertFalse($this->access->can_view_historique());
    }
}

/* End of file MaintenanceAccessTest.php */
/* Location: ./application/tests/mysql/MaintenanceAccessTest.php */
