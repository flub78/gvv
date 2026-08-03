<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Tarifs model
 *
 * Historique de prix (date, prix, nb_tickets) d'un produit
 * (application/models/produits_model.php). Façade de compatibilité du
 * refactoring tarifs -> produits + tarifs (doc/design_notes/refactoring_produits_tarifs.md,
 * étape 7 du plan) : toutes les méthodes publiques gardent leur signature et la
 * forme de leur résultat (mêmes clés de tableau) pour que les appelants
 * existants n'aient rien à changer, y compris pendant la période de transition
 * où `tarifs` porte encore les colonnes produit historiques en lecture
 * (`reference`, `description`, `compte`, `club`, `is_cotisation`,
 * `nb_personnes_max`, `public`, `type_ticket`, `saisie_par` — supprimées à la
 * migration finale, étape 12).
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Tarifs_model extends Common_Model {
    public $table = 'tarifs';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
        $this->load->model('produits_model');
    }

    /**
     * Colonnes reconstruisant la forme d'une ligne `tarifs` d'avant refactoring
     * (mêmes noms de clé), en jointure avec `produits` pour les attributs produit.
     */
    private function joined_select_columns() {
        return 'tarifs.id AS id, tarifs.produit_id AS produit_id, produits.reference AS reference,
                tarifs.date AS date, produits.description AS description,
                tarifs.prix AS prix, produits.nb_personnes_max AS nb_personnes_max, produits.compte AS compte,
                produits.club AS club, tarifs.nb_tickets AS nb_tickets, produits.type_ticket AS type_ticket,
                produits.is_cotisation AS is_cotisation, produits.public AS public,
                tarifs.created_by AS created_by, tarifs.created_at AS created_at,
                tarifs.updated_by AS updated_by, tarifs.updated_at AS updated_at';
    }

    /**
     * Retourne le tableau utilisé pour l'affichage par page, limité aux tarifs
     * d'un produit donné (sous-CRUD, cf. contrôleur tarifs.php::page()).
     *
     * @return objet La liste
     */
    public function select_page($produit_id, $nb = 1000, $debut = 0) {
        $session = $this->session->all_userdata();
        $tarif_tout = isset($session['filter_tarif_tout']) ? $session['filter_tarif_tout'] : true;
        $tarif_date = isset($session['filter_tarif_date']) ? $session['filter_tarif_date'] : '';

        gvv_debug("session=" . var_export($session, true));
        gvv_debug("tarifs select tout=" . $tarif_tout);
        gvv_debug("tarifs select date=" . $tarif_date);

        $select = 'tarifs.id as id, tarifs.date as date, produits.public as public,
                   produits.reference as reference, produits.description as description, tarifs.prix as prix,
                   produits.club as club, comptes.nom as nom_compte, tarifs.nb_tickets as nb_tickets,
                   produits.type_ticket as type_ticket, produits.is_cotisation as is_cotisation';

        if ($tarif_tout) {
            $this->db->select($select)
                ->from("tarifs")
                ->join("produits", "produits.id = tarifs.produit_id")
                ->join("comptes", "produits.compte = comptes.id")
                ->where("tarifs.produit_id", $produit_id)
                ->order_by('produits.reference', 'asc')
                ->order_by('tarifs.date', 'desc');

            if ($this->section) {
                $this->db->where('produits.club', $this->section_id);
            }

            $result = $this->safe_get();
        } else {
            if (! $tarif_date) {
                $tarif_date = date("d/m/Y");
            }
            $filter_date = date_ht2db($tarif_date);
            $tarif_public = isset($session['filter_tarif_public']) ? $session['filter_tarif_public'] : 0;
            gvv_debug("tarifs select public=" . $tarif_public);

            if (isset($session['filter_tarif_public'])) {
                if ($session['filter_tarif_public'] == 1) {
                    $public = "produits.public = 1";
                } elseif ($session['filter_tarif_public'] == 2) {
                    $public = "produits.public = 0";
                } else {
                    $public = "produits.public >= 0"; // match everything
                }
            } else {
                $public = "produits.public >= 0"; // match everything
            }

            $this->db->select($select)
                ->from("tarifs")
                ->join("produits", "produits.id = tarifs.produit_id")
                ->join("comptes", "produits.compte = comptes.id")
                ->where("tarifs.produit_id", $produit_id)
                ->where("tarifs.date <= '$filter_date'")
                ->where($public);

            if ($this->section) {
                $this->db->where('produits.club', $this->section_id);
            }

            $this->db->order_by('produits.reference', 'asc')
                ->order_by('tarifs.date', 'desc');
            $tmp = $this->safe_get();

            // Take only the first one
            $result = array();
            $refs = array();
            foreach ($tmp as $row) {
                if (! array_key_exists($row['reference'], $refs)) {
                    $result[] = $row;
                }
                $refs[$row['reference']] = 1;
            }
        }

        foreach ($result as $key => $row) {
            $kid = $this->primary_key;
            $image = $this->image($row[$kid], TRUE);
            $result[$key]['image'] = $image;

            $section = $this->sections_model->get_by_id('id', $row['club']);
            $result[$key]['section_name'] = $section['nom'];
        }

        $this->gvvmetadata->store_table("vue_tarifs", $result);
        return $result;
    }

    /**
     * Tous les tarifs d'un produit, sans les filtres de session de la page
     * tarifs/page (filter_tarif_tout/date/public, cf. select_page() ci-dessus).
     * Utilisé par le panneau tarifs intégré à produits/create|edit, qui doit
     * toujours refléter l'état réel en base, indépendamment d'un filtre laissé
     * actif par un passage antérieur sur tarifs/page.
     */
    public function all_for_produit($produit_id) {
        return $this->db->select('id, date, prix, nb_tickets')
            ->from('tarifs')
            ->where('produit_id', $produit_id)
            ->order_by('date', 'desc')
            ->order_by('id', 'desc')
            ->get()->result_array();
    }

    /**
     * Ajoute un élément
     *
     * N'écrit que les colonnes de prix : produit_id, date, prix,
     * nb_tickets (+ audit, injecté par Common_Model::create()). L'identité
     * produit vit désormais sur `produits` — produit_id doit toujours être
     * fourni par l'appelant.
     *
     * @param $data hash des valeurs
     */
    public function create($data) {
        return parent::create($this->filter_price_fields($data));
    }

    /**
     * Edite un element existant
     *
     * Même restriction de colonnes que create().
     *
     * @param integer $id $id de l'élément
     * @param hash $data donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        return parent::update($keyid, $this->filter_price_fields($data), $keyvalue);
    }

    /**
     * Ne conserve que les colonnes de prix + audit d'un tableau de données.
     * Les colonnes produit historiques (reference, description, compte, club,
     * is_cotisation, nb_personnes_max, public, type_ticket, saisie_par) ne sont
     * plus écrites par cette façade — elles vivent sur `produits`.
     */
    private function filter_price_fields($data) {
        $allowed = array('produit_id', 'date', 'prix', 'nb_tickets',
            'created_at', 'created_by', 'updated_at', 'updated_by');
        return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     */
    public function image($key) {
        $vals = $this->get_by_id('id', $key);
        if (empty($vals)) {
            return $key;
        }

        $produit = $this->produits_model->get_by_id('id', $vals['produit_id']);
        $label = !empty($produit['description']) ? $produit['description'] : $produit['reference'];
        return $label . ' : ' . euro($vals['prix']);
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down.
     *
     * Délègue à Produits_model::selector() en conservant la clé de retour
     * `reference` (comportement actuel : le sélecteur de tarif travaille sur
     * la reference, pas sur l'id).
     *
     * @param $where selection
     * @param $order ordre de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        return $this->produits_model->selector($where, $order, $filter_section);
    }

    /**
     * Retourne les tarifs marqués comme produits de cotisation pour une section.
     *
     * Retourne les champs avec les noms attendus par paiements_en_ligne/cotisation :
     *   id, libelle, annee, montant, compte_cotisation_id, section_id, actif
     *
     * Filtrage : is_cotisation=1, date <= aujourd'hui.
     *
     * INNER JOIN sur produits : `tarifs.produit_id` est NOT NULL depuis la
     * migration 148 et les colonnes produit historiques ont été supprimées de
     * `tarifs` par la migration 149 (étape 12) — l'identité produit ne vit
     * plus que sur `produits`.
     */
    public function get_cotisation_products_for_section($club_id) {
        $today = $this->db->escape(date('Y-m-d'));
        $cid   = (int) $club_id;
        $sql = "SELECT tarifs.id AS id,
                       produits.description AS libelle,
                       YEAR(tarifs.date) AS annee,
                       tarifs.prix AS montant,
                       produits.compte AS compte_cotisation_id,
                       produits.club AS section_id,
                       1 AS actif
                FROM tarifs
                JOIN produits ON produits.id = tarifs.produit_id
                WHERE produits.club = $cid
                  AND produits.is_cotisation = 1
                  AND tarifs.date <= $today
                ORDER BY tarifs.date DESC, libelle ASC";
        return $this->db->query($sql)->result_array();
    }

    /**
     * Retourne un tarif de cotisation par son id.
     *
     * Vérifie que le tarif est bien marqué is_cotisation=1 et est valide (date).
     * Retourne les mêmes alias que get_cotisation_products_for_section().
     * Retourne false si introuvable ou non valide.
     */
    public function get_cotisation_product_by_id($id) {
        $today = $this->db->escape(date('Y-m-d'));
        $id    = (int) $id;
        $sql = "SELECT tarifs.id AS id,
                       produits.description AS libelle,
                       YEAR(tarifs.date) AS annee,
                       tarifs.prix AS montant,
                       produits.compte AS compte_cotisation_id,
                       produits.club AS section_id,
                       1 AS actif
                FROM tarifs
                JOIN produits ON produits.id = tarifs.produit_id
                WHERE tarifs.id = $id
                  AND produits.is_cotisation = 1
                  AND tarifs.date <= $today";
        $row = $this->db->query($sql)->row_array();
        return $row ? $row : false;
    }

    /**
     * Retourne le tarif applicable à la référence à la date données
     */
    public function get_tarif($reference, $date = "") {
        gvv_debug("get_tarif(reference=$reference, date=$date)");

        $section = $this->gvv_model->section();

        $this->db->select($this->joined_select_columns())
            ->from('tarifs')
            ->join('produits', 'produits.id = tarifs.produit_id')
            ->where('produits.reference', $reference)
            ->where("tarifs.date <= \"$date\"");

        if ($this->section) {
            $this->db->where('produits.club', $section['id']);
        }

        $result = $this->db->order_by('tarifs.date', 'desc')
            ->limit(1)
            ->get();

        gvv_debug("get_tarif " . $this->db->last_query());

        if ($result) {
            return $result->row_array();
        } else {
            gvv_error("get_tarif error: " . $this->table . ' - ' . $this->db->_error_message());
            return FALSE;
        }
    }

    /**
     * Retourne une ligne de base.
     *
     * `id` : accès direct sur `tarifs`, inchangé (au moins une surcharge club —
     * ACES — stocke tarifs.id directement dans avions.maprix/pompes.ppu et
     * s'attend à retrouver toutes les colonnes encore présentes sur la ligne).
     * `reference` : jointure avec `produits`, la reference n'étant plus fiable
     * comme filtre direct sur `tarifs` (compat des ~15 appelants historiques).
     */
    public function get_by_id($keyid, $keyvalue) {
        if ($keyid !== 'reference') {
            return parent::get_by_id($keyid, $keyvalue);
        }

        $this->db->select($this->joined_select_columns())
            ->from('tarifs')
            ->join('produits', 'produits.id = tarifs.produit_id')
            ->where('produits.reference', $keyvalue);

        $query = $this->db->get();
        gvv_debug("sql: " . $this->db->last_query());
        if ($query === FALSE) {
            gvv_error("sql error: " . $this->db->_error_message());
            return array();
        }
        return $query->row_array();
    }
}

/* End of file */
