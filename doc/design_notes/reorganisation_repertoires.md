# Analyse — Réorganisation du codebase en blocs fonctionnels

Date : 8 février 2026

## Contexte

Le codebase GVV atteint une taille significative :

| Composant | Nombre | Sous-répertoires existants |
|-----------|--------|---------------------------|
| Controllers | 66 | `api/` (2 fichiers) |
| Models | 57 | `dx_auth/` (4 fichiers) |
| Views | 58 répertoires | chacun est déjà un répertoire par contrôleur |
| Language (FR) | 48 fichiers | aucun |

La question se pose de regrouper ces fichiers en sous-répertoires par bloc fonctionnel pour améliorer la lisibilité et la maintenabilité.

## Support natif CodeIgniter 2.x

| Composant | Sous-répertoires | Mécanisme |
|-----------|-----------------|-----------|
| Controllers | **OUI** natif | `Router.php` détecte automatiquement les sous-répertoires, URL devient `/sous-rep/controller/method` |
| Models | **OUI** natif | `$this->load->model('sous-rep/model_name')` |
| Views | **OUI** natif | `load_last_view('sous-rep/view')` fonctionne déjà |
| Language | **NON** | `Lang.php` ne parse pas les `/` — fichiers à plat uniquement |

---

## Avantages

1. **Lisibilité** — Avec 66 contrôleurs dans un seul répertoire, trouver un fichier relève de la recherche dans un annuaire. Un regroupement par bloc fonctionnel rend la navigation immédiate.

2. **Séparation des responsabilités** — On identifie visuellement les modules indépendants. Un développeur qui travaille sur la compta ne voit que les fichiers compta.

3. **Maintenance ciblée** — Quand un bloc entier est impacté (ex: refactoring formation), le périmètre est clairement délimité.

4. **Cohérence** — Les vues sont déjà organisées en répertoires par contrôleur. Aligner les contrôleurs et modèles sur la même logique est naturel.

5. **Scalabilité** — Le système d'acceptations ajoute déjà 5 contrôleurs et 4 modèles prévus. Chaque nouvelle fonctionnalité aggrave le problème.

6. **Précédent existant** — `api/` et `dx_auth/` prouvent que le pattern fonctionne déjà dans GVV.

## Inconvénients et risques

1. **Impact sur les URLs (contrôleurs)** — C'est le problème majeur. Déplacer `compta.php` dans `controllers/accounting/compta.php` change l'URL de `/compta/journal` à `/accounting/compta/journal`. Cela casse :
   - Tous les `redirect()` et `site_url()` dans le code
   - Les `controller_url()` dans les vues et menus
   - Les bookmarks des utilisateurs
   - Les éventuels liens externes

   *Atténuation* : le fichier `routes.php` de CI2 peut créer des alias pour maintenir la compatibilité, mais cela ajoute de la complexité.

2. **Volume de modifications** — Chaque déplacement de contrôleur nécessite de mettre à jour :
   - Les `include()` du parent (`Gvv_Controller.php`)
   - Les `redirect()` dans le contrôleur lui-même
   - Les `site_url()` / `controller_url()` dans toutes les vues qui y font référence
   - Les références dans `bs_menu.php`
   - Les tests Playwright (URLs)

3. **Fichiers de langue non supportés** — Il faudrait soit garder les langues à plat (incohérence), soit surcharger `Lang.php` (modification du framework — interdit par convention GVV).

4. **Modèles partagés** — `membres_model.php` est utilisé partout (vols, formation, compta, documents...). Le placer dans un bloc fonctionnel crée une dépendance croisée qui contredit l'idée de blocs indépendants.

5. **Risque de régression** — Projet en production depuis 12 ans, 5-6 associations. Le ratio risque/bénéfice est défavorable pour un projet en maintenance.

6. **Tests** — Les 979 tests PHPUnit et les 39 tests Playwright devront être vérifiés et potentiellement adaptés.

7. **Effort non fonctionnel** — Aucune valeur ajoutée pour les utilisateurs. Effort significatif pour un bénéfice purement développeur.

---

## Avis

**Contrôleurs : déconseillé.** L'impact sur les URLs et le volume de modifications cascadées rend le ratio effort/risque/bénéfice défavorable pour un projet en maintenance avec des utilisateurs en production.

**Modèles et vues : faisable progressivement.** Aucun impact sur les URLs, modifications localisées aux `load->model()` et `load_last_view()`.

**Langues : impossible.** Pas de support natif, modification du framework interdite.

### Recommandation pragmatique

- **Modèles** : réorganiser en sous-répertoires. Impact limité, gains réels.
- **Vues** : déjà organisées par contrôleur, on peut regrouper les répertoires de vues dans des blocs. Impact limité.
- **Contrôleurs** : garder à plat mais adopter une convention de nommage par préfixe (déjà partiellement en place : `formation_*`, `vols_*`, `associations_*`, `acceptance_*`).
- **Langues** : garder à plat.

---

## Blocs fonctionnels proposés

| Bloc fonctionnel | Répertoire | Contrôleurs (préfixe) | Modèles | Vues |
|---|---|---|---|---|
| **Authentification & rôles** | `auth/` | `auth`, `authorization`, `login_as`, `user_roles_per_section` | `auth_users_model`, `authorization_model`, `types_roles_model`, `user_roles_per_section_model` + `dx_auth/*` | `auth/`, `authorization/`, `user_roles_per_section/` |
| **Membres** | `membres/` | `membre`, `adherents_report`, `licences` | `membres_model`, `adherents_report_model`, `licences_model` | `membre/`, `adherents_report/`, `licences/` |
| **Flotte** | `flotte/` | `avion`, `planeur`, `terrains` | `avions_model`, `planeurs_model`, `terrains_model` | `avion/`, `planeur/`, `terrains/` |
| **Vols** | `vols/` | `vols_planeur`, `vols_avion`, `vols_decouverte` | `vols_planeur_model`, `vols_avion_model`, `vols_decouverte_model` | `vols_planeur/`, `vols_avion/`, `vols_decouverte/` |
| **Formation** | `formation/` | `formation_autorisations_solo`, `formation_inscriptions`, `formation_progressions`, `formation_rapports`, `formation_seances` | `formation_*_model` (7 modèles) | `formation_*/` (5 répertoires) |
| **Comptabilité** | `compta/` | `compta`, `comptes`, `facturation`, `achats`, `associations_ecriture`, `associations_of`, `associations_releve`, `plan_comptable`, `rapprochements` | `ecritures_model`, `comptes_model`, `facturation_model`, `achats_model`, `associations_*_model`, `clotures_model`, `plan_comptable_model` | vues correspondantes |
| **Documents** | `documents/` | `archived_documents`, `document_types`, `attachments` | `archived_documents_model`, `document_types_model`, `attachments_model` | vues correspondantes |
| **Acceptations** | `acceptances/` | `acceptance_admin` (+ futurs `acceptance`, `acceptance_training`, `acceptance_external`, `acceptance_sign`) | `acceptance_*_model` (4 modèles) | `acceptance_admin/` (+ futurs) |
| **Communication** | `communication/` | `mails`, `email_lists` | `mails_model`, `email_lists_model` | `mails/`, `email_lists/` |
| **Calendrier & événements** | `calendrier/` | `calendar`, `event`, `events_types`, `programmes`, `presences`, `reservations` | `calendar_model`, `event_model`, `events_types_model`, `reservations_model` | vues correspondantes |
| **Rapports** | `rapports/` | `rapports`, `reports`, `historique`, `alarmes`, `FFVV` | `rapports_model`, `reports_model`, `historique_model` | vues correspondantes |
| **Configuration** | `config/` | `configuration`, `sections`, `tarifs`, `categories`, `categorie`, `tickets`, `types_ticket`, `pompes` | modèles correspondants | vues correspondantes |
| **Administration** | `admin/` | `admin`, `backend`, `import`, `migration`, `oneshot`, `dbchecks`, `tools`, `openflyers` | `configuration_model`, `dbchecks_model` | `admin/`, `backend/`, `migration/` |
| **Accueil** | *(racine)* | `welcome`, `partage`, `procedures` | — | `welcome/` |

---

## Approche recommandée si mise en oeuvre

1. **Phase 1** — Modèles uniquement (le plus sûr, le plus de valeur)
2. **Phase 2** — Regroupement des répertoires de vues
3. **Phase 3** — Éventuellement les contrôleurs, avec routes de compatibilité et migration progressive

Chaque phase doit être validée par les tests complets (PHPUnit + Playwright) avant de passer à la suivante.
