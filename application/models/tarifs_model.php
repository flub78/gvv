<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Tarifs model
 *
 * C'est un CRUD de base, la seule chose que fait cette classe
 * est de définir le nom de la table. Tous les méthodes sont
 * implémentés dans Common_Model
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Tarifs_model extends Common_Model {
    public $table = 'tarifs';
    protected $primary_key = 'id';

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $session = $this->session->all_userdata();
        $tarif_tout = isset($session['filter_tarif_tout']) ? $session['filter_tarif_tout'] : true;
        $tarif_date = isset($session['filter_tarif_date']) ? $session['filter_tarif_date'] : '';

        gvv_debug("session=" . var_export($session, true));
        gvv_debug("tarifs select tout=" . $tarif_tout);
        gvv_debug("tarifs select date=" . $tarif_date);

        if ($tarif_tout) {
            $this->db->select('tarifs.id as id, date, date_fin, public, tarifs.reference as reference, tarifs.description as description, tarifs.prix as prix, tarifs.club as club, comptes.nom as nom_compte, nb_tickets, type_ticket')
                ->from("tarifs, comptes")
                ->where("tarifs.compte = comptes.id")
                ->order_by('tarifs.reference', 'asc')
                ->order_by('date', 'desc');

            if ($this->section) {
                $this->db->where('tarifs.club', $this->section_id);
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
                    $public = "public = 1";
                } elseif ($session['filter_tarif_public'] == 2) {
                    $public = "public = 0";
                } else {
                    $public = "public >= 0"; // match everything
                }
            } else {
                $public = "public >= 0"; // match everything
            }

            // Select ordered by desc date
            $this->db->select('tarifs.id as id, date, date_fin, public, tarifs.reference as reference, tarifs.description as description, tarifs.prix as prix, tarifs.club as club, comptes.nom as nom_compte, nb_tickets, type_ticket')
                ->from("tarifs, comptes")
                ->where("tarifs.compte = comptes.id")
                ->where("date <= '$filter_date'")
                ->where($public);

            if ($this->section) {
                $this->db->where('tarifs.club', $this->section_id);
            }

            $this->db->order_by('tarifs.reference', 'asc')
                ->order_by('date', 'desc');
            // ->get()->result_array();
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
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        // I tis a create the primary key should not be set
        $key =  $this->primary_key;
        if (isset($data[$key])) {
            unset($data[$key]);
        }
        if ($data['type_ticket'] == '') {
            unset($data['type_ticket']);
        }
        if ($this->db->insert($this->table, $data)) {
            $last_id = $this->db->insert_id();

            gvv_debug("create succesful, table=" . $this->table . ", \$last_id=$last_id, data=" . var_export($data, true));
            if (! $last_id) {
                $last_id = $data[$this->primary_key];
                gvv_debug("\$last_id=$last_id (\$data[primary_key])");
            }
            return $last_id;
        } else {
            gvv_error("create error: " . $this->table . ' - ' . $this->db->_error_message());
            return FALSE;
        }
    }

    /**
     * Edite un element existant
     *
     * @param integer $id
     *            $id de l'élément
     * @param hash $data
     *            donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        if ($keyvalue == '')
            $keyvalue = $data[$keyid];
        $this->db->where($keyid, $keyvalue);
        unset($data[$keyid]);

        if (isset($data['type_ticket'])) {
            if ($data['type_ticket'] == '') {
                unset($data['type_ticket']);
            }
        }

        if (!$this->db->update($this->table, $data)) {
            // Get MySQL error message
            $error = $this->db->_error_message();
            gvv_error("MySQL Error #$errno: $error");
        }
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        $vals = $this->get_by_id('id', $key);
        return $vals['reference'] . ' : ' . $vals['prix'];
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     *
     * Le selecteur de tarif est particulier pace que ce n'est pas sur l'ID qu'il travaille
     * Cela démontre probablement que la table devrait être restructurée
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        $key = $this->primary_key;

        $this->db->select('*')
            ->from($this->table)
            ->where($where);
        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }
        $allkeys = $this->db->get()->result_array();

        $result = array();
        foreach ($allkeys as $row) {
            $value = $row[$key];
            $reference = $row['reference'];
            $result[$reference] = $this->image($value);
        }
        if ($order == "asc") {
            asort($result);
        } else {
            arsort($result);
        }
        return $result;
    }

    /**
     * Retourne le tarif applicable à la référence à la date données
     */
    public function get_tarif($reference, $date = "") {
        gvv_debug("get_tarif(reference=$reference, date=$date)");

        $section = $this->gvv_model->section();

        $this->db->where('reference', $reference)
            ->where("date <= \"$date\"");

        if ($this->section) {
            $this->db->where('tarifs.club', $section['id']);
        }

        $result = $this->db->order_by('date', 'desc')
            ->limit(1)
            ->get($this->table);

        gvv_debug("get_tarif " . $this->db->last_query());

        if ($result) {
            return $result->row_array();
        } else {
            gvv_error("get_tarif error: " . $this->table . ' - ' . $this->db->_error_message());
            return FALSE;
        }
    }
}

/* End of file */
