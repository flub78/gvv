<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL — Maintenance_programme_model::synchroniser_structure() (Etape 4.2)
 *
 * Verifie que le re-parsing d'une nouvelle version de programme met a
 * jour les sections/taches actives, et que le retrait d'une tache deja
 * utilisee dans une maintenance_realisation la desactive (actif = 0)
 * plutot que de la supprimer -- son historique reste consultable.
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 4.2)
 */
class MaintenanceProgrammeSyncTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $CI;
    private $membre_login;
    private $macimmat;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('maintenance_programme_model');
        $this->CI->load->model('maintenance_programme_section_model');
        $this->CI->load->model('maintenance_tache_model');
        $this->CI->load->model('maintenance_dossier_model');
        $this->CI->load->model('maintenance_operation_model');
        $this->CI->load->model('maintenance_realisation_model');

        $membre = $this->db->limit(1)->get('membres')->row_array();
        $this->membre_login = $membre ? $membre['mlogin'] : null;

        $aircraft = $this->db->limit(1)->get('machinesa')->row_array();
        $this->macimmat = $aircraft ? $aircraft['macimmat'] : null;
    }

    private function markdownV1()
    {
        return <<<MD
# Visite 100h test sync

## Moteur

### Vidange
Vidange huile moteur.

### Controle bougies
Controle et nettoyage des bougies.

## Cellule

### Controle visuel
Inspection des surfaces.
MD;
    }

    public function testPremiereImportationCreeSectionsEtTaches()
    {
        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'SYNC_TEST_' . uniqid(), 'titre' => 'Titre initial',
        ));

        $result = $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $this->markdownV1());
        $this->assertTrue($result);

        $programme = $this->CI->maintenance_programme_model->get($programme_id);
        $this->assertSame('Visite 100h test sync', $programme['titre']);

        $sections = $this->CI->maintenance_programme_section_model->get_by_programme($programme_id);
        $this->assertCount(2, $sections);

        $taches = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $this->assertCount(3, $taches);

        $this->nettoyerProgramme($programme_id);
    }

    public function testNouvelleVersionMetAJourOrdreEtDescriptionParTitreInchange()
    {
        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'SYNC_TEST_' . uniqid(), 'titre' => 'Titre initial',
        ));
        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $this->markdownV1());

        $taches_v1 = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $vidange_v1 = current(array_filter($taches_v1, function ($t) { return $t['titre'] === 'Vidange'; }));
        $this->assertNotFalse($vidange_v1);

        // v2 : meme tache "Vidange" mais description modifiee et section reordonnee
        $markdown_v2 = <<<MD
# Visite 100h test sync v2

## Cellule

### Controle visuel
Inspection des surfaces.

## Moteur

### Vidange
Vidange huile moteur avec le nouveau filtre.

### Controle bougies
Controle et nettoyage des bougies.
MD;

        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $markdown_v2);

        // La tache "Vidange" est reutilisee (meme id), pas dupliquee
        $taches_v2 = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $this->assertCount(3, $taches_v2, 'Le nombre de taches actives ne doit pas changer, seul l ordre/description bouge');

        $vidange_v2 = current(array_filter($taches_v2, function ($t) { return $t['titre'] === 'Vidange'; }));
        $this->assertSame($vidange_v1['id'], $vidange_v2['id']);
        $this->assertStringContainsString('nouveau filtre', $vidange_v2['description']);

        $programme = $this->CI->maintenance_programme_model->get($programme_id);
        $this->assertSame('Visite 100h test sync v2', $programme['titre']);

        $this->nettoyerProgramme($programme_id);
    }

    public function testTacheObsoleteSansRealisationEstSupprimee()
    {
        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'SYNC_TEST_' . uniqid(), 'titre' => 'Titre initial',
        ));
        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $this->markdownV1());

        $taches_v1 = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $this->assertCount(3, $taches_v1);
        $bougies_id = current(array_filter($taches_v1, function ($t) { return $t['titre'] === 'Controle bougies'; }))['id'];

        // v2 : la tache "Controle bougies" disparait, jamais utilisee dans une realisation
        $markdown_v2 = <<<MD
# Visite 100h test sync

## Moteur

### Vidange
Vidange huile moteur.

## Cellule

### Controle visuel
Inspection des surfaces.
MD;

        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $markdown_v2);

        $taches_v2 = $this->CI->maintenance_tache_model->get_by_programme($programme_id, false);
        $ids_restants = array_column($taches_v2, 'id');
        $this->assertNotContains($bougies_id, $ids_restants, 'La tache non utilisee doit etre supprimee, pas simplement desactivee');
        $this->assertCount(2, $taches_v2);

        $this->nettoyerProgramme($programme_id);
    }

    public function testTacheObsoleteAvecRealisationEstDesactiveeEtResteConsultable()
    {
        if (!$this->membre_login || !$this->macimmat) {
            $this->markTestSkipped('Un membre et un aeronef sont necessaires pour ce test');
        }

        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'SYNC_TEST_' . uniqid(), 'titre' => 'Titre initial',
        ));
        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $this->markdownV1());

        $taches_v1 = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $bougies = current(array_filter($taches_v1, function ($t) { return $t['titre'] === 'Controle bougies'; }));

        // Utiliser la tache "Controle bougies" dans une realisation reelle
        $dossier_id = $this->CI->maintenance_dossier_model->ouvrir(array(
            'entite_type' => 'aeronef', 'entite_id' => $this->macimmat, 'programme_id' => $programme_id,
        ));
        $operation_id = $this->CI->maintenance_operation_model->create(array(
            'dossier_id' => $dossier_id, 'date_operation' => date('Y-m-d'),
            'mecano_id' => $this->membre_login, 'mode_saisie' => 'directe',
        ));
        $this->CI->maintenance_realisation_model->save_batch($operation_id, array(
            $bougies['id'] => array('statut' => 'fait'),
        ));

        // v2 : la tache "Controle bougies" disparait du markdown, mais elle est utilisee
        $markdown_v2 = <<<MD
# Visite 100h test sync

## Moteur

### Vidange
Vidange huile moteur.

## Cellule

### Controle visuel
Inspection des surfaces.
MD;

        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $markdown_v2);

        // La tache existe toujours (consultable) mais n'apparait plus dans la liste active
        $taches_actives = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $ids_actifs = array_column($taches_actives, 'id');
        $this->assertNotContains($bougies['id'], $ids_actifs);

        $tache_row = $this->CI->maintenance_tache_model->get($bougies['id']);
        $this->assertNotEmpty($tache_row, 'La tache doit rester consultable via get()');
        $this->assertSame(0, (int) $tache_row['actif']);
        $this->assertSame('Controle bougies', $tache_row['titre']);

        // La realisation existante reste intacte
        $realisations = $this->CI->maintenance_realisation_model->get_by_operation($operation_id);
        $this->assertCount(1, $realisations);
        $this->assertSame('Controle bougies', $realisations[0]['tache_titre']);

        $this->db->where('operation_id', $operation_id)->delete('maintenance_realisations');
        $this->db->where('id', $operation_id)->delete('maintenance_operations');
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->nettoyerProgramme($programme_id);
    }

    public function testInvalidMarkdownThrowsExceptionAndLeavesStructureUnchanged()
    {
        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'SYNC_TEST_' . uniqid(), 'titre' => 'Titre initial',
        ));
        $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, $this->markdownV1());
        $taches_avant = $this->CI->maintenance_tache_model->get_by_programme($programme_id);

        try {
            $this->CI->maintenance_programme_model->synchroniser_structure($programme_id, "# Sans aucune section");
            $this->fail('Une exception etait attendue pour un markdown invalide');
        } catch (Exception $e) {
            $this->assertStringContainsString('Aucune section trouvée', $e->getMessage());
        }

        $taches_apres = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $this->assertCount(count($taches_avant), $taches_apres);

        $this->nettoyerProgramme($programme_id);
    }

    private function nettoyerProgramme($programme_id)
    {
        $this->db->where('programme_id', $programme_id)->delete('maintenance_dossiers');
        $sections = $this->db->where('programme_id', $programme_id)->get('maintenance_programme_sections')->result_array();
        foreach ($sections as $section) {
            $this->db->where('programme_section_id', $section['id'])->delete('maintenance_taches');
        }
        $this->db->where('programme_id', $programme_id)->delete('maintenance_programme_sections');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }
}

/* End of file MaintenanceProgrammeSyncTest.php */
/* Location: ./application/tests/mysql/MaintenanceProgrammeSyncTest.php */
