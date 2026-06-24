<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests smoke pour la fonctionnalité Vérification des carnets de route.
 *
 * Vérifie :
 * - L'existence et la structure des fichiers clés
 * - Le chargement du modèle et la requête en base
 * - Le bon fonctionnement du helper de continuité sur des données réelles
 */
class CarnetRouteSmokeTest extends TransactionalTestCase
{
    // -------------------------------------------------------------------------
    // Vérifications statiques des fichiers
    // -------------------------------------------------------------------------

    public function testControllerFileExists()
    {
        $this->assertFileExists(
            APPPATH . 'controllers/carnets_route.php',
            'Le contrôleur carnets_route.php doit exister'
        );
    }

    public function testModelFileExists()
    {
        $this->assertFileExists(
            APPPATH . 'models/carnets_route_model.php',
            'Le modèle carnets_route_model.php doit exister'
        );
    }

    public function testHelperFileExists()
    {
        $this->assertFileExists(
            APPPATH . 'helpers/carnets_route_helper.php',
            'Le helper carnets_route_helper.php doit exister'
        );
    }

    public function testViewFileExists()
    {
        $this->assertFileExists(
            APPPATH . 'views/carnets_route/bs_page.php',
            'La vue bs_page.php doit exister'
        );
    }

    public function testLanguageFilesExist()
    {
        foreach (['french', 'english', 'dutch'] as $lang) {
            $this->assertFileExists(
                APPPATH . "language/{$lang}/carnets_route_lang.php",
                "Le fichier de langue {$lang}/carnets_route_lang.php doit exister"
            );
        }
    }

    public function testControllerExtendsMyController()
    {
        $source = file_get_contents(APPPATH . 'controllers/carnets_route.php');
        $this->assertStringContainsString(
            'class Carnets_route extends MY_Controller',
            $source,
            'Le contrôleur doit étendre MY_Controller'
        );
    }

    public function testControllerRequiresAdminRoles()
    {
        $source = file_get_contents(APPPATH . 'controllers/carnets_route.php');
        $this->assertStringContainsString(
            "require_roles(['ca', 'club-admin'])",
            $source,
            "L'accès doit être restreint aux rôles ca et club-admin"
        );
    }

    public function testControllerHasPageMethod()
    {
        $source = file_get_contents(APPPATH . 'controllers/carnets_route.php');
        $this->assertStringContainsString('public function page()', $source);
    }

    public function testControllerHasCsvMethod()
    {
        $source = file_get_contents(APPPATH . 'controllers/carnets_route.php');
        $this->assertStringContainsString('public function csv()', $source);
    }

    public function testControllerHasPdfMethod()
    {
        $source = file_get_contents(APPPATH . 'controllers/carnets_route.php');
        $this->assertStringContainsString('public function pdf()', $source);
    }

    public function testMenuEntryExists()
    {
        $source = file_get_contents(APPPATH . 'views/bs_menu.php');
        $this->assertStringContainsString(
            "controller_url('carnets_route/page')",
            $source,
            "L'entrée menu vers carnets_route/page doit être présente dans bs_menu.php"
        );
    }

    public function testDashboardCardExists()
    {
        $source = file_get_contents(APPPATH . 'views/bs_sub_dashboard.php');
        $this->assertStringContainsString(
            "controller_url('carnets_route/page')",
            $source,
            "La carte dashboard vers carnets_route/page doit être présente dans bs_sub_dashboard.php"
        );
    }

    public function testViewUsesDatatableClass()
    {
        $source = file_get_contents(APPPATH . 'views/carnets_route/bs_page.php');
        $this->assertStringContainsString(
            'searchable_nosort_datatable',
            $source,
            'La vue doit utiliser la classe searchable_nosort_datatable'
        );
    }

    // -------------------------------------------------------------------------
    // Tests d'intégration avec la base de données
    // -------------------------------------------------------------------------

    public function testModelLoadsWithoutError()
    {
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Carnets_route_model')) {
            require_once APPPATH . 'models/carnets_route_model.php';
        }

        $model = new Carnets_route_model();
        $this->assertInstanceOf('Carnets_route_model', $model);
    }

    public function testGetAvionsReturnsArray()
    {
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Carnets_route_model')) {
            require_once APPPATH . 'models/carnets_route_model.php';
        }

        $model = new Carnets_route_model();
        $avions = $model->get_avions();
        $this->assertIsArray($avions, 'get_avions() doit retourner un tableau');
    }

    public function testGetFlightsReturnsArrayForValidPeriod()
    {
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Carnets_route_model')) {
            require_once APPPATH . 'models/carnets_route_model.php';
        }

        $model = new Carnets_route_model();
        $avions = $model->get_avions();

        if (empty($avions)) {
            $this->markTestSkipped('Aucun avion en base — test ignoré');
        }

        $macid = array_key_first($avions);
        $flights = $model->get_flights($macid, '2020-01-01', date('Y-m-d'));
        $this->assertIsArray($flights, 'get_flights() doit retourner un tableau');
    }

    // -------------------------------------------------------------------------
    // Tests du helper de continuité sur données réelles
    // -------------------------------------------------------------------------

    public function testHelperRunsOnRealFlights()
    {
        if (!function_exists('build_continuity_rows')) {
            require_once APPPATH . 'helpers/carnets_route_helper.php';
        }
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Carnets_route_model')) {
            require_once APPPATH . 'models/carnets_route_model.php';
        }

        $model = new Carnets_route_model();
        $avions = $model->get_avions();

        if (empty($avions)) {
            $this->markTestSkipped('Aucun avion en base — test ignoré');
        }

        $macid   = array_key_first($avions);
        $flights = $model->get_flights($macid, '2020-01-01', date('Y-m-d'));
        $rows    = build_continuity_rows($flights);

        $this->assertIsArray($rows);

        foreach ($rows as $row) {
            $this->assertArrayHasKey('type', $row);
            $this->assertArrayHasKey('data', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertContains($row['type'], ['flight', 'gap', 'overlap', 'missing']);
            if ($row['type'] === 'flight') {
                $this->assertArrayHasKey('status', $row['data']);
                $this->assertContains($row['data']['status'], ['ok', 'error', 'missing']);
            }
        }
    }

    public function testSummaryDurationsAreRounded()
    {
        if (!function_exists('build_continuity_rows')) {
            require_once APPPATH . 'helpers/carnets_route_helper.php';
        }
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Carnets_route_model')) {
            require_once APPPATH . 'models/carnets_route_model.php';
        }

        $model = new Carnets_route_model();
        $avions = $model->get_avions();

        if (empty($avions)) {
            $this->markTestSkipped('Aucun avion en base — test ignoré');
        }

        $macid      = array_key_first($avions);
        $flights    = $model->get_flights($macid, '2020-01-01', date('Y-m-d'));
        $rows       = build_continuity_rows($flights);
        $anomalies  = array_filter($rows, fn($r) => $r['type'] !== 'flight');

        // Assertion inconditionnelle : la structure de rows est toujours valide
        $this->assertIsArray($rows, 'build_continuity_rows() doit retourner un tableau');

        foreach ($anomalies as $row) {
            $duration_str = (string)$row['duration'];
            $dot_pos = strpos($duration_str, '.');
            $decimals = $dot_pos === false ? 0 : strlen($duration_str) - $dot_pos - 1;
            $this->assertLessThanOrEqual(
                2,
                $decimals,
                "La durée {$duration_str} doit avoir au maximum 2 décimales"
            );
        }
    }
}
