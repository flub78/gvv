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
 * C'est typiquement un module à surcharger pour définir les regles de
 * facturation de chaque club (au moins jusqu'à ce que quelqu'un en
 * écrive un général qui puisse prendre en compte toute les particularités
 * de facturation.
 */
class Facturation_dac extends Facturation {
    // Class attributes
    protected $attr;

    /**
     * Facture le lancement en remorqué
     */
    public function facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf("Sleepstart vlucht %02du%02d %s", $hour, $minute, $name, $alt_rem);
        $free = FALSE;
        if ($vi == 1) {
            $desc .= " Initiatievlucht";
            $free = TRUE;
        } else if ($vi == 2) {
            $desc .= " PR vlucht";
            $free = TRUE;
        }

        if ($free) {
            return;
        }

        $lance = 3; // sleep
        $produit = "Sleepstart ";
        $produit .= $alt_rem;
        $produit .= "ft";

        $this->decompte_ou_facture($produit, $lance, $desc, $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name);
    }

    /**
     * Facture le lancement au treuil
     *
     * ALTER TABLE `tickets` CHANGE `achat` `achat` INT( 11 ) NULL COMMENT 'Numéro de l''achat'
     */
    public function facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf("Lierstart ESW vlucht %02du%02d %s", $hour, $minute, $name);
        $free = FALSE;
        if ($vi == 1) {
            $desc .= " Initiatievlucht";
            $free = TRUE;
        } else if ($vi == 2) {
            $desc .= " PR vlucht";
            $free = TRUE;
        }

        if ($free) {
            return;
        }

        $lance = 1; // treuil
        $produit = "Lierstart ESW";

        $this->decompte_ou_facture($produit, $lance, $desc, $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name);
    }

    /**
     * Facture les heures de vols
     */
    public function facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;

        $desc = sprintf("Minutengeld vlucht  %02du%02d %s", $hour, $minute, $name); // " à $debut"; // Prépare la description du produit
        $free = FALSE;
        if ($vi == 1) {
            $desc .= " Initiatievlucht"; // est-ce un vol d'initiation ?
            if ($duree > 30) {
                $desc .= " meer dan 30min";
                $duree -= 30;
            } else {
                $free = TRUE;
            }
        } else if ($vi == 2) {
            $desc .= " PR vlucht";
            if ($duree > 30) {
                $desc .= " deel boven 30min ten laste piloot";
                $duree -= 30;
            } else {
                $free = TRUE;
            }
        }

        // on ne facture pas les privés
        if ($prive == 1) {
            $desc .= " prive";
            $free = TRUE;
        }

        if ($free) {
            return;
        }

        // afiche la durée
        // $hour = intval ( $duree / 60 );
        // $minute = $duree - ($hour * 60);

        $duree_heure = $duree / 60;

        $desc .= " op $machine ";

        if ($max_facturation != 0)
            if ($duree > $max_facturation) {
                $duree = $max_facturation;
            }
        $duree_heure = $duree / 60;

        $this->nouvel_achat_partage(array (
                'date' => $date,
                'produit' => $tarif_heure,
                'quantite' => $duree_heure,
                'format' => 'time',
                'description' => $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
        ), $pilote, $payeur, $pourcentage);
    }

    /**
     * Facture les heures moteur
     */
    public function facture_moteur($vol_id, $date, $debut, $machine, $duree, $produit, $vi, $pilote, $payeur, $pourcentage, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf("Vlucht %02dh%02d %s", $hour, $minute, $name);

        $free = FALSE;
        if ($vi == 1) {
            $desc .= " Initiatievlucht";
            $free = TRUE;
        } else if ($vi == 2) {
            $desc .= " PR vlucht";
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

        $date = $vol ['vpdate'];
        $debut = $vol ['vpcdeb'];
        $duree = $vol ['vpduree'];
        $machine = $vol ['vpmacid'];
        $pilote = $vol ['vppilid'];
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
        if ($lancement == 3) {
            // Facture remorqué
            $this->facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name);
        } else if ($lancement == 1) {
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
