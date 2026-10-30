<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/bin/gen_test_docs.php';

/**
 * @feature Documentation des tests
 */
class GenTestDocsTest extends TestCase
{
    private function features()
    {
        return array(
            array('name' => 'Acceptation', 'keywords' => array('acceptance')),
            array('name' => 'Socle', 'keywords' => array('.')),
        );
    }

    /**
     * Scénario: Les features et leur ordre viennent de la liste documentée
     *   Étant donné une liste de features dans les instructions du projet
     *   Quand la documentation est générée
     *   Alors chaque feature de la liste est reprise dans le même ordre
     *   Et le texte placé hors de la liste est ignoré
     */
    public function testLoadFeatures_ReadsOnlyTheMarkedTable()
    {
        $markdown = "| Hors | `liste` |\n<!-- test-features:start -->\n"
            . "| Feature | Mots-clés des fichiers |\n|---|---|\n"
            . "| Vols | `vols` `planeur` |\n| Socle | `.` |\n<!-- test-features:end -->\n";

        $this->assertEquals(array(
            array('name' => 'Vols', 'keywords' => array('vols', 'planeur')),
            array('name' => 'Socle', 'keywords' => array('.')),
        ), td_load_features($markdown));
    }

    /**
     * Scénario: Un test sans feature déclarée rejoint la feature correspondant à son fichier
     *   Étant donné un test sans feature déclarée
     *   Quand son fichier correspond à plusieurs features de la liste
     *   Alors il appartient à la première de ces features
     *   Et un fichier sans correspondance rejoint la feature de repli
     */
    public function testClassify_UsesFirstMatchingFeature()
    {
        $this->assertEquals('Acceptation', td_classify('mysql/AcceptanceItemsModelTest.php', $this->features()));
        $this->assertEquals('Socle', td_classify('unit/helpers/CsvHelperTest.php', $this->features()));
    }

    /**
     * Scénario: Un test PHPUnit documenté affiche sa feature et son scénario
     *   Étant donné un test PHPUnit décrit par une feature et un scénario
     *   Quand la documentation est générée
     *   Alors le test apparaît avec cette feature et ce scénario
     */
    public function testParsePhp_ReadsFeatureAndScenario()
    {
        $source = <<<'PHP'
<?php
class ExampleTest {
    /**
     * @feature Acceptation
     * Scénario: Un cas
     *   Étant donné un contexte
     *   Alors un résultat
     * @see autre
     */
    public function testSomething() {}
}
PHP;
        $tests = td_parse_php($source);

        $this->assertCount(1, $tests);
        $this->assertEquals('testSomething', $tests[0]['name']);
        $this->assertEquals('Acceptation', $tests[0]['feature']);
        $this->assertEquals("Scénario: Un cas\nÉtant donné un contexte\nAlors un résultat", $tests[0]['scenario']);
    }

    /**
     * Scénario: Seuls les vrais tests PHPUnit sont documentés
     *   Étant donné un fichier de test contenant aussi des fonctions utilitaires
     *   Quand la documentation est générée
     *   Alors seuls les tests y figurent
     */
    public function testParsePhp_IgnoresHelpers()
    {
        $source = '<?php class T {
            public function testKept() {}
            public static function testResult() {}
            protected function testHelper() {}
            public function setUp() {}
        }';

        $this->assertEquals(array('testKept'), array_column(td_parse_php($source), 'name'));
    }

    /**
     * Scénario: Une feature peut être déclarée une fois pour tout un fichier
     *   Étant donné un fichier dont la feature est déclarée une seule fois
     *   Quand la documentation est générée
     *   Alors tous ses tests appartiennent à cette feature
     *   Mais un test peut déclarer sa propre feature
     */
    public function testParsePhp_AppliesFileFeature()
    {
        $source = '<?php
        /** @feature Acceptation */
        class T {
            public function testA() {}
            /** @feature Socle */
            public function testB() {}
        }';

        $this->assertEquals(array('Acceptation', 'Socle'), array_column(td_parse_php($source), 'feature'));
    }

    /**
     * Scénario: Un test Playwright documenté affiche sa feature et son scénario
     *   Étant donné un test de bout en bout décrit par une feature et un scénario
     *   Quand la documentation est générée
     *   Alors le test apparaît avec cette feature et ce scénario
     *   Et il est localisé à sa ligne dans le fichier
     */
    public function testParseJs_ReadsFeatureAndScenario()
    {
        $source = "const a = 1;\n/**\n * @feature Acceptation\n * Scénario: Un cas\n *   Alors un résultat\n */\n"
            . "test('l\\'admin voit la page', async ({ page }) => {\n});\n";

        $tests = td_parse_js($source);

        $this->assertCount(1, $tests);
        $this->assertEquals("l'admin voit la page", $tests[0]['name']);
        $this->assertEquals(7, $tests[0]['line']);
        $this->assertEquals('Acceptation', $tests[0]['feature']);
        $this->assertEquals("Scénario: Un cas\nAlors un résultat", $tests[0]['scenario']);
    }

    /**
     * Scénario: Un saut de test à l'exécution n'est pas un test
     *   Étant donné un test qui se saute lui-même quand une donnée manque
     *   Quand la documentation est générée
     *   Alors seul le test lui-même est documenté
     *   Et un test désactivé d'avance reste documenté
     */
    public function testParseJs_IgnoresRuntimeSkip()
    {
        $source = "test('un test', async () => {\n    test.skip('Aucun avion');\n    test.skip(!ok, 'raison');\n});\n"
            . "test.skip('un test désactivé', async () => {});\n";

        $this->assertEquals(array('un test', 'un test désactivé'), array_column(td_parse_js($source), 'name'));
    }

    /**
     * Scénario: Un test sans scénario garde un titre lisible
     *   Étant donné un test sans scénario
     *   Quand la documentation est générée
     *   Alors son nom est présenté sous forme de phrase
     */
    public function testHumanize_MakesMethodNameReadable()
    {
        $this->assertEquals('Resolve targets - excludes member', td_humanize('testResolveTargets_ExcludesMember'));
        $this->assertEquals('un test déjà lisible', td_humanize('un test déjà lisible'));
    }

    /**
     * Scénario: La page reste une page valide quel que soit le texte des tests
     *   Étant donné un test dont le texte contient du balisage
     *   Quand la documentation est générée
     *   Alors ce texte ne peut pas casser la page
     */
    public function testRenderHtml_EscapesTestText()
    {
        $html = td_render_html(array(array(
            'feature' => 'Socle', 'type' => 'E2E', 'suite' => 'playwright', 'name' => '</script><b>',
            'title' => '</script><b>', 'file' => 'a.spec.js', 'line' => 1, 'scenario' => null,
        )), $this->features());

        $this->assertStringNotContainsString('</script><b>', $html);
        $this->assertStringNotContainsString('{{DATA}}', $html);
    }

    /**
     * Scénario: Chaque test du projet appartient à une feature de la liste
     *   Étant donné la liste de features du projet
     *   Quand tous les tests du projet sont parcourus
     *   Alors chacun appartient à une feature de cette liste
     */
    public function testRepository_EveryTestBelongsToAListedFeature()
    {
        $root = dirname(__DIR__, 4);
        $features = td_load_features(file_get_contents("$root/" . TD_FEATURES_FILE));
        $tests = td_collect($root, $features);

        $this->assertNotEmpty($features);
        $this->assertNotEmpty($tests);
        $this->assertEquals(array(), td_unknown_features($tests, $features));
    }
}
