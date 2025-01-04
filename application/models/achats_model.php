<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *	Modèle des achats (lignes de factures)
 *
 */

$CI = &get_instance();
$CI->load->model('common_model');

class Achats_model extends Common_Model {
    public $table = 'achats';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = 'achats.id as id, achats.date as date, tarifs.reference as produit, quantite, '
            . "tarifs.prix as prix_unit, tarifs.prix * quantite as prix, "
            . 'achats.description as description,  pilote, facture';

        $db_res = $this->db
            ->select($select)
            ->from("achats, tarifs")
            ->where("achats.produit = tarifs.reference")
            ->limit($nb, $debut)
            ->order_by('achats.date')
            ->get();
        $result = $this->get_to_array($db_res);

        foreach ($result as $key => $row) {
            $kid = $this->primary_key;
            $image = $this->image($row[$kid], TRUE);
            $result[$key]['image'] = $image;
        }

        $this->gvvmetadata->store_table("vue_achats", $result, $this->db->last_query());
        return $result;
    }

    /**
     *	Retourne le tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select($nb = 1000, $debut = 0) {
        $select = 'achats.id as id, achats.date as date, tarifs.reference as produit, quantite, tarifs.prix as prix_unit, tarifs.prix * quantite as prix'
            . ', achats.description as description,  pilote, facture';

        $db_res = $this->db
            ->select($select)
            ->from("achats, tarifs")
            ->where("achats.produit = tarifs.reference")
            ->limit($nb, $debut)
            ->order_by('achats.date')
            ->get();
        $res = $this->get_to_array($db_res);
        return $res;
    }

    /**
     *	Retourne le tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_raw($nb = 1000, $debut = 0) {

        $db_res = $this->db->select('*')->from("achats")->get();
        $res = $this->get_to_array($db_res);
        return $res;
    }

    /**
     *	Retourne la liste des achats d'un pilote
     *	@return objet		  La liste
     */
    public function achats_de($pilote) {
        $select = 'achats.id as id, achats.date as date, tarifs.reference as nom_produit, quantite'
            . ', tarifs.prix as prix_unit, tarifs.prix * quantite as prix'
            . ', achats.description as description,  pilote, facture, compte, produit';
        $db_res = $this->db
            ->select($select)
            ->from("achats, tarifs")
            ->where("achats.produit = tarifs.reference")
            ->where(array("pilote" => $pilote, "facture" => 0))
            ->order_by('date')->get();
        $res = $this->get_to_array($db_res);
        return $res;
    }

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function factures_en_cours() {
        $select = ' sum(tarifs.prix * quantite) as total' . ', pilote, facture, mnom, mprenom';
        $db_res = $this->db
            ->select($select)
            ->from("achats, tarifs, membres")
            ->where("achats.produit = tarifs.reference")
            ->where("achats.pilote = membres.mlogin")
            ->where(array("facture" => 0))
            ->group_by('pilote')
            ->order_by("mnom, mprenom")
            ->get();
        $res = $this->get_to_array($db_res);
        return $res;
    }

    /**
     *	Retourne les achats correspondant à une facture
     *  @deprecated
     *	@return objet		  La liste
     */
    public function achats_de_facture($facture) {
        $select = 'achats.id as id, achats.date as date, tarifs.reference as nom_produit, quantite'
            . ', tarifs.prix as prix_unit, tarifs.prix * quantite as prix'
            . ', achats.description as description,  pilote, facture, compte';

        $db_res = $this->db
            ->select($select)
            ->from("achats, tarifs")
            ->where("achats.produit = tarifs.reference")
            ->where(array("facture" => $facture))
            ->order_by('date')->get();
        $res = $this->get_to_array($db_res);
        return $res;
    }

    /**
     *	Calcul the total des achats d'un pilote
     *  @deprecated
     *	@return objet		  La liste
     */
    public function montant_de($pilote) {
        $selection = $this->achats_de($pilote);
        $montant = 0.0;
        foreach ($selection as $row) {
            $montant += $row['prix'];
        }
        return $montant;
    }

    /**
     *	Retourne le nombre d'achat qu'a fait un pilote sur un produit
     *	@return objet		  La liste
     */
    public function a_achete($pilote, $produit, $annee) {
        $select = array(
            'pilote' => $pilote,
            'produit' => $produit,
            "YEAR(date)" => $annee
        );
        return $this->count($select);
    }

    /**
     * Retourne la quantité totale de produit acheté dans l'année
     * @param unknown $pilote
     * @param unknown $produit
     * @param unknown $annee
     */
    public function somme_achats($pilote, $produit, $annee) {

        $where = array(
            'pilote' => $pilote,
            'produit' => $produit,
            "YEAR(date)" => $annee
        );

        $row = $this->db->select('quantite')
            ->from('achats')
            ->where($where)
            ->select_sum('quantite')
            ->get()->row();

        // var_dump($row); exit;
        return isset($row->quantite) ? $row->quantite : 0;
    }

    /**
     *	Ajoute un achat
     *
     * @param hash des valeurs
     */
    public function create($data) {
        $data['saisie_par'] = $this->dx_auth->get_username();

        // enregistre le prix unitaire du produit au moment de l'achat
        gvv_debug("achat create: " . var_export($data, true));
        $produit = $data['produit'];
        $this->load->model('achats_model');

        $product_info = $this->tarifs_model->get_tarif($produit, $data['date']);
        if (count($product_info) == 0) {
            // Il n'y a pas de référence produit
            throw new Exception("Le produit \"$produit\", référencé par la facturation, n'existe pas dans les tarifs au " . $data['date']);
        }
        $data['prix'] = $product_info['prix'];

        $this->db->trans_start();

        if (isset($data['format'])) {
            $is_time = TRUE;
            unset($data['format']);
        } else {
            $is_time = FALSE;
        }

        if ($this->db->insert($this->table, $data)) {
            $data['id'] = $this->db->insert_id();

            $this->load->model('achats_model');
            $data['is_time'] = $is_time;
            $this->achats_model->gen_ecriture($data);

            $this->db->trans_complete();
            return $data['id'];
        } else {
            $msg = $this->db->_error_message();
            $this->db->trans_complete();
            gvv_error("Erreur lors de l'ajout de l'achat: " . $msg);
            return FALSE;
        }
    }

    /**
     *	Edite un element existant
     *
     *	@param integer $id	$id de l'élément
     *	@param string  $data donnée à remplacer
     *	@return bool		Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        // détruit la ligne d'écriture correspondante
        $this->load->model('ecritures_model');
        $this->ecritures_model->delete_all(array(
            'achat' => $data[$keyid]
        ));

        // enregistre le prix unitaire du produit au moment de l'achat
        $produit = $data['produit'];
        $product_info = $this->tarifs_model->get_tarif($produit, $data['date']);
        if (count($product_info) == 0) {
            // Il n'y a pas de référence produit
            throw new Exception("Le produit \"$produit\", référencé par la facturation, n'existe pas dans les tarifs au " . $data['date']);
        }
        $data['prix'] = $product_info['prix'];

        $keyvalue = $data[$keyid];
        $this->db->where($keyid, $keyvalue);
        $this->db->update($this->table, $data);

        if (isset($data['format'])) {
            $is_time = TRUE;
            unset($data['format']);
        } else {
            $is_time = FALSE;
        }

        $this->gen_ecriture($data);
    }

    /**
     * Génère l'écriture correspondante à un achat
     * @param unknown_type $data
     */
    function gen_ecriture($data = array()) {

        // Cherche le prix unitaire et le compte du produit
        $tarif = $this->tarifs_model->get_tarif($data['produit'], $data['date']);

        // Crée l'écriture correspondante
        // Array ( [id] => 0 [date] => 2011-07-18 [produit] => 15 [quantite] => 1 [prix] =>
        // [description] => [pilote] => mcdr [facture] => [saisie_par] => 0 [club] => )

        $this->load->model('ecritures_model');
        $this->load->model('comptes_model');

        $compte_pilote = $this->comptes_model->compte_pilote($data['pilote']);

        if (array_key_exists('is_time', $data)) {
            $quantite = ($data['is_time']) ? minute_to_time($data['quantite'] * 60) : $data['quantite'];
        } else {
            $quantite = $data['quantite'];
        }

        $ecriture = array(
            'id' => 0,
            'annee_exercise' => $this->config->item('annee_exercise'),
            'date_creation' => date("Y-m-d", time()),
            'date_op' => $data['date'],
            'compte1' => $compte_pilote,
            'compte2' => $tarif['compte'],
            'montant' => $tarif['prix'] * $data['quantite'],
            'description' => $data['description'],
            'num_cheque' => $tarif['reference'],
            'saisie_par' => $data['saisie_par'],
            'achat' => $data['id'],
            'quantite' => $quantite,
            'prix' => $tarif['prix']
        );

        $this->ecritures_model->create_ecriture($ecriture);
    }

    /**
     * delete
     * @param unknown_type $data
     */
    function delete($where = array()) {

        // détruit les lignes d'écriture correspondante        
        $selection = $this->select_all($where);
        $this->load->model('ecritures_model');
        $this->load->model('tickets_model');

        foreach ($selection as $row) {
            $this->ecritures_model->delete_all(array(
                'achat' => $row['id']
            ));
            $this->tickets_model->delete_all(array(
                'achat' => $row['id']
            ));
        }

        // detruit la ligne d'achat
        $this->db->delete($this->table, $where);
    }

    /**
     * Liste les ventes de l'année
     * @param unknown_type $year
     */
    function list_per_year($year) {

        $select = 'achats.id as id, achats.date as date, achats.produit as produit, sum(quantite) as quantite, '
            . "achats.prix as prix_unit, sum(achats.prix * quantite) as prix ";

        $db_res = $this->db->select($select)
            ->from("achats")
            ->where("YEAR(achats.date) = $year")
            ->order_by('achats.produit')
            ->group_by('achats.produit, prix_unit')
            ->get();

        $res = $this->get_to_array($db_res);

        $this->gvvmetadata->store_table("vue_achats_per_year", $res, $this->db->last_query());
        return $res;
    }

    /** 
     * For some reasons unit test library can only be invoked directly from the controller.
     * This test returns an array of test results.
     */
    function test() {
        $res = [];

        $res[] = ["description" => "Model achats", "result" => true];

        // Count elements in attachments table
        $initial_count = $this->db->count_all($this->table);
        $res[] = ["description" => "Initial count achats: " . $initial_count, "result" => true];

        // Insert a dummy element
        $data = array(
            'date' => '2025-01-01',
            'produit' => '80',
            'quantite' => '2',
            'prix' => '25.0',
            'description' => '2 remorqués',
            'pilote' => 'asterix',
            'saisie_par' => 'moi'
        );

        $insert_result = $this->db->insert($this->table, $data);
        $last_id = $this->db->insert_id();

        if (!$insert_result) {
            $res[] = ["description" => "Insert returns false", "result" => false];
            $msg = $this->db->_error_message();
            $this->db->trans_complete();
            gvv_error("Test: Erreur lors de l'ajout de l'achat: " . $msg);
        }

        $count = $this->db->count_all($this->table);

        $res[] = ["description" => "Insert returns true", "result" => $insert_result];
        $res[] = ["description" => "Attachment created", "result" => ($count == $initial_count + 1)];

        // Get last inserted id
        $res[] = ["description" => "Last inserted ID: " . $last_id, "result" => ($last_id > 0)];

        // Get last inserted element
        $last = $this->get_by_id('id', $last_id);

        $res[] = ["description" => "Last element id", "result" => ($last['id'] == $last_id)];
        $res[] = ["description" => "Last element produit", "result" => ($last['produit'] == '80')];

        // Delete last inserted element
        $delete_result = $this->db->delete($this->table, array('id' => $last_id));
        $res[] = ["description" => "Delete returns true", "result" => $delete_result];

        // Verify deletion
        $count_after_delete = $this->db->count_all($this->table);
        $res[] = ["description" => "Attachment deleted", "result" => ($count_after_delete == $initial_count)];

        return $res;
    }
}

/* End of file */