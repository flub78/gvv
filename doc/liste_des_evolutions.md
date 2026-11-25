# Évolutions de GVV depuis le passage sur GitHub

GVV a été importé de SVN vers GitHub en juin 2024. L'activité, faible en 2024 (55 commits), a fortement augmenté ensuite : environ 1 200 commits en 2025 et 1 350 en 2026. Les pull requests sont utilisées depuis février 2026.

Ce document résume les évolutions par grand domaine. Les domaines sont classés selon leur impact sur les utilisateurs, et à l'intérieur de chacun les évolutions les plus visibles viennent en premier. Le détail se trouve dans [l'historique GitHub](https://github.com/flub78/gvv/commits/main/) et dans le [guide utilisateur](users/fr/README.md).

*État au 25 novembre 2026.*

---

## 1. Vols, réservations et flotte

- **Réservation des aéronefs** : vues chronologique, semaine et mois ; réservations sur plusieurs jours ; contrôle de la cotisation, du solde et des masses maximales ; verrouillage des réservations passées, dont les instructeurs sont exemptés ; rappels par email regroupés. → [Rappels de réservation](users/fr/14_rappels_reservations.md)
- **Synchronisation avec Google Agenda** : les réservations sont reportées dans Google Agenda.
- **Saisie des vols** : vols avion, ULM et planeur revus, auto-saisie de ses propres vols, contrôle des horamètres et de l'autonomie, nouveaux types de vol (solo supervisé, VLD), bouton « Aujourd'hui », recherche des vols manquants. → [Saisie des vols](users/fr/04_saisie_vols.md)
- **Terrains** : aérodrome par défaut, et création d'un aérodrome directement depuis la saisie d'un vol.
- **Carnets de route** : contrôle de la cohérence des carnets.

## 2. Vols de découverte et paiements en ligne

- **Paiement par carte (HelloAsso)** : approvisionnement du compte pilote, paiement des cotisations, achat de vols de découverte, paiement au bar, paiement générique par QR code. → [Paiements en ligne](users/fr/12_paiements_en_ligne.md)
- **Page publique des vols de découverte** : achat en ligne sans compte GVV.
- **Gestion des bons** : vente, numérotation, date de validité, filtres, vues par année, exports CSV et PDF, prévention des doubles saisies.
- **Éditeur de bons** : modèle PDF configurable, qui ouvre les vols de découverte à tous les clubs.
- **Briefing passager** : signature par QR code, ou dépôt d'un briefing papier signé sur le vol.

## 3. Comptabilité, facturation et finances

- **Comptabilité multi-sections** : comptes, bilan, résultat, clôture et date de gel par section ; balance hiérarchique ; tableau de bord comptable. → [Comptabilité](users/fr/07_comptabilite.md)
- **Saisie guidée des écritures** : un formulaire par type d'opération (recette, dépense, remise d'espèces…), « créer et continuer », inversion des comptes, comptes masqués.
- **Rapprochements bancaires** : import des relevés, association aux écritures, marquage des écritures rapprochées.
- **Synchronisation OpenFlyers** : import des soldes et des écritures, vérification et annulation d'un import.
- **Facturation et tarifs** : séparation des produits et des tarifs, facturation des vols ULM, remise en vol, contrôle de la date de gel, blocage d'un vol dont la facturation échoue. → [Facturation](users/fr/06_facturation.md)
- **États financiers** : résultat avant et après dépréciations, gestion des prêts, comptes de caisse, résultat par section.
- **Relances des débiteurs**, avec exports CSV et PDF.
- **Exports Excel** des états financiers (bilan, résultat, balance), puis des listes.

## 4. Membres, cotisations et communication

- **Membres actifs ou inactifs par section**, fusion de deux fiches, renommage des identifiants. → [Gestion des membres](users/fr/02_gestion_membres.md)
- **Cotisations et licences** : saisie revue, gestion de l'année suivante, contrôle des vols sans cotisation. → [Cotisations](users/fr/10_gestion_cotisations.md)
- **Alarmes et notifications** : emails avant les échéances (visite médicale, licence, documents), vols sans cotisation ou sans licence, expérience récente, compte débiteur ; chaque membre choisit ses alarmes. → [Alarmes](users/fr/19_alarmes.md)
- **Listes de diffusion** : import, sous-listes, export. → [Listes de diffusion](users/fr/18_listes_diffusion.md)
- **Newsletter et veille sécurité** : veille automatisée sur le serveur, intégrée à la newsletter et pilotée depuis GVV. → [Newsletter et veille](users/fr/20_newsletter_veille.md)
- **Messages du jour** pour les membres. → [Messages](users/fr/15_gestion_messages.md)
- **Cartes de membre** : impression et réglage des marges.
- **Statistiques adhérents** : répartition par âge et par classe, pyramide des âges, fidélisation, évolution sur 10 ans. → [Rapports](users/fr/08_rapports.md)
- **Calendrier** : présences et calendrier des échéances. → [Calendrier](users/fr/05_calendrier.md)

## 5. Gestion des documents

- **Formulaires** : formulaires internes et publics (inscription, autorisation parentale, attestations), en plusieurs pages, avec signature, tampon de l'association et PDF. → [Formulaires](users/fr/13_formulaires.md)
- **Acceptation de documents** : publication de documents que les membres doivent lire, accepter ou signer.
- **Documents archivés** : types de documents, dates de validité et échéances, suivis par les alarmes.
- **Pièces jointes** : rattachées aux écritures et aux fiches, compressées, classées par section et par année.
- **Procédures du club.**

## 6. Formation

- **Suivi de formation** : fiches de progression, séances théoriques, autorisations de vol solo, certificats. → [Formations](users/fr/11_formations.md)
- **Rapports de formation** : heures en double commande, rapport annuel en PDF, filtrage par section.
- **Niveau d'entraînement** des pilotes solo.
- **Qualifications** : contrôle des compétences VLD et REP, suivi de la validité des visites médicales.

## 7. Maintenance des aéronefs

- **Suivi de navigabilité** : équipements à entretenir, programmes d'entretien déposés en Markdown, dossiers et opérations de maintenance (saisie directe ou compte rendu papier), bulletins de service des constructeurs. → [Maintenance](users/fr/16_maintenance_aeronefs.md)
- **Synthèse de navigabilité** consultable par tous les pilotes.
- **Transfert d'un équipement** d'un aéronef à l'autre, avec conservation de l'historique.

## 8. Configuration et interface

- **Configuration unifiée** : réglages stockés en base et regroupés dans une page à onglets (club, modules, messagerie, documents, vols de découverte, référentiels…). → [Configuration](users/fr/21_configuration.md)
- **Interface modernisée** : passage complet à Bootstrap 5, écrans adaptés aux mobiles, tableaux homogènes, messages d'erreur dans des alertes refermables, filtres mémorisés par page.
- **Menus et pages adaptés à la section** et aux droits de chaque utilisateur.
- **Guide utilisateur** en ligne, chapitre par chapitre.

## 9. Sécurité et autorisations

- **Refonte des autorisations** : rôles attribués par section, droits partiels du CA, menus filtrés selon les droits. → [Autorisations](users/fr/09_autorisations.md)
- **Renforcement des contrôles d'accès** : chaque enregistrement exige les mêmes droits que l'ouverture du formulaire, et les pages d'administration et de maintenance sont réservées aux rôles concernés.
- **Protection contre la falsification de requêtes (CSRF)** sur tous les formulaires et appels de l'application.
- **Journalisation** de toutes les créations, modifications et suppressions.
- **Audit des accès** aux documents et aux fichiers déposés.

## 10. DevOps et qualité

- **Sauvegardes** : sauvegarde et restauration des médias, envoi hors site supervisé sur Google Drive avec alertes. → [Sauvegarde hors site](users/fr/17_sauvegarde_hors_site.md)
- **Migration vers PHP 8.4.**
- **Tests automatisés** : plus de 1 100 tests PHPUnit et près de 1 000 tests Playwright de bout en bout, décrits en Gherkin, avec une documentation des tests générée automatiquement.
- **Développement par pull requests** sur GitHub, avec revue de code et contrôle automatique des secrets.
- **Guide pour les nouveaux contributeurs.**
