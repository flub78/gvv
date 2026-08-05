<?php
/**
 * Dutch language file for the Maintenance (Aircraft Maintenance) module
 */

// General
$lang['maintenance_acces_refuse'] = 'Toegang voorbehouden aan monteurs en beheerders.';
$lang['maintenance_btn_retour'] = 'Terug';
$lang['maintenance_btn_annuler'] = 'Annuleren';
$lang['maintenance_btn_creer'] = 'Aanmaken';
$lang['maintenance_btn_enregistrer'] = 'Opslaan';

// Equipements - Lijst
$lang['maintenance_equipements_title'] = 'Onderhoudbare uitrusting';
$lang['maintenance_equipements_create'] = 'Nieuwe uitrusting';
$lang['maintenance_equipements_edit'] = 'Bewerken';
$lang['maintenance_equipements_transfer'] = 'Overdragen';
$lang['maintenance_equipements_deactivate'] = 'Deactiveren';
$lang['maintenance_equipements_reactivate'] = 'Reactiveren';
$lang['maintenance_equipements_aucun'] = 'Nog geen uitrusting gedefinieerd.';

// Equipements - Velden
$lang['maintenance_equipement_nom'] = 'Naam';
$lang['maintenance_equipement_aeronef'] = 'Luchtvaartuig';
$lang['maintenance_equipement_aeronef_help_edit'] = 'Het gekoppelde luchtvaartuig kan hier niet worden gewijzigd.';
$lang['maintenance_equipement_description'] = 'Beschrijving';
$lang['maintenance_equipement_actif'] = 'Actief';
$lang['maintenance_equipement_inactif'] = 'Inactief';
$lang['maintenance_equipement_deactivate_confirm'] = 'Deze uitrusting deactiveren?';

// Equipements - Bevestigingsberichten
$lang['maintenance_equipement_created'] = 'Uitrusting succesvol aangemaakt.';
$lang['maintenance_equipement_updated'] = 'Uitrusting succesvol bijgewerkt.';
$lang['maintenance_equipement_deactivated'] = 'Uitrusting gedeactiveerd.';
$lang['maintenance_equipement_reactivated'] = 'Uitrusting gereactiveerd.';
$lang['maintenance_equipement_transferred'] = 'Uitrusting %s overgedragen naar luchtvaartuig %s.';

// Overdracht van uitrusting
$lang['maintenance_transfert_info'] = 'U staat op het punt uitrusting %s, momenteel gekoppeld aan luchtvaartuig %s, over te dragen naar een ander luchtvaartuig. De geschiedenis (dossiers, operaties) blijft bewaard.';
$lang['maintenance_transfert_nouvel_aeronef'] = 'Nieuw luchtvaartuig';
$lang['maintenance_transfert_confirmation'] = 'Ik bevestig de overdracht van deze uitrusting naar het geselecteerde luchtvaartuig.';

// Programma's - Lijst
$lang['maintenance_programmes_title'] = 'Onderhoudsprogramma\'s';
$lang['maintenance_programmes_create'] = 'Nieuw programma';
$lang['maintenance_programmes_view'] = 'Bekijken';
$lang['maintenance_programmes_edit'] = 'Bewerken';
$lang['maintenance_programmes_deactivate'] = 'Archiveren';
$lang['maintenance_programmes_aucun'] = 'Nog geen onderhoudsprogramma gedefinieerd.';

// Programma's - Velden
$lang['maintenance_programme_code'] = 'Code';
$lang['maintenance_programme_code_deja_utilise'] = 'Deze code wordt al gebruikt door een ander programma.';
$lang['maintenance_programme_titre'] = 'Titel';
$lang['maintenance_programme_titre_help'] = 'Moet exact overeenkomen met de H1-titel van het geuploade markdown-bestand.';
$lang['maintenance_programme_section'] = 'Sectie';
$lang['maintenance_programme_section_globale'] = 'Alle secties';
$lang['maintenance_programme_regle'] = 'Vervalregel';
$lang['maintenance_programme_regle_butee'] = 'Vervalregel';
$lang['maintenance_programme_regle_date'] = 'Kalenderdatum vervaldatum';
$lang['maintenance_programme_regle_heures'] = 'Vlieguren vervaldatum';
$lang['maintenance_programme_periodicite_mois'] = 'Periodiciteit (maanden)';
$lang['maintenance_programme_seuil_heures'] = 'Drempel (vlieguren)';
$lang['maintenance_programme_mois'] = 'maanden';
$lang['maintenance_programme_aucune_regle'] = 'Geen vervalregel gedefinieerd.';
$lang['maintenance_programme_regle_date_resume'] = 'Vervalt elke %s maanden';
$lang['maintenance_programme_regle_heures_resume'] = 'Vervalt elke %s vlieguren';
$lang['maintenance_programme_structure'] = 'Structuur';
$lang['maintenance_programme_nb_structure'] = '%d sectie(s), %d taak/taken';
$lang['maintenance_programme_aucune_structure'] = 'Nog geen structuur geïmporteerd. Upload een markdown-bestand om deze te genereren.';
$lang['maintenance_programme_section_vide'] = 'Geen taak in deze sectie.';

// Programma's - Gekoppeld document
$lang['maintenance_programme_document'] = 'Brondocument';
$lang['maintenance_programme_uploader'] = 'Versie uploaden';
$lang['maintenance_programme_document_depose_le'] = 'Geüpload op %s';
$lang['maintenance_programme_aucun_document'] = 'Nog geen document geüpload.';
$lang['maintenance_programme_fichier'] = 'Markdown-bestand';
$lang['maintenance_programme_fichier_help'] = 'Formaat: # Programmatitel, ## Sectie, ### Taak (extensie .md of .txt).';
$lang['maintenance_programme_upload_info_premiere_version'] = 'Deze eerste versie bepaalt de structuur van het programma (secties en taken).';
$lang['maintenance_programme_upload_info_nouvelle_version'] = 'Deze nieuwe versie vervangt de vorige. Taken die al gebruikt zijn in een onderhoudsoperatie blijven bewaard (gedeactiveerd als ze uit het bestand verdwijnen).';

// Programma's - Bevestigings- / foutmeldingen
$lang['maintenance_programme_created'] = 'Programma succesvol aangemaakt. Upload nu het markdown-bestand.';
$lang['maintenance_programme_updated'] = 'Programma succesvol bijgewerkt.';
$lang['maintenance_programme_deactivated'] = 'Programma gearchiveerd.';
$lang['maintenance_programme_reactivated'] = 'Programma gereactiveerd.';
$lang['maintenance_programme_deactivate_confirm'] = 'Dit programma archiveren?';
$lang['maintenance_programme_upload_error'] = 'Er is geen geldig bestand geüpload.';
$lang['maintenance_programme_upload_extension'] = 'Alleen .md- en .txt-bestanden zijn toegestaan.';
$lang['maintenance_programme_validation_error'] = 'Validatiefouten in het markdown-bestand:';
$lang['maintenance_programme_parse_error'] = 'Fout bij het analyseren van het markdown-bestand:';
$lang['maintenance_programme_storage_error'] = 'Kan het bestand niet op de server opslaan.';
$lang['maintenance_programme_sync_error'] = 'Het document is gearchiveerd maar het bijwerken van de structuur is mislukt:';
$lang['maintenance_programme_uploaded'] = 'Bestand geüpload en structuur succesvol bijgewerkt.';

// Dossiers - Lijst
$lang['maintenance_dossiers_title'] = 'Onderhoudsdossiers';
$lang['maintenance_dossiers_aucun'] = 'Geen onderhoudsdossier.';
$lang['maintenance_dossier_type_aeronef'] = 'Luchtvaartuig';
$lang['maintenance_dossier_type_equipement'] = 'Uitrusting';
$lang['maintenance_dossier_ouvrir_aeronef'] = 'Openen (luchtvaartuig)';
$lang['maintenance_dossier_ouvrir_equipement'] = 'Openen (uitrusting)';
$lang['maintenance_dossier_ouvrir_btn'] = 'Dossier openen';

// Dossiers - Velden
$lang['maintenance_dossier_entite'] = 'Entiteit';
$lang['maintenance_dossier_entite_aeronef'] = 'Luchtvaartuig';
$lang['maintenance_dossier_entite_equipement'] = 'Uitrusting';
$lang['maintenance_dossier_entite_invalide'] = 'De geselecteerde entiteit is niet gevonden.';
$lang['maintenance_dossier_programme'] = 'Onderhoudsprogramma';
$lang['maintenance_dossier_date_ouverture'] = 'Openingsdatum';
$lang['maintenance_dossier_date_suspension'] = 'Opschortingsdatum';
$lang['maintenance_dossier_date_cloture'] = 'Sluitingsdatum';
$lang['maintenance_dossier_mecano'] = 'Verantwoordelijke monteur';

// Dossiers - Statussen
$lang['maintenance_dossier_statut_ouvert'] = 'Open';
$lang['maintenance_dossier_statut_suspendu'] = 'Opgeschort';
$lang['maintenance_dossier_statut_cloture'] = 'Gesloten';
$lang['maintenance_dossier_statut_abandonne'] = 'Verlaten';

// Dossiers - Acties
$lang['maintenance_dossier_suspendre'] = 'Opschorten';
$lang['maintenance_dossier_reactiver'] = 'Reactiveren';
$lang['maintenance_dossier_cloturer'] = 'Sluiten';
$lang['maintenance_dossier_abandonner'] = 'Verlaten';
$lang['maintenance_dossier_termine'] = 'Dossier afgerond.';
$lang['maintenance_dossier_suspend_confirm'] = 'Dit dossier opschorten?';
$lang['maintenance_dossier_close_confirm'] = 'Dit dossier sluiten?';
$lang['maintenance_dossier_abandon_confirm'] = 'Dit dossier verlaten? Deze actie is moeilijk te herstellen.';

// Dossiers - Operaties
$lang['maintenance_dossier_operations'] = 'Geregistreerde operaties';
$lang['maintenance_dossier_aucune_operation'] = 'Geen operatie geregistreerd voor dit dossier.';

// Dossiers - Bevestigingsberichten
$lang['maintenance_dossier_ouvert'] = 'Dossier succesvol geopend.';
$lang['maintenance_dossier_suspendu'] = 'Dossier opgeschort.';
$lang['maintenance_dossier_reactive'] = 'Dossier gereactiveerd.';
$lang['maintenance_dossier_cloture'] = 'Dossier gesloten.';
$lang['maintenance_dossier_abandonne'] = 'Dossier verlaten.';

// Operaties - Formulier
$lang['maintenance_operation_create'] = 'Nieuwe operatie';
$lang['maintenance_operation_edit'] = 'Operatie bewerken';
$lang['maintenance_operation_date'] = 'Datum van de operatie';
$lang['maintenance_operation_horametre'] = 'Afgelezen urenteller';
$lang['maintenance_operation_horametre_help'] = 'Het resterende potentieel herstart op %s uur vanaf deze waarde.';
$lang['maintenance_operation_nouvelle_echeance'] = 'Nieuwe vervaldatum (optioneel)';
$lang['maintenance_operation_echeance_help'] = 'Laat leeg om automatisch te berekenen (+%s maanden vanaf de operatiedatum).';
$lang['maintenance_operation_commentaire'] = 'Algemene opmerking';
$lang['maintenance_operation_compte_rendu'] = 'Papieren rapport';
$lang['maintenance_operation_compte_rendu_help'] = 'Scan of foto van het door de werkplaats ondertekende rapport (PDF of afbeelding), optioneel.';
$lang['maintenance_operation_document_existant'] = 'Rapport al geüpload';
$lang['maintenance_operation_taches'] = 'Programmataken';
$lang['maintenance_operation_aucune_tache'] = 'Dit programma bevat geen taak.';
$lang['maintenance_operation_enregistrer'] = 'Operatie opslaan';
$lang['maintenance_operation_created'] = 'Operatie succesvol geregistreerd. Potentieel bijgewerkt.';
$lang['maintenance_operation_updated'] = 'Operatie succesvol bijgewerkt. Potentieel herberekend.';

// Realisaties
$lang['maintenance_realisation_fait'] = 'Gedaan';
$lang['maintenance_realisation_non_fait'] = 'Niet gedaan';
$lang['maintenance_realisation_non_applicable'] = 'N.v.t.';

// Service bulletins
$lang['maintenance_bulletins_title'] = 'Service bulletins';
$lang['maintenance_bulletins_deposer'] = 'Bulletin uploaden';
$lang['maintenance_bulletins_selectionner_aeronef'] = 'Selecteer een luchtvaartuig om de service bulletins te bekijken.';
$lang['maintenance_bulletins_aucun'] = 'Geen service bulletin voor dit luchtvaartuig.';
$lang['maintenance_bulletin_fichier'] = 'Bestand';
$lang['maintenance_bulletin_depose_le'] = 'Geüpload op';
$lang['maintenance_bulletin_statut'] = 'Status';
$lang['maintenance_bulletin_statut_a_traiter'] = 'Te verwerken';
$lang['maintenance_bulletin_statut_traite'] = 'Verwerkt';
$lang['maintenance_bulletin_statut_non_applicable'] = 'Niet van toepassing';
$lang['maintenance_bulletin_upload_error'] = 'Er is geen geldig bestand geüpload.';
$lang['maintenance_bulletin_uploaded'] = 'Bulletin succesvol geüpload.';
$lang['maintenance_bulletin_statut_invalide'] = 'Ongeldige status.';
$lang['maintenance_bulletin_statut_mis_a_jour'] = 'Bulletinstatus bijgewerkt.';

// Luchtwaardigheidsoverzicht
$lang['maintenance_synthese_titre'] = 'Luchtwaardigheidsoverzicht';
$lang['maintenance_synthese_titre_aeronef'] = 'Luchtwaardigheidsoverzicht';
$lang['maintenance_synthese_aeronef'] = 'Luchtvaartuig';
$lang['maintenance_synthese_etat_global'] = 'Algemene status';
$lang['maintenance_synthese_filtrer'] = 'Filteren';
$lang['maintenance_synthese_aucun_aeronef'] = 'Geen luchtvaartuig voor deze sectie.';
$lang['maintenance_synthese_entite'] = 'Entiteit';
$lang['maintenance_synthese_programme'] = 'Programma';
$lang['maintenance_synthese_aucun_dossier'] = 'Geen open dossier.';
$lang['maintenance_synthese_genere_le'] = 'Gegenereerd op';
$lang['maintenance_synthese_export_pdf'] = 'PDF-export';
$lang['maintenance_synthese_echeance'] = 'Vervaldatum';
$lang['maintenance_synthese_potentiel'] = 'Resterend potentieel';

// Luchtwaardigheidsstatussen (gedeeld tussen vlootweergave, luchtvaartuigweergave, PDF)
$lang['maintenance_etat_a_jour'] = 'Actueel';
$lang['maintenance_etat_echeance_proche'] = 'Binnenkort vervallen';
$lang['maintenance_etat_depasse'] = 'Vervallen';

// Onderhoudsdashboard
$lang['maintenance_dashboard_title'] = 'Onderhoud';
$lang['maintenance_operations_title'] = 'Onderhoudsoperaties';
$lang['maintenance_operations_aucune'] = 'Geen operatie geregistreerd.';
