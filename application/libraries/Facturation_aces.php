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
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Facturation des vols et mouvements de pompe
 *
 * Ce module est en charge de la génération des lignes de facture à partir
 * des vols. C'est également lui qui enregistre les achats de marchandise
 * a decompter (tickets de remorqué, etc).

 * C'est typiquement un module à surcharger pour définir les règles de
 * facturation de chaque club (au moins jusqu'à ce que quelqu'un en
 * écrive un général qui puisse prendre en compte toutes les particularités
 * de facturation).
 */
class Facturation_aces extends Facturation
{
    // Class attributes
    protected $attr;


    /**
     * Crée une nouvelle ligne d'achat
     */
    public function nouvel_achat ($data) {
        // acces au model des achats
        $this->CI->load->model('achats_model');

        // création de l'achat
        $data['facture'] = 0;
        $data['saisie_par'] = $this->CI->dx_auth->get_username();
        $data['club'] = 0;
        $res = $this->CI->achats_model->create($data);
        if (!$res) {
            echo "erreur durant la création de l'achat<br>";
        }

        $msg = "Facture: " . $data['date']
        . ", produit=" . $data['produit']
        . ", quantite=" . $data['quantite']
        . " " . $data['description'];

        gvv_info($msg );

        return $res;
    }
    

    /**
     * Facturation d'un vol avion
     * Génère les lignes de facture correspondantes.
     * Marque le vol
     *
     * Régles de facturation:
     *      * Les VI ne sont pas facturés au pilote mais à _via
     *      * Les vols d'essais ne sont pas facturés.
     *      * Les vols gratuits font l'objet d'une ligne gratuite sur la facture
     *
     *
     * Heures de vol
     *
     * @param unknown_type $vol
     */
	public function facture_vol_avion($vol) {
		
		$this->CI->load->model('membres_model');
		$this->CI->load->model('comptes_model');
		$this->CI->load->model('avions_model');
		$this->CI->load->model('tarifs_model');
		$this->CI->load->model('vols_avion_model');

		$date = $vol['vadate'];
		$duree = $vol['vaduree'];
		$machine = $vol['vamacid'];
		$vol_id = $vol['vaid'];
		$dc = $vol['vadc'];

		$pilote_info = $this->CI->membres_model->get_by_id('mlogin', $vol['vapilid']);
		$machine_info = $this->CI->avions_model->get_by_id('macimmat', $vol['vamacid']);
		$tarif_info = $this->CI->tarifs_model->get_by_id('id', $machine_info['maprix']);
		$pilote = $vol['vapilid'];

		$desc = $vol['vaduree'].'h sur '.$vol['vamacid'].' @ '.$vol['valieudeco'];
		if ($dc!=0) { $desc.=" DC"; }
		
		$free = FALSE;

		if ($vol['vacategorie'] == 1) {
			$desc .= " VI"; // est-ce un vol d'initiation ?
			$free = TRUE;
			if ($vol['vanbpax']=='3') { $tarifvi = "VI Avion 3 personnes"; }		// tarif vi 3 personnes
			else if ($vol['vanbpax']=='2') { $tarifvi = "VI Avion 2 personnes"; }			// tarif vi 2 personnes
			else { $tarifvi = "VI Avion 1 personne"; }												// tarif vi 1 personne
			
			$this->nouvel_achat(array(
            'date' => $date,
            'produit' => $tarifvi,
            'quantite' => 1,
            'description' => $desc.' n°'.$vol['vanumvi'],
            'pilote' => '_via',
            'machine' => $machine,
            'vol_avion' => $vol_id
            ));			
			
		} else if ($vol['vacategorie'] == 2) {
			// est-ce un vol d'essai ?
			$desc .= " vol d'essai";
			$free = TRUE;
		} else if ($vol['vacategorie'] == 3) {
			// est-ce un remorquage ?
			$desc .= " remorquage";
			$free = TRUE;
		}

		// on ne facture pas les privés
		if ($machine_info['maprive'] == 1) {
			$desc .= " privé";
		}

		$produi='maprix';
		$produi.=($dc==1) ? 'dc' : '';

		$this->nouvel_achat(array(
            'date' => $date,
            'produit' => $machine_info[$produi],
            'quantite' => ($free) ? 0 : $duree,
            'description' => $desc,
            'pilote' => $pilote,
            'machine' => $machine,
            'vol_avion' => $vol_id
            ));
            
      		
	}



    /**
	 * Facturation d'un mvt pompe
	 * Génère les lignes de facture correspondante.
	 */
	public function facture_pompes($mvt) {
		if($mvt['ppilid']=='aces') return; 		// on ne facture pas à l'ACES
	
		$this->CI->load->model('membres_model');
		$this->CI->load->model('comptes_model');
		$this->CI->load->model('tarifs_model');


		$date = $mvt['pdatemvt'];
		$mvt_id = $mvt['pid'];

		$pilote_info = $this->CI->membres_model->get_by_id('mlogin', $mvt['ppilid']);
		$tarif_id = $mvt['ppu'];

		$pilote = $mvt['ppilid'];
		
		$quantite = $mvt['pqte']*-1;
		if (strpos($tarif_id, "ULM")===false)	$desc = $quantite.'Litres de 100LL ';
		else $desc = $quantite.'Litres de 98SP ';
		if ($mvt['pmacid']!="") $desc.= "(".$mvt['pmacid'].") ";
	
		$this->nouvel_achat(array(
            'date' => $date,
            'produit' => $tarif_id,
            'quantite' => $quantite,
            'description' => $desc,
            'pilote' => $pilote,
            'machine' => '',
            'mvt_pompe' => $mvt_id
            ));
	}


    /**
     * Facture un remorqué
     *
     */
    public function facture_rem ($date, $desc, $pilote, $payeur, $pourcentage, $machine, $vol_id, $lfeg, $name, $demirem, $ticcolle) {
			if ($lfeg) $produit="Remorqué 500m LFEG"; else $produit="Remorqué 500m LFOY";		// tarif remorqué en fonction du lieu
			$produittic = "Remorqué 500m LFOY";			// produit pour les décompte de tickets informatisés
			$produitsup = "Remorqué Supplément pour LFEG";		// supplément argenton
			if ($demirem) $qte=0.5; else $qte=1;
        

      if ($lfeg) {			// si rem à Argenton
       	if ($ticcolle!=0) {	// si ticket collé on facture uniquement le supplément (qu'il soit demi ou entier)
      	  $this->nouvel_achat_partage(array('date' => $date, 'produit' => $produitsup, 'quantite' => $qte, 'description' => $desc, 'pilote' => $pilote,
                'machine' => $machine, 'vol_planeur' => $vol_id ), $pilote, $payeur, $pourcentage, $name);
       	} else {				// ticket non collé
       		if ($demirem) { // on facture le demi lfeg
       			$this->nouvel_achat_partage(array('date' => $date, 'produit' => $produit, 'quantite' => $qte, 'description' => $desc, 'pilote' => $pilote,
                'machine' => $machine, 'vol_planeur' => $vol_id ), $pilote, $payeur, $pourcentage, $name);
       		
       		} else {  // on facture un lfeg (ou décompte + facture supplément)
       		
       		
		        if (($payeur) && ($this->CI->tickets_model->solde($payeur) > 0)) {        // Le remorqué est décompté du payeur
		            $desc .= " décompté, pilote=$name";
		            // facture à 0 le payeur
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produittic,'quantite' => 0,'description' => $desc,'pilote' => $payeur,'machine' => $machine,'vol_planeur' => $vol_id));
		            // Décompte le remorqué au payeur
		            $this->CI->tickets_model->create(array('date' => $date,'pilote' => $payeur,'achat' => $achat,'quantite' => -1,'description' => $desc,'saisie_par' => $this->CI->dx_auth->get_username(),'club' => 0,'type' => 0));
		            // facture un sup au payeur
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produitsup,'quantite' => 1,'description' => $desc,'pilote' => $payeur,'machine' => $machine,'vol_planeur' => $vol_id));
		        } elseif (($payeur)) {        // Le remorqué est payé à l'unité par le payeur
		        		$desc .= " pilote=$name";
		            $this->nouvel_achat(array('date' => $date,'produit' => $produit,'quantite' => 1,'description' => $desc,'pilote' => $payeur,'machine' => $machine, 'vol_planeur' => $vol_id ));     
		        } elseif ( $this->CI->tickets_model->solde($pilote) > 0) {          // Le remorqué est décompté au pilote
		            $desc .= " décompté";
		            // facture à 0 le pilote
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produittic,'quantite' => 0,'description' => $desc,'pilote' => $pilote, 'machine' => $machine,'vol_planeur' => $vol_id));
		            // Décompte le remorqué
		            $this->CI->tickets_model->create(array('date' => $date,'pilote' => $pilote,'achat' => $achat,'quantite' => -1,'description' => $desc, 'saisie_par' => $this->CI->dx_auth->get_username(), 'club' => 0,'type' => 0 ));
		            // facture un sup au pilote
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produitsup,'quantite' => 1,'description' => $desc,'pilote' => $pilote,'machine' => $machine,'vol_planeur' => $vol_id));
		        } else {       // Le remorqué est payé à l'unité
		            $this->nouvel_achat_partage(array( 'date' => $date, 'produit' => $produit, 'quantite' => 1, 'description' => $desc,
		                'pilote' => $pilote, 'machine' => $machine, 'vol_planeur' => $vol_id) , $pilote, $payeur, $pourcentage, $name);
		        }
       			
       		}
      	}
       	 
      }
      else {					// si rem à St Romain
      	if ($ticcolle!=0) {	// si ticket collé on ne fait rien
      	} else {
      		if ($demirem) { // on facture le demi lfoy
       			$this->nouvel_achat_partage(array('date' => $date, 'produit' => $produit, 'quantite' => $qte, 'description' => $desc, 'pilote' => $pilote,
                'machine' => $machine, 'vol_planeur' => $vol_id ), $pilote, $payeur, $pourcentage, $name);
       		
       		} else {	// on facture un lfoy (ou décompte )
       		
       		
       			if (($payeur) && ($this->CI->tickets_model->solde($payeur) > 0)) {        // Le remorqué est décompté du payeur
		            $desc .= " décompté, pilote=$name";
		            // facture à 0 le payeur
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produittic,'quantite' => 0,'description' => $desc,'pilote' => $payeur,'machine' => $machine,'vol_planeur' => $vol_id));
		            // Décompte le remorqué au payeur
		            $this->CI->tickets_model->create(array('date' => $date,'pilote' => $payeur,'achat' => $achat,'quantite' => -1,'description' => $desc,'saisie_par' => $this->CI->dx_auth->get_username(),'club' => 0,'type' => 0));
		        } elseif (($payeur)) {        // Le remorqué est payé à l'unité par le payeur
		        		$desc .= " pilote=$name";
		            $this->nouvel_achat(array('date' => $date,'produit' => $produit,'quantite' => 1,'description' => $desc,'pilote' => $payeur,'machine' => $machine, 'vol_planeur' => $vol_id ));     
		        } elseif ( $this->CI->tickets_model->solde($pilote) > 0) {          // Le remorqué est décompté au pilote
		            $desc .= " décompté";
		            // facture à 0 le pilote
		            $achat = $this->nouvel_achat(array('date' => $date,'produit' => $produittic,'quantite' => 0,'description' => $desc,'pilote' => $pilote, 'machine' => $machine,'vol_planeur' => $vol_id));
		            // Décompte le remorqué
		            $this->CI->tickets_model->create(array('date' => $date,'pilote' => $pilote,'achat' => $achat,'quantite' => -1,'description' => $desc, 'saisie_par' => $this->CI->dx_auth->get_username(), 'club' => 0,'type' => 0 ));
		        } else {       // Le remorqué est payé à l'unité
		            $this->nouvel_achat_partage(array( 'date' => $date, 'produit' => $produit, 'quantite' => 1, 'description' => $desc,
		                'pilote' => $pilote, 'machine' => $machine, 'vol_planeur' => $vol_id) , $pilote, $payeur, $pourcentage, $name);
		        }
		               			
       		}
     		
      	}
      }


    return $desc;
    }



  public function facture_heures_planeur2 ($vol_id, $date, $pilote, $machine, $dureefact, $desc, $prive, $dc, $duree) {
    if ($prive == 1) {	
    	$prodheur= "Heures Planeur gratuite"; // on ne facture pas les planeur privé, tarif heure de vol gratuite
    }
    else {
	    if ($this->CI->membres_model->age($pilote)!=0) {	// si pilote a moins de 25 ans
				$matrice="m";
				$matrice.= ($dc!=0) ? "dc" : "so" ;
			} else {					// si pilote a plus de 25 ans
				$matrice="p";
				$matrice.= ($dc!=0) ? "dc" : "so" ;
			}
			
			$tarifp= array (
			'D-3874' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur ancien -25a", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur ancien +25a"),			
			'F-CBNA' => array( 'mso' => "Heures Planeur début -25a", 'mdc' => "Heures Planeur début -25a", 'pso' => "Heures Planeur début +25a", 'pdc' => "Heures Planeur début +25a"),
			'F-CDMT' => array( 'mso' => "Heures Planeur WA30", 'mdc' => "Heures Planeur DC", 'pso' => "Heures Planeur WA30", 'pdc' => "Heures Planeur DC"),
			'F-CEQZ' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a"),
			'F-CITT' => array( 'mso' => "Heures Planeur K21 -25a", 'mdc' => "Heures Planeur DC", 'pso' => "Heures Planeur K21 +25a", 'pdc' => "Heures Planeur DC"),
			'F-CEAU' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur ancien -25a", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur ancien +25a"),
			'F-CEQJ' => array( 'mso' => "Heures Planeur début -25a", 'mdc' => "Heures Planeur début -25a", 'pso' => "Heures Planeur début +25a", 'pdc' => "Heures Planeur début +25a"),
			'F-CDAL' => array( 'mso' => "Heures Planeur début -25a", 'mdc' => "Heures Planeur début -25a", 'pso' => "Heures Planeur début +25a", 'pdc' => "Heures Planeur début +25a"),
			'F-CDVN' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a"),
			'F-CEHG' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a"),
			'F-CEKR' => array( 'mso' => "Heures Planeur gratuite", 'mdc' => "Heures Planeur gratuite", 'pso' => "Heures Planeur gratuite", 'pdc' => "Heures Planeur gratuite"),
			'F-CESL' => array( 'mso' => "Heures Planeur gratuite", 'mdc' => "Heures Planeur gratuite", 'pso' => "Heures Planeur gratuite", 'pdc' => "Heures Planeur gratuite"),
			'F-CHDT' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a"),
			'F-CFRK' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a"),
			'F-CIGL' => array( 'mso' => "Heures Planeur gratuite", 'mdc' => "Heures Planeur gratuite", 'pso' => "Heures Planeur gratuite", 'pdc' => "Heures Planeur gratuite"),
			'F-CEJX' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur DC", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur DC"),
			'F-CEGV' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur ancien -25a", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur ancien +25a"),
			'F-CGGH' => array( 'mso' => "Heures Planeur gratuite", 'mdc' => "Heures Planeur gratuite", 'pso' => "Heures Planeur gratuite", 'pdc' => "Heures Planeur gratuite"),
			'F-XXXX' => array( 'mso' => "Heures Planeur gratuite", 'mdc' => "Heures Planeur gratuite", 'pso' => "Heures Planeur gratuite", 'pdc' => "Heures Planeur gratuite"),
			'F-CLGB' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur DC", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur DC"),
			'F-CLBG' => array( 'mso' => "Heures Planeur ancien -25a", 'mdc' => "Heures Planeur DC", 'pso' => "Heures Planeur ancien +25a", 'pdc' => "Heures Planeur DC"),
			'F-CFRD' => array( 'mso' => "Heures Planeur perfo -25a", 'mdc' => "Heures Planeur perfo -25a", 'pso' => "Heures Planeur perfo +25a", 'pdc' => "Heures Planeur perfo +25a")
			);
			$prodheur= $tarifp[$machine][$matrice];
		}
		
		// tester si forfait illimité
		$forfill = $this->CI->tickets_model->solde($pilote, 5);		// retourne 1 si un forfait illimité a été pris (ou plus si cumule car non effacés au 1er janvier)
		if ($forfill > 0) {
			$desc.=" forfait illimité";
			$this->nouvel_achat(array(
         'date' => $date,
         'produit' => $prodheur,
         'quantite' => 0,
         'description' => $desc,
         'pilote' => $pilote,
         'machine' => $machine,
         'vol_planeur' => $vol_id
         ));
       return;
		}
		// tester si forfait 30 ou 20 heures
		$nbheureforfait = $this->CI->tickets_model->solde($pilote, 3);		// retourne le nombre minutes restantes sur le forfait 30 ou 20 heures 
		if ($nbheureforfait >0) {
			//if ($dureefact > $nbheureforfait)  { $qteafacturer = $dureefact - $nbheureforfait; $qteadecompter = $nbheureforfait; $descf=" hors forfait"; }
			if ($duree > $nbheureforfait)  { $qteafacturer = $duree - $nbheureforfait; $qteadecompter = $nbheureforfait; $descf=" hors forfait"; }
			//else  { $qteafacturer = 0; $qteadecompter = $dureefact; $descf=" forfait 30h"; }
			else  { $qteafacturer = 0; $qteadecompter = $duree; $descf=" forfait 30h"; }
			
			$qteadecompter *= -1 ;
			$achat=$this->nouvel_achat(array(
            'date' => $date,
            'produit' => $prodheur,
            'quantite' => $qteafacturer / 60,
            'description' => $desc.$descf,
            'pilote' => $pilote,
            'machine' => $machine,
            'vol_planeur' => $vol_id
            ));
			   // Décompte le vol du forfait heures
            $this->CI->tickets_model->create(array(
                'date' => $date,
                'pilote' => $pilote,
                'achat' => $achat,
                'quantite' => $qteadecompter,
                'description' => $desc,
                'saisie_par' => $this->CI->dx_auth->get_username(),
                'club' => 0,
                'type' => 3
            ));

		return;
		}


		$achat=$this->nouvel_achat(array(
            'date' => $date,
            'produit' => $prodheur,
            'quantite' => $dureefact / 60,
            'description' => $desc,
            'pilote' => $pilote,
            'machine' => $machine,
            'vol_planeur' => $vol_id
            ));		
            
    }

	  /**
	  * Facturation d'un vol en planeur.
	  * Génère les lignes de facture correspondantes.
	  * Marque le vol
	  *
	  * Règles de facturation:
	  *         * Les VI ne sont pas facturés
	  *      * Les vols d'essais ne sont pas facturés.
	  *      * Les vols gratuits font l'objet d'une ligne gratuite sur la facture
	  *
	  * Lancement
	  *      * Autonome -> rien
	  *      * Treuil -> remorqué
	  *      * Rem
	  *           >= 500 
	  *           >= 250 
	  *							lfoy
	  *							lfeg
	  *							ticket collé ou pas
	  *
	  * Heures de vol
	  *      * Les machines privées ne sont pas facturées
	  *      * on ne facture pas au delà de 4 heures à Argenton et au delà de 2 heures à St Romain
	  *
	  * @param unknown_type $vol
	  */
	public function facture_vol_planeur ($vol) {
		$free= FALSE;
		$facture = $vol['facture'];
		$vol_id = $vol['vpid'];

		$this->CI->load->model('vols_planeur_model');
		$this->CI->load->model('membres_model');
		$this->CI->load->model('comptes_model');
		$this->CI->load->model('tickets_model');

		$date = $vol['vpdate'];
		$debut = $vol['vpcdeb'];
		$duree = $vol['vpduree'];
		$machine = $vol['vpmacid'];
		$deco=strtoupper(substr($vol['vplieudeco'], 0, 4));
		if ($deco=='LFEG') $lfeg= TRUE; else $lfeg= FALSE;		// si vol effectué à Argenton

		$durh = floor($duree/60);
		$durm = $duree - ($durh*60);        
		$desc = sprintf(" %02dh%02d sur %s à %s", $durh, $durm, $machine, $deco); // Prépare la description du produit
		$pilote = $pilotereel = $vol['vppilid'];
	  $name = '';
	 
	 	// Si le pilote vol sur le compte d'un autre
		if ($vol['compte']) {
	  		$compte_info = $this->CI->comptes_model->get_by_id('id', $vol['compte']); 
	  		$pilote =  $compte_info['pilote'];
				$name = $this->CI->membres_model->image($vol['vppilid']);
				$desc.=" pilote:$name"; 
	  	}
	
		$remorqueur = $vol['remorqueur'];
	
		$vi = $vol['vpcategorie'];
		$dc = $vol['vpdc'];
		$prive = $vol['prive'];
		$lancement = $vol['vpautonome'];
		$alt_rem = $vol['vpaltrem'];
		$payeur = $vol['payeur'];
		$pourcentage = $vol['pourcentage'];
		$ticcolle = $vol['vpticcolle'];
	
		$tarif_heure = $vol['mprix'];
		$tarif_forfait = $vol['mprix_forfait'];
		$max_facturation = $vol['mmax_facturation'];
		if ($alt_rem <=250) { $demirem= TRUE; $desc.= " 1/2 rem."; } else $demirem= FALSE;
	  
	  
		$data = array();
	  
		if ($vi==1) { $desc.=" VI n°".$vol['vpnumvi']; $free=TRUE; }
		if ($vi==2) { $desc.=" Vol d'essai"; $free=TRUE; }
		if ($dc!=0) { $desc.=" DC"; }
		
		$nbforfait10v = $this->CI->tickets_model->solde($pilote, 4);		// retourne le nombre de vols restant sur le forfait 10 vols (retourne 0 si pas de forfait ou forfait épuisé) 
		if ($nbforfait10v >0) {
			$numvol= 11 - $nbforfait10v; 
			$desc.=" Forfait ".$numvol."/10";
			$achat=$this->nouvel_achat(array(
            'date' => $date,
            'produit' => "Heures Planeur gratuite",		// 75 = heure de vol gratuite, bugue si = 0 (produit par défaut, gratuit)
            'quantite' => 0,
            'description' => $desc,
            'pilote' => $pilote,
            'machine' => $machine,
            'vol_planeur' => $vol_id
            ));
			   // Décompte le vol du forfait 10vols
            $this->CI->tickets_model->create(array(
                'date' => $date,
                'pilote' => $pilote,
                'achat' => $achat,
                'quantite' => -1,
                'description' => $desc,
                'saisie_par' => $this->CI->dx_auth->get_username(),
                'club' => 0,
                'type' => 4
            ));

		return;
		} 
		if ($free) {					// si VI ou vol d'essai écrit une ligne à zéro dans la facture du pilote
			$this->nouvel_achat(array(
         'date' => $date,
         'produit' => "Heures Planeur gratuite",		// 75 = heure de vol gratuite, bugue si = 0 (produit par défaut, gratuit)
         'quantite' => 0,
         'description' => $desc,
         'pilote' => $pilote,
         'machine' => $machine,
         'vol_planeur' => $vol_id
         ));
         if ($vi==1) {		// facturation du membre _vip
         	$tarifvi= "VI Planeur";  // tarif VI planeur 
				$this->nouvel_achat(array(
         	'date' => $date,
         	'produit' => $tarifvi,
         	'quantite' => 1,
         	'description' => $desc,
         	'pilote' => '_vip',
         	'machine' => $machine,
         	'vol_planeur' => $vol_id
         	));
         }
            
		return;
		}
	        		
	        		
	        		
        		
     		
		if ($lancement == 3 ) { // || $lancement == 1) on ne facture pas les treuillées car pas de treuil au club, c'est forcément fait à l'extérieur

      	// Facture remorqué ou treuillé si erreur de saisie - les autonomes ne sont pas facturés

			$data[] = $this->facture_rem($date, $desc, $pilote, $payeur, $pourcentage, $machine, $vol_id, $lfeg, $name, $demirem, $ticcolle);            
		}
     
     // calcul du temps à facturer
     if ($lfeg) {		// si vol à Argenton
     		if ($duree>180)	{
					if ($duree>240) $dureefact=210; 		// au dela de 4 heures on facture 3h30
					else $dureefact=180+round(($duree-180)/2, 0);		// entre 3h et 4h on facture 3 + 1/2
				}	else $dureefact=$duree;
     	
     } else {		// si vol à St Romain
     		if ($duree>90)	{
					if ($duree>120) $dureefact=105; 		// au dela de 2 heures on facture 1h45
					else $dureefact=90+round(($duree-90)/2, 0);		// entre 1h30 et 2h on facture 1h30 + 1/2
				} 	else $dureefact=$duree;
     }


// -----------------------------------------------------------

        // on ne facture pas les privés
        if ($prive == 1) {
            $desc .= " privé";
            $free = TRUE;
        }


        if ($payeur!="" && $pourcentage == 100) {
        	
        	$data[] = $this->facture_heures_planeur2 ($vol_id, $date, $payeur, $machine, $dureefact, $desc, $prive, $dc, $duree);

        } else if ($payeur!="" && $pourcentage == 50) {
        	
        	$dureefact /= 2;
        	$data[] = $this->facture_heures_planeur2 ($vol_id, $date, $pilote, $machine, $dureefact, $desc, $prive, $dc, $duree);
        	$data[] = $this->facture_heures_planeur2 ($vol_id, $date, $payeur, $machine, $dureefact, $desc, $prive, $dc, $duree);


        } else {
        	
        	$data[] = $this->facture_heures_planeur2 ($vol_id, $date, $pilote, $machine, $dureefact, $desc, $prive, $dc, $duree);
        	
				}


/*

		facture_heures_planeur2 ($vol_id, $date, $pilote, $machine, $dureefact, $desc);
            
     $data[] = $this->facture_heures ($vol_id, $date, $debut, $dureefact, $machine, $vi, $prive, $pilote,
     $payeur, $pourcentage, $prodheur, $tarif_forfait, $max_facturation, $name);
*/
     return $data;
    }

}