<?php
/**
 * Language file for acceptance system (Dutch)
 */

// Field labels
$lang['acceptance_title'] = 'Titel';
$lang['acceptance_category'] = 'Categorie';
$lang['acceptance_target_type'] = 'Doeltype';
$lang['acceptance_version_date'] = 'Versiedatum';
$lang['acceptance_mandatory'] = 'Verplichtingsniveau';
$lang['acceptance_mandatory_optional'] = 'Facultatief';
$lang['acceptance_mandatory_soft'] = 'Verplicht, niet blokkerend';
$lang['acceptance_mandatory_hard'] = 'Verplicht, blokkerend';
$lang['acceptance_deadline'] = 'Deadline';
$lang['acceptance_dual_validation'] = 'Dubbele validatie';
$lang['acceptance_role_1'] = 'Rol 1';
$lang['acceptance_role_2'] = 'Rol 2';
$lang['acceptance_target_roles'] = 'Doelrollen';
$lang['acceptance_target_user_login'] = 'Doelgebruiker';
$lang['acceptance_target_mode'] = 'Doelgroep';
$lang['acceptance_target_mode_roles'] = 'Categorieën';
$lang['acceptance_target_mode_user'] = 'Individuele gebruiker';
$lang['acceptance_active'] = 'Actief';
$lang['acceptance_created_by'] = 'Aangemaakt door';
$lang['acceptance_created_at'] = 'Aangemaakt op';
$lang['acceptance_updated_at'] = 'Gewijzigd op';
$lang['acceptance_status'] = 'Status';
$lang['acceptance_user'] = 'Gebruiker';
$lang['acceptance_external_name'] = 'Externe naam';
$lang['acceptance_validation_role'] = 'Validatierol';
$lang['acceptance_formula'] = 'Formule';
$lang['acceptance_acted_at'] = 'Actiedatum';
$lang['acceptance_initiated_by'] = 'Geïnitieerd door';
$lang['acceptance_signature_mode'] = 'Handtekeningmodus';
$lang['acceptance_linked_pilot'] = 'Gekoppelde piloot';
$lang['acceptance_linked_by'] = 'Gekoppeld door';
$lang['acceptance_linked_at'] = 'Gekoppeld op';
$lang['acceptance_signer_first_name'] = 'Voornaam ondertekenaar';
$lang['acceptance_signer_last_name'] = 'Achternaam ondertekenaar';
$lang['acceptance_signer_quality'] = 'Hoedanigheid';
$lang['acceptance_beneficiary_first_name'] = 'Voornaam begunstigde';
$lang['acceptance_beneficiary_last_name'] = 'Achternaam begunstigde';
$lang['acceptance_signature_type'] = 'Handtekeningtype';
$lang['acceptance_signed_at'] = 'Ondertekend op';
$lang['acceptance_pilot_attestation'] = 'Pilootattest';
$lang['acceptance_token'] = 'Token';
$lang['acceptance_mode'] = 'Modus';
$lang['acceptance_expires_at'] = 'Verloopt op';
$lang['acceptance_used'] = 'Gebruikt';
$lang['acceptance_used_at'] = 'Gebruikt op';
$lang['acceptance_item'] = 'Element';
$lang['acceptance_pdf_path'] = 'PDF-bestand';
$lang['acceptance_archived_document'] = 'Gearchiveerd document';

// Category enum values
$lang['acceptance_category_document'] = 'Document';
$lang['acceptance_category_formation'] = 'Opleiding';
$lang['acceptance_category_controle'] = 'Controle';
$lang['acceptance_category_briefing'] = 'Briefing';
$lang['acceptance_category_autorisation'] = 'Autorisatie';

// Target type enum values
$lang['acceptance_target_type_internal'] = 'Intern';
$lang['acceptance_target_type_external'] = 'Extern';

// Status enum values
$lang['acceptance_status_pending'] = 'In afwachting';
$lang['acceptance_status_accepted'] = 'Geaccepteerd';
$lang['acceptance_status_refused'] = 'Geweigerd';

// Signature mode enum values
$lang['acceptance_mode_direct'] = 'Direct';
$lang['acceptance_mode_link'] = 'Link';
$lang['acceptance_mode_qrcode'] = 'QR Code';
$lang['acceptance_mode_paper'] = 'Papier';

// Signature type enum values
$lang['acceptance_signature_tactile'] = 'Tactiel';
$lang['acceptance_signature_upload'] = 'Upload';

// Messages
$lang['acceptance_no_items'] = 'Geen elementen';
$lang['acceptance_no_records'] = 'Geen records';
$lang['acceptance_unknown_item'] = 'Onbekend element';
$lang['acceptance_unknown_record'] = 'Onbekend record';

// Admin interface
$lang['acceptance_admin_title'] = 'Acceptatiebeheer';
$lang['acceptance_admin_menu'] = 'Acceptaties';
$lang['acceptance_add_item'] = 'Nieuw element';
$lang['acceptance_choose_document'] = 'Dit document kiezen';
$lang['acceptance_add_item_for_document'] = 'Nieuwe acceptatieaanvraag voor dit document';
$lang['acceptance_filtered_by_document'] = 'Acceptaties gekoppeld aan document: %s';
$lang['acceptance_clear_document_filter'] = 'Alle acceptaties bekijken';
$lang['acceptance_edit_item'] = 'Element bewerken';
$lang['acceptance_tracking'] = 'Acceptatie-opvolging';
$lang['acceptance_edit'] = 'Bewerken';
$lang['acceptance_download_pdf'] = 'PDF downloaden';
$lang['acceptance_current_pdf'] = 'Huidig PDF';
$lang['acceptance_activate'] = 'Activeren';
$lang['acceptance_deactivate'] = 'Deactiveren';
$lang['acceptance_delete'] = 'Verwijderen';
$lang['acceptance_confirm_activate'] = 'Wilt u dit element activeren?';
$lang['acceptance_confirm_deactivate'] = 'Wilt u dit element deactiveren?';
$lang['acceptance_confirm_delete'] = 'Wilt u dit element echt verwijderen? Reeds geregistreerde acceptaties en weigeringen worden ook verwijderd. Deze actie is onomkeerbaar.';
$lang['acceptance_item_created'] = 'Element succesvol aangemaakt';
$lang['acceptance_item_updated'] = 'Element succesvol gewijzigd';
$lang['acceptance_item_activated'] = 'Element geactiveerd';
$lang['acceptance_item_deactivated'] = 'Element gedeactiveerd';
$lang['acceptance_item_deleted'] = 'Element verwijderd';
$lang['acceptance_item_not_found'] = 'Element niet gevonden';
$lang['acceptance_record_not_found'] = 'Record niet gevonden';
$lang['acceptance_pilot_linked'] = 'Acceptatie succesvol gekoppeld aan piloot';
$lang['acceptance_link_to_pilot'] = 'Koppelen aan piloot';
$lang['acceptance_back_to_list'] = 'Terug naar lijst';
$lang['acceptance_total'] = 'Totaal';
$lang['acceptance_linked'] = 'Gekoppeld';
$lang['acceptance_unlinked'] = 'Niet gekoppeld';
$lang['acceptance_link_status'] = 'Koppelingsstatus';
$lang['acceptance_overdue'] = 'Achterstallig';
$lang['acceptance_filter_all'] = 'Alle';
$lang['acceptance_yes'] = 'Ja';
$lang['acceptance_no'] = 'Nee';

// Form help texts
$lang['acceptance_pdf_help'] = 'Alleen PDF-formaat, maximaal 10 MB';
$lang['acceptance_archived_document_help'] = 'Dit document komt uit de documentarchiveringsmodule. Het kan niet worden vervangen vanuit dit formulier.';
$lang['acceptance_mandatory_help'] = 'Facultatief: vrije acceptatie, weigering of uitstel. Verplicht, niet blokkerend: het bijbehorende bericht van de dag kan niet worden verborgen totdat het is gevalideerd. Verplicht, blokkerend: bovendien is toegang tot GVV geblokkeerd totdat het element is gevalideerd (behalve uitloggen en de validatiepagina).';
$lang['acceptance_dual_validation_help'] = 'Vereist validatie door twee personen (bijv. instructeur en leerling)';
$lang['acceptance_role_1_placeholder'] = 'bijv. instructeur';
$lang['acceptance_role_2_placeholder'] = 'bijv. leerling';
$lang['acceptance_target_roles_placeholder'] = 'bijv. piloten, instructeurs, bestuur';
$lang['acceptance_target_roles_help'] = 'Vink een of meer rollen aan, in een specifieke sectie of alle secties. Leeg = alle leden.';
$lang['acceptance_target_user_help'] = 'Een item richt zich op een individuele gebruiker of op een of meer categorieën, nooit op beide.';
$lang['acceptance_select_document_help'] = 'Een nieuw te accepteren element moet verwijzen naar een reeds gearchiveerd document. Alle secties samen.';
$lang['acceptance_version_date_archived_help'] = 'Depotdatum van het gearchiveerde document, niet bewerkbaar vanuit dit formulier.';
$lang['acceptance_active_help'] = 'Alleen actieve elementen worden aan leden getoond';

// Error messages
$lang['acceptance_error_title_required'] = 'Titel is verplicht';
$lang['acceptance_error_category_required'] = 'Categorie is verplicht';
$lang['acceptance_error_create'] = 'Fout bij het aanmaken';
$lang['acceptance_error_directory'] = 'Kan opslagmap niet aanmaken';
$lang['acceptance_error_pilot_required'] = 'Selecteer een piloot';
$lang['acceptance_error_link'] = 'Fout bij het koppelen';
$lang['acceptance_archived_document_not_found'] = 'Gearchiveerd document niet gevonden';

// Member interface (Lot 4)
$lang['acceptance_menu_my_acceptances'] = 'Mijn acceptaties';
$lang['acceptance_dashboard_title'] = 'Te accepteren elementen';
$lang['acceptance_dashboard_intro'] = 'Dit zijn de elementen die uw validatie vereisen.';
$lang['acceptance_dashboard_empty'] = 'U heeft geen elementen die op acceptatie wachten.';
$lang['acceptance_dashboard_deadline'] = 'Te accepteren voor %s';
$lang['acceptance_btn_read_accept'] = 'Lezen en accepteren';
$lang['acceptance_btn_later'] = 'Later';
$lang['acceptance_btn_later_help'] = 'Dit element blijft in uw lijst staan, u kunt het later behandelen.';
$lang['acceptance_read_instruction'] = 'Lees het volledige document. De acceptatieknop verschijnt aan het einde.';
$lang['acceptance_btn_accept'] = 'Accepteren';
$lang['acceptance_btn_refuse'] = 'Weigeren';
$lang['acceptance_confirm_refuse'] = 'Wilt u dit element echt weigeren?';
$lang['acceptance_already_accepted'] = 'U heeft dit element geaccepteerd op %s.';
$lang['acceptance_already_refused'] = 'U heeft dit element geweigerd op %s.';
$lang['acceptance_accept_success'] = 'Acceptatie succesvol geregistreerd.';
$lang['acceptance_refuse_success'] = 'Weigering geregistreerd.';
$lang['acceptance_history_title'] = 'Mijn acceptatiegeschiedenis';
$lang['acceptance_history_empty'] = 'U heeft nog geen enkel element behandeld.';
$lang['acceptance_history_reread'] = 'Herlezen';
$lang['acceptance_back_to_list'] = 'Terug naar lijst';
$lang['acceptance_formula_member'] = 'Ik, ondergetekende %s, lid van de club geïdentificeerd door het systeem, erken kennis te hebben genomen van en accepteer %s op %s.';
$lang['acceptance_motd_title'] = 'Te accepteren: %s';
$lang['acceptance_motd_content'] = 'Een validatie is vereist: **%s**. [Lezen en accepteren](%s)';

/* End of file acceptance_lang.php */
/* Location: ./application/language/dutch/acceptance_lang.php */
