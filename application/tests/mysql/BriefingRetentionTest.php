<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Briefing passager
 */
class BriefingRetentionTest extends TestCase
{
    private $CI;
    private $retention;
    private $form;
    private $flight_ids = array();
    private $trace_submission_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->library('briefing_retention');
        $this->retention = $this->CI->briefing_retention;
        $this->CI->load->model('form_submissions_model');

        $this->form = $this->CI->db
            ->where('public_slug', Briefing_retention::FORM_SLUG)
            ->get('forms')
            ->row_array();
        if (empty($this->form)) {
            $this->markTestSkipped('Formulaire de briefing introuvable.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->flight_ids as $id) {
            $this->CI->db->delete('vols_decouverte', array('id' => $id));
        }
        foreach ($this->trace_submission_ids as $id) {
            $this->CI->db->delete('form_submission_deletions', array('submission_id' => $id));
            $this->CI->db->delete('form_submissions', array('id' => $id));
        }
    }

    private function flight($date_vol)
    {
        $this->CI->db->insert('vols_decouverte', array(
            'date_vente' => date('Y-m-d'),
            'date_vol'   => $date_vol,
            'club'       => 1,
            'product'    => 'TEST_RETENTION',
            'saisie_par' => 'phpunit',
            'cancelled'  => 0,
        ));
        $id = (int) $this->CI->db->insert_id();
        $this->flight_ids[] = $id;
        return $id;
    }

    private function submission($flight_id, $submitted_at)
    {
        return array(
            'id'           => 1,
            'form_id'      => $this->form['id'],
            'subject_type' => $flight_id ? 'vols_decouverte' : null,
            'subject_id'   => $flight_id,
            'submitted_at' => $submitted_at,
            'created_at'   => $submitted_at,
        );
    }

    private function until($flight_id, $submitted_at)
    {
        $result = $this->retention->protected_until($this->form, array($this->submission($flight_id, $submitted_at)));
        return $result[1] ?? null;
    }

    /**
     * Scénario: Le briefing d'un vol récent est protégé pendant trois mois après le vol
     *   Étant donné un vol de découverte effectué la semaine dernière
     *   Et son briefing enregistré
     *   Quand un administrateur envisage de le supprimer
     *   Alors le briefing est protégé jusqu'à trois mois après la date du vol
     */
    public function testBriefingOfRecentFlightIsProtectedForThreeMonths()
    {
        $flight_date = date('Y-m-d', strtotime('-7 days'));
        $expected = date('Y-m-d', strtotime($flight_date . ' +3 months'));

        $this->assertSame($expected, $this->until($this->flight($flight_date), date('Y-m-d H:i:s')));
    }

    /**
     * Scénario: La date du vol prime sur la date de dépôt
     *   Étant donné un vol de découverte effectué il y a un an
     *   Et son briefing déposé aujourd'hui
     *   Quand un administrateur envisage de le supprimer
     *   Alors le briefing n'est plus protégé
     */
    public function testFlightDateTakesPrecedenceOverDepositDate()
    {
        $flight = $this->flight(date('Y-m-d', strtotime('-1 year')));

        $this->assertNull($this->until($flight, date('Y-m-d H:i:s')));
    }

    /**
     * Scénario: Un briefing sans vol est protégé à partir de son dépôt
     *   Étant donné un briefing rempli sans vol de découverte, il y a un mois
     *   Quand un administrateur envisage de le supprimer
     *   Alors le briefing est protégé jusqu'à trois mois après son dépôt
     */
    public function testBriefingWithoutFlightIsProtectedFromItsDeposit()
    {
        $submitted = date('Y-m-d', strtotime('-1 month'));

        $this->assertSame(
            date('Y-m-d', strtotime($submitted . ' +3 months')),
            $this->until(null, $submitted . ' 10:00:00')
        );
    }

    /**
     * Scénario: Un vol sans date ne retire pas la protection
     *   Étant donné un vol de découverte dont la date n'est pas renseignée
     *   Et son briefing déposé aujourd'hui
     *   Quand un administrateur envisage de le supprimer
     *   Alors le briefing est protégé à partir de son dépôt
     */
    public function testFlightWithoutDateFallsBackOnDepositDate()
    {
        $flight = $this->flight(null);

        $this->assertSame(
            date('Y-m-d', strtotime('+3 months')),
            $this->until($flight, date('Y-m-d H:i:s'))
        );
    }

    /**
     * Scénario: Le délai de conservation écoulé, le briefing n'est plus protégé
     *   Étant donné un briefing rempli sans vol il y a quatre mois
     *   Quand un administrateur envisage de le supprimer
     *   Alors le briefing n'est plus protégé
     */
    public function testBriefingIsNoLongerProtectedAfterThreeMonths()
    {
        $this->assertNull($this->until(null, date('Y-m-d', strtotime('-4 months')) . ' 10:00:00'));
    }

    /**
     * Scénario: Les réponses aux autres formulaires ne sont pas concernées
     *   Étant donné une réponse récente à un formulaire qui n'est pas un briefing
     *   Quand un administrateur envisage de la supprimer
     *   Alors aucune protection ne s'applique
     */
    public function testOtherFormsAreNotProtected()
    {
        $other = array('id' => 0, 'public_slug' => 'autre-formulaire');

        $this->assertSame(array(), $this->retention->protected_until($other, array($this->submission(null, date('Y-m-d H:i:s')))));
        $this->assertFalse($this->retention->form_has_protected_submissions($other));
    }

    /**
     * Scénario: Un formulaire contenant un briefing à conserver reste protégé
     *   Étant donné le formulaire de briefing
     *   Et un briefing rempli aujourd'hui
     *   Quand un administrateur envisage de supprimer le formulaire
     *   Alors la suppression n'est pas permise
     */
    public function testFormHoldingProtectedBriefingIsProtected()
    {
        $id = $this->CI->form_submissions_model->create_submission(array('form_id' => $this->form['id']));
        $this->trace_submission_ids[] = $id;

        $this->assertTrue($this->retention->form_has_protected_submissions($this->form));
    }

    /**
     * Scénario: La suppression d'un briefing protégé laisse une trace
     *   Étant donné un briefing à conserver
     *   Quand un administrateur le supprime en donnant un motif
     *   Alors le briefing n'existe plus
     *   Et son auteur et son motif restent enregistrés
     */
    public function testDeletingProtectedBriefingKeepsATrace()
    {
        $flight = $this->flight(date('Y-m-d'));
        $id = $this->CI->form_submissions_model->create_submission(array(
            'form_id'      => $this->form['id'],
            'subject_type' => 'vols_decouverte',
            'subject_id'   => $flight,
        ));
        $this->trace_submission_ids[] = $id;
        $submission = $this->CI->form_submissions_model->get_by_id($id);

        $deleted = $this->CI->form_submissions_model->delete_protected_submission(
            $submission, '2099-01-01', 'Déposé sur le mauvais vol', 'phpunit'
        );

        $this->assertTrue($deleted);
        $this->assertFalse($this->CI->form_submissions_model->get_by_id($id));
        $trace = $this->CI->db->where('submission_id', $id)->get('form_submission_deletions')->row_array();
        $this->assertSame('Déposé sur le mauvais vol', $trace['reason']);
        $this->assertSame('phpunit', $trace['created_by']);
        $this->assertEquals($flight, $trace['subject_id']);
    }
}
