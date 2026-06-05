<?php
/*
 * GVV French translation - Reservations
 */

// Timeline labels
$lang['reservations_timeline_desc'] = "Réservations";

// Form labels
$lang['reservations_form_aircraft'] = "Avion";
$lang['reservations_form_pilot'] = "Pilote";
$lang['reservations_form_instructor'] = "Instructeur";
$lang['reservations_form_instructor_optional'] = "(optionnel)";
$lang['reservations_form_start_time'] = "Heure de début";
$lang['reservations_form_end_time'] = "Heure de fin";
$lang['reservations_form_purpose'] = "Objet";
$lang['reservations_form_notes'] = "Notes";
$lang['reservations_form_status'] = "Type de réservation";

// Select options
$lang['reservations_select_aircraft'] = "-- Sélectionner un avion --";
$lang['reservations_select_pilot'] = "-- Sélectionner un pilote --";
$lang['reservations_select_instructor_none'] = "-- Aucun --";

// Status options
$lang['reservations_status_maintenance'] = "Maintenance";
$lang['reservations_status_unavailable'] = "Indisponible";
$lang['reservations_status_vol_local'] = "Vol local";
$lang['reservations_status_navigation'] = "Navigation";
$lang['reservations_status_vld'] = "VLD";
$lang['reservations_status_convoyage'] = "Convoyage";

// Modal titles
$lang['reservations_modal_new'] = "Nouvelle Réservation";
$lang['reservations_modal_edit'] = "Modifier Réservation";

// Buttons
$lang['reservations_btn_create'] = "Créer Réservation";
$lang['reservations_btn_save'] = "Enregistrer";
$lang['reservations_btn_cancel'] = "Annuler";
$lang['reservations_btn_delete'] = "Supprimer";

// Validation messages
$lang['reservations_error_no_aircraft'] = "Veuillez sélectionner un avion";
$lang['reservations_error_no_pilot'] = "Veuillez sélectionner un pilote";
$lang['reservations_error_unknown'] = "Erreur inconnue";
$lang['reservations_error_saving'] = "Erreur lors de la sauvegarde";
$lang['reservations_error_deleting'] = "Erreur lors de la suppression";
$lang['reservations_error_prefix'] = "Erreur";
$lang['reservations_error_invalid_datetime'] = "Date ou heure invalide";
$lang['reservations_error_end_before_start'] = "L'heure de fin doit être postérieure à l'heure de début";
$lang['reservations_error_not_authorized'] = "Vous n'êtes pas autorisé à modifier cette réservation";
$lang['reservations_error_no_cotisation'] = "Vous n'avez pas de cotisation valide pour cette année";
$lang['reservations_error_insufficient_balance'] = "Solde insuffisant (%s). Coût total estimé de vos réservations : %s";
$lang['reservations_confirm_delete'] = "Êtes-vous sûr de vouloir supprimer cette réservation ?";

// Success messages
$lang['reservations_success_saved'] = "Réservation enregistrée avec succès";
$lang['reservations_success_deleted'] = "Réservation supprimée avec succès";

// Conflict messages
$lang['reservations_conflict_aircraft_conflict'] = "Cet avion est déjà réservé pour ce créneau horaire";
$lang['reservations_conflict_pilot_conflict'] = "Ce pilote a déjà une réservation pour ce créneau horaire";
$lang['reservations_conflict_instructor_conflict'] = "Cet instructeur a déjà une réservation pour ce créneau horaire";

/* End of file reservations_lang.php */
/* Location: ./application/language/french/reservations_lang.php */
