<?php
// Kopteksten voor afsluitingspagina (slechts 4 kolommen)
$lang['comptes_cloture_list_header'] = array('<strong>Code</strong>', '<strong>Naam</strong>', '<strong>Debetsaldo</strong>', '<strong>Creditsaldo</strong>');
/*
 * GVV Nederlandse vertaling
 */

$lang['gvv_comptes_title'] = "Rekening";
$lang['gvv_comptes_title_balance'] = "Balans der rekeningen";
$lang['gvv_comptes_title_detailed_balance'] = "Gedetailleerde balans van rekeningen";

$lang['gvv_comptes_title_bilan'] = "Balans";
$lang['gvv_comptes_title_resultat'] = "Jaarresultaat";
$lang['gvv_comptes_title_cloture'] = "Sluiting boekingen voor boekjaar";
$lang['gvv_comptes_title_cash'] = "Cash";
$lang['gvv_comptes_title_journaux'] = "Journaal der rekeningen";
$lang['gvv_comptes_title_financial'] = "Financieel raport voor boekjaar";
$lang['gvv_comptes_title_sales'] = "Verkopen";

$lang['gvv_comptes_field_nom'] = "Naam rekening";
$lang['gvv_comptes_field_codec'] = "Boekhoudkundige code";
$lang['gvv_comptes_field_desc'] = "Omschrijving";
$lang['gvv_comptes_field_debit'] = "Debet";
$lang['gvv_comptes_field_credit'] = "Credit";
$lang['gvv_comptes_field_saisie_par'] = "Aangemaakt door";
$lang['gvv_comptes_field_pilote'] = "Referentie Piloot";

$lang['gvv_vue_comptes_short_field_codec'] = "Code";
$lang['gvv_vue_comptes_short_field_id'] = "Rekening";
$lang['gvv_vue_comptes_short_field_solde_debit'] = "Saldo debiteur";
$lang['gvv_vue_comptes_short_field_solde_credit'] = "Saldo crediteur";
$lang['gvv_vue_comptes_short_field_nom'] = "Rekening";
$lang['gvv_vue_comptes_short_field_section_name'] = "Sectie";

$lang['comptes_filter_active_select'] = array(0 => 'Alle', 1 => 'Debiteurs', 2 => 'Niet negatief', 3 => 'Crediteurs', 4 => 'Nulsaldo');

$lang['comptes_label_totals'] = "Totalen";
$lang['comptes_label_total'] = "Totaal";
$lang['comptes_label_totals_balance'] = "Totaal balans";
$lang['comptes_label_balance'] = "Balans";
$lang['comptes_label_soldes'] = "Saldi";
$lang['comptes_label_date'] = "Datum";
$lang['comptes_label_class'] = "klasse";
$lang['comptes_label_to'] = "tot en met";
$lang['comptes_label_expenses'] = "Uitgaves";
$lang['comptes_label_earnings'] = "Inkomsten";
$lang['comptes_label_total_incomes'] = "Totaal inkomsten";
$lang['comptes_label_total_expenses'] = "Totaal uitgaves";
$lang['comptes_label_total_pertes'] = "Totaal verliezen";
$lang['comptes_label_total_benefices'] = "Totaal winsten";

$lang['comptes_warning'] = "Het is niet mogelijk rekeningen te verwijderen waarop reeds verwerkingen zijn gebeurd.";
$lang['comptes_confirm_delete_account'] = "Weet u zeker dat u de rekening wilt verwijderen";

$lang['comptes_bilan_actif'] = "Actief";
$lang['comptes_bilan_valeur_brute'] = "Brutto waarde";
$lang['comptes_bilan_amortissement'] = "Afschrijvingen";
$lang['comptes_bilan_valeur_nette'] = "Netto waarde";
$lang['comptes_bilan_passif'] = "Passief";
$lang['comptes_bilan_immobilise'] = "Vastgoed";
$lang['comptes_bilan_fonds_propres'] = "Kapitaal";
$lang['comptes_bilan_immobilisations_corp'] = "Onroerende goederen en bedrijfsmiddelen";
$lang['comptes_bilan_fonds_associatifs'] = "Kapitaal";
$lang['comptes_bilan_report_debt'] = "Rapport debiteuren";
$lang['comptes_bilan_report_cred'] = "Rapport crediteuren";
$lang['comptes_bilan_dispo'] = "Beschikbaar";
$lang['comptes_bilan_dettes_court_terme'] = "Schulden korte termijn";
$lang['comptes_bilan_creances_tiers'] = "Gegevens derden";
$lang['comptes_bilan_dettes_tiers'] = "Schulden aan derden";
$lang['comptes_bilan_dettes_banques'] = "Banklening";
$lang['comptes_bilan_comptes_financiers'] = "Financiële rekeningen";
$lang['comptes_bilan_total'] = "Totaal";
$lang['comptes_bilan_total_actif'] = "Totaal activa";
$lang['comptes_bilan_total_passif'] = "Totaal passiva";
$lang['comptes_bilan_resultat'] = "Resultaat";
$lang['comptes_bilan_resultat_avant_repartition'] = "Resultaat voor verdeling";

$lang['comptes_button_cloture'] = "Sluiting boekjaar";

$lang['comptes_table_header'] = array(
	'N°',
	'Rekening',
	'Nb',
	'Debet',
	'Credit',
	'Saldo debiteur',
	'Saldo crediteur',
	''
);

$lang['comptes_list_header'] = array(
	'Code',
	'Naam',
	'Debet',
	'Credit',
	'Saldo debiteur',
	'Saldo crediteur'
);

$lang['comptes_cloture'] = "Sluiting boekjaar";
$lang['comptes_cloture_impossible'] = "Sluiting niet mogelijk, de bewerkingen zijn geblokkeer op";
$lang['comptes_cloture_reintegration_resultat'] = "Resultaat boekjaar opnieuw integreren";
$lang['comptes_cloture_raz_charges'] = "Rekeningen uitgaves terug op 0 zetten";
$lang['comptes_cloture_raz_produits'] = "Rekeningen producten terug op 0 zetten";
$lang['comptes_cloture_date_fin'] = "Datum einde boekjaar";
$lang['comptes_cloture_date_gel'] = "Datum blokkering boekingen";
$lang['comptes_cloture_title_result'] = "Rekening ter verwerking van de resultaten en verschillen van het vorige boekjaar";
$lang['comptes_cloture_title_previous'] = "Rekeningen van het voorgaande boekjaar te verwerken in het kapitaal of geassocieerde fondsen";
$lang['comptes_cloture_title_charges_a_integrer'] = "Rekeningen kosten te verwerken in het resultaat";
$lang['comptes_cloture_title_produits_a_integrer'] = "Rekeningen producten te verwerken in het resultaat";

$lang['comptes_cloture_error_120'] = "Sluiting niet mogelijk omdat er geen rekening 120 is";
$lang['comptes_cloture_error_129'] = "Sluiting niet mogelijk omdat er geen rekening 129 is";
$lang['comptes_cloture_success'] = "Sluiting succesvol voltooid op";

$lang['comptes_balance_general'] = array('Gedetailleerd' => 0, 'Algemeen' => 1);

// Masked accounts filter
$lang['gvv_comptes_filter_masked'] = array(
    0 => 'Alle rekeningen',
    1 => 'Niet-verborgen rekeningen',
    2 => 'Alleen verborgen rekeningen'
);

// Masked accounts
$lang['gvv_comptes_comment_masked'] = "Aanvinken om de rekening te verbergen in selectoren en rapporten (alleen mogelijk als saldo 0 is)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Kan geen rekening verbergen met een saldo dat niet nul is. Huidig saldo: %s €";
$lang['gvv_comptes_field_masked'] = "Verborgen";
$lang['gvv_comptes_masked_warning'] = "Waarschuwing: een rekening met een saldo van %s € kan niet worden verborgen.";
$lang['gvv_comptes_can_mask'] = "Saldo is 0, rekening kan worden verborgen.";

$lang['gvv_comptes_title_dashboard'] = "Dashboard";
$lang['comptes_bilan_prets'] = "Leningen";

// Hierarchical balance
$lang['gvv_comptes_title_hierarchical_balance'] = "Hiërarchische balans";
$lang['gvv_comptes_expand_all'] = "Alles uitvouwen";
$lang['gvv_comptes_collapse_all'] = "Alles inklappen";

// Error messages
$lang['gvv_comptes_error_account_not_found'] = "De rekening bestaat niet of behoort niet tot deze sectie";

// Resultaat per secties
$lang['gvv_comptes_title_resultat_par_sections'] = "Bedrijfsresultaat per secties";
$lang['gvv_comptes_title_resultat_par_sections_detail'] = "Resultaatdetails per secties - %s";
$lang['comptes_label_charges'] = "Kosten";
$lang['comptes_label_produits'] = "Inkomsten";
