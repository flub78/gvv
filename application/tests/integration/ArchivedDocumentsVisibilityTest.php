<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test: règles de visibilité des documents archivés
 *
 * Règles vérifiées :
 * - Membre non-CA, section active : uniquement les documents de cette section + ses docs + club
 * - Membre non-CA, pas de section active : documents de TOUTES les sections + ses docs + club
 * - Membre CA (vue admin) : filtre section_id ne doit pas exclure les documents pilotes
 */
class ArchivedDocumentsVisibilityTest extends TestCase
{
    /** @var object CI instance */
    private $CI;
    /** @var Archived_documents_model */
    private $model;

    private $section1_id;
    private $section2_id;
    private $type_section_id;
    private $type_pilot_id;
    private $type_club_id;

    // Login réel d'un membre existant (contrainte FK)
    private $pilot_login = '9992';

    protected function setUp(): void
    {
        $this->CI = &get_instance();

        // Injecter gvvmetadata si absent (requis par le modèle)
        if (!isset($this->CI->gvvmetadata)) {
            $this->CI->gvvmetadata = new MockGvvMetadata();
        }

        // Démarrer une transaction pour isoler le test
        $this->CI->db->trans_start();

        // Charger les classes nécessaires
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Archived_documents_model')) {
            require_once APPPATH . 'models/archived_documents_model.php';
        }

        $this->CI->gvv_model = new Archived_documents_model();
        $this->model = $this->CI->gvv_model;

        $this->_create_fixtures();
    }

    protected function tearDown(): void
    {
        $this->CI->db->trans_rollback();
    }

    // -------------------------------------------------------------------------
    // Données de test
    // -------------------------------------------------------------------------

    private function _create_fixtures()
    {
        $ts = time();

        // Deux sections
        $this->CI->db->insert('sections', ['nom' => 'Section A ' . $ts, 'acronyme' => 'SA']);
        $this->section1_id = $this->CI->db->insert_id();

        $this->CI->db->insert('sections', ['nom' => 'Section B ' . $ts, 'acronyme' => 'SB']);
        $this->section2_id = $this->CI->db->insert_id();

        // Types de documents
        $this->CI->db->insert('document_types', [
            'code'          => 'test_sec_' . $ts,
            'name'          => 'Doc section test',
            'scope'         => 'section',
            'required'      => 0,
            'has_expiration'=> 0,
            'storage_by_year' => 0,
            'active'        => 1,
            'display_order' => 99,
        ]);
        $this->type_section_id = $this->CI->db->insert_id();

        $this->CI->db->insert('document_types', [
            'code'          => 'test_pil_' . $ts,
            'name'          => 'Doc pilote test',
            'scope'         => 'pilot',
            'required'      => 0,
            'has_expiration'=> 0,
            'storage_by_year' => 0,
            'active'        => 1,
            'display_order' => 98,
        ]);
        $this->type_pilot_id = $this->CI->db->insert_id();

        $this->CI->db->insert('document_types', [
            'code'          => 'test_club_' . $ts,
            'name'          => 'Doc club test',
            'scope'         => 'club',
            'required'      => 0,
            'has_expiration'=> 0,
            'storage_by_year' => 0,
            'active'        => 1,
            'display_order' => 97,
        ]);
        $this->type_club_id = $this->CI->db->insert_id();

        // Document de section A
        $this->CI->db->insert('archived_documents', [
            'document_type_id'  => $this->type_section_id,
            'pilot_login'       => null,
            'section_id'        => $this->section1_id,
            'file_path'         => 'test/sec_a.pdf',
            'original_filename' => 'sec_a.pdf',
            'uploaded_by'       => 'test',
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'is_current_version'=> 1,
            'validation_status' => 'approved',
        ]);

        // Document de section B
        $this->CI->db->insert('archived_documents', [
            'document_type_id'  => $this->type_section_id,
            'pilot_login'       => null,
            'section_id'        => $this->section2_id,
            'file_path'         => 'test/sec_b.pdf',
            'original_filename' => 'sec_b.pdf',
            'uploaded_by'       => 'test',
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'is_current_version'=> 1,
            'validation_status' => 'approved',
        ]);

        // Document pilote (section_id = NULL)
        $this->CI->db->insert('archived_documents', [
            'document_type_id'  => $this->type_pilot_id,
            'pilot_login'       => $this->pilot_login,
            'section_id'        => null,
            'file_path'         => 'test/pilot.pdf',
            'original_filename' => 'pilot.pdf',
            'uploaded_by'       => 'test',
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'is_current_version'=> 1,
            'validation_status' => 'approved',
        ]);

        // Document club (section_id = NULL, pilot_login = NULL)
        $this->CI->db->insert('archived_documents', [
            'document_type_id'  => $this->type_club_id,
            'pilot_login'       => null,
            'section_id'        => null,
            'file_path'         => 'test/club.pdf',
            'original_filename' => 'club.pdf',
            'uploaded_by'       => 'test',
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'is_current_version'=> 1,
            'validation_status' => 'approved',
        ]);
    }

    // =========================================================================
    // Tests get_section_documents : section active → seulement cette section
    // =========================================================================

    public function testGetSectionDocumentsRetourneSeulementLaSectionDemandee()
    {
        $docs = $this->model->get_section_documents($this->section1_id);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths, 'Doc section A doit être présent');
        $this->assertNotContains('test/sec_b.pdf', $paths, 'Doc section B ne doit pas être présent');
        $this->assertNotContains('test/pilot.pdf', $paths, 'Doc pilote ne doit pas être présent');
    }

    public function testGetSectionDocumentsSection2RetourneSeulementSection2()
    {
        $docs = $this->model->get_section_documents($this->section2_id);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_b.pdf', $paths, 'Doc section B doit être présent');
        $this->assertNotContains('test/sec_a.pdf', $paths, 'Doc section A ne doit pas être présent');
    }

    // =========================================================================
    // Tests get_all_section_documents : pas de section active → toutes les sections
    // =========================================================================

    public function testGetAllSectionDocumentsRetourneToutesLesSections()
    {
        $docs = $this->model->get_all_section_documents();

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths, 'Doc section A doit être présent');
        $this->assertContains('test/sec_b.pdf', $paths, 'Doc section B doit être présent');
    }

    public function testGetAllSectionDocumentsExclutDocsPilotes()
    {
        $docs = $this->model->get_all_section_documents();

        $paths = array_column($docs, 'file_path');
        $this->assertNotContains('test/pilot.pdf', $paths, 'Doc pilote ne doit pas être dans les docs section');
    }

    public function testGetAllSectionDocumentsExclutDocsClub()
    {
        $docs = $this->model->get_all_section_documents();

        $paths = array_column($docs, 'file_path');
        $this->assertNotContains('test/club.pdf', $paths, 'Doc club ne doit pas être dans les docs section');
    }

    // =========================================================================
    // Tests get_filtered_documents avec filtre section :
    // Les docs pilotes ne doivent PAS être exclus (règle membre CA)
    // =========================================================================

    public function testFiltreParSectionNExcluePasLesDocsPilotes()
    {
        $docs = $this->model->get_filtered_documents(['section_id' => $this->section1_id]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/pilot.pdf', $paths,
            'Les docs pilotes doivent rester visibles même avec un filtre section actif');
    }

    public function testFiltreParSectionNExcluePasLesDocsClub()
    {
        $docs = $this->model->get_filtered_documents(['section_id' => $this->section1_id]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/club.pdf', $paths,
            'Les docs club doivent rester visibles même avec un filtre section actif');
    }

    public function testFiltreParSectionAfficheSectionDemandeeSeulement()
    {
        $docs = $this->model->get_filtered_documents(['section_id' => $this->section1_id]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths, 'Doc section A doit être présent');
        $this->assertNotContains('test/sec_b.pdf', $paths,
            'Doc section B ne doit pas apparaître quand on filtre sur section A');
    }

    public function testSansFiltreTousLesDocumentsSontVisibles()
    {
        $docs = $this->model->get_filtered_documents([]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths);
        $this->assertContains('test/sec_b.pdf', $paths);
        $this->assertContains('test/pilot.pdf', $paths);
        $this->assertContains('test/club.pdf', $paths);
    }

    // =========================================================================
    // Scénario A : admin sur section planeur (section_id passé explicitement)
    // =========================================================================

    public function testAdminSectionPlaneur_NevoitPasDocsULM()
    {
        $docs = $this->model->get_filtered_documents(['section_id' => $this->section1_id]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths,
            'Doc planeur doit être visible');
        $this->assertNotContains('test/sec_b.pdf', $paths,
            'Doc ULM ne doit PAS être visible quand le filtre est sur section planeur');
    }

    public function testAdminSectionPlaneur_VoitTousDocsPilotes()
    {
        $docs = $this->model->get_filtered_documents(['section_id' => $this->section1_id]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/pilot.pdf', $paths,
            'Un admin sur section planeur doit voir tous les documents pilotes');
    }

    // =========================================================================
    // Scénario B : "Toutes" sélectionné en session
    // selector_with_all() stocke max(ID)+1 comme valeur de "Toutes".
    // Ce n'est pas un ID de section réel → gvv_model->section() retourne vide
    // → le contrôleur passe section_id=null → toutes les sections visibles.
    // =========================================================================

    public function testSectionModelRetourneVidePourIdInexistant()
    {
        // Simuler "Toutes" en session : stocker un ID hors plage (ex: 9999)
        $this->CI->session->set_userdata('section', 9999);
        // Recréer le modèle pour qu'il relise la session
        $model_toutes = new Archived_documents_model();

        $section = $model_toutes->section();
        $this->assertEmpty($section,
            'section() doit retourner vide quand la session contient un ID inexistant ("Toutes")');
    }

    public function testAdminToutes_VoitToutesLesSections()
    {
        // Quand section() est vide, le contrôleur passe section_id=null
        $docs = $this->model->get_filtered_documents(['section_id' => null]);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths,
            'Avec "Toutes", doc section A doit être visible');
        $this->assertContains('test/sec_b.pdf', $paths,
            'Avec "Toutes", doc section B (ULM) doit aussi être visible');
        $this->assertContains('test/pilot.pdf', $paths,
            'Avec "Toutes", docs pilotes doivent être visibles');
        $this->assertContains('test/club.pdf', $paths,
            'Avec "Toutes", docs club doivent être visibles');
    }

    // =========================================================================
    // Scénario C : admin choisit "Toutes" dans le filtre du formulaire
    // (section_id présent dans l'URL mais vide = '')
    // =========================================================================

    public function testAdminChoisitToutes_ViaFormulaire_VoitToutesLesSections()
    {
        // section_id='' envoyé par le formulaire quand "Toutes" est sélectionné
        $docs = $this->model->get_filtered_documents(['section_id' => '']);

        $paths = array_column($docs, 'file_path');
        $this->assertContains('test/sec_a.pdf', $paths,
            'Avec filtre "Toutes", doc section A doit être visible');
        $this->assertContains('test/sec_b.pdf', $paths,
            'Avec filtre "Toutes", doc ULM doit aussi être visible');
    }
}
