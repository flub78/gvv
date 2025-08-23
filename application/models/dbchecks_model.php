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
                'description' => $row->desc,
                'nom' => $row->nom,
                'num_cheque' => $row->num_cheque
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
            $checkbox = '<input type="checkbox" name="selection[]" value="' . "cbdel_" .$row->id . '">';

            // There is an issue, report it
            $elt = [
                $checkbox,
                $row->id,
                $row->date_op,
                $row->description,
                $row->num_cheque,
                $row->montant,
                $row->compte1,
                $row->compte2
            ];

            if (array_key_exists($row->compte1, $comptes)) {
                $elt[5] = $comptes[$row->compte1]['nom'];
            } else {
                $non_existing_accounts[$row->compte1] = true;
            }
            if (array_key_exists($row->compte2, $comptes)) {
                $elt[6] = $comptes[$row->compte2]['nom'];
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
                $elt = [$row->id, $row->nom, $row->desc, $row->debit, $row->credit, $solde, $solde_compte];
                $res[] = $elt;
            }
        }
        return $res;
    }

    public function sections() {
        $query = $this->db->query('SELECT e.id, e.date_op, e.montant, e.description, e.num_cheque, e.gel, e.club, 
            c1.nom AS compte1_nom, c1.codec AS compte1_codec, c1.club AS compte1_club,
            c2.nom AS compte2_nom, c2.codec AS compte2_codec, c2.club AS compte2_club
            FROM ecritures e
            JOIN comptes c1 ON e.compte1 = c1.id
            JOIN comptes c2 ON e.compte2 = c2.id
            WHERE  ((e.club != c1.club) OR (e.club != c2.club) OR (c1.club != c2.club))
            ORDER BY e.date_op');

        $res = [];
        foreach ($query->result() as $row) {
            $id = $row->id;

            // $solde_compte = $this->ecritures_model->solde_compte($id);

            $checkbox = '<input type="checkbox" name="selection[]" value="' . "cbdel_" .$row->id . '">';

            $res[] = [
                $checkbox,
                $row->id,
                $row->date_op,
                $row->montant, 
                $row->description,
                $row->num_cheque,
                $row->club,
                $row->compte1_nom,
                $row->compte1_codec,
                $row->compte1_club,
                $row->compte2_nom,
                $row->compte2_codec,
                $row->compte2_club
            ];
           
        }
        return $res;
    }


    /*
     * 
     */
}

/* End of file */