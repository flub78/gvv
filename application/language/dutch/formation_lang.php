<?php
/**
 * Dutch language file for Formation (Training) Management
 */

// General
$lang['formation_feature_disabled'] = 'De opleidingsbeheer functie is niet ingeschakeld.';

// Programmes - General
$lang['formation_programmes_title'] = 'Opleidingsprogramma\'s';
$lang['formation_programme_titre'] = 'Titel';
$lang['formation_programme_description'] = 'Beschrijving';
$lang['formation_programme_objectifs'] = 'Doelstellingen';
$lang['formation_programme_section'] = 'Sectie';
$lang['formation_programme_version'] = 'Versie';
$lang['formation_programme_actif'] = 'Actief';
$lang['formation_programme_date_creation'] = 'Aangemaakt op';
$lang['formation_programme_date_modification'] = 'Laatst gewijzigd';
$lang['formation_programme_nb_lecons'] = 'Aantal lessen';
$lang['formation_programme_nb_sujets'] = 'Aantal onderwerpen';

// Programmes - Actions
$lang['formation_programmes_create'] = 'Nieuw Programma';
$lang['formation_programmes_edit'] = 'Programma Bewerken';
$lang['formation_programmes_view'] = 'Programma Details';
$lang['formation_programmes_delete'] = 'Verwijderen';
$lang['formation_programmes_delete_confirm'] = 'Weet u zeker dat u het programma {name} wilt verwijderen?';
$lang['formation_programmes_export'] = 'Exporteren als Markdown';
$lang['formation_programmes_import'] = 'Importeren vanuit Markdown';
$lang['formation_programmes_back'] = 'Terug naar Programma\'s';

// Programmes - Messages
$lang['formation_programmes_no_programmes'] = 'Geen opleidingsprogramma\'s gedefinieerd.';
$lang['formation_programme_create_success'] = 'Programma succesvol aangemaakt.';
$lang['formation_programme_create_error'] = 'Fout bij het aanmaken van programma.';
$lang['formation_programme_update_success'] = 'Programma succesvol bijgewerkt.';
$lang['formation_programme_update_error'] = 'Fout bij het bijwerken van programma.';
$lang['formation_programme_delete_success'] = 'Programma succesvol verwijderd.';
$lang['formation_programme_delete_error'] = 'Fout bij het verwijderen van programma.';
$lang['formation_programme_delete_error_used'] = 'Dit programma kan niet worden verwijderd omdat het wordt gebruikt in actieve inschrijvingen.';
$lang['formation_programme_update_structure_blocked'] = 'De structuur van dit programma kan niet worden gewijzigd omdat er %d inschrijving(en) aan gekoppeld zijn. Om de structuur te wijzigen, maak een nieuw afgeleid programma (kopie) en archiveer het oude zodra de inschrijvingen zijn voltooid.';

// Import/Export
$lang['formation_import_file'] = 'Markdown Bestand';
$lang['formation_import_file_help'] = 'Selecteer een .md bestand met het opleidingsprogramma';
$lang['formation_import_manual'] = 'Handmatig aanmaken';
$lang['formation_import_from_markdown'] = 'Importeren vanuit Markdown';
$lang['formation_import_success'] = 'Programma succesvol geïmporteerd vanuit Markdown bestand.';
$lang['formation_import_error_upload'] = 'Fout bij het uploaden van bestand.';
$lang['formation_import_error_empty'] = 'Bestand is leeg of onleesbaar.';
$lang['formation_import_error_invalid'] = 'Ongeldige Markdown structuur';
$lang['formation_import_error_parse'] = 'Fout bij het parsen van Markdown bestand';
$lang['formation_import_error_db'] = 'Fout bij het aanmaken van programma in database.';
$lang['formation_import_error_lecon'] = 'Fout bij het aanmaken van een les.';
$lang['formation_import_error_sujet'] = 'Fout bij het aanmaken van een onderwerp.';
$lang['formation_import_error_transaction'] = 'Database transactie fout.';
$lang['formation_export_markdown'] = 'Exporteren als Markdown';
$lang['formation_export_pdf'] = 'Exporteren als PDF';

// Leçons
$lang['formation_lecon'] = 'Les';
$lang['formation_lecons'] = 'Lessen';
$lang['formation_lecon_numero'] = 'Nummer';
$lang['formation_lecon_titre'] = 'Les Titel';
$lang['formation_lecon_description'] = 'Beschrijving';
$lang['formation_lecon_objectifs'] = 'Doelstellingen';
$lang['formation_lecon_ordre'] = 'Volgorde';

// Sujets
$lang['formation_sujet'] = 'Onderwerp';
$lang['formation_sujets'] = 'Onderwerpen';
$lang['formation_sujet_numero'] = 'Nummer';
$lang['formation_sujet_titre'] = 'Onderwerp Titel';
$lang['formation_sujet_description'] = 'Beschrijving';
$lang['formation_sujet_objectifs'] = 'Doelstellingen';
$lang['formation_sujet_ordre'] = 'Volgorde';

// Inscriptions
$lang['formation_inscription'] = 'Inschrijving';
$lang['formation_inscriptions'] = 'Inschrijvingen';
$lang['formation_inscription_pilote'] = 'Piloot';
$lang['formation_inscription_programme'] = 'Programma';
$lang['formation_inscription_instructeur'] = 'Instructeur';
$lang['formation_inscription_date_debut'] = 'Startdatum';
$lang['formation_inscription_date_fin'] = 'Einddatum';
$lang['formation_inscription_statut'] = 'Status';
$lang['formation_inscription_statut_ouverte'] = 'Open';
$lang['formation_inscription_statut_suspendue'] = 'Opgeschort';
$lang['formation_inscription_statut_terminee'] = 'Voltooid';
$lang['formation_inscription_statut_abandonnee'] = 'Gestopt';
$lang['formation_inscription_resultat'] = 'Resultaat';
$lang['formation_inscription_commentaire'] = 'Opmerking';

// Séances
$lang['formation_seance'] = 'Sessie';
$lang['formation_seances'] = 'Sessies';
$lang['formation_seance_date'] = 'Datum';
$lang['formation_seance_duree'] = 'Duur (minuten)';
$lang['formation_seance_duree_cours'] = 'Duur';
$lang['formation_seance_pilote'] = 'Piloot';
$lang['formation_seance_instructeur'] = 'Instructeur';
$lang['formation_seance_inscription'] = 'Inschrijving';
$lang['formation_seance_libre'] = 'Vrije sessie (geen inschrijving)';
$lang['formation_seance_meteo'] = 'Weersomstandigheden';
$lang['formation_seance_commentaire'] = 'Opmerking';
$lang['formation_seance_programme'] = 'Programma';
$lang['formation_seance_aucun_programme'] = 'Geen programma';
$lang['formation_seance_type_formation_label'] = 'Opleidingssessie';
$lang['formation_seance_type_libre_label'] = 'Herhalingssessie voor gebrevetteerde piloot';
$lang['formation_seances_libres_title'] = 'Herhalingssessies';
$lang['formation_seance_precedente'] = 'Vorige sessie';
$lang['formation_seance_categorie'] = 'Categorie';
$lang['formation_seance_categorie_aucune'] = 'Geen categorie';
$lang['formation_seance_categorie_toutes'] = 'Alle categorieën';

// Évaluations
$lang['formation_evaluation'] = 'Beoordeling';
$lang['formation_evaluations'] = 'Beoordelingen';
$lang['formation_evaluation_sujet'] = 'Onderwerp';
$lang['formation_evaluation_niveau'] = 'Niveau';
$lang['formation_evaluation_niveau_non_vu'] = 'Niet behandeld';
$lang['formation_evaluation_niveau_debutant'] = 'Beginner';
$lang['formation_evaluation_niveau_progresse'] = 'In ontwikkeling';
$lang['formation_evaluation_niveau_acquis'] = 'Verworven';
$lang['formation_evaluation_niveau_maitrise'] = 'Beheerst';
$lang['formation_evaluation_commentaire'] = 'Opmerking';

// Rapporten
$lang['formation_rapports_title'] = 'Opleidingsrapporten';
$lang['formation_rapports_cloturees_succes'] = 'Succesvol afgeronde opleidingen';
$lang['formation_rapports_abandonnees'] = 'Afgebroken opleidingen';
$lang['formation_rapports_suspendues'] = 'Opgeschorte opleidingen';
// Mijn opleidingen (studentenweergave)
$lang['formation_mes_formations_title'] = 'Mijn opleidingen';
$lang['formation_mes_formations_empty'] = 'U bent momenteel niet ingeschreven voor een opleiding.';
$lang['formation_mes_formations_info'] = 'Bekijk uw opleidingen en voortgang.';
$lang['formation_voir_ma_progression'] = 'Mijn voortgang bekijken';
$lang['formation_voir_mes_seances'] = 'Mijn sessies bekijken';$lang['formation_rapports_ouvertes'] = 'Geopende opleidingen';
$lang['formation_rapports_en_cours'] = 'Lopende opleidingen';
$lang['formation_rapports_reentrainement'] = 'Herhalingssessies';
$lang['formation_rapports_par_instructeur'] = 'Per instructeur';
$lang['formation_rapports_par_categorie'] = 'Per sessiecategorie';
$lang['formation_rapports_nb_seances'] = 'Sessies';
$lang['formation_rapports_nb_seances_formation'] = 'Opleidingssessies';
$lang['formation_rapports_nb_seances_libre'] = 'Herhalingssessies';
$lang['formation_rapports_progression'] = 'Voortgang';
$lang['formation_rapports_aucune'] = 'Geen';
$lang['formation_rapports_date_cloture'] = 'Sluitingsdatum';
$lang['formation_rapports_motif'] = 'Reden';
$lang['formation_rapports_date_suspension'] = 'Opschortingsdatum';

// Sessies
$lang['formation_seances_create'] = 'Nieuwe Sessie';
$lang['formation_seances_edit'] = 'Sessie Bewerken';
$lang['formation_seances_detail'] = 'Sessie Details';
$lang['formation_seances_empty'] = 'Geen sessies geregistreerd.';
$lang['formation_seances_back'] = 'Terug naar Sessies';
$lang['formation_seances_back_to_formation'] = 'Terug naar Opleiding';

// Form elements
$lang['formation_form_required'] = 'Verplichte velden';
$lang['formation_form_optional'] = 'Optionele velden';
$lang['formation_form_save'] = 'Opslaan';
$lang['formation_form_cancel'] = 'Annuleren';

// Solovlucht autorisaties
$lang['formation_autorisation_solo'] = 'Solovlucht Autorisatie';
$lang['formation_autorisations_solo'] = 'Solovlucht Autorisaties';
$lang['formation_autorisations_solo_title'] = 'Solovlucht Autorisaties';
$lang['formation_autorisations_solo_list'] = 'Autorisatielijst';
$lang['formation_autorisations_solo_create'] = 'Nieuwe Autorisatie';
$lang['formation_autorisations_solo_edit'] = 'Autorisatie Bewerken';
$lang['formation_autorisations_solo_detail'] = 'Autorisatie Details';
$lang['formation_autorisations_solo_empty'] = 'Geen solovlucht autorisaties geregistreerd.';
$lang['formation_autorisations_solo_back'] = 'Terug naar Autorisaties';

$lang['formation_autorisation_solo_formation'] = 'Opleiding';
$lang['formation_autorisation_solo_eleve'] = 'Leerling';
$lang['formation_autorisation_solo_instructeur'] = 'Instructeur';
$lang['formation_autorisation_solo_date'] = 'Autorisatiedatum';
$lang['formation_autorisation_solo_section'] = 'Sectie/Club';
$lang['formation_autorisation_solo_machine'] = 'Geautoriseerd Vliegtuig';
$lang['formation_autorisation_solo_consignes'] = 'Instructies';
$lang['formation_autorisation_solo_consignes_help'] = 'Instructies moeten minimaal 250 tekens bevatten.';
$lang['formation_autorisation_solo_consignes_minlength'] = 'Instructies moeten minimaal 250 tekens bevatten.';
$lang['formation_autorisation_solo_date_creation'] = 'Aangemaakt op';
$lang['formation_autorisation_solo_date_modification'] = 'Laatst gewijzigd';

$lang['formation_autorisation_solo_created'] = 'Solovlucht autorisatie succesvol aangemaakt.';
$lang['formation_autorisation_solo_updated'] = 'Solovlucht autorisatie succesvol bijgewerkt.';
$lang['formation_autorisation_solo_deleted'] = 'Solovlucht autorisatie succesvol verwijderd.';
$lang['formation_autorisation_solo_create_error'] = 'Fout bij aanmaken autorisatie.';
$lang['formation_autorisation_solo_update_error'] = 'Fout bij bijwerken autorisatie.';
$lang['formation_autorisation_solo_delete_confirm'] = 'Weet u zeker dat u deze solovlucht autorisatie wilt verwijderen?';
$lang['formation_autorisation_solo_delete_confirm_btn'] = 'Bevestig Verwijdering';

$lang['formation_inscription_not_found'] = 'Opleiding niet gevonden.';
$lang['formation_acces_instructeur_requis'] = 'Toegang beperkt tot instructeurs.';
$lang['formation_acces_refuse'] = 'Toegang geweigerd.';

// Sessietypen
$lang['formation_types_seances_title']            = 'Typen opleidingssessies';
$lang['formation_type_seance_nom']                = 'Naam';
$lang['formation_type_seance_nature']             = 'Type';
$lang['formation_type_seance_description']        = 'Beschrijving';
$lang['formation_type_seance_periodicite']        = 'Max. periodiciteit (dagen)';
$lang['formation_type_seance_periodicite_help']   = 'Maximaal aantal dagen tussen twee sessies van dit type voor dezelfde leerling. Leeg laten = geen beperking.';
$lang['formation_type_seance_actif']              = 'Actief';
$lang['formation_nature_vol']                     = 'Vlucht';
$lang['formation_nature_theorique']               = 'Grondcursus';
$lang['formation_types_seances_create']           = 'Nieuw sessietype';
$lang['formation_types_seances_edit']             = 'Type bewerken';
$lang['formation_types_seances_delete']           = 'Verwijderen';
$lang['formation_types_seances_deactivate']       = 'Deactiveren';
$lang['formation_type_seance_created']            = 'Sessietype succesvol aangemaakt.';
$lang['formation_type_seance_updated']            = 'Sessietype succesvol bijgewerkt.';
$lang['formation_type_seance_deleted']            = 'Sessietype verwijderd.';
$lang['formation_type_seance_deactivated']        = 'Sessietype gedeactiveerd.';
$lang['formation_type_seance_in_use']             = 'Dit type wordt gebruikt door bestaande sessies en kan niet worden verwijderd. U kunt het deactiveren.';
$lang['formation_type_seance_no_periodicite']     = 'Geen beperking';
$lang['formation_type_seance_periodicite_jours']  = '%d d';

// Theoretische sessies
$lang['formation_seances_theoriques_title']           = 'Theoretische sessies';
$lang['formation_seance_theorique_create']            = 'Nieuwe theoretische sessie';
$lang['formation_seance_theorique_edit']              = 'Sessie bewerken';
$lang['formation_seance_theorique_detail']            = 'Sessiedetail';
$lang['formation_seances_theoriques_empty']           = 'Geen theoretische sessies geregistreerd.';
$lang['formation_seance_lieu']                        = 'Locatie';
$lang['formation_seance_lieu_placeholder']            = 'Bijv.: Vergaderzaal, Hangar A…';
$lang['formation_seance_participants']                = 'Deelnemers';
$lang['formation_seance_participants_requis']         = 'Voeg minimaal één deelnemer toe.';
$lang['formation_seance_participants_recherche']      = 'Zoek een lid…';
$lang['formation_seance_participants_aucun']          = 'Geen deelnemers.';
$lang['formation_seance_type_invalide']               = 'Het geselecteerde sessietype is niet theoretisch.';
$lang['formation_seance_commentaires']                = 'Opmerkingen';
$lang['formation_seance_theorique_create_success']    = 'Theoretische sessie succesvol aangemaakt.';
$lang['formation_seance_theorique_create_error']      = 'Fout bij het aanmaken van de sessie.';
$lang['formation_seance_theorique_update_success']    = 'Theoretische sessie succesvol bijgewerkt.';
$lang['formation_seance_theorique_update_error']      = 'Fout bij het bijwerken van de sessie.';
$lang['formation_seance_theorique_delete_success']    = 'Theoretische sessie verwijderd.';
$lang['formation_seance_nature']                      = 'Aard';
$lang['formation_seance_nature_vol']                  = 'Vlucht';
$lang['formation_seance_nature_theorique']            = 'Grondles';
$lang['formation_seance_nature_toutes']               = 'Alle';
$lang['formation_seance_nb_participants']             = 'Deelnemers';

// Jaarlijkse geconsolideerde rapporten (Fase 3)
$lang['formation_rapports_annuel_title']           = 'Jaarlijks geconsolideerd rapport';
$lang['formation_rapports_annuel_par_instructeur'] = 'Per instructeur';
$lang['formation_rapports_annuel_par_programme']   = 'Per programma';
$lang['formation_rapports_annuel_nb_seances_vol']  = 'Vluchtsessies';
$lang['formation_rapports_annuel_nb_seances_sol']  = 'Grondsessies';
$lang['formation_rapports_annuel_heures_vol']      = 'Vlieguren';
$lang['formation_rapports_annuel_heures_sol']      = 'Gronduren';
$lang['formation_rapports_annuel_nb_eleves_vol']   = 'Vluchtstudenten';
$lang['formation_rapports_annuel_nb_eleves_sol']   = 'Grondstudenten';
$lang['formation_rapports_annuel_total']           = 'Totaal';
$lang['formation_rapports_annuel_export_csv']      = 'CSV exporteren';
$lang['formation_rapports_annuel_aucun']           = 'Geen gegevens voor dit jaar.';

// Conformiteitsrapport
$lang['formation_rapports_conformite_title']           = 'Conformiteitsrapport';
$lang['formation_rapports_conformite_pilote']          = 'Piloot';
$lang['formation_rapports_conformite_derniere_seance'] = 'Laatste sessie';
$lang['formation_rapports_conformite_jours_ecoules']   = 'Verstreken dagen';
$lang['formation_rapports_conformite_periodicite']     = 'Max. periodiciteit';
$lang['formation_rapports_conformite_jamais']          = 'Nooit';
$lang['formation_rapports_conformite_aucun']           = 'Alle piloten voldoen.';
$lang['formation_rapports_conformite_aucun_type']      = 'Geen sessietype met een periodiciteitsbeperkingbeperking.';
$lang['formation_rapports_conformite_export_csv']      = 'CSV exporteren';
$lang['formation_rapports_conformite_non_conformes']   = 'niet-conforme piloot/piloten';

// Ontbrekende sleutels toegevoegd
$lang['formation_programme_section_help']         = 'Als "Globaal", is het programma zichtbaar in alle secties. Anders alleen in de geselecteerde sectie.';
$lang['formation_programme_type_aeronef']         = 'Type luchtvaartuig';
$lang['formation_programme_type_planeur']         = 'Zweefvliegtuig';
$lang['formation_programme_type_avion']           = 'Vliegtuig';
$lang['formation_structure_markdown']             = 'Programmastructuur';
$lang['formation_inscriptions_title']             = 'Lopende opleidingen';
$lang['formation_inscriptions_ouvrir']            = 'Opleiding openen';
$lang['formation_inscriptions_empty']             = 'Geen opleidingen gevonden.';
$lang['formation_inscriptions_count']             = 'opleiding(en)';
$lang['formation_inscription_date_ouverture']     = 'Openingsdatum';
$lang['formation_inscription_date_suspension']    = 'Schorsingsdatum';
$lang['formation_inscription_date_cloture']       = 'Sluitingsdatum';
$lang['formation_inscription_statut_cloturee']    = 'Gesloten';
$lang['formation_inscription_ouverte']            = 'Opleiding succesvol afgerond';
$lang['formation_inscription_cloturee']           = 'Succesvol gesloten';
$lang['formation_inscription_abandonnee']         = 'Verlaten';
$lang['formation_inscription_detail_title']       = 'Voortgangsblad';
$lang['formation_inscription_suspendre_title']    = 'Opleiding schorsen';
$lang['formation_inscription_suspendre_confirm']  = 'Weet u zeker dat u deze opleiding wilt schorsen?';
$lang['formation_inscription_suspendre_confirm_btn'] = 'Schorsing bevestigen';
$lang['formation_inscription_cloturer_title']     = 'Opleiding sluiten';
$lang['formation_inscription_cloturer_info']      = 'U staat op het punt deze opleiding te sluiten. Kies het sluitingstype.';
$lang['formation_inscription_cloturer_confirm_btn'] = 'Sluiting bevestigen';
$lang['formation_inscription_type_cloture']       = 'Sluitingstype';
$lang['formation_inscription_motif_suspension']   = 'Reden van schorsing';
$lang['formation_inscription_motif_cloture']      = 'Reden van sluiting';
$lang['formation_inscription_motif_required']     = 'De reden is verplicht.';
$lang['formation_inscription_create_success']     = 'Opleiding succesvol aangemaakt.';
$lang['formation_inscription_create_error']       = 'Fout bij het aanmaken van de opleiding.';
$lang['formation_inscription_update_success']     = 'Opleiding succesvol bijgewerkt.';
$lang['formation_inscription_update_error']       = 'Fout bij het bijwerken van de opleiding.';
$lang['formation_inscription_suspend_success']    = 'Opleiding succesvol geschorst.';
$lang['formation_inscription_suspend_error']      = 'Fout bij het schorsen van de opleiding.';
$lang['formation_inscription_reactivate_success'] = 'Opleiding succesvol gereactiveerd.';
$lang['formation_inscription_reactivate_error']   = 'Fout bij het reactiveren van de opleiding.';
$lang['formation_inscription_close_success']      = 'Opleiding succesvol gesloten.';
$lang['formation_inscription_close_error']        = 'Fout bij het sluiten van de opleiding.';
$lang['formation_inscription_already_open']       = 'Deze piloot heeft al een lopende opleiding voor dit programma.';
$lang['formation_inscription_cannot_suspend']     = 'Kan deze opleiding niet schorsen (onjuiste status).';
$lang['formation_inscription_cannot_reactivate']  = 'Kan deze opleiding niet reactiveren (onjuiste status).';
$lang['formation_inscription_cannot_close']       = 'Kan deze opleiding niet sluiten (onjuiste status).';
$lang['formation_inscription_type_required']      = 'Het sluitingstype is verplicht.';
$lang['formation_seances_title']                  = 'Opleidingssessies';
$lang['formation_seance_machine']                 = 'Luchtvaartuig';
$lang['formation_seance_duree_help']              = 'Formaat UU:MM';
$lang['formation_seance_nb_atterrissages']        = 'Aantal landingen';
$lang['formation_seance_prochaines_lecons']       = 'Voorbereiding volgende lessen';
$lang['formation_seance_libre_info']              = 'Deze sessie wordt gearchiveerd maar draagt niet bij aan een voortgangsblad.';
$lang['formation_seance_inscription_info']        = 'Sessie gekoppeld aan opleiding';
$lang['formation_seance_type']                    = 'Type';
$lang['formation_seance_type_formation']          = 'Opleiding';
$lang['formation_seance_type_libre']              = 'Vrij';
$lang['formation_seance_type_toutes']             = 'Alle';
$lang['formation_seance_mode_inscription']        = 'Met opleiding';
$lang['formation_seance_mode_libre']              = 'Zonder opleiding (vrije sessie)';
$lang['formation_seance_select_inscription']      = '-- Selecteer een opleiding --';
$lang['formation_seance_meteo_cavok']             = 'CAVOK';
$lang['formation_seance_meteo_vent_faible']       = 'Lichte wind';
$lang['formation_seance_meteo_vent_modere']       = 'Matige wind';
$lang['formation_seance_meteo_vent_fort']         = 'Sterke wind';
$lang['formation_seance_meteo_thermiques']        = 'Thermiek';
$lang['formation_seance_meteo_turbulences']       = 'Turbulentie';
$lang['formation_seance_meteo_nuageux']           = 'Bewolkt';
$lang['formation_seance_meteo_couvert']           = 'Geheel bewolkt';
$lang['formation_seance_meteo_pluie']             = 'Regen';
$lang['formation_seance_meteo_vent_travers']      = 'Zijwind';
$lang['formation_seance_create_success']          = 'Sessie succesvol opgeslagen.';
$lang['formation_seance_create_error']            = 'Fout bij het opslaan van de sessie.';
$lang['formation_seance_update_success']          = 'Sessie succesvol bijgewerkt.';
$lang['formation_seance_update_error']            = 'Fout bij het bijwerken van de sessie.';
$lang['formation_seance_delete_success']          = 'Sessie succesvol verwijderd.';
$lang['formation_seance_delete_error']            = 'Fout bij het verwijderen van de sessie.';
$lang['formation_seance_delete_confirm']          = 'Weet u zeker dat u deze sessie wilt verwijderen?';
$lang['formation_seance_inscription_required']    = 'Selecteer een open opleiding.';
$lang['formation_seance_inscription_not_open']    = 'De geselecteerde opleiding is niet open.';
$lang['formation_seance_pilote_programme_required'] = 'Piloot en programma zijn verplicht voor een vrije sessie.';
$lang['formation_seance_filtre_pilote']           = 'Filteren op piloot';
$lang['formation_seance_filtre_instructeur']      = 'Filteren op instructeur';
$lang['formation_seance_filtre_programme']        = 'Filteren op programma';
$lang['formation_seance_filtre_type']             = 'Filteren op type';
$lang['formation_seance_filtre_date_debut']       = 'Begindatum';
$lang['formation_seance_filtre_date_fin']         = 'Einddatum';
$lang['formation_evaluation_niveau_non_aborde']   = 'Niet behandeld';
$lang['formation_evaluation_niveau_aborde']       = 'Behandeld';
$lang['formation_evaluation_niveau_a_revoir']     = 'Te herzien';
$lang['formation_evaluation_lecon']               = 'Les';
$lang['formation_evaluation_select_lecon']        = '-- Selecteer een les --';
$lang['formation_evaluation_aucune']              = 'Geen beoordeling geregistreerd';
$lang['formation_progressions_title']             = 'Voortgangsbladen';
$lang['formation_progressions_empty']             = 'Geen voortgangsblad beschikbaar.';
$lang['formation_progression_titre']              = 'Voortgang';
$lang['formation_progression_fiche_title']        = 'Voortgangsblad';
$lang['formation_progression_voir']               = 'Bekijken';
$lang['formation_progression_voir_fiche']         = 'Voortgangsblad bekijken';
$lang['formation_progression_export_pdf']         = 'Exporteren naar PDF';
$lang['formation_progression_statistiques']       = 'Statistieken';
$lang['formation_progression_nb_seances']         = 'Aantal sessies';
$lang['formation_progression_heures_vol']         = 'Vlieguren';
$lang['formation_progression_atterrissages']      = 'Landingen';
$lang['formation_progression_pourcentage_acquis'] = 'Verworven percentage';
$lang['formation_progression_sujets_acquis']      = 'van de onderwerpen verworven';
$lang['formation_progression_detail_lecons']      = 'Detail per les';
$lang['formation_progression_no_lecons']          = 'Geen lessen gedefinieerd in dit programma.';
$lang['formation_progression_no_sujets']          = 'Geen onderwerpen gedefinieerd voor deze les.';
$lang['formation_progression_nb_seances_sujet']   = 'Sessies';
$lang['formation_progression_derniere_eval']      = 'Laatste beoordeling';
$lang['formation_progression_historique']         = 'Geschiedenis';

/* End of file formation_lang.php */
/* Location: ./application/language/dutch/formation_lang.php */
