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
        'phpunit.secret' => array('tab' => 'essai', 'store' => 'db', 'type' => 'password', 'label' => 'l',
            'read' => 'club-admin', 'write' => 'club-admin', 'secret' => TRUE),
        'phpunit.mode' => array('tab' => 'essai', 'store' => 'db', 'type' => 'select', 'label' => 'l',
            'options' => array('mail' => 'mail', 'smtp' => 'SMTP'), 'read' => 'ca', 'write' => 'club-admin'),
        'phpunit.serveur' => array('tab' => 'essai', 'store' => 'db', 'type' => 'text', 'label' => 'l',
            'read' => 'ca', 'write' => 'club-admin', 'required_if' => array('phpunit.mode' => 'smtp')),
        'phpunit.defaut' => array('tab' => 'defaut', 'store' => 'db', 'type' => 'text', 'label' => 'l',
            'read' => 'ca', 'write' => 'club-admin', 'required' => TRUE, 'default' => 'mail'),
        'phpunit.module' => array('tab' => 'systeme', 'store' => 'db', 'type' => 'text', 'label' => 'l',
            'read' => 'ca', 'write' => 'club-admin', 'requires' => array('phpunit.serveur')),
    );

    private $tabs = array(
        'essai'   => array('icon' => '', 'label' => 'essai'),
        'systeme' => array('icon' => '', 'label' => 'systeme'),
        'defaut'  => array('icon' => '', 'label' => 'defaut'),
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
     * Scénario: Chaque profil ne voit que les paramètres qui le concernent
     *   Étant donné un paramètre réservé aux développeurs, dans un onglet qui contient aussi un paramètre ouvert au CA
     *   Quand un membre du CA, un administrateur du club, un développeur ou un simple membre ouvre la configuration
     *   Alors le membre du CA et l'administrateur voient l'onglet, sans le paramètre réservé aux développeurs
     *   Et seul l'administrateur peut modifier l'onglet ouvert au CA
     *   Et le développeur voit tout
     *   Et le simple membre ne voit aucun onglet
     */
    public function testVisibleTabs_DependOnProfile()
    {
        $this->store->set_rank(1);
        $this->assertSame(array('essai', 'systeme', 'defaut'), array_keys($this->store->visible_tabs()));
        $this->assertFalse($this->store->can_write_tab('essai'));

        $this->store->set_rank(2);
        $this->assertSame(array('essai', 'systeme', 'defaut'), array_keys($this->store->visible_tabs()));
        $this->assertTrue($this->store->can_write_tab('essai'));
        $this->assertFalse($this->store->can_read('phpunit.systeme'));

        $this->store->set_rank(3);
        $this->assertSame(array('essai', 'systeme', 'defaut'), array_keys($this->store->visible_tabs()));
        $this->assertTrue($this->store->can_read('phpunit.systeme'));

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

    /** Dernière ligne d'historique d'un paramètre de test. */
    private function lastHistory($key)
    {
        return $this->CI->db->from('parametres_history')->where('cle', $key)
            ->order_by('id', 'DESC')->limit(1)->get()->row_array();
    }

    private function requireHistory()
    {
        if (!$this->CI->db->table_exists('parametres_history')) {
            $this->markTestSkipped('Migration 185 non appliquée');
        }
    }

    /**
     * Scénario: Chaque modification est tracée avec l'ancienne et la nouvelle valeur
     *   Étant donné une adresse e-mail déjà enregistrée
     *   Quand l'administrateur la remplace par une autre
     *   Alors l'historique indique qui l'a modifiée, quand, dans quel onglet, et les deux valeurs
     *   Mais enregistrer à nouveau la même valeur n'ajoute rien à l'historique
     */
    public function testHistory_TracesEachChange()
    {
        $this->requireHistory();
        $this->store->save('phpunit.email', 'a@example.org');
        $first = $this->lastHistory('phpunit.email');
        $this->assertNull($first['ancienne_valeur']);

        $this->store->save('phpunit.email', 'b@example.org');
        $h = $this->lastHistory('phpunit.email');
        $this->assertSame('essai', $h['onglet']);
        $this->assertSame('a@example.org', $h['ancienne_valeur']);
        $this->assertSame('b@example.org', $h['nouvelle_valeur']);
        $this->assertSame('modification', $h['action']);
        $this->assertSame($this->CI->dx_auth->get_username(), $h['created_by']);
        $this->assertNotEmpty($h['created_at']);

        $this->store->save('phpunit.email', 'b@example.org');
        $this->assertSame($h['id'], $this->lastHistory('phpunit.email')['id']);
    }

    /**
     * Scénario: Un secret est conservé si le champ est laissé vide, et sa valeur n'est jamais tracée
     *   Étant donné un mot de passe enregistré
     *   Quand l'administrateur enregistre l'onglet sans le ressaisir
     *   Alors le mot de passe est conservé
     *   Quand il demande explicitement son effacement
     *   Alors le mot de passe est effacé
     *   Et l'historique indique seulement qu'il a été modifié puis effacé, sans aucune valeur
     */
    public function testSecret_KeptWhenEmpty_ClearedExplicitly_NeverTraced()
    {
        $this->requireHistory();
        $this->assertSame('saved', $this->store->save('phpunit.secret', 'motdepasse')['status']);
        $h = $this->lastHistory('phpunit.secret');
        $this->assertSame('1', (string) $h['secret']);
        $this->assertNull($h['nouvelle_valeur']);

        $this->assertSame('unchanged', $this->store->save('phpunit.secret', '')['status']);
        $this->assertSame('motdepasse', $this->store->get('phpunit.secret')['value']);

        $this->assertSame('saved', $this->store->save('phpunit.secret', '', NULL, array('clear' => TRUE))['status']);
        $this->assertTrue($this->store->get('phpunit.secret')['is_default']);
        $h = $this->lastHistory('phpunit.secret');
        $this->assertSame('effacement', $h['action']);
        $this->assertNull($h['ancienne_valeur']);
        $this->assertNull($h['nouvelle_valeur']);
    }

    /**
     * Scénario: Revenir à la valeur précédente depuis l'historique
     *   Étant donné une adresse e-mail modifiée deux fois
     *   Quand l'administrateur revient sur la dernière modification
     *   Alors l'adresse précédente est de nouveau en vigueur
     *   Et ce retour apparaît lui-même dans l'historique
     */
    public function testRestore_PutsBackPreviousValueAndIsTraced()
    {
        $this->requireHistory();
        $this->store->save('phpunit.email', 'a@example.org');
        $this->store->save('phpunit.email', 'b@example.org');
        $id = $this->lastHistory('phpunit.email')['id'];

        $res = $this->store->restore($id);
        $this->assertSame('saved', $res['status']);
        $this->assertSame('a@example.org', $this->store->get('phpunit.email')['value']);
        $h = $this->lastHistory('phpunit.email');
        $this->assertSame('retour', $h['action']);
        $this->assertSame('b@example.org', $h['ancienne_valeur']);
        $this->assertSame('a@example.org', $h['nouvelle_valeur']);
    }

    /**
     * Scénario: Un retour arrière est refusé pour un secret ou pour un profil non autorisé
     *   Étant donné un mot de passe modifié et une adresse e-mail modifiée
     *   Quand l'administrateur veut revenir sur la modification du mot de passe
     *   Alors le retour est refusé, avec son motif
     *   Et quand un membre du CA veut revenir sur la modification de l'adresse
     *   Alors le retour est refusé, et l'adresse reste inchangée
     */
    public function testRestore_RefusedForSecretAndLowerProfile()
    {
        $this->requireHistory();
        $this->store->save('phpunit.secret', 'motdepasse');
        $this->assertSame('parametres_err_restore_secret',
            $this->store->restore($this->lastHistory('phpunit.secret')['id'])['error']);

        $this->store->save('phpunit.email', 'a@example.org');
        $this->store->save('phpunit.email', 'b@example.org');
        $this->store->set_rank(1);
        $this->assertSame('parametres_err_forbidden',
            $this->store->restore($this->lastHistory('phpunit.email')['id'])['error']);
        $this->assertSame('b@example.org', $this->store->get('phpunit.email')['value']);
    }

    /**
     * Scénario: Un retour arrière vers une valeur devenue invalide est refusé
     *   Étant donné un serveur d'envoi renseigné puis devenu obligatoire avec le mode SMTP
     *   Quand l'administrateur revient sur la saisie du serveur, ce qui le viderait
     *   Alors le retour est refusé comme une saisie vide
     */
    public function testRestore_IsRevalidated()
    {
        $this->requireHistory();
        $this->store->save('phpunit.serveur', 'smtp.example.org');
        $id = $this->lastHistory('phpunit.serveur')['id'];
        $this->store->save('phpunit.mode', 'smtp');
        $this->assertSame('parametres_err_required', $this->store->restore($id)['error']);
        $this->assertSame('smtp.example.org', $this->store->get('phpunit.serveur')['value']);
    }

    /**
     * Scénario: Un paramètre n'est obligatoire que selon la valeur d'un autre
     *   Étant donné un serveur d'envoi obligatoire seulement en mode SMTP
     *   Quand le mode d'envoi est « mail »
     *   Alors le serveur peut rester vide et l'onglet n'est pas incomplet
     *   Quand le mode passe à SMTP sans serveur
     *   Alors l'onglet est signalé incomplet
     *   Et un mode qui n'est pas proposé est refusé
     */
    public function testRequiredIf_AndSelectOptions()
    {
        $this->store->save('phpunit.email', 'a@example.org');
        $this->store->save('phpunit.mode', 'mail');
        $this->assertFalse($this->store->is_missing('phpunit.serveur'));
        $this->assertSame('complete', $this->store->tab_status('essai'));

        $this->store->save('phpunit.mode', 'smtp');
        $this->assertTrue($this->store->is_missing('phpunit.serveur'));
        $this->assertSame('incomplete', $this->store->tab_status('essai'));

        $this->assertSame('parametres_err_option', $this->store->save('phpunit.mode', 'pigeon')['error']);
    }

    /**
     * Scénario: Activer un module dont des paramètres manquent est possible, avec un avertissement
     *   Étant donné un module qui a besoin d'un serveur d'envoi, et le mode SMTP choisi sans serveur
     *   Quand l'administrateur active le module
     *   Alors le module est activé
     *   Et l'onglet où compléter le serveur est désigné
     *   Mais une fois le serveur renseigné, il n'y a plus d'avertissement
     */
    public function testModuleWithMissingRequirements_IsSavedWithWarning()
    {
        $this->store->save('phpunit.mode', 'smtp');
        $this->assertSame('saved', $this->store->save('phpunit.module', 'actif')['status']);
        $this->assertSame(array('essai' => array('phpunit.serveur')),
            $this->store->missing_requirements('phpunit.module'));

        $this->store->save('phpunit.serveur', 'smtp.example.org');
        $this->assertSame(array(), $this->store->missing_requirements('phpunit.module'));
    }

    /**
     * Scénario: La page Configuration reste accessible quels que soient les modules actifs
     *   Étant donné un administrateur du club
     *   Quand il ouvre la page Configuration
     *   Alors il voit les onglets Club, Modules, Messagerie et Pages publiques
     *   Et il peut toujours modifier l'onglet Modules pour réactiver un module
     */
    public function testConfigurationPage_DoesNotDependOnModules()
    {
        $real = new Parametres_store();
        $real->set_rank(2);
        $tabs = array_keys($real->visible_tabs());
        foreach (array('club', 'modules', 'messagerie', 'pages_publiques') as $tab) {
            $this->assertContains($tab, $tabs);
        }
        $this->assertTrue($real->can_write_tab('modules'));

        $real->set_rank(1);
        $this->assertSame(array('club', 'modules'), array_keys($real->visible_tabs()));
        $this->assertFalse($real->can_write_tab('club'));
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

    /**
     * Scénario: Un réglage obligatoire satisfait par la valeur par défaut de GVV ne rend pas l'onglet incomplet
     *   Étant donné un réglage obligatoire dont GVV fournit une valeur par défaut
     *   Et que le club n'a rien saisi
     *   Quand l'administrateur consulte l'état de l'onglet
     *   Alors l'onglet n'est pas signalé incomplet
     */
    public function testRequiredWithDefault_IsNotIncomplete()
    {
        $this->assertFalse($this->store->is_missing('phpunit.defaut'));
        $this->assertNotSame('incomplete', $this->store->tab_status('defaut'));
    }
}
