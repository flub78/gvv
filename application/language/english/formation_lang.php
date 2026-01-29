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
$lang['formation_seance_pilote'] = 'Pilot';
$lang['formation_seance_instructeur'] = 'Instructor';
$lang['formation_seance_inscription'] = 'Enrollment';
$lang['formation_seance_libre'] = 'Free session (no enrollment)';
$lang['formation_seance_meteo'] = 'Weather Conditions';
$lang['formation_seance_commentaire'] = 'Comment';
$lang['formation_seance_type_formation_label'] = 'Training session';
$lang['formation_seance_type_libre_label'] = 'Refresher session for licensed pilot';
$lang['formation_seances_libres_title'] = 'Refresher sessions';
$lang['formation_seance_precedente'] = 'Previous session';

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

/* End of file formation_lang.php */
/* Location: ./application/language/english/formation_lang.php */
