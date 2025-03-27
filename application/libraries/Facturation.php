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
 *	Gestion de la facturation.
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * La facturation consiste à générer des achats à partir des caractéristiques
 * des vols.
 * La facturation peut aussi décompter des tickets.
 *
 * C'est typiquement un module à surcharger pour définir les règles de
 * facturation de chaque club (au moins jusqu'à ce que quelqu'un en
 * écrive un général qui puisse prendre en compte toutes les particularités
 * de facturation).
 *
 * Voir facture_vol_avion, facture_vol_planeur, facture_pompes pour les détails.
 *
 * Pour configurer la facturation il faut donc que
 *
 * 1) vous créiez les références de produit qui vont être référencé par la facturation.
 * Par example si vous voulez facturer des remorqués à des prix différents en fonction
 * de l'altitude, ou de l'age du pilote, il vous faut créer plusieurs tarifs de remorqués.
 *
 * 2) Vous créiez votre propre module de facturation. Le plus simple est de créer une classe
 * Facturation_nom_de_mon_club. C'est celui la qui sera appellé si nom_de_mon_club est la
 * valeur "type de facturation" de l'écran de configuration club.
 *
 * Le programme appellera vos routines de facturation chaque fois qu'un vol sera créer ou modifié.
 * Pour simplifier, si un vol est supprimé ou modifié, tout les achats, les tickets et les écritures
 * relative aux vol sont supprimé. Le vol est ensuite refacturé s'il s'agissait d'une modification.
 * Vous n'avez donc à gérer ni la modification ni la suppression des vols.
 *
 * Dans les routines de facturation, il vous suffit de déterminer quels sont les achats déclenchés par le vol
 * en fonction de vols règles de facturation et de les générés.
 *
 * Dans mon club par exemple, on facture les heures de vols à l'heure (à des tarifs différents suivant le
 * type de forfait acquis par les pilotes) et le remorqué à l'unité à des tarifs différents suivant l'altitude (300 ou 500).
 * On facture à la centaine de metres au dela de 500m. Un seul vol peut donc déclencher un achat d'heure planeur,
 * un achat de remorqué et un achat de centaines de mètre supplémentaire.
 */
class Facturation {
    // Class attributes
    protected $attr;
    protected $CI;

    /**
     * Constructor
     *
     * @access Public
     * @param
     *            string
     * @return none
     */
    function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('tarifs_model');
        $this->CI->config->load('facturation');

        // Chargement des modules d'accès à la base de données pour aller
        // chercher les information suplémentaires
        $this->CI->load->model('membres_model');
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('avions_model');
        $this->CI->load->model('vols_avion_model');
        $this->CI->load->model('tickets_model');

        $this->CI->lang->load('facturation');
        // $this->CI->lang->line("")
    }

    /**
     * Constructor - Sets Widget Preferences
     *
     * The constructor can be passed an array of attributes values
     */
    public function Facturation($attrs = array()) {
        // set object attributes
        foreach ($attrs as $key => $value) {
            $this->attr[$key] = $attrs[$key];
        }
    }

    /**
     * Facturation d'un vol planeur
     * Génère les achats correspondants.
     *
     * @param unknown_type $vol
     *            = array (
     *            'vpid' => '1654', // numéro du vol
     *            'vpdate' => '2012-12-29', // date format mysql
     *            'vppilid' => 'vpeignot', // identifiant du pilote
     *            'vpmacid' => 'F-CECO', // immatriculation de la machine
     *            'vpcdeb' => '12.00', // heure de début en heure.minute
     *            'vpcfin' => '12.30', // heude de fin
     *            'vpduree' => '30.00', // durée en minutes
     *            'tempmoteur' => '0.00', // durée temps moteur
     *            'vpobs' => '', // commentaire sur le vol
     *            'instructeur' => '', // identifiant instructeur
     *            'vpcategorie' => '0', // categorie du vol définit dans config/program.php
     *            // $config['categories_vol_planeur'] = array (
     *            // 0 => 'Standard',
     *            // 1 => "Vol d'initiation",
     *            // 2 => "Vol d'essai",
     *            // 3 => "Concours"
     *            // );
     *            'vpautonome' => '3', // type de lancement défini dans language/xxx/gvv_lang.php
     *            // $lang['gvv_launch_type'] = array (
     *            // 1 => 'Treuil',
     *            // 2 => 'Autonome',
     *            // 3 => 'Remorqué',
     *            // 4 => 'Extérieur'
     *            // );
     *            'vpaltrem' => '500', // Altitude ou durée du remorquage
     *            'payeur' => 'ladams', // identifiant du payeur (le pilote si non spécifié)
     *            'pourcentage' => '50', // pourcentage que paye le payeur (le pilote paye le reste)
     *            'facture' => '0', // champ obsolete
     *            'remorqueur' => 'F-JUFA', // immaticulation du remorqueur
     *            'vplieudeco' => 'LFOI', // lieu de décollage
     *            'vpdc' => '0', // vrai si c'est un vol en double commande
     *            'vpticcolle' => '0', // Ticket de remorqué collé sur la planche
     *            'vpnumvi' => '', // numéro de vol d'initiation
     *            'pilote' => 'Vincent Peignot', // nom du pilote en toutes lettres
     *            'compte' => '37', // numéro du compte pilote
     *            'prive' => '0', // vol sur une machine privé
     *            'mprix' => 'Heure de vol biplace', // tarif horaire à appliquer
     *            'mprix_forfait' => 'Heure de vol forfait', // tarif horaire à appliquer pour les pilotes au forfait
     *            'mprix_moteur' => 'Gratuit', // tarif des heures moteur
     *            'mmax_facturation' => '180', // temps maximal de facturation en minutes
     *            )
     */
    public function facture_vol_planeur($vol) {
        // a surcharger
    }

    /**
     *
     *
     * Annule la facturation d'un vol
     *
     * @param unknown_type $vol_id
     */
    public function annule_facturation_vol_planeur($vol_id) {
        gvv_debug("Annulation de la facturation du vol planeur $vol_id");

        $this->CI->load->model('vols_planeur_model');

        $this->CI->load->model('achats_model');

        // On recherche les achats associés aux vols
        // On supprime ces achats
        $this->CI->achats_model->delete(array(
            'vol_planeur' => $vol_id,
            'facture' => 0
        ));

        $this->CI->tickets_model->delete(array(
            'vol' => $vol_id
        ));
    }

    /**
     * Facturation d'un vol avion
     * Génère les lignes de facture correspondante.
     *
     * @param unknown_type $vol
     *            Exemple d'information qu'on trouve dans la structure $vol
     *            array (
     *            'vaid' => 353, // numéro du vol
     *            'vadate' => '2012-12-29', // date du vol
     *            'vapilid' => 'fpeignot', // identifiant du pilote
     *            'vamacid' => 'F-JUFA', // identifiant de l'avion
     *            'vacdeb' => '13.90', // heure de début en heure.centième
     *            'vacfin' => '14.3', // heure de fin en heure.centième
     *            'vaduree' => '0.4', // durée du vol en heure
     *            'vaobs' => '', // commentaire sur le vol
     *            'vadc' => false, // vrai si double commande
     *            'vacategorie' => '0', // catégorie du vol définie dans application/config/config.php $config['categories_vol_avion']
     *            'varem' => false, // vrai pour les vols de remorquage
     *            'vanumvi' => '', // numéro de vol d'initiation
     *            'vanbpax' => '', // nombre de passager
     *            'vaprixvol' => false, // vol sur un avion privé
     *            'vainst' => '', // identifiant de l'instructeur
     *            'valieudeco' => '', // lieu de décollage
     *            'valieuatt' => '', // lieu d'atterrissage
     *            'facture' => false, // obsolete
     *            'payeur' => '', // nom du payeur si ce n'est pas le pilote
     *            'pourcentage' => '0', // pourcentage du cout à la charge du pilote
     *            'club' => false, // club d'appartenance de l'avion
     *            'gel' => false, // vol modifiable
     *            'saisie_par' => 'fpeignot', // utilisateur qui a crée le vol
     *            'vaatt' => '1', // nombre total d'atterrissage
     *            'local' => '0', // vrai pour les vols locaux
     *            'nuit' => false, // vrai pour les vols de nuit
     *            'reappro' => '0', // moment de l'avitaillement language/xxx/gvv_lang.php/$lang['gvv_refueling']
     *            'essence' => '0', // quantité d'essence ajouté
     *            'vahdeb' => '', // heure de début du vol
     *            'vahfin' => '', // heure de fin
     *            )
     *
     *            Exemple d'information pilote
     *            (si vous en avez besoin pour la facturation il faut faire une demande à la base)
     *            array (
     *            'mlogin' => 'fpeignot',
     *            'mnom' => 'Peignot',
     *            'mprenom' => 'Frédéric',
     *            'memail' => 'frederic.peignot@free.fr',
     *            'memailparent' => '',
     *            'madresse' => '1 Square des Sablons',
     *            'cp' => '78160',
     *            'ville' => 'Marly le Roi',
     *            'mtelf' => '',
     *            'mtelm' => '06 01 02 03 04',
     *            'mdaten' => '1959-08-29',
     *            'm25ans' => '0',
     *            'mlieun' => '0',
     *            'msexe' => 'M',
     *            'mniveaux' => '80128',
     *            'mbranum' => '',
     *            'mbradat' => '2010-12-11',
     *            'mbraval' => '2011-12-30',
     *            'mbrpnum' => 'VV01',
     *            'mbrpdat' => '1976-09-08',
     *            'mbrpval' => '2012-10-31',
     *            'numinstavion' => '',
     *            'dateinstavion' => '0000-00-00',
     *            'numivv' => '1PIC',
     *            'dateivv' => '2013-10-31',
     *            'medical' => '2010-10-31',
     *            'numlicencefed' => '',
     *            'vallicencefed' => '0000-00-00',
     *            'manneeins' => '0',
     *            'manneeffvv' => '0',
     *            'manneeffa' => '0',
     *            'msolde' => '0.00',
     *            'mforfvv' => '0',
     *            'macces' => '0',
     *            'club' => '0',
     *            'ext' => '0',
     *            'actif' => '1',
     *            'username' => '0',
     *            'photo' => '',
     *            'compte' => '0',
     *            'profil' => '0',
     *            'comment' => 'Licence annuelle 800220011L',
     *            'trigramme' => 'PGT',
     *            'categorie' => '0',
     *            )
     *
     *            Exemple d'information machine
     *            (si vous en avez besoin pour la facturation il faut faire une demande à la base)
     *            array (
     *            'macconstruc' => 'AEROPOOL',
     *            'macmodele' => 'Dynamic',
     *            'macimmat' => 'F-JUFA',
     *            'macnbhdv' => '6.10',
     *            'macplaces' => '2',
     *            'macrem' => '1',
     *            'maprive' => '0',
     *            'club' => '0',
     *            'actif' => '1',
     *            'comment' => 'DY 454/2012',
     *            'maprix' => 'Heure de vol Dynamic',
     *            'maprixdc' => 'Heure de vol Dynamic',
     *            'horametre_en_minutes' => '0',
     *            )
     *
     */
    public function facture_vol_avion($vol) {

        // Cette version réalise une facturation de base. C'est à dire qu'elle établit une ligne de facture
        // proportionnelle au temps de vol sauf en cas de vol d'essai, de vol d'initiation ou de remorqué.
        // Si cela ne vous satisfait pas il suffit de la surcharger.
        gvv_debug("facture_vol_avion " . var_export($vol, true));

        // Quelques variables pour simplifier la systaxe
        $date = $vol['vadate']; // date du vol
        $duree = $vol['vaduree']; // durée du vol en heure
        $machine = $vol['vamacid']; // immatriculation de l'avion
        $vol_id = $vol['vaid']; // identifiant du vol
        $payeur = $vol['payeur']; // identifiant du payeur si ce n'est pas le pilote
        $pourcentage = $vol['pourcentage']; // pourcentage à la charge du payeur
        $pilote = $vol['vapilid'];

        // On va chercher en base les informations suplémentaire sur le pilote,
        // l'avion, les tarifs à appliquer
        $pilote_info = $this->CI->membres_model->get_by_id('mlogin', $vol['vapilid']);
        $machine_info = $this->CI->avions_model->get_by_id('macimmat', $machine);

        $tarif_dc_info = $this->CI->tarifs_model->get_tarif($machine_info['maprixdc'], $date);

        gvv_debug("facture_vol_avion pilote " . var_export($pilote_info, true));
        gvv_debug("facture_vol_avion machine " . var_export($machine_info, true));

        // On facture la double en sus si un tarif DC existe pour l'avion
        $dc_a_facturer = ($vol['vadc'] && $tarif_dc_info['prix'] > 0);

        // Chaine de caractère identifiant le vol (date + machine)
        $image = $this->CI->vols_avion_model->image($vol['vaid']);

        // desc contient le commentaire qu'on affichera sur la facture
        // ce commentaire est enrichi en fonction des décisions prises pour la facturation
        $desc = $image;

        // Le vol est-il gratuit ?
        $free = FALSE;

        if ($vol['vacategorie'] == VI) {
            $desc .= " " . $this->CI->lang->line("facturation_vi"); // est-ce un vol d'initiation ?
            $free = TRUE;
        } elseif ($vol['vacategorie'] == VE) {
            // est-ce un vol d'essai ?
            $desc .= " " . $this->CI->lang->line("facturation_ve");
            $free = TRUE;
        } elseif ($vol['vacategorie'] == REM) {
            // est-ce un remorquage ?
            $desc .= " " . $this->CI->lang->line("facturation_rem");
            $free = TRUE;
        }

        // Cas de base, le vol est payé par le pilote, au prix de l'heure de vol
        // de l'avion. On génère une nouvelle ligne de facturation
        $this->nouvel_achat_partage(array(
            'date' => $date,
            'produit' => $machine_info['maprix'],
            'quantite' => ($free) ? 0 : $duree,
            'description' => $desc,
            'pilote' => $pilote,
            'machine' => $machine,
            'vol_avion' => $vol_id
        ), $pilote, $payeur, $pourcentage);

        if ($dc_a_facturer) {
            // Si il y a un surcout pour la double commande
            $this->nouvel_achat(array(
                'date' => $date,
                'produit' => $machine_info['maprixdc'],
                'quantite' => ($free) ? 0 : $duree,
                'description' => $tarif_dc_info['description'],
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_avion' => $vol_id
            ), $pilote, $payeur, $pourcentage);
        }
    }

    /**
     * Facturation d'un mvt pompe
     * Génère les lignes de facture correspondante.
     *
     * array
     * 'pid' => int 221
     * 'pnum' => string '0' (length=1)
     * 'pdatesaisie' => string '2012-05-03' (length=10)
     * 'pdatemvt' => string '2012-05-03' (length=10)
     * 'ppilid' => string 'abraracourcix' (length=13)
     * 'pmacid' => string 'F-BLIT' (length=6)
     * 'ptype' => string 'D' (length=1)
     * 'pqte' => int -100
     * 'ppu' => string '1' (length=1)
     * 'pprix' => string '100' (length=3)
     * 'pdesc' => string '' (length=0)
     * 'psaisipar' => string 'testadmin' (length=9)
     */
    public function facture_pompes($mvt) {
        var_dump($mvt);
    }

    /**
     * Crée une nouvelle ligne d'achat.
     *
     * Cette routine gère les pilotes qui volent sur le compte d'un autre (pilote
     * volant sur le compte d'un parent).
     *
     * Elle gère aussi les dates de tarifs. Elle applique le dernier tarif
     * applicable à la date de facturation/
     *
     * C'est la routine que doit appeller vos routine de facturation. Contrairement
     * aux routines de facturation, celle ci doit convenir à la majorité des associations.
     */
    protected function nouvel_achat($data) {
        // acces au model des achats
        $this->CI->load->model('achats_model');

        if ($data['quantite'] < 0.0000001 || $data['produit'] == $this->CI->lang->line("facturation_free_product")) {
            gvv_debug("nouvel_achat gratuit non comptabilisé" . var_export($data, true));
            return;
        } else {
            gvv_debug("nouvel_achat " . var_export($data, true));
        }

        // création de l'achat
        $data['facture'] = 0;
        $data['saisie_par'] = $this->CI->dx_auth->get_username();
        $data['club'] = 0;

        $pilote_info = $this->CI->membres_model->get_by_id('mlogin', $data['pilote']);
        gvv_debug("pilote_info " . var_export($pilote_info, true));
        if ($pilote_info['compte']) {
            // Si le pilote est facturé sur le compte d'un autre. (Cas des enfants facturés
            // sur le compte de leur parent)

            $compte_info = $this->CI->comptes_model->get_by_id('id', $pilote_info['compte']);
            $pilote = $compte_info['pilote'];

            // On ajoute le nom du pilote à la description du produit
            $data['description'] .= ' ' . $this->CI->membres_model->image($data['pilote']);
            // Et on remplace le compte à débiter
            $data['pilote'] = $pilote;
        }

        $res = $this->CI->achats_model->create($data);
        if (! $res) {
            throw new Exception($this->CI->lang->line("facturation_purchase_error") . " " . var_export($data, true));
        }

        $vol_id = "";
        if (isset($data['vol_avion'])) {
            $vol_id = "avion " . $data['vol_avion'];
        }
        if (isset($data['vol_planeur'])) {
            $vol_id = "planeur " . $data['vol_planeur'];
        }
        $msg = "Facturation: " . $data['date'] . ", produit=" . $data['produit'] . ", quantite=" . $data['quantite'] . " a " . $data['pilote'] . ", vol=$vol_id" . " sur " . $data['machine'] . " " . $data['description'];

        gvv_info($msg);
        return $res;
    }

    /**
     * Crée une ou plusieurs lignes de facture
     *
     * Si l'achat est partagé il est affecté au payeur à 100% ou 50 %
     *
     * @param $data =
     *            array (size=10)
     *            'date' => string '2012-12-29' (length=10)
     *            'produit' => string 'Remorqué 500m' (length=14)
     *            'quantite' => float 0.5
     *            'description' => string ' à 12h00 partagé 50%' (length=38)
     *            'pilote' => string 'fpeignot' (length=8)
     *            'machine' => string 'F-CECO' (length=6)
     *            'vol_planeur' => string '1654' (length=4)
     *            'facture' => int 0
     *            'saisie_par' => string 'fpeignot' (length=8)
     *            'club' => int 0
     * @param $pilote identifiant
     *            du pilote
     * @param $payeur identifiant
     *            du payeur
     * @param $pourcentage à
     *            affecter au au payeur
     * @param $name nom
     *            du pilote à insérer dans le descriptif de l'achat
     */
    protected function nouvel_achat_partage($data, $pilote, $payeur, $pourcentage, $name = "") {
        gvv_debug("nouvel achat partagé pilote=$pilote, payeur=$payeur, pourcentage=$pourcentage");

        if ($name == "") {
            $name = $this->CI->membres_model->image($data['pilote']);
        }

        if (($payeur) && ($pourcentage == 100)) {

            // tout pour le payeur
            $data['description'] .= " " . $this->CI->lang->line("facturation_paid_for") . " " . $name;
            $data['pilote'] = $payeur;

            $this->nouvel_achat($data);
        } elseif (($payeur) && ($pourcentage == 50)) {

            // partagé à 50 %
            $data['description'] .= " " . $this->CI->lang->line("facturation_shared_50");
            $data['quantite'] /= 2;
            $this->nouvel_achat($data);

            $data['pilote'] = $payeur;
            $this->nouvel_achat($data);
        } else {
            // pas de partage
            $this->nouvel_achat($data);
        }
    }

    /**
     * Decompte un ticket ou facture un produit quand il n'y a plus de tickets
     */
    protected function decompte_ou_facture($produit, $ticket, $desc, $vol_id, $date, $debut, $machine, $alt_rem, $vi, $pilote, $payeur, $pourcentage, $name) {

        // payeur à 100 % qui a assez de tickets
        if (($payeur) && ($this->CI->tickets_model->solde($payeur, $ticket) >= 1) && ($pourcentage == 100)) {

            // La prestation est décompté au payeur

            $nb = $this->CI->tickets_model->solde($payeur, $ticket) - 1;
            $desc .= " decompté, pilote=$name";
            $achat = $this->nouvel_achat(array(
                'date' => $date,
                'produit' => $produit,
                'quantite' => 0,
                'description' => $desc,
                'pilote' => $payeur,
                'machine' => $machine,
                'vol_planeur' => $vol_id
            ));

            // Décompte le ticket
            $this->CI->tickets_model->create(array(
                'date' => $date,
                'pilote' => $payeur,
                'achat' => $achat,
                'quantite' => -1,
                'description' => $desc,
                'saisie_par' => $this->CI->dx_auth->get_username(),
                'club' => 0,
                'type' => $ticket,
                'vol' => $vol_id
            ));

            // il y a un payeur à 100 % mais il n'a plus de tickets
        } elseif (($payeur) && ($pourcentage == 100)) {
            // Le treuillé est payé à l'unité par le payeur

            $this->nouvel_achat(array(
                'date' => $date,
                'produit' => $produit,
                'quantite' => 1,
                'description' => $desc,
                'pilote' => $payeur,
                'machine' => $machine,
                'vol_planeur' => $vol_id
            ));

            // il n'y a pas de payeur, ou il n'a plus de tickets
            // mais le pilote a encore des tickets
            //
            // On ne decompte jamais de demi tickets
        } elseif ($this->CI->tickets_model->solde($pilote, $ticket) >= 1) {

            // La prestation est décompté au pilote

            $nb = $this->CI->tickets_model->solde($pilote, $ticket) - 1;
            $desc .= " decompté";
            $achat = $this->nouvel_achat(array(
                'date' => $date,
                'produit' => $produit,
                'quantite' => 0,
                'description' => $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
            ));

            // Décompte le ticket
            $this->CI->tickets_model->create(array(
                'date' => $date,
                'pilote' => $pilote,
                'achat' => $achat,
                'quantite' => -1,
                'description' => $desc,
                'saisie_par' => $this->CI->dx_auth->get_username(),
                'club' => 0,
                'type' => $ticket,
                'vol' => $vol_id
            ));
        } else {

            // La prestation est payé à l'unité, eventuellement partagée
            $this->nouvel_achat_partage(array(
                'date' => $date,
                'produit' => $produit,
                'quantite' => 1,
                'description' => $desc,
                'pilote' => $pilote,
                'machine' => $machine,
                'vol_planeur' => $vol_id
            ), $pilote, $payeur, $pourcentage, $name);
        }
    }
}
