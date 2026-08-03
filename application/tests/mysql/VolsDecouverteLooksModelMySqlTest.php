<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Vols_decouverte_looks_model and
 * Vols_decouverte_look_sections_model (Lot 1 —
 * doc/plans/configuration_bons_vols_decouverte_plan.md).
 *
 * @see application/models/vols_decouverte_looks_model.php
 * @see application/models/vols_decouverte_look_sections_model.php
 */
class VolsDecouverteLooksModelMySqlTest extends TestCase
{
    private $CI;
    private $looks;
    private $look_sections;

    /** @var array Ids créés par les tests, nettoyés dans tearDown() */
    private $createdLookIds = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('vols_decouverte_looks_model');
        $this->CI->load->model('vols_decouverte_look_sections_model');
        $this->looks = $this->CI->vols_decouverte_looks_model;
        $this->look_sections = $this->CI->vols_decouverte_look_sections_model;
    }

    protected function tearDown(): void
    {
        $this->CI->db->query("DELETE FROM vols_decouverte_look_sections WHERE section_id = 1");
        foreach ($this->createdLookIds as $id) {
            $this->CI->db->delete('vols_decouverte_looks', array('id' => $id));
        }
        $this->createdLookIds = array();
    }

    private function sampleLayout()
    {
        return array(
            'version' => 1,
            'recto' => array(
                'variable_fields' => array(),
                'static_fields' => array(),
                'qr_field' => array('enabled' => true, 'x' => 175, 'y' => 5, 'size' => 30),
            ),
            'verso' => array(
                'variable_fields' => array(
                    array('id' => 'numero', 'enabled' => true, 'x' => 5, 'y' => 5, 'font' => 'helvetica', 'bold' => true, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 60),
                ),
                'static_fields' => array(),
                'qr_field' => null,
            ),
        );
    }

    public function testModelInstantiation()
    {
        $this->assertEquals('vols_decouverte_looks', $this->looks->table);
        $this->assertEquals('vols_decouverte_look_sections', $this->look_sections->table);
    }

    public function testSaveLookCreateThenUpdateRoundtrip()
    {
        $layout = $this->sampleLayout();
        $id = $this->looks->save_look(null, 'Look de test', $layout, '/tmp/recto.png', '/tmp/verso.png');
        $this->assertNotFalse($id, 'save_look() should create a new look and return its id');
        $this->createdLookIds[] = $id;

        $row = $this->looks->get_by_id('id', $id);
        $this->assertEquals('Look de test', $row['nom']);
        $this->assertEquals($layout, json_decode($row['layout_json'], true));
        $this->assertNotEmpty($row['created_at'], 'created_at should be auto-injected');
        $this->assertNotEmpty($row['created_by'], 'created_by should be auto-injected');

        $layout['verso']['variable_fields'][0]['x'] = 42;
        $this->looks->save_look($id, 'Look de test modifié', $layout, '/tmp/recto.png', '/tmp/verso.png');

        $updated = $this->looks->get_by_id('id', $id);
        $this->assertEquals('Look de test modifié', $updated['nom']);
        $this->assertEquals(42, json_decode($updated['layout_json'], true)['verso']['variable_fields'][0]['x']);
        $this->assertEquals($row['created_at'], $updated['created_at'], 'created_at must not change on update');
    }

    public function testGetLayoutDecodesJson()
    {
        $layout = $this->sampleLayout();
        $id = $this->looks->save_look(null, 'Look décodage', $layout);
        $this->createdLookIds[] = $id;

        $row = $this->looks->get_by_id('id', $id);
        $this->assertEquals($layout, $this->looks->get_layout($row));
    }

    public function testGetDefaultLookReturnsEmbeddedDefaultWhenNoneConfigured()
    {
        // Pas de garde-fou requis : environnement de test sans look is_default=1.
        $default = $this->looks->get_default_look();

        $this->assertNull($default['id'], 'Embedded default look must not be a persisted row');
        $decoded = json_decode($default['layout_json'], true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('recto', $decoded);
        $this->assertArrayHasKey('verso', $decoded);
    }

    public function testSetDefaultMarksExactlyOneLookAsDefault()
    {
        $idA = $this->looks->save_look(null, 'Look A', $this->sampleLayout());
        $idB = $this->looks->save_look(null, 'Look B', $this->sampleLayout());
        $this->createdLookIds[] = $idA;
        $this->createdLookIds[] = $idB;

        $this->looks->set_default($idA);
        $this->assertEquals(1, (int) $this->looks->get_by_id('id', $idA)['is_default']);
        $this->assertEquals(0, (int) $this->looks->get_by_id('id', $idB)['is_default']);

        $this->looks->set_default($idB);
        $this->assertEquals(0, (int) $this->looks->get_by_id('id', $idA)['is_default'], 'Only one look must be default at a time');
        $this->assertEquals(1, (int) $this->looks->get_by_id('id', $idB)['is_default']);

        $default = $this->looks->get_default_look();
        $this->assertEquals($idB, $default['id']);
    }

    public function testGetLookForSectionFallsBackToDefaultWhenNoAssociation()
    {
        $idA = $this->looks->save_look(null, 'Look A', $this->sampleLayout());
        $this->createdLookIds[] = $idA;
        $this->looks->set_default($idA);

        $resolved = $this->looks->get_look_for_section(1);
        $this->assertEquals($idA, $resolved['id']);
    }

    public function testGetLookForSectionUsesExplicitAssociationOverDefault()
    {
        $idDefault = $this->looks->save_look(null, 'Look par défaut', $this->sampleLayout());
        $idSpecific = $this->looks->save_look(null, 'Look section 1', $this->sampleLayout());
        $this->createdLookIds[] = $idDefault;
        $this->createdLookIds[] = $idSpecific;
        $this->looks->set_default($idDefault);

        $this->look_sections->assign(1, $idSpecific);

        $resolved = $this->looks->get_look_for_section(1);
        $this->assertEquals($idSpecific, $resolved['id'], 'An explicit section association must win over the default look');
    }

    public function testAssignIsUpsertNotDuplicateInsert()
    {
        $idA = $this->looks->save_look(null, 'Look A', $this->sampleLayout());
        $idB = $this->looks->save_look(null, 'Look B', $this->sampleLayout());
        $this->createdLookIds[] = $idA;
        $this->createdLookIds[] = $idB;

        $this->look_sections->assign(1, $idA);
        $this->look_sections->assign(1, $idB);

        $this->assertEquals($idB, $this->look_sections->get_look_id_for_section(1));

        $count = $this->CI->db->query(
            "SELECT COUNT(*) as n FROM vols_decouverte_look_sections WHERE section_id = 1"
        )->row_array();
        $this->assertEquals(1, (int) $count['n'], 'assign() must upsert, never leave two rows for the same section');
    }

    public function testClearRemovesAssociation()
    {
        $idA = $this->looks->save_look(null, 'Look A', $this->sampleLayout());
        $this->createdLookIds[] = $idA;
        $this->look_sections->assign(1, $idA);

        $this->look_sections->clear(1);

        $this->assertNull($this->look_sections->get_look_id_for_section(1));
    }
}
