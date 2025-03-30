<?php
/*
 * GVV Fichier de traduction française
*/

# Compta

$lang['gvv_compta_title_line'] = "Ecriture comptable";
$lang['gvv_compta_title_recette'] = "Saisie d'une recette";
$lang['gvv_compta_title_wire'] = "Virement de compte à compte";
$lang['gvv_compta_title_depense'] = "Saisie d'une dépense";
$lang['gvv_compta_title_manual'] = "Facturation manuelle d'un pilote";
$lang['gvv_compta_title_remboursement'] = "Crédit d'un compte pilote pour remboursement de frais";
$lang['gvv_compta_title_paiement'] = "Reglement par pilote";
$lang['gvv_compta_title_avance'] = "Remboursement d'avance pilote";
$lang['gvv_compta_title_avoir'] = "Enregistrement d'un avoir fournisseur";
$lang['gvv_compta_title_avoir_use'] = "Dépense avec utilisation d'un avoir fournisseur";
$lang['gvv_comptes_title_journal'] = "Grand journal";
$lang['gvv_comptes_title_error'] = "Erreur";
$lang['gvv_comptes_error_no_account'] = "Il n'y a pas de compte associé à l'identifiant de connexion";
$lang['gvv_compta_title_entries'] = "Extrait du compte";

$lang['gvv_ecritures_field_date_op'] = "Date de l'opération";
$lang['gvv_ecritures_field_compte1'] = "Emploi";
$lang['gvv_ecritures_field_compte2'] = "Ressource";
$lang['gvv_ecritures_field_montant'] = "Montant de l'écriture";
$lang['gvv_ecritures_field_description'] = "Libellé";
$lang['gvv_ecritures_field_num_cheque'] = "Numéro de pièce comptable";
$lang['gvv_ecritures_field_gel'] = "Vérifié";

# $this->data['message'] = $this->lang->line("gvv_compta_message_advice_manual");

$lang['gvv_compta_message_advice_manual'] = "Si possible utilisez plutôt l'achat de produit"
	. " qui enregistrera aussi une vente.";

$lang['gvv_compta_message_advice_wire'] = "Emploi est le compte à créditer, Ressource est le compte à débiter";

$lang['gvv_compta_comptes'] = "Comptes";
$lang['gvv_compta_compte'] = "Compte";
$lang['gvv_compta_date'] = "Date";
$lang['gvv_compta_jusqua'] = "Jusqu'a";
$lang['gvv_compta_type_ecriture'] = array(0 => 'Tout', 1 => 'Vérifiés', 2 => 'Non vérifiés');
$lang['gvv_compta_emploi'] = "Emploi";
$lang['gvv_compta_resource'] = "Ressource";
$lang['gvv_compta_montant_min'] = "Montant minimum";
$lang['gvv_compta_montant_max'] = "maximum";
$lang['gvv_compta_selector_debit_credit'] = array(0 => 'Débits et crédits', 1 => 'Débits', 2 => 'Crédits');

$lang['gvv_compta_button_freeze'] = "Gel";

$lang['gvv_vue_journal_short_field_id'] = "Id";
$lang['gvv_vue_journal_short_field_date_op'] = "Date";
$lang['gvv_vue_journal_short_field_code1'] = "Code";
$lang['gvv_vue_journal_short_field_compte1'] = "Débit";
$lang['gvv_vue_journal_short_field_code2'] = "Code";
$lang['gvv_vue_journal_short_field_compte2'] = "Crédit";
$lang['gvv_vue_journal_short_field_description'] = "Description";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Référence";
$lang['gvv_vue_journal_short_field_montant'] = "Montant";
$lang['gvv_vue_journal_short_field_gel'] = "Gel";

$lang['gvv_ecritures_short_field_id'] = "Id";
$lang['gvv_ecritures_short_field_date_op'] = "Date";
$lang['gvv_ecritures_short_field_code1'] = "Code";
$lang['gvv_ecritures_short_field_compte1'] = "Débit";
$lang['gvv_ecritures_short_field_code2'] = "Code";
$lang['gvv_ecritures_short_field_compte2'] = "Crédit";
$lang['gvv_ecritures_short_field_description'] = "Description";
$lang['gvv_ecritures_short_field_num_cheque'] = "Référence";
$lang['gvv_ecritures_short_field_montant'] = "Montant";
$lang['gvv_ecritures_short_field_gel'] = "Gel";
$lang['gvv_ecritures_short_field_nom_compte2'] = "Compte";

$lang['gvv_vue_journal_selector'] = array(
	0 => "Selectionner ...",
	1 => "Les dépenses", // Emploi 600 - 700
	2 => "Les recettes", // ressources 700 - 800
	3 => "Les paiements pilotes", // Ressources 411
	4 => "Les immobilisations" // Emploi 200-300
);

$lang['gvv_compta_fieldset_addresses'] = "Adresse";
$lang['gvv_compta_fieldset_association'] = "Association";
$lang['gvv_compta_fieldset_pilote'] = "Pilote";
$lang['gvv_compta_fieldset_compte'] = "Compte";
$lang['gvv_compta_fieldset_achats'] = "Achats";
$lang['gvv_compta_filter_tooltip'] = 'Cliquez pour afficher/masquer';

$lang['gvv_compta_label_accounting_code'] = 'Code comptable';
$lang['gvv_compta_label_description'] = 'Description';
$lang['gvv_compta_label_balance_before'] = 'Solde avant le';
$lang['gvv_compta_label_debitor'] = 'débiteur';
$lang['gvv_compta_label_creditor'] = 'créditeur';
$lang['gvv_compta_label_balance_at'] = 'Solde au';
$lang['gvv_compta_label_section'] = 'Section';


$lang['gvv_compta_purchase_headers'] =  array("Date", "Produit", "Quantité", "Commentaire", "");

$lang['gvv_vue_journal_short_field_date_op'] = "Date";
$lang['gvv_vue_journal_short_field_autre_compte'] = "Compte";
$lang['gvv_vue_journal_short_field_description'] = "Description";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Référence";
$lang['gvv_vue_journal_short_field_prix'] = "Prix unitaire";
$lang['gvv_vue_journal_short_field_quantite'] = "Quantité";
$lang['gvv_vue_journal_short_field_debit'] = "Débit";
$lang['gvv_vue_journal_short_field_credit'] = "Crédit";
$lang['gvv_vue_journal_short_field_solde'] = "Solde";
$lang['gvv_vue_journal_short_field_gel'] = "Gelé";
$lang['gvv_vue_journal_short_field_section'] = "Section";

$lang['gvv_compta_csv_header'] = array('Date', 'Code', 'Compte', 'Description', 'Référence', 'Débit', 'Crédit');
$lang['gvv_compta_csv_header_411'] = array('Date', 'Description', 'Référence', 'Prix', 'Quantité', 'Débit', 'Crédit');

$lang['gvv_compta_error_same_accounts'] = "Dans une écriture, les comptes doivent être différents.";

$lang['valid_numeric'] = "Valeur non décimale";
