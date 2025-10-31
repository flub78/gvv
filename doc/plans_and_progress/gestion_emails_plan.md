# Implementation Plan - Gestion des Adresses Email

**Projet:** GVV - Gestion Vol à voile
**Fonctionnalité:** Système de gestion des listes de diffusion email

**Documents associés:**
- **PRD (Exigences):** [doc/prds/gestion_emails.md](../prds/gestion_emails.md)
- **Design (Architecture):** [doc/design_notes/gestion_emails_design.md](../design_notes/gestion_emails_design.md)

**Statut global:** ⚪ Non démarré (0/113 tâches - 0%)
**Phase actuelle:** N/A
**Estimation:** 8 semaines (1 personne)

**Légende:** ⚪ Non démarré | 🔵 En cours | 🟢 Terminé | 🔴 Bloqué | ⏸️ En pause

---

## Phase 1: Fondations - ⚪ 0/19 (Semaine 1)

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

---

## Phase 2: Sélection par critères via user_roles_per_section - ⚪ 0/15 (Semaine 2)

### 2.1 Analyse architecture autorisations
- [ ] Analyser table `user_roles_per_section` (user_id, types_roles_id, section_id, revoked_at)
- [ ] Analyser table `types_roles` (id, nom, description, scope)
- [ ] Analyser table `sections` (id, nom, description)
- [ ] Comprendre lien users ↔ membres (mlogin = username)
- [ ] Tester requête 3-tables: user_roles_per_section → users → membres

### 2.2 Méthodes model pour chargement données
- [ ] Méthode `get_available_roles()` - charge tous types_roles pour UI
- [ ] Méthode `get_available_sections()` - charge toutes sections pour UI
- [ ] Méthode `get_users_by_role_and_section($types_roles_id, $section_id)` - sélection simple

### 2.3 Résolution critères JSON
- [ ] Méthode `build_criteria_json($selections)` - construit JSON avec types_roles_id + section_id
- [ ] Méthode `apply_criteria($criteria_json)` - résout via user_roles_per_section
- [ ] Gérer filtre `revoked_at IS NULL` (rôles actifs uniquement)
- [ ] Gérer filtre optionnel `membres.actif` (statut membre)
- [ ] Méthode `resolve_list_members($list_id)` - résolution complète (critères + manuels + externes)

### 2.4 Interface UI sélection par rôles
- [ ] Charger rôles et sections via AJAX/PHP
- [ ] Grouper checkboxes par section
- [ ] Marquer rôles globaux (scope='global')
- [ ] Logique combinaison ET/OU
- [ ] Prévisualisation AJAX du nombre de destinataires

### 2.5 Tests et optimisation
- [ ] Ajouter index `users(username)` pour performance jointure membres
- [ ] Tests d'intégration sélection multi-rôles/sections
- [ ] Test dédoublonnage (utilisateur avec multiples rôles)

---

## Phase 3: Sélection manuelle et import - ⚪ 0/15 (Semaine 3)

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

---

## Phase 4: Export et utilisation - ⚪ 0/20 (Semaine 4)

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

---

## Phase 5: Controller et UI - ⚪ 0/15 (Semaine 5)

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

---

## Phase 6: Internationalisation et documentation - ⚪ 0/9 (Semaine 6)

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

---

## Phase 7: Tests et qualité - ⚪ 0/11 (Semaine 7)

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

---

## Phase 8: Déploiement - ⚪ 0/9 (Semaine 8)

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

---

## Notes et blocages

**2025-10-31 - Création du projet**
- PRD validé
- Design Document créé
- Architecture confirmée : 2 tables, 3 types de listes
- Décision : localStorage pour préférences mailto (pas en DB)
- Décision : JSON pour critères (flexibilité)
- Budget estimé : 8 semaines

**Blocages actuels:** Aucun - projet non démarré

---

**Dernière mise à jour:** 2025-10-31
