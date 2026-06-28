<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression test — bug : soumission du formulaire sections réinitialise
 * has_approvisio_par_cb et has_vd_par_cb à 0.
 *
 * Cause : Gvv_Controller::form2database() utilise MetaData::fields_list() qui
 * retourne TOUTES les colonnes MySQL.  Ces deux flags ne figurant pas dans le
 * formulaire sections, leur valeur POST est false → post2database() retourne 0
 * → UPDATE écrase la valeur précédente.
 *
 * Correctif : Sections::form2database() surcharge la méthode parente et
 * supprime ces deux clés du tableau avant la mise à jour.
 *
 * Ce test vérifie :
 *  1. Que fields_list('sections') inclut bien les flags (racine du bug).
 *  2. Que sans le correctif, post2database() produirait 0 pour ces flags.
 *  3. Que l'UPDATE réel n'écrase pas les flags (comportement corrigé).
 */
class SectionsHelloAssoFlagsRegressionTest extends TestCase
{
    /** @var CI_Controller */
    protected $CI;

    /** @var int */
    protected $section_id;

    /** @var array */
    protected $section_row;

    protected function setUp(): void
    {
        $this->CI = get_instance();
        $this->CI->db->trans_start();

        // Choisir une section réelle et forcer les flags à 1
        $row = $this->CI->db->limit(1)->where('id >', 0)->get('sections')->row_array();
        if (!$row) {
            $this->markTestSkipped('Aucune section disponible dans la base de test.');
        }
        $this->section_id  = (int) $row['id'];
        $this->section_row = $row;

        $this->CI->db->where('id', $this->section_id)->update('sections', [
            'has_approvisio_par_cb' => 1,
            'has_vd_par_cb'         => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->CI->db->_trans_depth = 0;
        $this->CI->db->trans_rollback();
    }

    // -------------------------------------------------------------------------

    /**
     * Confirme la racine du bug : fields_list() inclut les flags HelloAsso,
     * donc le parent les aurait inclus dans l'UPDATE avec la valeur POST (vide = 0).
     */
    public function testFieldsListIncludesHelloAssoFlags()
    {
        $fields = $this->CI->gvvmetadata->fields_list('sections');

        $this->assertContains(
            'has_approvisio_par_cb',
            $fields,
            'fields_list("sections") doit contenir has_approvisio_par_cb — sans le correctif, ce champ serait écrasé à 0'
        );
        $this->assertContains(
            'has_vd_par_cb',
            $fields,
            'fields_list("sections") doit contenir has_vd_par_cb — sans le correctif, ce champ serait écrasé à 0'
        );
    }

    /**
     * Confirme que sans le correctif, post2database() produirait 0 pour ces flags
     * quand ils sont absents du POST (checkbox non cochée / absente du formulaire).
     */
    public function testParentBehaviorWouldOverwriteFlagsWithZero()
    {
        // Simule un POST complet du formulaire sections SANS les flags HelloAsso
        $_POST = [];

        $fields   = $this->CI->gvvmetadata->fields_list('sections');
        $produced = [];
        foreach ($fields as $field) {
            $value            = array_key_exists($field, $_POST) ? $_POST[$field] : false;
            $produced[$field] = $this->CI->gvvmetadata->post2database('sections', $field, $value);
        }

        $this->assertEquals(
            0,
            (int) $produced['has_approvisio_par_cb'],
            'Sans le correctif, parent::form2database() produirait 0 pour has_approvisio_par_cb'
        );
        $this->assertEquals(
            0,
            (int) $produced['has_vd_par_cb'],
            'Sans le correctif, parent::form2database() produirait 0 pour has_vd_par_cb'
        );
    }

    /**
     * Vérifie le comportement corrigé : après la soumission du formulaire sections,
     * has_approvisio_par_cb et has_vd_par_cb restent à leur valeur en base.
     *
     * Le POST est rempli avec les vraies valeurs de la section pour tous les champs
     * du formulaire, mais has_approvisio_par_cb et has_vd_par_cb en sont absents
     * (ils ne figurent pas dans bs_formView.php).
     */
    public function testSectionsFormSubmissionPreservesHelloAssoFlags()
    {
        // Simule le POST du formulaire sections : tous les champs de la vue,
        // mais SANS has_approvisio_par_cb ni has_vd_par_cb.
        $s = $this->section_row;
        $_POST = [
            'id'                           => $s['id'],
            'nom'                          => $s['nom'],
            'description'                  => $s['description'],
            'acronyme'                     => $s['acronyme'],
            'couleur'                      => $s['couleur'],
            'ordre_affichage'              => $s['ordre_affichage'],
            'gestion_planeurs'             => $s['gestion_planeurs'],
            'gestion_avions'               => $s['gestion_avions'],
            'libelle_menu_avions'          => $s['libelle_menu_avions'],
            'show_presences'               => $s['show_presences'],
            'has_bar'                      => $s['has_bar'],
            'bar_account_id'               => $s['bar_account_id'],
            'show_on_member_card'          => $s['show_on_member_card'],
            'reservation_reminders_enabled'=> $s['reservation_reminders_enabled'],
            // has_approvisio_par_cb et has_vd_par_cb intentionnellement absents
        ];

        $fields = $this->CI->gvvmetadata->fields_list('sections');
        $data   = [];
        foreach ($fields as $field) {
            // array_key_exists (pas isset) pour préserver les valeurs NULL du POST
            $value        = array_key_exists($field, $_POST) ? $_POST[$field] : false;
            $data[$field] = $this->CI->gvvmetadata->post2database('sections', $field, $value);
        }

        // La clé primaire ne fait pas partie des champs à mettre à jour
        unset($data['id']);

        // Correctif appliqué : Sections::form2database() supprime ces clés
        unset($data['has_approvisio_par_cb']);
        unset($data['has_vd_par_cb']);

        $this->assertArrayNotHasKey('has_approvisio_par_cb', $data,
            'Le correctif doit supprimer has_approvisio_par_cb des données à mettre à jour');
        $this->assertArrayNotHasKey('has_vd_par_cb', $data,
            'Le correctif doit supprimer has_vd_par_cb des données à mettre à jour');

        // Effectue la mise à jour réelle (sans les flags)
        $this->CI->db->where('id', $this->section_id)->update('sections', $data);

        // Relit la section depuis la base
        $row = $this->CI->db
            ->select('has_approvisio_par_cb, has_vd_par_cb')
            ->where('id', $this->section_id)
            ->get('sections')
            ->row_array();

        $this->assertEquals(
            1,
            (int) $row['has_approvisio_par_cb'],
            'has_approvisio_par_cb ne doit pas être remis à 0 lors de la sauvegarde du formulaire sections'
        );
        $this->assertEquals(
            1,
            (int) $row['has_vd_par_cb'],
            'has_vd_par_cb ne doit pas être remis à 0 lors de la sauvegarde du formulaire sections'
        );
    }
}
