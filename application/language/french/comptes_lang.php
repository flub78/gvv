<?php
/*
 * GVV French translation
*/

$lang['gvv_comptes_title'] = "Compte";
$lang['gvv_comptes_title_balance'] = "Balance générale des comptes";
$lang['gvv_comptes_title_detailed_balance'] = "Balance détaillée des comptes";
$lang['gvv_comptes_title_bilan'] = "Bilan de fin d'exercice";
$lang['gvv_comptes_title_resultat'] = "Résultat d'exploitation de l'exercice";
$lang['gvv_comptes_title_cloture'] = "Clôture de l'exercice comptable";
$lang['gvv_comptes_title_cash'] = "Trésorerie";
$lang['gvv_comptes_title_journaux'] = "Journaux des comptes";
$lang['gvv_comptes_title_financial'] = "Rapport financier pour l'exercice";
$lang['gvv_comptes_title_sales'] = "Ventes et facturation";
$lang['gvv_comptes_title_dashboard'] = "Tableau de bord";


$lang['gvv_comptes_field_nom'] = "Nom du compte";
$lang['gvv_comptes_field_codec'] = "Code comptable";
$lang['gvv_comptes_field_desc'] = "Description";
$lang['gvv_comptes_field_debit'] = "Débit";
$lang['gvv_comptes_field_credit'] = "Crédit";
$lang['gvv_comptes_field_saisie_par'] = "Créateur";
$lang['gvv_comptes_field_pilote'] = "Référence du pilote";

$lang['gvv_vue_comptes_short_field_codec'] = "Code";
$lang['gvv_vue_comptes_short_field_id'] = "Compte";
$lang['gvv_vue_comptes_short_field_solde_debit'] = "Solde débiteur";
$lang['gvv_vue_comptes_short_field_solde_credit'] = "Solde créditeur";
$lang['gvv_vue_comptes_short_field_nom'] = "Compte";
$lang['gvv_vue_comptes_short_field_section_name'] = "Section";

$lang['comptes_filter_active_select'] = array(0 => 'Tous', 1 => 'Débiteurs', 2 => 'Non nuls', 3 => 'Créditeurs', 4 => 'Solde à zéro');

$lang['comptes_label_totals'] = "Totaux";
$lang['comptes_label_total'] = "Total";
$lang['comptes_label_totals_balance'] = "Totaux solde";
$lang['comptes_label_balance'] = "Balance";
$lang['comptes_label_soldes'] = "Soldes";
$lang['comptes_label_date'] = "Date";
$lang['comptes_label_class'] = "Classe";
$lang['comptes_label_to'] = "jusqus'à";
$lang['comptes_label_expenses'] = "Charges";
$lang['comptes_label_earnings'] = "Produits";
$lang['comptes_label_total_incomes'] = "Total produits";
$lang['comptes_label_total_expenses'] = "Total charges";
$lang['comptes_label_total_pertes'] = "Pertes";
$lang['comptes_label_total_benefices'] = "Profits";

$lang['comptes_warning'] = "Il n'est pas possible de supprimer des comptes sur lesquels des écritures ont été passées";

$lang['comptes_bilan_actif'] = "Actif";
$lang['comptes_bilan_valeur_brute'] = "Valeur brute";
$lang['comptes_bilan_amortissement'] = "Amortissements";
$lang['comptes_bilan_valeur_nette'] = "Valeur nette";
$lang['comptes_bilan_passif'] = "Passif";
$lang['comptes_bilan_immobilise'] = "Immobilisé";
$lang['comptes_bilan_fonds_propres'] = "Fonds associatifs propres";
$lang['comptes_bilan_immobilisations_corp'] = "Immobilisations corporelles";
$lang['comptes_bilan_fonds_associatifs'] = "Fonds associatifs";
$lang['comptes_bilan_report_debt'] = "Reports à nouveau débiteurs";
$lang['comptes_bilan_report_cred'] = "Reports à nouveau créditeurs";
$lang['comptes_bilan_dispo'] = "Actifs financiers";
$lang['comptes_bilan_dettes_court_terme'] = "Dettes à court terme";
$lang['comptes_bilan_creances_tiers'] = "Créances de tiers";
$lang['comptes_bilan_dettes_tiers'] = "Dettes envers des tiers";
$lang['comptes_bilan_dettes_banques'] = "Emprunts bancaires";
$lang['comptes_bilan_comptes_financiers'] = "Comptes financiers";
$lang['comptes_bilan_total'] = "Total";
$lang['comptes_bilan_total_actif'] = "Total actif";
$lang['comptes_bilan_total_passif'] = "Total passif";
$lang['comptes_bilan_resultat'] = "Résultat";
$lang['comptes_bilan_resultat_avant_repartition'] = "Résultat avant répartition";
$lang['comptes_bilan_prets'] = "Prêts";


$lang['comptes_button_cloture'] = "Clôture";

$lang['comptes_table_header'] = array(
	'N°',
	'Compte',
	'Nb',
	'Débit',
	'Crédit',
	'Solde débiteur',
	'Solde Créditeur',
	''
);

$lang['comptes_list_header'] = array(
	'Codec',
	'Nom',
	'Debit',
	'Credit',
	'Solde débiteur',
	'Solde créditeur'
);

$lang['comptes_cloture'] = "Clôture de l'exercice";
$lang['comptes_cloture_impossible'] = "Clôture impossible, les opérations sont gelées au";
$lang['comptes_cloture_reintegration_resultat'] = "réintégration du résultat de l'exercice";
$lang['comptes_cloture_raz_charges'] = "remise à 0 des comptes de charges";
$lang['comptes_cloture_raz_produits'] = "remise à 0 des comptes de produits";
$lang['comptes_cloture_date_fin'] = "Date de fin d'exercice";
$lang['comptes_cloture_date_gel'] = "Date de gel des écritures";
$lang['comptes_cloture_title_result'] = "Compte utilisé pour intégrer les résultats et écarts de l'exercice précédant";
$lang['comptes_cloture_title_previous'] = "Comptes de l'exercice précédant à réintégrer dans le capital ou les fonds associatifs";
$lang['comptes_cloture_title_charges_a_integrer'] = "Comptes de charges à intégrer dans le résultat";
$lang['comptes_cloture_title_produits_a_integrer'] = "Comptes de produits à intégrer dans le résultat";

$lang['comptes_cloture_error_120'] = "Clôture impossible car il n'y a pas de compte 120";
$lang['comptes_cloture_error_129'] = "Clôture impossible car il n'y a pas de compte 129";

$lang['comptes_balance_general'] = array('Détaillée' => 0, 'Générale' => 1);

// Masked accounts filter
$lang['gvv_comptes_filter_masked'] = array(
    0 => 'Tous les comptes',
    1 => 'Comptes non masqués',
    2 => 'Comptes masqués uniquement'
);

// Masked accounts
$lang['gvv_comptes_comment_masked'] = "Cocher pour masquer le compte des sélecteurs et rapports (uniquement possible si le solde est à 0)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Impossible de masquer un compte dont le solde n'est pas nul. Solde actuel : %s €";
$lang['gvv_comptes_field_masked'] = "Masqué";
$lang['gvv_comptes_masked_warning'] = "Attention : un compte avec un solde de %s € ne peut pas être masqué.";
$lang['gvv_comptes_can_mask'] = "Le solde est à 0, le compte peut être masqué.";

// Hierarchical balance
$lang['gvv_comptes_title_hierarchical_balance'] = "Balance des comptes";
$lang['gvv_comptes_expand_all'] = "Tout développer";
$lang['gvv_comptes_collapse_all'] = "Tout réduire";
