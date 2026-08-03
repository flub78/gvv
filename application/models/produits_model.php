<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Produits model
 *
 * CRUD sur la table `produits` (identité du produit — reference, description,
 * compte, club, is_cotisation, nb_personnes_max, public, type_ticket). Les prix
 * datés restent sur `tarifs` (voir tarifs_model.php).
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Produits_model extends Common_Model {
    public $table = 'produits';
    protected $primary_key = 'id';

    /**
     * Ajoute un élément.
     *
     * Le formulaire soumet toujours un champ caché `id` (vide en création,
     * cf. bs_formView.php) ; l'INSERT échoue en SQL strict si on le laisse
     * passer tel quel (id = '' n'est pas une valeur entière valide, même sur
     * une colonne AUTO_INCREMENT). Même garde que l'ancien Tarifs_model.
     */
    public function create($data) {
        if (isset($data[$this->primary_key])) {
            unset($data[$this->primary_key]);
        }
        return parent::create($data);
    }

    /**
     * Retourne le tableau utilisé pour l'affichage par page (vue_produits).
     *
     * @return objet La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        // Sous-requête : tarif applicable à la date du jour (le plus récent
        // dont la date de départ n'est pas dans le futur, départage par id le
        // plus grand en cas d'égalité de date — même règle que la migration
        // 146). IFNULL -> '' pour un affichage vide plutôt que "0,00 €"
        // quand le produit n'a aucun tarif applicable aujourd'hui.
        $prix_du_jour_subquery = "(SELECT t.prix FROM tarifs t
            WHERE t.produit_id = produits.id AND t.date <= CURDATE()
            ORDER BY t.date DESC, t.id DESC LIMIT 1)";

        $this->db->select('produits.id as id, produits.reference as reference, produits.description as description,
                            produits.club as club, comptes.nom as nom_compte, produits.public as public,
                            produits.is_cotisation as is_cotisation, produits.nb_personnes_max as nb_personnes_max,
                            produits.type_ticket as type_ticket')
            ->select('IFNULL(' . $prix_du_jour_subquery . ", '') as prix", FALSE)
            ->from('produits')
            ->join('comptes', 'produits.compte = comptes.id')
            ->order_by('produits.reference', 'asc');

        if ($this->section) {
            $this->db->where('produits.club', $this->section_id);
        }

        $result = $this->safe_get();

        foreach ($result as $key => $row) {
            $result[$key]['image'] = $this->image($row['id']);
            $section = $this->sections_model->get_by_id('id', $row['club']);
            $result[$key]['section_name'] = $section['nom'];
        }

        $this->gvvmetadata->store_table("vue_produits", $result);
        return $result;
    }

    /**
     * Retourne une chaine de caractère qui identifie un produit de façon unique.
     * Cette chaine est utilisée dans les affichages (sélecteurs, listes).
     */
    public function image($key) {
        $vals = $this->get_by_id('id', $key);
        if (empty($vals)) {
            return $key;
        }
        if ($vals['description'] == '') {
            return $vals['reference'];
        }
        return $vals['description'];
    }

    /**
     * Retourne un hash utilisable dans un menu drop-down.
     *
     * Clé = reference (et non l'id), comme l'ancien Tarifs_model::selector()
     * qu'il remplace : de nombreux appelants stockent la reference texte du
     * produit (ex. avions.maprix, pompes.ppu), pas son id.
     *
     * @param $where selection
     * @param $order ordre de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        $this->db->select('*')
            ->from($this->table)
            ->where($where);
        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }
        $allkeys = $this->db->get()->result_array();

        $result = array();
        foreach ($allkeys as $row) {
            $reference = $row['reference'];
            $result[$reference] = $this->image($row['id']);
        }
        if ($order == "asc") {
            asort($result);
        } else {
            arsort($result);
        }
        return $result;
    }
}

/* End of file */
