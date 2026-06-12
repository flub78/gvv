<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests d'intégration pour la fusion de membres en doublon.
 *
 * Chaque test s'exécute dans une transaction annulée en tearDown() →
 * la base est toujours restaurée à son état initial.
 *
 * Données de test créées dynamiquement dans chaque test via _create_membre()
 * et _create_compte_411() pour rester indépendants de l'état réel de la DB.
 */
class MembresFusionTest extends TransactionalTestCase
{
    /** @var Membres_fusion_model */
    private $model;

    // Compteur pour générer des logins uniques
    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('membres_fusion_model');
        $this->CI->load->model('ecritures_model');
        $this->CI->load->helper('validation');
        $this->model = $this->CI->membres_fusion_model;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function _unique_login(string $prefix = 'tsrc'): string
    {
        return $prefix . '_' . getmypid() . '_' . (++self::$seq);
    }

    /**
     * Insère un membre minimal en base et retourne son mlogin.
     */
    private function _create_membre(array $overrides = []): string
    {
        $login = $this->_unique_login($overrides['mlogin_prefix'] ?? 'tm');
        unset($overrides['mlogin_prefix']);

        $defaults = [
            'mlogin'  => $login,
            'mnom'    => 'Nom_' . $login,
            'mprenom' => 'Prenom_' . $login,
            'memail'  => $login . '@test.invalid',
            'msexe'   => 'M',
            'actif'   => 1,
            'ext'     => 0,
            'm25ans'  => 0,
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('membres', $data);
        return $login;
    }

    /**
     * Crée un compte 411 pour un membre dans une section et retourne l'id du compte.
     */
    private function _create_compte_411(string $mlogin, int $club = 1): int
    {
        $this->CI->db->insert('comptes', [
            'nom'        => '(411) ' . $mlogin,
            'pilote'     => $mlogin,
            'codec'      => '411',
            'club'       => $club,
            'actif'      => 1,
            'debit'      => 0,
            'credit'     => 0,
            'saisie_par' => 'test',
        ]);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Crée une écriture entre deux comptes et retourne son id.
     */
    private function _create_ecriture(int $compte1, int $compte2, float $montant, int $club = 1): int
    {
        $this->CI->db->insert('ecritures', [
            'annee_exercise' => date('Y'),
            'date_creation'  => date('Y-m-d'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => $compte1,
            'compte2'        => $compte2,
            'montant'        => $montant,
            'description'    => 'test_fusion',
            'saisie_par'     => 'test',
            'club'           => $club,
        ]);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Retourne le solde d'un compte en DB (crédit - débit des écritures).
     * Utilise les mêmes colonnes que l'application.
     */
    private function _solde_compte_db(int $compte_id): float
    {
        $r1 = $this->CI->db->query(
            "SELECT COALESCE(SUM(montant),0) AS s FROM ecritures WHERE compte1=?",
            [$compte_id]
        )->row_array();
        $r2 = $this->CI->db->query(
            "SELECT COALESCE(SUM(montant),0) AS s FROM ecritures WHERE compte2=?",
            [$compte_id]
        )->row_array();
        return (float)$r2['s'] - (float)$r1['s'];
    }

    // -------------------------------------------------------------------------
    // Test 1 : Rapport d'analyse
    // -------------------------------------------------------------------------

    public function testAnalyseRapportComptesEtSoldes()
    {
        $src = $this->_create_membre(['mnom' => 'Source', 'ville' => 'Paris']);
        $dst = $this->_create_membre(['mnom' => 'Destination', 'ville' => '']);

        // Compte 411 source avec une écriture de 50 €
        $c_src = $this->_create_compte_411($src, 1);
        // Compte bancaire de test (n'importe quel compte non-pilote)
        $c_bank = $this->CI->db->query(
            "SELECT id FROM comptes WHERE codec != '411' LIMIT 1"
        )->row_array();
        if (!empty($c_bank)) {
            $this->_create_ecriture($c_bank['id'], $c_src, 50.0, 1);
        }

        $rapport = $this->model->analyse($src, $dst);

        $this->assertEquals($src, $rapport['source']['mlogin']);
        $this->assertEquals($dst, $rapport['destination']['mlogin']);

        // Au moins un compte source trouvé
        $this->assertArrayHasKey(1, $rapport['comptes_src']);

        // Vérification des champs : ville non nulle dans source, vide dans destination → will_copy
        $found = false;
        foreach ($rapport['fields_comparison'] as $fc) {
            if ($fc['field'] === 'ville') {
                $this->assertEquals('Paris', $fc['src_val']);
                $this->assertTrue($fc['will_copy'], 'ville doit être copiée (destination vide)');
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Le champ ville doit figurer dans fields_comparison');
    }

    // -------------------------------------------------------------------------
    // Test 2 : Fusion fiche membre — copie des champs vides
    // -------------------------------------------------------------------------

    public function testFusionFicheMembre()
    {
        $src = $this->_create_membre([
            'mnom'    => 'SourceNom',
            'ville'   => 'Lyon',
            'mtelf'   => '0600000001',
            'memail'  => null,
        ]);
        $dst = $this->_create_membre([
            'mnom'    => 'DestinationNom',
            'ville'   => '',
            'mtelf'   => '',
            'memail'  => null,
        ]);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success'], 'La fusion doit réussir');

        $dst_after = $this->model->get_membre($dst);
        $this->assertEquals('DestinationNom', $dst_after['mnom'], 'mnom destination doit être conservé');
        $this->assertEquals('Lyon', $dst_after['ville'], 'ville de source doit être copiée');
        $this->assertEquals('0600000001', $dst_after['mtelf'], 'mtelf de source doit être copié');
    }

    // -------------------------------------------------------------------------
    // Test 3 : Fusion fiche membre — les champs renseignés en destination ne bougent pas
    // -------------------------------------------------------------------------

    public function testFusionConserveDestination()
    {
        $src = $this->_create_membre(['ville' => 'Marseille', 'mtelf' => '0600000002']);
        $dst = $this->_create_membre(['ville' => 'Bordeaux',  'mtelf' => '0700000002']);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success']);

        $dst_after = $this->model->get_membre($dst);
        $this->assertEquals('Bordeaux',   $dst_after['ville'], 'ville destination conservée');
        $this->assertEquals('0700000002', $dst_after['mtelf'], 'mtelf destination conservé');
    }

    // -------------------------------------------------------------------------
    // Test 4 : Exhaustivité — aucune référence à source après fusion
    // -------------------------------------------------------------------------

    public function testExhaustiviteAucuneReferenceSourceApres()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        // Insérer quelques enregistrements référençant source
        $this->CI->db->insert('achats', [
            'pilote'     => $src,
            'quantite'   => 1,
            'prix'       => 10,
            'produit'    => 'test',
            'date'       => date('Y-m-d'),
            'saisie_par' => 'test',
            'club'       => 1,
        ]);
        $this->CI->db->insert('calendar', [
            'mlogin'         => $src,
            'start_datetime' => date('Y-m-d H:i:s'),
            'end_datetime'   => date('Y-m-d H:i:s'),
            'role'           => 'pilote',
            'commentaire'    => 'test',
        ]);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success']);

        // Aucun achats avec pilote = source
        $cnt_achats = $this->CI->db->where('pilote', $src)->count_all_results('achats');
        $this->assertEquals(0, $cnt_achats, 'Aucun achat ne doit rester sur le membre source');

        // Aucun calendar avec mlogin = source
        $cnt_cal = $this->CI->db->where('mlogin', $src)->count_all_results('calendar');
        $this->assertEquals(0, $cnt_cal, 'Aucune entrée calendar ne doit rester sur le membre source');

        // La fiche membre source est supprimée
        $this->assertNull($this->model->get_membre($src), 'La fiche membre source doit être supprimée');
    }

    // -------------------------------------------------------------------------
    // Test 5 : Conservation des soldes — compte 411 re-pointé (pas de conflit)
    // -------------------------------------------------------------------------

    public function testConservationSoldesCompte411SansConflit()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        // Source a un compte 411 section 1, destination n'en a pas
        $c_src = $this->_create_compte_411($src, 1);
        $c_bank = $this->CI->db->query("SELECT id FROM comptes WHERE codec != '411' LIMIT 1")->row_array();

        $solde_src_avant = 0.0;
        if (!empty($c_bank)) {
            $this->_create_ecriture($c_bank['id'], $c_src, 75.0, 1);
            $this->_create_ecriture($c_src, $c_bank['id'], 25.0, 1);
            $solde_src_avant = 75.0 - 25.0;
        }

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success'], 'La fusion doit réussir');

        // Le compte source doit maintenant pointer vers destination
        $compte_after = $this->CI->db->get_where('comptes', ['id' => $c_src])->row_array();
        $this->assertEquals($dst, $compte_after['pilote'], 'Le compte 411 doit être re-pointé vers destination');

        // Le solde du compte est préservé (les écritures n'ont pas bougé)
        if (!empty($c_bank)) {
            $solde_after = $this->_solde_compte_db($c_src);
            $this->assertEqualsWithDelta($solde_src_avant, $solde_after, 0.01, 'Le solde du compte doit être préservé');
        }
    }

    // -------------------------------------------------------------------------
    // Test 6 : Merge comptes 411 — les deux membres ont un compte dans la même section
    // -------------------------------------------------------------------------

    public function testMergeComptes411MemeSection()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        $c_src = $this->_create_compte_411($src, 1);
        $c_dst = $this->_create_compte_411($dst, 1);

        $c_bank = $this->CI->db->query("SELECT id FROM comptes WHERE codec != '411' LIMIT 1")->row_array();

        $solde_src_avant = 0.0;
        $solde_dst_avant = 0.0;

        if (!empty($c_bank)) {
            // Source : crédit 100, débit 30 → solde +70
            $this->_create_ecriture($c_bank['id'], $c_src, 100.0, 1);
            $this->_create_ecriture($c_src, $c_bank['id'], 30.0, 1);
            $solde_src_avant = 70.0;

            // Destination : crédit 50 → solde +50
            $this->_create_ecriture($c_bank['id'], $c_dst, 50.0, 1);
            $solde_dst_avant = 50.0;
        }

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success'], 'La fusion doit réussir');

        // Le compte source doit être supprimé
        $compte_src_after = $this->CI->db->get_where('comptes', ['id' => $c_src])->row_array();
        $this->assertEmpty($compte_src_after, 'Le compte 411 source doit être supprimé');

        // Les écritures du compte source sont maintenant sur le compte destination
        if (!empty($c_bank)) {
            $solde_dst_apres = $this->_solde_compte_db($c_dst);
            $expected = $solde_src_avant + $solde_dst_avant;
            $this->assertEqualsWithDelta($expected, $solde_dst_apres, 0.01,
                "Le solde destination après = $expected (src $solde_src_avant + dst $solde_dst_avant)");
        }
    }

    // -------------------------------------------------------------------------
    // Test 7 : Invariant comptable — le nombre total d'écritures est inchangé
    // -------------------------------------------------------------------------

    public function testInvariantNombreEcrituresTotal()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        $c_src = $this->_create_compte_411($src, 1);
        $c_dst = $this->_create_compte_411($dst, 1);

        $c_bank = $this->CI->db->query("SELECT id FROM comptes WHERE codec != '411' LIMIT 1")->row_array();
        if (empty($c_bank)) {
            $this->markTestSkipped('Aucun compte non-411 disponible pour les écritures de test');
        }

        $this->_create_ecriture($c_bank['id'], $c_src, 100.0, 1);
        $this->_create_ecriture($c_bank['id'], $c_dst, 50.0, 1);

        $r_avant = $this->CI->db->query("SELECT COUNT(*) as cnt FROM ecritures")->row_array();
        $total_avant = (int) $r_avant['cnt'];

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success']);

        $r_apres = $this->CI->db->query("SELECT COUNT(*) as cnt FROM ecritures")->row_array();
        $total_apres = (int) $r_apres['cnt'];
        $this->assertEquals($total_avant, $total_apres, 'Le nombre total d\'écritures doit être identique après fusion');
    }

    // -------------------------------------------------------------------------
    // Test 8 : Conflits d'unicité — formation_seances_participants
    // -------------------------------------------------------------------------

    public function testConflitsUniciteFormationSeancesParticipants()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        // Créer une séance fictive (uniquement les champs NOT NULL sans défaut)
        $this->CI->db->insert('formation_seances', [
            'date_seance'     => date('Y-m-d'),
            'instructeur_id'  => $dst,
            'pilote_id'       => $dst,
            'seance_theorique'=> 0,
        ]);
        $seance_id = (int) $this->CI->db->insert_id();

        if ($seance_id === 0) {
            $this->markTestSkipped('Impossible de créer une séance de formation (colonnes manquantes ?)');
        }

        // Les deux membres sont participants à la même séance → conflit UK
        $this->CI->db->insert('formation_seances_participants', [
            'seance_id' => $seance_id,
            'pilote_id' => $src,
        ]);
        $this->CI->db->insert('formation_seances_participants', [
            'seance_id' => $seance_id,
            'pilote_id' => $dst,
        ]);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success'], 'La fusion doit réussir même avec conflit de clé unique');

        // La ligne source (doublon) doit avoir été supprimée, destination conservée
        $count_dst = $this->CI->db->where('seance_id', $seance_id)->where('pilote_id', $dst)
                                   ->count_all_results('formation_seances_participants');
        $this->assertEquals(1, $count_dst, 'L\'entrée destination doit être conservée');

        $count_src = $this->CI->db->where('seance_id', $seance_id)->where('pilote_id', $src)
                                   ->count_all_results('formation_seances_participants');
        $this->assertEquals(0, $count_src, 'L\'entrée source en doublon doit être supprimée');
    }

    // -------------------------------------------------------------------------
    // Test 9 : Atomicité — si la transaction échoue, rien n'est modifié
    // -------------------------------------------------------------------------

    public function testAtomiciteFusionRollbackSurErreur()
    {
        $src = $this->_create_membre(['ville' => 'Nantes']);
        $dst = $this->_create_membre(['ville' => '']);

        // Appeler fusionner avec un membre source inexistant → doit retourner success=false
        $result = $this->model->fusionner($src . '_inexistant', $dst);
        $this->assertFalse($result['success'], 'La fusion avec source inexistante doit échouer');

        // La fiche destination doit être intacte
        $dst_after = $this->model->get_membre($dst);
        $this->assertNotNull($dst_after, 'Le membre destination doit rester intact');
        $this->assertEquals('', $dst_after['ville'] ?? '', 'La ville destination ne doit pas avoir changé');
    }

    // -------------------------------------------------------------------------
    // Test 10 : membre_payeur propagé vers destination
    // -------------------------------------------------------------------------

    public function testMembrePayeurPropagation()
    {
        $src   = $this->_create_membre();
        $dst   = $this->_create_membre();
        // Tiers membre dont membre_payeur pointe vers source
        $tiers = $this->_create_membre(['mlogin_prefix' => 'tiers']);

        $this->CI->db->where('mlogin', $tiers)->update('membres', ['membre_payeur' => $src]);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success']);

        $tiers_after = $this->model->get_membre($tiers);
        $this->assertEquals($dst, $tiers_after['membre_payeur'],
            'membre_payeur doit être mis à jour vers destination');
    }

    // -------------------------------------------------------------------------
    // Test 11 : Réaffectation events (cotisations)
    // -------------------------------------------------------------------------

    public function testReaffectationEvents()
    {
        $src = $this->_create_membre();
        $dst = $this->_create_membre();

        // Insérer une cotisation pour source
        $this->CI->db->insert('events', [
            'emlogin' => $src,
            'etype'   => 1,
            'edate'   => date('Y-m-d'),
            'year'    => (int) date('Y'),
        ]);

        $result = $this->model->fusionner($src, $dst);
        $this->assertTrue($result['success']);

        $cnt_src = $this->CI->db->where('emlogin', $src)->count_all_results('events');
        $cnt_dst = $this->CI->db->where('emlogin', $dst)->count_all_results('events');

        $this->assertEquals(0, $cnt_src, 'L\'événement source doit être réaffecté');
        $this->assertGreaterThan(0, $cnt_dst, 'L\'événement doit exister sur destination');
    }

    // -------------------------------------------------------------------------
    // Test 12 : Accès restreint — contrôle de la structure du contrôleur
    // -------------------------------------------------------------------------

    public function testAccesRestreintDevUserStructure()
    {
        $controller_file = APPPATH . 'controllers/membres_fusion.php';
        $this->assertFileExists($controller_file, 'Le contrôleur membres_fusion doit exister');

        $source = file_get_contents($controller_file);

        // Le contrôleur doit appeler _check_dev_user() dans le constructeur
        $this->assertStringContainsString(
            '$this->_check_dev_user();',
            $source,
            'Le constructeur doit appeler _check_dev_user()'
        );

        // La méthode _check_dev_user doit appeler show_error avec 403
        $this->assertRegExp(
            '/show_error\(.*403\)/',
            $source,
            '_check_dev_user doit déclencher une erreur 403 pour les non-autorisés'
        );

        // La vérification doit utiliser dev_users depuis la config
        $this->assertStringContainsString(
            "config->item('dev_users')",
            $source,
            '_check_dev_user doit lire la liste dev_users depuis la config'
        );
    }

    // -------------------------------------------------------------------------
    // Test 12b : Dashboard — la carte fusion est présente dans la vue
    // -------------------------------------------------------------------------

    public function testDashboardContientCarteFusion()
    {
        $dashboard_file = APPPATH . 'views/bs_sub_dashboard.php';
        $this->assertFileExists($dashboard_file, 'La vue sub-dashboard doit exister');

        $source = file_get_contents($dashboard_file);

        $this->assertStringContainsString(
            'membres_fusion',
            $source,
            'Le dashboard doit contenir un lien vers membres_fusion'
        );
        $this->assertStringContainsString(
            'db_card_fusion_membres',
            $source,
            'Le dashboard doit utiliser la clé de langue db_card_fusion_membres'
        );
    }

    // -------------------------------------------------------------------------
    // Test 13 : Analyse correcte quand aucune référence n'existe
    // -------------------------------------------------------------------------

    public function testAnalyseMembresVierges()
    {
        $src = $this->_create_membre(['ville' => 'Toulouse']);
        $dst = $this->_create_membre(['ville' => '']);

        $rapport = $this->model->analyse($src, $dst);

        $this->assertIsArray($rapport);
        $this->assertArrayHasKey('fields_comparison', $rapport);
        $this->assertArrayHasKey('references', $rapport);
        $this->assertArrayHasKey('soldes', $rapport);
        $this->assertArrayHasKey('conflicts', $rapport);

        // Pas de conflits attendus pour deux membres sans données
        $this->assertEmpty($rapport['conflicts'], 'Aucun conflit attendu pour des membres vierges');
        // Pas de références attendues
        $this->assertEmpty($rapport['references'], 'Aucune référence attendue pour des membres vierges');
    }
}
