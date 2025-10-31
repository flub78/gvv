# Plan & Progress - Gestion des Adresses Email

**Projet:** GVV - Gestion Vol à voile
**Fonctionnalité:** Système de gestion des listes de diffusion email

**Documents associés:**
- **PRD (Exigences):** [doc/prds/gestion_emails.md](../prds/gestion_emails.md)
- **Design (Architecture):** [doc/design_notes/gestion_emails_design.md](../design_notes/gestion_emails_design.md)

---

## Métadonnées du projet

| Champ | Valeur |
|-------|--------|
| **Responsable** | À définir |
| **Date de début** | Non démarré |
| **Date de fin estimée** | +8 semaines après démarrage |
| **Statut global** | ⚪ Non démarré (0%) |
| **Phase actuelle** | N/A |
| **Budget temps** | 8 semaines (1 personne) |

---

## Timeline et jalons

| Phase | Dates estimées | Durée | Statut |
|-------|----------------|-------|--------|
| Phase 1: Fondations | Semaine 1 | 1 sem | ⚪ Non démarré |
| Phase 2: Sélection par critères | Semaine 2 | 1 sem | ⚪ Non démarré |
| Phase 3: Sélection manuelle et import | Semaine 3 | 1 sem | ⚪ Non démarré |
| Phase 4: Export et utilisation | Semaine 4 | 1 sem | ⚪ Non démarré |
| Phase 5: Controller et UI | Semaine 5 | 1 sem | ⚪ Non démarré |
| Phase 6: i18n et documentation | Semaine 6 | 1 sem | ⚪ Non démarré |
| Phase 7: Tests et qualité | Semaine 7 | 1 sem | ⚪ Non démarré |
| Phase 8: Déploiement | Semaine 8 | 1 sem | ⚪ Non démarré |

**Légende:** ⚪ Non démarré | 🔵 En cours | 🟢 Terminé | 🔴 Bloqué | ⏸️ En pause

---

## Phase 1: Fondations (Semaine 1)

**Statut:** ⚪ Non démarré (0/19 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 1.1 Migration base de données
- [ ] Créer migration `043_create_email_lists.php`
- [ ] Table email_lists avec champs (id, name, description, criteria, created_by, timestamps)
- [ ] Table email_list_members avec champs (id, email_list_id, user_id, external_email, added_at)
- [ ] Ajouter index (name UNIQUE, email_list_id, user_id)
- [ ] Ajouter FK (created_by → users, email_list_id → email_lists, user_id → users)
- [ ] Ajouter contrainte CHECK (user_id XOR external_email)
- [ ] Tester migration up
- [ ] Tester migration down (rollback)
- [ ] Mettre à jour `application/config/migration.php` version = 43

### 1.2 Helper de validation email
- [ ] Créer `application/helpers/email_helper.php`
- [ ] Fonction `validate_email($email)` - validation RFC 5322
- [ ] Fonction `normalize_email($email)` - lowercase + trim
- [ ] Fonction `deduplicate_emails($emails)` - case-insensitive dedup
- [ ] Fonction `chunk_emails($emails, $size)` - découpage en parties

### 1.3 Model de base
- [ ] Créer `application/models/email_lists_model.php`
- [ ] Méthodes CRUD : create_list, get_list, update_list, delete_list
- [ ] Méthode get_user_lists($user_id)

### 1.4 Tests
- [ ] Tests unitaires helper : `application/tests/unit/helpers/EmailHelperTest.php`
- [ ] Tests MySQL model : `application/tests/mysql/EmailListsModelTest.php`

**Blocages/Notes:** Aucun

---

## Phase 2: Sélection par critères (Semaine 2)

**Statut:** ⚪ Non démarré (0/12 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 2.1 Sélection par rôles/droits
- [ ] Analyser table users (champs de droits/rôles)
- [ ] Méthode model `get_members_by_role($role)`
- [ ] Méthode model `get_members_by_permission($permission)`
- [ ] Interface view de sélection par rôles (checkboxes)

### 2.2 Sélection par sections
- [ ] Analyser structure sections dans la base
- [ ] Méthode model `get_members_by_section($section_id)`
- [ ] Interface view de sélection par sections
- [ ] Combinaison sections + rôles (logique ET/OU)

### 2.3 Sélection par statut
- [ ] Méthode model `get_members_by_status($status)`
- [ ] Interface view de sélection par statut (actif, inactif, candidat)
- [ ] Prévisualisation AJAX du nombre de destinataires
- [ ] Dédoublonnage automatique lors de sélections multiples

### 2.4 Stockage critères JSON
- [ ] Méthode `build_criteria_json($selections)` - construction JSON
- [ ] Méthode `apply_criteria($criteria_json)` - résolution SQL
- [ ] Méthode `resolve_list_members($list_id)` - résolution complète (critères + manuels + externes)

### 2.5 Tests
- [ ] Tests d'intégration sélection multi-critères

**Blocages/Notes:** Aucun

---

## Phase 3: Sélection manuelle et import (Semaine 3)

**Statut:** ⚪ Non démarré (0/15 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 3.1 Sélection manuelle de membres
- [ ] Interface view avec liste déroulante/recherche de membres
- [ ] Méthode model `add_manual_member($list_id, $user_id)`
- [ ] Méthode model `remove_manual_member($list_id, $user_id)`
- [ ] Méthode model `get_manual_members($list_id)`
- [ ] Affichage liste des membres avec bouton suppression

### 3.2 Import fichier texte
- [ ] Interface upload fichier texte
- [ ] Helper `parse_text_emails($content)` - extraction emails ligne par ligne
- [ ] Validation de chaque adresse
- [ ] Détection doublons (fichier + liste)
- [ ] Rapport d'erreurs

### 3.3 Import fichier CSV
- [ ] Interface upload CSV avec configuration colonnes
- [ ] Helper `parse_csv_emails($content, $config)` - colonnes configurables
- [ ] Support nom, prénom, email
- [ ] Détection encoding (UTF-8, ISO-8859-1)
- [ ] Prévisualisation avant import final

### 3.4 Gestion doublons
- [ ] Interface gestion doublons (ignorer/remplacer)
- [ ] Helper `detect_duplicates($new_emails, $existing_emails)`
- [ ] Rapport détaillé des doublons

### 3.5 Tests
- [ ] Tests unitaires parsing (texte, CSV)
- [ ] Tests détection doublons

**Blocages/Notes:** Aucun

---

## Phase 4: Export et utilisation (Semaine 4)

**Statut:** ⚪ Non démarré (0/20 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 4.1 Export presse-papier
- [ ] JS `copyToClipboard(text)` avec Clipboard API
- [ ] Formatage adresses (virgules/points-virgules)
- [ ] Notification visuelle succès (toast Bootstrap)
- [ ] Gestion erreurs (permissions, liste vide)
- [ ] Fallback pour navigateurs anciens

### 4.2 Export fichiers TXT/Markdown
- [ ] Helper `generate_txt_export($emails, $separator)`
- [ ] Helper `generate_markdown_export($list_data, $emails)`
- [ ] Controller action `download_txt($id)`
- [ ] Controller action `download_md($id)`
- [ ] Interface sélection format (TXT/MD) et séparateur
- [ ] Génération nom fichier automatique (ex: `animateurs_simulateur.txt`)
- [ ] Encodage UTF-8, headers HTTP (Content-Disposition)

### 4.3 Découpage en sous-listes
- [ ] Interface config taille découpage (défaut 20)
- [ ] Calcul auto nombre de parties
- [ ] Sélecteur de partie (1/5, 2/5, etc.)
- [ ] Affichage répartition (destinataires 1-20, 21-40, etc.)
- [ ] JS `chunkEmails(emails, size, partNumber)`

### 4.4 Génération mailto
- [ ] JS `generateMailto(emails, params)` - TO, CC, BCC, Subject, Reply-To
- [ ] Détection limite URL (~2000 caractères)
- [ ] Fallback presse-papier si URL trop longue

### 4.5 Mémorisation préférences
- [ ] JS `saveMailtoPreferences(prefs)` - localStorage
- [ ] JS `loadMailtoPreferences()` - restauration auto
- [ ] Interface saisie paramètres (TO/CC/BCC, titre, reply-to)

### 4.6 Tests
- [ ] Tests unitaires export fichiers
- [ ] Tests JS (si framework disponible)

**Blocages/Notes:** Aucun

---

## Phase 5: Controller et UI (Semaine 5)

**Statut:** ⚪ Non démarré (0/15 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 5.1 Controller
- [ ] Créer `application/controllers/email_lists.php`
- [ ] Action `index()` - liste des listes
- [ ] Action `create()` - formulaire création
- [ ] Action `store()` - sauvegarde nouvelle liste
- [ ] Action `edit($id)` - formulaire modification
- [ ] Action `update($id)` - sauvegarde modifications
- [ ] Action `delete($id)` - suppression avec confirmation
- [ ] Action `view($id)` - prévisualisation + export
- [ ] Contrôle d'accès (secrétaires uniquement)

### 5.2 Views
- [ ] `index.php` - tableau listes (nom, nb destinataires, modifiée, actions)
- [ ] `create.php` - formulaire avec 3 onglets (critères/manuel/import)
- [ ] `edit.php` - formulaire modification
- [ ] `view.php` - prévisualisation + export
- [ ] `_criteria_tab.php`, `_manual_tab.php`, `_import_tab.php`
- [ ] Bootstrap 5 pour tous les formulaires

### 5.3 Metadata et navigation
- [ ] Ajouter définitions dans `Gvvmetadata.php` pour email_lists
- [ ] Ajouter menu "Communications" > "Listes de diffusion"

### 5.4 Tests
- [ ] Tests controller (toutes actions)

**Blocages/Notes:** Aucun

---

## Phase 6: Internationalisation et documentation (Semaine 6)

**Statut:** ⚪ Non démarré (0/9 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 6.1 Traductions
- [ ] `application/language/french/email_lists_lang.php`
- [ ] `application/language/english/email_lists_lang.php`
- [ ] `application/language/dutch/email_lists_lang.php`

### 6.2 Documentation utilisateur
- [ ] Section guide utilisateur français
- [ ] Section guide utilisateur anglais
- [ ] Section guide utilisateur néerlandais
- [ ] Captures d'écran interfaces

### 6.3 Documentation technique
- [ ] Vérifier Design Document à jour
- [ ] Diagrammes PlantUML (si modifications)
- [ ] PHPDoc dans tout le code

**Blocages/Notes:** Aucun

---

## Phase 7: Tests et qualité (Semaine 7)

**Statut:** ⚪ Non démarré (0/11 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 7.1 Tests unitaires
- [ ] Helper email: couverture >80%
- [ ] Validation, normalisation, dédoublonnage
- [ ] Parsing (texte, CSV)
- [ ] Génération mailto et fichiers

### 7.2 Tests d'intégration
- [ ] Sélection multi-critères avec base réelle
- [ ] Résolution listes (critères + manuels + externes)
- [ ] Détection doublons complexes
- [ ] CRUD listes
- [ ] Tests avec données volumineuses (500+ membres)

### 7.3 Tests manuels
- [ ] Chrome, Firefox, Edge (dernières versions)
- [ ] Mobile (Chrome/Safari iOS/Android)
- [ ] Outlook, Thunderbird, Gmail (ouverture mailto)
- [ ] Export fichiers et copier/coller
- [ ] Tests performance (>100 destinataires)

### 7.4 Validation couverture
- [ ] Exécuter `./run-all-tests.sh --coverage`
- [ ] Vérifier couverture >70% globale

**Blocages/Notes:** Aucun

---

## Phase 8: Déploiement (Semaine 8)

**Statut:** ⚪ Non démarré (0/9 tâches)
**Responsable:** À définir
**Dates:** Non démarrée

### 8.1 Pré-déploiement
- [ ] Analyser données existantes (ancien système email)
- [ ] Script migration si nécessaire
- [ ] Tests migration sur copie base
- [ ] Déployer sur environnement de test
- [ ] Validation toutes fonctionnalités

### 8.2 Documentation déploiement
- [ ] Procédure de déploiement
- [ ] Checklist pré-déploiement
- [ ] Plan de rollback

### 8.3 Formation et production
- [ ] Formation secrétaires
- [ ] Déploiement production
- [ ] Monitoring initial

**Blocages/Notes:** Aucun

---

## Blocages actuels

Aucun blocage - projet non démarré.

---

## Décisions et notes

### 2025-10-31 - Création du projet
- PRD validé
- Design Document créé
- Architecture confirmée : 2 tables, 3 types de listes
- Décision : localStorage pour préférences mailto (pas en DB)
- Décision : JSON pour critères (flexibilité)
- Budget estimé : 8 semaines

---

## Rétrospectives

_(À compléter après chaque phase)_

### Phase 1 (à venir)
**Ce qui a bien fonctionné:**
- TBD

**À améliorer:**
- TBD

**Blocages rencontrés:**
- TBD

---

## Statistiques

| Métrique | Valeur |
|----------|--------|
| **Tâches totales** | 110 |
| **Tâches complétées** | 0 |
| **% Complétion** | 0% |
| **Phase actuelle** | Aucune (non démarré) |
| **Jours écoulés** | 0 |
| **Jours restants estimés** | 40 (8 semaines × 5 jours) |

---

**Dernière mise à jour:** 2025-10-31
**Par:** Claude Code
**Prochaine révision:** Au démarrage de la Phase 1
