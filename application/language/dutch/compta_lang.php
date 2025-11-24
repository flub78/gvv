<?php
/*
 * GVV Nederlandse vertaling
 */

# Accounting

$lang['gvv_compta_title_line'] = "Boeking";
$lang['gvv_compta_title_recette'] = "Boeken ontvangst";
$lang['gvv_compta_title_wire'] = "Overschrijving";
$lang['gvv_compta_title_depense'] = "Boeken uitgave";
$lang['gvv_compta_title_manual'] = "Handmatig factureren piloot";
$lang['gvv_compta_title_remboursement'] = "Terugbetaling onkosten piloot";
$lang['gvv_compta_title_paiement'] = "Betaling door piloot";
$lang['gvv_compta_title_avance'] = "Voorafbetaling door piloot";
$lang['gvv_compta_title_avoir'] = "Registratie leverancierskrediet";
$lang['gvv_compta_title_avoir_use'] = "Uitgave betaald met leverancierskrediet";
$lang['gvv_compta_title_depot'] = "Contante storting";
$lang['gvv_compta_title_retrait'] = "Vloeistofonttrekking";
$lang['gvv_compta_title_remb_capital'] = "Terugbetaling leningkapitaal";
$lang['gvv_compta_title_encaissement_section'] = "Inning voor een sectie";
$lang['gvv_compta_title_reversement_section'] = "Sectie omkering";
$lang['gvv_compta_title_saisie_cotisation'] = "Contributie Registratie";
$lang['gvv_comptes_title_journal'] = "Uitgebreid journaal boekingen";
$lang['gvv_comptes_title_error'] = "Fout";
$lang['gvv_comptes_error_no_account'] = "De gebruiker heeft rekening gekoppeld aan zijn account";
$lang['gvv_compta_title_entries'] = "Uittreksel rekening";

$lang['gvv_ecritures_field_date_op'] = "Boekingsdatum";
$lang['gvv_ecritures_field_compte1'] = "Bron";
$lang['gvv_ecritures_field_compte2'] = "Grootboekrekening";
$lang['gvv_ecritures_field_montant'] = "Bedrag";
$lang['gvv_ecritures_field_description'] = "Omschrijving";
$lang['gvv_ecritures_field_num_cheque'] = "Referentie boeking";
$lang['gvv_ecritures_field_gel'] = "Gecontroleerd";

# $this->data['message'] = $this->lang->line("gvv_compta_message_advice_manual");

$lang['gvv_compta_message_advice_manual'] = "Indien mogelijk probeer aankoop product te gebruiken"
	. " dat registreert ook een verkoop.";

$lang['gvv_compta_message_advice_wire'] = "De bron is de te krediteren rekeningen, de grootboekrekening is de te debiteren rekening";

$lang['gvv_compta_comptes'] = "Rekeningen";
$lang['gvv_compta_compte'] = "Rekening";
$lang['gvv_compta_date'] = "Datum";
$lang['gvv_compta_jusqua'] = "Tot en met";
$lang['gvv_compta_type_ecriture'] = array(0 => 'Alles', 1 => 'Gecontroleerd', 2 => 'Niet gecontroleerd');
$lang['gvv_compta_emploi'] = "Uitgave";
$lang['gvv_compta_resource'] = "Inkomst";
$lang['gvv_compta_montant_min'] = "Minimum bedrag";
$lang['gvv_compta_montant_max'] = "Maximum";
$lang['gvv_compta_selector_debit_credit'] = array(0 => 'Debet en credit', 1 => 'Debet', 2 => 'Credit');

$lang['gvv_compta_button_freeze'] = "Lock";

$lang['gvv_vue_journal_short_field_id'] = "Id";
$lang['gvv_vue_journal_short_field_date_op'] = "Datum";
$lang['gvv_vue_journal_short_field_code1'] = "Code";
$lang['gvv_vue_journal_short_field_compte1'] = "Gedebiteerd";
$lang['gvv_vue_journal_short_field_code2'] = "Code";
$lang['gvv_vue_journal_short_field_compte2'] = "Gecrediteerd";
$lang['gvv_vue_journal_short_field_description'] = "Omschrijving";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Referentie";
$lang['gvv_vue_journal_short_field_montant'] = "Bedrag";
$lang['gvv_vue_journal_short_field_gel'] = "Lock";

$lang['gvv_ecritures_short_field_id'] = "Id";
$lang['gvv_ecritures_short_field_date_op'] = "Datum";
$lang['gvv_ecritures_short_field_code1'] = "Code";
$lang['gvv_ecritures_short_field_compte1'] = "Gedebiteerd";
$lang['gvv_ecritures_short_field_code2'] = "Code";
$lang['gvv_ecritures_short_field_compte2'] = "Gecrediteerd";
$lang['gvv_ecritures_short_field_description'] = "Description";
$lang['gvv_ecritures_short_field_num_cheque'] = "Omschrijving";
$lang['gvv_ecritures_short_field_montant'] = "Bedrag";
$lang['gvv_ecritures_short_field_gel'] = "Lock";
$lang['gvv_ecritures_short_field_nom_compte2'] = "Rekening";

$lang['gvv_vue_journal_selector'] = array(
	0 => "Selecteer ...",
	1 => "Uitgaves", // Emploi 600 - 700
	2 => "Ontvangsten", // ressources 700 - 800
	3 => "Betalingen piloten", // Ressources 411
	4 => "Passiva/Activa" // Emploi 200-300
);

$lang['gvv_compta_fieldset_addresses'] = "Adres";
$lang['gvv_compta_fieldset_association'] = "Vereniging";
$lang['gvv_compta_fieldset_pilote'] = "Piloot";
$lang['gvv_compta_fieldset_compte'] = "Rekening";
$lang['gvv_compta_fieldset_achats'] = "Aankoop";
$lang['gvv_compta_filter_tooltip'] = 'Klik om te verbergen/tonen';

$lang['gvv_compta_label_accounting_code'] = 'Boekhoudcode';
$lang['gvv_compta_label_description'] = 'Omschrijving';
$lang['gvv_compta_label_balance_before'] = 'Saldo voor';
$lang['gvv_compta_label_debitor'] = 'Debiteur';
$lang['gvv_compta_label_creditor'] = 'Crediteur';
$lang['gvv_compta_label_balance_at'] = 'Saldo op';
$lang['gvv_compta_label_section'] = 'Sectie';
$lang['gvv_compta_label_compte_banque'] = 'Bankrekening (512)';
$lang['gvv_compta_label_compte_pilote'] = 'Pilootrekening (411)';
$lang['gvv_compta_label_compte_recette'] = 'Contributie-ontvangsten rekening (700)';
$lang['gvv_compta_label_annee_cotisation'] = 'Contributiejaar';
$lang['gvv_compta_label_montant'] = 'Contributiebedrag';
$lang['gvv_compta_label_pilote'] = 'Lid';
$lang['gvv_compta_error_double_cotisation'] = 'Dit lid heeft al een contributie voor dit jaar';
$lang['gvv_compta_success_cotisation'] = 'Contributie succesvol geregistreerd';
$lang['gvv_compta_error_cotisation'] = 'Fout bij het registreren van de contributie';

$lang['gvv_compta_purchase_headers'] =  array("Datum", "Product", "Aantal", "Opmerking", "");

$lang['gvv_vue_journal_short_field_date_op'] = "Datum";
$lang['gvv_vue_journal_short_field_autre_compte'] = "Rekening";
$lang['gvv_vue_journal_short_field_description'] = "Omschrijving";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Referentie";
$lang['gvv_vue_journal_short_field_prix'] = "Eenheidsprijs";
$lang['gvv_vue_journal_short_field_quantite'] = "Aantal";
$lang['gvv_vue_journal_short_field_debit'] = "Debet";
$lang['gvv_vue_journal_short_field_credit'] = "Credit";
$lang['gvv_vue_journal_short_field_solde'] = "Saldo";
$lang['gvv_vue_journal_short_field_gel'] = "Locked";
$lang['gvv_vue_journal_short_field_section'] = "Sectie";

$lang['gvv_compta_csv_header'] = array('Datum', 'Code', 'Rekening', 'Omschrijving', 'Referentie', 'Debet', 'Credit', 'Saldo');
$lang['gvv_compta_csv_header_411'] = array('Datum', 'Omschrijving', 'Referentie', 'Prijs', 'Aantal', 'Debet', 'Credit', 'Saldo');

$lang['gvv_compta_error_same_accounts'] = "Bij een boekhoudkundige boeking moeten de rekeningen verschillend zijn.";
$lang['gvv_compta_frozen_line_cannot_modify'] = "Wijziging van een vergrendelde boeking is verboden.";
$lang['gvv_compta_frozen_line_cannot_delete'] = "Verwijdering van een vergrendelde boeking is verboden.";

// Attachment upload (Phase 1)
$lang['gvv_choose_files'] = "Bestanden kiezen";
$lang['gvv_optional'] = "optioneel";
$lang['gvv_supported_formats'] = "Ondersteunde formaten";
$lang['gvv_confirm_remove_file'] = "Weet u zeker dat u dit bestand wilt verwijderen?";
$lang['gvv_attachment_description'] = "Beschrijving (optioneel)"; // PRD CA1.9

$lang['valid_numeric'] = "Niet-decimale waarde";

$lang['gvv_compta_title_remb_capital'] = "Terugbetaling leningkapitaal";
