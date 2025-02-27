<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('ecritures_model');

/**
 * Database verifications
 *
 * check the database coherency
 */
class Dbchecks_model extends Common_Model {

    /**
     * Recherche de comptes non existants dans les ecritures
     * Recherche d'achats non existant dans les ecritures
     *
     * @return boolean[][][]|NULL[][][]|mixed[][][]|unknown[][][]
     */
    public function unreferenced_accounts() {

        // Hash table pour les comptes
        $comptes = [];
        $query = $this->db->query('SELECT * FROM `comptes`');
        foreach ($query->result() as $row) {
            $id = $row->id;
            $elt = [
                'id' => $id,
                'description' => $row->desc
            ];
            $comptes[$id] = $elt;
        }

        // Hash table pour les achats
        $achats = [];
        $query = $this->db->query('SELECT * FROM `achats`');
        foreach ($query->result() as $row) {
            $id = $row->id;
            $elt = [
                'id' => $id,
                'description' => $row->date . " : " . $row->produit
            ];
            $achats[$id] = $elt;
        }

        // Vérification des références dans écritures
        $query = $this->db->query('SELECT * FROM `ecritures`');
        $select = [];
        $non_existing_accounts = [];
        foreach ($query->result() as $row) {
            if (array_key_exists($row->compte1, $comptes) && array_key_exists($row->compte2, $comptes)) {
                // if compte1 and compte2 exist, no problem, check the next line
                continue;
            }

            // There is an issue, report it
            $elt = [
                $row->date_op,
                $row->description,
                $row->montant,
                $row->compte1,
                $row->compte2
            ];
            if (array_key_exists($row->compte1, $comptes)) {
                $elt[3] = $comptes[$row->compte1]['description'];
            } else {
                $non_existing_accounts[$row->compte1] = true;
            }
            if (array_key_exists($row->compte2, $comptes)) {
                $elt[4] = $comptes[$row->compte2]['description'];
            } else {
                $non_existing_accounts[$row->compte2] = true;
            }
            $select[] = $elt;
        }

        // echo 'Total Results: ' . $query->num_rows();
        $nea = [];
        foreach ($non_existing_accounts as $key => $value) {
            $nea[] = [$key];
        }

        $purchases = [];
        // Check for non existing purchases
        foreach ($query->result() as $row) {
            if ($row->achat) {
                if (!array_key_exists($row->achat, $achats)) {
                    $elt = [
                        $row->id,
                        $row->date_op,
                        $row->description,
                        $row->montant,
                        $row->achat
                    ];
                    $purchases[] = $elt;
                }
            }
        }

        return [
            'lines' => $select,
            'accounts' => $nea,
            'bad_purchase_lines' => $purchases
        ];
    }

    /**
     * Looks for uncoherencies in glider flights
     */
    public function volsp_references() {
        // Hash table pour les comptes
        $comptes = [];
        $query = $this->db->query('SELECT * FROM `comptes`');
        foreach ($query->result() as $row) {
            $id = $row->id;
            $elt = [
                'id' => $id,
                'description' => $row->desc
            ];
            $comptes[$id] = $elt;
        }

        // Hash table pour les membres
        $members = [];
        $unknown_members = [];
        $query = $this->db->query('SELECT * FROM `membres`');
        foreach ($query->result() as $row) {
            $id = $row->mlogin;
            $elt = [
                'id' => $id,
                'nom' => $row->mnom,
                'prenom' => $row->mprenom
            ];
            $members[$id] = $elt;
        }

        // Hash table pour les planeurs
        $machines = [];
        $unknown_machines = [];

        $query = $this->db->query('SELECT * FROM `machinesp`');
        foreach ($query->result() as $row) {
            $id = $row->mpimmat;
            $elt = [
                'id' => $id,
                'modele' => $row->mpmodele
            ];
            $machines[$id] = $elt;
        }

        $query = $this->db->query('SELECT * FROM `volsp`');
        foreach ($query->result() as $row) {
            $elt = [
                $row->vpdate,
                $row->vpcdeb,
                $row->vppilid,
                $row->vpmacid,
                $row->vpinst
            ];

            $bad_references = false;

            if (! array_key_exists($row->vppilid, $members)) {
                $elt[2] = "inconnu " . $row->vppilid;
                $bad_references = true;
                if (! array_key_exists($row->vppilid, $unknown_members)) {
                    $unknown_members[$row->vppilid] = true;
                }
            }

            if (! array_key_exists($row->vpmacid, $machines)) {
                $elt[3] = "inconnu " . $row->vpmacid;
                $bad_references = true;
                if (! array_key_exists($row->vpmacid, $unknown_machines)) {
                    $unknown_machines[$row->vpmacid] = true;
                }
            }

            if ($row->vpinst && ! array_key_exists($row->vpinst, $members)) {
                $elt[4] = "inconnu " . $row->vpinst;
                $bad_references = true;
                if (! array_key_exists($row->vpinst, $unknown_members)) {
                    $unknown_members[$row->vpinst] = true;
                }
            }

            if ($bad_references) {
                $select[] = $elt;
            }
        }
        $nepil = [];
        foreach ($unknown_members as $key => $value) {
            $nepil[] = [
                $key
            ];
        }

        $nemach = [];
        foreach ($unknown_machines as $key => $value) {
            $nemach[] = [
                $key
            ];
        }

        return [
            'vols' => $select,
            'pils' => $nepil,
            'machines' => $nemach
        ];
    }

    /**
     * Looks for uncoherencies in glider flights
     */
    public function volsa_references() {
        // Hash table pour les comptes
        $comptes = [];
        $query = $this->db->query('SELECT * FROM `comptes`');
        foreach ($query->result() as $row) {
            $id = $row->id;
            $elt = [
                'id' => $id,
                'description' => $row->desc
            ];
            $comptes[$id] = $elt;
        }

        // Hash table pour les membres
        $members = [];
        $unknown_members = [];
        $query = $this->db->query('SELECT * FROM `membres`');
        foreach ($query->result() as $row) {
            $id = $row->mlogin;
            $elt = [
                'id' => $id,
                'nom' => $row->mnom,
                'prenom' => $row->mprenom
            ];
            $members[$id] = $elt;
        }

        // Hash table pour les planeurs
        $machines = [];
        $unknown_machines = [];

        $query = $this->db->query('SELECT * FROM `machinesa`');
        foreach ($query->result() as $row) {
            $id = $row->macimmat;
            $elt = [
                'id' => $id,
                'modele' => $row->macmodele
            ];
            $machines[$id] = $elt;
        }

        $query = $this->db->query('SELECT * FROM `volsa`');
        foreach ($query->result() as $row) {
            $elt = [
                $row->vadate,
                $row->vacdeb,
                $row->vapilid,
                $row->vamacid,
                $row->vainst
            ];

            $bad_references = false;

            if (! array_key_exists($row->vapilid, $members)) {
                $elt[2] = "inconnu " . $row->vapilid;
                $bad_references = true;
                if (! array_key_exists($row->vapilid, $unknown_members)) {
                    $unknown_members[$row->vapilid] = true;
                }
            }

            if (! array_key_exists($row->vamacid, $machines)) {
                $elt[3] = "inconnu " . $row->vamacid;
                $bad_references = true;
                if (! array_key_exists($row->vamacid, $unknown_machines)) {
                    $unknown_machines[$row->vamacid] = true;
                }
            }

            if ($row->vainst && ! array_key_exists($row->vainst, $members)) {
                $elt[4] = "inconnu " . $row->vainst;
                $bad_references = true;
                if (! array_key_exists($row->vainst, $unknown_members)) {
                    $unknown_members[$row->vainst] = true;
                }
            }

            if ($bad_references) {
                $select[] = $elt;
            }
        }
        $nepil = [];
        foreach ($unknown_members as $key => $value) {
            $nepil[] = [
                $key
            ];
        }

        $nemach = [];
        foreach ($unknown_machines as $key => $value) {
            $nemach[] = [
                $key
            ];
        }

        return [
            'vols' => $select,
            'pils' => $nepil,
            'machines' => $nemach
        ];
    }

    /**
     * Looks for uncoherencies in glider flights
     */
    public function achats_references() {

        // Hash table pour les membres
        $members = [];
        $unknown_members = [];
        $query = $this->db->query('SELECT * FROM `membres`');
        foreach ($query->result() as $row) {
            $id = $row->mlogin;
            $elt = ['id' => $id, 'nom' => $row->mnom, 'prenom' => $row->mprenom];
            $members[$id] = $elt;
        }
        echo "membres = " . count($members) . "\n";

        // Hash table pour les vols planeurs
        $vols_planeur = [];
        $query = $this->db->query('SELECT * FROM `volsp`');
        foreach ($query->result() as $row) {
            $elt = [$row->vpdate, $row->vpcdeb, $row->vppilid, $row->vpmacid, $row->vpinst];
            $vols_planeur[$row->vpid] = $elt;
        }
        echo "vols planeur = " . count($vols_planeur) . "\n";


        // Hash table pour les vols avion
        $vols_avion = [];
        $query = $this->db->query('SELECT * FROM `volsa`');
        foreach ($query->result() as $row) {
            $elt = [$row->vadate, $row->vacdeb, $row->vapilid, $row->vamacid, $row->vainst];
            $vols_avion[$row->vaid] = $elt;
        }
        echo "vols avion = " . count($vols_avion) . "\n";

        // Hash table pour les avions
        $avions = [];
        $query = $this->db->query('SELECT * FROM `machinesa`');
        foreach ($query->result() as $row) {
            $id = $row->macimmat;
            $elt = ['id' => $id, 'modele' => $row->macmodele];
            $avions[$id] = $elt;
        }
        echo "avions = " . count($avions) . "\n";

        // Hash table pour les planeurs
        $planeurs = [];
        $query = $this->db->query('SELECT * FROM `machinesp`');
        foreach ($query->result() as $row) {
            $id = $row->mpimmat;
            $elt = ['id' => $id, 'modele' => $row->mpmodele];
            $planeurs[$id] = $elt;
        }
        echo "planeurs = " . count($planeurs) . "\n";

        /**
         * Pour les achats on peut vérifier
         *      - qu'ils référencent un produit
         *          - qui existe pour la date d'achat
         *      - qu'ils référencent un pilote
         *      - que s'ils référencent un vol planeur
         *          - il existe
         *          - sur un planeur qui existe
         *      - que s'ils référencent un vol avion
         *          - il existe
         *          - sur un avion qui existe
         */
        $query = $this->db->query('SELECT * FROM `achats`');
        foreach ($query->result() as $row) {
            $id = $row->id;
            //$elt = ['id' => $id, 'modele' => $row->macmodele];

            if ($row->pilote && ! array_key_exists($row->pilote, $members)) {
                echo "pilote inconnu " . $row->pilote;
            }

            if ($row->vol_planeur) {
                if (! array_key_exists($row->vol_planeur, $vols_planeur)) {
                    echo "vol planeur inconnu" . $row->vol_planeur;
                }

                if (! array_key_exists($row->machine, $planeurs)) {
                    echo "planeur inconnu" . $row->machine;
                }
            }

            if ($row->vol_avion) {
                if (! array_key_exists($row->vol_avion, $vols_avion)) {
                    echo "vol avion inconnu" . $row->vol_avion;
                }

                if (! array_key_exists($row->machine, $avions)) {
                    echo "avion inconnu" . $row->machine;
                }
            }
        }

        exit;
        return ['vols' => $select, 'pils' => $nepil, 'machines' => $nemach];
    }

    public function soldes() {
        $query = $this->db->query('SELECT * FROM `comptes`');
        foreach ($query->result() as $row) {
            $solde = $row->credit -  $row->debit;
            $id = $row->id;

            $solde_compte = $this->ecritures_model->solde_compte($id);

            if ($solde != $solde_compte) {

                echo "id=" . $row->id
                    . " " . $row->nom
                    . " " . $row->desc
                    . ", debit=" . $row->debit
                    . ", credit=" . $row->credit
                    . ", solde=" . $solde
                    . ", solde_compte=" . $solde_compte
                    . "<br>";
            }
        }
    }


    /*
     * 
     */
}

/* End of file */