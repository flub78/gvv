<?php
$lang['gvv_bar_title']                   = "Régler mes consommations de bar";
$lang['gvv_bar_intro']                   = "Déclarez vos consommations et débitez votre solde pilote directement.";
$lang['gvv_bar_label_solde']             = "Votre solde actuel";
$lang['gvv_bar_label_section']           = "Section";
$lang['gvv_bar_montant']                 = "Montant";
$lang['gvv_bar_montant_help']            = "Montant minimum : 1 € (entier en euros)";
$lang['gvv_bar_description']             = "Descriptif des consommations";
$lang['gvv_bar_description_placeholder'] = "Ex : 2 cafés, 1 sandwich – 28/03/2026";
$lang['gvv_bar_description_help']        = "Décrivez ce que vous avez consommé (obligatoire).";
$lang['gvv_bar_button_valider']          = "Confirmer le paiement";
$lang['gvv_bar_button_link']             = "Régler mes consommations de bar";
$lang['gvv_provision_button_link']       = "Provisionner mon compte en ligne";
$lang['gvv_bar_success']                 = "Paiement de %s € enregistré. Votre solde a été mis à jour.";
$lang['gvv_bar_error_section']           = "Veuillez sélectionner une section avant d'effectuer un paiement.";
$lang['gvv_bar_error_no_bar']            = "Cette section ne dispose pas d'un bar.";
$lang['gvv_bar_error_no_account']        = "Le compte de recette bar n'est pas configuré pour cette section. Contactez l'administrateur.";
$lang['gvv_bar_error_no_pilot_account']  = "Votre compte pilote est introuvable dans cette section.";
$lang['gvv_bar_error_montant_min']       = "Le montant doit être un entier en euros, minimum 1 €.";
$lang['gvv_bar_error_description']       = "Le descriptif des consommations est obligatoire.";
$lang['gvv_bar_error_solde']             = "Solde insuffisant : vous avez %.2f € disponibles.";
$lang['gvv_bar_error_creation']          = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";

// Bar par carte — pilote authentifié (UC1)
$lang['gvv_bar_carte_title']             = "Régler mes consommations de bar par carte";
$lang['gvv_bar_carte_intro']             = "Payez vos consommations de bar directement par carte bancaire via HelloAsso.";
$lang['gvv_bar_carte_button_link']       = "Régler mes consommations par carte";
$lang['gvv_bar_carte_button_valider']    = "Payer par carte";
$lang['gvv_bar_carte_helloasso_notice']  = "Vous allez être redirigé vers HelloAsso pour effectuer le paiement par carte bancaire.";
$lang['gvv_bar_carte_error_section']     = "Aucune section active trouvée dans votre session. Veuillez vous reconnecter.";
$lang['gvv_bar_carte_error_disabled']    = "Les paiements en ligne ne sont pas activés pour cette section. Contactez l'administrateur.";
$lang['gvv_bar_carte_error_checkout']    = "Impossible d'initier le paiement HelloAsso. Veuillez réessayer ou contacter l'administrateur.";

// Index / transactions pilote (EF6)
$lang['gvv_pel_index_title']         = "Mes paiements en ligne";
$lang['gvv_pel_index_intro']         = "Historique de vos paiements en ligne (HelloAsso).";
$lang['gvv_pel_index_empty']         = "Aucun paiement enregistré.";
$lang['gvv_pel_col_date']            = "Date";
$lang['gvv_pel_col_montant']         = "Montant";
$lang['gvv_pel_col_statut']          = "Statut";
$lang['gvv_pel_col_plateforme']      = "Plateforme";
$lang['gvv_pel_statut_pending']      = "En attente";
$lang['gvv_pel_statut_completed']    = "Réglé";
$lang['gvv_pel_statut_failed']       = "Échec";
$lang['gvv_pel_statut_cancelled']    = "Annulé";

// Confirmation / annulation / erreur (EF6)
$lang['gvv_pel_confirm_title']       = "Paiement confirmé";
$lang['gvv_pel_confirm_intro']       = "Votre paiement a bien été enregistré.";
$lang['gvv_pel_confirm_back']        = "Retour à mon compte";
$lang['gvv_pel_cancel_title']        = "Paiement annulé";
$lang['gvv_pel_cancel_intro']        = "Vous avez annulé le paiement. Aucun montant n'a été débité.";
$lang['gvv_pel_cancel_back']         = "Retour à mon compte";
$lang['gvv_pel_error_title']         = "Erreur de paiement";
$lang['gvv_pel_error_intro']         = "Une erreur est survenue lors du paiement. Veuillez réessayer ou contacter l'administrateur.";
$lang['gvv_pel_error_back']          = "Retour à mon compte";

// Config admin HelloAsso (EF5)
$lang['gvv_admin_config_title']              = "Configuration Paiements en Ligne (HelloAsso)";
$lang['gvv_admin_config_section']            = "Section";
$lang['gvv_admin_config_select_section']     = "— Choisir une section —";
$lang['gvv_admin_config_helloasso_title']    = "Crédentiels HelloAsso";
$lang['gvv_admin_config_client_id']         = "Client ID";
$lang['gvv_admin_config_client_secret']     = "Client Secret";
$lang['gvv_admin_config_secret_set']        = "● défini (laisser vide pour conserver)";
$lang['gvv_admin_config_secret_empty']      = "non défini";
$lang['gvv_admin_config_secret_help']       = "Laisser vide pour conserver le secret actuel.";
$lang['gvv_admin_config_account_slug']      = "Slug de l'organisation";
$lang['gvv_admin_config_slug_help']         = "Identifiant de votre association dans HelloAsso (ex: aeroclub-de-xxx).";
$lang['gvv_admin_config_environment']       = "Environnement";
$lang['gvv_admin_config_webhook_secret']    = "Secret Webhook";
$lang['gvv_admin_config_webhook_url']       = "URL Webhook (à copier dans HelloAsso)";
$lang['gvv_admin_config_webhook_url_help']  = "Copiez cette URL dans votre interface HelloAsso pour recevoir les confirmations de paiement.";
$lang['gvv_admin_config_test_btn']          = "Tester la connexion";
$lang['gvv_admin_config_test_ok']           = "Connexion HelloAsso établie avec succès.";
$lang['gvv_admin_config_test_fail']         = "Échec de la connexion HelloAsso. Vérifiez les crédentiels.";
$lang['gvv_admin_config_test_pending']      = "Test en cours…";
$lang['gvv_admin_config_test_error']        = "Erreur réseau lors du test.";
$lang['gvv_admin_config_bar_title']         = "Configuration Bar";
$lang['gvv_admin_config_has_bar']           = "Cette section dispose d'un bar";
$lang['gvv_admin_config_bar_account']       = "Compte de recette bar (7xx)";
$lang['gvv_admin_config_bar_account_help']  = "Compte crédité lors des règlements de consommations de bar.";
$lang['gvv_admin_config_transaction_title'] = "Paramètres de transaction";
$lang['gvv_admin_config_compte_passage']    = "Compte de passage (débit HelloAsso)";
$lang['gvv_admin_config_compte_passage_help'] = "Compte débité lors d'un paiement HelloAsso, en attente du virement de la plateforme (ex: 467).";
$lang['gvv_admin_config_montant_min']       = "Montant minimum (€)";
$lang['gvv_admin_config_montant_max']       = "Montant maximum (€)";
$lang['gvv_admin_config_enabled']           = "Activer les paiements en ligne pour cette section";
$lang['gvv_admin_config_enabled_help']      = "Si désactivé, les boutons de paiement en ligne sont masqués pour tous les utilisateurs.";
$lang['gvv_admin_config_saved']             = "Configuration enregistrée.";
$lang['gvv_admin_config_error_no_section']  = "Veuillez sélectionner une section.";

// Provisionnement compte pilote (EF1)
$lang['gvv_provision_title']                = "Approvisionner mon compte pilote";
$lang['gvv_provision_intro']                = "Rechargez votre compte pilote par carte bancaire via HelloAsso.";
$lang['gvv_provision_montant_help']         = "Montant entre %s\u{00A0}€ et %s\u{00A0}€.";
$lang['gvv_provision_montant_help_multi']   = "Choisissez un montant multiple de 100 €.";
$lang['gvv_provision_select_montant']       = "— Choisissez un montant —";
$lang['gvv_provision_error_montant_multiple'] = "Le montant doit être un multiple de 100 €.";
$lang['gvv_provision_button_valider']       = "Approvisionner par carte";
$lang['gvv_provision_checkout_description'] = "Provisionnement compte pilote — %s";
$lang['gvv_provision_error_montant_min']    = "Le montant minimum est de %s\u{00A0}€.";
$lang['gvv_provision_error_montant_max']    = "Le montant maximum est de %s\u{00A0}€.";
$lang['gvv_provision_error_limit_day']      = "Vous avez atteint la limite de 5 demandes en attente par jour. Réessayez demain ou contactez l'administrateur.";
$lang['gvv_button_cancel']                  = "Annuler";

// Dashboard — section Mes paiements
$lang['gvv_dashboard_payments_title']       = "Mes paiements";
$lang['gvv_dashboard_pay_cotisation']       = "Payer ma cotisation";
$lang['gvv_dashboard_pay_cotisation_sub']   = "Renouvellement en ligne";
$lang['gvv_dashboard_pay_section_active']   = "Section active : %s";
$lang['gvv_dashboard_pay_section_required'] = "Choisissez une section active";
$lang['gvv_dashboard_pay_bar']              = "Payer mes notes de bar";
$lang['gvv_dashboard_pay_bar_sub']          = "Débit solde ou carte";
$lang['gvv_dashboard_provision_account']    = "Approvisionner mon compte %s (CB)";
$lang['gvv_dashboard_provision_sub']        = "Paiement en ligne HelloAsso";

// Bar hub
$lang['gvv_bar_hub_title']                  = "Payer mes notes de bar";
$lang['gvv_bar_hub_intro']                  = "Choisissez votre mode de règlement pour vos consommations de bar.";
$lang['gvv_bar_hub_debit_title']            = "Débiter mon compte";
$lang['gvv_bar_hub_debit_sub']              = "Déduire le montant de votre solde pilote disponible";
$lang['gvv_bar_hub_carte_title']            = "Paiement en ligne (CB)";
$lang['gvv_bar_hub_carte_sub']              = "Payer par carte bancaire via HelloAsso";
$lang['gvv_bar_hub_back']                   = "Retour";

// Cotisation trésorier par carte (UC6)
$lang['gvv_cotisation_helloasso_button']        = "Payer par carte (HelloAsso)";
$lang['gvv_cotisation_helloasso_error_user']    = "Pilote introuvable. Veuillez vérifier la sélection.";
$lang['gvv_cotisation_helloasso_error_tx']      = "Erreur lors de la création de la transaction. Veuillez réessayer.";
$lang['gvv_cotisation_helloasso_error_checkout']= "Impossible d'initier le paiement HelloAsso. Veuillez réessayer ou valider manuellement.";
$lang['gvv_cotisation_qr_title']               = "Paiement cotisation par carte — HelloAsso";
$lang['gvv_cotisation_qr_intro']               = "Ouvrez le lien HelloAsso sur ce poste pour procéder au paiement par carte.";
$lang['gvv_cotisation_qr_scan_title']          = "Scanner avec le téléphone";
$lang['gvv_cotisation_qr_scan_intro']          = "Le pilote scanne ce QR code avec son smartphone pour payer directement.";
$lang['gvv_cotisation_qr_direct_title']        = "Payer sur cet écran";
$lang['gvv_cotisation_qr_direct_intro']        = "Ouvre la page de paiement HelloAsso sur ce poste.";
$lang['gvv_cotisation_qr_direct_button']       = "Ouvrir HelloAsso";
$lang['gvv_cotisation_qr_back']                = "Retour au formulaire cotisation";
$lang['gvv_cotisation_qr_url_missing']         = "URL de paiement non disponible. Contactez l'administrateur.";

// Approvisionnement compte pilote par CB — trésorier (UC7)
$lang['gvv_credit_tresorier_title']             = "Approvisionner un compte pilote par carte";
$lang['gvv_credit_tresorier_intro']             = "Sélectionnez le pilote et le montant, puis choisissez le mode de règlement.";
$lang['gvv_credit_tresorier_button']            = "Payer par carte (HelloAsso)";
$lang['gvv_credit_tresorier_error_user']        = "Pilote introuvable. Veuillez vérifier la sélection.";
$lang['gvv_credit_tresorier_error_tx']          = "Erreur lors de la création de la transaction. Veuillez réessayer.";
$lang['gvv_credit_tresorier_error_checkout']    = "Impossible d'initier le paiement HelloAsso. Veuillez réessayer ou valider manuellement.";
$lang['gvv_credit_tresorier_success']           = "Provisionnement de %s € enregistré pour le pilote %s.";
$lang['gvv_credit_qr_title']                    = "Approvisionner compte pilote par carte — HelloAsso";
$lang['gvv_credit_qr_intro']                    = "Utilisez le bouton de paiement sur ce poste, ou transférez la saisie au porteur de la carte via le petit QR code.";
$lang['gvv_credit_qr_scan_title']               = "Transférer vers téléphone";
$lang['gvv_credit_qr_scan_intro']               = "Petit QR code: le porteur de la carte ouvre la même page HelloAsso sur son téléphone.";
$lang['gvv_credit_qr_scan_unnecessary']         = "Le paiement est initié par le même utilisateur: le transfert par QR code est inutile.";
$lang['gvv_credit_qr_direct_title']             = "Payer sur cet écran";
$lang['gvv_credit_qr_direct_intro']             = "Ouvre la page de paiement HelloAsso sur ce poste.";
$lang['gvv_credit_qr_direct_button']            = "Ouvrir HelloAsso";
$lang['gvv_credit_qr_back']                     = "Retour au formulaire approvisionnement";
$lang['gvv_credit_qr_url_missing']              = "URL de paiement non disponible. Contactez l'administrateur.";
$lang['gvv_credit_tresorier_menu']              = "Approvisionner compte pilote (CB)";

// Cotisation en ligne — pilote (UC3)
$lang['gvv_cotisation_form_title']          = "Payer ma cotisation en ligne";
$lang['gvv_cotisation_form_intro']          = "Sélectionnez votre cotisation et payez par carte bancaire via HelloAsso.";
$lang['gvv_cotisation_form_no_produits']    = "Aucune cotisation disponible pour cette section. Contactez votre trésorier.";
$lang['gvv_cotisation_form_choose']         = "Choisissez votre cotisation";
$lang['gvv_cotisation_form_button']         = "Payer par carte (HelloAsso)";
$lang['gvv_cotisation_helloasso_notice']    = "Vous allez être redirigé vers HelloAsso pour effectuer le paiement par carte bancaire.";
$lang['gvv_cotisation_error_produit']       = "Produit de cotisation invalide ou non disponible.";
$lang['gvv_cotisation_error_already_paid']  = "Vous avez déjà réglé votre cotisation pour l'année %d.";
$lang['gvv_cotisation_error_tx']            = "Erreur lors de la création de la transaction. Veuillez réessayer.";
$lang['gvv_cotisation_error_checkout']      = "Impossible d'initier le paiement HelloAsso. Veuillez réessayer.";

// Bon decouverte via lien / QR public (UC4)
$lang['gvv_decouverte_menu']                        = "Bon découverte (paiement carte)";
$lang['gvv_decouverte_manager_title']               = "Bon découverte - Paiement par carte";
$lang['gvv_decouverte_manager_intro']               = "Générez un lien de paiement public ou un QR code pour un bon découverte dans la section";
$lang['gvv_decouverte_product']                     = "Produit";
$lang['gvv_decouverte_product_choose']              = "- Choisir un produit découverte -";
$lang['gvv_decouverte_beneficiaire']                = "Bénéficiaire";
$lang['gvv_decouverte_de_la_part']                  = "De la part de";
$lang['gvv_decouverte_email']                       = "Email du bénéficiaire (optionnel)";
$lang['gvv_decouverte_email_help']                  = "Utilisé pour envoyer la confirmation du bon après paiement.";
$lang['gvv_decouverte_helloasso_notice']            = "Le bénéficiaire pourra payer en scannant le QR code ou en ouvrant le lien HelloAsso.";
$lang['gvv_decouverte_generate_button']             = "Générer le QR code";
$lang['gvv_decouverte_payer_cb_label']              = "Paiement carte";
$lang['gvv_decouverte_payer_cb_button']             = "Payer par CB (HelloAsso)";
$lang['gvv_decouverte_error_product']               = "Veuillez sélectionner un produit découverte valide.";
$lang['gvv_decouverte_error_beneficiaire']          = "Le nom du bénéficiaire est obligatoire.";
$lang['gvv_decouverte_error_email']                 = "L'adresse email du bénéficiaire est invalide.";
$lang['gvv_decouverte_error_amount']                = "Le montant du produit découverte est invalide.";
$lang['gvv_decouverte_error_tx']                    = "Erreur lors de la création de la transaction découverte.";
$lang['gvv_decouverte_error_checkout']              = "Impossible d'initier le paiement HelloAsso pour ce bon découverte.";
$lang['gvv_decouverte_qr_title']                    = "Bon découverte - Paiement par carte HelloAsso";
$lang['gvv_decouverte_qr_intro']                    = "Le paiement peut être fait sur ce poste, ou transféré au porteur de la carte avec le petit QR code.";
$lang['gvv_decouverte_qr_scan_title']               = "Transférer vers téléphone";
$lang['gvv_decouverte_qr_scan_intro']               = "Petit QR code: le client ouvre la même page HelloAsso sur son téléphone.";
$lang['gvv_decouverte_qr_scan_unnecessary']         = "Le paiement est initié par le même utilisateur: le transfert par QR code est inutile.";
$lang['gvv_decouverte_qr_direct_title']             = "Paiement sur cet écran";
$lang['gvv_decouverte_qr_direct_intro']             = "Ouvre la page de paiement HelloAsso sur cet appareil.";
$lang['gvv_decouverte_qr_direct_button']            = "Ouvrir HelloAsso";
$lang['gvv_decouverte_qr_url_missing']              = "URL de paiement indisponible. Contactez l'administrateur.";
$lang['gvv_decouverte_qr_back']                     = "Retour au formulaire bon découverte";
$lang['gvv_decouverte_public_confirm_title']        = "Paiement enregistré";
$lang['gvv_decouverte_public_confirm_intro']        = "Votre paiement du bon découverte a été enregistré avec succès.";
$lang['gvv_decouverte_email_subject']               = "Bon découverte confirmé";
// Bar externe via QR Code — personne sans compte GVV (UC2)
$lang['gvv_public_bar_title']              = "Régler mes consommations de bar";
$lang['gvv_public_bar_intro']              = "Payez vos consommations de bar par carte bancaire via HelloAsso —";
$lang['gvv_public_bar_prenom']             = "Prénom";
$lang['gvv_public_bar_nom']               = "Nom";
$lang['gvv_public_bar_email']             = "Email (optionnel)";
$lang['gvv_public_bar_email_placeholder'] = "votre@email.fr";
$lang['gvv_public_bar_email_help']        = "Pour recevoir une confirmation de paiement.";
$lang['gvv_public_bar_montant_help']      = "Montant minimum : 2,00 €";
$lang['gvv_public_bar_button_valider']    = "Payer par carte";
$lang['gvv_public_bar_helloasso_notice']  = "Vous allez être redirigé vers HelloAsso pour effectuer le paiement par carte bancaire.";
$lang['gvv_public_bar_error_club']        = "Identifiant de section manquant ou invalide. Veuillez utiliser le QR code fourni par le club.";
$lang['gvv_public_bar_error_no_bar']      = "Cette section ne dispose pas d'un bar ou le paiement en ligne n'est pas activé.";
$lang['gvv_public_bar_error_disabled']    = "Les paiements en ligne ne sont pas activés pour cette section.";
$lang['gvv_public_bar_error_nom']         = "Le nom est obligatoire.";
$lang['gvv_public_bar_error_prenom']      = "Le prénom est obligatoire.";
$lang['gvv_public_bar_error_email']       = "L'adresse email n'est pas valide.";
$lang['gvv_public_bar_error_montant_min'] = "Le montant minimum est de 2,00 €.";
$lang['gvv_public_bar_error_checkout']    = "Impossible d'initier le paiement HelloAsso. Veuillez réessayer.";
$lang['gvv_public_bar_confirm_title']     = "Paiement enregistré";
$lang['gvv_public_bar_confirm_intro']     = "Votre règlement a bien été pris en compte. Merci !";
$lang['gvv_public_bar_confirm_section']   = "Section :";

// Génération affiche QR code bar — trésorier
$lang['gvv_bar_qrcode_menu']                  = "Générer affiche QR code bar";
$lang['gvv_bar_qrcode_title']                 = "Générer une affiche QR code pour le bar";
$lang['gvv_bar_qrcode_intro']                 = "Personnalisez l'affiche puis générez le PDF imprimable pour les paiements bar de la section";
$lang['gvv_bar_qrcode_label_title']           = "Titre";
$lang['gvv_bar_qrcode_label_text_top']        = "Texte au-dessus du QR code";
$lang['gvv_bar_qrcode_label_text_bottom']     = "Texte au-dessous du QR code";
$lang['gvv_bar_qrcode_label_url']             = "URL de paiement encodée dans le QR code";
$lang['gvv_bar_qrcode_button_generate_pdf']   = "Générer le PDF";
$lang['gvv_bar_qrcode_error_title_required']  = "Le titre est obligatoire.";
$lang['gvv_bar_qrcode_default_title']         = "Règlement des consommations bar";
$lang['gvv_bar_qrcode_default_text_top']      = "Scannez ce QR code pour régler votre consommation par carte bancaire.";
$lang['gvv_bar_qrcode_default_text_bottom']   = "Merci de présenter votre confirmation de paiement au bar.";

// Liste trésorier (EF4)
$lang['gvv_liste_title']                    = "Paiements en ligne";
$lang['gvv_liste_filter_from']              = "Du";
$lang['gvv_liste_filter_to']               = "Au";
$lang['gvv_liste_filter_statut']            = "Statut";
$lang['gvv_liste_filter_plateforme']        = "Plateforme";
$lang['gvv_liste_filter_section']           = "Section";
$lang['gvv_liste_filter_all']               = "Tous";
$lang['gvv_liste_filter_apply']             = "Filtrer";
$lang['gvv_liste_filter_reset']             = "Réinitialiser";
$lang['gvv_liste_stat_count']               = "Réglés ce mois";
$lang['gvv_liste_stat_total']               = "Montant total";
$lang['gvv_liste_stat_commissions']         = "Commissions";
$lang['gvv_liste_export_csv']               = "Exporter CSV";
$lang['gvv_liste_empty']                    = "Aucun paiement pour la période sélectionnée.";
$lang['gvv_liste_col_pilote']               = "Pilote";
$lang['gvv_liste_col_commission']           = "Commission";
$lang['gvv_liste_col_reference']            = "Référence";
$lang['gvv_liste_col_section']              = "Section";
$lang['gvv_liste_menu']                     = "Paiements en ligne";
