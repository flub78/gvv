<?php
if (! defined('BASEPATH'))
        exit('No direct script access allowed');

include_once("MetaData.php");

/**
 *
 * Metadata for GVV
 *
 * @author idefix
 * @package librairies
 */
class GVVMetadata extends Metadata {
        /**
         * Constructor
         */
        function __construct() {
                parent::__construct();

                $CI = &get_instance();
                $CI->lang->load('gvv');

                // @todo Ces déclaration devrait être déplacées vers les modèles
                // Elles décrivent les données et il serait plus efficace de n'en charger que les
                // tables utiles en fonction du contexte.

                // Cela a été commencé avec MailMetadata qui hérite de cette classe et ne contient que
                // les informations relatives à la gestion des mails.

                // Les types sont forcément ceux des tables (pas des vues). Les noms peuvent être
                // surchargés dans les vues.

                /**
                 * Vue achats
                 */

                $this->field['achats']['id']['Name'] = 'Id';
                $this->field['achats']['date']['Name'] = 'Date';
                $this->field['tarifs']['reference']['Name'] = 'Produit';
                $this->field['achats']['quantite']['Name'] = 'Quantité';
                $this->field['achats']['prix']['Name'] = 'Prix';
                $this->field['achats']['description']['Name'] = 'Description';
                $this->field['achats']['pilote']['Name'] = 'Pilote';
                $this->field['achats']['facture']['Name'] = 'Facture';

                $this->field['vue_achats']['prix_unit']['Name'] = 'Prix unitaire';
                $this->field['vue_achats']['prix']['Name'] = 'Prix';
                $this->field['vue_achats_per_year']['prix']['Name'] = 'Prix';
                $this->field['vue_achats_per_year']['prix_unit']['Name'] = 'Prix unitaire';
                $this->field['vue_achats_per_year']['quantite']['Name'] = 'Quantité';
                $this->field['vue_achats_per_year']['quantite']['Type'] = 'decimal';
                $this->field['vue_achats_per_year']['prix']['Subtype'] = 'currency';
                $this->field['vue_achats_per_year']['prix_unit']['Subtype'] = 'currency';

                $this->field['achats']['produit']['Subtype'] = 'selector';
                $this->field['achats']['produit']['Selector'] = 'produit_selector';
                $this->field['achats']['pilote']['Subtype'] = 'selector';
                $this->field['achats']['pilote']['Selector'] = 'pilote_selector';

                /**
                 * Vue categories
                 */
                $this->field['categorie']['nom']['Name'] = 'Nom';
                $this->field['categorie']['description']['Name'] = 'Description';
                $this->field['categorie']['parent']['Name'] = 'Parent';
                $this->field['categorie']['type']['Name'] = 'Type';

                $this->field['categorie']['parent']['Subtype'] = 'selector';
                $this->field['categorie']['parent']['Selector'] = 'parent_selector';
                $this->field['categorie']['parent']['Image'] = 'parent_image';

                $this->field['vue_categories']['nom_parent']['Name'] = 'Parent';
                /**
                 * Vue comptes
                 */
                $this->field['vue_comptes']['id']['Name'] = 'Compte';
                $this->field['comptes']['codec']['Name'] = 'Code';
                $this->field['comptes']['actif']['Name'] = 'Actif';
                $this->field['comptes']['debit']['Name'] = 'Débit';
                $this->field['comptes']['credit']['Name'] = 'Crédit';
                $this->field['comptes']['codec']['Subtype'] = 'selector';
                $this->field['comptes']['codec']['Selector'] = 'codec_selector';
                $this->field['comptes']['pilote']['Subtype'] = 'selector';
                $this->field['comptes']['pilote']['Selector'] = 'pilote_selector';
                $this->field['vue_comptes']['solde_debit']['Name'] = 'Solde débiteur';
                $this->field['vue_comptes']['solde_credit']['Name'] = 'Solde créditeur';
                $this->field['comptes']['saisie_par']['Default'] = 'current_user';
                $this->field['comptes']['debit']['Attrs'] = array(
                        'readonly' => "readonly"
                );
                $this->field['comptes']['credit']['Attrs'] = array(
                        'readonly' => "readonly"
                );
                $this->field['comptes']['saisie_par']['Attrs'] = array(
                        'readonly' => "readonly"
                );

                $this->field['comptes']['actif']['Subtype'] = 'enumerate';
                $this->field['vue_comptes']['actif']['Enumerate'] = $CI->lang->line("gvv_asset_liability");
                $this->field['vue_comptes']['debit']['Subtype'] = 'currency';
                $this->field['vue_comptes']['credit']['Subtype'] = 'currency';
                $this->field['vue_comptes']['solde_debit']['Subtype'] = 'currency';
                $this->field['vue_comptes']['solde_credit']['Subtype'] = 'currency';
                $this->field['vue_comptes']['id']['Subtype'] = 'key';
                $this->field['vue_comptes']['id']['Action'] = 'compta/journal_compte';
                $this->field['vue_comptes']['id']['Image'] = 'nom';
                $this->field['vue_comptes']['nom']['Type'] = 'varchar';
                $this->db['default_fields']['vue_comptes'] = array(
                        'codec',
                        'id',
                        // 'debit',
                        // 'credit',
                        'solde_debit',
                        'solde_credit'
                );

                /**
                 * Vue écritures
                 */
                // $this->field['ecritures']['id']['Name'] = 'Id';
                // $this->field['ecritures']['date_op']['Name'] = 'Date';
                // $this->field['ecritures']['compte1']['Name'] = 'Débit';
                // $this->field['ecritures']['code2']['Name'] = 'Code';
                // $this->field['ecritures']['compte2']['Name'] = 'Crédit';
                // $this->field['ecritures']['montant']['Name'] = 'Montant';
                // $this->field['ecritures']['description']['Name'] = 'Description';
                // $this->field['ecritures']['num_cheque']['Name'] = 'Référence';
                // $this->field['ecritures']['nom_compte2']['Name'] = 'Compte';
                // $this->field['ecritures']['debit']['Name'] = 'Débit';
                // $this->field['ecritures']['credit']['Name'] = 'Credit';
                $this->field['ecritures']['date_op']['Subtype'] = 'activity_date';

                $this->field['ecritures']['description']['Attrs'] = array(
                        'class' => "description"
                );
                $this->field['ecritures']['num_cheque']['Attrs'] = array(
                        'class' => "num_cheque"
                );

                $this->db['default_fields']['ecritures'] = array(
                        'id',
                        'date_op',
                        'compte1',
                        'compte2',
                        'montant',
                        'description',
                        'num_cheque'
                );

                $this->field['ecritures']['date_op']['Default'] = 'today';
                $this->field['ecritures']['annee_exercise']['Default'] = 'current_year';

                $this->field['ecritures']['compte1']['Subtype'] = 'selector';
                $this->field['ecritures']['compte1']['Selector'] = 'compte1_selector';
                $this->field['ecritures']['compte2']['Subtype'] = 'selector';
                $this->field['ecritures']['compte2']['Selector'] = 'compte2_selector';
                // temporaire
                $this->field['ecritures']['categorie']['Subtype'] = 'selector';
                $this->field['ecritures']['categorie']['Selector'] = 'categorie_selector';

                $this->field['ecritures']['debit']['Subtype'] = 'currency';
                $this->field['ecritures']['credit']['Subtype'] = 'currency';

                $this->db['default_fields']['vue_journal'] = array(
                        'id',
                        'date_op',
                        'code1',
                        'compte1',
                        'code2',
                        'compte2',
                        'description',
                        'num_cheque',
                        'montant',
                        'section',
                        'gel'
                );

                // TODO supporter la traduction
                $this->field['vue_journal']['autre_code']['Name'] = 'Code';
                $this->field['vue_journal']['code1']['Name'] = 'Emploi';
                $this->field['vue_journal']['code2']['Name'] = 'Ressource';
                $this->field['vue_journal']['autre_compte']['Name'] = 'Compte';
                $this->field['vue_journal']['prix']['Name'] = 'Prix unitaire';
                $this->field['vue_journal']['quantite']['Name'] = 'Quantité';
                $this->field['vue_journal']['debit']['Name'] = 'Débit';
                $this->field['vue_journal']['credit']['Name'] = 'Crédit';
                $this->field['vue_journal']['solde']['Name'] = 'Solde';
                $this->field['vue_journal']['gel']['Name'] = '';

                $this->field['ecritures']['gel']['Subtype'] = 'checkbox';
                $this->field['vue_journal']['gel']['Subtype'] = 'checkbox';

                $this->field['vue_journal']['quantite']['Subtype'] = 'int';

                $this->field['vue_journal']['compte1']['Subtype'] = 'key';
                $this->field['vue_journal']['compte1']['Action'] = 'compta/journal_compte';
                $this->field['vue_journal']['compte1']['Image'] = 'nom_compte1';

                $this->field['vue_journal']['compte2']['Subtype'] = 'key';
                $this->field['vue_journal']['compte2']['Action'] = 'compta/journal_compte';
                $this->field['vue_journal']['compte2']['Image'] = 'nom_compte2';

                $this->field['vue_journal']['autre_compte']['Subtype'] = 'key';
                $this->field['vue_journal']['autre_compte']['Action'] = 'compta/journal_compte';
                $this->field['vue_journal']['autre_compte']['Image'] = 'autre_nom_compte';

                $this->field['vue_journal']['date_op']['Type'] = 'date';
                $this->field['vue_journal']['description']['Type'] = 'varchar';
                $this->field['vue_journal']['num_cheque']['Type'] = 'varchar';

                $this->field['vue_journal']['montant']['Subtype'] = 'currency';

                $this->field['vue_journal']['debit']['Subtype'] = 'currency';
                $this->field['vue_journal']['credit']['Subtype'] = 'currency';
                $this->field['vue_journal']['solde']['Subtype'] = 'currency';
                $this->field['vue_journal']['prix']['Subtype'] = 'currency';

                /**
                 * Table event
                 */
                $this->alias_table["vue_exp_autre"] = "events";
                $this->alias_table["vue_exp_fai"] = "events";
                $this->alias_table["vue_exp_avion"] = "events";
                $this->alias_table["vue_exp_vv"] = "events";

                // $this->field['events_year']['event_type']['Name'] = 'Type';
                $this->field['events_year']['event_type']['Type'] = 'varchar';

                $this->field['events_year']['stat']['Name'] = 'Nb';
                $this->field['events_year']['stat']['Type'] = 'int';

                for ($i = 1; $i < 10; $i++) {
                        $this->field['events_year'][$i]['Type'] = 'varchar';
                        $this->field['events_year'][$i]['Name'] = '';
                }

                $this->field['events']['event_type']['Type'] = 'varchar';
                $this->field['events']['date']['Type'] = 'date';
                $this->field['events']['glider_flight']['Subtype'] = 'key';
                $this->field['events']['glider_flight']['Action'] = 'vols_planeur/edit';
                $this->field['events']['glider_flight']['Image'] = 'glider_flight_image';
                $this->field['events']['plane_flight']['Subtype'] = 'key';
                $this->field['events']['plane_flight']['Action'] = 'vols_avion/edit';
                $this->field['events']['plane_flight']['Image'] = 'plane_flight_image';

                /**
                 * Table planeur (machinesp)
                 */
                $this->keys['vue_planeurs'] = 'mpimmat';

                $this->alias_table["vue_planeurs"] = "machinesp";

                $this->field['machinesp']['prix']['Subtype'] = 'currency';
                $this->field['machinesp']['prix_forfait']['Subtype'] = 'currency';
                $this->field['machinesp']['prix_moteur']['Subtype'] = 'currency';

                $this->field['machinesp']['mpautonome']['Subtype'] = 'boolean';

                $this->field['machinesp']['mptreuil']['Subtype'] = 'boolean';
                $this->field['machinesp']['mpprive']['Subtype'] = 'enumerate';
                $this->field['machinesp']['mpprive']['Enumerate'] = $CI->lang->line("gvv_owner_type");
                $this->field['machinesp']['banalise']['Subtype'] = 'boolean';

                $this->field['machinesp']['actif']['Subtype'] = 'boolean';

                $this->field['machinesp']['mprix']['Subtype'] = 'selector';
                $this->field['machinesp']['mprix']['Selector'] = 'produit_selector';
                $this->field['machinesp']['mprix_forfait']['Subtype'] = 'selector';
                $this->field['machinesp']['mprix_forfait']['Selector'] = 'produit_selector';
                $this->field['machinesp']['mprix_moteur']['Subtype'] = 'selector';
                $this->field['machinesp']['mprix_moteur']['Selector'] = 'produit_selector';
                $this->field['machinesp']['proprio']['Subtype'] = 'selector';
                $this->field['machinesp']['proprio']['Selector'] = 'owner_selector';

                /**
                 * Table avion (machinesa)
                 */
                $this->keys['vue_avions'] = 'macimmat';

                $this->alias_table["vue_avions"] = "machinesa";

                $this->field['machinesa']['macconstruc']['Name'] = 'Constructeur';
                $this->field['machinesa']['macmodele']['Name'] = 'Modèle';
                $this->field['machinesa']['macimmat']['Name'] = 'Immat';
                $this->field['machinesa']['macnbhdv']['Name'] = 'Pré-heures';
                $this->field['machinesa']['macplaces']['Name'] = 'Places';
                $this->field['machinesa']['macrem']['Name'] = 'Remorqueur';
                $this->field['machinesa']['maprive']['Name'] = 'Privé';
                $this->field['machinesa']['actif']['Name'] = 'Actif';
                $this->field['machinesa']['maprix']['Name'] = 'Prix';
                $this->field['machinesa']['maprixdc']['Name'] = 'Prix DC';
                $this->field['machinesa']['vols']['Name'] = 'Vols';
                $this->field['machinesa']['fabrication']['Name'] = 'Mise en service';

                $this->field['machinesa']['macrem']['Subtype'] = 'boolean';
                $this->field['machinesa']['maprive']['Subtype'] = 'boolean';
                $this->field['machinesa']['actif']['Subtype'] = 'boolean';
                $this->field['machinesa']['horametre_en_minutes']['Subtype'] = 'boolean';

                $this->field['machinesa']['maprix']['Subtype'] = 'selector';
                $this->field['machinesa']['maprix']['Selector'] = 'produit_selector';
                $this->field['machinesa']['maprixdc']['Subtype'] = 'selector';
                $this->field['machinesa']['maprixdc']['Selector'] = 'produit_selector';

                $this->field['machinesa']['prix']['Name'] = 'Prix';

                /*
         * $this->field['machinesa']['vols']['Subtype'] = 'key';
         * $this->field['machinesa']['vols']['Action'] = 'membre/edit';
         * $this->field['machinesa']['vols']['Image'] = 'vols';
         */

                /**
                 * Table membre
                 */
                $this->field['membres']['mtelf']['Title'] = 'chiffres, espaces ou tirets';
                $this->field['membres']['mtelm']['Title'] = 'chiffres, espaces ou tirets';

                $this->field['membres']['m25ans']['Subtype'] = 'boolean';
                $this->field['membres']['ext']['Subtype'] = 'boolean';
                $this->field['membres']['actif']['Subtype'] = 'boolean';
                $this->field['membres']['nom_prenom']['Type'] = 'varchar';
                $this->field['membres']['memail']['Subtype'] = 'email';
                $this->field['membres']['memailparent']['Subtype'] = 'email';
                $this->field['membres']['photo']['Subtype'] = 'upload_image';

                $this->field['membres']['compte']['Subtype'] = 'selector';
                $this->field['membres']['compte']['Selector'] = 'compte_pilote_selector';
                $this->field['membres']['comment']['Title'] = 'Information supplémentaire';

                $this->field['membres']['inst_glider']['Subtype'] = 'selector';
                $this->field['membres']['inst_glider']['Selector'] = 'inst_glider_selector';
                $this->field['membres']['inst_airplane']['Subtype'] = 'selector';
                $this->field['membres']['inst_airplane']['Selector'] = 'inst_airplane_selector';

                $this->field['membres']['categorie']['Subtype'] = 'enumerate';
                $this->field['membres']['categorie']['Enumerate'] = $this->CI->config->item('categories_pilote');

                /**
                 * Table planc
                 */
                $this->field['planc']['pcode']['Name'] = 'Code';
                $this->field['planc']['pdesc']['Name'] = 'Description';

                /**
                 * Table tarifs
                 */
                $this->field['tarifs']['prix']['Subtype'] = 'currency';

                $this->field['vue_tarifs']['nom_compte']['Type'] = 'varchar';

                $this->field['vue_tarifs']['reference']['Type'] = 'varchar';
                $this->field['vue_tarifs']['description']['Type'] = 'varchar';
                $this->field['vue_tarifs']['prix']['Subtype'] = 'currency';
                $this->field['vue_tarifs']['date']['Type'] = 'date';
                $this->field['vue_tarifs']['date_fin']['Type'] = 'date';
                $this->field['tarifs']['compte']['Subtype'] = 'selector';
                $this->field['tarifs']['compte']['Selector'] = 'compte_selector';
                $this->field['tarifs']['date']['Default'] = 'today';

                $this->field['tarifs']['type_ticket']['Subtype'] = 'selector';
                $this->field['tarifs']['type_ticket']['Selector'] = 'ticket_selector';
                $this->field['tarifs']['type_ticket']['Default'] = '';
                $this->field['tarifs']['public']['Subtype'] = 'boolean';
                $this->field['vue_tarifs']['public']['Subtype'] = 'boolean';

                /**
                 * Table terrains
                 */
                $this->keys['vue_terrains'] = 'oaci';

                /**
                 * Table tickets
                 */
                $this->field['tickets']['date']['Name'] = 'Date';
                $this->field['tickets']['quantite']['Name'] = 'Quantité';
                $this->field['tickets']['description']['Name'] = 'Description';
                $this->field['vue_tickets']['vol']['Name'] = 'Vol';
                $this->field['vue_tickets']['pilote']['Name'] = 'Pilote';
                $this->field['tickets']['pilote']['Subtype'] = 'selector';
                $this->field['tickets']['pilote']['Selector'] = 'pilote_selector';

                $this->field['vue_tickets']['type']['Name'] = 'Type';
                $this->field['vue_tickets']['nom']['Name'] = 'Type';
                $this->field['tickets']['type']['Subtype'] = 'selector';
                $this->field['tickets']['type']['Selector'] = 'ticket_selector';

                $this->field['vue_tickets']['pilote']['Subtype'] = 'key';
                $this->field['vue_tickets']['pilote']['Action'] = 'membre/edit';
                $this->field['vue_tickets']['pilote']['Image'] = 'pilote_image';
                $this->field['vue_tickets']['vol']['Subtype'] = 'key';
                $this->field['vue_tickets']['vol']['Action'] = 'vols_planeur/edit';
                $this->field['vue_tickets']['vol']['Image'] = 'vol_image';

                $this->field['vue_tickets']['type']['Subtype'] = 'selector';
                $this->field['vue_tickets']['type']['Selector'] = 'ticket_selector';

                $this->db['default_fields']['vue_tickets'] = array(
                        'date',
                        'pilote',
                        'quantite',
                        'type',
                        'description',
                        'vol'
                );

                $this->field['vue_solde_tickets']['pilote']['Name'] = 'Pilote';
                $this->field['vue_solde_tickets']['type']['Name'] = 'Tickets';
                $this->field['vue_solde_tickets']['solde']['Name'] = 'Solde';

                $this->field['vue_solde_tickets']['solde']['Type'] = 'int';

                $this->field['vue_solde_tickets']['pilote']['Subtype'] = 'key';
                $this->field['vue_solde_tickets']['pilote']['Action'] = 'tickets/view';
                $this->field['vue_solde_tickets']['pilote']['Image'] = 'nom_prenom';
                $this->field['vue_solde_tickets']['nom']['Name'] = 'Type';

                /**
                 * Vols avion
                 */
                $this->keys['vue_vols_avion'] = 'vaid';

                $this->field['vue_vols_avion']['vadate']['Name'] = 'Date';
                $this->field['vue_vols_avion']['vapilid']['Name'] = 'Pilote';
                $this->field['vue_vols_avion']['instructeur']['Name'] = 'Instruct';
                $this->field['vue_vols_avion']['vamacid']['Name'] = 'Immat';
                $this->field['vue_vols_avion']['vacdeb']['Name'] = 'Début';
                $this->field['vue_vols_avion']['vacfin']['Name'] = 'Fin';
                $this->field['vue_vols_avion']['vaduree']['Name'] = 'Durée';
                $this->field['vue_vols_avion']['vaatt']['Name'] = 'Att';
                $this->field['vue_vols_avion']['vaobs']['Name'] = 'Observations';
                $this->field['vue_vols_avion']['valieudeco']['Name'] = 'Lieu';
                $this->field['vue_vols_avion']['vacategorie']['Name'] = 'VI';
                $this->field['vue_vols_avion']['vacategorie']['Name'] = 'Catégorie';
                $this->field['vue_vols_avion']['vadc']['Name'] = 'DC';
                $this->field['vue_vols_avion']['prive']['Name'] = 'Prv';
                $this->field['vue_vols_avion']['m25ans']['Name'] = '-25';
                $this->field['vue_vols_avion']['essence']['Name'] = 'Ess';

                $this->field['volsa']['vadate']['Default'] = 'today';

                $this->field['volsa']['vacategorie']['Subtype'] = 'boolean';
                // Remplacement vpcategorie par catégories_vol_planeur
                $this->field['volsa']['vacategorie']['Subtype'] = 'enumerate';
                $this->field['volsa']['vacategorie']['Enumerate'] = $this->CI->config->item('categories_vol_avion');

                $this->field['volsa']['vadate']['Subtype'] = 'activity_date';
                $this->field['volsa']['vadc']['Subtype'] = 'boolean';
                $this->field['volsa']['nuit']['Subtype'] = 'boolean';
                $this->field['volsa']['m25ans']['Subtype'] = 'boolean';
                $this->field['volsa']['vamacid']['Subtype'] = 'selector';
                $this->field['volsa']['vamacid']['Selector'] = 'machine_selector';
                $this->field['volsa']['vapilid']['Subtype'] = 'selector';
                $this->field['volsa']['vapilid']['Selector'] = 'pilote_selector';
                $this->field['volsa']['vainst']['Subtype'] = 'selector';
                $this->field['volsa']['vainst']['Selector'] = 'inst_selector';
                $this->field['volsa']['prive']['Subtype'] = 'boolean';
                $this->field['volsa']['payeur']['Subtype'] = 'selector';
                $this->field['volsa']['payeur']['Selector'] = 'payeur_selector';
                $this->field['volsa']['vaduree']['Subtype'] = 'centieme';

                $this->field['volsa']['pourcentage']['Subtype'] = 'enumerate';
                $this->field['volsa']['pourcentage']['Enumerate'] = array(
                        '0' => 0,
                        '50' => 50,
                        '100' => 100
                );

                $attrs = array(
                        'onChange' => "calcul()"
                );
                $this->field['volsa']['vacdeb']['Attrs'] = $attrs;
                $this->field['volsa']['vacfin']['Attrs'] = $attrs;
                $this->field['volsa']['vaduree']['Attrs'] = array(
                        'readonly' => "readonly"
                );

                $this->field['volsa']['reappro']['Subtype'] = 'enumerate';
                $this->field['volsa']['reappro']['Enumerate'] = $CI->lang->line("gvv_refueling");

                $this->field['volsa']['local']['Subtype'] = 'enumerate';
                $this->field['volsa']['local']['Enumerate'] = $CI->lang->line("gvv_navigation");

                $this->field['volsa']['valieudeco']['Subtype'] = 'selector';
                $this->field['volsa']['valieudeco']['Selector'] = 'terrains_selector';
                $this->field['volsa']['valieuatt']['Subtype'] = 'selector';
                $this->field['volsa']['valieuatt']['Selector'] = 'terrains_selector';

                $this->field['volsa']['vahdeb']['Subtype'] = 'time';
                $this->field['volsa']['vahfin']['Subtype'] = 'time';

                $this->field['vue_vols_avion']['vadate']['Type'] = 'date';

                $this->field['vue_vols_avion']['vapilid']['Subtype'] = 'key';
                $this->field['vue_vols_avion']['vapilid']['Action'] = 'membre/edit';
                $this->field['vue_vols_avion']['vapilid']['Image'] = 'pilote';
                $this->field['vue_vols_avion']['vamacid']['Subtype'] = 'key';
                $this->field['vue_vols_avion']['vamacid']['Action'] = 'avion/edit';

                $this->field['vue_vols_avion']['vacategorie']['Subtype'] = 'boolean';
                // Remplacement vpcategorie par catégories_vol_planeur
                $this->field['vue_vols_avion']['vacategorie']['Subtype'] = 'enumerate';
                $this->field['vue_vols_avion']['vacategorie']['Enumerate'] = $this->CI->config->item('categories_vol_avion_short');

                $this->field['vue_vols_avion']['vadc']['Subtype'] = 'boolean';
                $this->field['vue_vols_avion']['prive']['Subtype'] = 'boolean';
                $this->field['vue_vols_avion']['m25ans']['Subtype'] = 'boolean';

                $this->db['default_fields']['vue_vols_avion'] = array(
                        'vadate',
                        'vapilid',
                        'instructeur',
                        'vamacid',
                        'section_name',
                        'vacdeb',
                        'vacfin',
                        'vaduree',
                        'vaatt',
                        'vaobs',
                        'valieudeco',
                        'm25ans',
                        'vadc',
                        'vacategorie',
                        'prive',
                        'essence'
                );

                /**
                 * Vols planeur
                 */
                $this->keys['vue_vols_planeur'] = 'vpid';
                // $this->alias_table["vue_vols_planeur"] = "volsp";

                $this->field['vue_vols_planeur']['vpdate']['Type'] = 'date';
                $this->field['vue_vols_planeur']['vpcdeb']['Subtype'] = 'time';
                $this->field['vue_vols_planeur']['vpcfin']['Subtype'] = 'time';

                $this->field['vue_vols_planeur']['vpduree']['Subtype'] = 'minute';
                $this->field['vue_vols_planeur']['vpobs']['Type'] = 'varchar';

                $this->field['vue_vols_planeur']['vppilid']['Subtype'] = 'key';
                $this->field['vue_vols_planeur']['vppilid']['Action'] = 'membre/edit';
                $this->field['vue_vols_planeur']['vppilid']['Image'] = 'pilote';
                $this->field['vue_vols_planeur']['vpmacid']['Subtype'] = 'key';
                $this->field['vue_vols_planeur']['vpmacid']['Action'] = 'planeur/edit';

                if ($this->CI->config->item('remorque_100eme')) {
                        $this->field['volsp']['vpaltrem']['Default'] = 10;
                }
                $this->field['volsp']['vpduree']['Attrs'] = array(
                        'readonly' => "readonly"
                );

                $this->field['volsp']['vpcategorie']['Subtype'] = 'boolean';
                $this->field['volsp']['vpdc']['Subtype'] = 'boolean';

                // Remplacement vpcategorie par catégories_vol_planeur
                $this->field['volsp']['vpcategorie']['Subtype'] = 'enumerate';
                $this->field['volsp']['vpcategorie']['Enumerate'] = $this->CI->config->item('categories_vol_planeur');

                $this->field['volsp']['vpticcolle']['Subtype'] = 'boolean';

                $this->field['vue_vols_planeur']['vpcategorie']['Subtype'] = 'boolean';
                // Remplacement vpcategorie par catégories_vol_planeur
                $this->field['vue_vols_planeur']['vpcategorie']['Subtype'] = 'enumerate';
                $this->field['vue_vols_planeur']['vpcategorie']['Enumerate'] = $this->CI->config->item('categories_vol_planeur_short');

                $this->field['vue_vols_planeur']['vpdc']['Subtype'] = 'boolean';
                $this->field['vue_vols_planeur']['prive']['Subtype'] = 'boolean';
                $this->field['vue_vols_planeur']['m25ans']['Subtype'] = 'boolean';

                $this->field['vue_vols_planeur']['vpautonome']['Subtype'] = 'enumerate';
                $this->field['vue_vols_planeur']['vpautonome']['Enumerate'] = $CI->lang->line("gvv_short_launch_type");

                $this->field['volsp']['vpautonome']['Subtype'] = 'enumerate';
                $this->field['volsp']['vpautonome']['Enumerate'] = $CI->lang->line("gvv_launch_type");

                $this->field['volsp']['vpdate']['Subtype'] = 'activity_date';
                $this->field['volsp']['vpduree']['Subtype'] = 'minute';
                $this->field['volsp']['vpcdeb']['Subtype'] = 'time';
                $this->field['volsp']['vpcfin']['Subtype'] = 'time';
                $this->field['volsp']['vpmacid']['Subtype'] = 'selector';
                $this->field['volsp']['vpmacid']['Selector'] = 'machine_selector';
                $this->field['volsp']['vppilid']['Subtype'] = 'selector';
                $this->field['volsp']['vppilid']['Selector'] = 'pilote_selector';
                $this->field['volsp']['vpinst']['Subtype'] = 'selector';
                $this->field['volsp']['vpinst']['Selector'] = 'inst_selector';
                $this->field['volsp']['vppassager']['Subtype'] = 'selector';
                $this->field['volsp']['vppassager']['Selector'] = 'pilote_selector';
                $this->field['volsp']['vptreuillard']['Subtype'] = 'selector';
                $this->field['volsp']['vptreuillard']['Selector'] = 'treuillard_selector';

                $this->field['volsp']['remorqueur']['Subtype'] = 'selector';
                $this->field['volsp']['remorqueur']['Selector'] = 'rem_selector';
                $this->field['volsp']['pilote_remorqueur']['Subtype'] = 'selector';
                $this->field['volsp']['pilote_remorqueur']['Selector'] = 'pilrem_selector';
                $this->field['volsp']['payeur']['Subtype'] = 'selector';
                $this->field['volsp']['payeur']['Selector'] = 'payeur_selector';
                $this->field['volsp']['treuillard']['Subtype'] = 'selector';
                $this->field['volsp']['treuillard']['Selector'] = 'treuillard_selector';

                $this->field['volsp']['pourcentage']['Subtype'] = 'enumerate';
                $this->field['volsp']['pourcentage']['Enumerate'] = array(
                        '0' => 0,
                        '50' => 50,
                        '100' => 100
                );

                $this->field['volsp']['vplieudeco']['Subtype'] = 'selector';
                $this->field['volsp']['vplieudeco']['Selector'] = 'terrains_selector';
                $this->field['volsp']['vplieuatt']['Subtype'] = 'selector';
                $this->field['volsp']['vplieuatt']['Selector'] = 'terrains_selector';

                $this->field['volsp']['reappro']['Subtype'] = 'enumerate';
                $this->field['volsp']['reappro']['Enumerate'] = $CI->lang->line("gvv_refueling");

                $this->db['default_fields']['vue_vols_planeur'] = array(
                        'vpdate',
                        'vpcdeb',
                        'vpduree',
                        'vpmacid',
                        'vppilid',
                        'instructeur',
                        'vpautonome',
                        'rem_id',
                        'vpaltrem',
                        'vpobs',
                        'vplieudeco',
                        'vpnbkm',
                        'm25ans',
                        'vpdc',
                        'prive',
                        'vpcategorie'

                );
                // 'vpcfin'

                /*
         *
         * vue pompes
         */

                $this->keys['vue_pompes'] = 'pid';

                $this->field['vue_pompes']['pdatemvt']['Name'] = 'Date';
                $this->field['vue_pompes']['ppilid']['Name'] = 'Utilisateur';
                $this->field['vue_pompes']['ppilid']['Subtype'] = 'key';
                $this->field['vue_pompes']['ppilid']['Action'] = 'membre/edit';
                $this->field['vue_pompes']['ppilid']['Image'] = 'utilisateur';
                $this->field['vue_pompes']['pmacid']['Name'] = 'Appareil';
                $this->field['vue_pompes']['ptype']['Name'] = 'Type Mvt';
                $this->field['vue_pompes']['ptype']['Subtype'] = 'enumerate';
                $this->field['vue_pompes']['ptype']['Enumerate'] = $CI->lang->line("gvv_gas_operation");

                $this->field['vue_pompes']['pqte']['Name'] = 'QTE';
                $this->field['vue_pompes']['pprix']['Name'] = 'Prix';
                $this->field['vue_pompes']['pdesc']['Name'] = 'Description';

                $this->db['default_fields']['vue_pompes'] = array(
                        'pdatemvt',
                        'ppilid',
                        'pmacid',
                        'ptype',
                        'pqte',
                        'pprix',
                        'pdesc'
                );

                $this->field['pompes']['pdatemvt']['Default'] = 'today';
                $this->field['pompes']['ppilid']['Name'] = 'Facturer à';
                $this->field['pompes']['ppilid']['Subtype'] = 'selector';
                $this->field['pompes']['ppilid']['Selector'] = 'pilote_selector';
                $this->field['pompes']['pmacid']['Name'] = 'Immatriculation';
                $this->field['pompes']['pqte']['Name'] = 'Quantité';
                $this->field['pompes']['pqte']['Attrs'] = array(
                        'onChange' => "calculpompe()"
                );
                $this->field['pompes']['ptype']['Name'] = 'Type de mvt';
                $this->field['pompes']['ptype']['Subtype'] = 'enumerate';
                $this->field['pompes']['ptype']['Enumerate'] = $CI->lang->line("gvv_gas_operation");

                $this->field['pompes']['ppu']['Name'] = 'Tarif';
                $this->field['pompes']['ppu']['Subtype'] = 'selector';
                $this->field['pompes']['ppu']['Selector'] = 'prixu_selector';
                $this->field['pompes']['ppu']['Attrs'] = array(
                        'onChange' => "calculpompe()"
                );

                $this->field['pompes']['pprix']['Name'] = 'Montant';
                $this->field['pompes']['pprix']['Attrs'] = array(
                        'readonly' => "readonly"
                );
                $this->field['pompes']['pprix']['Name'] = 'Montant';
                $this->field['pompes']['pdesc']['Name'] = 'Commentaires';

                /*
                * Facturation, ce n'est pas vraiment une table mais on fait comme si
                * pour pouvoir bénéficier des routines associées
                */
                $fields = array(
                        'rem',
                        'remex',
                        'rem_100m',
                        'rem_bas',
                        'treuille',
                        'forfait_id',
                        'via1p',
                        'via2p',
                        'via3p',
                        'vip',
                        'essexte',
                        'essbase',
                        'essaces'
                );
                $this->fields['facturation'] = $fields;
                $this->field['facturation']['payeur_non_pilote']['Type'] = 'int';
                $this->field['facturation']['payeur_non_pilote']['Subtype'] = 'boolean';
                $this->field['facturation']['gestion_pompes']['Type'] = 'int';
                $this->field['facturation']['gestion_pompes']['Subtype'] = 'boolean';
                $this->field['facturation']['partage']['Type'] = 'int';
                $this->field['facturation']['partage']['Subtype'] = 'boolean';
                $this->field['facturation']['remorque_100eme']['Type'] = 'int';
                $this->field['facturation']['remorque_100eme']['Subtype'] = 'boolean';
                $this->field['facturation']['date_gel']['Type'] = 'date';

                $this->field['facturation']['payeur_non_pilote']['Comment'] = $CI->lang->line("gvv_comment_billed_to");
                $this->field['facturation']['partage']['Comment'] = $CI->lang->line("gvv_comment_sharing");
                $this->field['facturation']['gestion_pompes']['Comment'] = $CI->lang->line("gvv_comment_gas_station");
                $this->field['facturation']['remorque_100eme']['Comment'] = $CI->lang->line("gvv_comment_towin100");
                $this->field['facturation']['date_gel']['Comment'] = $CI->lang->line("gvv_comment_freeze");

                /**
                 * Attachments
                 */
                $this->field['attachments']['file']['Subtype'] = 'loader';
                $this->field['attachments']['file']['Subtype'] = 'upload_image';


                /**
                 * Rôles par section
                 */
                $this->field['user_roles_per_section']['types_roles_id']['Subtype'] = 'selector';
                $this->field['user_roles_per_section']['types_roles_id']['Selector'] = 'role_selector';

                $this->field['user_roles_per_section']['section_id']['Subtype'] = 'selector';
                $this->field['user_roles_per_section']['section_id']['Selector'] = 'section_selector';

                $this->field['user_roles_per_section']['user_id']['Subtype'] = 'selector';
                $this->field['user_roles_per_section']['user_id']['Selector'] = 'user_selector';

                /**
                 * Vols de découverte
                 */
                $this->alias_table["vue_vols_decouverte"] = "vols_decouverte";

                $this->field['vols_decouverte']['cancelled']['Subtype'] = 'checkbox';
                $this->field['vols_decouverte']['beneficiaire_email']['Subtype'] = 'email';

                $this->field['vols_decouverte']['validite']['Type'] = 'date';


                $this->field['vols_decouverte']['product']['Subtype'] = 'selector';
                $this->field['vols_decouverte']['product']['Selector'] = 'product_selector';

                $this->field['vols_decouverte']['pilote']['Subtype'] = 'selector';
                $this->field['vols_decouverte']['pilote']['Selector'] = 'pilote_selector';

                $this->field['vols_decouverte']['airplane_immat']['Subtype'] = 'selector';
                $this->field['vols_decouverte']['airplane_immat']['Selector'] = 'machine_selector';

                $this->field['vols_decouverte']['prix']['Subtype'] = 'currency';

                /**
                 * Associations des comptes OpenFLyers
                 */

                // $this->dump();
        }
}
