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

/* End of file formation_lang.php */
/* Location: ./application/language/dutch/formation_lang.php */
