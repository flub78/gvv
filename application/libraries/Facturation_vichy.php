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
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );

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
class Facturation_vichy extends Facturation {
	// Class attributes
	protected $attr;
	
	
	
	/**
	 * Facture le lancement en remorqué
	 */
	public function facture_rem($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {
		gvv_debug ( 'Facturation remorqué' );
		
		$hour = ( int ) $debut;
		$minute = ($debut - $hour) * 100;
		
		$vacdeb = $vol[vacdeb];
		$vacfin = $vol[vacfin];
		
		$duree = $vacfin - $vacdeb;
		
		$desc = sprintf ( " remorquage à %02dh%02d %s", $hour, $minute, $name );
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
		
		$this->CI->load->model ( "tarifs_model" );
		
		$produit = "centieme";
		
		$this->nouvel_achat_partage ( array (
				'date' => $date,
				'produit' => $produit,
				'quantite' => ($free) ? 0 : $duree,
				'description' => $desc,
				'pilote' => $pilote,
				'machine' => $machine,
				'vol_planeur' => $vol_id 
		), $pilote, $payeur, $pourcentage, $name );
	}
	
	/**
	 * Facture le lancement au treuil
	 */
	public function facture_treuil($vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $treuillard) {
		$hour = ( int ) $debut;
		$minute = ($debut - $hour) * 100;

		gvv_debug ( "  dddd debut = $debut");
		gvv_debug ( "  dddd hour = $hour");		
		gvv_debug ( "  dddd minute = $minute");
		
		$desc = sprintf ( " Treuil vol de %02dh%02d %s", $hour, $minute, $name );
		$free = FALSE;
		if ($vi == 1) {
			$desc .= " VI";
			$free = TRUE;
		} else if ($vi == 2) {
			$desc .= " vol d'essai";
			$free = TRUE;
		}
		
		////////////////////////////////////////////////////////////////////////
		////                                                                ////
		////         Gestion des tickets                                    ////
		////                                                                ////
		////////////////////////////////////////////////////////////////////////
        $typTicket = 1; // treuil
        $produit = "treuillee";

        // Credite le treuillard d'un ticket de treuillee
        if ($treuillard) {
            $this->CI->tickets_model->create(array (
                    'date' => $date,
                    'pilote' => $treuillard,
                    // 'achat' => "",
                    'quantite' => 1,
                    'description' => $desc,
                    'saisie_par' => $this->CI->dx_auth->get_username(),
                    'club' => 0,
                    'type' => $typTicket,
                    'vol' => $vol_id
            ));
			
			$soldeTicketTreuillard = $this->CI->tickets_model->solde($treuillard, $typTicket);
			gvv_debug ( "   GESTION TICKET TREUIL : ajout d'un ticket pour treuillard $treuillard Total = $soldeTicketTreuillard");
        }

		$soldeTicketPilote = $this->CI->tickets_model->solde($pilote, $typTicket);
		gvv_debug ( "   GESTION TICKET TREUIL : affichage nb tickets pour pilote $pilote Total = $soldeTicketPilote");
        if ($soldeTicketPilote >= 7)
		{			
			gvv_debug ( "   GESTION TICKET TREUIL : utilisation ticket treuil pour $pilote");
			$this->CI->tickets_model->create(array (
                    'date' => $date,
                    'pilote' => $pilote,
                    // 'achat' => "",
                    'quantite' => -7,
                    'description' => $desc,
                    'saisie_par' => $this->CI->dx_auth->get_username(),
                    'club' => 0,
                    'type' => $typTicket,
                    'vol' => $vol_id
            ));
		}
			
		else
		{
            gvv_debug ( "   GESTION TICKET TREUIL : nb ticket insuffisant $pilote paye treuillee");
			$this->nouvel_achat_partage ( array (
					'date' => $date,
					'produit' => "treuillee",
					'quantite' => ($free) ? 0 : 1,
					'description' => $desc,
					'pilote' => $pilote,
					'machine' => $machine,
					'vol_planeur' => $vol_id 
			), $pilote, $payeur, $pourcentage, $name );
		}
	}
	
	
	/**
	 * Facture les heures de vols
	 */
	public function facture_heures($vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name) {
		$hour = ( int ) $debut;
		$minute = ($debut - $hour) * 100;
		
		$desc = sprintf ( " à %02dh%02d %s", $hour, $minute, $name ); // " à $debut"; // Prépare la description du produit
		
		$free = FALSE;
		if ($vi == 1) {
			$desc .= " VI"; // est-ce un vol d'initiation ?
			$free = TRUE;
		} else if ($vi == 2) {
			// est-ce un vol d'essai ?
			$desc .= " vol d'essai";
			$free = TRUE;
		}
		
		// on ne facture pas les privés
		if ($prive == 1) {
			$desc .= " privé";
		} elseif ($duree > $max_facturation) {
			$duree = $max_facturation;
		}
		
		// afiche la durée
		$hour = intval ( $duree / 60 );
		$minute = $duree - ($hour * 60);
		$desc .= " sur $machine";
		
		/////////////////////////////////////////////////////////////////////////////////
		///                                                                           ///
		///               LO : 23/04/2016                                             ///
		///                                                                           ///
		/////////////////////////////////////////////////////////////////////////////////

		// variable necessaires au calcul
		$m25ans = $this->CI->membres_model->moins_25ans($pilote, $date);
		$annee = substr($date, 0, 4);
		
		if ($m25ans) { // si pilote a moins de 25 ans
                $limAge = "m";
            } else { // si pilote a plus de 25 ans
                $limAge = "p";
            }
			
		// Calcul total heures par an par pilote par machine        
		$nbHeuresMachines = $this->CI->vols_planeur_model->par_pilote_machine("mlogin, vpmacid", array(), array (
				'membres.actif' => "1",
				'membres.mlogin' => $pilote,
                'machinesp.actif' => "1",
				'machinesp.mpimmat' => $machine,
				'YEAR(vpdate)' => $annee
		));
		$heuresPiloteMachine = $nbHeuresMachines[0]['minutes']/60;
				
		if ($heuresPiloteMachine <= 15)
            $tranche = 'A';
		elseif ($heuresPiloteMachine > 15 && $heuresPiloteMachine <= 26)
			$tranche = 'B';
		elseif ($heuresPiloteMachine > 26 && $heuresPiloteMachine <= 40)
			$tranche = 'C';
		elseif ($heuresPiloteMachine > 40)
		{
			$free = TRUE;
			$tranche = 'NONE';
		}
			
                
		
		//gvv_debug ( "iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii " . var_export ( $nbHeuresMachines, true ) );
		//gvv_debug ( "heures total pilote = $heuresPiloteMachine ");
		
		$tarifp = array (
			'F-CFIE' => array (
				'm' => array (
					'A' => "hdv_astir_m_25_tA",
					'B' => "hdv_astir_m_25_tB",
					'C' => "hdv_astir_m_25_tC"),
				'p' => array (
					'A' => "hdv_astir_p_25_tA",
					'B' => "hdv_astir_p_25_tB",
					'C' => "hdv_astir_p_25_tC")
					),
            'F-CGBS' => array (
                'm' => array (
					'A' => "hdv_pegase_m_25_tA",
					'B' => "hdv_pegase_m_25_tB",
					'C' => "hdv_pegase_m_25_tC"),
				'p' => array (
					'A' => "hdv_pegase_p_25_tA",
					'B' => "hdv_pegase_p_25_tB",
					'C' => "hdv_pegase_p_25_tC")
					),
            'F-CPCV' => array (
                'm' => array (
					'A' => "hdv_twin_m_25_tA",
					'B' => "hdv_twin_m_25_tB",
					'C' => "hdv_twin_m_25_tC"),
				'p' => array (
					'A' => "hdv_twin_p_25_tA",
					'B' => "hdv_twin_p_25_tB",
					'C' => "hdv_twin_p_25_tC")
					)
				);
			
		if (!$free)
			$prodheur = $tarifp [$machine] [$limAge][$tranche];
		else
			$prodheur = "NONE";
		
		gvv_debug ( "------------ AFFICHAGE FACTURATION  ---------" );
		gvv_debug ( "   pilote = $pilote" );
		gvv_debug ( "   année = $annee" );	
		gvv_debug ( "   machine = $machine" );
		gvv_debug ( "   nb heures machine = $heuresPiloteMachine" );
		gvv_debug ( "   prodheur = $prodheur" );
		gvv_debug ( "   duree(min) = $duree" );
		gvv_debug ( "---------------------------------------------" );
		
		$this->nouvel_achat_partage ( array (
				'date' => $date,
				'produit' => $prodheur,
				'quantite' => ($free) ? 0 : $duree / 60,
				'format' => 'time',
				'description' => $desc,
				'pilote' => $pilote,
				'machine' => $machine,
				'vol_planeur' => $vol_id 
		), $pilote, $payeur, $pourcentage );
		
		////// FIN LO : 23/04/2016 //////////////////////////////////////////////////////////////
			
		//$this->nouvel_achat_partage ( array (
		//		'date' => $date,
		//		'produit' => ($this->forfait ( $pilote )) ? $tarif_forfait : $tarif_heure,
		//		'quantite' => ($free) ? 0 : $duree / 60,
		//		'format' => 'time',
		//		'description' => ($this->forfait ( $pilote )) ? $desc . " au forfait" : $desc,
		//		'pilote' => $pilote,
		//		'machine' => $machine,
		//		'vol_planeur' => $vol_id 
		//), $pilote, $payeur, $pourcentage );
		
		return $desc;
	}
	
	/**
	 * Facture les heures moteur
	 */
	public function facture_moteur($vol_id, $date, $debut, $machine, $duree, $produit, $vi, $pilote, $payeur, $pourcentage, $name) {
		$hour = ( int ) $debut;
		$minute = ($debut - $hour) * 100;
		$desc = sprintf ( " à %02dh%02d %s", $hour, $minute, $name );
		
		$free = FALSE;
		if ($vi == 1) {
			$desc .= " VI";
			$free = TRUE;
		} else if ($vi == 2) {
			$desc .= " vol d'essai";
			$free = TRUE;
		}
		
		$this->nouvel_achat_partage ( array (
				'date' => $date,
				'produit' => $produit,
				'quantite' => ($free) ? 0 : $duree,
				'description' => $desc,
				'pilote' => $pilote,
				'machine' => $machine,
				'vol_planeur' => $vol_id 
		), $pilote, $payeur, $pourcentage, $name );
	}
	
	/**
	 *
	 * Vrai si le pilote paye au forfait
	 *
	 * @param unknown_type $pilote        	
	 */
	private function forfait($pilote) {
		// acces au model des achats
		$this->CI->load->model ( 'achats_model' );
		$nb = $this->CI->achats_model->a_achete ( $pilote, "Forfait heures", date ( "Y" ) );
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
	 *        	= array (
	 *        	'vpid' => '1654',
	 *        	'vpdate' => '2012-12-29',
	 *        	'vppilid' => 'vpeignot',
	 *        	'vpmacid' => 'F-CECO',
	 *        	'vpcdeb' => '12.00',
	 *        	'vpcfin' => '12.30',
	 *        	'vpduree' => '30.00',
	 *        	'tempmoteur' => '0.00',
	 *        	'vpobs' => '',
	 *        	'instructeur' => '',
	 *        	'vpcategorie' => '0',
	 *        	'vpautonome' => '3',
	 *        	'vpaltrem' => '500',
	 *        	'payeur' => 'ladams',
	 *        	'pourcentage' => '50',
	 *        	'facture' => '0',
	 *        	'remorqueur' => 'F-JUFA',
	 *        	'vplieudeco' => 'LFOI',
	 *        	'vpdc' => '0',
	 *        	'vpticcolle' => '0',
	 *        	'vpnumvi' => '',
	 *        	'pilote' => 'Vincent Peignot',
	 *        	'compte' => '37',
	 *        	'prive' => '0',
	 *        	'mprix' => 'Heure de vol biplace',
	 *        	'mprix_forfait' => 'Heure de vol forfait',
	 *        	'mprix_moteur' => 'Gratuit',
	 *        	'mmax_facturation' => '180',
	 *        	)
	 */
	public function facture_vol_planeur($vol) {
		gvv_debug ( "facture_vol_planeur " . var_export ( $vol, true ) );
		$facture = $vol ['facture'];
		$vol_id = $vol ['vpid'];
		
		$this->CI->load->model ( 'vols_planeur_model' );
		$this->CI->load->model ( 'membres_model' );
		$this->CI->load->model ( 'comptes_model' );
		
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
			$this->facture_rem ( $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name );
		} else if ($lancement == 1) {
			// Facture Treuillé
			$this->facture_treuil ( $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name, $treuillard );
		} else {
			// echo "Autonome et extérieurs non facturés<br>";
		}
		
		$this->facture_heures ( $vol_id, $date, $debut, $duree, $machine, $vi, $prive, $pilote, $payeur, $pourcentage, $tarif_heure, $tarif_forfait, $max_facturation, $name );
		
		if ($temp_moteur > 0) {
			$this->facture_moteur ( $vol_id, $date, $debut, $machine, $temp_moteur, $tarif_moteur, $vi, $pilote, $payeur, $pourcentage, $name );
		}
	}
}