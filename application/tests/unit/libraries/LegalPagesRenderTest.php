<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Pages publiques
 *
 * Construction des règles de confidentialité et des conditions d'utilisation
 * à partir des textes génériques et des informations du club.
 */
class LegalPagesRenderTest extends TestCase
{
    private $pages;

    private $club = array(
        'sigle_club' => 'ACA', 'nom_club' => 'Aéroclub d\'essai', 'adresse_club' => '1 rue du Terrain',
        'cp_club' => '80000', 'ville_club' => 'Abbeville', 'tel_club' => '0102030405',
        'email_club' => 'info@example.org', 'url_club' => 'https://example.org', 'contact_rgpd' => 'le président',
    );

    protected function setUp(): void
    {
        if (!class_exists('Parametres_store', false)) {
            require_once APPPATH . 'libraries/Parametres_store.php';
        }
        require_once APPPATH . 'libraries/Legal_pages.php';
        // render() et template() n'utilisent pas CodeIgniter : pas de constructeur.
        $this->pages = (new ReflectionClass('Legal_pages'))->newInstanceWithoutConstructor();
    }

    /**
     * Scénario: Les informations du club apparaissent dans le texte
     *   Étant donné un texte qui cite le nom et l'adresse e-mail du club
     *   Quand la page publique est construite
     *   Alors le texte affiche le nom et l'adresse e-mail réels du club
     */
    public function testRender_InsertsClubInformation()
    {
        $out = $this->pages->render('{nom_club} — {email_club} — {inconnu}', $this->club, array());
        $this->assertSame('Aéroclub d\'essai — info@example.org — {inconnu}', $out);
    }

    /**
     * Scénario: La mention des paiements en ligne dépend de leur activation
     *   Étant donné un texte avec un paragraphe sur les paiements en ligne
     *   Quand le paiement en ligne n'est activé dans aucune section
     *   Alors la page ne mentionne pas les paiements en ligne
     *   Mais quand il est activé, le paragraphe apparaît
     */
    public function testRender_ConditionalBlocks()
    {
        $text = "Début\n[[si paiements]]Paiements HelloAsso\n[[fin]]Fin";
        $this->assertSame("Début\nFin", $this->pages->render($text, $this->club, array('paiements' => FALSE)));
        $this->assertSame("Début\nPaiements HelloAsso\nFin", $this->pages->render($text, $this->club, array('paiements' => TRUE)));
        $this->assertSame("Début\nPaiements HelloAsso\nFin",
            $this->pages->render(str_replace('si paiements', 'si !paiements', $text), $this->club, array('paiements' => FALSE)));
    }

    /**
     * Scénario: Chaque langue dispose d'un texte générique complet
     *   Étant donné un club qui n'a saisi aucun texte
     *   Quand un visiteur consulte les règles de confidentialité ou les conditions d'utilisation en français, anglais ou néerlandais
     *   Alors un texte générique existe dans sa langue
     *   Et toutes les informations du club y sont remplacées
     */
    public function testTemplates_ExistForAllLanguagesAndUseKnownFields()
    {
        foreach (array('french', 'english', 'dutch') as $lang) {
            foreach (array_keys(Legal_pages::PAGES) as $page) {
                $file = APPPATH . "views/legal/$lang/$page.md";
                $this->assertFileExists($file);
                $out = $this->pages->render($this->pages->template($page, $lang), $this->club,
                    array('paiements' => TRUE, 'site' => TRUE, 'telephone' => TRUE));
                $this->assertNotRegExp('/\{\w+\}/', $out, "$lang/$page : information du club non remplacée");
                $this->assertStringNotContainsString('[[', $out, "$lang/$page : bloc conditionnel non fermé");
                $this->assertStringContainsString('Aéroclub d\'essai', $out);
            }
        }
    }

    /**
     * Scénario: Une langue sans texte générique utilise le français
     *   Étant donné une langue pour laquelle GVV ne fournit pas de texte
     *   Quand un visiteur consulte les règles de confidentialité dans cette langue
     *   Alors le texte générique français est affiché
     */
    public function testTemplate_FallsBackToFrench()
    {
        $this->assertSame($this->pages->template('confidentialite', 'french'),
            $this->pages->template('confidentialite', 'klingon'));
    }
}
