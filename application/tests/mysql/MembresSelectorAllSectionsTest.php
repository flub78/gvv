<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for the "all sections" member/instructor selectors used by the
 * forms generation page (Forms_admin::generate()) when no section is active
 * ("Toutes").
 *
 * Before this, Forms_admin::generate() called get_selector(0)/inst_selector(0),
 * which fall back to the (non-existent) active section and return an empty
 * selector — making it impossible to target a member when "Toutes" is
 * selected, e.g. to generate the same training attestation for members of
 * different sections.
 *
 * Relies on the Gaulois test users (bin/create_test_users.sh):
 *   - asterix        : 411 + user role in sections 1, 4
 *   - obelix         : 411 + user role in sections 1, 2, 4
 *   - abraracourcix  : 411 + user role in sections 1..4, instructeur in section 3 only
 * The tests are read-only (no fixtures created) and leave the DB untouched.
 */
class MembresSelectorAllSectionsTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Membres_model */
    private $model;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('membres_model');
        $this->model = $this->CI->membres_model;

        $required = array('asterix', 'obelix', 'abraracourcix');
        $found = $this->CI->db->select('mlogin')
            ->where_in('mlogin', $required)
            ->get('membres')->result_array();
        if (count($found) < count($required)) {
            $this->markTestSkipped(
                'Gaulois test users missing — run bin/create_test_users.sh'
            );
        }
    }

    public function test_get_selector_all_spans_every_section()
    {
        $selector = $this->model->get_selector_all();

        $this->assertArrayHasKey('asterix', $selector, 'membre section 1/4');
        $this->assertArrayHasKey('obelix', $selector, 'membre section 1/2/4');
        $this->assertArrayHasKey('abraracourcix', $selector, 'membre section 1..4');
    }

    public function test_get_selector_all_is_a_superset_of_a_single_section()
    {
        $all = $this->model->get_selector_all();
        $ulm = $this->model->get_selector(2); // section ULM

        foreach (array_keys($ulm) as $login) {
            $this->assertArrayHasKey(
                $login, $all,
                "get_selector_all() doit contenir tout membre d'une section précise ($login)"
            );
        }

        // asterix n'a ni compte 411 ni rôle user en section 2 : présent dans
        // "toutes sections", absent du sélecteur restreint à la section 2.
        $this->assertArrayHasKey('asterix', $all);
        $this->assertArrayNotHasKey('asterix', $ulm);
    }

    public function test_inst_selector_all_spans_every_section()
    {
        $all = $this->model->inst_selector_all();
        $planeur = $this->model->inst_selector(1); // section Planeur

        // abraracourcix est instructeur en section 3 uniquement.
        $this->assertArrayHasKey('abraracourcix', $all, 'instructeur toutes sections');
        $this->assertArrayNotHasKey(
            'abraracourcix', $planeur,
            'instructeur section 3 ne doit pas apparaître dans le sélecteur de la section 1'
        );
    }

    public function test_inst_selector_all_excludes_non_instructors()
    {
        $all = $this->model->inst_selector_all();
        $this->assertArrayNotHasKey('asterix', $all, 'asterix (rôle user seul) n\'est pas instructeur');
    }
}
