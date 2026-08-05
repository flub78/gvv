<?php
/**
 * French language file for the Maintenance (Aircraft Maintenance) module
 */

// General
$lang['maintenance_acces_refuse'] = 'Accès réservé aux mécanos et administrateurs.';
$lang['maintenance_btn_retour'] = 'Retour';
$lang['maintenance_btn_annuler'] = 'Annuler';
$lang['maintenance_btn_creer'] = 'Créer';
$lang['maintenance_btn_enregistrer'] = 'Enregistrer';

// Equipements - Liste
$lang['maintenance_equipements_title'] = 'Équipements maintenables';
$lang['maintenance_equipements_create'] = 'Nouvel équipement';
$lang['maintenance_equipements_edit'] = 'Modifier';
$lang['maintenance_equipements_transfer'] = 'Transférer';
$lang['maintenance_equipements_deactivate'] = 'Désactiver';
$lang['maintenance_equipements_reactivate'] = 'Réactiver';
$lang['maintenance_equipements_aucun'] = 'Aucun équipement défini.';

// Equipements - Champs
$lang['maintenance_equipement_nom'] = 'Nom';
$lang['maintenance_equipement_aeronef'] = 'Aéronef';
$lang['maintenance_equipement_aeronef_help_edit'] = 'L\'aéronef de rattachement ne se modifie pas ici.';
$lang['maintenance_equipement_description'] = 'Description';
$lang['maintenance_equipement_actif'] = 'Actif';
$lang['maintenance_equipement_inactif'] = 'Inactif';
$lang['maintenance_equipement_deactivate_confirm'] = 'Désactiver cet équipement ?';

// Equipements - Messages de confirmation
$lang['maintenance_equipement_created'] = 'Équipement créé avec succès.';
$lang['maintenance_equipement_updated'] = 'Équipement mis à jour avec succès.';
$lang['maintenance_equipement_deactivated'] = 'Équipement désactivé.';
$lang['maintenance_equipement_reactivated'] = 'Équipement réactivé.';
$lang['maintenance_equipement_transferred'] = 'Équipement %s transféré vers l\'aéronef %s.';

// Transfert d'un equipement
$lang['maintenance_transfert_info'] = 'Vous êtes sur le point de transférer l\'équipement %s, actuellement rattaché à l\'aéronef %s, vers un autre aéronef. Son historique (dossiers, opérations) sera conservé.';
$lang['maintenance_transfert_nouvel_aeronef'] = 'Nouvel aéronef';
$lang['maintenance_transfert_confirmation'] = 'Je confirme le transfert de cet équipement vers l\'aéronef sélectionné.';

// Programmes - Liste
$lang['maintenance_programmes_title'] = 'Programmes d\'entretien';
$lang['maintenance_programmes_create'] = 'Nouveau programme';
$lang['maintenance_programmes_view'] = 'Voir';
$lang['maintenance_programmes_edit'] = 'Modifier';
$lang['maintenance_programmes_deactivate'] = 'Archiver';
$lang['maintenance_programmes_aucun'] = 'Aucun programme d\'entretien défini.';

// Programmes - Champs
$lang['maintenance_programme_code'] = 'Code';
$lang['maintenance_programme_code_deja_utilise'] = 'Ce code est déjà utilisé par un autre programme.';
$lang['maintenance_programme_titre'] = 'Titre';
$lang['maintenance_programme_titre_help'] = 'Doit correspondre exactement au titre H1 du fichier markdown déposé.';
$lang['maintenance_programme_section'] = 'Section';
$lang['maintenance_programme_section_globale'] = 'Toutes sections';
$lang['maintenance_programme_regle'] = 'Règle de butée';
$lang['maintenance_programme_regle_butee'] = 'Règle de butée';
$lang['maintenance_programme_regle_date'] = 'Butée calendaire';
$lang['maintenance_programme_regle_heures'] = 'Butée horaire';
$lang['maintenance_programme_periodicite_mois'] = 'Périodicité (mois)';
$lang['maintenance_programme_seuil_heures'] = 'Seuil (heures de vol)';
$lang['maintenance_programme_mois'] = 'mois';
$lang['maintenance_programme_aucune_regle'] = 'Aucune règle de butée définie.';
$lang['maintenance_programme_regle_date_resume'] = 'Échéance tous les %s mois';
$lang['maintenance_programme_regle_heures_resume'] = 'Échéance toutes les %s heures de vol';
$lang['maintenance_programme_structure'] = 'Structure';
$lang['maintenance_programme_nb_structure'] = '%d section(s), %d tâche(s)';
$lang['maintenance_programme_aucune_structure'] = 'Aucune structure importée pour le moment. Déposez un fichier markdown pour la générer.';
$lang['maintenance_programme_section_vide'] = 'Aucune tâche dans cette section.';

// Programmes - Document lie
$lang['maintenance_programme_document'] = 'Document source';
$lang['maintenance_programme_uploader'] = 'Déposer une version';
$lang['maintenance_programme_document_depose_le'] = 'Déposé le %s';
$lang['maintenance_programme_aucun_document'] = 'Aucun document déposé pour le moment.';
$lang['maintenance_programme_fichier'] = 'Fichier markdown';
$lang['maintenance_programme_fichier_help'] = 'Format : # Titre du programme, ## Section, ### Tâche (extensions .md ou .txt).';
$lang['maintenance_programme_upload_info_premiere_version'] = 'Cette première version définira la structure du programme (sections et tâches).';
$lang['maintenance_programme_upload_info_nouvelle_version'] = 'Cette nouvelle version remplace la précédente. Les tâches déjà utilisées dans une opération de maintenance sont conservées (désactivées si elles disparaissent du fichier).';

// Programmes - Messages de confirmation / erreur
$lang['maintenance_programme_created'] = 'Programme créé avec succès. Déposez maintenant son fichier markdown.';
$lang['maintenance_programme_updated'] = 'Programme mis à jour avec succès.';
$lang['maintenance_programme_deactivated'] = 'Programme archivé.';
$lang['maintenance_programme_reactivated'] = 'Programme réactivé.';
$lang['maintenance_programme_deactivate_confirm'] = 'Archiver ce programme ?';
$lang['maintenance_programme_upload_error'] = 'Aucun fichier valide n\'a été déposé.';
$lang['maintenance_programme_upload_extension'] = 'Seuls les fichiers .md et .txt sont acceptés.';
$lang['maintenance_programme_validation_error'] = 'Erreurs de validation du fichier markdown :';
$lang['maintenance_programme_parse_error'] = 'Erreur d\'analyse du fichier markdown :';
$lang['maintenance_programme_storage_error'] = 'Impossible d\'enregistrer le fichier sur le serveur.';
$lang['maintenance_programme_sync_error'] = 'Le document a été archivé mais la mise à jour de la structure a échoué :';
$lang['maintenance_programme_uploaded'] = 'Fichier déposé et structure mise à jour avec succès.';

// Dossiers - Liste
$lang['maintenance_dossiers_title'] = 'Dossiers d\'entretien';
$lang['maintenance_dossiers_aucun'] = 'Aucun dossier d\'entretien.';
$lang['maintenance_dossier_type_aeronef'] = 'Aéronef';
$lang['maintenance_dossier_type_equipement'] = 'Équipement';
$lang['maintenance_dossier_ouvrir_aeronef'] = 'Ouvrir (aéronef)';
$lang['maintenance_dossier_ouvrir_equipement'] = 'Ouvrir (équipement)';
$lang['maintenance_dossier_ouvrir_btn'] = 'Ouvrir le dossier';

// Dossiers - Champs
$lang['maintenance_dossier_entite'] = 'Entité';
$lang['maintenance_dossier_entite_aeronef'] = 'Aéronef';
$lang['maintenance_dossier_entite_equipement'] = 'Équipement';
$lang['maintenance_dossier_entite_invalide'] = 'L\'entité sélectionnée est introuvable.';
$lang['maintenance_dossier_programme'] = 'Programme d\'entretien';
$lang['maintenance_dossier_date_ouverture'] = 'Date d\'ouverture';
$lang['maintenance_dossier_date_suspension'] = 'Date de suspension';
$lang['maintenance_dossier_date_cloture'] = 'Date de clôture';
$lang['maintenance_dossier_mecano'] = 'Mécano référent';

// Dossiers - Statuts
$lang['maintenance_dossier_statut_ouvert'] = 'Ouvert';
$lang['maintenance_dossier_statut_suspendu'] = 'Suspendu';
$lang['maintenance_dossier_statut_cloture'] = 'Clôturé';
$lang['maintenance_dossier_statut_abandonne'] = 'Abandonné';

// Dossiers - Actions
$lang['maintenance_dossier_suspendre'] = 'Suspendre';
$lang['maintenance_dossier_reactiver'] = 'Réactiver';
$lang['maintenance_dossier_cloturer'] = 'Clôturer';
$lang['maintenance_dossier_abandonner'] = 'Abandonner';
$lang['maintenance_dossier_termine'] = 'Dossier terminé.';
$lang['maintenance_dossier_suspend_confirm'] = 'Suspendre ce dossier ?';
$lang['maintenance_dossier_close_confirm'] = 'Clôturer ce dossier ?';
$lang['maintenance_dossier_abandon_confirm'] = 'Abandonner ce dossier ? Cette action est difficilement réversible.';

// Dossiers - Operations
$lang['maintenance_dossier_operations'] = 'Opérations enregistrées';
$lang['maintenance_dossier_aucune_operation'] = 'Aucune opération enregistrée pour ce dossier.';

// Dossiers - Messages de confirmation
$lang['maintenance_dossier_ouvert'] = 'Dossier ouvert avec succès.';
$lang['maintenance_dossier_suspendu'] = 'Dossier suspendu.';
$lang['maintenance_dossier_reactive'] = 'Dossier réactivé.';
$lang['maintenance_dossier_cloture'] = 'Dossier clôturé.';
$lang['maintenance_dossier_abandonne'] = 'Dossier abandonné.';

// Operations - Formulaire
$lang['maintenance_operation_create'] = 'Nouvelle opération';
$lang['maintenance_operation_edit'] = 'Modifier l\'opération';
$lang['maintenance_operation_date'] = 'Date de l\'opération';
$lang['maintenance_operation_horametre'] = 'Horamètre relevé';
$lang['maintenance_operation_horametre_help'] = 'Le potentiel repartira de %s heures à partir de cette valeur.';
$lang['maintenance_operation_nouvelle_echeance'] = 'Nouvelle échéance (optionnel)';
$lang['maintenance_operation_echeance_help'] = 'Laisser vide pour calculer automatiquement (+%s mois depuis la date de l\'opération).';
$lang['maintenance_operation_commentaire'] = 'Remarque globale';
$lang['maintenance_operation_compte_rendu'] = 'Compte rendu papier';
$lang['maintenance_operation_compte_rendu_help'] = 'Scan ou photo du compte rendu signé par l\'atelier (PDF ou image), optionnel.';
$lang['maintenance_operation_document_existant'] = 'Compte rendu déjà déposé';
$lang['maintenance_operation_taches'] = 'Tâches du programme';
$lang['maintenance_operation_aucune_tache'] = 'Ce programme ne contient aucune tâche.';
$lang['maintenance_operation_enregistrer'] = 'Enregistrer l\'opération';
$lang['maintenance_operation_created'] = 'Opération enregistrée avec succès. Potentiel mis à jour.';
$lang['maintenance_operation_updated'] = 'Opération mise à jour avec succès. Potentiel recalculé.';

// Realisations
$lang['maintenance_realisation_fait'] = 'Fait';
$lang['maintenance_realisation_non_fait'] = 'Non fait';
$lang['maintenance_realisation_non_applicable'] = 'N/A';

// Bulletins de service
$lang['maintenance_bulletins_title'] = 'Bulletins de service';
$lang['maintenance_bulletins_deposer'] = 'Déposer un bulletin';
$lang['maintenance_bulletins_selectionner_aeronef'] = 'Sélectionnez un aéronef pour voir ses bulletins de service.';
$lang['maintenance_bulletins_aucun'] = 'Aucun bulletin de service pour cet aéronef.';
$lang['maintenance_bulletin_fichier'] = 'Fichier';
$lang['maintenance_bulletin_depose_le'] = 'Déposé le';
$lang['maintenance_bulletin_statut'] = 'Statut';
$lang['maintenance_bulletin_statut_a_traiter'] = 'À traiter';
$lang['maintenance_bulletin_statut_traite'] = 'Traité';
$lang['maintenance_bulletin_statut_non_applicable'] = 'Non applicable';
$lang['maintenance_bulletin_upload_error'] = 'Aucun fichier valide n\'a été déposé.';
$lang['maintenance_bulletin_uploaded'] = 'Bulletin déposé avec succès.';
$lang['maintenance_bulletin_statut_invalide'] = 'Statut invalide.';
$lang['maintenance_bulletin_statut_mis_a_jour'] = 'Statut du bulletin mis à jour.';

// Synthese de navigabilite
$lang['maintenance_synthese_titre'] = 'Synthèse de navigabilité';
$lang['maintenance_synthese_titre_aeronef'] = 'Synthèse de navigabilité';
$lang['maintenance_synthese_aeronef'] = 'Aéronef';
$lang['maintenance_synthese_etat_global'] = 'État global';
$lang['maintenance_synthese_filtrer'] = 'Filtrer';
$lang['maintenance_synthese_aucun_aeronef'] = 'Aucun aéronef pour cette section.';
$lang['maintenance_synthese_entite'] = 'Entité';
$lang['maintenance_synthese_programme'] = 'Programme';
$lang['maintenance_synthese_aucun_dossier'] = 'Aucun dossier ouvert.';
$lang['maintenance_synthese_genere_le'] = 'Généré le';
$lang['maintenance_synthese_export_pdf'] = 'Export PDF';
$lang['maintenance_synthese_echeance'] = 'Échéance';
$lang['maintenance_synthese_potentiel'] = 'Potentiel restant';

// Etats de navigabilite (partages entre vue flotte, vue aeronef, PDF)
$lang['maintenance_etat_a_jour'] = 'À jour';
$lang['maintenance_etat_echeance_proche'] = 'Échéance proche';
$lang['maintenance_etat_depasse'] = 'Dépassé';

// Dashboard maintenance
$lang['maintenance_dashboard_title'] = 'Maintenance';
$lang['maintenance_operations_title'] = 'Opérations de maintenance';
$lang['maintenance_operations_aucune'] = 'Aucune opération enregistrée.';
