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
$lang['gvv_compta_title_paiement'] = "Approvisionement compte pilote";
$lang['gvv_compta_title_avance'] = "Remboursement d'avance pilote";
$lang['gvv_compta_title_avoir'] = "Enregistrement d'un avoir fournisseur";
$lang['gvv_compta_title_avoir_use'] = "Dépense avec utilisation d'un avoir fournisseur";
$lang['gvv_compta_title_depot'] = "Dépôt d'espèces";
$lang['gvv_compta_title_retrait'] = "Retrait de liquide";
$lang['gvv_compta_title_remb_capital'] = "Remboursement de capital d'emprunt";
$lang['gvv_compta_title_mise_disposition_emprunt'] = "Mise a disposition d'emprunt";
$lang['gvv_compta_title_amortissement'] = "Dotation aux amortissements";
$lang['gvv_compta_title_encaissement_section'] = "Encaissement pour une section";
$lang['gvv_compta_title_reversement_section'] = "Reversement section";
$lang['gvv_compta_title_saisie_cotisation'] = "Enregistrement Cotisation";
$lang['gvv_comptes_title_journal'] = "Grand journal";
$lang['gvv_comptes_title_error'] = "Erreur";
$lang['gvv_comptes_error_no_account'] = "Il n'y a pas de compte associé à l'identifiant de connexion";
$lang['gvv_compta_title_entries'] = "Extrait de compte";

$lang['gvv_ecritures_field_date_op'] = "Date de l'opération";
$lang['gvv_ecritures_field_compte1'] = "Débit";
$lang['gvv_ecritures_field_compte2'] = "Crédit";
$lang['gvv_ecritures_field_montant'] = "Montant de l'écriture";
$lang['gvv_ecritures_field_description'] = "Libellé";
$lang['gvv_ecritures_field_num_cheque'] = "Numéro de pièce comptable";
$lang['gvv_ecritures_field_gel'] = "Vérifié";

# $this->data['message'] = $this->lang->line("gvv_compta_message_advice_manual");

$lang['gvv_compta_message_advice_manual'] = "Si possible utilisez plutôt l'achat de produit"
	. " qui enregistrera aussi une vente.";

$lang['gvv_compta_message_advice_wire'] = "Débit : compte débité — Crédit : compte crédité";

$lang['gvv_compta_comptes'] = "Comptes";
$lang['gvv_compta_compte'] = "Compte";
$lang['gvv_compta_date'] = "Date";
$lang['gvv_compta_jusqua'] = "Jusqu'à";
$lang['gvv_compta_type_ecriture'] = array(0 => 'Tout', 1 => 'Vérifiés', 2 => 'Non vérifiés');
$lang['gvv_compta_emploi'] = "Débit";
$lang['gvv_compta_resource'] = "Crédit";
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
$lang['gvv_compta_label_compte_banque'] = 'Compte banque (512)';
$lang['gvv_compta_label_compte_pilote'] = 'Compte pilote (411)';
$lang['gvv_compta_label_compte_recette'] = 'Compte recette cotisation (700)';
$lang['gvv_compta_label_annee_cotisation'] = 'Année de cotisation';
$lang['gvv_compta_label_montant'] = 'Montant de la transaction';
$lang['gvv_compta_label_pilote'] = 'Membre';
$lang['gvv_compta_error_double_cotisation'] = 'Ce membre a déjà une cotisation pour cette année';
$lang['gvv_compta_success_cotisation'] = 'Cotisation enregistrée avec succès';
$lang['gvv_compta_error_cotisation'] = 'Erreur lors de l\'enregistrement de la cotisation';


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

$lang['gvv_compta_csv_header'] = array('Date', 'Code', 'Compte', 'Description', 'Référence', 'Débit', 'Crédit', 'Solde');
$lang['gvv_compta_csv_header_411'] = array('Date', 'Description', 'Référence', 'Prix', 'Quantité', 'Débit', 'Crédit', 'Solde');

$lang['gvv_compta_error_same_accounts'] = "Dans une écriture, les comptes doivent être différents.";
$lang['gvv_compta_frozen_line_cannot_modify'] = "La modification d'une écriture gelée est interdite.";
$lang['gvv_compta_frozen_line_cannot_delete'] = "La suppression d'une écriture gelée est interdite.";

// Attachment upload (Phase 1)
$lang['gvv_choose_files'] = "Choisir des fichiers";
$lang['gvv_optional'] = "facultatif";
$lang['gvv_supported_formats'] = "Formats supportés";
$lang['gvv_confirm_remove_file'] = "Êtes-vous sûr de vouloir supprimer ce fichier ?";
$lang['gvv_attachment_description'] = "Description (facultatif)"; // PRD CA1.9

$lang['valid_numeric'] = "Valeur non décimale";

// Transfert d'écritures
$lang['gvv_transfert_title']            = "Transfert d'écritures";
$lang['gvv_transfert_select_ecriture']  = "Ecriture";
$lang['gvv_transfert_add']              = "Ajouter";
$lang['gvv_transfert_selection']        = "Ecritures sélectionnées";
$lang['gvv_transfert_empty']            = "Aucune écriture sélectionnée.";
$lang['gvv_transfert_export']           = "Exporter";
$lang['gvv_transfert_already_added']    = "Cette écriture est déjà dans la liste.";
$lang['gvv_transfert_col_date']         = "Date";
$lang['gvv_transfert_col_emploi']       = "Débit";
$lang['gvv_transfert_col_ressource']    = "Crédit";
$lang['gvv_transfert_col_montant']      = "Montant";
$lang['gvv_transfert_col_description']  = "Libellé";
$lang['gvv_transfert_col_actions']      = "";
$lang['gvv_transfert_preview_json']     = "Prévisualiser le JSON";
$lang['gvv_transfert_preview_modal_title'] = "Prévisualisation du JSON d'export";
$lang['gvv_transfert_copy_json']        = "Copier dans le presse-papier";
$lang['gvv_transfert_copy_ok']          = "JSON copié dans le presse-papier.";
$lang['gvv_transfert_copy_ko']          = "Impossible de copier automatiquement. Copiez manuellement.";
$lang['gvv_import_title']               = "Import d'écritures";
$lang['gvv_import_file_label']          = "Fichier d'export (.json)";
$lang['gvv_import_submit']              = "Importer";
$lang['gvv_import_text_label']          = "Ou collez le JSON d'export";
$lang['gvv_import_text_submit']         = "Importer depuis la zone de texte";
$lang['gvv_import_success']             = "écritures importées avec succès.";
$lang['gvv_import_error_json']          = "Fichier JSON invalide.";
$lang['gvv_import_error_codec']         = "Compte introuvable pour le codec";
$lang['gvv_import_error_insert']        = "Erreur d'insertion pour l'écriture du";
$lang['gvv_import_errors_title']        = "Erreurs";
$lang['gvv_import_skipped']             = "Aucune écriture importée suite aux erreurs.";
$lang['gvv_transfert_menu']             = "Transfert d'écritures";
$lang['gvv_import_error_old_format']    = "Fichier au format v1.0 — veuillez ré-exporter depuis l'instance source.";
$lang['gvv_import_error_compte_not_found'] = "Compte introuvable :";
$lang['gvv_import_error_codec_mismatch']   = "Code comptable différent :";
$lang['gvv_import_error_nom_mismatch']     = "Nom du compte différent :";
$lang['gvv_import_error_section_mismatch'] = "Les deux comptes appartiennent à des sections différentes :";
$lang['gvv_import_nothing_selected']    = "Aucune écriture sélectionnée.";
$lang['gvv_import_preview_title']       = "Prévisualisation de l'import";
$lang['gvv_import_col_status']          = "Statut";
$lang['gvv_import_col_date']            = "Date";
$lang['gvv_import_col_emploi']          = "Débit";
$lang['gvv_import_col_ressource']       = "Crédit";
$lang['gvv_import_col_montant']         = "Montant";
$lang['gvv_import_col_description']     = "Libellé";
$lang['gvv_import_confirm']             = "Importer la sélection";
$lang['gvv_import_result_title']        = "Résultat de l'import";
$lang['gvv_import_rollback']            = "Aucune écriture insérée suite aux erreurs (annulation).";
$lang['gvv_import_select_all']          = "Tout cocher";
$lang['gvv_import_deselect_all']        = "Tout décocher";
$lang['gvv_import_create_missing_account'] = "Créer le compte manquant";
$lang['gvv_import_missing_account_context_msg'] = "Le compte %s %s n'existe pas sur cette instance. Complétez et validez le formulaire pour revenir à l'import.";
$lang['gvv_import_missing_account_invalid_data'] = "Impossible de préparer la création du compte : données incomplètes.";
