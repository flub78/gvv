<?php

use PHPUnit\Framework\TestCase;

/**
 * Création rapide d'un aérodrome depuis les formulaires de saisie de vol
 * (bouton "+" à côté des sélecteurs d'aérodrome).
 *
 * Composants de la fonctionnalité :
 *   - Contrôleur   : Terrains::ajax_create() — endpoint JSON
 *   - Vue modale   : application/views/terrains/bs_ajax_modal.php
 *   - Script       : assets/javascript/terrain_modal.js
 *   - Intégrations : application/views/vols_avion/bs_formView.php
 *                    application/views/vols_planeur/bs_formView.php
 *   - Traductions  : application/language/{french,english,dutch}/terrains_lang.php
 *
 * Ces tests vérifient, par inspection du code source (même approche que
 * VolsAvionCreateAndContinueTest), que tous les points d'intégration sont en
 * place. Le comportement HTTP de bout en bout (ouverture de la modale, appel
 * AJAX, ajout et sélection de l'option) est couvert par le test Playwright
 * playwright/tests/terrain-ajout-depuis-saisie-vol.spec.js.
 *
 * @covers Terrains::ajax_create
 */
class TerrainsAjaxCreateTest extends TestCase
{
    private function controller()
    {
        return file_get_contents(APPPATH . 'controllers/terrains.php');
    }

    /**
     * Le contrôleur expose la méthode AJAX et son constructeur élargit
     * l'autorisation aux planchistes pour cette seule méthode.
     */
    public function test_controleur_expose_ajax_create_avec_autorisation_elargie()
    {
        $source = $this->controller();

        $this->assertStringContainsString('function ajax_create()', $source);
        $this->assertRegExp(
            "/fetch_method\\(\\)\\s*===\\s*'ajax_create'/",
            $source,
            "Le constructeur doit tester la méthode courante avant d'élargir l'autorisation"
        );
        $this->assertStringContainsString(
            "\$this->require_roles(['planchiste', 'ca', 'bureau', 'tresorier'])",
            $source,
            'ajax_create doit être accessible aux planchistes'
        );
        $this->assertStringContainsString(
            "\$this->require_roles(['ca', 'bureau', 'tresorier'])",
            $source,
            'Le CRUD terrains standard doit rester réservé à ca / bureau / trésorier'
        );
    }

    /**
     * ajax_create renvoie du JSON : succès applicatif, HTTP 422 pour une
     * erreur de saisie, HTTP 500 pour un échec d'enregistrement.
     */
    public function test_ajax_create_repond_en_json()
    {
        $source = $this->controller();

        $this->assertStringContainsString("set_content_type('application/json')", $source);
        // 422 doit être passé avec son texte : CI 2.x ne connaît pas ce code et
        // déclenche sinon une page d'erreur HTML 500 (show_error).
        $this->assertRegExp("/set_status_header\\(422,\\s*'[^']+'\\)/", $source);
        $this->assertRegExp('/set_status_header\(500/', $source);
        $this->assertRegExp('/[\'"]success[\'"]\s*=>\s*true/', $source);
        $this->assertRegExp('/[\'"]success[\'"]\s*=>\s*false/', $source);
        $this->assertStringContainsString("'label'", $source);
    }

    /**
     * Validation côté serveur : OACI obligatoire, longueur max, unicité,
     * nom obligatoire, fréquences numériques, et rattrapage du doublon SQL
     * (code 1062).
     */
    public function test_ajax_create_valide_les_entrees()
    {
        $source = $this->controller();

        $this->assertStringContainsString('gvv_terrains_ajax_error_oaci_required', $source);
        $this->assertStringContainsString('gvv_terrains_ajax_error_oaci_too_long', $source);
        $this->assertStringContainsString('gvv_terrains_ajax_error_oaci_exists', $source);
        $this->assertStringContainsString('gvv_terrains_ajax_error_nom_required', $source);
        $this->assertStringContainsString('gvv_terrains_ajax_error_freq_invalid', $source);

        $this->assertStringContainsString("get_by_id('oaci', \$oaci)", $source);
        $this->assertRegExp('/mb_strlen\(\$oaci\)\s*>\s*10/', $source);
        $this->assertRegExp('/\$code\s*==\s*1062/', $source);
    }

    /**
     * Les deux formulaires de saisie de vol incluent la modale et affichent
     * un bouton "+" ciblant chacun des quatre sélecteurs d'aérodrome.
     */
    public function test_formulaires_de_vol_integrent_le_bouton_et_la_modale()
    {
        $avion   = file_get_contents(APPPATH . 'views/vols_avion/bs_formView.php');
        $planeur = file_get_contents(APPPATH . 'views/vols_planeur/bs_formView.php');

        foreach (array('avion' => $avion, 'planeur' => $planeur) as $label => $source) {
            $this->assertStringContainsString(
                'terrains/bs_ajax_modal',
                $source,
                "Le formulaire $label doit inclure la vue modale terrains/bs_ajax_modal"
            );
            $this->assertStringContainsString(
                'js-add-terrain',
                $source,
                "Le formulaire $label doit afficher le bouton d'ajout d'aérodrome"
            );
        }

        $this->assertStringContainsString('data-target="valieudeco"', $avion);
        $this->assertStringContainsString('data-target="valieuatt"', $avion);
        $this->assertStringContainsString('data-target="vplieudeco"', $planeur);
        $this->assertStringContainsString('data-target="vplieuatt"', $planeur);

        // La modale reçoit la liste des sélecteurs à mettre à jour
        $this->assertStringContainsString("'terrain_modal_selects' => array('vplieudeco', 'vplieuatt')", $planeur);
        $this->assertStringContainsString("array('valieudeco', 'valieuatt')", $avion);
    }

    /**
     * La modale et le script existent, et la modale ne contient pas de
     * balise <form> (elle est placée hors du formulaire de saisie de vol).
     */
    public function test_modale_et_script_presents()
    {
        $modal_path = APPPATH . 'views/terrains/bs_ajax_modal.php';
        $this->assertFileExists($modal_path);
        $this->assertFileExists(FCPATH . 'assets/javascript/terrain_modal.js');

        $modal = file_get_contents($modal_path);
        $this->assertStringContainsString('id="terrainModal"', $modal);
        $this->assertStringContainsString("site_url('terrains/ajax_create')", $modal);
        // La modale est placée hors du <form> de saisie de vol : pas de <form> imbriqué.
        $modal_no_comments = preg_replace('#/\*.*?\*/#s', '', $modal);
        $this->assertDoesNotMatchRegExpCompat('/<form[\s>]/', $modal_no_comments, 'La modale ne doit pas contenir de <form> imbriqué');
        $this->assertStringNotContainsString('form_open', $modal_no_comments);

        $js = file_get_contents(FCPATH . 'assets/javascript/terrain_modal.js');
        $this->assertStringContainsString('js-add-terrain', $js);
        $this->assertStringContainsString('bootstrap.Modal', $js);
        $this->assertStringContainsString("trigger('change')", $js);
    }

    /**
     * Toutes les clés de langue de la fonctionnalité sont présentes et non
     * vides dans les trois langues supportées.
     */
    public function test_traductions_presentes_dans_les_trois_langues()
    {
        $keys = array(
            'gvv_terrains_ajax_add_title',
            'gvv_terrains_ajax_modal_title',
            'gvv_terrains_ajax_help',
            'gvv_terrains_ajax_submit',
            'gvv_terrains_ajax_cancel',
            'gvv_terrains_ajax_error_oaci_required',
            'gvv_terrains_ajax_error_oaci_too_long',
            'gvv_terrains_ajax_error_oaci_exists',
            'gvv_terrains_ajax_error_nom_required',
            'gvv_terrains_ajax_error_freq_invalid',
            'gvv_terrains_ajax_error_save',
            'gvv_terrains_ajax_error_network',
        );

        foreach (array('french', 'english', 'dutch') as $language) {
            $lang = array();
            require(APPPATH . 'language/' . $language . '/terrains_lang.php');
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $lang, "Clé $key manquante en $language");
                $this->assertNotEmpty($lang[$key], "Clé $key vide en $language");
            }
        }
    }

    /**
     * assertDoesNotMatchRegularExpression n'existe pas en PHPUnit 8.5.
     */
    private function assertDoesNotMatchRegExpCompat($pattern, $string, $message = '')
    {
        $this->assertSame(0, preg_match($pattern, $string), $message);
    }
}
