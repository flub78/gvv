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
$lang['formation_programme_section_help'] = 'Si "Globale", le programme sera visible dans toutes les sections. Sinon, seulement dans la section sélectionnée.';
$lang['formation_programme_version'] = 'Version';
$lang['formation_programme_actif'] = 'Actif';
$lang['formation_programme_date_creation'] = 'Date de création';
$lang['formation_programme_date_modification'] = 'Dernière modification';
$lang['formation_programme_nb_lecons'] = 'Nombre de leçons';
$lang['formation_programme_nb_sujets'] = 'Nombre de sujets';
$lang['formation_programme_type_aeronef'] = 'Type d\'aéronef';
$lang['formation_programme_type_planeur'] = 'Planeur';
$lang['formation_programme_type_avion'] = 'Avion';

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
$lang['formation_programme_delete_error_used'] = 'Ce programme ne peut pas être supprimé car il est utilisé dans des formations actives.';

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
$lang['formation_structure_markdown'] = 'Structure du programme';

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
$lang['formation_inscription'] = 'Formation';
$lang['formation_inscriptions'] = 'Formations';
$lang['formation_inscriptions_title'] = 'Formations en cours';
$lang['formation_inscriptions_ouvrir'] = 'Ouvrir une formation';
$lang['formation_inscriptions_empty'] = 'Aucune formation enregistrée.';
$lang['formation_inscriptions_count'] = 'formation(s)';
$lang['formation_inscription_pilote'] = 'Pilote';
$lang['formation_inscription_programme'] = 'Programme';
$lang['formation_inscription_instructeur'] = 'Instructeur référent';
$lang['formation_inscription_date_ouverture'] = 'Date d\'ouverture';
$lang['formation_inscription_date_suspension'] = 'Date de suspension';
$lang['formation_inscription_date_cloture'] = 'Date de clôture';
$lang['formation_inscription_statut'] = 'Statut';
$lang['formation_inscription_commentaire'] = 'Commentaire';

// Inscriptions - Statuts
$lang['formation_inscription_statut_ouverte'] = 'Ouverte';
$lang['formation_inscription_statut_suspendue'] = 'Suspendue';
$lang['formation_inscription_statut_cloturee'] = 'Clôturée';
$lang['formation_inscription_statut_abandonnee'] = 'Abandonnée';
$lang['formation_inscription_ouverte'] = 'Formation terminée avec succès';
$lang['formation_inscription_cloturee'] = 'Clôturée avec succès';
$lang['formation_inscription_abandonnee'] = 'Abandonnée';

// Inscriptions - Actions
$lang['formation_inscription_detail_title'] = 'Détail de la formation';
$lang['formation_inscription_suspendre_title'] = 'Suspendre la formation';
$lang['formation_inscription_suspendre_confirm'] = 'Êtes-vous sûr de vouloir suspendre cette formation ?';
$lang['formation_inscription_suspendre_confirm_btn'] = 'Confirmer la suspension';
$lang['formation_inscription_cloturer_title'] = 'Clôturer la formation';
$lang['formation_inscription_cloturer_info'] = 'Vous allez clôturer cette formation. Choisissez le type de clôture.';
$lang['formation_inscription_cloturer_confirm_btn'] = 'Confirmer la clôture';
$lang['formation_inscription_type_cloture'] = 'Type de clôture';
$lang['formation_inscription_motif_suspension'] = 'Motif de suspension';
$lang['formation_inscription_motif_cloture'] = 'Motif de clôture';
$lang['formation_inscription_motif_required'] = 'Le motif est obligatoire.';

// Inscriptions - Messages
$lang['formation_inscription_create_success'] = 'Formation créée avec succès.';
$lang['formation_inscription_create_error'] = 'Erreur lors de la création de la formation.';
$lang['formation_inscription_update_success'] = 'Formation mise à jour avec succès.';
$lang['formation_inscription_update_error'] = 'Erreur lors de la mise à jour de la formation.';
$lang['formation_inscription_suspend_success'] = 'Formation suspendue avec succès.';
$lang['formation_inscription_suspend_error'] = 'Erreur lors de la suspension de la formation.';
$lang['formation_inscription_reactivate_success'] = 'Formation réactivée avec succès.';
$lang['formation_inscription_reactivate_error'] = 'Erreur lors de la réactivation de la formation.';
$lang['formation_inscription_close_success'] = 'Formation clôturée avec succès.';
$lang['formation_inscription_close_error'] = 'Erreur lors de la clôture de la formation.';
$lang['formation_inscription_already_open'] = 'Ce pilote a déjà une formation ouverte pour ce programme.';
$lang['formation_inscription_cannot_suspend'] = 'Impossible de suspendre cette formation (statut incorrect).';
$lang['formation_inscription_cannot_reactivate'] = 'Impossible de réactiver cette formation (statut incorrect).';
$lang['formation_inscription_cannot_close'] = 'Impossible de clôturer cette formation (statut incorrect).';
$lang['formation_inscription_type_required'] = 'Le type de clôture est obligatoire.';
$lang['formation_inscription_date_debut'] = 'Date de début';
$lang['formation_inscription_date_fin'] = 'Date de fin';
$lang['formation_inscription_resultat'] = 'Résultat';

// Séances - Général
$lang['formation_seance'] = 'Séance';
$lang['formation_seances'] = 'Séances';
$lang['formation_seances_title'] = 'Séances de formation';
$lang['formation_seances_create'] = 'Nouvelle séance';
$lang['formation_seances_edit'] = 'Modifier la séance';
$lang['formation_seances_detail'] = 'Détail de la séance';
$lang['formation_seances_empty'] = 'Aucune séance enregistrée.';
$lang['formation_seances_back'] = 'Retour aux séances';

// Séances - Champs
$lang['formation_seance_date'] = 'Date';
$lang['formation_seance_pilote'] = 'Pilote';
$lang['formation_seance_instructeur'] = 'Instructeur';
$lang['formation_seance_machine'] = 'Aéronef';
$lang['formation_seance_duree'] = 'Durée du vol';
$lang['formation_seance_duree_help'] = 'Format HH:MM';
$lang['formation_seance_nb_atterrissages'] = 'Nombre d\'atterrissages';
$lang['formation_seance_commentaire'] = 'Commentaires';
$lang['formation_seance_prochaines_lecons'] = 'Prochaines leçons recommandées';
$lang['formation_seance_programme'] = 'Programme';

// Séances - Types
$lang['formation_seance_inscription'] = 'Formation';
$lang['formation_seance_libre'] = 'Séance libre (hors formation)';
$lang['formation_seance_libre_info'] = 'Cette séance sera archivée mais ne contribuera pas à une fiche de progression.';
$lang['formation_seance_inscription_info'] = 'Séance liée à la formation';
$lang['formation_seance_type'] = 'Type';
$lang['formation_seance_type_formation'] = 'Formation';
$lang['formation_seance_type_libre'] = 'Libre';
$lang['formation_seance_type_toutes'] = 'Toutes';
$lang['formation_seance_mode_inscription'] = 'Avec formation';
$lang['formation_seance_mode_libre'] = 'Hors formation (séance libre)';
$lang['formation_seance_select_inscription'] = '-- Sélectionnez une formation --';

// Séances - Météo
$lang['formation_seance_meteo'] = 'Conditions météo';
$lang['formation_seance_meteo_cavok'] = 'CAVOK';
$lang['formation_seance_meteo_vent_faible'] = 'Vent faible';
$lang['formation_seance_meteo_vent_modere'] = 'Vent modéré';
$lang['formation_seance_meteo_vent_fort'] = 'Vent fort';
$lang['formation_seance_meteo_thermiques'] = 'Thermiques';
$lang['formation_seance_meteo_turbulences'] = 'Turbulences';
$lang['formation_seance_meteo_nuageux'] = 'Nuageux';
$lang['formation_seance_meteo_couvert'] = 'Couvert';
$lang['formation_seance_meteo_pluie'] = 'Pluie';
$lang['formation_seance_meteo_vent_travers'] = 'Vent de travers';

// Séances - Messages
$lang['formation_seance_create_success'] = 'Séance enregistrée avec succès.';
$lang['formation_seance_create_error'] = 'Erreur lors de l\'enregistrement de la séance.';
$lang['formation_seance_update_success'] = 'Séance mise à jour avec succès.';
$lang['formation_seance_update_error'] = 'Erreur lors de la mise à jour de la séance.';
$lang['formation_seance_delete_success'] = 'Séance supprimée avec succès.';
$lang['formation_seance_delete_error'] = 'Erreur lors de la suppression de la séance.';
$lang['formation_seance_delete_confirm'] = 'Êtes-vous sûr de vouloir supprimer cette séance ?';
$lang['formation_seance_inscription_required'] = 'Veuillez sélectionner une formation ouverte.';
$lang['formation_seance_inscription_not_open'] = 'La formation sélectionnée n\'est pas ouverte.';
$lang['formation_seance_pilote_programme_required'] = 'Le pilote et le programme sont obligatoires pour une séance libre.';

// Séances - Filtres
$lang['formation_seance_filtre_pilote'] = 'Filtrer par pilote';
$lang['formation_seance_filtre_instructeur'] = 'Filtrer par instructeur';
$lang['formation_seance_filtre_programme'] = 'Filtrer par programme';
$lang['formation_seance_filtre_type'] = 'Filtrer par type';
$lang['formation_seance_filtre_date_debut'] = 'Date de début';
$lang['formation_seance_filtre_date_fin'] = 'Date de fin';

// Évaluations
$lang['formation_evaluation'] = 'Évaluation';
$lang['formation_evaluations'] = 'Évaluations';
$lang['formation_evaluation_sujet'] = 'Sujet';
$lang['formation_evaluation_niveau'] = 'Niveau';
$lang['formation_evaluation_niveau_non_aborde'] = 'Non abordé';
$lang['formation_evaluation_niveau_aborde'] = 'Abordé';
$lang['formation_evaluation_niveau_a_revoir'] = 'À revoir';
$lang['formation_evaluation_niveau_acquis'] = 'Acquis';
$lang['formation_evaluation_commentaire'] = 'Commentaire';
$lang['formation_evaluation_lecon'] = 'Leçon';
$lang['formation_evaluation_select_lecon'] = '-- Sélectionnez une leçon --';
$lang['formation_evaluation_aucune'] = 'Aucune évaluation enregistrée';

// Progressions
$lang['formation_progressions_title'] = 'Fiches de progression';
$lang['formation_progressions_empty'] = 'Aucune fiche de progression disponible.';
$lang['formation_progression_titre'] = 'Progression';
$lang['formation_progression_fiche_title'] = 'Fiche de progression';
$lang['formation_progression_voir'] = 'Voir';
$lang['formation_progression_voir_fiche'] = 'Voir la fiche de progression';
$lang['formation_progression_export_pdf'] = 'Exporter en PDF';
$lang['formation_progression_statistiques'] = 'Statistiques';
$lang['formation_progression_nb_seances'] = 'Nombre de séances';
$lang['formation_progression_heures_vol'] = 'Heures de vol';
$lang['formation_progression_atterrissages'] = 'Atterrissages';
$lang['formation_progression_pourcentage_acquis'] = 'Pourcentage acquis';
$lang['formation_progression_sujets_acquis'] = 'des sujets acquis';
$lang['formation_progression_detail_lecons'] = 'Détail par leçon';
$lang['formation_progression_no_lecons'] = 'Aucune leçon définie dans ce programme.';
$lang['formation_progression_no_sujets'] = 'Aucun sujet défini pour cette leçon.';
$lang['formation_progression_nb_seances_sujet'] = 'Séances';
$lang['formation_progression_derniere_eval'] = 'Dernière évaluation';
$lang['formation_progression_historique'] = 'Historique';

// Form elements
$lang['formation_form_required'] = 'Champs obligatoires';
$lang['formation_form_optional'] = 'Champs optionnels';
$lang['formation_form_save'] = 'Enregistrer';
$lang['formation_form_cancel'] = 'Annuler';

/* End of file formation_lang.php */
/* Location: ./application/language/french/formation_lang.php */
