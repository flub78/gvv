<?php
/**
 * English language file for Formation (Training) Management
 */

// General
$lang['formation_feature_disabled'] = 'The training management feature is not enabled.';

// Programmes - General
$lang['formation_programmes_title'] = 'Training Programs';
$lang['formation_programme_titre'] = 'Title';
$lang['formation_programme_description'] = 'Description';
$lang['formation_programme_objectifs'] = 'Objectives';
$lang['formation_programme_section'] = 'Section';
$lang['formation_programme_version'] = 'Version';
$lang['formation_programme_actif'] = 'Active';
$lang['formation_programme_date_creation'] = 'Created on';
$lang['formation_programme_date_modification'] = 'Last modified';
$lang['formation_programme_nb_lecons'] = 'Number of lessons';
$lang['formation_programme_nb_sujets'] = 'Number of topics';

// Programmes - Actions
$lang['formation_programmes_create'] = 'New Program';
$lang['formation_programmes_edit'] = 'Edit Program';
$lang['formation_programmes_view'] = 'Program Details';
$lang['formation_programmes_delete'] = 'Delete';
$lang['formation_programmes_delete_confirm'] = 'Are you sure you want to delete the program {name}?';
$lang['formation_programmes_export'] = 'Export as Markdown';
$lang['formation_programmes_import'] = 'Import from Markdown';
$lang['formation_programmes_back'] = 'Back to Programs';

// Programmes - Messages
$lang['formation_programmes_no_programmes'] = 'No training programs defined.';
$lang['formation_programme_create_success'] = 'Program created successfully.';
$lang['formation_programme_create_error'] = 'Error creating program.';
$lang['formation_programme_update_success'] = 'Program updated successfully.';
$lang['formation_programme_update_error'] = 'Error updating program.';
$lang['formation_programme_delete_success'] = 'Program deleted successfully.';
$lang['formation_programme_delete_error'] = 'Error deleting program.';
$lang['formation_programme_delete_error_used'] = 'This program cannot be deleted because it is used in active enrollments.';
$lang['formation_programme_update_structure_blocked'] = 'The structure of this program cannot be modified because %d enrollment(s) are associated with it. To modify the structure, create a new derived program (copy) and archive the old one once enrollments are completed.';

// Import/Export
$lang['formation_import_file'] = 'Markdown File';
$lang['formation_import_file_help'] = 'Select a .md file containing the training program';
$lang['formation_import_manual'] = 'Create manually';
$lang['formation_import_from_markdown'] = 'Import from Markdown';
$lang['formation_import_success'] = 'Program imported successfully from Markdown file.';
$lang['formation_import_error_upload'] = 'Error uploading file.';
$lang['formation_import_error_empty'] = 'File is empty or unreadable.';
$lang['formation_import_error_invalid'] = 'Invalid Markdown structure';
$lang['formation_import_error_parse'] = 'Error parsing Markdown file';
$lang['formation_import_error_db'] = 'Error creating program in database.';
$lang['formation_import_error_lecon'] = 'Error creating a lesson.';
$lang['formation_import_error_sujet'] = 'Error creating a topic.';
$lang['formation_import_error_transaction'] = 'Database transaction error.';
$lang['formation_export_markdown'] = 'Export as Markdown';
$lang['formation_export_pdf'] = 'Export as PDF';

// Leçons
$lang['formation_lecon'] = 'Lesson';
$lang['formation_lecons'] = 'Lessons';
$lang['formation_lecon_numero'] = 'Number';
$lang['formation_lecon_titre'] = 'Lesson Title';
$lang['formation_lecon_description'] = 'Description';
$lang['formation_lecon_objectifs'] = 'Objectives';
$lang['formation_lecon_ordre'] = 'Order';

// Sujets
$lang['formation_sujet'] = 'Topic';
$lang['formation_sujets'] = 'Topics';
$lang['formation_sujet_numero'] = 'Number';
$lang['formation_sujet_titre'] = 'Topic Title';
$lang['formation_sujet_description'] = 'Description';
$lang['formation_sujet_objectifs'] = 'Objectives';
$lang['formation_sujet_ordre'] = 'Order';

// Inscriptions
$lang['formation_inscription'] = 'Enrollment';
$lang['formation_inscriptions'] = 'Enrollments';
$lang['formation_inscription_pilote'] = 'Pilot';
$lang['formation_inscription_programme'] = 'Program';
$lang['formation_inscription_instructeur'] = 'Instructor';
$lang['formation_inscription_date_debut'] = 'Start Date';
$lang['formation_inscription_date_fin'] = 'End Date';
$lang['formation_inscription_statut'] = 'Status';
$lang['formation_inscription_statut_ouverte'] = 'Open';
$lang['formation_inscription_statut_suspendue'] = 'Suspended';
$lang['formation_inscription_statut_terminee'] = 'Completed';
$lang['formation_inscription_statut_abandonnee'] = 'Abandoned';
$lang['formation_inscription_resultat'] = 'Result';
$lang['formation_inscription_commentaire'] = 'Comment';

// Séances
$lang['formation_seance'] = 'Session';
$lang['formation_seances'] = 'Sessions';
$lang['formation_seance_date'] = 'Date';
$lang['formation_seance_duree'] = 'Duration (minutes)';
$lang['formation_seance_duree_cours'] = 'Duration';
$lang['formation_seance_pilote'] = 'Pilot';
$lang['formation_seance_instructeur'] = 'Instructor';
$lang['formation_seance_inscription'] = 'Enrollment';
$lang['formation_seance_libre'] = 'Free session (no enrollment)';
$lang['formation_seance_meteo'] = 'Weather Conditions';
$lang['formation_seance_commentaire'] = 'Comment';
$lang['formation_seance_programme'] = 'Program';
$lang['formation_seance_aucun_programme'] = 'No program';
$lang['formation_seance_type_formation_label'] = 'Training session';
$lang['formation_seance_type_libre_label'] = 'Refresher session for licensed pilot';
$lang['formation_seances_libres_title'] = 'Refresher sessions';
$lang['formation_seance_precedente'] = 'Previous session';
$lang['formation_seance_categorie'] = 'Category';
$lang['formation_seance_categorie_aucune'] = 'No category';
$lang['formation_seance_categorie_toutes'] = 'All categories';

// Évaluations
$lang['formation_evaluation'] = 'Assessment';
$lang['formation_evaluations'] = 'Assessments';
$lang['formation_evaluation_sujet'] = 'Topic';
$lang['formation_evaluation_niveau'] = 'Level';
$lang['formation_evaluation_niveau_non_vu'] = 'Not covered';
$lang['formation_evaluation_niveau_debutant'] = 'Beginner';
$lang['formation_evaluation_niveau_progresse'] = 'Progressing';
$lang['formation_evaluation_niveau_acquis'] = 'Acquired';
$lang['formation_evaluation_niveau_maitrise'] = 'Mastered';
$lang['formation_evaluation_commentaire'] = 'Comment';

// Reports
$lang['formation_rapports_title'] = 'Training reports';
$lang['formation_rapports_cloturees_succes'] = 'Successfully completed trainings';
$lang['formation_rapports_abandonnees'] = 'Abandoned trainings';
$lang['formation_rapports_suspendues'] = 'Suspended trainings';
// My training (student view)
$lang['formation_mes_formations_title'] = 'My Training';
$lang['formation_mes_formations_empty'] = 'You are not enrolled in any training program.';
$lang['formation_mes_formations_info'] = 'View your training programs and progress.';
$lang['formation_voir_ma_progression'] = 'View my progress';
$lang['formation_voir_mes_seances'] = 'View my sessions';$lang['formation_rapports_ouvertes'] = 'Opened trainings';
$lang['formation_rapports_en_cours'] = 'Ongoing trainings';
$lang['formation_rapports_reentrainement'] = 'Refresher sessions';
$lang['formation_rapports_par_instructeur'] = 'By instructor';
$lang['formation_rapports_par_categorie'] = 'By session category';
$lang['formation_rapports_nb_seances'] = 'Sessions';
$lang['formation_rapports_nb_seances_formation'] = 'Training sessions';
$lang['formation_rapports_nb_seances_libre'] = 'Refresher sessions';
$lang['formation_rapports_progression'] = 'Progression';
$lang['formation_rapports_aucune'] = 'None';
$lang['formation_rapports_date_cloture'] = 'Closing date';
$lang['formation_rapports_motif'] = 'Reason';
$lang['formation_rapports_date_suspension'] = 'Suspension date';

// Sessions
$lang['formation_seances_create'] = 'New Session';
$lang['formation_seances_edit'] = 'Edit Session';
$lang['formation_seances_detail'] = 'Session Details';
$lang['formation_seances_empty'] = 'No sessions recorded.';
$lang['formation_seances_back'] = 'Back to Sessions';
$lang['formation_seances_back_to_formation'] = 'Back to Training';

// Form elements
$lang['formation_form_required'] = 'Required fields';
$lang['formation_form_optional'] = 'Optional fields';
$lang['formation_form_save'] = 'Save';
$lang['formation_form_cancel'] = 'Cancel';

// Solo flight authorizations
$lang['formation_autorisation_solo'] = 'Solo Flight Authorization';
$lang['formation_autorisations_solo'] = 'Solo Flight Authorizations';
$lang['formation_autorisations_solo_title'] = 'Solo Flight Authorizations';
$lang['formation_autorisations_solo_list'] = 'Authorization List';
$lang['formation_autorisations_solo_create'] = 'New Authorization';
$lang['formation_autorisations_solo_edit'] = 'Edit Authorization';
$lang['formation_autorisations_solo_detail'] = 'Authorization Details';
$lang['formation_autorisations_solo_empty'] = 'No solo flight authorizations recorded.';
$lang['formation_autorisations_solo_back'] = 'Back to Authorizations';

$lang['formation_autorisation_solo_formation'] = 'Training';
$lang['formation_autorisation_solo_eleve'] = 'Student';
$lang['formation_autorisation_solo_instructeur'] = 'Instructor';
$lang['formation_autorisation_solo_date'] = 'Authorization Date';
$lang['formation_autorisation_solo_section'] = 'Section/Club';
$lang['formation_autorisation_solo_machine'] = 'Authorized Aircraft';
$lang['formation_autorisation_solo_consignes'] = 'Instructions';
$lang['formation_autorisation_solo_consignes_help'] = 'Instructions must contain at least 250 characters.';
$lang['formation_autorisation_solo_consignes_minlength'] = 'Instructions must contain at least 250 characters.';
$lang['formation_autorisation_solo_date_creation'] = 'Created on';
$lang['formation_autorisation_solo_date_modification'] = 'Last modified';

$lang['formation_autorisation_solo_created'] = 'Solo flight authorization created successfully.';
$lang['formation_autorisation_solo_updated'] = 'Solo flight authorization updated successfully.';
$lang['formation_autorisation_solo_deleted'] = 'Solo flight authorization deleted successfully.';
$lang['formation_autorisation_solo_create_error'] = 'Error creating authorization.';
$lang['formation_autorisation_solo_update_error'] = 'Error updating authorization.';
$lang['formation_autorisation_solo_delete_confirm'] = 'Are you sure you want to delete this solo flight authorization?';
$lang['formation_autorisation_solo_delete_confirm_btn'] = 'Confirm Deletion';

$lang['formation_inscription_not_found'] = 'Training not found.';
$lang['formation_acces_instructeur_requis'] = 'Access restricted to instructors.';
$lang['formation_acces_refuse'] = 'Access denied.';

// Session types
$lang['formation_types_seances_title']            = 'Training session types';
$lang['formation_type_seance_nom']                = 'Name';
$lang['formation_type_seance_nature']             = 'Type';
$lang['formation_type_seance_description']        = 'Description';
$lang['formation_type_seance_periodicite']        = 'Max. periodicity (days)';
$lang['formation_type_seance_periodicite_help']   = 'Maximum number of days between two sessions of this type for the same student. Leave empty for no constraint.';
$lang['formation_type_seance_actif']              = 'Active';
$lang['formation_nature_vol']                     = 'In-flight';
$lang['formation_nature_theorique']               = 'Ground';
$lang['formation_types_seances_create']           = 'New session type';
$lang['formation_types_seances_edit']             = 'Edit type';
$lang['formation_types_seances_delete']           = 'Delete';
$lang['formation_types_seances_deactivate']       = 'Deactivate';
$lang['formation_type_seance_created']            = 'Session type created successfully.';
$lang['formation_type_seance_updated']            = 'Session type updated successfully.';
$lang['formation_type_seance_deleted']            = 'Session type deleted.';
$lang['formation_type_seance_deactivated']        = 'Session type deactivated.';
$lang['formation_type_seance_in_use']             = 'This type is used by existing sessions and cannot be deleted. You can deactivate it instead.';
$lang['formation_type_seance_no_periodicite']     = 'No constraint';
$lang['formation_type_seance_periodicite_jours']  = '%d d';

// Theoretical sessions
$lang['formation_seances_theoriques_title']           = 'Theoretical sessions';
$lang['formation_seance_theorique_create']            = 'New theoretical session';
$lang['formation_seance_theorique_edit']              = 'Edit session';
$lang['formation_seance_theorique_detail']            = 'Session detail';
$lang['formation_seances_theoriques_empty']           = 'No theoretical sessions recorded.';
$lang['formation_seance_lieu']                        = 'Location';
$lang['formation_seance_lieu_placeholder']            = 'E.g.: Meeting room, Hangar A…';
$lang['formation_seance_participants']                = 'Participants';
$lang['formation_seance_participants_requis']         = 'Please add at least one participant.';
$lang['formation_seance_participants_recherche']      = 'Search for a member…';
$lang['formation_seance_participants_aucun']          = 'No participants.';
$lang['formation_seance_type_invalide']               = 'The selected session type is not theoretical.';
$lang['formation_seance_commentaires']                = 'Comments';
$lang['formation_seance_theorique_create_success']    = 'Theoretical session created successfully.';
$lang['formation_seance_theorique_create_error']      = 'Error creating session.';
$lang['formation_seance_theorique_update_success']    = 'Theoretical session updated successfully.';
$lang['formation_seance_theorique_update_error']      = 'Error updating session.';
$lang['formation_seance_theorique_delete_success']    = 'Theoretical session deleted.';
$lang['formation_seance_nature']                      = 'Nature';
$lang['formation_seance_nature_vol']                  = 'Flight';
$lang['formation_seance_nature_theorique']            = 'Ground class';
$lang['formation_seance_nature_toutes']               = 'All';
$lang['formation_seance_nb_participants']             = 'Participants';

// Annual consolidated reports (Phase 3)
$lang['formation_rapports_annuel_title']           = 'Annual Consolidated Report';
$lang['formation_rapports_annuel_par_instructeur'] = 'By instructor';
$lang['formation_rapports_annuel_par_programme']   = 'By programme';
$lang['formation_rapports_annuel_nb_seances_vol']  = 'Flight sessions';
$lang['formation_rapports_annuel_nb_seances_sol']  = 'Ground sessions';
$lang['formation_rapports_annuel_heures_vol']      = 'Flight hours';
$lang['formation_rapports_annuel_heures_sol']      = 'Ground hours';
$lang['formation_rapports_annuel_nb_eleves_vol']   = 'Flight students';
$lang['formation_rapports_annuel_nb_eleves_sol']   = 'Ground students';
$lang['formation_rapports_annuel_total']           = 'Total';
$lang['formation_rapports_annuel_export_csv']      = 'Export CSV';
$lang['formation_rapports_annuel_aucun']           = 'No data for this year.';

// Compliance report
$lang['formation_rapports_conformite_title']           = 'Compliance Report';
$lang['formation_rapports_conformite_pilote']          = 'Pilot';
$lang['formation_rapports_conformite_derniere_seance'] = 'Last session';
$lang['formation_rapports_conformite_jours_ecoules']   = 'Days elapsed';
$lang['formation_rapports_conformite_periodicite']     = 'Max. periodicity';
$lang['formation_rapports_conformite_jamais']          = 'Never';
$lang['formation_rapports_conformite_aucun']           = 'All pilots are compliant.';
$lang['formation_rapports_conformite_aucun_type']      = 'No session type with a periodicity constraint.';
$lang['formation_rapports_conformite_export_csv']      = 'Export CSV';
$lang['formation_rapports_conformite_non_conformes']   = 'non-compliant pilot(s)';

// Missing keys added for completeness
$lang['formation_programme_section_help']         = 'If "Global", the programme will be visible in all sections. Otherwise, only in the selected section.';
$lang['formation_programme_type_aeronef']         = 'Aircraft type';
$lang['formation_programme_type_planeur']         = 'Glider';
$lang['formation_programme_type_avion']           = 'Aeroplane';
$lang['formation_structure_markdown']             = 'Programme structure';
$lang['formation_inscriptions_title']             = 'Ongoing training';
$lang['formation_inscriptions_ouvrir']            = 'Open a training';
$lang['formation_inscriptions_empty']             = 'No training records found.';
$lang['formation_inscriptions_count']             = 'training(s)';
$lang['formation_inscription_date_ouverture']     = 'Opening date';
$lang['formation_inscription_date_suspension']    = 'Suspension date';
$lang['formation_inscription_date_cloture']       = 'Closing date';
$lang['formation_inscription_statut_cloturee']    = 'Closed';
$lang['formation_inscription_ouverte']            = 'Training completed successfully';
$lang['formation_inscription_cloturee']           = 'Closed successfully';
$lang['formation_inscription_abandonnee']         = 'Abandoned';
$lang['formation_inscription_detail_title']       = 'Progress record';
$lang['formation_inscription_suspendre_title']    = 'Suspend training';
$lang['formation_inscription_suspendre_confirm']  = 'Are you sure you want to suspend this training?';
$lang['formation_inscription_suspendre_confirm_btn'] = 'Confirm suspension';
$lang['formation_inscription_cloturer_title']     = 'Close training';
$lang['formation_inscription_cloturer_info']      = 'You are about to close this training. Choose the closing type.';
$lang['formation_inscription_cloturer_confirm_btn'] = 'Confirm closing';
$lang['formation_inscription_type_cloture']       = 'Closing type';
$lang['formation_inscription_motif_suspension']   = 'Suspension reason';
$lang['formation_inscription_motif_cloture']      = 'Closing reason';
$lang['formation_inscription_motif_required']     = 'The reason is required.';
$lang['formation_inscription_create_success']     = 'Training created successfully.';
$lang['formation_inscription_create_error']       = 'Error creating the training.';
$lang['formation_inscription_update_success']     = 'Training updated successfully.';
$lang['formation_inscription_update_error']       = 'Error updating the training.';
$lang['formation_inscription_suspend_success']    = 'Training suspended successfully.';
$lang['formation_inscription_suspend_error']      = 'Error suspending the training.';
$lang['formation_inscription_reactivate_success'] = 'Training reactivated successfully.';
$lang['formation_inscription_reactivate_error']   = 'Error reactivating the training.';
$lang['formation_inscription_close_success']      = 'Training closed successfully.';
$lang['formation_inscription_close_error']        = 'Error closing the training.';
$lang['formation_inscription_already_open']       = 'This pilot already has an open training for this programme.';
$lang['formation_inscription_cannot_suspend']     = 'Cannot suspend this training (incorrect status).';
$lang['formation_inscription_cannot_reactivate']  = 'Cannot reactivate this training (incorrect status).';
$lang['formation_inscription_cannot_close']       = 'Cannot close this training (incorrect status).';
$lang['formation_inscription_type_required']      = 'The closing type is required.';
$lang['formation_seances_title']                  = 'Training sessions';
$lang['formation_seance_machine']                 = 'Aircraft';
$lang['formation_seance_duree_help']              = 'Format HH:MM';
$lang['formation_seance_nb_atterrissages']        = 'Number of landings';
$lang['formation_seance_prochaines_lecons']       = 'Preparation for next lessons';
$lang['formation_seance_libre_info']              = 'This session will be archived but will not contribute to a progress record.';
$lang['formation_seance_inscription_info']        = 'Session linked to training';
$lang['formation_seance_type']                    = 'Type';
$lang['formation_seance_type_formation']          = 'Training';
$lang['formation_seance_type_libre']              = 'Free';
$lang['formation_seance_type_toutes']             = 'All';
$lang['formation_seance_mode_inscription']        = 'With training';
$lang['formation_seance_mode_libre']              = 'Without training (free session)';
$lang['formation_seance_select_inscription']      = '-- Select a training --';
$lang['formation_seance_meteo_cavok']             = 'CAVOK';
$lang['formation_seance_meteo_vent_faible']       = 'Light wind';
$lang['formation_seance_meteo_vent_modere']       = 'Moderate wind';
$lang['formation_seance_meteo_vent_fort']         = 'Strong wind';
$lang['formation_seance_meteo_thermiques']        = 'Thermals';
$lang['formation_seance_meteo_turbulences']       = 'Turbulence';
$lang['formation_seance_meteo_nuageux']           = 'Cloudy';
$lang['formation_seance_meteo_couvert']           = 'Overcast';
$lang['formation_seance_meteo_pluie']             = 'Rain';
$lang['formation_seance_meteo_vent_travers']      = 'Crosswind';
$lang['formation_seance_create_success']          = 'Session saved successfully.';
$lang['formation_seance_create_error']            = 'Error saving the session.';
$lang['formation_seance_update_success']          = 'Session updated successfully.';
$lang['formation_seance_update_error']            = 'Error updating the session.';
$lang['formation_seance_delete_success']          = 'Session deleted successfully.';
$lang['formation_seance_delete_error']            = 'Error deleting the session.';
$lang['formation_seance_delete_confirm']          = 'Are you sure you want to delete this session?';
$lang['formation_seance_inscription_required']    = 'Please select an open training.';
$lang['formation_seance_inscription_not_open']    = 'The selected training is not open.';
$lang['formation_seance_pilote_programme_required'] = 'Pilot and programme are required for a free session.';
$lang['formation_seance_filtre_pilote']           = 'Filter by pilot';
$lang['formation_seance_filtre_instructeur']      = 'Filter by instructor';
$lang['formation_seance_filtre_programme']        = 'Filter by programme';
$lang['formation_seance_filtre_type']             = 'Filter by type';
$lang['formation_seance_filtre_date_debut']       = 'Start date';
$lang['formation_seance_filtre_date_fin']         = 'End date';
$lang['formation_evaluation_niveau_non_aborde']   = 'Not covered';
$lang['formation_evaluation_niveau_aborde']       = 'Covered';
$lang['formation_evaluation_niveau_a_revoir']     = 'To review';
$lang['formation_evaluation_lecon']               = 'Lesson';
$lang['formation_evaluation_select_lecon']        = '-- Select a lesson --';
$lang['formation_evaluation_aucune']              = 'No evaluation recorded';
$lang['formation_progressions_title']             = 'Progress records';
$lang['formation_progressions_empty']             = 'No progress record available.';
$lang['formation_progression_titre']              = 'Progress';
$lang['formation_progression_fiche_title']        = 'Progress record';
$lang['formation_progression_voir']               = 'View';
$lang['formation_progression_voir_fiche']         = 'View progress record';
$lang['formation_progression_export_pdf']         = 'Export to PDF';
$lang['formation_progression_statistiques']       = 'Statistics';
$lang['formation_progression_nb_seances']         = 'Number of sessions';
$lang['formation_progression_heures_vol']         = 'Flight hours';
$lang['formation_progression_atterrissages']      = 'Landings';
$lang['formation_progression_pourcentage_acquis'] = 'Percentage acquired';
$lang['formation_progression_sujets_acquis']      = 'of subjects acquired';
$lang['formation_progression_detail_lecons']      = 'Detail by lesson';
$lang['formation_progression_no_lecons']          = 'No lessons defined in this programme.';
$lang['formation_progression_no_sujets']          = 'No subjects defined for this lesson.';
$lang['formation_progression_nb_seances_sujet']   = 'Sessions';
$lang['formation_progression_derniere_eval']      = 'Last evaluation';
$lang['formation_progression_historique']         = 'History';

/* End of file formation_lang.php */
/* Location: ./application/language/english/formation_lang.php */
