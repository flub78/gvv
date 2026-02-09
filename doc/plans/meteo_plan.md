# Plan d’implémentation — Page Météo & Préparation des Vols

Date : 9 février 2026

## Liste de tâches (avec suivi)

### 1) Conception & préparation
- [x] Vérifier l’existant (modèles, contrôleurs, vues, metadata) pour éviter toute duplication.
	- Existant UI : cartes “sub-card” sur le dashboard en [application/views/bs_dashboard.php](application/views/bs_dashboard.php).
	- Prototype UI : composant “card-gvv” en [doc/prototypes/prototype_dashboard.html](doc/prototypes/prototype_dashboard.html).
	- Pas de module dédié “cartes / météo” repéré ; la météo actuelle est limitée aux séances de formation (ex. [application/controllers/formation_seances.php](application/controllers/formation_seances.php)).
- [x] Valider les besoins CRUD et les champs requis (titre, type, HTML, miniature, lien, ordre, catégorie, visibilité).
	- Champs retenus : titre, type (html | miniature+lien), fragment HTML, URL miniature, URL lien, ordre d’affichage, catégorie (texte libre), actif/visible (booléen).
- [x] Définir les règles d’affichage des cartes sur le dashboard (grille, tailles, fallback).
	- Grille responsive Bootstrap : row g-3 + colonnes col-12 / col-md-6 / col-lg-4.
	- Carte avec en‑tête (titre) + corps ; snippet rendu tel quel avec lien direct visible en fallback.
	- Miniature affichée en ratio fixe (image responsive), lien externe via bouton.

### 2) Base de données & migrations
- [x] Concevoir la table des cartes (schéma, types, index).
	- Table retenue : `preparation_cards` (cartes de préparation des vols).
	- Champs principaux : titre, type (html | link), html_fragment, image_url, link_url, catégorie libre, ordre, visible, created_at, updated_at.
- [x] Créer la migration (application/migrations) et mettre à jour application/config/migration.php.
	- Migration : [application/migrations/069_create_preparation_cards.php](application/migrations/069_create_preparation_cards.php)
	- Version : [application/config/migration.php](application/config/migration.php)
- [x] Ajouter un test de migration (appliquer/rollback) dans la suite de tests appropriée.
	- Test : [application/tests/mysql/PreparationCardsMigrationTest.php](application/tests/mysql/PreparationCardsMigrationTest.php)

### 3) Modèles (data)
- [x] Créer/mettre à jour le modèle pour les cartes (CRUD, tri, filtre par visibilité).
	- Modèle : [application/models/preparation_cards_model.php](application/models/preparation_cards_model.php)
- [x] Garantir que select_page() retourne la clé primaire même si non affichée.
	- Inclus via `id` dans `select_page()`.
- [x] Gérer l’ordre d’affichage et la catégorie libre (string).
	- Ordre géré par renumérotation `display_order`, catégorie stockée en texte libre.

### 4) Métadonnées (rendu)
- [x] Déclarer les champs dans application/libraries/Gvvmetadata.php (types, sous-types, libellés).
	- Définitions ajoutées pour `preparation_cards` et `vue_preparation_cards`.
	- Fichier : [application/libraries/Gvvmetadata.php](application/libraries/Gvvmetadata.php)
- [x] Définir les champs pour le formulaire et la table (titre, type, HTML, miniature, lien, ordre, catégorie).
	- Champs et libellés définis pour formulaire/table.

### 5) Contrôleurs (logique)
- [x] Ajouter le contrôleur/page “Météo & préparation des vols” côté lecture.
	- Contrôleur : [application/controllers/meteo.php](application/controllers/meteo.php) (méthode `index()`).
- [x] Ajouter le CRUD administrateur avec contrôle d’accès.
	- Contrôleur : [application/controllers/meteo.php](application/controllers/meteo.php) (méthodes `page()`, `create()`, `edit()`, `delete()`).
- [x] Assurer le fallback si snippet externe en échec (lien direct visible).
	- Fallback lien direct dans [application/views/meteo/publicView.php](application/views/meteo/publicView.php)

### 6) Vues (UI)
- [x] Créer la page de listing des cartes en style dashboard (Bootstrap 5).
	- Vue publique : [application/views/meteo/publicView.php](application/views/meteo/publicView.php)
- [x] Créer les vues de création/édition/suppression.
	- Vues admin : [application/views/meteo/bs_tableView.php](application/views/meteo/bs_tableView.php), [application/views/meteo/bs_formView.php](application/views/meteo/bs_formView.php)
- [x] Rendre les cartes “HTML embarqué” et “miniature + lien” selon le type.
	- Rendu conditionnel + fallback lien direct dans la vue publique.

### 7) Internationalisation
- [x] Ajouter les libellés FR/EN/NL dans les fichiers de langue.
	- FR : [application/language/french/meteo_lang.php](application/language/french/meteo_lang.php)
	- EN : [application/language/english/meteo_lang.php](application/language/english/meteo_lang.php)
	- NL : [application/language/dutch/meteo_lang.php](application/language/dutch/meteo_lang.php)

### 8) Sécurité & conformité
- [x] Documenter la responsabilité admin sur le contenu externe.
	- Message dans le formulaire admin : [application/views/meteo/bs_formView.php](application/views/meteo/bs_formView.php)
	- Libellés : [application/language/french/meteo_lang.php](application/language/french/meteo_lang.php), [application/language/english/meteo_lang.php](application/language/english/meteo_lang.php), [application/language/dutch/meteo_lang.php](application/language/dutch/meteo_lang.php)
- [x] Vérifier l’usage HTTPS et les domaines autorisés (si décision de whitelist).
	- Rappel HTTPS inclus dans le formulaire admin (aucune whitelist appliquée à ce stade).

### 9) Tests
- [ ] Tests unitaires du modèle (CRUD, tri, visibilité).
- [x] Tests d’intégration (CRUD complet + affichage via metadata).
	- MySQL : [application/tests/mysql/PreparationCardsModelTest.php](application/tests/mysql/PreparationCardsModelTest.php)
- [x] Test de migration (apply + rollback).
	- MySQL : [application/tests/mysql/PreparationCardsMigrationTest.php](application/tests/mysql/PreparationCardsMigrationTest.php)
- [ ] Smoke test UI (Playwright) pour vérifier l’accès à la page et l’affichage des cartes.

### 10) Validation
- [ ] Vérifier le chargement page < 2s hors temps tiers.
- [ ] Vérifier l’affichage correct sur Chrome/Firefox/Edge.
- [ ] Vérifier que les non-admins ne peuvent pas modifier les cartes.

### 11) Documentation
- [ ] Mettre à jour la documentation utilisateur (admin) sur l’ajout de cartes.
- [ ] Documenter les limites et risques des snippets HTML.
- [ ] Ajouter un exemple de carte HTML et de carte “miniature + lien”.
