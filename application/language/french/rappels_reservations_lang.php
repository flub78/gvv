<?php
/*
 * GVV French — Rappels réservations
 */

// Page "Mes réservations"
$lang['mes_reservations_title']          = "Mes futures réservations";
$lang['mes_reservations_no_resa']        = "Vous n'avez aucune réservation à venir.";
$lang['mes_reservations_col_date']       = "Date / Heure";
$lang['mes_reservations_col_aircraft']   = "Aéronef";
$lang['mes_reservations_col_role']       = "Rôle";
$lang['mes_reservations_col_status']     = "Type";
$lang['mes_reservations_col_actions']    = "Actions";
$lang['mes_reservations_role_pilot']     = "Pilote";
$lang['mes_reservations_role_instructor']= "Instructeur";
$lang['mes_reservations_btn_add']        = "Ajouter une réservation";
$lang['mes_reservations_btn_edit']       = "Modifier";
$lang['mes_reservations_btn_delete']     = "Supprimer";
$lang['mes_reservations_confirm_delete'] = "Supprimer cette réservation ?";
$lang['mes_reservations_deleted_ok']     = "Réservation supprimée.";
$lang['mes_reservations_deleted_error']  = "Erreur lors de la suppression.";
$lang['mes_reservations_not_found']      = "Réservation introuvable ou non autorisée.";

// Préférences de rappel
$lang['mes_reservations_prefs_title']    = "Mes préférences de rappel";
$lang['mes_reservations_prefs_channel']  = "Canal de notification";
$lang['mes_reservations_prefs_period']   = "Délai de rappel (heures avant le départ)";
$lang['mes_reservations_prefs_save']     = "Enregistrer mes préférences";
$lang['mes_reservations_prefs_ok']       = "Préférences enregistrées.";
$lang['mes_reservations_prefs_error']    = "Erreur lors de l'enregistrement des préférences.";
$lang['mes_reservations_channel_email']  = "Email seulement";
$lang['mes_reservations_channel_sms']    = "SMS seulement";
$lang['mes_reservations_channel_both']   = "Email + SMS";
$lang['mes_reservations_channel_none']   = "Aucune notification";

// Messages email — contenu
$lang['reminder_type_scheduled']   = "Rappel de réservation";
$lang['reminder_intro_scheduled']  = "Vous avez une réservation prévue prochainement.";
$lang['reminder_event_create']     = "Nouvelle réservation";
$lang['reminder_event_update']     = "Réservation modifiée";
$lang['reminder_event_cancel']     = "Réservation annulée";
$lang['reminder_intro_event']      = "Une réservation vous concerne.";
$lang['reminder_role_pilot']       = "Pilote";
$lang['reminder_role_instructor']  = "Instructeur";

// Libellés du tableau récapitulatif (email)
$lang['label_date_heure']      = "Date / heure";
$lang['label_aeronef']         = "Aéronef";
$lang['label_pilote']          = "Pilote";
$lang['label_instructeur']     = "Instructeur";
$lang['label_statut']          = "Statut";
$lang['label_votre_role']      = "Votre rôle";
$lang['label_type_message']    = "Type de message";
$lang['label_declenchement']   = "Déclenchement";
$lang['label_heure']           = "Heure";

// Sujets des emails de rappel
$lang['subject_rappel_reservation'] = "Rappel réservation";
$lang['subject_rappels_journee']    = "Rappels de réservation pour le";

// Titre du résumé quotidien et pied de page des emails
$lang['daily_summary_heading'] = "Rappels de réservation pour la journée du";
$lang['footer_auto_message']   = "Message automatique envoyé par %s – GVV. Ne pas répondre à cet email.";

// SMS
$lang['sms_rappel_vol']        = "Rappel vol";
$lang['sms_event_create']      = "Nouvelle";
$lang['sms_event_update']      = "Modif.";
$lang['sms_event_cancel']      = "Annulée";
$lang['sms_reservation_word']  = "réservation";
$lang['sms_role_label']        = "rôle";
$lang['sms_instr_label']       = "instr";
$lang['sms_multi_reservations']= "Vous avez %1\$d réservations pour la journée du %2\$s. Détails sur %3\$s";
$lang['connector_le']          = "le";

// Noms des jours de la semaine
$lang['jour_1'] = "Lundi";
$lang['jour_2'] = "Mardi";
$lang['jour_3'] = "Mercredi";
$lang['jour_4'] = "Jeudi";
$lang['jour_5'] = "Vendredi";
$lang['jour_6'] = "Samedi";
$lang['jour_7'] = "Dimanche";

// Page de test des rappels
$lang['test_rappel_titre']             = "Test des rappels de réservation";
$lang['test_rappel_form_title']        = "Paramètres du test";
$lang['test_rappel_label_reservation'] = "Réservation";
$lang['test_rappel_select_resa']       = "Sélectionner une réservation";
$lang['test_rappel_no_resa']           = "Aucune réservation disponible.";
$lang['test_rappel_label_canal']       = "Canal";
$lang['test_rappel_label_type']        = "Type de notification";
$lang['test_rappel_label_destinataire']= "Destinataire";
$lang['test_rappel_btn_envoyer']       = "Envoyer le rappel de test";
$lang['test_rappel_redirection_info']  = "Les messages sont redirigés vers :";
$lang['test_rappel_no_redirection']    = "Aucune redirection de test configurée — les messages seront envoyés aux vrais destinataires.";
