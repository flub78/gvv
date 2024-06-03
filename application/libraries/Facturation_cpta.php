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
class Facturation_cpta extends Facturation {
    // Class attributes
    protected $attr;

    /**
     * Facture le lancement en remorqué
     */
    public function facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {
    	gvv_debug ( 'Facturation remorqué' );
    	
    	$hour = ( int ) $debut;
    	$minute = ($debut - $hour) * 100;
    	$desc = sprintf ( " à %02dh%02d %s", $hour, $minute, $name );
    	$free = FALSE;
    	if ($vi == 1) {
    		$desc .= " VI";
    		$free = TRUE;
    	} elseif ($vi == 2) {
    		$desc .= " vol d'essai";
    		$free = TRUE;
    	} elseif ($vi == 3) {
    		$desc .= " concours";
    	}
    	
    	$m25ans = $this->CI->membres_model->moins_25ans ( $pilote, $date );
    	$this->CI->load->model ( "tarifs_model" );
    	
    	# $produit = "Centième";
    	$produit = "Remorqué 500m";
    	
    	$this->nouvel_achat_partage ( array (
    			'date' => $date,
    			'produit' => $produit,
    			# 'quantite' => ($free) ? 0 : $alt_rem,
    			'quantite' => ($free) ? 0 : 1,
    			'description' => $desc,
    			'pilote' => $pilote,
    			'machine' => $machine,
    			'vol_planeur' => $vol_id
    	), $pilote, $payeur, $pourcentage, $name );
    	
    	return $desc;
    }

    /**
     * Facture le lancement au treuil
     *
     * ALTER TABLE `tickets` CHANGE `achat` `achat` INT( 11 ) NULL COMMENT 'Numéro de l''achat'
     */
    public function facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $treuillard) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;
        $desc = sprintf("Treuillée à %02dh%02d %s", $hour, $minute, $name);
        $free = FALSE;
        if ($vi == 1) {
            $desc .= " VI";
            $free = TRUE;
        } else if ($vi == 2) {
            $desc .= " vol d'essai";
            $free = TRUE;
        }

        $lance = 1; // treuil
        $produit = "Treuillée";

        // Credite le treuillard d' 1/10 de treuillé
        if ($treuillard) {
            // Décompte le ticket
            $this->CI->tickets_model->create(array (
                    'date' => $date,
                    'pilote' => $treuillard,
                    // 'achat' => "",
                    'quantite' => 0.1,
                    'description' => sprintf("Treuillard du vol de %02dh%02d %s", $hour, $minute, $name),
                    'saisie_par' => $this->CI->dx_auth->get_username(),
                    'club' => 0,
                    'type' => $lance,
                    'vol' => $vol_id
            ));
        }

        if ($free) {
            return;
        }

        $this->decompte_ou_facture($produit, $lance, $desc, $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name);
    }

    /**
     * Facture les heures de vols
     */
    public function facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name) {
        $hour = ( int ) $debut;
        $minute = ($debut - $hour) * 100;

        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name); // " à $debut"; // Prépare la description du produit
        $free = FALSE;
        if ($vi == 1) {
            $desc .= " VI"; // est-ce un vol d'initiation ?
            $free = TRUE;
        } else if ($vi == 2) {
            // est-ce un vol d'essai ?
            $desc .= " vol d'essai";
            $free = TRUE;
        }

        $this->CI->load->model('planeurs_model');
        $planeur = $this->CI->planeurs_model->get_by_id('mpimmat', $machine);

        $banalise = $planeur ['banalise'];
        $proprio = $planeur ['proprio'];

        // on ne facture pas les privés
        if (($prive == 1) && ! $banalise) {
            $desc .= " privé";
            $free = TRUE;
        }

        // on ne facture pas les propriétaires sur leur planeur banalisé
        if (($pilote == $proprio) && $banalise) {
            $desc .= " propriétaire";
            $free = TRUE;
        }

        if ($free) {
            return;
        }

        // afiche la durée
        // $hour = intval ( $duree / 60 );
        // $minute = $duree - ($hour * 60);
        $desc .= " sur $machine";
        $duree_heure = $duree / 60;

        if ($this->forfait($pilote, "Forfait_illimité", $date) ||
        	$this->forfait($pilote, "Forfait_illimité_-25", $date)) {
            // tout le vol est a facturer au forfait dans les forfaits illimités
            $this->nouvel_achat_partage(array (
                    'date' => $date,
                    'produit' => $tarif_forfait,
                    'quantite' => $duree_heure,
                    'format' => 'time',
                    'description' => $desc . " au forfait",
                    'pilote' => $pilote,
                    'machine' => $machine,
                    'vol_planeur' => $vol_id
            ), $pilote, $payeur, $pourcentage);
            return;
        }

        if ($this->forfait($pilote, "Forfait_annuel_jeunes", $date) || 
        	$this->forfait($pilote, "Forfait_annuel", $date) || 
        	$this->forfait($pilote, "Complément_forfait_début", $date) ||
        	$this->forfait($pilote, "Forfait_début", $date)	||
        	$this->forfait($pilote, "Forfait_début_-25", $date)	) {
        		
        	// Tous les cas de forfait non illimités

            $heures_forfait = $this->CI->achats_model->somme_achats($pilote, $tarif_forfait, substr($date, 0, 4));
            $heures_hors_forfait = $this->CI->achats_model->somme_achats($pilote, $tarif_heure, substr($date, 0, 4));
            $heure_total_facture = $heures_forfait + $heures_hors_forfait;

            // echo "heures forfait = " . $heures_forfait . br ();
            // echo "heures hors forfait = " . $heures_hors_forfait . br ();
            // echo "heures facturé total = " . $heure_total_facture . br ();

            if ($this->forfait($pilote, "Forfait_début", $date) || 
            		$this->forfait($pilote, "Forfait_début_-25", $date)) {
            	
            	// On a un forfait début
            	if ($this->forfait($pilote, "Complément_forfait_début", $date)) {
            		$limit1 = 30;
            	} else {
            		$limit1 = 15;
            	}
            	
            } else {
            	// On a pas de forfait début mais un forfait annuel non illimité
            	$limit1 = 30;
            }
            
            if ($heures_forfait < $limit1) {
                // Au moins un peu du vol est à facturer au forfait
            	
                if ($heures_forfait + $duree_heure < $limit1) {

                    // tout le vol est a facturer au forfait
                    $this->nouvel_achat_partage(array (
                            'date' => $date,
                            'produit' => $tarif_forfait,
                            'quantite' => $duree_heure,
                            'format' => 'time',
                            'description' => $desc . " au forfait",
                            'pilote' => $pilote,
                            'machine' => $machine,
                            'vol_planeur' => $vol_id
                    ), $pilote, $payeur, $pourcentage);
                } else {
                    // Une partie au forfait, une partie au tarif 2
                    $duree_forfait = $limit1 - $heures_forfait;
                    $duree_apres = $heures_forfait + $duree_heure - $limit1;

                    $this->nouvel_achat_partage(array (
                            'date' => $date,
                            'produit' => $tarif_forfait,
                            'quantite' => $duree_forfait,
                            'format' => 'time',
                            'description' => $desc . " au forfait",
                            'pilote' => $pilote,
                            'machine' => $machine,
                            'vol_planeur' => $vol_id
                    ), $pilote, $payeur, $pourcentage);

                    $this->nouvel_achat_partage(array (
                            'date' => $date,
                            'produit' => $tarif_heure,
                            'quantite' => $duree_apres,
                            'format' => 'time',
                            'description' => $desc . " après forfait",
                            'pilote' => $pilote,
                            'machine' => $machine,
                            'vol_planeur' => $vol_id
                    ), $pilote, $payeur, $pourcentage);
                }
            } else {
            	
                // Le forfait est dépassé
            	if ($duree > $max_facturation) {
            		// on applique la limite de facturation
            		$duree = $max_facturation;
            		$duree_heure = $duree / 60;
            	}
            	
            	$this->nouvel_achat_partage(array (
                        'date' => $date,
                        'produit' => $tarif_heure,
                        'quantite' => $duree_heure,
                        'format' => 'time',
                        'description' => $desc . " après forfait",
                        'pilote' => $pilote,
                        'machine' => $machine,
                        'vol_planeur' => $vol_id
                ), $pilote, $payeur, $pourcentage);
            }
            return;
        }

        // Pilote pas au forfait
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
        $desc = sprintf(" à %02dh%02d %s", $hour, $minute, $name);

        $free = FALSE;
        if ($vi == 1) {
            $desc .= " VI";
            $free = TRUE;
        } else if ($vi == 2) {
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
     * @param
     *            type de forfait
     * @param
     *            date du vol, format = 'YYYY-MM-DD'
     */
    private function forfait($pilote, $produit = "Forfait heures", $date = "") {
        if ($date) {
            $year = substr($date, 0, 4);
        } else {
            $year = date("Y");
        }
        // acces au model des achats
        $this->CI->load->model('achats_model');
        $nb = $this->CI->achats_model->a_achete($pilote, $produit, $year);
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
        // var_dump($vol); exit;
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
        $treuillard = $vol ['pilote_remorqueur'];

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
            $this->facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $treuillard);
        } else {
            // echo "Autonome et extérieurs non facturés<br>";
        }

        $this->facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name);

        if ($temp_moteur > 0) {
            $this->facture_moteur($vol_id, $date, $debut, $machine, $temp_moteur, $tarif_moteur, $vi, $pilote, $payeur, $pourcentage, $name);
        }
    }
}