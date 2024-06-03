<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *    Gestion de la facturation.
 *
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Facturation des vols planeurs
 *
 * Ce module est en charge de la génération des lignes de facture à partir
 * des vols. C'est également lui qui enregistre les achats de marchandise
 * a decompter (tickets de remorqué, etc).
 *
 * C'est typiquement un module à surgharger pour définir les regles de
 * facturation de chaque club (au moins jusqu'à ce que quelqu'un en
 * écrive un général qui puisse prendre en compte toute les particularités
 * de facturation.
 */
class Facturation_accabs extends Facturation {
    // Class attributes
    protected $attr;

    /**
     * Facture le lancement en remorqué
     */
    public function facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $lieudeco) {
        gvv_debug('Facturation remorqué');

        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name);
        $free = FALSE;
        if ($vi == VI) {
            $desc .= " VI";
            $free = TRUE;
        } elseif ($vi == VE) {
            $desc .= " vol d'essai";
            $free = TRUE;
        } elseif ($vi == CONCOURS) {
            $desc .= " concours";
        }

        $m25ans = $this->CI->membres_model->moins_25ans($pilote, $date);
        $this->CI->load->model("tarifs_model");

        if ($vi == 3) {
            // concours
            $produit = "Remorqué concours";

            $this->nouvel_achat_partage(array (
                    'date' => $date,
                    'produit' => $produit,
                    'quantite' => ($free) ? 0 : 1,
                    'description' => $desc,
                    'pilote' => $pilote,
                    'machine' => $machine,
                    'vol_planeur' => $vol_id
            ), $pilote, $payeur, $pourcentage, $name);

            return $desc;
        } elseif ($alt_rem <= 350) {

            $produit = "Remorqué 300m";
            $produit_m25ans = "Remorqué 300m -25ans";
            if ($m25ans) {
                $tarifs_m25ans = $this->CI->tarifs_model->get_tarif($produit_m25ans, $date);
                if (count($tarifs_m25ans)) {
                    $produit = $produit_m25ans;
                }
            }

            $this->nouvel_achat_partage(array (
                    'date' => $date,
                    'produit' => $produit,
                    'quantite' => ($free) ? 0 : 1,
                    'description' => $desc,
                    'pilote' => $pilote,
                    'machine' => $machine,
                    'vol_planeur' => $vol_id
            ), $pilote, $payeur, $pourcentage, $name);
        } else {

            // Remorqué à 500
            $desc = $this->facture_un_500($date, $desc, $pilote, $payeur, $pourcentage, $machine, $vol_id, $free, $name, $lieudeco);
        }

        // Supléments d'altitude
        if ($alt_rem > 500) {
            $sup = intval(($alt_rem - 500) / 100);

            $this->nouvel_achat_partage(array (
                    'date' => $date,
                    'produit' => "Remorqué 100m",
                    'quantite' => ($free) ? 0 : $sup,
                    'description' => sprintf(" à %02dh%02d", $hour, $minute),
                    'pilote' => $pilote,
                    'machine' => $machine,
                    'vol_planeur' => $vol_id
            ), $pilote, $payeur, $pourcentage, $name);
        }
        return $desc;
    }

    /**
     * Facture le lancement au treuil
     */
    public function facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name);
        $free = FALSE;
        if ($vi == VI) {
            $desc .= " VI";
            $free = TRUE;
        } else if ($vi == VE) {
            $desc .= " vol d'essai";
            $free = TRUE;
        }

        $this->nouvel_achat_partage(array (
                'date' => $date,
                'produit' => "Treuillé",
                'quantite' => ($free) ? 0 : 1,
                'description' => $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
        ), $pilote, $payeur, $pourcentage, $name);
    }

    /**
     * Facture un 500 éventuellement décompté
     */
    public function facture_un_500($date, $desc, $pilote, $payeur, $pourcentage, $machine, $vol_id, $free, $name, $lieudeco) {
        if ($free) {
            return;
        }

        $this->CI->load->model('tickets_model');

        $produit = "Remorqué 500m";
        if ($lieudeco != "LFOI") {
            $produit = "Remorqué extérieur 500m";
        }
        $m25ans = $this->CI->membres_model->moins_25ans($pilote, $date);
        if ($m25ans) {
            $this->CI->load->model("tarifs_model");
            $produit_m25ans = "Remorqué 500m -25ans";
            if ($lieudeco != "LFOI") {
                $produit_m25ans = "Remorqué extérieur 500m -25ans";
            }
            $tarifs_m25ans = $this->CI->tarifs_model->get_tarif($produit_m25ans, $date);
            if (count($tarifs_m25ans)) {
                $produit = $produit_m25ans;
            }
        }

        gvv_debug("facturation d'un remorqué à 500");

        $lance = 0; // Remorqué
        $this->decompte_ou_facture($produit, $lance, $desc, $vol_id, $date, '', $machine, 500, '', $pilote, $payeur, $pourcentage, $name);
        return $desc;
    }

    /**
     * Facture les heures de vols
     */
    public function facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;

        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name); // " à $debut"; // Prépare la description du produit
        $free = FALSE;
        if ($vi == VI) {
            $desc .= " VI"; // est-ce un vol d'initiation ?
            $free = TRUE;
        } else if ($vi == VE) {
            // est-ce un vol d'essai ?
            $desc .= " vol d'essai";
            $free = TRUE;
        }

        // on ne facture pas les privés
        if ($prive == PROPRIO_PRIVE) {
            $desc .= " privé";
            $free = TRUE;
        } elseif ($duree > $max_facturation) {
            $duree = $max_facturation;
        }

        // afiche la durée
        $hour = intval($duree / 60);
        $minute = $duree - ($hour * 60);
        $desc .= " sur $machine";
		$year = substr($date, 0, 4);

		$au_forfait = $this->forfait($pilote, $year);
        
        $this->nouvel_achat_partage(array (
                'date' => $date,
                'produit' => ($au_forfait) ? $tarif_forfait : $tarif_heure,
                'quantite' => ($free) ? 0 : $duree / 60,
                'format' => 'time',
                'description' => ($au_forfait) ? $desc . " au forfait" : $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
        ), $pilote, $payeur, $pourcentage);

        return $desc;
    }

    /**
     * Facture les heures moteur
     */
    public function facture_moteur($vol_id, $date, $debut, $machine, $duree, $produit, $vi, $pilote, $payeur, $pourcentage, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name);

        $free = FALSE;
        if ($vi == VI) {
            $desc .= " VI";
            $free = TRUE;
        } else if ($vi == VE) {
            $desc .= " vol d'essai";
            $free = TRUE;
        }

        $this->nouvel_achat_partage(array (
                'date' => $date,
                'produit' => $produit,
                'quantite' => ($free) ? 0 : $duree,
                'description' => $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
        ), $pilote, $payeur, $pourcentage, $name);
    }

    /**
     *
     * Vrai si le pilote paye au forfait
     *
     * @param unknown_type $pilote
     */
    private function forfait($pilote, $year) {
        // acces au model des achats
        $this->CI->load->model('achats_model');
        $nb = $this->CI->achats_model->a_achete($pilote, "Forfait heures", $year);
        return ($nb > 0);
    }

    /**
     * Facturation d'un vol.
     * Génère les lignes de facture correspondantes.
     * Marque le vol
     *
     * Régles de facturation:
     * * Les VI ne sont pas facturés
     * * Les vols d'essais ne sont pas facturés.
     * * Les vols gratuits font l'objet d'une ligne gratuite sur la facture
     *
     * Heures de vol
     * * Les machines privées ne sont pas facturées
     * * on ne facture pas au dela de 3 heures
     *
     * @param unknown_type $vol
     *            = array (
     *            'vpid' => '1654',
     *            'vpdate' => '2012-12-29',
     *            'vppilid' => 'vpeignot',
     *            'vpmacid' => 'F-CECO',
     *            'vpcdeb' => '12.00',
     *            'vpcfin' => '12.30',
     *            'vpduree' => '30.00',
     *            'tempmoteur' => '0.00',
     *            'vpobs' => '',
     *            'instructeur' => '',
     *            'vpcategorie' => '0',
     *            'vpautonome' => '3',
     *            'vpaltrem' => '500',
     *            'payeur' => 'ladams',
     *            'pourcentage' => '50',
     *            'facture' => '0',
     *            'remorqueur' => 'F-JUFA',
     *            'vplieudeco' => 'LFOI',
     *            'vpdc' => '0',
     *            'vpticcolle' => '0',
     *            'vpnumvi' => '',
     *            'pilote' => 'Vincent Peignot',
     *            'compte' => '37',
     *            'prive' => '0',
     *            'mprix' => 'Heure de vol biplace',
     *            'mprix_forfait' => 'Heure de vol forfait',
     *            'mprix_moteur' => 'Gratuit',
     *            'mmax_facturation' => '180',
     *            )
     */
    public function facture_vol_planeur($vol) {
        gvv_debug("facture_vol_planeur " . var_export($vol, true));
        $facture = $vol ['facture'];
        $vol_id = $vol ['vpid'];

        $this->CI->load->model('vols_planeur_model');
        $this->CI->load->model('membres_model');
        $this->CI->load->model('comptes_model');

        $date = $vol ['vpdate'];
        $debut = $vol ['vpcdeb'];
        $duree = $vol ['vpduree'];
        $machine = $vol ['vpmacid'];
        $pilote = $vol ['vppilid'];
        $lieudeco= $vol['vplieudeco'];
        $name = "";

        $remorqueur = $vol ['remorqueur'];

        $vi = $vol ['vpcategorie'];
        $prive = $vol ['prive'];
        $lancement = $vol ['vpautonome'];
        $alt_rem = $vol ['vpaltrem'];
        $payeur = $vol ['payeur'];
        $pourcentage = $vol ['pourcentage'];

        $tarif_heure = $vol ['mprix'];
        $tarif_forfait = $vol ['mprix_forfait'];
        $tarif_moteur = $vol ['mprix_moteur'];
        $max_facturation = $vol ['mmax_facturation'];
        $temp_moteur = $vol ['tempmoteur'];

        $data = array ();
        if ($lancement == REM) {
            // Facture remorqué
            $this->facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $lieudeco);
        } else if ($lancement == TREUIL) {
            // Facture Treuillé
            $this->facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name);
        } else {
            // echo "Autonome et extérieurs non facturés<br>";
        }

        $this->facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name);

        if ($temp_moteur > 0) {
            $this->facture_moteur($vol_id, $date, $debut, $machine, $temp_moteur, $tarif_moteur, $vi, $pilote, $payeur, $pourcentage, $name);
        }
    }
}