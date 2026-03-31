<?php
$lang['gvv_bar_title']                   = "Mijn bar rekening betalen";
$lang['gvv_bar_intro']                   = "Geef uw bar consumptie op en debiteer uw pilootsaldo direct.";
$lang['gvv_bar_label_solde']             = "Uw huidige saldo";
$lang['gvv_bar_label_section']           = "Sectie";
$lang['gvv_bar_montant']                 = "Bedrag";
$lang['gvv_bar_montant_help']            = "Minimumbedrag: € 1 (alleen gehele euro's)";
$lang['gvv_bar_description']             = "Omschrijving van consumptie";
$lang['gvv_bar_description_placeholder'] = "Bijv.: 2 koffies, 1 broodje – 28/03/2026";
$lang['gvv_bar_description_help']        = "Beschrijf wat u heeft geconsumeerd (verplicht).";
$lang['gvv_bar_button_valider']          = "Betaling bevestigen";
$lang['gvv_bar_button_link']             = "Mijn bar rekening betalen";
$lang['gvv_provision_button_link']       = "Mijn account online opwaarderen";
$lang['gvv_bar_success']                 = "Betaling van € %s geregistreerd. Uw saldo is bijgewerkt.";
$lang['gvv_bar_error_section']           = "Selecteer een sectie voordat u een betaling doet.";
$lang['gvv_bar_error_no_bar']            = "Deze sectie heeft geen bar.";
$lang['gvv_bar_error_no_account']        = "De bar-opbrengstrekening is niet geconfigureerd voor deze sectie. Neem contact op met de beheerder.";
$lang['gvv_bar_error_no_pilot_account']  = "Uw pilootrekening is niet gevonden in deze sectie.";
$lang['gvv_bar_error_montant_min']       = "Het bedrag moet een geheel getal in euro's zijn, minimum € 1.";
$lang['gvv_bar_error_description']       = "Een omschrijving van uw consumptie is verplicht.";
$lang['gvv_bar_error_solde']             = "Onvoldoende saldo: u heeft € %.2f beschikbaar.";
$lang['gvv_bar_error_creation']          = "Er is een fout opgetreden bij het registreren. Probeer het opnieuw.";

// Bar per kaart — geauthenticeerde piloot (UC1)
$lang['gvv_bar_carte_title']             = "Mijn bar rekening betalen met kaart";
$lang['gvv_bar_carte_intro']             = "Betaal uw bar consumptie direct met bankkaart via HelloAsso.";
$lang['gvv_bar_carte_button_link']       = "Bar rekening betalen met kaart";
$lang['gvv_bar_carte_button_valider']    = "Betalen met kaart";
$lang['gvv_bar_carte_helloasso_notice']  = "U wordt doorgestuurd naar HelloAsso om de kaartbetaling te voltooien.";
$lang['gvv_bar_carte_error_disabled']    = "Online betalingen zijn niet ingeschakeld voor deze sectie. Neem contact op met de beheerder.";
$lang['gvv_bar_carte_error_checkout']    = "Kan HelloAsso betaling niet starten. Probeer het opnieuw of neem contact op met de beheerder.";

// Index / piloot transacties (EF6)
$lang['gvv_pel_index_title']         = "Mijn online betalingen";
$lang['gvv_pel_index_intro']         = "Geschiedenis van uw online betalingen (HelloAsso).";
$lang['gvv_pel_index_empty']         = "Geen betalingen geregistreerd.";
$lang['gvv_pel_col_date']            = "Datum";
$lang['gvv_pel_col_montant']         = "Bedrag";
$lang['gvv_pel_col_statut']          = "Status";
$lang['gvv_pel_col_plateforme']      = "Platform";
$lang['gvv_pel_statut_pending']      = "In afwachting";
$lang['gvv_pel_statut_completed']    = "Betaald";
$lang['gvv_pel_statut_failed']       = "Mislukt";
$lang['gvv_pel_statut_cancelled']    = "Geannuleerd";

// Bevestiging / annulering / fout (EF6)
$lang['gvv_pel_confirm_title']       = "Betaling bevestigd";
$lang['gvv_pel_confirm_intro']       = "Uw betaling is succesvol geregistreerd.";
$lang['gvv_pel_confirm_back']        = "Terug naar mijn rekening";
$lang['gvv_pel_cancel_title']        = "Betaling geannuleerd";
$lang['gvv_pel_cancel_intro']        = "U heeft de betaling geannuleerd. Er is geen bedrag afgeschreven.";
$lang['gvv_pel_cancel_back']         = "Terug naar mijn rekening";
$lang['gvv_pel_error_title']         = "Betalingsfout";
$lang['gvv_pel_error_intro']         = "Er is een fout opgetreden tijdens de betaling. Probeer het opnieuw of neem contact op met de beheerder.";
$lang['gvv_pel_error_back']          = "Terug naar mijn rekening";

// HelloAsso admin config (EF5)
$lang['gvv_admin_config_title']              = "Configuratie Online Betalingen (HelloAsso)";
$lang['gvv_admin_config_section']            = "Sectie";
$lang['gvv_admin_config_select_section']     = "— Selecteer een sectie —";
$lang['gvv_admin_config_helloasso_title']    = "HelloAsso Inloggegevens";
$lang['gvv_admin_config_client_id']         = "Client ID";
$lang['gvv_admin_config_client_secret']     = "Client Secret";
$lang['gvv_admin_config_secret_set']        = "● ingesteld (leeg laten om te behouden)";
$lang['gvv_admin_config_secret_empty']      = "niet ingesteld";
$lang['gvv_admin_config_secret_help']       = "Leeg laten om het huidige secret te behouden.";
$lang['gvv_admin_config_account_slug']      = "Organisatie Slug";
$lang['gvv_admin_config_slug_help']         = "Uw verenigingsidentificator in HelloAsso (bijv. aeroclub-de-xxx).";
$lang['gvv_admin_config_environment']       = "Omgeving";
$lang['gvv_admin_config_webhook_secret']    = "Webhook Secret";
$lang['gvv_admin_config_webhook_url']       = "Webhook URL (kopiëren naar HelloAsso)";
$lang['gvv_admin_config_webhook_url_help']  = "Kopieer deze URL naar uw HelloAsso-interface om betalingsbevestigingen te ontvangen.";
$lang['gvv_admin_config_test_btn']          = "Verbinding testen";
$lang['gvv_admin_config_test_ok']           = "HelloAsso-verbinding succesvol tot stand gebracht.";
$lang['gvv_admin_config_test_fail']         = "HelloAsso-verbinding mislukt. Controleer uw inloggegevens.";
$lang['gvv_admin_config_test_pending']      = "Testen…";
$lang['gvv_admin_config_test_error']        = "Netwerkfout tijdens de test.";
$lang['gvv_admin_config_bar_title']         = "Bar Configuratie";
$lang['gvv_admin_config_has_bar']           = "Deze sectie heeft een bar";
$lang['gvv_admin_config_bar_account']       = "Bar-opbrengstrekening (7xx)";
$lang['gvv_admin_config_bar_account_help']  = "Rekening gecrediteerd bij betaling van barconsumptie.";
$lang['gvv_admin_config_transaction_title'] = "Transactieparameters";
$lang['gvv_admin_config_compte_passage']    = "Doorlooprekening (HelloAsso debet)";
$lang['gvv_admin_config_compte_passage_help'] = "Rekening gedebiteerd bij HelloAsso-betalingen, in afwachting van platformoverboeking (bijv. 467).";
$lang['gvv_admin_config_montant_min']       = "Minimumbedrag (€)";
$lang['gvv_admin_config_montant_max']       = "Maximumbedrag (€)";
$lang['gvv_admin_config_enabled']           = "Online betalingen inschakelen voor deze sectie";
$lang['gvv_admin_config_enabled_help']      = "Indien uitgeschakeld, zijn online betalingsknoppen verborgen voor alle gebruikers.";
$lang['gvv_admin_config_saved']             = "Configuratie opgeslagen.";
$lang['gvv_admin_config_error_no_section']  = "Selecteer een sectie.";

// Pilotenrekening opladen (EF1)
$lang['gvv_provision_title']                = "Mijn pilootrekening opladen";
$lang['gvv_provision_intro']                = "Laad uw pilootrekening op met bankkaart via HelloAsso.";
$lang['gvv_provision_montant_help']         = "Bedrag tussen €\u{00A0}%s en €\u{00A0}%s.";
$lang['gvv_provision_montant_help_multi']   = "Kies een bedrag in veelvouden van € 100.";
$lang['gvv_provision_select_montant']       = "— Kies een bedrag —";
$lang['gvv_provision_error_montant_multiple'] = "Het bedrag moet een veelvoud van € 100 zijn.";
$lang['gvv_provision_button_valider']       = "Opladen met kaart";
$lang['gvv_provision_checkout_description'] = "Pilotenrekening opladen — %s";
$lang['gvv_provision_error_montant_min']    = "Het minimumbedrag is €\u{00A0}%s.";
$lang['gvv_provision_error_montant_max']    = "Het maximumbedrag is €\u{00A0}%s.";
$lang['gvv_provision_error_limit_day']      = "U heeft de limiet van 5 openstaande aanvragen per dag bereikt. Probeer het morgen opnieuw of neem contact op met de beheerder.";
$lang['gvv_button_cancel']                  = "Annuleren";

// Dashboard — Mes paiements
$lang['gvv_dashboard_payments_title']       = "Mijn betalingen";
$lang['gvv_dashboard_pay_cotisation']       = "Contributie betalen";
$lang['gvv_dashboard_pay_cotisation_sub']   = "Online verlenging";
$lang['gvv_dashboard_pay_section_active']   = "Actieve sectie: %s";
$lang['gvv_dashboard_pay_section_required'] = "Selecteer een actieve sectie";
$lang['gvv_dashboard_pay_bar']              = "Barrekening betalen";
$lang['gvv_dashboard_pay_bar_sub']          = "Saldo afschrijven of kaart";
$lang['gvv_dashboard_provision_account']    = "Mijn %s-rekening opladen (kaart)";
$lang['gvv_dashboard_provision_sub']        = "HelloAsso online betaling";

// Bar hub
$lang['gvv_bar_hub_title']                  = "Barrekening betalen";
$lang['gvv_bar_hub_intro']                  = "Kies uw betaalmethode voor barconsumptie.";
$lang['gvv_bar_hub_debit_title']            = "Rekening afschrijven";
$lang['gvv_bar_hub_debit_sub']              = "Bedrag aftrekken van uw beschikbaar pilotsaldo";
$lang['gvv_bar_hub_carte_title']            = "Online betaling (kaart)";
$lang['gvv_bar_hub_carte_sub']              = "Betalen met bankkaart via HelloAsso";
$lang['gvv_bar_hub_back']                   = "Terug";

// Contributie per kaart — penningmeester (UC6)
$lang['gvv_cotisation_helloasso_button']        = "Betalen per kaart (HelloAsso)";
$lang['gvv_cotisation_helloasso_error_user']    = "Piloot niet gevonden. Controleer de selectie.";
$lang['gvv_cotisation_helloasso_error_tx']      = "Fout bij aanmaken van de transactie. Probeer opnieuw.";
$lang['gvv_cotisation_helloasso_error_checkout']= "Kan HelloAsso-betaling niet starten. Probeer opnieuw of valideer handmatig.";
$lang['gvv_cotisation_qr_title']               = "Contributie betalen per kaart — HelloAsso";
$lang['gvv_cotisation_qr_intro']               = "Open de HelloAsso-link op dit toestel om met kaart te betalen.";
$lang['gvv_cotisation_qr_scan_title']          = "Scannen met smartphone";
$lang['gvv_cotisation_qr_scan_intro']          = "De piloot scant deze QR-code met zijn telefoon om direct te betalen.";
$lang['gvv_cotisation_qr_direct_title']        = "Betalen op dit scherm";
$lang['gvv_cotisation_qr_direct_intro']        = "Opent de HelloAsso-betaalpagina op dit toestel.";
$lang['gvv_cotisation_qr_direct_button']       = "HelloAsso openen";
$lang['gvv_cotisation_qr_back']                = "Terug naar contributieformulier";
$lang['gvv_cotisation_qr_url_missing']         = "Betaal-URL niet beschikbaar. Neem contact op met de beheerder.";

// Pilotenrekening opladen via penningmeester (UC7)
$lang['gvv_credit_tresorier_title']             = "Pilotenrekening opladen per kaart";
$lang['gvv_credit_tresorier_intro']             = "Selecteer de piloot en het bedrag, kies daarna de betaalwijze.";
$lang['gvv_credit_tresorier_button']            = "Betalen per kaart (HelloAsso)";
$lang['gvv_credit_tresorier_error_user']        = "Piloot niet gevonden. Controleer de selectie.";
$lang['gvv_credit_tresorier_error_tx']          = "Fout bij aanmaken van de transactie. Probeer opnieuw.";
$lang['gvv_credit_tresorier_error_checkout']    = "Kan HelloAsso-betaling niet starten. Probeer opnieuw of valideer handmatig.";
$lang['gvv_credit_tresorier_success']           = "Oplading van € %s geregistreerd voor piloot %s.";
$lang['gvv_credit_qr_title']                    = "Pilotenrekening opladen per kaart — HelloAsso";
$lang['gvv_credit_qr_intro']                    = "Gebruik directe betaling op dit toestel, of draag de invoer over aan de kaarthouder met de kleine QR-code.";
$lang['gvv_credit_qr_scan_title']               = "Overzetten naar telefoon";
$lang['gvv_credit_qr_scan_intro']               = "Kleine QR-code: de kaarthouder opent dezelfde HelloAsso-betaalpagina op zijn telefoon.";
$lang['gvv_credit_qr_scan_unnecessary']         = "De betaling is gestart door dezelfde gebruiker: QR-overdracht is niet nodig.";
$lang['gvv_credit_qr_direct_title']             = "Betalen op dit scherm";
$lang['gvv_credit_qr_direct_intro']             = "Opent de HelloAsso-betaalpagina op dit toestel.";
$lang['gvv_credit_qr_direct_button']            = "HelloAsso openen";
$lang['gvv_credit_qr_back']                     = "Terug naar opladingsformulier";
$lang['gvv_credit_qr_url_missing']              = "Betaal-URL niet beschikbaar. Neem contact op met de beheerder.";
$lang['gvv_credit_tresorier_menu']              = "Pilotenrekening opladen (kaart)";

// Trésorier lijst (EF4)
$lang['gvv_liste_title']                    = "Online betalingen";
$lang['gvv_liste_filter_from']              = "Van";
$lang['gvv_liste_filter_to']               = "Tot";
$lang['gvv_liste_filter_statut']            = "Status";
$lang['gvv_liste_filter_plateforme']        = "Platform";
$lang['gvv_liste_filter_section']           = "Sectie";
$lang['gvv_liste_filter_all']               = "Alle";
$lang['gvv_liste_filter_apply']             = "Filteren";
$lang['gvv_liste_filter_reset']             = "Resetten";
$lang['gvv_liste_stat_count']               = "Betaald deze maand";
$lang['gvv_liste_stat_total']               = "Totaalbedrag";
$lang['gvv_liste_stat_commissions']         = "Commissies";
$lang['gvv_liste_export_csv']               = "CSV exporteren";
$lang['gvv_liste_empty']                    = "Geen betalingen voor de geselecteerde periode.";
$lang['gvv_liste_col_pilote']               = "Piloot";
$lang['gvv_liste_col_commission']           = "Commissie";
$lang['gvv_liste_col_reference']            = "Referentie";
$lang['gvv_liste_col_section']              = "Sectie";
$lang['gvv_liste_menu']                     = "Online betalingen";

// Bar externe via QR Code — externe persoon zonder GVV account (UC2)
$lang['gvv_public_bar_title']              = "Mijn bar rekening betalen";
$lang['gvv_public_bar_intro']              = "Betaal uw bar consumptie via kaart via HelloAsso —";
$lang['gvv_public_bar_prenom']             = "Voornaam";
$lang['gvv_public_bar_nom']               = "Achternaam";
$lang['gvv_public_bar_email']             = "E-mail (optioneel)";
$lang['gvv_public_bar_email_placeholder'] = "uw@email.nl";
$lang['gvv_public_bar_email_help']        = "Om een betalingsbevestiging te ontvangen.";
$lang['gvv_public_bar_montant_help']      = "Minimumbedrag: € 2,00";
$lang['gvv_public_bar_button_valider']    = "Betalen met kaart";
$lang['gvv_public_bar_helloasso_notice']  = "U wordt doorgestuurd naar HelloAsso om de kaartbetaling te voltooien.";
$lang['gvv_public_bar_error_club']        = "Ontbrekende of ongeldige sectie-id. Gebruik de QR-code van de club.";
$lang['gvv_public_bar_error_no_bar']      = "Deze sectie heeft geen bar of online betaling is niet ingeschakeld.";
$lang['gvv_public_bar_error_disabled']    = "Online betalingen zijn niet ingeschakeld voor deze sectie.";
$lang['gvv_public_bar_error_nom']         = "Achternaam is verplicht.";
$lang['gvv_public_bar_error_prenom']      = "Voornaam is verplicht.";
$lang['gvv_public_bar_error_email']       = "Het e-mailadres is ongeldig.";
$lang['gvv_public_bar_error_montant_min'] = "Het minimumbedrag is € 2,00.";
$lang['gvv_public_bar_error_checkout']    = "Kan HelloAsso-betaling niet starten. Probeer het opnieuw.";
$lang['gvv_public_bar_confirm_title']     = "Betaling geregistreerd";
$lang['gvv_public_bar_confirm_intro']     = "Uw betaling is succesvol geregistreerd. Dank u!";
$lang['gvv_public_bar_confirm_section']   = "Sectie:";

// Bar QR-code affiche generatie — penningmeester
$lang['gvv_bar_qrcode_menu']                  = "Bar QR-code affiche genereren";
$lang['gvv_bar_qrcode_title']                 = "Genereer een bar QR-code affiche";
$lang['gvv_bar_qrcode_intro']                 = "Pas de affiche aan en genereer daarna een afdrukbare PDF voor barbetalingen van sectie";
$lang['gvv_bar_qrcode_label_title']           = "Titel";
$lang['gvv_bar_qrcode_label_text_top']        = "Tekst boven de QR-code";
$lang['gvv_bar_qrcode_label_text_bottom']     = "Tekst onder de QR-code";
$lang['gvv_bar_qrcode_label_url']             = "Betalings-URL gecodeerd in de QR-code";
$lang['gvv_bar_qrcode_button_generate_pdf']   = "PDF genereren";
$lang['gvv_bar_qrcode_error_title_required']  = "Titel is verplicht.";
$lang['gvv_bar_qrcode_default_title']         = "Betaling barconsumpties";
$lang['gvv_bar_qrcode_default_text_top']      = "Scan deze QR-code om uw barconsumpties met kaart te betalen.";
$lang['gvv_bar_qrcode_default_text_bottom']   = "Toon uw betalingsbevestiging aan de bar.";

// Lidmaatschap online — piloot (UC3)
$lang['gvv_cotisation_form_title']          = "Mijn lidmaatschap online betalen";
$lang['gvv_cotisation_form_intro']          = "Selecteer uw lidmaatschap en betaal via kaart via HelloAsso.";
$lang['gvv_cotisation_form_no_produits']    = "Geen lidmaatschap beschikbaar voor deze sectie. Neem contact op met uw penningmeester.";
$lang['gvv_cotisation_form_choose']         = "Kies uw lidmaatschap";
$lang['gvv_cotisation_form_button']         = "Betalen met kaart (HelloAsso)";
$lang['gvv_cotisation_helloasso_notice']    = "U wordt doorgestuurd naar HelloAsso om de kaartbetaling te voltooien.";
$lang['gvv_cotisation_error_produit']       = "Ongeldig of niet beschikbaar lidmaatschapsproduct.";
$lang['gvv_cotisation_error_already_paid']  = "U heeft uw lidmaatschap voor %d al betaald.";
$lang['gvv_cotisation_error_tx']            = "Fout bij aanmaken transactie. Probeer het opnieuw.";
$lang['gvv_cotisation_error_checkout']      = "Kan HelloAsso betaling niet starten. Probeer het opnieuw.";

// Discoveriebon via publieke link / QR (UC4)
$lang['gvv_decouverte_menu']                        = "Discoveriebon (kaartbetaling)";
$lang['gvv_decouverte_manager_title']               = "Discoveriebon - Betaling per kaart";
$lang['gvv_decouverte_manager_intro']               = "Genereer een publieke betaallink of QR-code voor een discoveriebon in sectie";
$lang['gvv_decouverte_product']                     = "Product";
$lang['gvv_decouverte_product_choose']              = "- Kies een discoverieproduct -";
$lang['gvv_decouverte_beneficiaire']                = "Begunstigde";
$lang['gvv_decouverte_de_la_part']                  = "Vanwege";
$lang['gvv_decouverte_email']                       = "E-mail begunstigde (optioneel)";
$lang['gvv_decouverte_email_help']                  = "Wordt gebruikt om na betaling een bevestiging te sturen.";
$lang['gvv_decouverte_helloasso_notice']            = "De begunstigde kan betalen door de QR-code te scannen of de HelloAsso-link te openen.";
$lang['gvv_decouverte_generate_button']             = "QR-code genereren";
$lang['gvv_decouverte_payer_cb_label']              = "Kaartbetaling";
$lang['gvv_decouverte_payer_cb_button']             = "Betalen per kaart (HelloAsso)";
$lang['gvv_decouverte_error_product']               = "Selecteer een geldig discoverieproduct.";
$lang['gvv_decouverte_error_beneficiaire']          = "Naam van de begunstigde is verplicht.";
$lang['gvv_decouverte_error_email']                 = "E-mailadres van de begunstigde is ongeldig.";
$lang['gvv_decouverte_error_amount']                = "Bedrag van het discoverieproduct is ongeldig.";
$lang['gvv_decouverte_error_tx']                    = "Fout bij aanmaken van discoverietransactie.";
$lang['gvv_decouverte_error_checkout']              = "Kan HelloAsso-betaling voor deze discoveriebon niet starten.";
$lang['gvv_decouverte_qr_title']                    = "Discoveriebon - HelloAsso kaartbetaling";
$lang['gvv_decouverte_qr_intro']                    = "Betaling kan op dit toestel gebeuren, of worden overgezet naar de kaarthouder via de kleine QR-code.";
$lang['gvv_decouverte_qr_scan_title']               = "Overzetten naar telefoon";
$lang['gvv_decouverte_qr_scan_intro']               = "Kleine QR-code: de klant opent dezelfde HelloAsso-betaalpagina op zijn telefoon.";
$lang['gvv_decouverte_qr_scan_unnecessary']         = "De betaling is gestart door dezelfde gebruiker: QR-overdracht is niet nodig.";
$lang['gvv_decouverte_qr_direct_title']             = "Betalen op dit scherm";
$lang['gvv_decouverte_qr_direct_intro']             = "Opent de HelloAsso-betaalpagina op dit toestel.";
$lang['gvv_decouverte_qr_direct_button']            = "HelloAsso openen";
$lang['gvv_decouverte_qr_url_missing']              = "Betaal-URL niet beschikbaar. Neem contact op met de beheerder.";
$lang['gvv_decouverte_qr_back']                     = "Terug naar discoveriebon formulier";
$lang['gvv_decouverte_public_confirm_title']        = "Betaling geregistreerd";
$lang['gvv_decouverte_public_confirm_intro']        = "Uw betaling voor de discoveriebon is succesvol geregistreerd.";
$lang['gvv_decouverte_email_subject']               = "Discoveriebon bevestigd";
