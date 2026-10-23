<?php
/**
 * Nederlandse taal — Nieuwsbrief (veiligheidswacht)
 */

$lang['newsletter_title']          = 'Veiligheidsnieuwsbrief';
$lang['newsletter_archive_title']  = 'Newsletters';
$lang['newsletter_admin_title']    = 'Nieuwsbrief beheer';
$lang['newsletter_subscribers']    = 'Abonnees';

$lang['newsletter_subscribe_title']       = 'Abonneren op de nieuwsbrief';
$lang['newsletter_subscribe_intro']       = 'Ontvang onze veiligheids- en regelgevingsnieuwsbrieven per e-mail.';
$lang['newsletter_first_name']            = 'Voornaam';
$lang['newsletter_last_name']             = 'Achternaam';
$lang['newsletter_email']                 = 'E-mailadres';
$lang['newsletter_subscribe_btn']         = 'Abonneren';
$lang['newsletter_subscribe_success']     = 'Er is een bevestigingsmail verzonden naar %s. Klik op de link om uw abonnement te activeren.';
$lang['newsletter_already_subscribed']    = 'Dit adres is al geabonneerd.';
$lang['newsletter_subscribe_error']       = 'Er is een fout opgetreden. Probeer het opnieuw.';

$lang['newsletter_confirm_title']         = 'Abonnement bevestigd';
$lang['newsletter_confirm_success']       = 'Uw abonnement is bevestigd. U ontvangt toekomstige nieuwsbrieven per e-mail.';
$lang['newsletter_confirm_invalid']       = 'Deze bevestigingslink is ongeldig of is al gebruikt.';

$lang['newsletter_unsubscribe_title']     = 'Afmelden';
$lang['newsletter_unsubscribe_success']   = 'U bent afgemeld van de nieuwsbrief.';
$lang['newsletter_unsubscribe_invalid']   = 'Deze afmeldlink is ongeldig.';
$lang['newsletter_unsubscribe_btn']       = 'Afmelden';
$lang['newsletter_unsubscribe_confirm']   = 'Weet u zeker dat u zich wilt afmelden van de nieuwsbrief?';

$lang['newsletter_no_newsletters']        = 'Nog geen nieuwsbrieven beschikbaar.';
$lang['newsletter_period']                = 'Periode';
$lang['newsletter_published_at']          = 'Gepubliceerd op';
$lang['newsletter_open_pdf']              = 'Openen';

$lang['newsletter_no_subscribers']        = 'Geen abonnees.';
$lang['newsletter_subscriber_name']       = 'Naam';
$lang['newsletter_subscriber_email']      = 'E-mail';
$lang['newsletter_subscriber_confirmed']  = 'Bevestigd';
$lang['newsletter_subscriber_date']       = 'Abonnementsdatum';
$lang['newsletter_subscriber_status']     = 'Status';
$lang['newsletter_status_active']         = 'Actief';
$lang['newsletter_status_pending']        = 'In afwachting';
$lang['newsletter_status_unsubscribed']   = 'Afgemeld';
$lang['newsletter_delete_subscriber']     = 'Verwijderen';
$lang['newsletter_delete_confirm']        = 'Deze abonnee verwijderen?';
$lang['newsletter_delete_success']        = 'Abonnee verwijderd.';

$lang['newsletter_config_title']          = 'Configuratie regelgevingswacht';
$lang['newsletter_config_saved']          = 'Configuratie opgeslagen.';
$lang['newsletter_config_imap']           = 'Verzamelpostbus (IMAP)';
$lang['newsletter_config_smtp']           = 'Verzendadres (SMTP)';
$lang['newsletter_config_people']         = 'Revisoren en validatoren';
$lang['newsletter_config_paths']          = 'AI-agentpaden';
$lang['newsletter_config_imap_host']      = 'IMAP-server';
$lang['newsletter_config_imap_port']      = 'Poort';
$lang['newsletter_config_imap_user']      = 'Gebruiker';
$lang['newsletter_config_imap_password']  = 'Wachtwoord (leeg laten om huidig te bewaren)';
$lang['newsletter_config_imap_ssl']       = 'SSL/TLS';
$lang['newsletter_config_imap_mailbox']   = 'IMAP-map';
$lang['newsletter_config_smtp_from']      = 'Verzendadres';
$lang['newsletter_config_smtp_from_name'] = 'Weergavenaam';
$lang['newsletter_config_reviewers']      = 'Revisoren (e-mails gescheiden door komma\'s)';
$lang['newsletter_config_validators']     = 'Validatoren (e-mails gescheiden door komma\'s)';
$lang['newsletter_config_review_subject'] = 'Onderwerp van het revisie-e-mail';
$lang['newsletter_config_review_body']    = 'Tekst van het revisie-e-mail';
$lang['newsletter_config_context_path']   = 'Pad naar AI-contextbestand';
$lang['newsletter_config_output_path']    = 'AI-uitvoermap';
$lang['newsletter_config_alerts']         = 'Directe meldingen inschakelen';
$lang['newsletter_config_save']           = 'Opslaan';
$lang['newsletter_test_ai_title']         = 'AI-agenttest (Anthropic)';
$lang['newsletter_test_ai_prompt']        = 'Prompt';
$lang['newsletter_test_ai_model']         = 'Model';
$lang['newsletter_test_ai_max_tokens']    = 'Max tokens';
$lang['newsletter_test_ai_send']          = 'Verzenden naar Claude';
$lang['newsletter_test_ai_result']        = 'Antwoord';
$lang['newsletter_test_ai_no_key']        = 'Anthropic API-sleutel niet geconfigureerd.';
$lang['newsletter_config_anthropic_key']  = 'Anthropic API-sleutel';
$lang['newsletter_config_anthropic_model'] = 'Standaardmodel';
$lang['newsletter_test_email_title']      = 'E-mail verzendtest';
$lang['newsletter_test_imap_title']       = 'IMAP leestest';
$lang['newsletter_test_to']              = 'Verzenden naar';
$lang['newsletter_test_send']            = 'Test verzenden';
$lang['newsletter_test_run']             = 'Tests uitvoeren';
$lang['newsletter_test_since']           = 'E-mails sinds (formaat 01-Jan-2026)';
$lang['newsletter_test_pattern']         = 'Onderwerpfilter (optioneel)';
$lang['newsletter_test_ok']              = 'OK';
$lang['newsletter_test_fail']            = 'MISLUKT';
$lang['newsletter_publish_title']         = 'Nieuwsbrief publiceren';
$lang['newsletter_publish_period_start']  = 'Begin periode';
$lang['newsletter_publish_period_end']    = 'Einde periode';
$lang['newsletter_publish_newsletter_title'] = 'Titel van de nieuwsbrief';
$lang['newsletter_publish_pdf']           = 'PDF-bestand';
$lang['newsletter_publish_btn']           = 'Publiceren en verzenden naar abonnees';
$lang['newsletter_publish_success']       = 'Nieuwsbrief gepubliceerd en verzonden naar %d abonnee(s).';
$lang['newsletter_publish_error']         = 'Fout tijdens publicatie.';
$lang['newsletter_pdf_required']          = 'Een PDF-bestand is vereist.';
$lang['newsletter_pdf_invalid']           = 'Het bestand moet een PDF zijn.';
