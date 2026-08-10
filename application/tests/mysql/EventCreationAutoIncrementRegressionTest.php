<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression test — bug : la création d'un événement (certificat/brevet) depuis
 * la fiche membre échouait avec :
 *   Erreur: 1366 Incorrect integer value: '' for column `events`.`id` at row 1
 *
 * Cause : le formulaire de création (application/views/event/bs_formView.php)
 * émet toujours un champ caché `id` vide (form_hidden('id', $id, '')). Ce champ
 * était inclus par Gvv_Controller::form2database() dans les données envoyées à
 * l'INSERT, car MetaData::fields_list() ignorait son paramètre $no_autogen_key :
 *  - autogen_key() était appelée sans le paramètre $table (aurait levé une
 *    erreur fatale si le paramètre avait jamais été utilisé) ;
 *  - le unset() cherchait la clé par nom de champ dans un tableau indexé
 *    numériquement (['id','emlogin',...]), donc ne supprimait jamais rien.
 *
 * Résultat : `id` = '' partait dans l'INSERT sur une colonne int auto_increment
 * NOT NULL, ce que MySQL en mode strict (STRICT_TRANS_TABLES, actif sur ce
 * serveur) refuse.
 *
 * Correctif :
 *  - MetaData::fields_list($table, TRUE) exclut désormais correctement la clé
 *    auto-incrémentée (recherche par valeur, et $table transmis à autogen_key()).
 *  - Gvv_Controller::form2database() appelle fields_list($table, $action == CREATION)
 *    pour ne plus envoyer `id` à la création.
 *
 * Note : le test passe par une requête SQL brute (db->query) pour la preuve au
 * niveau base de données, car le wrapper de test Common_Model/RealDatabase
 * convertit silencieusement les chaînes vides en NULL dans insert() — ce que
 * ne fait pas le driver mysqli réel utilisé en production, où '' part telle
 * quelle et déclenche l'erreur 1366 (reproduit et vérifié manuellement sur
 * gvv.net).
 */
class EventCreationAutoIncrementRegressionTest extends TestCase
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string */
    protected $mlogin;

    /** @var int */
    protected $etype;

    protected function setUp(): void
    {
        $this->CI = get_instance();
        $this->CI->load->model('event_model');
        $this->CI->db->trans_start();

        $membre = $this->CI->db->limit(1)->get('membres')->row_array();
        if (!$membre) {
            $this->markTestSkipped('Aucun membre disponible dans la base de test.');
        }
        $this->mlogin = $membre['mlogin'];

        $type = $this->CI->db->limit(1)->get('events_types')->row_array();
        if (!$type) {
            $this->markTestSkipped("Aucun type d'événement disponible dans la base de test.");
        }
        $this->etype = $type['id'];
    }

    protected function tearDown(): void
    {
        $this->CI->db->_trans_depth = 0;
        $this->CI->db->trans_rollback();
    }

    // -------------------------------------------------------------------------

    /**
     * Confirme la racine du correctif : fields_list('events', true) exclut la
     * clé auto-incrémentée, alors que fields_list('events') (défaut) la
     * contient toujours (utilisé tel quel pour la MODIFICATION, où l'id réel
     * doit être envoyé).
     */
    public function testFieldsListExcludesAutoIncrementKeyOnlyWhenRequested()
    {
        $fieldsForCreation = $this->CI->gvvmetadata->fields_list('events', true);
        $this->assertNotContains(
            'id',
            $fieldsForCreation,
            "fields_list('events', true) doit exclure la clé auto-incrémentée"
        );

        $fieldsDefault = $this->CI->gvvmetadata->fields_list('events');
        $this->assertContains(
            'id',
            $fieldsDefault,
            "fields_list('events') sans argument doit toujours contenir 'id' (nécessaire à la MODIFICATION)"
        );
    }

    /**
     * Preuve au niveau base de données du bug original : un INSERT avec une
     * chaîne vide pour la colonne id (auto_increment, NOT NULL) échoue avec
     * l'erreur MySQL 1366 en mode strict. Sans cette colonne, l'INSERT réussit
     * et MySQL génère l'id automatiquement — exactement ce que le correctif
     * obtient en excluant 'id' des données envoyées.
     */
    public function testRawInsertWithEmptyStringIdFailsButOmittingIdSucceeds()
    {
        $mlogin = $this->CI->db->escape_str($this->mlogin);
        $etype = (int) $this->etype;

        $sqlWithEmptyId = "INSERT INTO events (id, emlogin, etype, edate, ecomment) "
            . "VALUES ('', '$mlogin', $etype, CURDATE(), 'regression test 1366 - avec id vide')";

        $caught = null;
        try {
            $this->CI->db->query($sqlWithEmptyId);
        } catch (Exception $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, "L'INSERT avec id='' doit échouer (reproduction du bug original)");
        $this->assertSame(1366, (int) $this->CI->db->_error_number(),
            "L'échec attendu est l'erreur MySQL 1366 (Incorrect integer value)");
        $this->assertStringContainsString('Incorrect integer value', $caught->getMessage(),
            "Le message d'erreur doit correspondre au bug original");

        $sqlWithoutId = "INSERT INTO events (emlogin, etype, edate, ecomment) "
            . "VALUES ('$mlogin', $etype, CURDATE(), 'regression test 1366 - sans id')";
        $this->CI->db->query($sqlWithoutId);
        $id = $this->CI->db->insert_id();

        $this->assertGreaterThan(0, (int) $id, "Sans la colonne id, MySQL doit générer un id automatiquement");
    }

    /**
     * Vérifie le comportement corrigé de bout en bout : la création d'un
     * événement depuis la fiche membre réussit, avec un id auto-généré,
     * exactement comme le ferait Gvv_Controller::form2database() en action
     * CREATION (fields_list + post2database), suivi de Event_model::create().
     */
    public function testEventCreationSucceedsWithAutoGeneratedId()
    {
        $_POST = array(
            'id'              => '',
            'emlogin'         => $this->mlogin,
            'etype'           => $this->etype,
            'edate'           => date('d/m/Y'),
            'evaid'           => '0',
            'evpid'           => '0',
            'ecomment'        => 'Regression test 1366 (post-fix)',
            'date_expiration' => '',
        );

        // Reproduit exactement Gvv_Controller::form2database() en CREATION
        $action = CREATION;
        $fields = $this->CI->gvvmetadata->fields_list('events', $action == CREATION);
        $data = array();
        foreach ($fields as $field) {
            $value = array_key_exists($field, $_POST) ? $_POST[$field] : null;
            $data[$field] = $this->CI->gvvmetadata->post2database('events', $field, $value);
        }
        $this->assertArrayNotHasKey('id', $data, "Le correctif doit exclure 'id' des données envoyées à l'INSERT");

        $id = $this->CI->event_model->create($data);

        $this->assertNotFalse($id, "La création doit réussir : " . $this->CI->db->_error_message());
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, (int) $id);

        $row = $this->CI->db->where('id', $id)->get('events')->row_array();
        $this->assertNotEmpty($row, "L'événement créé doit être lisible en base");
        $this->assertEquals($this->mlogin, $row['emlogin']);
        $this->assertEquals($this->etype, $row['etype']);
        $this->assertEquals('Regression test 1366 (post-fix)', $row['ecomment']);
    }
}
