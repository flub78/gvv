<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Synchronisation des réservations avec Google Agenda
 */

$lang['agenda_google_titre'] = 'Agenda Google';
$lang['agenda_google_etat_active'] = 'Synchronisation active';
$lang['agenda_google_etat_suspendue'] = 'Synchronisation suspendue';
$lang['agenda_google_etat_inactive'] = 'Synchronisation inactive';
$lang['agenda_google_explication'] = 'Reliez votre agenda Google pour y retrouver automatiquement vos réservations, comme pilote et comme instructeur. Elles sont mises à jour quand elles sont modifiées ou annulées, quelle que soit la personne qui fait le changement.';
$lang['agenda_google_compte'] = 'Compte Google';
$lang['agenda_google_derniere_maj'] = 'Dernière mise à jour réussie';
$lang['agenda_google_jamais'] = 'aucune pour l\'instant';
$lang['agenda_google_suspendue_texte'] = 'Votre agenda n\'est plus mis à jour : GVV n\'a plus l\'autorisation d\'y écrire. Reliez à nouveau votre agenda Google pour reprendre la synchronisation.';
$lang['agenda_google_cause'] = 'Cause :';
$lang['agenda_google_btn_activer'] = 'Relier mon agenda Google';
$lang['agenda_google_btn_relier_nouveau'] = 'Relier à nouveau mon agenda';
$lang['agenda_google_btn_arreter'] = 'Arrêter la synchronisation';
$lang['agenda_google_arret_titre'] = 'Arrêter la synchronisation ?';
$lang['agenda_google_arret_avertissement'] = 'Les réservations déjà présentes dans votre agenda Google y resteront, mais ne seront plus mises à jour. Vous pourrez les supprimer vous-même dans Google Agenda. Vos réservations dans GVV ne changent pas.';
$lang['agenda_google_annuler'] = 'Annuler';
$lang['agenda_google_btn_confirmer_arret'] = 'Arrêter';
$lang['agenda_google_active_ok'] = 'Synchronisation active avec le compte Google %s.';
$lang['agenda_google_active_nombre'] = '%d réservation(s) à venir vont être ajoutées à votre agenda dans les prochaines minutes.';
$lang['agenda_google_arret_ok'] = 'Synchronisation arrêtée. Les événements déjà présents restent dans votre agenda Google.';
$lang['agenda_google_arret_revocation'] = 'Google n\'a pas pu confirmer le retrait de l\'accès : vous pouvez le retirer vous-même dans les paramètres de sécurité de votre compte Google (applications tierces).';
$lang['agenda_google_err_etat'] = 'La liaison avec Google n\'a pas abouti : la demande a expiré ou ne vient pas de cette page. Recommencez depuis « Mes réservations ».';
$lang['agenda_google_err_refus'] = 'Synchronisation non activée : l\'accès à votre agenda n\'a pas été accordé sur l\'écran de Google. Recommencez en cochant l\'accès à l\'agenda.';
$lang['agenda_google_err_passagere'] = 'Synchronisation non activée : Google n\'a pas répondu. Réessayez dans quelques minutes.';
$lang['agenda_google_err_activation'] = 'Synchronisation non activée : Google a refusé la liaison. Recommencez ; si le problème persiste, prévenez l\'administrateur du club.';
$lang['agenda_google_err_pas_active'] = 'La synchronisation n\'était pas active : rien n\'a été modifié.';
$lang['agenda_google_indisponible'] = 'La synchronisation avec Google Agenda n\'est pas disponible pour votre compte.';
$lang['agenda_google_evt_titre'] = 'Réservation %s';
$lang['agenda_google_evt_pilote'] = 'Pilote : %s';
$lang['agenda_google_evt_instructeur'] = 'Instructeur : %s';
$lang['agenda_google_evt_commentaire'] = 'Commentaire : %s';
$lang['agenda_google_evt_lien'] = 'Réservation dans GVV : %s';
$lang['agenda_google_mail_suspension_sujet'] = 'Votre agenda Google n\'est plus mis à jour';
$lang['agenda_google_mail_suspension_corps'] = '<p>Bonjour %1$s,</p><p>GVV n\'a plus l\'autorisation d\'écrire dans votre agenda Google : vos réservations n\'y sont plus mises à jour. Cela arrive quand l\'accès de GVV a été retiré dans votre compte Google, ou quand le compte ou l\'agenda n\'est plus disponible.</p><p>Pour reprendre la synchronisation, ouvrez <a href="%2$s">Mes réservations</a> et reliez à nouveau votre agenda Google.</p>';
$lang['agenda_google_retour_configuration'] = 'Configuration';
$lang['agenda_google_liste_titre'] = 'Synchronisations Google Agenda suspendues';
$lang['agenda_google_liste_aide'] = 'Membres dont l\'agenda Google n\'est plus mis à jour parce que l\'autorisation donnée à GVV n\'est plus valable. Chaque membre concerné a été prévenu par e-mail et doit relier à nouveau son agenda depuis « Mes réservations ».';
$lang['agenda_google_liste_vide'] = 'Aucune synchronisation suspendue.';
$lang['agenda_google_liste_module_inactif'] = 'La synchronisation avec Google Agenda n\'est pas activée pour le club : aucun agenda n\'est mis à jour.';
$lang['gvv_vue_agenda_google_suspendues_short_field_membre'] = 'Membre';
$lang['gvv_vue_agenda_google_suspendues_short_field_etat'] = 'État';
$lang['gvv_vue_agenda_google_suspendues_short_field_cause_suspension'] = 'Cause';
$lang['gvv_vue_agenda_google_suspendues_short_field_updated_at'] = 'Suspendue le';
$lang['gvv_vue_agenda_google_suspendues_short_field_derniere_reussite_at'] = 'Dernière mise à jour réussie';
