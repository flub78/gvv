<?php
/**
 * English language file for the Maintenance (Aircraft Maintenance) module
 */

// General
$lang['maintenance_acces_refuse'] = 'Access restricted to mechanics and administrators.';
$lang['maintenance_btn_retour'] = 'Back';
$lang['maintenance_btn_annuler'] = 'Cancel';
$lang['maintenance_btn_creer'] = 'Create';
$lang['maintenance_btn_enregistrer'] = 'Save';

// Equipments - List
$lang['maintenance_equipements_title'] = 'Maintainable equipments';
$lang['maintenance_equipements_create'] = 'New equipment';
$lang['maintenance_equipements_edit'] = 'Edit';
$lang['maintenance_equipements_transfer'] = 'Transfer';
$lang['maintenance_equipements_deactivate'] = 'Deactivate';
$lang['maintenance_equipements_reactivate'] = 'Reactivate';
$lang['maintenance_equipements_aucun'] = 'No equipment defined yet.';

// Equipments - Fields
$lang['maintenance_equipement_nom'] = 'Name';
$lang['maintenance_equipement_aeronef'] = 'Aircraft';
$lang['maintenance_equipement_aeronef_help_edit'] = 'The attached aircraft cannot be changed here.';
$lang['maintenance_equipement_description'] = 'Description';
$lang['maintenance_equipement_actif'] = 'Active';
$lang['maintenance_equipement_inactif'] = 'Inactive';
$lang['maintenance_equipement_deactivate_confirm'] = 'Deactivate this equipment?';

// Equipments - Confirmation messages
$lang['maintenance_equipement_created'] = 'Equipment created successfully.';
$lang['maintenance_equipement_updated'] = 'Equipment updated successfully.';
$lang['maintenance_equipement_deactivated'] = 'Equipment deactivated.';
$lang['maintenance_equipement_reactivated'] = 'Equipment reactivated.';
$lang['maintenance_equipement_transferred'] = 'Equipment %s transferred to aircraft %s.';

// Equipment transfer
$lang['maintenance_transfert_info'] = 'You are about to transfer equipment %s, currently attached to aircraft %s, to another aircraft. Its history (dossiers, operations) will be preserved.';
$lang['maintenance_transfert_nouvel_aeronef'] = 'New aircraft';
$lang['maintenance_transfert_confirmation'] = 'I confirm the transfer of this equipment to the selected aircraft.';

// Programs - List
$lang['maintenance_programmes_title'] = 'Maintenance programs';
$lang['maintenance_programmes_create'] = 'New program';
$lang['maintenance_programmes_view'] = 'View';
$lang['maintenance_programmes_edit'] = 'Edit';
$lang['maintenance_programmes_deactivate'] = 'Archive';
$lang['maintenance_programmes_aucun'] = 'No maintenance program defined yet.';

// Programs - Fields
$lang['maintenance_programme_code'] = 'Code';
$lang['maintenance_programme_code_deja_utilise'] = 'This code is already used by another program.';
$lang['maintenance_programme_titre'] = 'Title';
$lang['maintenance_programme_titre_help'] = 'Must match exactly the H1 title of the uploaded markdown file.';
$lang['maintenance_programme_section'] = 'Section';
$lang['maintenance_programme_section_globale'] = 'All sections';
$lang['maintenance_programme_regle'] = 'Due rule';
$lang['maintenance_programme_regle_butee'] = 'Due rule';
$lang['maintenance_programme_regle_date'] = 'Calendar due date';
$lang['maintenance_programme_regle_heures'] = 'Flight hours due';
$lang['maintenance_programme_periodicite_mois'] = 'Periodicity (months)';
$lang['maintenance_programme_seuil_heures'] = 'Threshold (flight hours)';
$lang['maintenance_programme_mois'] = 'months';
$lang['maintenance_programme_aucune_regle'] = 'No due rule defined.';
$lang['maintenance_programme_regle_date_resume'] = 'Due every %s months';
$lang['maintenance_programme_regle_heures_resume'] = 'Due every %s flight hours';
$lang['maintenance_programme_structure'] = 'Structure';
$lang['maintenance_programme_nb_structure'] = '%d section(s), %d task(s)';
$lang['maintenance_programme_aucune_structure'] = 'No structure imported yet. Upload a markdown file to generate it.';
$lang['maintenance_programme_section_vide'] = 'No task in this section.';

// Programs - Linked document
$lang['maintenance_programme_document'] = 'Source document';
$lang['maintenance_programme_uploader'] = 'Upload a version';
$lang['maintenance_programme_document_depose_le'] = 'Uploaded on %s';
$lang['maintenance_programme_aucun_document'] = 'No document uploaded yet.';
$lang['maintenance_programme_fichier'] = 'Markdown file';
$lang['maintenance_programme_fichier_help'] = 'Format: # Program title, ## Section, ### Task (.md or .txt extension).';
$lang['maintenance_programme_upload_info_premiere_version'] = 'This first version will define the program structure (sections and tasks).';
$lang['maintenance_programme_upload_info_nouvelle_version'] = 'This new version replaces the previous one. Tasks already used in a maintenance operation are preserved (deactivated if removed from the file).';

// Programs - Confirmation / error messages
$lang['maintenance_programme_created'] = 'Program created successfully. Now upload its markdown file.';
$lang['maintenance_programme_updated'] = 'Program updated successfully.';
$lang['maintenance_programme_deactivated'] = 'Program archived.';
$lang['maintenance_programme_reactivated'] = 'Program reactivated.';
$lang['maintenance_programme_deactivate_confirm'] = 'Archive this program?';
$lang['maintenance_programme_upload_error'] = 'No valid file was uploaded.';
$lang['maintenance_programme_upload_extension'] = 'Only .md and .txt files are accepted.';
$lang['maintenance_programme_validation_error'] = 'Validation errors in the markdown file:';
$lang['maintenance_programme_parse_error'] = 'Error parsing the markdown file:';
$lang['maintenance_programme_storage_error'] = 'Unable to save the file on the server.';
$lang['maintenance_programme_sync_error'] = 'The document was archived but the structure update failed:';
$lang['maintenance_programme_uploaded'] = 'File uploaded and structure updated successfully.';

// Dossiers - List
$lang['maintenance_dossiers_title'] = 'Maintenance dossiers';
$lang['maintenance_dossiers_aucun'] = 'No maintenance dossier.';
$lang['maintenance_dossier_type_aeronef'] = 'Aircraft';
$lang['maintenance_dossier_type_equipement'] = 'Equipment';
$lang['maintenance_dossier_ouvrir_aeronef'] = 'Open (aircraft)';
$lang['maintenance_dossier_ouvrir_equipement'] = 'Open (equipment)';
$lang['maintenance_dossier_ouvrir_btn'] = 'Open dossier';

// Dossiers - Fields
$lang['maintenance_dossier_entite'] = 'Entity';
$lang['maintenance_dossier_entite_aeronef'] = 'Aircraft';
$lang['maintenance_dossier_entite_equipement'] = 'Equipment';
$lang['maintenance_dossier_entite_invalide'] = 'The selected entity could not be found.';
$lang['maintenance_dossier_programme'] = 'Maintenance program';
$lang['maintenance_dossier_date_ouverture'] = 'Opening date';
$lang['maintenance_dossier_date_suspension'] = 'Suspension date';
$lang['maintenance_dossier_date_cloture'] = 'Closure date';
$lang['maintenance_dossier_mecano'] = 'Referent mechanic';

// Dossiers - Statuses
$lang['maintenance_dossier_statut_ouvert'] = 'Open';
$lang['maintenance_dossier_statut_suspendu'] = 'Suspended';
$lang['maintenance_dossier_statut_cloture'] = 'Closed';
$lang['maintenance_dossier_statut_abandonne'] = 'Abandoned';

// Dossiers - Actions
$lang['maintenance_dossier_suspendre'] = 'Suspend';
$lang['maintenance_dossier_reactiver'] = 'Reactivate';
$lang['maintenance_dossier_cloturer'] = 'Close';
$lang['maintenance_dossier_abandonner'] = 'Abandon';
$lang['maintenance_dossier_termine'] = 'Dossier finished.';
$lang['maintenance_dossier_suspend_confirm'] = 'Suspend this dossier?';
$lang['maintenance_dossier_close_confirm'] = 'Close this dossier?';
$lang['maintenance_dossier_abandon_confirm'] = 'Abandon this dossier? This action is hard to reverse.';

// Dossiers - Operations
$lang['maintenance_dossier_operations'] = 'Recorded operations';
$lang['maintenance_dossier_aucune_operation'] = 'No operation recorded for this dossier.';

// Dossiers - Confirmation messages
$lang['maintenance_dossier_ouvert'] = 'Dossier opened successfully.';
$lang['maintenance_dossier_suspendu'] = 'Dossier suspended.';
$lang['maintenance_dossier_reactive'] = 'Dossier reactivated.';
$lang['maintenance_dossier_cloture'] = 'Dossier closed.';
$lang['maintenance_dossier_abandonne'] = 'Dossier abandoned.';

// Operations - Form
$lang['maintenance_operation_create'] = 'New operation';
$lang['maintenance_operation_edit'] = 'Edit operation';
$lang['maintenance_operation_date'] = 'Operation date';
$lang['maintenance_operation_horametre'] = 'Recorded hour meter';
$lang['maintenance_operation_horametre_help'] = 'The remaining potential will restart at %s hours from this value.';
$lang['maintenance_operation_nouvelle_echeance'] = 'New due date (optional)';
$lang['maintenance_operation_echeance_help'] = 'Leave blank to compute automatically (+%s months from the operation date).';
$lang['maintenance_operation_commentaire'] = 'Overall remark';
$lang['maintenance_operation_compte_rendu'] = 'Paper report';
$lang['maintenance_operation_compte_rendu_help'] = 'Scan or photo of the report signed by the workshop (PDF or image), optional.';
$lang['maintenance_operation_document_existant'] = 'Report already uploaded';
$lang['maintenance_operation_taches'] = 'Program tasks';
$lang['maintenance_operation_aucune_tache'] = 'This program has no task.';
$lang['maintenance_operation_enregistrer'] = 'Save operation';
$lang['maintenance_operation_created'] = 'Operation recorded successfully. Potential updated.';
$lang['maintenance_operation_updated'] = 'Operation updated successfully. Potential recalculated.';

// Realisations
$lang['maintenance_realisation_fait'] = 'Done';
$lang['maintenance_realisation_non_fait'] = 'Not done';
$lang['maintenance_realisation_non_applicable'] = 'N/A';

// Service bulletins
$lang['maintenance_bulletins_title'] = 'Service bulletins';
$lang['maintenance_bulletins_deposer'] = 'Upload a bulletin';
$lang['maintenance_bulletins_selectionner_aeronef'] = 'Select an aircraft to see its service bulletins.';
$lang['maintenance_bulletins_aucun'] = 'No service bulletin for this aircraft.';
$lang['maintenance_bulletin_fichier'] = 'File';
$lang['maintenance_bulletin_depose_le'] = 'Uploaded on';
$lang['maintenance_bulletin_statut'] = 'Status';
$lang['maintenance_bulletin_statut_a_traiter'] = 'To process';
$lang['maintenance_bulletin_statut_traite'] = 'Processed';
$lang['maintenance_bulletin_statut_non_applicable'] = 'Not applicable';
$lang['maintenance_bulletin_upload_error'] = 'No valid file was uploaded.';
$lang['maintenance_bulletin_uploaded'] = 'Bulletin uploaded successfully.';
$lang['maintenance_bulletin_statut_invalide'] = 'Invalid status.';
$lang['maintenance_bulletin_statut_mis_a_jour'] = 'Bulletin status updated.';

// Airworthiness synthesis
$lang['maintenance_synthese_titre'] = 'Airworthiness synthesis';
$lang['maintenance_synthese_titre_aeronef'] = 'Airworthiness synthesis';
$lang['maintenance_synthese_aeronef'] = 'Aircraft';
$lang['maintenance_synthese_etat_global'] = 'Overall status';
$lang['maintenance_synthese_filtrer'] = 'Filter';
$lang['maintenance_synthese_aucun_aeronef'] = 'No aircraft for this section.';
$lang['maintenance_synthese_entite'] = 'Entity';
$lang['maintenance_synthese_programme'] = 'Program';
$lang['maintenance_synthese_aucun_dossier'] = 'No open dossier.';
$lang['maintenance_synthese_genere_le'] = 'Generated on';
$lang['maintenance_synthese_export_pdf'] = 'PDF export';
$lang['maintenance_synthese_echeance'] = 'Due date';
$lang['maintenance_synthese_potentiel'] = 'Remaining potential';

// Airworthiness states (shared across fleet view, aircraft view, PDF)
$lang['maintenance_etat_a_jour'] = 'Up to date';
$lang['maintenance_etat_echeance_proche'] = 'Due soon';
$lang['maintenance_etat_depasse'] = 'Overdue';

// Maintenance dashboard
$lang['maintenance_dashboard_title'] = 'Maintenance';
$lang['maintenance_operations_title'] = 'Maintenance operations';
$lang['maintenance_operations_aucune'] = 'No operation recorded.';
