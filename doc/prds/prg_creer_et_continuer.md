# PRD : Application du Pattern PRG au workflow "Créer et continuer"

**Date:** 2025-12-02
**Version:** 2.0
**Statut:** Proposition
**Priorité:** Moyenne
**Effort estimé:** Moyen (4-5 heures)

**Historique des versions :**
- v2.0 (2025-12-02) : Approche modifiée - PRG + préservation du pré-remplissage (Option A)
- v1.1 (2025-12-02) : Correction description technique - Le formulaire actuel est PRÉ-REMPLI (pas vide)
- v1.0 (2025-12-02) : Version initiale

---

## 1. CONTEXTE

### 1.0 Choix de l'approche (Option A)

**Décision :** Implémenter le pattern PRG **avec préservation du pré-remplissage**.

**Justification :**
- ✅ **Sécurité** : Élimine le risque de doublon F5
- ✅ **Fonctionnalité** : Préserve le workflow de saisie rapide (comptabilité, vols)
- ✅ **UX** : Meilleure expérience utilisateur globale
- ⚠️ **Complexité** : Légèrement plus complexe à implémenter (flash data) mais bénéfices justifient l'effort

**Alternatives rejetées :**
- ❌ **Option B** (PRG simple, formulaire vide) : Perte de fonctionnalité utile pour power users
- ❌ **Conserver comportement actuel** : Risque de doublon inacceptable

### 1.1 Situation Actuelle

Le codebase GVV applique le pattern Post-Redirect-Get (PRG) de manière cohérente après les opérations de création/modification réussies, **sauf** dans un cas spécifique : le workflow "Créer et continuer".

**Comportement actuel du bouton "Créer et continuer" :**
- L'utilisateur remplit un formulaire de création
- L'utilisateur clique sur le bouton "Créer et continuer"
- L'enregistrement est créé en base de données avec succès
- La page affiche directement le formulaire **pré-rempli avec les données qui viennent d'être créées** et un message de succès
- **Problème** : L'URL reste une URL POST, pas de redirection GET

**Localisation du code :**
- `application/libraries/Gvv_Controller.php` ligne 557-573 (comportement par défaut)
- `application/controllers/compta.php` ligne 334-342 (override pour écritures comptables)
- Potentiellement d'autres contrôleurs héritant ce comportement

### 1.2 Problème Identifié

**Vulnérabilité : Double soumission involontaire**

Lorsque l'utilisateur utilise "Créer et continuer" puis appuie sur F5 (rafraîchir la page) :
1. La page affichée est le résultat d'un POST (formulaire pré-rempli + message succès)
2. Le navigateur détecte qu'il s'agit d'une page POST
3. Le navigateur affiche un avertissement : "Confirmer la nouvelle soumission du formulaire ?"
4. Si l'utilisateur confirme → **Le POST ORIGINAL est re-soumis avec les mêmes données**
5. **Un doublon identique est créé en base de données**

**Mécanisme technique :**
- `$this->data` contient les valeurs POST (lignes 293-305 dans compta.php)
- Le formulaire HTML est rendu avec ces valeurs → formulaire pré-rempli
- F5 re-soumet le POST original (pas le contenu HTML affiché)
- Pas de contrainte UNIQUE → doublon créé

**Scénario d'erreur type :**
- Comptable crée une écriture (date=01/12, compte1=512, compte2=411, montant=100€) avec "Créer et continuer"
- Page affiche formulaire pré-rempli + message "Écriture EC-123 créée avec succès"
- Comptable appuie sur F5 par habitude (vérifier, rafraîchir, etc.)
- Navigateur re-soumet POST avec date=01/12, compte1=512, compte2=411, montant=100€
- → Création d'une écriture EC-124 identique (doublon)

### 1.3 Impact

| Aspect | Niveau | Description |
|--------|--------|-------------|
| **Sécurité/Intégrité données** | Moyen | Risque de doublons en base |
| **Fréquence** | Faible | Workflow peu utilisé |
| **Utilisateurs concernés** | Tous | Mais surtout power users (comptabilité, gestion) |
| **Détection** | Difficile | Utilisateur peut ne pas remarquer le doublon |
| **Correction** | Manuelle | Nécessite suppression manuelle des doublons |

---

## 2. OBJECTIFS

### 2.1 Objectif Principal

Appliquer le pattern Post-Redirect-Get (PRG) au workflow "Créer et continuer" pour **éliminer le risque de double soumission involontaire**.

### 2.2 Objectifs Spécifiques

1. **Sécurité** : Éliminer la possibilité de créer des doublons via F5 après "Créer et continuer"
2. **Cohérence** : Aligner le comportement de "Créer et continuer" avec le pattern PRG standard
3. **UX** : **Préserver** la fonctionnalité de pré-remplissage utile pour la saisie rapide d'éléments similaires
4. **Performance** : Maintenir le workflow de saisie rapide (comptabilité, vols, etc.)
5. **Rétrocompatibilité** : Ne pas casser le comportement existant des autres boutons

### 2.3 Non-Objectifs

- Modifier le comportement des échecs de validation (doit rester sans redirect)
- Modifier le comportement des erreurs de base de données (doit rester sans redirect)
- Modifier le comportement des requêtes AJAX
- Ajouter de nouvelles fonctionnalités au workflow "Créer et continuer"

---

## 3. EXIGENCES FONCTIONNELLES

### 3.1 REQ-001 : Redirection après succès avec préservation des valeurs

**Priorité :** Critique

Après une création réussie avec le bouton "Créer et continuer", le système **DOIT** :
1. Créer l'enregistrement en base de données
2. Stocker le message de succès en session flash (`set_flashdata('success', $msg)`)
3. **Stocker les données créées en session flash** (`set_flashdata('prefill_data', $processed_data)`)
4. Effectuer une redirection HTTP vers la page de création (GET)
5. Réinjecter les données stockées dans le formulaire
6. Afficher le formulaire pré-rempli avec les valeurs de l'élément créé
7. Afficher le message de succès stocké en flash

**Mécanisme technique :**
```php
// Dans formValidation() après création réussie
$this->session->set_flashdata('success', $msg);
$this->session->set_flashdata('prefill_data', $processed_data);
redirect($this->controller . "/create");

// Dans create()
$prefill = $this->session->flashdata('prefill_data');
if ($prefill) {
    $this->data = array_merge($this->gvvmetadata->defaults_list($table), $prefill);
}
```

**Résultat attendu :**
- URL finale = `/controller/create` (GET)
- Formulaire **pré-rempli** avec les valeurs de l'élément créé (pour faciliter saisie d'éléments similaires)
- Message "Élément [ID] créé avec succès" affiché
- F5 recharge le formulaire en GET → **pas de doublon** (formulaire vide ou avec valeurs par défaut)

### 3.2 REQ-002 : Message de succès

**Priorité :** Élevée

Le message de succès **DOIT** :
- Être identique au message actuel (préserver UX)
- Inclure l'identifiant ou l'image de l'élément créé
- S'afficher dans une zone de notification visible (classe Bootstrap `alert alert-success`)
- Être automatiquement supprimé après affichage (mécanisme flash)

**Format du message :**
```
"[Image/ID de l'élément] créé(e) avec succès."
```

Exemples :
- Écriture comptable : "Écriture EC2025-001 créée avec succès."
- Vol : "Vol 2025-12-02-001 créé avec succès."
- Membre : "Membre DUPONT Jean créé avec succès."

### 3.3 REQ-003 : Comportement bouton "Créer" standard

**Priorité :** Élevée

Le comportement du bouton "Créer" (sans "et continuer") **NE DOIT PAS** être modifié.

**Comportement actuel à préserver :**
- Création de l'enregistrement
- Redirection vers la page de liste ou de détail (selon contrôleur)
- Message de succès en flash

### 3.4 REQ-004 : Contrôleurs concernés

**Priorité :** Critique

La correction **DOIT** s'appliquer à :

**Obligatoire :**
- `application/libraries/Gvv_Controller.php` (comportement par défaut)
- `application/controllers/compta.php` (override spécifique)

**À vérifier et corriger si nécessaire :**
- Tous les contrôleurs héritant de `Gvv_Controller` et utilisant le bouton "Créer et continuer"
- Identifier via recherche de code les autres implémentations

### 3.5 REQ-005 : Nettoyage des champs non pré-remplissables

**Priorité :** Élevée

Certains champs **NE DOIVENT PAS** être pré-remplis même avec la fonctionnalité de préservation :

**Champs à exclure systématiquement :**
- `id` - Clé primaire auto-incrémentée
- `date_creation` - Date de création de l'enregistrement original
- Tout champ avec contrainte UNIQUE qui causerait une erreur

**Mécanisme technique :**
```php
// Avant de stocker en flash
$prefill_data = $processed_data;
unset($prefill_data['id']);
unset($prefill_data['date_creation']);
// Ajouter autres champs spécifiques si nécessaire

$this->session->set_flashdata('prefill_data', $prefill_data);
```

**Champs à conserver pour pré-remplissage :**
- Comptes comptables (`compte1`, `compte2`)
- Pilotes, avions, instructeurs
- Dates d'opération (peuvent être modifiées par l'utilisateur)
- Montants (peuvent être modifiés)
- Descriptions (peuvent être modifiées)

### 3.6 REQ-006 : Préservation des workflows d'erreur

**Priorité :** Critique

Les cas d'erreur **NE DOIVENT PAS** être modifiés (pas de redirect) :

1. **Échec de validation** : Réaffichage direct du formulaire avec erreurs + données préservées
2. **Erreur base de données** : Réaffichage direct du formulaire avec message d'erreur + données préservées
3. **Erreur métier** : Réaffichage direct du formulaire avec message d'erreur + données préservées

**Raison :** Ces cas doivent préserver les données saisies pour permettre correction immédiate.

---

## 4. EXIGENCES NON FONCTIONNELLES

### 4.1 NFR-001 : Performance

**Priorité :** Moyenne

- Le redirect ne doit pas ajouter de délai perceptible (< 100ms)
- Pas d'impact sur le temps de chargement de la page de création
- Pas d'augmentation du nombre de requêtes au serveur

### 4.2 NFR-002 : Compatibilité

**Priorité :** Élevée

La modification **DOIT** être compatible avec :
- PHP 7.4
- CodeIgniter 2.x
- Tous les navigateurs supportés (Chrome, Firefox, Safari, Edge)
- Les tests automatisés existants

### 4.3 NFR-003 : Maintenabilité

**Priorité :** Élevée

- Le code modifié doit suivre les conventions GVV existantes
- La logique doit rester centralisée dans `Gvv_Controller`
- Les contrôleurs enfants ne doivent pas nécessiter de modifications (sauf overrides explicites)
- Le code doit être commenté pour expliquer le pattern PRG

### 4.4 NFR-004 : Testabilité

**Priorité :** Moyenne

La modification **DEVRAIT** :
- Être vérifiable par test manuel simple
- Ne pas casser les tests PHPUnit existants
- Permettre l'ajout de tests automatisés pour vérifier le redirect

---

## 5. CRITÈRES D'ACCEPTATION

### 5.1 Critères Fonctionnels

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-001** | Après "Créer et continuer", l'URL est une URL GET | Vérifier dans la barre d'adresse : `/controller/create` |
| **AC-002** | Le message de succès s'affiche correctement | Vérifier présence de l'alerte verte avec message |
| **AC-003** | Le formulaire est **pré-rempli** avec les valeurs créées | Vérifier que les champs utiles sont pré-remplis (comptes, pilote, etc.) |
| **AC-004** | Les champs ID et date_creation sont vides/par défaut | Vérifier que id et date_creation ne sont PAS pré-remplis |
| **AC-005** | F5 recharge le formulaire (GET) sans créer de doublon | Appuyer sur F5, vérifier pas de doublon en BDD |
| **AC-006** | Le bouton "Créer" standard fonctionne toujours | Tester création avec "Créer" → doit rediriger vers liste |
| **AC-007** | Les erreurs de validation s'affichent sans redirect | Soumettre formulaire invalide → vérifier affichage erreurs |
| **AC-008** | Les erreurs DB s'affichent sans redirect | Forcer erreur DB → vérifier affichage erreur + données préservées |
| **AC-009** | Workflow saisie rapide préservé | Créer 3 écritures similaires en modifiant seulement montant → doit être rapide |

### 5.2 Critères de Régression

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-REG-001** | Les autres contrôleurs fonctionnent normalement | Tester création dans 5+ contrôleurs différents |
| **AC-REG-002** | Les tests PHPUnit passent | Exécuter `./run-all-tests.sh` |
| **AC-REG-003** | Pas de messages d'erreur PHP | Vérifier logs Apache : `/var/log/apache2/error.log` |

### 5.3 Critères de Performance

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-PERF-001** | Temps de réponse ≤ temps actuel + 100ms | Chronométrer avant/après avec outils dev navigateur |

---

## 6. CAS D'USAGE

### 6.1 CU-001 : Création multiple d'écritures comptables

**Acteur :** Comptable

**Préconditions :**
- Utilisateur connecté avec rôle "tresorier"
- Page de création d'écriture comptable ouverte

**Flux nominal :**
1. Utilisateur remplit le formulaire d'écriture (compte1=512, compte2=411, montant=100€, description="Facture A")
2. Utilisateur clique sur "Créer et continuer"
3. **Système crée l'écriture en base**
4. **Système stocke message de succès en flash**
5. **Système stocke données créées en flash (sauf id, date_creation)**
6. **Système redirige vers `/compta/create` (GET)**
7. Système affiche formulaire **pré-rempli** avec compte1=512, compte2=411, montant=100€, description="Facture A"
8. Système affiche message de succès en haut de la page
9. Utilisateur **modifie seulement** montant=150€ et description="Facture B"
10. Utilisateur clique sur "Créer et continuer"
11. Répétition des étapes 3-10 pour chaque écriture

**Gain de temps :** Évite de re-saisir les comptes à chaque fois

**Flux alternatif 1A : Utilisateur appuie sur F5 après création**
- 8a. Utilisateur appuie sur F5 après voir le formulaire pré-rempli
- 8b. **Système recharge la page de création (GET)**
- 8c. Formulaire avec valeurs par défaut s'affiche (message de succès et pré-remplissage disparus)
- 8d. **Aucun doublon créé en base**
- Utilisateur peut saisir une nouvelle écriture complètement différente

**Flux alternatif 1B : Erreur de validation**
- 3a. Validation échoue (ex: compte1 = compte2)
- 3b. **Système affiche formulaire avec erreurs (PAS de redirect)**
- 3c. Données saisies sont préservées
- 3d. Utilisateur corrige et re-soumet
- Retour à l'étape 3

### 6.2 CU-002 : Création multiple de vols

**Acteur :** Pilote ou instructeur

**Préconditions :**
- Utilisateur connecté avec droits de saisie de vol
- Page de création de vol ouverte

**Flux nominal :**
1. Utilisateur remplit le formulaire de vol (date=02/12, pilote=DUPONT, avion=F-CGXX, élève=MARTIN, durée=1h00)
2. Utilisateur clique sur "Créer et continuer"
3. **Système crée le vol en base**
4. **Système stocke message et données en flash**
5. **Système redirige vers `/vols_avion/create` (GET)**
6. Système affiche formulaire **pré-rempli** avec date=02/12, pilote=DUPONT, avion=F-CGXX
7. Utilisateur **modifie seulement** élève=BERNARD, durée=0h45
8. Utilisateur clique sur "Créer et continuer"
9. Répète pour tous les vols de la journée

**Bénéfice :**
- En fin de journée d'instruction, saisie rapide de 10-20 vols sans re-saisir pilote/avion
- Pas de risque de doublons avec F5

### 6.3 CU-003 : Création d'élément puis consultation

**Acteur :** Utilisateur quelconque

**Préconditions :**
- Utilisateur crée un élément avec "Créer et continuer"
- Veut vérifier que la création a réussi

**Flux nominal :**
1. Utilisateur crée un élément avec "Créer et continuer"
2. Message de succès s'affiche
3. Utilisateur navigue vers la page de liste (lien dans menu)
4. Utilisateur vérifie que l'élément apparaît dans la liste
5. Utilisateur utilise le bouton "Retour" du navigateur
6. **Système affiche la page de création (GET)**
7. **Aucun doublon créé**

**Bénéfice :** Navigation naturelle sans effet de bord.

---

## 7. CONTRAINTES

### 7.1 Contraintes Techniques

1. **Framework :** CodeIgniter 2.x (legacy) - Pas de migration vers CI3/CI4
2. **PHP :** Version 7.4 uniquement
3. **Navigateurs :** Support Chrome, Firefox, Safari, Edge (versions récentes)
4. **Sessions :** Utilisation des flash data CodeIgniter existantes

### 7.2 Contraintes de Déploiement

1. **Zéro downtime :** La modification ne doit pas nécessiter d'arrêt de service
2. **Migration données :** Aucune migration de base de données nécessaire
3. **Rollback :** Doit être possible en restaurant les fichiers PHP précédents

### 7.3 Contraintes de Compatibilité

1. **Rétrocompatibilité :** Les contrôleurs existants ne doivent pas nécessiter de modification
2. **Tests :** Ne doit pas casser les tests PHPUnit existants
3. **Configuration :** Aucun changement de configuration nécessaire

---

## 8. HORS PÉRIMÈTRE

Les éléments suivants sont **explicitement exclus** de ce PRD :

### 8.1 Modifications Non Incluses

- ❌ Modification du comportement de validation (erreurs doivent rester sans redirect)
- ❌ Modification des messages d'erreur existants
- ❌ Ajout de confirmation "Voulez-vous créer un autre élément ?"
- ❌ Ajout de compteur "X éléments créés dans cette session"
- ❌ Modification de l'apparence visuelle des formulaires
- ❌ Migration vers AJAX pour les soumissions de formulaires
- ❌ Ajout de validation côté client (JavaScript)

### 8.2 Contrôleurs Non Concernés

- ❌ Contrôleurs n'utilisant pas le bouton "Créer et continuer"
- ❌ Contrôleurs utilisant uniquement AJAX
- ❌ Contrôleurs en mode lecture seule (visualisation)

### 8.3 Fonctionnalités Futures

Les éléments suivants pourront être traités dans des PRDs ultérieurs :

- Migration progressive vers des soumissions AJAX
- Validation en temps réel côté client
- Messages de succès avec undo/annuler
- Statistiques de création (compteur de session)

---

## 9. DÉPENDANCES

### 9.1 Dépendances Techniques

- Aucune nouvelle bibliothèque nécessaire
- Utilisation de fonctions CodeIgniter existantes : `redirect()`, `set_flashdata()`

### 9.2 Dépendances Organisationnelles

- Validation par le mainteneur du projet
- Tests en environnement de développement avant déploiement

---

## 10. RISQUES

### 10.1 Risques Techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Casse des tests existants | Faible | Moyen | Exécuter tests avant merge |
| Comportement inattendu dans certains contrôleurs | Faible | Moyen | Tests manuels sur 5+ contrôleurs |
| Performance dégradée | Très faible | Faible | Chronométrer avant/après |

### 10.2 Risques Utilisateur

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Surprise du changement de comportement | Faible | Faible | Comportement reste similaire pour l'utilisateur |
| Perte de données en cours de saisie | Très faible | Élevé | Ne concerne que le succès (données déjà sauvées) |

---

## 11. MÉTRIQUES DE SUCCÈS

### 11.1 Métriques Quantitatives

| Métrique | Cible | Mesure |
|----------|-------|--------|
| **Tests PHPUnit réussis** | 100% | `./run-all-tests.sh` |
| **Contrôleurs testés manuellement** | ≥ 5 | Tests de non-régression |
| **Doublons créés avec F5** | 0 | Test manuel post-modification |
| **Temps de réponse** | ≤ +100ms | Chrome DevTools |

### 11.2 Métriques Qualitatives

- ✅ L'expérience utilisateur est préservée ET sécurisée (formulaire pré-rempli + pas de doublon F5)
- ✅ Workflow de saisie rapide maintenu (comptabilité, vols)
- ✅ Pas de régression sur les autres workflows
- ✅ Code conforme aux conventions GVV
- ✅ Documentation inline ajoutée

---

## 12. PLANNING ET JALONS

### 12.1 Estimation d'Effort

| Phase | Durée estimée | Description |
|-------|---------------|-------------|
| **Analyse** | 1h | ✅ Déjà complétée (ce PRD v2.0) |
| **Développement** | 2h | Modification `Gvv_Controller.php` + `compta.php` + mécanisme flash |
| **Tests manuels** | 1h | Test pré-remplissage + F5 sur 5-6 contrôleurs + cas limites |
| **Tests automatisés** | 0.5h | Vérification suite PHPUnit |
| **Documentation** | 0.5h | Commentaires dans le code |
| **TOTAL** | **5h** | |

**Justification de l'augmentation :**
- Développement plus complexe (gestion flash data, nettoyage champs)
- Tests plus complets (vérifier pré-remplissage + exclusions)

### 12.2 Jalons

| Jalon | Livrable | Critère de succès |
|-------|----------|-------------------|
| **M1 : Modification code** | Code modifié dans `Gvv_Controller.php` et `compta.php` | Code compile sans erreur PHP |
| **M2 : Tests manuels** | Rapport de tests sur 5+ contrôleurs | Tous les critères d'acceptation validés |
| **M3 : Tests automatisés** | Suite PHPUnit passante | 100% des tests passent |
| **M4 : Documentation** | Commentaires ajoutés au code | Documentation inline présente |
| **M5 : Revue de code** | Code reviewé | Validation par mainteneur |

---

## 13. VALIDATION ET APPROBATION

### 13.1 Parties Prenantes

| Rôle | Nom | Responsabilité |
|------|-----|----------------|
| **Auteur PRD** | Claude Code | Rédaction et analyse |
| **Mainteneur** | Frédéric Peignot | Validation et approbation |
| **Testeur** | Frédéric Peignot | Tests de non-régression |

### 13.2 Processus de Validation

1. ✅ **Revue PRD** : Validation des exigences et du périmètre
2. ⏳ **Implémentation** : Modification du code selon PRD
3. ⏳ **Tests** : Validation des critères d'acceptation
4. ⏳ **Revue de code** : Approbation par mainteneur
5. ⏳ **Merge** : Intégration dans la branche main

### 13.3 Critères d'Approbation

Le PRD est approuvé si :
- ✅ Les exigences sont claires et testables
- ✅ Le périmètre est bien défini (inclusions/exclusions)
- ✅ Les risques sont identifiés et atténués
- ✅ L'effort est raisonnable (≤ 1 jour)

---

## 14. ANNEXES

### 14.1 Références

- **Design Note :** `doc/design_notes/prg_pattern_analysis.md`
- **Code Source :** `application/libraries/Gvv_Controller.php`
- **Override :** `application/controllers/compta.php`

### 14.2 Glossaire

| Terme | Définition |
|-------|------------|
| **PRG** | Post-Redirect-Get - Pattern web évitant les doubles soumissions |
| **Flash data** | Données de session disponibles uniquement pour le prochain request |
| **Gvv_Controller** | Contrôleur parent dans GVV dont héritent la majorité des contrôleurs |
| **load_last_view()** | Fonction GVV chargeant une vue sans redirect |
| **redirect()** | Fonction CodeIgniter effectuant une redirection HTTP |

---

**Document approuvé par :** _[À compléter]_
**Date d'approbation :** _[À compléter]_
**Prochaine étape :** Implémentation selon design note
