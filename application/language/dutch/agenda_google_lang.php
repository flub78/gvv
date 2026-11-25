<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Synchronisation des réservations avec Google Agenda
 */

$lang['agenda_google_titre'] = 'Google Agenda';
$lang['agenda_google_etat_active'] = 'Synchronisatie actief';
$lang['agenda_google_etat_suspendue'] = 'Synchronisatie opgeschort';
$lang['agenda_google_etat_inactive'] = 'Synchronisatie inactief';
$lang['agenda_google_explication'] = 'Koppel uw Google Agenda om uw reserveringen er automatisch in terug te vinden, als piloot en als instructeur. Ze worden bijgewerkt wanneer ze worden gewijzigd of geannuleerd, wie de wijziging ook aanbrengt.';
$lang['agenda_google_compte'] = 'Google-account';
$lang['agenda_google_derniere_maj'] = 'Laatste geslaagde bijwerking';
$lang['agenda_google_jamais'] = 'nog geen';
$lang['agenda_google_suspendue_texte'] = 'Uw agenda wordt niet meer bijgewerkt: GVV mag er niet meer in schrijven. Koppel uw Google Agenda opnieuw om de synchronisatie te hervatten.';
$lang['agenda_google_cause'] = 'Oorzaak:';
$lang['agenda_google_btn_activer'] = 'Mijn Google Agenda koppelen';
$lang['agenda_google_btn_relier_nouveau'] = 'Mijn agenda opnieuw koppelen';
$lang['agenda_google_btn_arreter'] = 'Synchronisatie stoppen';
$lang['agenda_google_arret_titre'] = 'Synchronisatie stoppen?';
$lang['agenda_google_arret_avertissement'] = 'De reserveringen die al in uw Google Agenda staan, blijven daar maar worden niet meer bijgewerkt. U kunt ze zelf in Google Agenda verwijderen. Uw reserveringen in GVV veranderen niet.';
$lang['agenda_google_annuler'] = 'Annuleren';
$lang['agenda_google_btn_confirmer_arret'] = 'Stoppen';
$lang['agenda_google_active_ok'] = 'Synchronisatie actief met Google-account %s.';
$lang['agenda_google_active_nombre'] = '%d komende reservering(en) worden de komende minuten aan uw agenda toegevoegd.';
$lang['agenda_google_arret_ok'] = 'Synchronisatie gestopt. De bestaande afspraken blijven in uw Google Agenda staan.';
$lang['agenda_google_arret_revocation'] = 'Google kon niet bevestigen dat de toegang is ingetrokken: u kunt die zelf intrekken in de beveiligingsinstellingen van uw Google-account (apps van derden).';
$lang['agenda_google_err_etat'] = 'De koppeling met Google is mislukt: het verzoek is verlopen of kwam niet van deze pagina. Begin opnieuw vanaf "Mijn reserveringen".';
$lang['agenda_google_err_refus'] = 'Synchronisatie niet geactiveerd: de toegang tot uw agenda is niet verleend op het scherm van Google. Begin opnieuw en vink de toegang tot de agenda aan.';
$lang['agenda_google_err_passagere'] = 'Synchronisatie niet geactiveerd: Google reageerde niet. Probeer het over enkele minuten opnieuw.';
$lang['agenda_google_err_activation'] = 'Synchronisatie niet geactiveerd: Google weigerde de koppeling. Begin opnieuw; als het probleem aanhoudt, verwittig de beheerder van de club.';
$lang['agenda_google_err_pas_active'] = 'De synchronisatie was niet actief: er is niets gewijzigd.';
$lang['agenda_google_indisponible'] = 'Synchronisatie met Google Agenda is niet beschikbaar voor uw account.';
$lang['agenda_google_evt_titre'] = 'Reservering %s';
$lang['agenda_google_evt_pilote'] = 'Piloot: %s';
$lang['agenda_google_evt_instructeur'] = 'Instructeur: %s';
$lang['agenda_google_evt_commentaire'] = 'Opmerking: %s';
$lang['agenda_google_evt_lien'] = 'Reservering in GVV: %s';
$lang['agenda_google_mail_suspension_sujet'] = 'Uw Google Agenda wordt niet meer bijgewerkt';
$lang['agenda_google_mail_suspension_corps'] = '<p>Hallo %1$s,</p><p>GVV mag niet meer in uw Google Agenda schrijven: uw reserveringen worden er niet meer bijgewerkt. Dit gebeurt wanneer de toegang van GVV in uw Google-account is ingetrokken, of wanneer het account of de agenda niet meer beschikbaar is.</p><p>Om de synchronisatie te hervatten, opent u <a href="%2$s">Mijn reserveringen</a> en koppelt u uw Google Agenda opnieuw.</p>';
$lang['agenda_google_retour_configuration'] = 'Configuratie';
$lang['agenda_google_liste_titre'] = 'Opgeschorte Google Agenda-synchronisaties';
$lang['agenda_google_liste_aide'] = 'Leden van wie de Google Agenda niet meer wordt bijgewerkt omdat de toestemming aan GVV niet meer geldig is. Elk betrokken lid is per e-mail verwittigd en moet zijn agenda opnieuw koppelen via "Mijn reserveringen".';
$lang['agenda_google_liste_vide'] = 'Geen opgeschorte synchronisatie.';
$lang['agenda_google_liste_module_inactif'] = 'Synchronisatie met Google Agenda is niet geactiveerd voor de club: er wordt geen agenda bijgewerkt.';
$lang['gvv_vue_agenda_google_suspendues_short_field_membre'] = 'Lid';
$lang['gvv_vue_agenda_google_suspendues_short_field_etat'] = 'Status';
$lang['gvv_vue_agenda_google_suspendues_short_field_cause_suspension'] = 'Oorzaak';
$lang['gvv_vue_agenda_google_suspendues_short_field_updated_at'] = 'Opgeschort op';
$lang['gvv_vue_agenda_google_suspendues_short_field_derniere_reussite_at'] = 'Laatste geslaagde bijwerking';
