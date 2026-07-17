<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Non-régression : quand aucun tarif n'existe pour la période d'un vol,
 * la facturation doit échouer avec une erreur ET le vol ne doit PAS être
 * créé en base.
 *
 * Avant correctif, Vols_planeur_model::create() et Vols_avion_model::create()
 * inséraient d'abord le vol puis appelaient facture(). Si la facturation
 * levait une exception (tarif manquant), le vol restait malgré tout en base.
 *
 * @covers Vols_planeur_model::create
 * @covers Vols_avion_model::create
 */
class VolsCreationTarifManquantTest extends TransactionalTestCase
{
    /** @var string Date antérieure à tous les tarifs configurés en base (aucun tarif ne peut matcher) */
    private $date_sans_tarif = '1990-01-01';

    private $pilot_login;
    private $glider_immat;
    private $plane_immat;

    public function setUp(): void
    {
        parent::setUp();

        $this->CI->load->model('vols_planeur_model');
        $this->CI->load->model('vols_avion_model');
        $this->CI->load->model('achats_model');
        $this->CI->load->model('membres_model');
        $this->CI->load->model('planeurs_model');
        $this->CI->load->model('avions_model');
        $this->CI->load->model('sections_model');
        $this->CI->load->model('tarifs_model');
        $this->CI->load->model('tickets_model');
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('ecritures_model');

        // tarifs_model::get_tarif() appelle $this->gvv_model->section() — alias requis
        if (!isset($this->CI->gvv_model)) {
            $this->CI->gvv_model = $this->CI->tarifs_model;
        }

        // Charge les configs autoloadées en production mais absentes du bootstrap de test
        $this->CI->config->load('club', FALSE, TRUE);
        $this->CI->lang->load('facturation', 'french');

        // Planeur actif, non privé, avec un tarif heure référencé
        $glider = $this->CI->db
            ->select('mpimmat')
            ->from('machinesp')
            ->where('actif', 1)
            ->where('mpprive', 0)
            ->where('mprix !=', '')
            ->where('mprix !=', 'Gratuit')
            ->where('mprix !=', 'Free')
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($glider)) {
            $this->markTestSkipped('Aucun planeur actif avec tarif heure configuré trouvé en base');
        }
        $this->glider_immat = $glider['mpimmat'];

        // Avion actif avec un tarif heure référencé
        $plane = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->where('maprix !=', '')
            ->where('maprix !=', 'Gratuit')
            ->where('maprix !=', 'Free')
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($plane)) {
            $this->markTestSkipped('Aucun avion actif avec tarif heure configuré trouvé en base');
        }
        $this->plane_immat = $plane['macimmat'];

        // Un membre actif quelconque
        $pilot = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($pilot)) {
            $this->markTestSkipped('Aucun membre actif trouvé en base');
        }
        $this->pilot_login = $pilot['mlogin'];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function dataVolPlaneur(array $overrides = []): array
    {
        $defaults = [
            'vpdate'            => $this->date_sans_tarif,
            'vppilid'           => $this->pilot_login,
            'vpmacid'           => $this->glider_immat,
            'vpcdeb'            => '10.00',
            'vpcfin'            => '11.00',
            'vpduree'           => 60,
            'vpautonome'        => 0,
            'vpaltrem'          => 0,
            'vpcategorie'       => 0,
            'vpdc'              => 0,
            'vpticcolle'        => 0,
            'facture'           => 0,
            'payeur'            => '',
            'pourcentage'       => 0,
            'tempmoteur'        => 0,
            'remorqueur'        => '',
            'pilote_remorqueur' => '',
            'vplieudeco'        => 'LFOI',
        ];
        return array_merge($defaults, $overrides);
    }

    private function dataVolAvion(array $overrides = []): array
    {
        $defaults = [
            'vadate'      => $this->date_sans_tarif,
            'vapilid'     => $this->pilot_login,
            'vamacid'     => $this->plane_immat,
            'vacdeb'      => '10.00',
            'vacfin'      => '11.50',
            'vaduree'     => '1.50',
            'vahdeb'      => '10.00',
            'vahfin'      => '11.50',
            'vaobs'       => 'Test VolsCreationTarifManquant',
            'vadc'        => 0,
            'vacategorie' => 0,
            'vaatt'       => 1,
            'facture'     => 0,
            'payeur'      => '',
            'pourcentage' => 0,
            'gel'         => 0,
            'club'        => 0,
            'vainst'      => '',
            'valieudeco'  => '',
            'valieuatt'   => '',
            'local'       => 0,
            'nuit'        => 0,
            'reappro'     => 0,
            'essence'     => 0,
        ];
        return array_merge($defaults, $overrides);
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * Vol planeur : si aucun tarif n'existe pour la période, la création
     * doit échouer et le vol ne doit pas apparaître en base.
     */
    public function testCreationVolPlaneurEchoueSiTarifManquantNeCreePasLeVol(): void
    {
        $where = [
            'vpmacid' => $this->glider_immat,
            'vpdate'  => $this->date_sans_tarif,
            'vppilid' => $this->pilot_login,
        ];

        $avant = $this->CI->db->where($where)->count_all_results('volsp');
        $this->assertEquals(0, $avant, 'Précondition : aucun vol planeur existant avec ces critères');

        $exception_levee = false;
        try {
            $this->CI->vols_planeur_model->create($this->dataVolPlaneur());
        } catch (Exception $e) {
            $exception_levee = true;
            $this->assertStringContainsString('tarifs', $e->getMessage(),
                "Le message d'erreur doit signaler l'absence de tarif");
        }

        $this->assertTrue($exception_levee,
            "La création doit échouer avec une exception quand aucun tarif n'existe pour la période");

        $apres = $this->CI->db->where($where)->count_all_results('volsp');
        $this->assertEquals(0, $apres,
            'Le vol planeur ne doit pas être créé en base quand la facturation échoue (tarif manquant)');
    }

    /**
     * Vol avion : si aucun tarif n'existe pour la période, la création
     * doit échouer et le vol ne doit pas apparaître en base.
     */
    public function testCreationVolAvionEchoueSiTarifManquantNeCreePasLeVol(): void
    {
        $where = [
            'vamacid' => $this->plane_immat,
            'vadate'  => $this->date_sans_tarif,
            'vapilid' => $this->pilot_login,
        ];

        $avant = $this->CI->db->where($where)->count_all_results('volsa');
        $this->assertEquals(0, $avant, 'Précondition : aucun vol avion existant avec ces critères');

        $exception_levee = false;
        try {
            $this->CI->vols_avion_model->create($this->dataVolAvion());
        } catch (Exception $e) {
            $exception_levee = true;
            $this->assertStringContainsString('tarifs', $e->getMessage(),
                "Le message d'erreur doit signaler l'absence de tarif");
        }

        $this->assertTrue($exception_levee,
            "La création doit échouer avec une exception quand aucun tarif n'existe pour la période");

        $apres = $this->CI->db->where($where)->count_all_results('volsa');
        $this->assertEquals(0, $apres,
            'Le vol avion ne doit pas être créé en base quand la facturation échoue (tarif manquant)');
    }
}
