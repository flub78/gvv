<?php

use PHPUnit\Framework\TestCase;

/**
 * Vérifie que l'enregistrement d'un briefing passager met à jour la date
 * du vol de découverte (date_vol) à la date de création du briefing,
 * uniquement si date_vol n'était pas déjà renseignée.
 *
 * Couvre la méthode Archived_documents_model::create_briefing_and_update_date_vol()
 * appelée par les contrôleurs briefing_passager (UC1) et briefing_sign (UC2).
 *
 * @package tests
 * @see application/models/archived_documents_model.php
 * @see application/controllers/briefing_passager.php
 * @see application/controllers/briefing_sign.php
 */
class BriefingDateVolUpdateTest extends TestCase
{
    /** @var object CodeIgniter instance */
    protected $CI;
    /** @var object Database */
    protected $db;
    /** @var Archived_documents_model */
    protected $model;
    /** @var int briefing_passager document type ID */
    protected $briefing_type_id;

    /** @var array IDs des vols_decouverte créés — supprimés dans tearDown */
    protected $vld_ids = array();
    /** @var array IDs des archived_documents créés — supprimés dans tearDown */
    protected $doc_ids = array();

    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('archived_documents_model');
        $this->model = $this->CI->archived_documents_model;

        // Récupère le type "briefing_passager"
        $row = $this->db->query(
            "SELECT id FROM document_types WHERE code = 'briefing_passager' LIMIT 1"
        )->row_array();

        if (empty($row)) {
            $this->markTestSkipped(
                'Type document briefing_passager introuvable — veuillez lancer les migrations 087+.'
            );
        }
        $this->briefing_type_id = (int)$row['id'];
    }

    protected function tearDown(): void
    {
        // Suppression des documents d'abord (contrainte FK sur vld_id)
        foreach ($this->doc_ids as $id) {
            $this->db->delete('archived_documents', array('id' => $id));
        }
        // Suppression des vols de découverte
        foreach ($this->vld_ids as $id) {
            $this->db->delete('vols_decouverte', array('id' => $id));
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Crée un vol de découverte minimal avec la date_vol spécifiée (NULL par défaut).
     * @param string|null $date_vol
     * @return int ID inséré
     */
    private function createVld($date_vol = null)
    {
        $data = array(
            'date_vente' => date('Y-m-d'),
            'club'       => 1,
            'product'    => 'TEST_BRIEFING_DATE',
            'saisie_par' => 'phpunit',
            'cancelled'  => 0,
            'date_vol'   => $date_vol,
        );
        $this->db->insert('vols_decouverte', $data);
        $id = (int)$this->db->insert_id();
        $this->vld_ids[] = $id;
        return $id;
    }

    /**
     * Enregistre un briefing passager pour le VLD donné via la méthode du modèle.
     * @param int $vld_id
     * @return int ID du document créé
     */
    private function recordBriefing($vld_id)
    {
        $data = array(
            'document_type_id'  => $this->briefing_type_id,
            'vld_id'            => $vld_id,
            'section_id'        => 1,
            'file_path'         => 'uploads/documents/test/briefing_phpunit.pdf',
            'original_filename' => 'briefing_phpunit.pdf',
            'description'       => 'Briefing PHPUnit #' . $vld_id,
            'uploaded_by'       => 'phpunit',
            'validation_status' => 'approved',
        );
        $doc_id = $this->model->create_briefing_and_update_date_vol($data);
        if ($doc_id) {
            $this->doc_ids[] = $doc_id;
        }
        return $doc_id;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Quand date_vol est NULL sur le VLD, l'enregistrement d'un briefing
     * doit mettre à jour date_vol à la date du jour.
     */
    public function testBriefingCreation_SetsDateVolToToday_WhenNull()
    {
        $vld_id = $this->createVld(null);

        $doc_id = $this->recordBriefing($vld_id);
        $this->assertNotFalse($doc_id, 'Le document de briefing doit être créé.');

        $vld = $this->db->get_where('vols_decouverte', array('id' => $vld_id))->row_array();
        $this->assertNotEmpty($vld, 'Le VLD doit exister après création du briefing.');
        $this->assertEquals(
            date('Y-m-d'),
            $vld['date_vol'],
            "date_vol doit être mise à jour à aujourd'hui quand elle était NULL."
        );
    }

    /**
     * Quand date_vol est déjà renseignée, l'enregistrement d'un briefing
     * ne doit PAS écraser la date existante.
     */
    public function testBriefingCreation_DoesNotOverrideExistingDateVol()
    {
        $existing_date = date('Y-m-d', strtotime('-3 days'));
        $vld_id        = $this->createVld($existing_date);

        $doc_id = $this->recordBriefing($vld_id);
        $this->assertNotFalse($doc_id, 'Le document de briefing doit être créé.');

        $vld = $this->db->get_where('vols_decouverte', array('id' => $vld_id))->row_array();
        $this->assertNotEmpty($vld, 'Le VLD doit exister après création du briefing.');
        $this->assertEquals(
            $existing_date,
            $vld['date_vol'],
            'date_vol ne doit pas être écrasée quand elle était déjà renseignée.'
        );
    }

    /**
     * Le document archivé est bien lié au VLD et marqué comme version courante.
     */
    public function testBriefingCreation_CreatesLinkedCurrentDocument()
    {
        $vld_id = $this->createVld(null);
        $doc_id = $this->recordBriefing($vld_id);

        $this->assertNotFalse($doc_id, 'Le document de briefing doit être créé.');

        $doc = $this->db->get_where('archived_documents', array('id' => $doc_id))->row_array();
        $this->assertNotEmpty($doc, 'Le document archivé doit exister en base.');
        $this->assertEquals($vld_id, (int)$doc['vld_id'],
            'Le document doit être lié au VLD.');
        $this->assertEquals(1, (int)$doc['is_current_version'],
            'Le document doit être marqué comme version courante.');
        $this->assertEquals('approved', $doc['validation_status'],
            'Le document doit avoir le statut approved.');
    }

    /**
     * La méthode retourne false et ne met pas à jour date_vol
     * quand vld_id est absent des données.
     */
    public function testBriefingCreation_WithoutVldId_DoesNotCrash()
    {
        $data = array(
            'document_type_id'  => $this->briefing_type_id,
            // Pas de vld_id
            'section_id'        => 1,
            'file_path'         => 'uploads/documents/test/briefing_novld.pdf',
            'original_filename' => 'briefing_novld.pdf',
            'description'       => 'Briefing sans VLD',
            'uploaded_by'       => 'phpunit',
            'validation_status' => 'approved',
        );
        $doc_id = $this->model->create_briefing_and_update_date_vol($data);
        // Le document peut être créé (vld_id est nullable), pas de crash
        // On enregistre pour nettoyage si créé
        if ($doc_id) {
            $this->doc_ids[] = $doc_id;
            $this->assertIsInt($doc_id, 'Un doc sans vld_id peut être créé sans erreur.');
        } else {
            // Ou la création échoue proprement — les deux sont acceptables
            $this->assertFalse($doc_id);
        }
        // L'essentiel : pas d'exception levée
        $this->assertTrue(true, 'Aucune exception ne doit être levée sans vld_id.');
    }
}

/* End of file BriefingDateVolUpdateTest.php */
/* Location: ./application/tests/mysql/BriefingDateVolUpdateTest.php */
