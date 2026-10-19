<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Licences
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont
 *  implémentés dans Common_Model
 */
class Licences_model extends Common_Model {
    public $table = 'licences';
    protected $primary_key = 'pilote,year,type';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $where = array ()) {
        $select = $this->select_columns('id, pilote, type, year, date, comment', $nb, $debut, $where);
        $this->gvvmetadata->store_table("vue_licences", $select);
        return $select;
    }

    /**
     * Extrait les informations de formation
     * @param int $type Type de licence
     * @param int|null $year_min Année de début (null = auto)
     * @param int|null $year_max Année de fin (null = auto)
     * @param string $member_status Status des membres ('all', 'active', 'inactive')
     * @param int|null $section_id ID de la section (null = toutes les sections)
     * @return array Array avec 'data' (lignes de la table) et 'total' (ligne de total)
     */
    public function per_year($type, $year_min = null, $year_max = null, $member_status = 'active', $section_id = null, $format = "html") {

        // Liste de pilotes selon le statut demandé
        $this->db->distinct();
        $this->db->select('membres.mlogin, membres.mnom, membres.mprenom, membres.m25ans');
        $this->db->from("membres");

        // Si un filtre de section est appliqué, joindre avec la table comptes
        if ($section_id !== null && $section_id !== 'all') {
            $this->db->join("comptes", "membres.mlogin = comptes.pilote", "inner");
            $this->db->where('comptes.club', $section_id);
            $this->db->where('comptes.codec', '411');
        }

        // Filtrer selon le statut des membres (actif = rôle "Utilisateur" dans au moins une section)
        if ($member_status === 'active') {
            $this->db->join('users u_lic', 'u_lic.username = membres.mlogin', 'inner');
            $this->db->join('user_roles_per_section urps_lic', 'urps_lic.user_id = u_lic.id', 'inner');
            $this->db->join('types_roles tr_lic', 'tr_lic.id = urps_lic.types_roles_id', 'inner');
            $this->db->where('tr_lic.nom', 'user');
        } elseif ($member_status === 'inactive') {
            $this->db->where('membres.mlogin NOT IN (SELECT u.username FROM users u INNER JOIN user_roles_per_section urps ON urps.user_id = u.id INNER JOIN types_roles tr ON tr.id = urps.types_roles_id WHERE tr.nom = \'user\')', NULL, FALSE);
        }
        // Si 'all', pas de filtre

        $actifs = $this->db->order_by("membres.mnom, membres.mprenom")->get()->result_array();

        // extraction des licences
        $selection = $this->select_columns('id, pilote, type, year, date, comment', 1000000, 0, array (
            'type' => $type
        ));

        // Déterminer min et max
        if ($year_min === null || $year_max === null) {
            // Mode automatique : calculer depuis les données
            $min_range = 10;
            $min = date("Y");
            $max = $min;
            foreach ($selection as $licence) {
                $year = $licence['year'];
                if ($year < $min)
                    $min = $year;
                if ($year > $max)
                    $max = $year;
            }
            if (($max - $min) < $min_range)
                $min = $max - $min_range;
        } else {
            // Utiliser les paramètres fournis
            $min = $year_min;
            $max = $year_max;
        }

        // Initialise the array
        $results = array ();
        $line = 0;
        $col = 0;
        
        // Initialiser la ligne d'en-tête ET la ligne de total
        $total_annuel = array ();
        $results[$line][$col] = "Pilote";
        $total_annuel[$col] = "Total";
        $col++;

        // Créer les colonnes d'années
        for ($year = $min; $year <= $max; $year++) {
            $results[$line][$col] = $year;
            $total_annuel[$col] = 0;
            $col++;
        }
        
        // Nombre total de colonnes (1 pour "Pilote" + nombre d'années)
        $num_columns = $col;

        $pilote_line = array ();
        foreach ($actifs as $pilote) {
            $line++;
            $col = 0;
            $mlogin = $pilote['mlogin'];
            $pilote_line[$mlogin] = $line;
            $results[$line][$col++] = ($format == "html")
                ? anchor(controller_url("event/page/$mlogin"), $pilote['mnom'] . ' ' . $pilote['mprenom'])
                : $pilote['mnom'] . ' ' . $pilote['mprenom'];
            for ($year = $min; $year <= $max; $year++) {
                // Checkbox non cochée par défaut
                $checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $mlogin . '" data-year="' . $year . '" data-type="' . $type . '">';
                $results[$line][$col++] = $checkbox;
            }
        }

        foreach ($selection as $licence) {
            $pilote = $licence['pilote'];
            $year = $licence['year'];
            
            // Vérifier que l'année est dans la plage affichée
            if ($year < $min || $year > $max) {
                continue;
            }
            
            // Checkbox cochée pour les licences existantes
            $checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $pilote . '" data-year="' . $year . '" data-type="' . $type . '" checked>';
            $col = $year - $min + 1;
            if ( array_key_exists($pilote, $pilote_line) ) {
            	$results[$pilote_line[$pilote]][$col] = $checkbox;
            	$total_annuel[$col] += 1;
            }
        }
        
        // S'assurer que le total a exactement le bon nombre de colonnes
        // Remplir les colonnes manquantes avec 0
        for ($i = 0; $i < $num_columns; $i++) {
            if (!isset($total_annuel[$i])) {
                $total_annuel[$i] = 0;
            }
        }
        
        // Retourner les données et le total séparément
        return array(
            'data' => $results,
            'total' => $total_annuel
        );
    }

    /**
     * Retourne l'année minimum pour laquelle il y a des données
     * @return int Année minimum, ou année courante - 5 si pas de données
     */
    public function get_min_year() {
        $this->db->select_min('year');
        $this->db->from('licences');
        $result = $this->db->get()->row_array();

        $min_year = isset($result['year']) && !empty($result['year']) ? $result['year'] : (date("Y") - 5);
        return (int)$min_year;
    }

    /**
     * Retourne l'année maximum présente dans la table licences
     * @return int Année maximum ou année courante + 1 si pas de données
     */
    public function get_max_year() {
        $this->db->select_max('year');
        $this->db->from('licences');
        $result = $this->db->get()->row_array();

        $max_year = isset($result['year']) && !empty($result['year']) ? $result['year'] : (date("Y") + 1);
        return (int)$max_year;
    }

    /**
     * Vérifie si une cotisation existe déjà pour un pilote et une année donnée
     * @param string $pilote Login du pilote
     * @param int $year Année de cotisation
     * @return bool True si une cotisation existe, false sinon
     */
    public function check_cotisation_exists($pilote, $year) {
        $this->db->where('pilote', $pilote);
        $this->db->where('year', $year);
        $this->db->where('type', 0); // Type 0 = cotisation simple
        $query = $this->db->get($this->table);

        return $query->num_rows() > 0;
    }

    /**
     * Vols planeur et avion/ULM de l'année, sous forme de sous-requête SQL :
     * pilote, categorie (planeur | avion | ulm), date_vol, section_id,
     * licence_type (licence exigée par la section du vol, sections.licence_type).
     *
     * Les vols planeur viennent de volsp, qui ne porte pas de section : ils
     * sont rattachés à la section qui gère les planeurs (gestion_planeurs).
     * Les vols avion/ULM viennent de volsa, dans la section du vol (club) :
     * un remorquage, enregistré dans la section planeur, relève donc de la
     * licence de cette section. Catégorie : ULM quand la section du vol a
     * l'acronyme ULM, avion sinon (colonnes de la page « Vols sans cotisation »).
     *
     * @param int $year
     * @return string
     */
    private function vols_annee_sql($year) {
        $year = (int) $year;
        $debut = $this->db->escape("$year-01-01");
        $fin = $this->db->escape("$year-12-31");
        return "SELECT vppilid AS pilote, 'planeur' AS categorie, vpdate AS date_vol,
                    gs.id AS section_id, gs.licence_type
                FROM volsp
                LEFT JOIN (SELECT id, licence_type FROM sections WHERE gestion_planeurs = 1 ORDER BY id LIMIT 1) gs ON 1 = 1
                WHERE vpdate BETWEEN $debut AND $fin
                UNION ALL
                SELECT volsa.vapilid, IF(sections.acronyme = 'ULM', 'ulm', 'avion'), volsa.vadate,
                    volsa.club, sections.licence_type
                FROM volsa
                LEFT JOIN sections ON sections.id = volsa.club
                WHERE volsa.vadate BETWEEN $debut AND $fin";
    }

    /**
     * Pilotes ayant volé pendant l'année sans cotisation (type 0) pour cette année.
     * Les pilotes extérieurs (membres.ext = 1) ne sont pas censés cotiser et sont exclus.
     *
     * @param int $year Année contrôlée
     * @return array Une ligne par pilote : pilote, nom, prenom, planeur, avion, ulm,
     *               dernier_vol, sections (identifiants des sections des vols, séparés par des virgules)
     */
    public function pilotes_vols_sans_cotisation($year) {
        $year = (int) $year;
        $sql = "SELECT v.pilote, m.mnom AS nom, m.mprenom AS prenom,
                    SUM(v.categorie = 'planeur') AS planeur,
                    SUM(v.categorie = 'avion') AS avion,
                    SUM(v.categorie = 'ulm') AS ulm,
                    MAX(v.date_vol) AS dernier_vol,
                    GROUP_CONCAT(DISTINCT v.section_id ORDER BY v.section_id) AS sections
                FROM (" . $this->vols_annee_sql($year) . ") v
                LEFT JOIN membres m ON m.mlogin = v.pilote
                WHERE (m.ext IS NULL OR m.ext = 0)
                AND NOT EXISTS (
                    SELECT 1 FROM licences l
                    WHERE l.pilote = v.pilote AND l.year = $year AND l.type = 0
                )
                GROUP BY v.pilote, m.mnom, m.mprenom
                ORDER BY m.mnom, m.mprenom, v.pilote";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Pilotes ayant volé pendant l'année dans une section sans la licence
     * exigée par cette section (sections.licence_type) pour l'année. Les
     * sections sans licence exigée ne sont pas contrôlées ; les pilotes
     * extérieurs (membres.ext = 1) sont exclus.
     *
     * @param int $year Année contrôlée
     * @return array Une ligne par pilote et par section : pilote, nom, prenom, section_id,
     *               section_nom, licence_type, vols, dernier_vol, sections (= section_id)
     */
    public function pilotes_vols_sans_licence($year) {
        $year = (int) $year;
        $sql = "SELECT v.pilote, m.mnom AS nom, m.mprenom AS prenom, v.section_id, s.nom AS section_nom,
                    v.licence_type, COUNT(*) AS vols, MAX(v.date_vol) AS dernier_vol, v.section_id AS sections
                FROM (" . $this->vols_annee_sql($year) . ") v
                JOIN sections s ON s.id = v.section_id
                LEFT JOIN membres m ON m.mlogin = v.pilote
                WHERE (m.ext IS NULL OR m.ext = 0)
                AND v.licence_type > 0
                AND NOT EXISTS (
                    SELECT 1 FROM licences l
                    WHERE l.pilote = v.pilote AND l.year = $year AND l.type = v.licence_type
                )
                GROUP BY v.pilote, m.mnom, m.mprenom, v.section_id, s.nom, v.licence_type
                ORDER BY m.mnom, m.mprenom, v.pilote, s.nom";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Années pour lesquelles des vols planeur ou avion/ULM existent, plus récente en premier.
     * @return array year => year
     */
    public function vols_year_selector() {
        $sql = "SELECT DISTINCT y FROM (
                    SELECT YEAR(vpdate) AS y FROM volsp
                    UNION SELECT YEAR(vadate) FROM volsa
                ) t WHERE y IS NOT NULL ORDER BY y DESC";
        $years = array();
        foreach ($this->db->query($sql)->result_array() as $row) {
            $years[(int) $row['y']] = (int) $row['y'];
        }
        $current = (int) date('Y');
        if (!isset($years[$current])) {
            $years = array($current => $current) + $years;
        }
        return $years;
    }

    /**
     * Crée une nouvelle cotisation (licence)
     * @param string $pilote Login du pilote
     * @param int $type Type de licence (0 = cotisation simple)
     * @param int $year Année de cotisation
     * @param string $date Date de souscription (format Y-m-d)
     * @param string $comment Commentaire
     * @return int ID de la licence créée, ou false en cas d'erreur
     */
    public function create_cotisation($pilote, $type, $year, $date, $comment) {
        $data = array(
            'pilote' => $pilote,
            'type' => $type,
            'year' => $year,
            'date' => $date,
            'comment' => $comment
        );

        $this->inject_audit_fields($data, TRUE);
        $this->db->insert($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * Retourne les données pour la vue annuelle détaillée (Vue par année).
     *
     * Colonnes : Pilote (nom+prénom), Email, Cotisation (type 0),
     * puis une colonne par section possédant un type de licence associé.
     *
     * Type de licence d'une section : sections.licence_type (vide = pas de colonne).
     *
     * @param int    $year          Année à afficher
     * @param string $member_status 'active' | 'inactive' | 'all'
     * @return array ['members' => [...], 'sections' => [...]]
     */
    public function per_year_detail($year, $member_status = 'active') {
        $sections_raw = $this->db->select('id, nom, acronyme, licence_type')
            ->where('licence_type >', 0)
            ->order_by('ordre_affichage', 'asc')->order_by('nom', 'asc')
            ->get('sections')->result_array();

        $sections = array();
        foreach ($sections_raw as $s) {
            $sections[] = array(
                'id'           => $s['id'],
                'nom'          => $s['nom'],
                'acronyme'     => $s['acronyme'],
                'licence_type' => (int) $s['licence_type'],
            );
        }

        // Membres
        $this->db->select('membres.mlogin, membres.mnom, membres.mprenom, membres.memail');
        $this->db->from('membres');
        if ($member_status === 'active') {
            $this->db->distinct();
            $this->db->join('users u_det', 'u_det.username = membres.mlogin', 'inner');
            $this->db->join('user_roles_per_section urps_det', 'urps_det.user_id = u_det.id', 'inner');
            $this->db->join('types_roles tr_det', 'tr_det.id = urps_det.types_roles_id', 'inner');
            $this->db->where('tr_det.nom', 'user');
        } elseif ($member_status === 'inactive') {
            $this->db->where('membres.mlogin NOT IN (SELECT u.username FROM users u INNER JOIN user_roles_per_section urps ON urps.user_id = u.id INNER JOIN types_roles tr ON tr.id = urps.types_roles_id WHERE tr.nom = \'user\')', NULL, FALSE);
        }
        $this->db->order_by('membres.mnom', 'asc');
        $this->db->order_by('membres.mprenom', 'asc');
        $members_raw = $this->db->get()->result_array();

        // Licences de l'année
        $this->db->select('pilote, type');
        $this->db->from('licences');
        $this->db->where('year', $year);
        $licences_raw = $this->db->get()->result_array();

        $licences_index = array();
        foreach ($licences_raw as $lic) {
            $licences_index[$lic['pilote']][$lic['type']] = true;
        }

        $members = array();
        foreach ($members_raw as $m) {
            $login = $m['mlogin'];
            $row = array(
                'mlogin'     => $login,
                'nom'        => $m['mnom'],
                'prenom'     => $m['mprenom'],
                'email'      => $m['memail'] ?: '',
                'cotisation' => isset($licences_index[$login][0]),
            );
            foreach ($sections as $s) {
                $row['section_' . $s['id']] = isset($licences_index[$login][$s['licence_type']]);
            }
            $members[] = $row;
        }

        return array('members' => $members, 'sections' => $sections);
    }

}

/* End of file licences_model.php */
/* Location: ./application/models/licences_model.php */