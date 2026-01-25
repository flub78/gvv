<?php
/**
 * French language file for Formation (Training) Management
 */

// General
$lang['formation_feature_disabled'] = 'La fonctionnalité de gestion des formations n\'est pas activée.';

// Programmes - General
$lang['formation_programmes_title'] = 'Programmes de formation';
$lang['formation_programme_titre'] = 'Titre';
$lang['formation_programme_description'] = 'Description';
$lang['formation_programme_objectifs'] = 'Objectifs';
$lang['formation_programme_section'] = 'Section';
$lang['formation_programme_version'] = 'Version';
$lang['formation_programme_actif'] = 'Actif';
$lang['formation_programme_date_creation'] = 'Date de création';
$lang['formation_programme_date_modification'] = 'Dernière modification';
$lang['formation_programme_nb_lecons'] = 'Nombre de leçons';
$lang['formation_programme_nb_sujets'] = 'Nombre de sujets';

// Programmes - Actions
$lang['formation_programmes_create'] = 'Nouveau programme';
$lang['formation_programmes_edit'] = 'Modifier le programme';
$lang['formation_programmes_view'] = 'Détails du programme';
$lang['formation_programmes_delete'] = 'Supprimer';
$lang['formation_programmes_delete_confirm'] = 'Êtes-vous sûr de vouloir supprimer le programme {name} ?';
$lang['formation_programmes_export'] = 'Exporter en Markdown';
$lang['formation_programmes_import'] = 'Importer depuis Markdown';
$lang['formation_programmes_back'] = 'Retour aux programmes';

// Programmes - Messages
$lang['formation_programmes_no_programmes'] = 'Aucun programme de formation défini.';
$lang['formation_programme_create_success'] = 'Programme créé avec succès.';
$lang['formation_programme_create_error'] = 'Erreur lors de la création du programme.';
$lang['formation_programme_update_success'] = 'Programme mis à jour avec succès.';
$lang['formation_programme_update_error'] = 'Erreur lors de la mise à jour du programme.';
$lang['formation_programme_delete_success'] = 'Programme supprimé avec succès.';
$lang['formation_programme_delete_error'] = 'Erreur lors de la suppression du programme.';
$lang['formation_programme_delete_error_used'] = 'Ce programme ne peut pas être supprimé car il est utilisé dans des inscriptions actives.';

// Import/Export
$lang['formation_import_file'] = 'Fichier Markdown';
$lang['formation_import_file_help'] = 'Sélectionner un fichier .md contenant le programme de formation';
$lang['formation_import_manual'] = 'Créer manuellement';
$lang['formation_import_from_markdown'] = 'Importer depuis Markdown';
$lang['formation_import_success'] = 'Programme importé avec succès depuis le fichier Markdown.';
$lang['formation_import_error_upload'] = 'Erreur lors du téléchargement du fichier.';
$lang['formation_import_error_empty'] = 'Le fichier est vide ou illisible.';
$lang['formation_import_error_invalid'] = 'Structure Markdown invalide';
$lang['formation_import_error_parse'] = 'Erreur lors de l\'analyse du fichier Markdown';
$lang['formation_import_error_db'] = 'Erreur lors de la création du programme en base de données.';
$lang['formation_import_error_lecon'] = 'Erreur lors de la création d\'une leçon.';
$lang['formation_import_error_sujet'] = 'Erreur lors de la création d\'un sujet.';
$lang['formation_import_error_transaction'] = 'Erreur lors de la transaction en base de données.';
$lang['formation_export_markdown'] = 'Exporter en Markdown';

// Leçons
$lang['formation_lecon'] = 'Leçon';
$lang['formation_lecons'] = 'Leçons';
$lang['formation_lecon_numero'] = 'Numéro';
$lang['formation_lecon_titre'] = 'Titre de la leçon';
$lang['formation_lecon_description'] = 'Description';
$lang['formation_lecon_objectifs'] = 'Objectifs';
$lang['formation_lecon_ordre'] = 'Ordre';

// Sujets
$lang['formation_sujet'] = 'Sujet';
$lang['formation_sujets'] = 'Sujets';
$lang['formation_sujet_numero'] = 'Numéro';
$lang['formation_sujet_titre'] = 'Titre du sujet';
$lang['formation_sujet_description'] = 'Description';
$lang['formation_sujet_objectifs'] = 'Objectifs';
$lang['formation_sujet_ordre'] = 'Ordre';

// Inscriptions
$lang['formation_inscription'] = 'Inscription';
$lang['formation_inscriptions'] = 'Inscriptions';
$lang['formation_inscription_pilote'] = 'Pilote';
$lang['formation_inscription_programme'] = 'Programme';
$lang['formation_inscription_instructeur'] = 'Instructeur';
$lang['formation_inscription_date_debut'] = 'Date de début';
$lang['formation_inscription_date_fin'] = 'Date de fin';
$lang['formation_inscription_statut'] = 'Statut';
$lang['formation_inscription_statut_ouverte'] = 'Ouverte';
$lang['formation_inscription_statut_suspendue'] = 'Suspendue';
$lang['formation_inscription_statut_terminee'] = 'Terminée';
$lang['formation_inscription_statut_abandonnee'] = 'Abandonnée';
$lang['formation_inscription_resultat'] = 'Résultat';
$lang['formation_inscription_commentaire'] = 'Commentaire';

// Séances
$lang['formation_seance'] = 'Séance';
$lang['formation_seances'] = 'Séances';
$lang['formation_seance_date'] = 'Date';
$lang['formation_seance_duree'] = 'Durée (minutes)';
$lang['formation_seance_pilote'] = 'Pilote';
$lang['formation_seance_instructeur'] = 'Instructeur';
$lang['formation_seance_inscription'] = 'Inscription';
$lang['formation_seance_libre'] = 'Séance libre (sans inscription)';
$lang['formation_seance_meteo'] = 'Conditions météo';
$lang['formation_seance_commentaire'] = 'Commentaire';

// Évaluations
$lang['formation_evaluation'] = 'Évaluation';
$lang['formation_evaluations'] = 'Évaluations';
$lang['formation_evaluation_sujet'] = 'Sujet';
$lang['formation_evaluation_niveau'] = 'Niveau';
$lang['formation_evaluation_niveau_non_vu'] = 'Non vu';
$lang['formation_evaluation_niveau_debutant'] = 'Débutant';
$lang['formation_evaluation_niveau_progresse'] = 'En progression';
$lang['formation_evaluation_niveau_acquis'] = 'Acquis';
$lang['formation_evaluation_niveau_maitrise'] = 'Maîtrisé';
$lang['formation_evaluation_commentaire'] = 'Commentaire';

// Form elements
$lang['formation_form_required'] = 'Champs obligatoires';
$lang['formation_form_optional'] = 'Champs optionnels';
$lang['formation_form_save'] = 'Enregistrer';
$lang['formation_form_cancel'] = 'Annuler';

/* End of file formation_lang.php */
/* Location: ./application/language/french/formation_lang.php */
