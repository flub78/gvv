<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Régression : "Créer et faire une autre saisie" sur vols_planeur/create doit
 * reprendre uniquement date, moyen de lancement, altitude par défaut, remorqueur,
 * pilote remorqueur, type de vol et lieux de décollage/atterrissage pour la
 * saisie suivante, et repartir des valeurs par défaut pour tous les autres
 * champs (payeur en particulier).
 *
 * Le mécanisme utilisé est le prefill "dernier vol de l'année" de
 * Vols_planeur::form_static_element(), combiné à une vraie redirection HTTP
 * (Post-Redirect-Get) désormais activée pour la table volsp dans
 * Gvv_Controller::formValidation() — sans ce PRG, CodeIgniter réaffiche les
 * champs texte à partir de $_POST via set_value(), quelle que soit la valeur
 * passée à input_field().
 *
 * @covers Vols_planeur_model::latest_flight
 * @covers Vols_planeur::form_static_element
 * @covers Gvv_Controller::formValidation
 */
class VolsPlaneurCreateAndContinueTest extends TransactionalTestCase
{
    private $pilot_login;
    private $machine_immat;

    public function setUp(): void
    {
        parent::setUp();

        $this->CI->load->model('vols_planeur_model');

        $pilot = $this->CI->db->select('mlogin')->from('membres')
            ->where('actif', 1)->limit(1)->get()->row_array();
        $machine = $this->CI->db->select('mpimmat')->from('machinesp')
            ->where('actif', 1)->limit(1)->get()->row_array();

        if (empty($pilot) || empty($machine)) {
            $this->markTestSkipped('Aucun membre ou planeur actif trouvé en base pour ce test');
        }

        $this->pilot_login = $pilot['mlogin'];
        $this->machine_immat = $machine['mpimmat'];
    }

    private function insertVol(array $overrides = []): int
    {
        $defaults = [
            'vpdate'      => '2099-01-15',
            'vppilid'     => $this->pilot_login,
            'vpmacid'     => $this->machine_immat,
            'vpcdeb'      => '10.00',
            'vpcfin'      => '11.00',
            'vpduree'     => 60,
            'vpautonome'  => 3,
            'vpaltrem'    => 500,
            'vpcategorie' => 0,
            'vpdc'        => 0,
            'vpticcolle'  => 0,
            'facture'     => 0,
            'payeur'      => '',
            'pourcentage' => 0,
            'vplieudeco'  => 'LFOI',
            'vplieuatt'   => 'LFOI',
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('volsp', $data);
        $id = (int) $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id, "L'insertion du vol test doit réussir");
        return $id;
    }

    /**
     * latest_flight() doit désormais renvoyer le vrai lieu d'atterrissage du
     * vol (vplieuatt), pas seulement le lieu de décollage : un vol cross-country
     * peut décoller et atterrir sur deux terrains différents, et le formulaire
     * de saisie suivante doit reprendre les deux valeurs distinctement.
     */
    public function testLatestFlightReturnsRealLandingSiteAndCategory()
    {
        // Vol plus ancien : ne doit pas être repris.
        $this->insertVol([
            'vpdate'      => '2099-01-10',
            'vpcategorie' => 5,
            'vplieudeco'  => 'EBSG',
            'vplieuatt'   => 'EBSG',
        ]);

        // Vol le plus récent de l'année : décollage et atterrissage différents,
        // catégorie distincte du vol précédent.
        $this->insertVol([
            'vpdate'      => '2099-01-20',
            'vpcategorie' => 3,
            'vplieudeco'  => 'EBSG',
            'vplieuatt'   => 'EBSH',
        ]);

        $latest = $this->CI->vols_planeur_model->latest_flight(['year(vpdate)' => 2099]);

        $this->assertCount(1, $latest);
        $this->assertSame('EBSG', $latest[0]['vplieudeco']);
        $this->assertSame('EBSH', $latest[0]['vplieuatt'], 'vplieuatt doit être le vrai lieu d\'atterrissage du dernier vol, pas une copie de vplieudeco');
        $this->assertEquals(3, $latest[0]['vpcategorie'], 'vpcategorie (type de vol) doit être remonté par latest_flight()');
    }

    /**
     * Vols_planeur::form_static_element() doit copier vpcategorie et le vrai
     * vplieuatt (pas vplieudeco) depuis le dernier vol lors d'une création.
     */
    public function testFormStaticElementCopiesCategoryAndRealLandingSite()
    {
        $controller_file = APPPATH . 'controllers/vols_planeur.php';
        $source = file_get_contents($controller_file);

        $this->assertStringContainsString(
            "\$this->data ['vpcategorie'] = \$latestf [0] ['vpcategorie'];",
            $source,
            "form_static_element() doit reprendre vpcategorie (type de vol) du dernier vol"
        );

        $this->assertStringContainsString(
            "\$this->data ['vplieuatt'] = \$latestf [0] ['vplieuatt'];",
            $source,
            "form_static_element() doit reprendre le vrai lieu d'atterrissage, pas vplieudeco"
        );
    }

    /**
     * "Créer et faire une autre saisie" sur vols_planeur doit passer par une
     * vraie redirection HTTP (Post-Redirect-Get) plutôt que le réaffichage en
     * place utilisé par défaut par Gvv_Controller, sans quoi les champs texte
     * (payeur, altitude, observations...) resteraient affichés avec la valeur
     * postée via set_value(), quelle que soit la valeur passée à input_field().
     */
    public function testGvvControllerExcludesVolspFromInPlaceRedisplay()
    {
        $controller_file = APPPATH . 'libraries/Gvv_Controller.php';
        $source = file_get_contents($controller_file);

        $this->assertStringContainsString(
            "\$table != 'achats' && \$table != 'volsp'",
            $source,
            "formValidation() doit exclure la table volsp du réaffichage en place, pour permettre la vraie redirection PRG de validationOkPage()"
        );
    }
}
