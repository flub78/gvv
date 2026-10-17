<?php

use PHPUnit\Framework\TestCase;

/**
 * Niveau d'entraînement : heures de vol des pilotes solo sur les 12 derniers
 * mois, heures de DC données ajoutées pour les instructeurs.
 *
 * @see application/models/formation_seance_model.php get_niveau_entrainement()
 */
class FormationNiveauEntrainementTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Formation_seance_model */
    private $model;

    private $machine = 'F-TESTN';
    private $instructeur = 'zztestnivi';
    private $pilote_solo = 'zztestnivs';
    private $eleve = 'zztestnive';

    private $created_vaid = array();
    private $created_user_ids = array();

    /** Section existante gérant des avions mais pas de planeurs */
    private $section_id;

    /** Section du modèle, restaurée après le test */
    private $saved_section;
    private $saved_section_id;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('formation_seance_model');
        $this->model = $this->CI->formation_seance_model;

        // Sauvegardée avant tout skip : tearDown() la restaure dans tous les cas
        $this->saved_section = $this->model->section;
        $this->saved_section_id = $this->model->section_id;

        // Section de test gérant des avions : volsa filtré sur cette section,
        // pilotes actifs = rôle 'user' non révoqué dans cette section
        // (volsa.club est un tinyint : une section créée pour le test aurait
        // un id hors limites, on utilise donc une section existante)
        $section = $this->CI->db->where('gestion_planeurs', 0)->where('gestion_avions', 1)
            ->where('id <', 128)->limit(1)->get('sections')->row_array();
        if (!$section) {
            $this->markTestSkipped('Aucune section avion sans planeurs');
        }
        $this->section_id = (int) $section['id'];

        $this->model->section = $section;
        $this->model->section_id = $this->section_id;

        foreach (array($this->instructeur, $this->pilote_solo, $this->eleve) as $login) {
            $this->CI->db->delete('membres', array('mlogin' => $login));
            $this->CI->db->insert('membres', array(
                'mlogin'  => $login,
                'mnom'    => 'Test',
                'mprenom' => $login,
                'actif'   => 1,
            ));
            $this->addCotisation($login);
            $this->addUserRole($login);
        }

        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
        $this->CI->db->insert('machinesa', array(
            'macimmat'       => $this->machine,
            'macconstruc'    => 'TestConstructeur',
            'macmodele'      => 'TestNiveau',
            'macplaces'      => 2,
            'maprix'         => '0',
            'maprive'        => 0,
            'horametre_mode' => 1,
            'actif'          => 1,
            'club'           => $this->section_id,
        ));
    }

    protected function tearDown(): void
    {
        foreach (array($this->instructeur, $this->pilote_solo, $this->eleve) as $login) {
            $this->CI->db->delete('licences', array('pilote' => $login));
        }
        foreach ($this->created_vaid as $vaid) {
            $this->CI->db->delete('volsa', array('vaid' => $vaid));
        }
        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
        foreach ($this->created_user_ids as $uid) {
            $this->CI->db->delete('user_roles_per_section', array('user_id' => $uid));
            $this->CI->db->delete('users', array('id' => $uid));
        }
        foreach (array($this->instructeur, $this->pilote_solo, $this->eleve) as $login) {
            $this->CI->db->delete('membres', array('mlogin' => $login));
        }
        $this->model->section = $this->saved_section;
        $this->model->section_id = $this->saved_section_id;
    }

    private function addUserRole($login)
    {
        $this->CI->db->insert('users', array(
            'username' => $login,
            'password' => 'x',
            'email'    => $login . '@example.invalid',
            'last_ip'  => '127.0.0.1',
        ));
        $uid = (int) $this->CI->db->insert_id();
        $this->created_user_ids[] = $uid;
        $role = $this->CI->db->get_where('types_roles', array('nom' => 'user'))->row_array();
        $this->CI->db->insert('user_roles_per_section', array(
            'user_id'        => $uid,
            'types_roles_id' => $role['id'],
            'section_id'     => $this->section_id,
        ));
    }

    private function addCotisation($login, $year = null)
    {
        $this->CI->db->insert('licences', array(
            'pilote'  => $login,
            'type'    => 0,
            'year'    => $year ?: (int) date('Y'),
            'date'    => date('Y-m-d'),
            'comment' => 'Test FormationNiveauEntrainement',
        ));
    }

    private function insertFlight($pilote, $instructeur, $vaduree, $date)
    {
        $this->CI->db->insert('volsa', array(
            'vadate'      => $date,
            'vapilid'     => $pilote,
            'vamacid'     => $this->machine,
            'vainst'      => $instructeur,
            'vahdeb'      => 9.00,
            'vahfin'      => 10.00,
            'vacdeb'      => 0.00,
            'vacfin'      => $vaduree,
            'vaduree'     => $vaduree,
            'vaobs'       => 'Test FormationNiveauEntrainement',
            'vadc'        => $instructeur ? 1 : 0,
            'vacategorie' => 0,
            'vaatt'       => 1,
            'club'        => $this->section_id,
        ));
        $id = $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id, 'insertFlight should succeed');
        $this->created_vaid[] = $id;
        return $id;
    }

    public function test_heures_solo_et_dc_sur_12_mois(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $ancien = date('Y-m-d', strtotime('-18 months'));
        $debut  = date('Y-m-d', strtotime('-12 months'));

        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);
        $this->insertFlight($this->pilote_solo, null, 3.00, $ancien);   // hors période
        $this->insertFlight($this->instructeur, null, 1.00, $recent);
        $this->insertFlight($this->eleve, $this->instructeur, 4.00, $recent);

        $rows = $this->model->get_niveau_entrainement($debut);
        $solo = $this->find_row($rows, $this->pilote_solo);
        $inst = $this->find_row($rows, $this->instructeur);

        $this->assertNotNull($solo);
        $this->assertEquals(2.0, (float) $solo['heures_solo']);
        $this->assertEquals(2.0, (float) $solo['heures_total']);
        $this->assertEquals(1, (int) $solo['nb_vols']);

        $this->assertNotNull($inst);
        $this->assertEquals(1.0, (float) $inst['heures_solo']);
        $this->assertEquals(4.0, (float) $inst['heures_instruction']);
        $this->assertEquals(0.0, (float) $inst['heures_dc']);
        $this->assertEquals(5.0, (float) $inst['heures_total']);

        // L'élève n'a volé qu'en double commande : il n'est pas pilote solo
        $this->assertNull($this->find_row($rows, $this->eleve));
    }

    public function test_dc_recue_incluse_dans_total(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));

        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);
        $this->insertFlight($this->pilote_solo, $this->instructeur, 1.50, $recent);

        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $solo = $this->find_row($rows, $this->pilote_solo);

        $this->assertNotNull($solo);
        $this->assertEquals(2.0, (float) $solo['heures_solo']);
        $this->assertEquals(1.5, (float) $solo['heures_dc']);
        $this->assertEquals(0.0, (float) $solo['heures_instruction']);
        $this->assertEquals(3.5, (float) $solo['heures_total']);
        $this->assertEquals(2, (int) $solo['nb_vols']);
    }

    public function test_cotisation_annee_precedente_acceptee(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);
        $this->CI->db->delete('licences', array('pilote' => $this->pilote_solo));
        $this->addCotisation($this->pilote_solo, (int) date('Y') - 1);

        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $this->assertNotNull($this->find_row($rows, $this->pilote_solo),
            'La cotisation de l\'année précédente doit suffire');
    }

    public function test_cotisation_trop_ancienne_exclue(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);
        $this->CI->db->delete('licences', array('pilote' => $this->pilote_solo));
        $this->addCotisation($this->pilote_solo, (int) date('Y') - 2);

        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $this->assertNull($this->find_row($rows, $this->pilote_solo),
            'Une cotisation antérieure à l\'année précédente ne suffit pas');
    }

    public function test_section_sans_planeurs_ni_avions_vide(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);

        $this->model->section = array('id' => $this->section_id, 'gestion_planeurs' => 0, 'gestion_avions' => 0);
        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $this->assertSame(array(), $rows);
    }

    public function test_pilote_inactif_dans_la_section_exclu(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);

        $user = $this->CI->db->get_where('users', array('username' => $this->pilote_solo))->row_array();
        $this->CI->db->where('user_id', $user['id'])
            ->update('user_roles_per_section', array('revoked_at' => date('Y-m-d H:i:s')));

        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $this->assertNull($this->find_row($rows, $this->pilote_solo),
            'Un pilote sans rôle actif dans la section ne doit pas être listé');
    }

    public function test_pilote_sans_cotisation_exclu(): void
    {
        $recent = date('Y-m-d', strtotime('-1 month'));
        $this->insertFlight($this->pilote_solo, null, 2.00, $recent);
        $this->CI->db->delete('licences', array('pilote' => $this->pilote_solo));

        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $this->assertNull($this->find_row($rows, $this->pilote_solo),
            'Un pilote sans cotisation ne doit pas être listé');
    }

    public function test_tri_par_heures_croissantes(): void
    {
        $rows = $this->model->get_niveau_entrainement(date('Y-m-d', strtotime('-12 months')));
        $previous = -1;
        foreach ($rows as $row) {
            $this->assertGreaterThanOrEqual($previous, (float) $row['heures_total']);
            $previous = (float) $row['heures_total'];
        }
    }

    private function find_row(array $rows, $login)
    {
        foreach ($rows as $row) {
            if ($row['mlogin'] === $login) {
                return $row;
            }
        }
        return null;
    }
}
