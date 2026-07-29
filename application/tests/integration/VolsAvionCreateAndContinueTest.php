<?php

use PHPUnit\Framework\TestCase;

/**
 * Régression : "Créer et faire une autre saisie" sur vols_avion/create doit
 * reprendre uniquement date, type de vol et lieux de décollage/atterrissage
 * du vol qui vient d'être créé, et repartir des valeurs par défaut pour tous
 * les autres champs (la machine n'étant pas reprise, les horamètres
 * s'initialisent alors correctement via le widget JS horametres_last_data).
 *
 * Contrairement à vols_planeur (qui rejoue le "dernier vol de l'année" en
 * base), vols_avion transmet les valeurs à garder via flashdata, déposées
 * dans post_create() et consommées dans form_static_element() : ce sont
 * précisément les valeurs du vol qui vient d'être créé, pas une requête sur
 * le dernier vol chronologique (qui pourrait différer en cas de saisie
 * rétroactive).
 *
 * Ces tests vérifient, par inspection du code source, que les trois points
 * d'intégration nécessaires sont bien en place (même style que
 * ComptaEditAuthorizationTest / VolsPlaneurCreateAndContinueTest) : le
 * comportement de bout en bout a été validé manuellement sur le serveur de
 * développement (gvv.net).
 *
 * @covers Vols_avion::post_create
 * @covers Vols_avion::form_static_element
 * @covers Gvv_Controller::formValidation
 */
class VolsAvionCreateAndContinueTest extends TestCase
{
    /**
     * post_create() doit déposer en flashdata exactement vadate, vacategorie,
     * valieudeco, valieuatt (pas vamacid/vapilid) quand le bouton est
     * "Créer et faire une autre saisie".
     */
    public function testPostCreateStoresKeepFieldsAsFlashdataForCreateAndContinue()
    {
        $controller_file = APPPATH . 'controllers/vols_avion.php';
        $source = file_get_contents($controller_file);

        $this->assertStringContainsString(
            "if (\$this->input->post('button') == \$this->lang->line('gvv_button_create_and_continue')) {",
            $source,
            "post_create() doit détecter le bouton 'Créer et faire une autre saisie'"
        );

        $this->assertRegExp(
            "/set_flashdata\\('vols_avion_keep', array\\(\\s*'vadate' => \\\$data\\['vadate'\\],\\s*'vacategorie' => \\\$data\\['vacategorie'\\],\\s*'valieudeco' => \\\$data\\['valieudeco'\\],\\s*'valieuatt' => \\\$data\\['valieuatt'\\],\\s*\\)\\);/",
            $source,
            "post_create() doit déposer vadate/vacategorie/valieudeco/valieuatt en flashdata, et rien d'autre (surtout pas vamacid ni vapilid)"
        );
    }

    /**
     * form_static_element() doit réinjecter la flashdata pour ces 4 champs en
     * mode CREATION, avant de retomber sur la logique d'aérodrome par défaut.
     */
    public function testFormStaticElementAppliesFlashdataOnCreation()
    {
        $controller_file = APPPATH . 'controllers/vols_avion.php';
        $source = file_get_contents($controller_file);

        $this->assertRegExp(
            "/if \\(\\\$action == CREATION\\) \\{.*?\\\$keep = \\\$this->session->flashdata\\('vols_avion_keep'\\);\\s*if \\(\\\$keep\\) \\{\\s*\\\$this->data\\['vadate'\\] = \\\$keep\\['vadate'\\];\\s*\\\$this->data\\['vacategorie'\\] = \\\$keep\\['vacategorie'\\];\\s*\\\$this->data\\['valieudeco'\\] = \\\$keep\\['valieudeco'\\];\\s*\\\$this->data\\['valieuatt'\\] = \\\$keep\\['valieuatt'\\];\\s*\\} else \\{/s",
            $source,
            "form_static_element() doit appliquer la flashdata vols_avion_keep en priorité en mode CREATION, avec repli sur l'aérodrome par défaut"
        );
    }

    /**
     * Gvv_Controller::formValidation() doit exclure la table volsa du
     * réaffichage en place, pour permettre la vraie redirection PRG de
     * validationOkPage() (sans quoi CodeIgniter réafficherait systématiquement
     * les champs texte à partir de $_POST via set_value(), quelle que soit la
     * valeur passée à input_field()).
     */
    public function testGvvControllerExcludesVolsaFromInPlaceRedisplay()
    {
        $controller_file = APPPATH . 'libraries/Gvv_Controller.php';
        $source = file_get_contents($controller_file);

        $this->assertStringContainsString(
            "\$table != 'achats' && \$table != 'volsp' && \$table != 'volsa'",
            $source,
            "formValidation() doit exclure la table volsa du réaffichage en place"
        );
    }
}
