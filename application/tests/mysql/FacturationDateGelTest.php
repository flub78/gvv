<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Date de gel et facturation générée (achats, vols avion) : une écriture
 * générée par un achat ou un vol ne peut ni sortir d'une période clôturée,
 * ni y entrer, ni en être supprimée.
 *
 * Le vol, l'achat et la date de gel (2099-06-30) sont créés dans une
 * transaction annulée en fin de test : la base n'est pas modifiée.
 *
 * @covers Achats_model
 * @covers Vols_avion_model::update
 * @covers Ecritures_model::select_flight_frozen_lines
 */
class FacturationDateGelTest extends TransactionalTestCase
{
    const FREEZE_DATE = '2099-06-30';
    const CLOSED_DATE = '2099-06-15';
    const OPEN_DATE = '2099-07-10';
    const OTHER_OPEN_DATE = '2099-07-20';

    private $pilot;
    private $plane;
    private $produit;

    public function setUp(): void
    {
        parent::setUp();

        foreach (['vols_avion_model', 'vols_planeur_model', 'planeurs_model', 'event_model', 'achats_model', 'tarifs_model', 'tickets_model', 'comptes_model',
                  'ecritures_model', 'clotures_model', 'sections_model', 'membres_model', 'avions_model'] as $model) {
            $this->CI->load->model($model);
        }
        // tarifs_model::get_tarif() appelle $this->gvv_model->section() — alias requis
        if (!isset($this->CI->gvv_model)) {
            $this->CI->gvv_model = $this->CI->tarifs_model;
        }
        $this->CI->config->load('club', FALSE, TRUE);
        $this->CI->lang->load('facturation', 'french');
        $this->CI->lang->load('compta', 'french');

        // Pilote, avion et produit d'un vol avion déjà facturé : tarif et compte pilote existent
        $row = $this->CI->db->select('v.vapilid, v.vamacid, a.produit')
            ->from('volsa v')
            ->join('achats a', 'a.vol_avion = v.vaid')
            ->join('ecritures e', 'e.achat = a.id')
            ->order_by('v.vadate', 'desc')
            ->limit(1)->get()->row_array();
        if (empty($row)) {
            $this->markTestSkipped('Aucun vol avion facturé en base');
        }
        $this->pilot = $row['vapilid'];
        $this->plane = $row['vamacid'];
        $this->produit = $row['produit'];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function freeze() {
        $section_id = $this->CI->achats_model->section_id();
        $this->CI->db->insert('clotures', [
            'section' => $section_id ? $section_id : 1,
            'date' => self::FREEZE_DATE,
            'description' => 'PHPUnit FacturationDateGelTest',
        ]);
    }

    private function vol_avion($date) {
        return [
            'vadate' => $date, 'vapilid' => $this->pilot, 'vamacid' => $this->plane,
            'vacdeb' => '10.00', 'vacfin' => '11.00', 'vaduree' => '1.00',
            'vahdeb' => '10.00', 'vahfin' => '11.00', 'vaobs' => 'PHPUnit FacturationDateGelTest',
            'vadc' => 0, 'vacategorie' => 0, 'vaatt' => 1, 'facture' => 0, 'payeur' => '',
            'pourcentage' => 0, 'gel' => 0, 'club' => 0, 'vainst' => '', 'valieudeco' => '',
            'valieuatt' => '', 'local' => 0, 'nuit' => 0, 'reappro' => 0, 'essence' => 0,
        ];
    }

    private function create_vol_avion($date) {
        $data = $this->vol_avion($date);
        $data['vaid'] = $this->CI->vols_avion_model->create($data);
        $this->assertNotEmpty($this->ecritures_vol($data['vaid']), 'Le vol de test doit être facturé');
        return $data;
    }

    /** Écritures générées par un vol avion : [id => date_op] */
    private function ecritures_vol($vaid) {
        $rows = $this->CI->db->select('e.id, e.date_op')->from('ecritures e')
            ->join('achats a', 'a.id = e.achat')->where('a.vol_avion', $vaid)
            ->order_by('e.id')->get()->result_array();
        return array_column($rows, 'date_op', 'id');
    }

    /** Nombre d'écritures dont l'achat d'origine n'existe plus */
    private function orphan_count() {
        return $this->CI->db->from('ecritures e')->join('achats a', 'a.id = e.achat', 'left')
            ->where('e.achat >', 0)->where('a.id IS NULL', null, false)->count_all_results();
    }

    private function achat($date) {
        return [
            'date' => $date, 'produit' => $this->produit, 'quantite' => 1,
            'description' => 'PHPUnit FacturationDateGelTest', 'pilote' => $this->pilot,
            'saisie_par' => 'phpunit',
        ];
    }

    /** Écritures générées par un achat : [id => date_op] */
    private function ecritures_achat($achat_id) {
        $rows = $this->CI->db->select('id, date_op')->from('ecritures')->where('achat', $achat_id)
            ->order_by('id')->get()->result_array();
        return array_column($rows, 'date_op', 'id');
    }

    /** Exécute $action et retourne vrai si elle a levé une exception */
    private function rejected(callable $action) {
        try {
            $action();
        } catch (Exception $e) {
            return true;
        }
        return false;
    }

    // -----------------------------------------------------------------------
    // Vols avion
    // -----------------------------------------------------------------------

    public function test_vol_avion_ouvert_deplace_dans_la_periode_ouverte_est_accepte() {
        $this->freeze();
        $vol = $this->create_vol_avion(self::OPEN_DATE);

        $vol['vadate'] = self::OTHER_OPEN_DATE;
        $this->CI->vols_avion_model->update('vaid', $vol);

        $ecritures = $this->ecritures_vol($vol['vaid']);
        $this->assertCount(1, $ecritures);
        $this->assertSame([self::OTHER_OPEN_DATE], array_values($ecritures));
    }

    public function test_vol_avion_cloture_ne_peut_pas_etre_ramene_apres_la_date_de_gel() {
        $vol = $this->create_vol_avion(self::CLOSED_DATE);
        $this->freeze();
        $avant = $this->ecritures_vol($vol['vaid']);
        $orphelines = $this->orphan_count();

        $vol['vadate'] = self::OPEN_DATE;
        $rejected = $this->rejected(function () use ($vol) {
            $this->CI->vols_avion_model->update('vaid', $vol);
        });

        $this->assertSame($orphelines, $this->orphan_count(), 'Aucune écriture ne doit perdre son achat');
        $this->assertSame($avant, $this->ecritures_vol($vol['vaid']), 'La facturation ne doit pas changer');
        $this->assertSame(self::CLOSED_DATE, $this->CI->db->get_where('volsa', ['vaid' => $vol['vaid']])->row()->vadate);
        $this->assertTrue($rejected, 'Sortir la facturation d\'un vol de la période clôturée doit être refusé');
    }

    public function test_vol_avion_ouvert_ne_peut_pas_etre_deplace_dans_la_periode_cloturee() {
        $this->freeze();
        $vol = $this->create_vol_avion(self::OPEN_DATE);
        $avant = $this->ecritures_vol($vol['vaid']);
        $orphelines = $this->orphan_count();

        $vol['vadate'] = self::CLOSED_DATE;
        $rejected = $this->rejected(function () use ($vol) {
            $this->CI->vols_avion_model->update('vaid', $vol);
        });

        $this->assertSame($orphelines, $this->orphan_count(), 'Aucune écriture ne doit perdre son achat');
        $this->assertSame($avant, $this->ecritures_vol($vol['vaid']), 'La facturation ne doit pas changer');
        $this->assertSame(self::OPEN_DATE, $this->CI->db->get_where('volsa', ['vaid' => $vol['vaid']])->row()->vadate);
        $this->assertTrue($rejected, 'Déplacer un vol dans la période clôturée doit être refusé');
    }

    public function test_vol_avion_ne_peut_pas_etre_cree_dans_la_periode_cloturee() {
        $this->freeze();
        $rejected = $this->rejected(function () {
            $this->CI->vols_avion_model->create($this->vol_avion(self::CLOSED_DATE));
        });

        $this->assertSame(0, $this->CI->db->where(['vadate' => self::CLOSED_DATE, 'vamacid' => $this->plane])
            ->count_all_results('volsa'));
        $this->assertTrue($rejected, 'Créer un vol facturé dans la période clôturée doit être refusé');
    }

    public function test_vol_avion_cloture_est_detecte_comme_verrouille() {
        $vol = $this->create_vol_avion(self::CLOSED_DATE);
        $this->assertEmpty($this->CI->ecritures_model->select_flight_frozen_lines($vol['vaid'], 'vol_avion'));
        $this->freeze();
        $this->assertNotEmpty($this->CI->ecritures_model->select_flight_frozen_lines($vol['vaid'], 'vol_avion'));
    }

    // -----------------------------------------------------------------------
    // Vols planeur
    // -----------------------------------------------------------------------

    /** Vol planeur facturé à $date, ou test ignoré si aucun planeur facturé n'existe */
    private function create_vol_planeur($date) {
        $row = $this->CI->db->select('v.vppilid, v.vpmacid')->from('volsp v')
            ->join('achats a', 'a.vol_planeur = v.vpid')->join('ecritures e', 'e.achat = a.id')
            ->where('v.vpautonome', 1)->order_by('v.vpdate', 'desc')->limit(1)->get()->row_array();
        if (empty($row)) {
            $this->markTestSkipped('Aucun vol planeur autonome facturé en base');
        }
        $data = [
            'vpdate' => $date, 'vppilid' => $row['vppilid'], 'vpmacid' => $row['vpmacid'],
            'vpcdeb' => '10.00', 'vpcfin' => '11.00', 'vpduree' => 60, 'vpautonome' => 1,
            'vpaltrem' => 0, 'vpcategorie' => 0, 'vpdc' => 0, 'vpticcolle' => 0, 'facture' => 0,
            'payeur' => '', 'pourcentage' => 0, 'tempmoteur' => 0, 'remorqueur' => '',
            'pilote_remorqueur' => '', 'vplieudeco' => '', 'vpobs' => 'PHPUnit FacturationDateGelTest',
        ];
        $data['vpid'] = $this->CI->vols_planeur_model->create($data);
        if (!$this->ecritures_vol_planeur($data['vpid'])) {
            $this->markTestSkipped('Le vol planeur de test n\'est pas facturé');
        }
        return $data;
    }

    private function ecritures_vol_planeur($vpid) {
        $rows = $this->CI->db->select('e.id, e.date_op')->from('ecritures e')
            ->join('achats a', 'a.id = e.achat')->where('a.vol_planeur', $vpid)
            ->order_by('e.id')->get()->result_array();
        return array_column($rows, 'date_op', 'id');
    }

    public function test_vol_planeur_ouvert_deplace_dans_la_periode_ouverte_est_accepte() {
        $this->freeze();
        $vol = $this->create_vol_planeur(self::OPEN_DATE);

        $vol['vpdate'] = self::OTHER_OPEN_DATE;
        $this->CI->vols_planeur_model->update('vpid', $vol);
        $this->assertSame([self::OTHER_OPEN_DATE], array_unique(array_values($this->ecritures_vol_planeur($vol['vpid']))));
    }

    public function test_vol_planeur_cloture_ne_peut_pas_etre_ramene_apres_la_date_de_gel() {
        $vol = $this->create_vol_planeur(self::CLOSED_DATE);
        $this->freeze();
        $avant = $this->ecritures_vol_planeur($vol['vpid']);
        $orphelines = $this->orphan_count();

        $vol['vpdate'] = self::OPEN_DATE;
        $rejected = $this->rejected(function () use ($vol) {
            $this->CI->vols_planeur_model->update('vpid', $vol);
        });

        $this->assertSame($orphelines, $this->orphan_count(), 'Aucune écriture ne doit perdre son achat');
        $this->assertSame($avant, $this->ecritures_vol_planeur($vol['vpid']), 'La facturation ne doit pas changer');
        $this->assertTrue($rejected, 'Sortir la facturation d\'un vol planeur de la période clôturée doit être refusé');
    }

    public function test_vol_planeur_ouvert_ne_peut_pas_etre_deplace_dans_la_periode_cloturee() {
        $this->freeze();
        $vol = $this->create_vol_planeur(self::OPEN_DATE);
        $avant = $this->ecritures_vol_planeur($vol['vpid']);

        $vol['vpdate'] = self::CLOSED_DATE;
        $rejected = $this->rejected(function () use ($vol) {
            $this->CI->vols_planeur_model->update('vpid', $vol);
        });

        $this->assertSame($avant, $this->ecritures_vol_planeur($vol['vpid']), 'La facturation ne doit pas changer');
        $this->assertTrue($rejected, 'Déplacer un vol planeur dans la période clôturée doit être refusé');
    }

    // -----------------------------------------------------------------------
    // Achats
    // -----------------------------------------------------------------------

    public function test_achat_cree_dans_la_periode_ouverte_est_accepte() {
        $this->freeze();
        $id = $this->CI->achats_model->create($this->achat(self::OPEN_DATE));
        $this->assertSame([self::OPEN_DATE], array_values($this->ecritures_achat($id)));
    }

    public function test_achat_ne_peut_pas_etre_cree_dans_la_periode_cloturee() {
        $this->freeze();
        $rejected = $this->rejected(function () {
            $this->CI->achats_model->create($this->achat(self::CLOSED_DATE));
        });

        $this->assertSame(0, $this->CI->db->where(['date_op' => self::CLOSED_DATE, 'description' => 'PHPUnit FacturationDateGelTest'])
            ->count_all_results('ecritures'));
        $this->assertTrue($rejected, 'Créer un achat dans la période clôturée doit être refusé');
    }

    public function test_achat_ouvert_modifie_dans_la_periode_ouverte_est_accepte() {
        $this->freeze();
        $data = $this->achat(self::OPEN_DATE);
        $data['id'] = $this->CI->achats_model->create($data);

        $data['date'] = self::OTHER_OPEN_DATE;
        $this->CI->achats_model->update('id', $data);
        $this->assertSame([self::OTHER_OPEN_DATE], array_values($this->ecritures_achat($data['id'])));
    }

    public function test_achat_ouvert_ne_peut_pas_etre_deplace_dans_la_periode_cloturee() {
        $this->freeze();
        $data = $this->achat(self::OPEN_DATE);
        $data['id'] = $this->CI->achats_model->create($data);
        $avant = $this->ecritures_achat($data['id']);

        $data['date'] = self::CLOSED_DATE;
        $rejected = $this->rejected(function () use ($data) {
            $this->CI->achats_model->update('id', $data);
        });
        $this->assertSame($avant, $this->ecritures_achat($data['id']));
        $this->assertTrue($rejected, 'Déplacer un achat dans la période clôturée doit être refusé');
    }

    public function test_achat_cloture_ne_peut_pas_etre_ramene_apres_la_date_de_gel() {
        $data = $this->achat(self::CLOSED_DATE);
        $data['id'] = $this->CI->achats_model->create($data);
        $this->freeze();
        $avant = $this->ecritures_achat($data['id']);

        $data['date'] = self::OPEN_DATE;
        $rejected = $this->rejected(function () use ($data) {
            $this->CI->achats_model->update('id', $data);
        });
        $this->assertSame($avant, $this->ecritures_achat($data['id']));
        $this->assertTrue($rejected, 'Sortir un achat de la période clôturée doit être refusé');
    }

    public function test_achat_cloture_ne_peut_pas_etre_supprime() {
        $data = $this->achat(self::CLOSED_DATE);
        $id = $this->CI->achats_model->create($data);
        $this->freeze();
        $avant = $this->ecritures_achat($id);

        $rejected = $this->rejected(function () use ($id) {
            $this->CI->achats_model->delete(['id' => $id]);
        });
        $this->assertSame($avant, $this->ecritures_achat($id));
        $this->assertSame(1, $this->CI->db->where('id', $id)->count_all_results('achats'));
        $this->assertTrue($rejected, 'Supprimer un achat de la période clôturée doit être refusé');
    }

    public function test_achat_ouvert_peut_etre_supprime() {
        $this->freeze();
        $id = $this->CI->achats_model->create($this->achat(self::OPEN_DATE));
        $this->CI->achats_model->delete(['id' => $id]);
        $this->assertSame([], $this->ecritures_achat($id));
        $this->assertSame(0, $this->CI->db->where('id', $id)->count_all_results('achats'));
    }

    public function test_date_achat_controlee_par_rapport_a_la_date_de_gel_dans_le_formulaire() {
        $this->CI->load->library('gvvmetadata');
        $this->assertSame('activity_date', $this->CI->gvvmetadata->field_subtype('achats', 'date'));
    }
}
