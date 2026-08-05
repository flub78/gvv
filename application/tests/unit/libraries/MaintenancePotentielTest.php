<?php
/**
 * Tests unitaires — Maintenance_potentiel::calculer_etat()
 *
 * Couvre la logique pure de calcul d'etat (aucun acces base de donnees
 * necessaire au-dela du seuil de configuration, qui retombe sur sa
 * valeur par defaut de 30 jours sous ce bootstrap minimal).
 *
 * Les methodes touchant reellement la base (appliquer_operation,
 * etat_pire_cas, mise_a_jour_manuelle) sont testees avec des donnees
 * reelles dans application/tests/mysql/MaintenancePotentielTest.php.
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 3.1, 9.1)
 */

use PHPUnit\Framework\TestCase;

class MaintenancePotentielTest extends TestCase
{
    private $potentiel;

    protected function setUp(): void
    {
        parent::setUp();

        $CI = &get_instance();
        $CI->load->library('Maintenance_potentiel');
        $this->potentiel = $CI->maintenance_potentiel;
    }

    public function testSeuilEcheanceProcheParDefautEst30Jours()
    {
        $this->assertSame(30, $this->potentiel->seuil_echeance_proche_jours());
    }

    public function testEtatAJourQuandAucuneDonneeSuivie()
    {
        $dossier = array('echeance_courante' => null, 'heures_restantes_courant' => null);
        $this->assertSame('a_jour', $this->potentiel->calculer_etat($dossier));
    }

    public function testEcheanceDepasseeDeUnJourEstDepasse()
    {
        $dossier = array('echeance_courante' => date('Y-m-d', strtotime('-1 day')));
        $this->assertSame('depasse', $this->potentiel->calculer_etat($dossier));
    }

    public function testEcheanceAujourdhuiEstEcheanceProche()
    {
        $dossier = array('echeance_courante' => date('Y-m-d'));
        $this->assertSame('echeance_proche', $this->potentiel->calculer_etat($dossier));
    }

    public function testEcheanceExactementAuSeuilEstEcheanceProche()
    {
        $dossier = array('echeance_courante' => date('Y-m-d', strtotime('+30 days')));
        $this->assertSame('echeance_proche', $this->potentiel->calculer_etat($dossier));
    }

    public function testEcheanceJusteAuDessusDuSeuilEstAJour()
    {
        $dossier = array('echeance_courante' => date('Y-m-d', strtotime('+31 days')));
        $this->assertSame('a_jour', $this->potentiel->calculer_etat($dossier));
    }

    public function testEcheanceLointaineEstAJour()
    {
        $dossier = array('echeance_courante' => date('Y-m-d', strtotime('+200 days')));
        $this->assertSame('a_jour', $this->potentiel->calculer_etat($dossier));
    }

    public function testPotentielHoraireNegatifEstDepasse()
    {
        $dossier = array('heures_restantes_courant' => -0.5);
        $this->assertSame('depasse', $this->potentiel->calculer_etat($dossier));
    }

    public function testPotentielHoraireNulEstAJour()
    {
        $dossier = array('heures_restantes_courant' => 0);
        $this->assertSame('a_jour', $this->potentiel->calculer_etat($dossier));
    }

    public function testPotentielHorairePositifEstAJour()
    {
        $dossier = array('heures_restantes_courant' => 42.5);
        $this->assertSame('a_jour', $this->potentiel->calculer_etat($dossier));
    }

    public function testEtatGlobalRetientLePireDesDeuxDimensions()
    {
        // Echeance a jour mais potentiel horaire depasse -> le pire l'emporte
        $dossier = array(
            'echeance_courante' => date('Y-m-d', strtotime('+200 days')),
            'heures_restantes_courant' => -1,
        );
        $this->assertSame('depasse', $this->potentiel->calculer_etat($dossier));

        // Echeance proche mais potentiel horaire correct -> echeance_proche l'emporte
        $dossier2 = array(
            'echeance_courante' => date('Y-m-d', strtotime('+5 days')),
            'heures_restantes_courant' => 10,
        );
        $this->assertSame('echeance_proche', $this->potentiel->calculer_etat($dossier2));
    }
}

/* End of file MaintenancePotentielTest.php */
/* Location: ./application/tests/unit/libraries/MaintenancePotentielTest.php */
