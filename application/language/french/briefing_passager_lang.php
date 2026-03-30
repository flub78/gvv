<?php
/**
 * Language file for passenger briefing (French)
 */

$lang['briefing_passager_title']              = 'Briefing passager';
$lang['briefing_passager_list_title']         = 'Briefings passagers';
$lang['briefing_passager_new']                = 'Nouveau briefing';
$lang['briefing_passager_upload']             = 'Déposer un document signé';
$lang['briefing_passager_generate_link']      = 'Générer un lien de signature numérique';
$lang['briefing_passager_view']               = 'Voir le briefing';
$lang['briefing_passager_replace']            = 'Remplacer le briefing';
$lang['briefing_passager_delete']             = 'Supprimer';
$lang['briefing_passager_export_pdf']         = 'Exporter en PDF';

$lang['briefing_passager_field_vld']          = 'Vol de découverte';
$lang['briefing_passager_field_date_vol']     = 'Date du vol';
$lang['briefing_passager_field_aerodrome']    = 'Aérodrome';
$lang['briefing_passager_field_appareil']     = 'Appareil';
$lang['briefing_passager_field_pilote']       = 'Pilote';
$lang['briefing_passager_field_nom']          = 'Nom';
$lang['briefing_passager_field_prenom']       = 'Prénom';
$lang['briefing_passager_field_naissance']    = 'Date de naissance';
$lang['briefing_passager_field_poids']        = 'Poids déclaré (kg)';
$lang['briefing_passager_field_urgence']      = 'Personne à prévenir en cas d\'accident';
$lang['briefing_passager_field_mode']         = 'Mode';
$lang['briefing_passager_field_date_sign']    = 'Date de signature';
$lang['briefing_passager_field_statut']       = 'Statut';

$lang['briefing_passager_mode_upload']        = 'Document scanné';
$lang['briefing_passager_mode_digital']       = 'Signature numérique';

$lang['briefing_passager_statut_present']     = 'Présent';
$lang['briefing_passager_statut_absent']      = 'Absent';

$lang['briefing_passager_search_vld']         = 'Rechercher un vol de découverte';
$lang['briefing_passager_search_placeholder'] = 'Nom, numéro de vol ou téléphone…';
$lang['briefing_passager_no_vld_found']       = 'Aucun vol de découverte trouvé';
$lang['briefing_passager_select_vld']         = 'Sélectionner un vol de découverte';

$lang['briefing_passager_upload_success']     = 'Briefing enregistré avec succès.';
$lang['briefing_passager_upload_error']       = 'Erreur lors de l\'enregistrement du briefing.';
$lang['briefing_passager_fields_required']    = 'Les champs suivants sont obligatoires : %s';
$lang['briefing_passager_already_exists']     = 'Un briefing existe déjà pour ce vol. Voulez-vous le remplacer ?';
$lang['briefing_passager_confirm_delete']     = 'Êtes-vous sûr de vouloir supprimer ce briefing ?';
$lang['briefing_passager_delete_protected']   = 'Ce briefing ne peut pas être supprimé avant 3 mois.';
$lang['briefing_passager_not_found']          = 'Briefing introuvable.';
$lang['briefing_passager_dir_error']          = 'Impossible de créer le répertoire de stockage.';
$lang['briefing_passager_type_error']         = 'Type de document briefing_passager introuvable.';

$lang['briefing_passager_sign_title']         = 'Déclaration d\'acceptation des risques';
$lang['briefing_passager_sign_scan_qr']       = 'Scannez pour ouvrir sur votre téléphone';
$lang['briefing_passager_sign_flight_info']   = 'Informations du vol';
$lang['briefing_passager_sign_instructions']  = 'Consignes de sécurité';
$lang['briefing_passager_sign_passenger']     = 'Vos informations';
$lang['briefing_passager_sign_acceptance']    = 'Déclaration';
$lang['briefing_passager_sign_checkbox']      = 'Je soussigné(e) atteste avoir pris connaissance des informations ci-dessus et accepte d\'effectuer le vol dans ces conditions.';
$lang['briefing_passager_sign_accept_required'] = 'Vous devez lire et accepter le document en cochant la case ou en signant avant de valider.';
$lang['briefing_passager_sign_submit']        = 'Valider et signer';
$lang['briefing_passager_sign_success']       = 'Votre déclaration a bien été enregistrée. Merci.';
$lang['briefing_passager_sign_already_done']  = 'Cette déclaration a déjà été signée.';
$lang['briefing_passager_sign_invalid_token'] = 'Lien invalide ou expiré.';
$lang['briefing_passager_sign_draw']          = 'Signez ici';
$lang['briefing_passager_sign_clear']         = 'Effacer la signature';

$lang['briefing_passager_no_consignes']       = 'Aucune consigne de sécurité disponible pour cette section.';
$lang['briefing_passager_consignes_download'] = 'Télécharger les consignes';

$lang['briefing_passager_filter_days']        = 'Période (jours)';
$lang['briefing_passager_filter_apply']       = 'Filtrer';
$lang['briefing_passager_no_briefings']       = 'Aucun briefing sur cette période.';
$lang['briefing_passager_menu']               = 'Briefings passagers';
$lang['briefing_passager_field_ddn']          = 'Date de naissance';
$lang['briefing_passager_link_title']         = 'Lien de signature numérique';
$lang['briefing_passager_link_generated']     = 'Le lien suivant a été généré. Montrez ce QRCode au passager ou envoyez-lui le lien.';
$lang['briefing_passager_link_copy']          = 'Copier le lien';
$lang['briefing_passager_link_open']          = 'Ouvrir le lien';
$lang['briefing_passager_sign_draw_pad']      = 'Signature tactile';
$lang['briefing_passager_sign_or']            = 'ou';
$lang['briefing_passager_sign_i_accept']      = 'J\'ai lu et j\'accepte';
$lang['briefing_passager_sign_optional']      = 'facultatif';
$lang['briefing_passager_sign_scroll_required'] = 'Veuillez faire défiler et lire l\'intégralité du document pour pouvoir valider.';
$lang['briefing_passager_pdf_title']          = 'Déclaration d\'acceptation des risques';
$lang['briefing_passager_email_subject']      = 'Votre déclaration de vol de découverte';
$lang['briefing_passager_email_body']         = 'Veuillez trouver ci-joint votre déclaration d\'acceptation des risques.';
$lang['briefing_passager_add']                = 'Ajouter un briefing';
$lang['briefing_passager_redirecting']        = 'Chargement…';

// Actions principales sur la page upload (simplification)
$lang['briefing_passager_action_download']      = 'Télécharger les consignes';
$lang['briefing_passager_action_download_help'] = 'Téléchargez le document PDF des consignes de sécurité.';
$lang['briefing_passager_action_sign']          = 'Lire et accepter';
$lang['briefing_passager_action_sign_help']     = 'Le passager lit les consignes et signe la déclaration sur cet écran ou sur son téléphone.';
$lang['briefing_passager_action_upload_help']   = 'Déposez un document signé scanné (photo ou PDF).';
$lang['briefing_passager_sign_qr_help']         = 'Scanner avec le téléphone du passager pour signer depuis son appareil.';

/* End of file briefing_passager_lang.php */
/* Location: ./application/language/french/briefing_passager_lang.php */
