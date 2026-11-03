<?php

require_once __DIR__ . '/TransactionalTestCase.php';

/**
 * @feature Configuration
 *
 * Droits, lecture et enregistrement des paramètres de la page Configuration,
 * sur la vraie base (transaction annulée après chaque test).
 */
class ParametresStoreTest extends TransactionalTestCase
{
    /** @var Parametres_store */
    private $store;

    private $registry = array(
        'phpunit.texte' => array('tab' => 'essai', 'store' => 'db', 'type' => 'markdown', 'label' => 'l',
            'read' => 'club-admin', 'write' => 'club-admin', 'per_lang' => TRUE, 'rules' => 'max_length[20]'),
        'phpunit.email' => array('tab' => 'essai', 'store' => 'db', 'type' => 'email', 'label' => 'l',
            'read' => 'ca', 'write' => 'club-admin', 'required' => TRUE),
        'phpunit.systeme' => array('tab' => 'systeme', 'store' => 'db', 'type' => 'text', 'label' => 'l',
            'read' => 'dev', 'write' => 'dev'),
    );

    private $tabs = array(
        'essai'   => array('icon' => '', 'label' => 'essai'),
        'systeme' => array('icon' => '', 'label' => 'systeme'),
    );

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->CI->db->field_exists('updated_at', 'configuration')) {
            $this->markTestSkipped('Migration 184 non appliquée');
        }
        require_once APPPATH . 'libraries/Config_file_writer.php';
        require_once APPPATH . 'libraries/Parametres_store.php';
        $this->CI->config_file_writer = new Config_file_writer();
        $this->store = new Parametres_store(array('registry' => $this->registry, 'tabs' => $this->tabs));
        $this->store->set_rank(2);
    }

    /**
     * Scénario: Chaque profil ne voit que les onglets qui le concernent
     *   Étant donné un onglet réservé aux développeurs et un onglet ouvert au CA
     *   Quand un membre du CA, un administrateur du club, un développeur ou un simple membre ouvre la configuration
     *   Alors le membre du CA et l'administrateur voient seulement l'onglet ouvert au CA
     *   Et le développeur voit les deux onglets
     *   Et le simple membre n'en voit aucun
     */
    public function testVisibleTabs_DependOnProfile()
    {
        $this->store->set_rank(1);
        $this->assertSame(array('essai'), array_keys($this->store->visible_tabs()));
        $this->assertFalse($this->store->can_write_tab('essai'));

        $this->store->set_rank(2);
        $this->assertSame(array('essai'), array_keys($this->store->visible_tabs()));
        $this->assertTrue($this->store->can_write_tab('essai'));

        $this->store->set_rank(3);
        $this->assertSame(array('essai', 'systeme'), array_keys($this->store->visible_tabs()));

        $this->store->set_rank(0);
        $this->assertSame(array(), $this->store->visible_tabs());
    }

    /**
     * Scénario: Un texte saisi par langue est relu dans sa langue, avec repli sur le français
     *   Étant donné un texte propre au club saisi seulement en français
     *   Quand on le consulte en anglais
     *   Alors le texte français est affiché
     *   Et il est daté et attribué à la personne qui l'a saisi
     */
    public function testSaveAndReadPerLanguage()
    {
        $res = $this->store->save('phpunit.texte', 'Bonjour', 'french');
        $this->assertSame('saved', $res['status']);

        $this->assertTrue($this->store->get('phpunit.texte', 'english')['is_default']);
        $fallback = $this->store->get_with_fallback('phpunit.texte', 'english');
        $this->assertSame('Bonjour', $fallback['value']);
        $this->assertNotEmpty($fallback['updated_at']);
        $this->assertSame($this->CI->dx_auth->get_username(), $fallback['updated_by']);
    }

    /**
     * Scénario: Effacer un texte revient au texte par défaut
     *   Étant donné un texte propre au club
     *   Quand l'administrateur vide le champ et enregistre
     *   Alors aucun texte propre au club n'est plus défini
     */
    public function testEmptyValueRestoresDefault()
    {
        $this->store->save('phpunit.texte', 'Bonjour', 'french');
        $this->assertSame('saved', $this->store->save('phpunit.texte', '', 'french')['status']);
        $this->assertTrue($this->store->get('phpunit.texte', 'french')['is_default']);
        $this->assertSame(0, $this->CI->db->where('cle', 'phpunit.texte')->count_all_results('configuration'));
    }

    /**
     * Scénario: Une valeur invalide est refusée avec son motif
     *   Étant donné une adresse e-mail obligatoire et un texte limité en longueur
     *   Quand l'administrateur saisit une adresse invalide ou un texte trop long
     *   Alors chaque saisie est refusée avec le motif
     *   Et rien n'est enregistré
     */
    public function testInvalidValuesAreRejected()
    {
        $this->assertSame('parametres_err_email', $this->store->save('phpunit.email', 'pas-une-adresse')['error']);
        $this->assertSame('parametres_err_max_length',
            $this->store->save('phpunit.texte', str_repeat('x', 21), 'french')['error']);
        $this->assertSame(0, $this->CI->db->where_in('cle', array_keys($this->registry))->count_all_results('configuration'));
    }

    /**
     * Scénario: Un membre du CA ne peut pas modifier un paramètre réservé à l'administrateur
     *   Étant donné un paramètre lisible par le CA mais modifiable seulement par l'administrateur du club
     *   Quand un membre du CA tente de l'enregistrer
     *   Alors la modification est refusée
     */
    public function testWriteForbiddenForLowerProfile()
    {
        $this->store->set_rank(1);
        $this->assertTrue($this->store->can_read('phpunit.email'));
        $this->assertSame('parametres_err_forbidden', $this->store->save('phpunit.email', 'a@example.org')['error']);
    }

    /**
     * Scénario: Enregistrer la valeur déjà en vigueur ne change rien
     *   Étant donné une adresse e-mail déjà enregistrée
     *   Quand l'administrateur enregistre la même adresse
     *   Alors aucune modification n'est signalée
     */
    public function testSameValueIsUnchanged()
    {
        $this->assertSame('saved', $this->store->save('phpunit.email', 'a@example.org')['status']);
        $this->assertSame('unchanged', $this->store->save('phpunit.email', 'a@example.org')['status']);
    }

    /**
     * Scénario: L'état d'un onglet signale un paramètre obligatoire manquant
     *   Étant donné un onglet contenant une adresse e-mail obligatoire
     *   Quand l'adresse n'est pas renseignée
     *   Alors l'onglet est incomplet
     *   Mais une fois l'adresse renseignée, il est complet
     *   Et un onglet où rien n'est renseigné ni obligatoire est optionnel
     */
    public function testTabStatus()
    {
        $this->assertSame('incomplete', $this->store->tab_status('essai'));
        $this->store->save('phpunit.email', 'a@example.org');
        $this->assertSame('complete', $this->store->tab_status('essai'));
        $this->assertSame('optional', $this->store->tab_status('systeme'));
    }

    /**
     * @feature Pages publiques
     * Scénario: Le texte propre au club remplace le texte générique
     *   Étant donné un club qui a saisi ses propres règles de confidentialité en français
     *   Quand un visiteur consulte la page
     *   Alors il voit le texte du club, avec ses informations insérées, et sa date de mise à jour
     *   Mais tant que rien n'est saisi, il voit le texte générique de GVV
     */
    public function testLegalPage_UsesClubTextOrGenericTemplate()
    {
        $this->CI->config->set_item('nom_club', 'Club PHPUnit');
        $this->CI->config->set_item('contact_rgpd', 'la secrétaire');
        require_once APPPATH . 'libraries/Legal_pages.php';
        $this->CI->load->helper('markdown');

        // Store réel (registre complet) pour la clé legal.confidentialite.
        $real = new Parametres_store();
        $real->set_rank(2);
        $this->CI->parametres_store = $real;
        $pages = (new ReflectionClass('Legal_pages'))->newInstanceWithoutConstructor();
        $prop = new ReflectionProperty('Legal_pages', 'CI');
        $prop->setAccessible(TRUE);
        $prop->setValue($pages, $this->CI);

        $existing = $real->get('legal.confidentialite', 'french');
        if (!$existing['is_default']) {
            $real->save('legal.confidentialite', '', 'french');
        }
        $generic = $pages->page('confidentialite', 'french');
        $this->assertFalse($generic['is_club_text']);
        $this->assertNull($generic['updated_at']);
        $this->assertStringContainsString('Club PHPUnit', $generic['html']);
        $this->assertStringContainsString('la secrétaire', $generic['html']);

        $real->save('legal.confidentialite', "# Règles de {nom_club}\nTexte du club", 'french');
        $club = $pages->page('confidentialite', 'french');
        $this->assertTrue($club['is_club_text']);
        $this->assertNotEmpty($club['updated_at']);
        $this->assertStringContainsString('<h1>Règles de Club PHPUnit</h1>', $club['html']);
    }
}
