<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Vérifie que la facturation impute les achats au payeur quand celui-ci
 * est différent du pilote.
 *
 * La logique testée est Facturation::nouvel_achat_partage() (classe de base),
 * appelée via Facturation_cpta comme module de référence.
 *
 * Trois cas :
 *  - payeur à 100 % : seul le payeur est débité
 *  - payeur à 50 %  : pilote et payeur sont débités chacun à moitié
 *  - sans payeur    : seul le pilote est débité (non-régression)
 *
 * @covers Facturation::nouvel_achat_partage
 */
class VolsPlaneurePayeurFacturationTest extends TransactionalTestCase
{
    /** @var string login du pilote (membre existant en base) */
    private $pilot_login;

    /** @var string login du payeur (membre différent, sans redirection de compte) */
    private $payeur_login;

    /** @var string immatriculation du planeur (tarif non-gratuit, non-privé) */
    private $machine_immat;

    public function setUp(): void
    {
        parent::setUp();

        $this->CI->load->model('vols_planeur_model');
        $this->CI->load->model('achats_model');
        $this->CI->load->model('membres_model');
        $this->CI->load->model('sections_model');   // Facturation::nouvel_achat
        $this->CI->load->model('tarifs_model');     // achats_model::create
        $this->CI->load->model('tickets_model');    // decompte_ou_facture
        $this->CI->load->model('comptes_model');    // achats_model::gen_ecriture
        $this->CI->load->model('ecritures_model');  // achats_model::gen_ecriture

        // tarifs_model::get_tarif() appelle $this->gvv_model->section() — alias requis
        if (!isset($this->CI->gvv_model)) {
            $this->CI->gvv_model = $this->CI->tarifs_model;
        }

        // Charge les traductions pour que les descriptions restent sous 80 caractères
        $this->CI->lang->load('facturation', 'french');

        // Cherche un planeur actif avec tarif heure non-gratuit et non-privé
        $machine = $this->CI->db
            ->select('mpimmat, mprix')
            ->from('machinesp')
            ->where('actif', 1)
            ->where('mpprive', 0)
            ->where('mprix !=', '')
            ->where('mprix !=', 'Gratuit')
            ->where('mprix !=', 'Free')
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($machine)) {
            $this->markTestSkipped('Aucun planeur actif avec tarif heure configuré trouvé en base');
        }

        $this->machine_immat = $machine['mpimmat'];

        // La section active provient du MockSession (section_id = 1 dans l'env de test)
        $section_id = (int) $this->CI->session->userdata('section');

        // Cherche deux membres actifs distincts :
        //  - sans redirection de compte (membres.compte = 0) pour être facturés directement
        //  - avec un compte comptable 411 dans la section active (pour gen_ecriture())
        //  - avec un nom court (≤ 18 chars) pour que la description reste sous 80 chars
        //    (MockLang retourne la clé brute "facturation_paid_for" au lieu de la traduction)
        $join_cond = "comptes.pilote = membres.mlogin AND comptes.codec = '411'"
            . ($section_id ? " AND comptes.club = $section_id" : "");

        $members = $this->CI->db
            ->select('membres.mlogin')
            ->from('membres')
            ->join('comptes', $join_cond, 'inner')
            ->where('membres.actif', 1)
            ->where('(membres.compte = 0 OR membres.compte IS NULL)')
            ->where("LENGTH(CONCAT(membres.mprenom, ' ', membres.mnom)) <=", 18)
            ->group_by('membres.mlogin')
            ->limit(2)
            ->get()
            ->result_array();

        if (count($members) < 2) {
            $this->markTestSkipped(
                'Pas assez de membres actifs avec compte 411, sans redirection et nom court (besoin de 2)');
        }

        $this->pilot_login  = $members[0]['mlogin'];
        $this->payeur_login = $members[1]['mlogin'];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Insère un vol planeur de test et retourne son vpid.
     * vpautonome=0 : pas de remontage, seules les heures sont facturées.
     */
    private function insertVolPlaneur(array $overrides = []): int
    {
        $defaults = [
            'vpdate'      => '2099-01-15',
            'vppilid'     => $this->pilot_login,
            'vpmacid'     => $this->machine_immat,
            'vpcdeb'      => '10.00',
            'vpcfin'      => '11.00',
            'vpduree'     => 60,
            'vpautonome'  => 0,
            'vpaltrem'    => 0,
            'vpcategorie' => 0,
            'vpdc'        => 0,
            'vpticcolle'  => 0,
            'facture'     => 0,
            'payeur'      => '',
            'pourcentage' => 0,
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('volsp', $data);
        $id = (int) $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id, 'L\'insertion du vol test doit réussir');
        return $id;
    }

    /**
     * Charge le module de facturation concret disponible et l'affecte à $this->CI->facturation_module.
     * Retourne le nom du module chargé, ou null si aucun module concret trouvé.
     *
     * La classe de base Facturation doit être chargée en premier car les modules en héritent.
     */
    private function loadFacturationModule(): ?string
    {
        // La classe de base doit exister avant que PHP puisse instancier un enfant
        if (!class_exists('Facturation', false)) {
            $this->CI->load->library('Facturation', '', 'facturation_base');
        }

        // dac en premier : pas de logique forfait, appelle directement nouvel_achat_partage
        $modules = ['dac', 'aces', 'accabs', 'cpta', 'ulm', 'vichy'];
        foreach ($modules as $module) {
            $class = 'Facturation_' . $module;
            $path  = APPPATH . 'libraries/' . $class . '.php';
            if (file_exists($path)) {
                // Utilise un alias unique par appel pour éviter le cache CI
                $alias = 'facturation_test_' . $module;
                $this->CI->load->library($class, '', $alias);
                if (isset($this->CI->$alias)) {
                    $this->CI->facturation_module = $this->CI->$alias;
                    return $class;
                }
            }
        }
        return null;
    }

    /**
     * Retourne les achats créés pour un vol donné.
     */
    private function getAchatsForVol(int $vol_id): array
    {
        return $this->CI->db
            ->select('pilote, quantite, produit')
            ->from('achats')
            ->where('vol_planeur', $vol_id)
            ->get()
            ->result_array();
    }

    /**
     * Insère un vol, récupère son tableau via a_facturer(), surcharge les champs
     * payeur/pourcentage et facture via le module concret.
     *
     * @return array|null Les achats créés, ou null si le module de facturation est absent.
     */
    private function insertEtFacturer(array $volOverrides, array $facturationOverrides): ?array
    {
        $vol_id = $this->insertVolPlaneur($volOverrides);

        // Récupère le vol avec tous les champs de jointure (mprix, pilote, prive…)
        $vols = $this->CI->vols_planeur_model->a_facturer($vol_id);
        $this->assertCount(1, $vols,
            "a_facturer() doit trouver le vol $vol_id inséré dans la transaction courante");

        $vol = array_merge($vols[0], $facturationOverrides);

        // Charge un module de facturation concret (indépendant du config club)
        $module = $this->loadFacturationModule();
        if ($module === null) {
            $this->markTestSkipped('Aucun module de facturation concret trouvé dans libraries/');
        }

        $this->CI->facturation_module->facture_vol_planeur($vol);

        return $this->getAchatsForVol($vol_id);
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * Quand le payeur est à 100 %, seul le payeur doit être débité.
     */
    public function testPayeur100PourcentEstSeulDebite(): void
    {
        $achats = $this->insertEtFacturer([], [
            'payeur'      => $this->payeur_login,
            'pourcentage' => 100,
        ]);

        $this->assertNotEmpty($achats,
            "La facturation doit créer au moins un achat " .
            "(planeur={$this->machine_immat}, payeur={$this->payeur_login})");

        foreach ($achats as $achat) {
            $this->assertEquals(
                $this->payeur_login,
                $achat['pilote'],
                "Avec payeur à 100 %, l'achat doit être imputé au payeur " .
                "({$this->payeur_login}) et non au pilote ({$this->pilot_login})"
            );
        }
    }

    /**
     * Quand le payeur est à 50 %, pilote et payeur sont chacun débités
     * d'une demi-quantité.
     */
    public function testPayeur50PourcentPartageEntrePiloteEtPayeur(): void
    {
        $achats = $this->insertEtFacturer([], [
            'payeur'      => $this->payeur_login,
            'pourcentage' => 50,
        ]);

        $this->assertNotEmpty($achats,
            "La facturation doit créer au moins un achat (partage à 50 %)");

        $pilotes_debites = array_column($achats, 'pilote');

        $this->assertContains($this->pilot_login, $pilotes_debites,
            "Avec partage à 50 %, le pilote ({$this->pilot_login}) doit être débité");

        $this->assertContains($this->payeur_login, $pilotes_debites,
            "Avec partage à 50 %, le payeur ({$this->payeur_login}) doit être débité");
    }

    /**
     * Sans payeur, seul le pilote est débité (non-régression).
     */
    public function testSansPayeurPiloteSeulEstDebite(): void
    {
        $achats = $this->insertEtFacturer([], [
            'payeur'      => '',
            'pourcentage' => 0,
        ]);

        $this->assertNotEmpty($achats,
            "Sans payeur, la facturation doit créer un achat pour le pilote");

        foreach ($achats as $achat) {
            $this->assertEquals(
                $this->pilot_login,
                $achat['pilote'],
                "Sans payeur, l'achat doit être imputé au pilote ({$this->pilot_login}) uniquement"
            );
        }
    }
}
