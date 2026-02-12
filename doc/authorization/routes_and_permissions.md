# GVV - Routes et Autorisations

**Version:** 1.0
**Date:** 2026-02-12
**Objectif:** Référence complète des routes et des autorisations requises pour chaque contrôleur/action.
Ce document sert de base pour la revue des autorisations et l'implémentation du nouveau système.

---

## Rôles disponibles

### Nouveau système (types_roles)

| ID | Nom | Description |
|----|-----|-------------|
| 1 | user | Connexion et consultation des données utilisateur |
| 2 | auto_planchiste | CRUD sur ses propres données uniquement |
| 5 | planchiste | CRUD sur les données de vol |
| 6 | ca | Consultation de toutes les données d'une section (y compris finances globales) |
| 7 | bureau | Consultation de toutes les données d'une section (y compris finances personnelles) |
| 8 | tresorier | Modification des données financières d'une section |
| 9 | super-tresorier | Modification des données financières de toutes les sections |
| 10 | club-admin | Accès complet à toutes les données et configurations |
| 11 | instructeur | Gestion des formations |
| 12 | mecano | Mécanicien |

### Hiérarchie DX_Auth (système legacy)

```
membre → planchiste → ca → bureau → tresorier → admin
```

Chaque rôle hérite des droits de tous ses parents. Avec `is_role('ca', true, true)`, un utilisateur ayant le rôle `bureau`, `tresorier` ou `admin` passe aussi le test.

**Note importante :** Le nouveau système est **non-hiérarchique**. Chaque rôle est indépendant. Un utilisateur doit avoir explicitement le rôle `user` pour se connecter ET le rôle `planchiste` pour éditer des vols.

---

## Légende

- **Login** : Connexion requise (tout utilisateur authentifié)
- **Rôle requis** : Rôle minimum dans le système legacy (avec héritage)
- **Nouveau rôle** : Rôle(s) à utiliser dans le nouveau système (sans héritage)
- **own** : L'utilisateur ne peut accéder qu'à ses propres données
- **all** : L'utilisateur peut accéder à toutes les données
- **feature flag** : Contrôleur protégé par un flag de configuration en plus de l'auth
- **(hérité)** : L'autorisation est héritée du constructeur / `modification_level`
- **aucun** : Aucune vérification d'autorisation

### Colonnes du tableau

| Colonne | Description |
|---------|-------------|
| Route | URL relative (contrôleur/méthode) |
| Auth legacy | Autorisation actuelle dans le système DX_Auth |
| Rôle(s) nouveau système | Rôle(s) proposé(s) pour le nouveau système `require_roles()` |
| Scope données | own = ses données, all = toutes les données, n/a = sans objet |
| Notes | Remarques, exceptions, comportements particuliers |

---

## Routes par contrôleur

### acceptance_admin

Gestion des documents d'acceptation des règles du club.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| acceptance_admin/index | ca ou admin | ca | all | Redirige vers page() |
| acceptance_admin/page | ca ou admin | ca | all | Liste des documents |
| acceptance_admin/create | ca ou admin | ca | all | Création document |
| acceptance_admin/edit | ca ou admin | ca | all | Modification document |
| acceptance_admin/formValidation | ca ou admin | ca | all | Validation formulaire |
| acceptance_admin/toggle_active | ca ou admin | ca | all | Activation/désactivation |
| acceptance_admin/tracking | ca ou admin | ca | all | Suivi des signatures |
| acceptance_admin/link_pilot | ca ou admin | ca | all | Association pilote |
| acceptance_admin/download | **login** | **user** | all | **Pas de vérification de rôle !** |

---

### achats

Gestion des achats. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| achats/* (toutes méthodes) | ca (hérité) | ca | all | Via modification_level |

---

### adherents_report

Rapport sur les adhérents. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| adherents_report/index | login + URI perms | ca | all | check_uri_permissions() |
| adherents_report/page | login + URI perms | ca | all | |
| adherents_report/set_year | login + URI perms | ca | all | |

---

### admin

Administration système (backup/restore).

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| admin/backup | login (check_login) | club-admin | all | **Devrait être admin uniquement** |
| admin/backup_media | login (check_login) | club-admin | all | **Devrait être admin uniquement** |
| admin/* (toutes méthodes) | login (check_login) | club-admin | all | **Pas de vérification de rôle !** |

---

### alarmes

Gestion des alarmes/conditions des pilotes. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| alarmes/index | login | user | own/all | Affiche les conditions du pilote spécifié ou du pilote courant |
| alarmes/* (modification) | ca (hérité) | ca | all | Via modification_level |

---

### archived_documents

Gestion documentaire. **Feature flag : `gestion_documentaire`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| archived_documents/index | login | user | own | Redirige vers my_documents() ou page() selon rôle |
| archived_documents/my_documents | login | user | own | Ses propres documents |
| archived_documents/page | ca ou admin | ca | all | Liste de tous les documents |
| archived_documents/create | login | user | own | Redirige vers create_pilot() si non-admin |
| archived_documents/create_pilot | login | user | own | Upload de document par le pilote |
| archived_documents/edit | ca ou admin | ca | all | Modification d'un document |
| archived_documents/delete | ca ou admin | ca | all | Suppression |
| archived_documents/approve | ca ou admin | ca | all | Approbation |
| archived_documents/reject | ca ou admin | ca | all | Rejet |
| archived_documents/toggle_alarm | ca ou admin | ca | all | Basculer l'alarme |
| archived_documents/view | login | user | own | Admin ou propriétaire du document |
| archived_documents/download | login | user | own | Admin ou propriétaire du document |
| archived_documents/preview | login | user | own | Admin ou propriétaire du document |

---

### associations_ecriture

Associations comptables. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| associations_ecriture/associate | ca (hérité) | tresorier | all | Endpoint AJAX |

---

### associations_of

Associations OpenFlyers. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| associations_of/associate | ca (hérité) | ca | all | Endpoint AJAX |

---

### associations_releve

Associations relevés bancaires. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| associations_releve/associate | ca (hérité) | tresorier | all | Endpoint AJAX |

---

### attachments

Pièces jointes comptables.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| attachments/page | bureau | bureau | all | Consultation |
| attachments/create | tresorier | tresorier | all | Création |
| attachments/edit | tresorier | tresorier | all | Modification |
| attachments/formValidation | tresorier | tresorier | all | Validation |
| attachments/delete | tresorier | tresorier | all | Suppression |
| attachments/generate_thumbnail | login | user | all | Génération miniature |

---

### auth

Contrôleur d'authentification. **Pas de login requis** pour la plupart des méthodes.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| auth/login | aucun | aucun | n/a | Page de connexion |
| auth/logout | login | aucun | n/a | Déconnexion |
| auth/register | aucun | aucun | n/a | Inscription |
| auth/activate | aucun | aucun | n/a | Activation compte |
| auth/forgot_password | aucun | aucun | n/a | Mot de passe oublié |
| auth/reset_password | aucun | aucun | n/a | Réinitialisation |
| auth/change_password | login | user | own | Changement mot de passe |
| auth/cancel_account | login | user | own | Suppression compte |

---

### authorization

Gestion du système d'autorisation.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| authorization/* (toutes) | admin ou club-admin | club-admin | all | Vérifié dans le constructeur |

---

### avion

Gestion de la flotte avions. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| avion/page | login | user | all | Consultation liste |
| avion/view | login | user | all | Détail avion |
| avion/create | ca | ca | all | Création |
| avion/edit | ca (hérité) | ca | all | Modification |
| avion/delete | ca (hérité) | ca | all | Suppression |
| avion/export | ca | ca | all | Export |

---

### backend

Administration backend.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| backend/* (toutes) | login + URI perms | club-admin | all | check_uri_permissions() |

---

### calendar

Calendrier des événements.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| calendar/index | login | user | all | Affichage calendrier |
| calendar/ajout | login | user | own | Création : CA ou propre événement |
| calendar/update | login | user | own | Modification : CA ou propre événement |
| calendar/delete | login | user | own | Suppression : CA ou propre événement |

---

### categorie

Catégories comptables. `modification_level = 'tresorier'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| categorie/* (toutes) | tresorier (hérité) | tresorier | all | Via modification_level |

---

### compta

Comptabilité. `modification_level = 'tresorier'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| compta/page | login | user | all | Consultation (avec filtre selon rôle) |
| compta/view | login | user | own/all | Détail écriture |
| compta/edit | tresorier | tresorier | all | Modification écriture |
| compta/create | tresorier (hérité) | tresorier | all | Création écriture |
| compta/delete | tresorier (hérité) | tresorier | all | Suppression écriture |
| compta/check | **fpeignot uniquement** | club-admin | all | **Accès codé en dur pour un utilisateur !** |
| compta/* (autres) | tresorier (hérité) | tresorier | all | Via modification_level |

---

### comptes

Plan des comptes. `modification_level = 'tresorier'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| comptes/page | login | user | all | Consultation |
| comptes/view | login | user | all | Détail compte |
| comptes/create | tresorier (hérité) | tresorier | all | Création |
| comptes/edit | tresorier (hérité) | tresorier | all | Modification |
| comptes/delete | tresorier (hérité) | tresorier | all | Suppression |
| comptes/check | **fpeignot uniquement** | club-admin | all | **Accès codé en dur !** |

---

### config

Configuration système.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| config/* (toutes) | login + URI perms | club-admin | all | check_uri_permissions() |

---

### configuration

Configuration du club. `modification_level = 'bureau'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| configuration/* (toutes) | bureau (hérité) | bureau | all | Via modification_level |

---

### coverage

Couverture de code. **Outil de test - pas de login.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| coverage/* (toutes) | **aucun** | **aucun** | n/a | **Login commenté - outil de dev uniquement** |

---

### dbchecks

Vérifications base de données. **Login commenté.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| dbchecks/* (toutes) | **aucun** | club-admin | all | **Login commenté ! Devrait être protégé** |

---

### document_types

Types de documents. **Feature flag : `gestion_documentaire`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| document_types/* (toutes) | login | ca | all | require_roles(['ca']) déjà implémenté |

---

### email_lists

Listes de diffusion email.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| email_lists/* (lecture) | login | user | own | Utilisateur voit ses propres listes |
| email_lists/* (modification) | secretaire | ca | all | Admin peut modifier toutes les listes |

---

### event

Événements. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| event/* (toutes) | ca (hérité) | ca | all | Via modification_level |

---

### events_types

Types d'événements. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| events_types/* (toutes) | ca (hérité) | ca | all | Via modification_level |

---

### facturation

Facturation. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| facturation/* (toutes) | ca (hérité) | tresorier | all | **Revoir : devrait être tresorier ?** |

---

### FFVV

Interface fédération (FFVV).

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| FFVV/* (toutes) | login (check_login) | ca | all | **Pas de vérification de rôle !** |

---

### formation_autorisations_solo

Autorisations solo formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| formation_autorisations_solo/index | instructeur | instructeur | all | Via formation_access |
| formation_autorisations_solo/create | instructeur | instructeur | all | |
| formation_autorisations_solo/store | instructeur | instructeur | all | |
| formation_autorisations_solo/edit | instructeur | instructeur | all | |
| formation_autorisations_solo/update | instructeur | instructeur | all | |
| formation_autorisations_solo/delete | instructeur | instructeur | all | |
| formation_autorisations_solo/detail | instructeur ou élève | instructeur, user (own) | own/all | L'élève peut voir son propre détail |

---

### formation_inscriptions

Inscriptions formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| formation_inscriptions/* (toutes) | login | user | all | **Pas de vérification de rôle spécifique** |

---

### formation_progressions

Progressions formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| formation_progressions/index | login + formation_access | instructeur | all | |
| formation_progressions/mes_formations | login + formation_access | user | own | Ses propres formations |
| formation_progressions/export_pdf | login + formation_access | instructeur, user (own) | own/all | |

---

### formation_rapports

Rapports de formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| formation_rapports/* (toutes) | login + formation_access | instructeur | all | Via formation_access |

---

### formation_seances

Séances de formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| formation_seances/detail | login | user | own/all | L'élève voit une vue limitée |
| formation_seances/* (autres) | login | instructeur | all | |

---

### historique

Historique. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| historique/* (toutes) | ca (hérité) | ca | all | Via modification_level |

---

### import

Import de données. **Login commenté.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| import/* (toutes) | **aucun** | club-admin | all | **Login commenté ! Devrait être protégé** |

---

### licences

Gestion des licences. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| licences/* (toutes) | ca (hérité) | ca | all | require_roles(['ca']) déjà implémenté |

---

### login_as

Usurpation d'identité (pour admin).

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| login_as/index | admin (is_admin) | club-admin | all | Liste des utilisateurs |
| login_as/switch_to | admin (is_admin) | club-admin | all | Connexion en tant qu'autre utilisateur |

---

### mails

Envoi d'emails. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| mails/addresses | ca | ca | all | Gestion des adresses |
| mails/* (autres) | ca (hérité) | ca | all | Via modification_level |

---

### membre

Gestion des membres. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| membre/page | login | user | all | Consultation (boutons edit/delete selon rôle) |
| membre/view | login | user | own/all | Détail membre |
| membre/edit | login | user (own), ca (all) | own/all | Chacun peut éditer sa propre fiche |
| membre/create | ca | ca | all | Création membre |
| membre/delete | ca (hérité) | ca | all | Suppression |
| membre/ajax_toggle_actif | admin | club-admin | all | Activation/désactivation |
| membre/sync_accounts | ca | ca | all | Synchronisation comptes |
| membre/export | ca | ca | all | Export |

---

### meteo

Cartes météo.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| meteo/index | ca ou admin | ca | all | Via can_manage_cards() |
| meteo/page | ca ou admin | ca | all | |
| meteo/create | ca ou admin | ca | all | |
| meteo/edit | ca ou admin | ca | all | |
| meteo/view | ca ou admin | ca | all | |
| meteo/delete | ca ou admin | ca | all | |

---

### migration

Migration de base de données.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| migration/* (toutes) | login + URI perms | club-admin | all | check_uri_permissions() |

---

### oneshot

Scripts ponctuels.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| oneshot/* (toutes) | login (check_login) | club-admin | all | **Pas de vérification de rôle !** |

---

### openflyers

Interface OpenFlyers.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| openflyers/* (toutes) | login (check_login) | ca | all | **Pas de vérification de rôle !** |

---

### partage

Partage de fichiers. **Pas de login requis.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| partage/upload | **aucun** | **aucun** | n/a | **Endpoint public - intentionnel ?** |
| partage/do_upload | **aucun** | **aucun** | n/a | **Endpoint public** |
| partage/delete | **aucun** | **aucun** | n/a | **Endpoint public - risque sécurité !** |

---

### plan_comptable

Plan comptable.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| plan_comptable/page | login | tresorier | all | Consultation |
| plan_comptable/view | login | tresorier | all | Détail |
| plan_comptable/export | ca | tresorier | all | Export |
| plan_comptable/create | login | tresorier | all | **Pas de vérification de rôle !** |
| plan_comptable/edit | login | tresorier | all | **Pas de vérification de rôle !** |
| plan_comptable/delete | login | tresorier | all | **Pas de vérification de rôle !** |

---

### planeur

Gestion de la flotte planeurs. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| planeur/page | login | user | all | Consultation liste |
| planeur/view | login | user | all | Détail planeur |
| planeur/create | ca | ca | all | Création |
| planeur/edit | ca (hérité) | ca | all | Modification |
| planeur/delete | ca (hérité) | ca | all | Suppression |
| planeur/export | ca | ca | all | Export |

---

### pompes

Gestion des pompes/ravitaillement. `modification_level = 'bureau'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| pompes/* (toutes) | bureau (hérité) | bureau | all | Via modification_level |

---

### presences

Gestion des présences.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| presences/index | login | user | all | Consultation |
| presences/create_presence | login | user (own), ca (all) | own/all | CA ou propre présence |
| presences/update_presence | login | user (own), ca (all) | own/all | CA ou propre présence |
| presences/delete_presence | login | user (own), ca (all) | own/all | CA ou propre présence |

---

### procedures

Gestion des procédures. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| procedures/page | login | user | all | Consultation |
| procedures/view | ca ou admin | ca | all | Détail |
| procedures/edit_markdown | ca ou admin | ca | all | Édition markdown |
| procedures/attachments | ca ou admin | ca | all | Pièces jointes |
| procedures/delete | admin | club-admin | all | Suppression (admin seulement) |
| procedures/create | ca (hérité) | ca | all | Création |

---

### programmes

Programmes de formation. **Feature flag : `gestion_formations`**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| programmes/* (toutes) | formation_access + can_manage | instructeur | all | Via formation_access |

---

### rapports

Rapports. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| rapports/* (toutes) | login + URI perms + ca | ca | all | check_uri_permissions() + modification_level |

---

### rapprochements

Rapprochements bancaires.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| rapprochements/page | login (check_login) | tresorier | all | **Pas de vérification de rôle !** |
| rapprochements/export_ecritures | ca | tresorier | all | Export |
| rapprochements/* (autres) | login (check_login) | tresorier | all | **Pas de vérification de rôle !** |

---

### reports

Rapports. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| reports/* (toutes) | ca (hérité) | ca | all | Via modification_level |

---

### reservations

Réservations de machines.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| reservations/* (toutes) | login | user | own/all | Tout utilisateur connecté peut réserver |

---

### sections

Gestion des sections.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| sections/page | login | user | all | Consultation |
| sections/view | login | user | all | Détail |
| sections/create | ca (hérité) | ca | all | Création |
| sections/edit | ca (hérité) | ca | all | Modification |
| sections/delete | ca (hérité) | ca | all | Suppression |
| sections/export | ca | ca | all | Export |
| sections/test | login | user | all | Test |

---

### tarifs

Gestion des tarifs.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| tarifs/page | login | user | all | Consultation (boutons selon rôle) |
| tarifs/view | login | user | all | Détail |
| tarifs/create | ca (hérité) | ca | all | Création |
| tarifs/edit | ca (hérité) | ca | all | Modification |
| tarifs/delete | ca (hérité) | ca | all | Suppression |
| tarifs/clone_elt | ca (hérité) | ca | all | Duplication |

---

### terrains

Gestion des terrains. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| terrains/* (toutes) | ca (hérité) | ca | all | require_roles(['ca']) déjà implémenté |

---

### tests_ciunit

Tests unitaires legacy. **Pas de login.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| tests_ciunit/* (toutes) | **aucun** | **aucun** | n/a | **Login commenté - outil de dev uniquement** |

---

### tickets

Gestion des tickets (bons de vol). `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| tickets/page | login | user (own), ca (all) | own/all | Non-CA ne voit que ses tickets |
| tickets/view | login | user (own), ca (all) | own/all | |
| tickets/create | ca (hérité) | ca | all | Création |
| tickets/edit | ca (hérité) | ca | all | Modification |
| tickets/delete | ca (hérité) | ca | all | Suppression |
| tickets/export | login | user (own), ca (all) | own/all | Non-CA ne voit que ses tickets |
| tickets/solde | login | user | all | Consultation solde |

---

### tools

Outils utilitaires. **Pas de login requis.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| tools/index | **aucun** | **aucun** | n/a | Page d'accueil/redirection |
| tools/bye | **aucun** | **aucun** | n/a | Page de déconnexion |

---

### types_ticket

Types de tickets. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| types_ticket/* (toutes) | ca (hérité) | ca | all | Via modification_level |

---

### user_roles_per_section

Gestion des rôles utilisateurs par section.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| user_roles_per_section/* (toutes) | login | club-admin | all | **Pas de vérification de rôle !** |

---

### vols_avion

Gestion des vols avion. `modification_level = 'planchiste'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| vols_avion/page | login | user | all | Consultation liste |
| vols_avion/view | login | user | all | Détail vol |
| vols_avion/create | planchiste | planchiste | all | Création vol |
| vols_avion/edit | planchiste | planchiste | all | Modification vol |
| vols_avion/delete | planchiste | planchiste | all | Suppression vol |
| vols_avion/csv | login | planchiste | all | Export CSV |
| vols_avion/pdf | login | planchiste | all | Export PDF |
| vols_avion/filterValidation | login | user | all | Validation filtre |
| vols_avion/stat_* | login | user | all | Statistiques |
| vols_avion/export_* | login | planchiste | all | Exports |

---

### vols_decouverte

Gestion des vols découverte. `modification_level = 'ca'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| vols_decouverte/page | login | user | all | Consultation liste |
| vols_decouverte/view | login | user | all | Détail |
| vols_decouverte/create | ca (hérité) | ca | all | Création |
| vols_decouverte/edit | ca (hérité) | ca | all | Modification |
| vols_decouverte/delete | ca (hérité) | ca | all | Suppression |
| vols_decouverte/export | ca | ca | all | Export |
| vols_decouverte/print_vd | login | user | all | Impression |
| vols_decouverte/email_vd | login | user | all | Email |
| vols_decouverte/qr | login | user | all | QR Code |
| vols_decouverte/action | login | user | all | Action sur vol |
| vols_decouverte/pre_flight | login | user | all | Pré-vol |
| vols_decouverte/done | login | user | all | Vol terminé |

---

### vols_planeur

Gestion des vols planeur. `modification_level = 'planchiste'`

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| vols_planeur/page | login | user | all | Consultation liste |
| vols_planeur/view | login | user | all | Détail vol |
| vols_planeur/create | planchiste | planchiste | all | Création vol |
| vols_planeur/plancheauto | planchiste | planchiste | all | Planche auto |
| vols_planeur/plancheauto_select | planchiste | planchiste | all | Sélection planche auto |
| vols_planeur/edit | planchiste | planchiste | all | Modification vol |
| vols_planeur/delete | planchiste | planchiste | all | Suppression vol |
| vols_planeur/csv | planchiste | planchiste | all | Export CSV |
| vols_planeur/pdf | planchiste | planchiste | all | Export PDF |
| vols_planeur/gesasso | login | user | all | Export GesAsso |
| vols_planeur/filterValidation | planchiste | planchiste | all | Validation filtre |
| vols_planeur/vols_du_pilote | planchiste | planchiste | all | Vols d'un pilote |
| vols_planeur/vols_de_la_machine | planchiste | planchiste | all | Vols d'une machine |
| vols_planeur/statistic | planchiste | planchiste | all | Statistiques |
| vols_planeur/cumuls | planchiste | planchiste | all | Cumuls |
| vols_planeur/histo | planchiste | planchiste | all | Historique |
| vols_planeur/age | planchiste | planchiste | all | Analyse par âge |
| vols_planeur/export_per | planchiste | planchiste | all | Export périodique |
| vols_planeur/pdf_machine | planchiste | planchiste | all | PDF par machine |
| vols_planeur/pdf_month | planchiste | planchiste | all | PDF par mois |
| vols_planeur/jours_de_vol | planchiste | planchiste | all | Jours de vol |
| vols_planeur/par_pilote_machine | planchiste | planchiste | all | Par pilote/machine |
| vols_planeur/ajax_page | login | user | all | AJAX données (actions selon rôle) |
| vols_planeur/pilote_au_sol | login | user | all | Pilote au sol |
| vols_planeur/machine_au_sol | login | user | all | Machine au sol |
| vols_planeur/selection | login | user | all | Sélection |

---

### welcome

Page d'accueil / dashboard.

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| welcome/index | login | user | all | Dashboard principal |
| welcome/set_cookie | login | user | n/a | Configuration cookie |
| welcome/nyi | login | user | n/a | Not Yet Implemented |
| welcome/about | login | user | n/a | À propos |
| welcome/compta | tresorier | tresorier | all | Dashboard comptabilité |
| welcome/ca | ca | ca | all | Dashboard CA |
| welcome/new_year | login | user | all | Changement d'année |
| welcome/deny | login | user | n/a | Page accès refusé |

---

### API : api/vols_avion

API de test pour les vols avion. **Protégée par flag `test_api`.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| api/vols_avion/ajax_latest | config test_api | **aucun** | all | API de test uniquement |
| api/vols_avion/get | config test_api | **aucun** | all | API de test |
| api/vols_avion/count | config test_api | **aucun** | all | API de test |

---

### API : api/vols_planeur

API de test pour les vols planeur. **Protégée par flag `test_api`.**

| Route | Auth legacy | Rôle(s) nouveau système | Scope | Notes |
|-------|-------------|------------------------|-------|-------|
| api/vols_planeur/ajax_latest | config test_api | **aucun** | all | API de test uniquement |
| api/vols_planeur/get | config test_api | **aucun** | all | API de test |
| api/vols_planeur/count | config test_api | **aucun** | all | API de test |

---

## Anomalies et points d'attention

### Contrôleurs sans protection d'accès (login commenté)

| Contrôleur | Risque | Action recommandée |
|------------|--------|-------------------|
| coverage | Faible (dev) | Désactiver en production |
| dbchecks | **Élevé** | Ajouter login + club-admin |
| import | **Élevé** | Ajouter login + club-admin |
| tests_ciunit | Faible (dev) | Désactiver en production |

### Contrôleurs avec login mais sans vérification de rôle

| Contrôleur | Risque | Action recommandée |
|------------|--------|-------------------|
| admin (backup) | **Élevé** | Restreindre à club-admin |
| FFVV | Moyen | Restreindre à ca |
| oneshot | **Élevé** | Restreindre à club-admin |
| openflyers | Moyen | Restreindre à ca |
| rapprochements (hors export) | **Élevé** | Restreindre à tresorier |
| plan_comptable (CUD) | **Élevé** | Restreindre à tresorier |
| user_roles_per_section | **Élevé** | Restreindre à club-admin |

### Endpoint public potentiellement dangereux

| Contrôleur | Route | Risque | Action recommandée |
|------------|-------|--------|-------------------|
| partage | upload/delete | **Critique** | Vérifier si intentionnel, ajouter auth si nécessaire |
| acceptance_admin | download | Moyen | Vérifier la politique d'accès aux documents |

### Accès codés en dur

| Contrôleur | Route | Problème | Action recommandée |
|------------|-------|----------|-------------------|
| compta | check | Restreint à 'fpeignot' | Remplacer par rôle club-admin |
| comptes | check | Restreint à 'fpeignot' | Remplacer par rôle club-admin |

### Incohérences potentielles entre rôle legacy et nouveau système

| Contrôleur | modification_level legacy | Nouveau rôle proposé | Commentaire |
|------------|-------------------------|---------------------|-------------|
| facturation | ca | tresorier | La facturation est financière |
| associations_ecriture | ca | tresorier | Les écritures sont financières |
| associations_releve | ca | tresorier | Les relevés sont financiers |
| email_lists | secretaire | ca | Le rôle 'secretaire' n'existe plus |

---

## Résumé par rôle

### Accès sans authentification
- auth (login, register, forgot_password, reset_password, activate)
- tools (index, bye)
- partage (upload, do_upload, delete)
- coverage, tests_ciunit (outils de dev)

### user (tout utilisateur connecté)
- welcome (index, about, nyi, set_cookie, new_year)
- membre (page, view, edit own)
- calendar (view, CRUD own events)
- presences (view, CRUD own)
- reservations (CRUD)
- tickets (page own, view own, export own, solde)
- alarmes (view own)
- archived_documents (my_documents, create_pilot, view own, download own)
- vols_planeur (page, view, ajax_page, pilote_au_sol, machine_au_sol)
- vols_avion (page, view, stats)
- vols_decouverte (page, view, action, print, email, qr)
- avion (page, view)
- planeur (page, view)
- sections (page, view)
- tarifs (page, view)
- formation_progressions (mes_formations)
- formation_inscriptions
- formation_seances (detail own)

### planchiste
- vols_planeur (create, edit, delete, csv, pdf, stats, exports...)
- vols_avion (create, edit, delete, csv, pdf, exports...)

### ca (Conseil d'Administration)
- membre (create, delete, sync_accounts, export)
- mails
- avion (create, export)
- planeur (create, export)
- event, events_types
- vols_decouverte (create, edit, delete, export)
- sections (create, edit, delete, export)
- tarifs (create, edit, delete)
- terrains
- licences
- alarmes (modification)
- historique
- rapports, reports
- adherents_report
- achats
- associations_of
- types_ticket
- tickets (create, edit, delete, view all)
- meteo
- acceptance_admin
- archived_documents (page, edit, delete, approve, reject)
- document_types
- facturation
- email_lists (modification all)
- FFVV, openflyers

### bureau
- configuration
- pompes
- attachments (page - lecture seule)

### tresorier
- compta (edit, create, delete)
- comptes (create, edit, delete)
- categorie
- attachments (create, edit, delete)
- rapprochements
- plan_comptable
- associations_ecriture, associations_releve

### instructeur
- formation_autorisations_solo
- formation_progressions
- formation_rapports
- formation_seances
- programmes

### club-admin
- admin (backup, restore)
- authorization
- backend
- config
- migration
- login_as
- user_roles_per_section
- dbchecks, import, oneshot
