# Ventilation Rétrospective Multi-Sections 2024

**Date:** 2025-12-09
**Contexte:** Migration comptable multi-sections
**Objectif:** Retraiter les données 2024 pour assurer la comparabilité des exercices

---

## 1. Contexte et Problématique

### Situation initiale

GVV gère la comptabilité de la section planeur depuis 2011. En 2025, le système a été étendu pour supporter plusieurs sections :
- Section Planeur (existante depuis 2011)
- Section Avion (nouvelle)
- Section ULM (nouvelle)
- Services Généraux (nouvelle)

### Problème rencontré

Les nouvelles sections ont été initialisées au 01/01/2025 avec les soldes de clôture 2024, mais cela crée plusieurs incohérences :

1. **Incomparabilité des exercices**
   - Exercice 2024 : Section Planeur uniquement
   - Exercice 2025 : Toutes les sections cumulées
   - Les vues Bilan et Compte de Résultat sont faussées

2. **Comptes clients déséquilibrés**
   - Cotisations 2025 facturées en 2025
   - Mais paiements effectués en 2024
   - Soldes clients incorrects

3. **Comptes de résultat incomplets pour 2024**
   - Pas de données historiques pour les nouvelles sections
   - Impossible de comparer 2024/2025 à structure constante

### Architecture comptable existante

**Point crucial :** Toutes les initialisations de comptes ont été réalisées par des écritures comptables équilibrées avec le **compte 102 (Report à nouveau)**.

Exemple d'écriture d'ouverture au 01/01/2025 :
```
Date : 01/01/2025
Section Avion

Débit  : 512 - Banque Avion              10 000 €
Débit  : 411 - Clients Avion              5 000 €
Crédit : 102 - Report à nouveau Avion    15 000 €
```

Cette architecture est **comptablement orthodoxe** et constitue la base de la solution.

---

## 2. Principes Comptables Applicables

### Comptabilité de trésorerie
- Les opérations sont enregistrées à la date du relevé bancaire
- Respect du flux de trésorerie réel

### Principe de séparation des exercices
- Chaque exercice doit être autonome
- Les charges et produits doivent être rattachés à leur exercice

### Comparabilité des états financiers
- Les bilans et comptes de résultat doivent être comparables d'un exercice à l'autre
- Nécessite un retraitement rétrospectif lors de changements de structure

### Retraitement rétrospectif
- Technique comptable permettant de modifier les données historiques
- Utilisée lors de changements de méthodes ou de structure
- Doit préserver les soldes finaux (cohérence avec la réalité bancaire)

---

## 3. Solution Retenue : Écritures Rétrospectives avec Compensation Automatique (RAN - Retrospective Adjustment Nullification)

### 3.1 Principe de base

**Objectif :** Passer des écritures en 2024 pour les nouvelles sections tout en maintenant la cohérence des soldes au 01/01/2025.

**Mécanisme :**
1. Saisir une écriture datée de 2024 (ex: cotisations, charges, produits)
2. Calculer automatiquement l'impact sur les soldes
3. Passer des écritures de compensation (avec le compte 102) à la même date pour annuler l'impact
4. Garantir que les soldes au 01/01/2025 restent identiques à la réalité

**Point clé :** Les écritures de compensation sont passées à la **même date** que l'écriture principale, pas en date d'ouverture.

### 3.2 Exemple concret

#### Situation initiale
- Solde bancaire réel Section Avion au 01/01/2025 : **10 000 €**
- Solde compte client au 01/01/2025 : **250 €**
- Écriture d'ouverture actuelle :
  ```
  01/01/2025
  Débit  : 512 Banque Avion     10 000 €
  Crédit : 102 RAN Avion        10 000 €
  ```

#### Écriture à passer : Cotisations encaissées en 2024
```
15/12/2024
Débit  : 512 Banque Avion        100 €
Crédit : 411 Client Dupont       100 €
```

#### Problème
Après cette écriture, les soldes au 01/01/2025 seraient modifiés :
- Solde bancaire calculé : 10 100 € (au lieu de 10 000 €)
- Solde client calculé : 150 € (au lieu de 250 €)

#### Solution : Écritures de compensation à la même date

**Transaction atomique :**
```
ÉTAPE 1 - Passer l'écriture 2024 normale :
  Date : 15/12/2024
  Débit  : 512 Banque Avion        100 €
  Crédit : 411 Client Dupont       100 €
  Libellé : "Cotisation Dupont 2024"

ÉTAPE 2 - Écriture de compensation pour le compte 512 :
  Date : 15/12/2024
  Débit  : 102 RAN Avion           100 €
  Crédit : 512 Banque Avion        100 €
  Libellé : "Ajustement rétrospectif pour compenser l'écriture"
  Numéro pièce : [ID de l'écriture ÉTAPE 1]

ÉTAPE 3 - Écriture de compensation pour le compte 411 :
  Date : 15/12/2024
  Débit  : 411 Client Dupont       100 €
  Crédit : 102 RAN Avion           100 €
  Libellé : "Ajustement rétrospectif pour compenser l'écriture"
  Numéro pièce : [ID de l'écriture ÉTAPE 1]
```

**Résultat :**
- Impact écriture normale : +100 € sur compte 512, -100 € sur compte 411
- Impact compensations : -100 € sur compte 512, +100 € sur compte 411
- Net : Aucun impact sur les soldes finaux ✓
- Solde bancaire au 01/01/2025 : **10 000 € ✓** (inchangé)
- Solde client au 01/01/2025 : **250 € ✓** (inchangé)

### 3.3 Application aux comptes de résultat (600/700)

La même stratégie s'applique aux comptes de résultat, permettant de modifier le résultat de l'année précédente sans modifier les soldes bancaires finaux.

**Exemple - Charges 2024 Section Avion :**

```
TRANSACTION ATOMIQUE :

ÉTAPE 1 - Écriture synthétique 2024 :
  Date : 31/12/2024
  Débit  : 606 - Fournitures         15 000 € (total annuel)
  Crédit : 512 - Banque Avion        15 000 €
  Libellé : "Charges fournitures 2024"

ÉTAPE 2 - Compensation compte 606 :
  Date : 31/12/2024
  Débit  : 102 - RAN Avion           15 000 €
  Crédit : 606 - Fournitures         15 000 €
  Libellé : "Ajustement rétrospectif pour compenser l'écriture"
  Numéro pièce : [ID de l'écriture ÉTAPE 1]

ÉTAPE 3 - Compensation compte 512 :
  Date : 31/12/2024
  Débit  : 512 - Banque Avion        15 000 €
  Crédit : 102 - RAN Avion           15 000 €
  Libellé : "Ajustement rétrospectif pour compenser l'écriture"
  Numéro pièce : [ID de l'écriture ÉTAPE 1]
```

**Résultat :**
- Le compte 606 affiche bien 15 000 € de charges en 2024
- Le solde banque au 01/01/2025 reste inchangé : 10 000 € ✓
- Le résultat 2024 est modifié comme souhaité
- Les écritures de compensation s'annulent mutuellement pour les comptes de bilan

### 3.4 Validité comptable

Cette approche est **comptablement correcte** car :

✅ Toutes les écritures sont équilibrées
✅ Les soldes finaux correspondent à la réalité bancaire
✅ Le compte 102 (RAN) sert de compte de compensation
✅ C'est du retraitement rétrospectif classique en comptabilité
✅ La traçabilité est assurée par le référencement des écritures (champ Numéro pièce)
✅ Les écritures de compensation sont clairement identifiables

---

## 4. Architecture Technique : "Mode RAN" (Retrospective Adjustment Nullification)

### 4.1 Paramétrage du mode RAN

Le mode RAN est activé via un paramètre booléen dans `application/config/program.php` :

```php
/**
 * Mode RAN (Retrospective Adjustment Nullification)
 * Active la saisie d'écritures rétrospectives avec compensation automatique
 * Permet de passer des écritures 2024 sans modifier les soldes finaux
 */
$config['ran_mode_enabled'] = true;  // false par défaut
```

### 4.2 Comportement du mode RAN

#### 4.2.1 Restrictions d'accès

**IMPORTANT : Le mode RAN n'est activé que si TOUTES les conditions suivantes sont remplies :**

1. `ran_mode_enabled = true` dans `config/program.php`
2. **ET** l'utilisateur connecté a les droits d'administrateur

**Pour les utilisateurs non-administrateurs :**
- Le mode RAN reste **invisible** même si `ran_mode_enabled = true`
- Aucun indicateur visuel (fond rouge, bandeau) n'est affiché
- Le **contrôle de date de gel reste actif** normalement
- Le système fonctionne en mode standard, sans compensation automatique

Cette restriction garantit que seuls les administrateurs peuvent utiliser le mode RAN pour les opérations de ventilation rétrospective.

#### 4.2.2 Comportement actif (admin avec ran_mode_enabled = true)

Lorsque `ran_mode_enabled = true` ET que l'utilisateur est administrateur :

1. **Interface visuelle** :
   - Le formulaire de saisie d'écriture s'affiche sur **fond rouge**
   - Un avertissement est affiché : *"Mode d'écriture avec compensation rétrospective. Les écritures passées dans ce mode seront compensées afin de préserver le solde final des comptes concernés"*

2. **Contrôle de date de gel désactivé** :
   - Les écritures peuvent être saisies sur des périodes normalement gelées
   - Permet de passer des écritures en 2024 même si l'exercice est clôturé

3. **Compensation automatique** :
   - Pour chaque compte référencé dans l'écriture qui possède au moins une écriture d'initialisation avec le compte 102 de la section courante
   - Des écritures de compensation sont automatiquement générées pour préserver les soldes finaux

### 4.3 Justification du mode spécial

Un **mode de saisie dédié** se justifie car :

1. **Transaction atomique obligatoire** : Écriture 2024 + Compensations = indivisible
2. **Calculs automatiques** : Éviter les erreurs humaines dans le calcul des impacts
3. **Contrôles de cohérence** : Vérifier que les soldes finaux restent constants
4. **Traçabilité renforcée** : Référencement via le champ Numéro pièce
5. **Sécurité** : Interface distincte (fond rouge) pour éviter les erreurs de manipulation

### 4.4 Structure de la transaction

```php
/**
 * Saisir une écriture rétrospective avec compensation automatique (Mode RAN)
 *
 * @param string $date Date de l'écriture (doit être en 2024)
 * @param array $ecritures Lignes d'écriture (compte, débit, crédit, libellé)
 * @param string $section Identifiant de la section
 * @return int ID de l'écriture créée
 * @throws Exception Si incohérence ou erreur
 */
function saisir_ecriture_retrospective($date, $ecritures, $section) {

    // Validation : uniquement 2024
    if ($date >= '2025-01-01' || $date < '2024-01-01') {
        throw new Exception("Mode RAN: uniquement année 2024");
    }

    DB::beginTransaction();

    try {
        // ÉTAPE 1 : Récupérer les soldes actuels au 01/01/2025
        $soldes_avant = $this->get_soldes_au_01_01_2025($section);

        // ÉTAPE 2 : Passer l'écriture 2024 normalement
        $id_ecriture = $this->comptabilite->passer_ecriture($date, $ecritures);

        // ÉTAPE 3 : Identifier les comptes concernés (ayant une initialisation avec 102)
        $comptes_a_compenser = $this->identifier_comptes_a_compenser($ecritures, $section);

        // ÉTAPE 4 : Pour chaque compte, passer les écritures de compensation
        foreach ($comptes_a_compenser as $compte => $montant) {
            // Compensation : annuler l'impact sur le compte
            // Si on a débité le compte de X, on le crédite de X avec contrepartie 102
            // Si on a crédité le compte de X, on le débite de X avec contrepartie 102

            $this->passer_ecriture_compensation(
                $date,                    // Même date que l'écriture principale
                $compte,                   // Compte à compenser
                $montant,                  // Montant (positif = débit, négatif = crédit)
                $section,                  // Section
                $id_ecriture              // Référence à l'écriture principale
            );
        }

        // ÉTAPE 5 : Vérifier la cohérence des soldes finaux
        $soldes_apres = $this->get_soldes_au_01_01_2025($section);

        if (!$this->soldes_identiques($soldes_avant, $soldes_apres)) {
            throw new Exception("ERREUR CRITIQUE: Soldes 01/01/2025 modifiés !");
        }

        DB::commit();

        return $id_ecriture;

    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}

/**
 * Identifier les comptes nécessitant une compensation
 * Critère : comptes ayant au moins une écriture d'initialisation avec 102
 */
private function identifier_comptes_a_compenser($ecritures, $section) {
    $comptes = [];

    foreach ($ecritures as $ligne) {
        $compte = $ligne['compte'];

        // Vérifier si ce compte a une initialisation avec 102
        if ($this->a_initialisation_avec_102($compte, $section)) {
            $montant = $ligne['debit'] - $ligne['credit'];

            if (!isset($comptes[$compte])) {
                $comptes[$compte] = 0;
            }
            $comptes[$compte] += $montant;
        }
    }

    return $comptes;
}

/**
 * Passer une écriture de compensation pour annuler l'impact sur un compte
 */
private function passer_ecriture_compensation($date, $compte, $montant, $section, $id_ecriture_ref) {
    // L'écriture de compensation inverse l'impact :
    // - Si montant > 0 (on a débité), on crédite le compte et débite 102
    // - Si montant < 0 (on a crédité), on débite le compte et crédite 102

    $ecritures_compensation = [
        [
            'compte' => ($montant > 0) ? '102' : $compte,
            'debit'  => abs($montant),
            'credit' => 0,
            'libelle' => 'Ajustement rétrospectif pour compenser l\'écriture',
            'numero_piece' => $id_ecriture_ref
        ],
        [
            'compte' => ($montant > 0) ? $compte : '102',
            'debit'  => 0,
            'credit' => abs($montant),
            'libelle' => 'Ajustement rétrospectif pour compenser l\'écriture',
            'numero_piece' => $id_ecriture_ref
        ]
    ];

    $this->comptabilite->passer_ecriture($date, $ecritures_compensation);
}

/**
 * Vérifier si un compte a une écriture d'initialisation avec le compte 102
 */
private function a_initialisation_avec_102($compte, $section) {
    $sql = "SELECT COUNT(*) as nb
            FROM ecritures
            WHERE compte = ?
            AND section = ?
            AND date = '2025-01-01'
            AND EXISTS (
                SELECT 1 FROM ecritures e2
                WHERE e2.id_piece = ecritures.id_piece
                AND e2.compte = '102'
            )";

    $result = $this->db->query($sql, [$compte, $section]);
    return $result->row()->nb > 0;
}
```

### 4.5 Contrôles de cohérence

#### Contrôle 1 : Stabilité des soldes au 01/01/2025
```php
/**
 * Vérifier que les soldes au 01/01/2025 restent constants
 */
private function verifier_stabilite_soldes($section, $soldes_avant, $soldes_apres) {
    foreach ($soldes_avant as $compte => $montant) {
        if (abs($soldes_apres[$compte] - $montant) > 0.01) {
            throw new Exception(
                "Solde modifié pour $compte: " .
                "avant=$montant, après={$soldes_apres[$compte]}"
            );
        }
    }
    return true;
}
```

#### Contrôle 2 : Validation de période
```php
/**
 * Autoriser uniquement les écritures sur 2024
 */
private function valider_periode($date) {
    if ($date < '2024-01-01' || $date >= '2025-01-01') {
        throw new Exception(
            "Mode RAN : date doit être en 2024 (reçu: $date)"
        );
    }
    return true;
}
```

### 4.6 Traçabilité

La traçabilité est assurée par les mécanismes suivants :

1. **Champ Numéro de pièce comptable** : Toutes les écritures de compensation référencent l'ID de l'écriture principale
2. **Libellés explicites** : *"Ajustement rétrospectif pour compenser l'écriture"*
3. **Requêtes SQL** : Possibilité de retrouver toutes les écritures de compensation via le numéro de pièce

**Exemple de requête de traçabilité :**
```sql
-- Retrouver toutes les écritures de compensation pour une écriture donnée
SELECT *
FROM ecritures
WHERE numero_piece = 12458  -- ID de l'écriture principale
AND libelle LIKE 'Ajustement rétrospectif%'
ORDER BY date, id;
```

---

## 5. Interface Utilisateur

### 5.1 Modification du formulaire de saisie existant

Lorsque le mode RAN est activé (`$config['ran_mode_enabled'] = true`), le formulaire de saisie d'écriture standard est modifié comme suit :

**Modifications visuelles :**
- Fond de page : **rouge (#ffcccc ou similaire)**
- Bandeau d'avertissement en haut du formulaire

**Mockup ASCII :**

```
┌─────────────────────────────────────────────────────────────┐
│                    ⚠️  MODE RAN ACTIF                       │
│                                                              │
│ Mode d'écriture avec compensation rétrospective.            │
│ Les écritures passées dans ce mode seront compensées        │
│ afin de préserver le solde final des comptes concernés.     │
└─────────────────────────────────────────────────────────────┘

[Formulaire de saisie d'écriture standard]

Section : [Avion ▼]
Date    : [31/12/2024]

Écritures :
┌──────────┬───────────────────┬────────────┬──────────────┐
│ Compte   │ Libellé           │ Débit      │ Crédit       │
├──────────┼───────────────────┼────────────┼──────────────┤
│ 512      │ Banque            │            │ 100,00 €     │
│ 411      │ Client Dupont     │ 100,00 €   │              │
└──────────┴───────────────────┴────────────┴──────────────┘

Total : 100,00 € | 100,00 € ✓ Équilibré

[ Annuler ]  [ ✓ Valider ]
```

**Implémentation technique :**
- Le contrôle de date de gel est **désactivé** en mode RAN
- Les écritures peuvent être saisies sur des dates passées (2024)
- Aucune modification majeure du formulaire existant (réutilisation du code existant)

### 5.2 Message de succès après saisie

**Exigence importante : Transparence complète**

Après le passage d'une écriture en mode RAN, le message de succès doit **lister clairement toutes les écritures passées** :
- L'écriture principale
- Toutes les écritures de compensation créées automatiquement

**Exemple de message de succès :**

```
┌─────────────────────────────────────────────────────────────┐
│ ✓ Écriture enregistrée avec succès (Mode RAN)              │
└─────────────────────────────────────────────────────────────┘

ÉCRITURE PRINCIPALE (ID: 12458)
Date : 15/12/2024
  512 - Banque Avion           Débit : 100,00 €
  411 - Client Dupont          Crédit : 100,00 €
  Libellé : Cotisation Dupont 2024

ÉCRITURES DE COMPENSATION AUTOMATIQUES

Compensation 1 (ID: 12459)
Date : 15/12/2024
  102 - RAN Avion              Débit : 100,00 €
  512 - Banque Avion           Crédit : 100,00 €
  Libellé : Ajustement rétrospectif pour compenser l'écriture
  Référence : 12458

Compensation 2 (ID: 12460)
Date : 15/12/2024
  411 - Client Dupont          Débit : 100,00 €
  102 - RAN Avion              Crédit : 100,00 €
  Libellé : Ajustement rétrospectif pour compenser l'écriture
  Référence : 12458

─────────────────────────────────────────────────────────────
VÉRIFICATION :
✓ 3 écritures créées au total
✓ Soldes au 01/01/2025 : inchangés
✓ Équilibre comptable : OK
```

**Implémentation technique :**
```php
/**
 * Afficher le résumé des écritures passées en mode RAN
 */
function afficher_message_succes_ran($id_ecriture_principale) {
    // Récupérer l'écriture principale
    $ecriture = $this->comptabilite->get_ecriture($id_ecriture_principale);

    // Récupérer les compensations
    $compensations = $this->db->query(
        "SELECT * FROM ecritures
         WHERE numero_piece = ?
         AND libelle LIKE 'Ajustement rétrospectif%'
         ORDER BY id",
        [$id_ecriture_principale]
    )->result_array();

    // Construire le message détaillé
    $message = $this->construire_message_recapitulatif(
        $ecriture,
        $compensations
    );

    // Afficher avec mise en forme
    $this->session->set_flashdata('success_ran', $message);
}
```

### 5.3 Rapport post-saisie

**Pas de rapport PDF spécifique nécessaire.**

Les listings standard des opérations des comptes 102 en 2024 sont suffisants pour la traçabilité.

Pour vérifier les écritures de compensation :
```sql
-- Lister toutes les opérations sur le compte 102 en 2024
SELECT *
FROM ecritures
WHERE compte = '102'
AND date >= '2024-01-01'
AND date < '2025-01-01'
AND libelle LIKE 'Ajustement rétrospectif%'
ORDER BY date, id;
```

---

## 6. Modification et Suppression d'Écritures Rétrospectives

### 6.1 Problématique

Lorsqu'une écriture passée en 2024 (en mode RAN) doit être modifiée ou supprimée, il faut également gérer ses écritures de compensation associées pour maintenir la cohérence.

### 6.2 Stratégie de modification

**Approche recommandée : Suppression + Re-saisie**

Pour modifier une écriture rétrospective :

1. **Identifier l'écriture à modifier** et toutes ses compensations (via le champ `numero_piece`)
2. **Supprimer** l'écriture et ses compensations en mode transactionnel
3. **Re-saisir** la nouvelle écriture corrigée en mode RAN
4. Les nouvelles compensations seront automatiquement créées

**Exemple de code :**
```php
/**
 * Modifier une écriture rétrospective
 */
function modifier_ecriture_retrospective($id_ecriture, $nouvelles_donnees) {
    DB::beginTransaction();

    try {
        // 1. Supprimer l'écriture et ses compensations
        $this->supprimer_ecriture_retrospective($id_ecriture);

        // 2. Re-saisir avec les nouvelles données
        $nouvel_id = $this->saisir_ecriture_retrospective(
            $nouvelles_donnees['date'],
            $nouvelles_donnees['ecritures'],
            $nouvelles_donnees['section']
        );

        DB::commit();
        return $nouvel_id;

    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

### 6.3 Stratégie de suppression

Pour supprimer une écriture rétrospective, il faut supprimer **toutes les écritures associées** :

```php
/**
 * Supprimer une écriture rétrospective et ses compensations
 */
function supprimer_ecriture_retrospective($id_ecriture) {
    DB::beginTransaction();

    try {
        // 1. Récupérer les soldes avant suppression
        $soldes_avant = $this->get_soldes_au_01_01_2025($section);

        // 2. Supprimer les écritures de compensation
        $sql = "DELETE FROM ecritures
                WHERE numero_piece = ?
                AND libelle LIKE 'Ajustement rétrospectif%'";
        $this->db->query($sql, [$id_ecriture]);

        // 3. Supprimer l'écriture principale
        $this->db->query("DELETE FROM ecritures WHERE id = ?", [$id_ecriture]);

        // 4. Vérifier que les soldes au 01/01/2025 restent identiques
        $soldes_apres = $this->get_soldes_au_01_01_2025($section);

        if (!$this->soldes_identiques($soldes_avant, $soldes_apres)) {
            throw new Exception("ERREUR: Soldes 01/01/2025 modifiés après suppression !");
        }

        DB::commit();

    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

### 6.4 Alternative : Interdire la modification/suppression

**Option plus sûre pour la phase initiale :**

Interdire la modification ou suppression des écritures rétrospectives après leur création.

- Afficher un message d'erreur : *"Les écritures rétrospectives ne peuvent pas être modifiées. Contactez l'administrateur."*
- Forcer l'utilisation d'une écriture d'annulation/correction si nécessaire
- Maintenir une traçabilité complète de toutes les opérations

Cette approche garantit l'intégrité des données mais nécessite plus de vigilance lors de la saisie initiale.

---

## 7. Plan d'Action

### Phase 1 : Préparation (1-2 jours)

#### 1.1 Analyse des données à ventiler

Pour chaque nouvelle section (Avion, ULM, Services Généraux) :

- [x] Lister les cotisations 2025 encaissées en 2024
- [x] Calculer les totaux par compte de résultat (600, 700) (voir les documents comptables 2024)
- [x] Vérifier les soldes bancaires au 01/01/2025
- [x] Identifier les comptes ayant une initialisation avec 102
- [x] Préparer un fichier TXT de synthèse

#### 1.2 Validation comptable

- [x] Vérifier la cohérence avec les soldes bancaires réels
- [x] Documenter les hypothèses de ventilation
- [x] Créer une sauvegarde complète de la base de données

### Phase 2 : Développement (3-5 jours)

#### 2.1 Configuration

- [x] Ajouter le paramètre `ran_mode_enabled` dans `config/program.php`
- [x] tests phpunit à 100% de succès

#### 2.2 Fonctions de base

- [x] `saisir_ecriture_retrospective()` - Transaction atomique avec compensations
- [x] `identifier_comptes_a_compenser()` - Identification des comptes avec initialisation 102
- [x] `passer_ecriture_compensation()` - Création des écritures de compensation
- [x] `is_account_initialized()` - Vérification de l'existence d'une initialisation (fonction existante réutilisée)
- [x] `get_soldes_au_01_01_2025()` - Récupération des soldes de référence
- [x] `soldes_identiques()` - Contrôles de stabilité des soldes
- [x] Validation syntaxe PHP - Aucune erreur

#### 2.3 Interface utilisateur

- [x] Modification du formulaire de saisie d'écriture existant
  - [x] Ajout du fond rouge quand `ran_mode_enabled = true` ET utilisateur admin
  - [x] Ajout du bandeau d'avertissement (admin uniquement)
  - [x] Contrôle de date de gel non applicable en mode RAN (validation désactivée pour 2024)
  - [x] Restriction d'accès: RAN mode visible uniquement pour les admins via `$this->dx_auth->is_role('admin')`
- [x] Message de succès détaillé après saisie
  - [x] Lister l'écriture principale
  - [x] Lister toutes les écritures de compensation créées
  - [x] Message intégré directement dans `formValidation()` du contrôleur

#### 2.4 Gestion des modifications/suppressions

- [ ] `supprimer_ecriture_retrospective()` - Suppression avec compensations
- [ ] `modifier_ecriture_retrospective()` - Suppression + re-saisie
- [ ] OU : Interdire modification/suppression (option plus sûre)

### Phase 3 : Tests (2-3 jours)

#### 3.1 Tests unitaires

- [ ] Test de l'identification des comptes à compenser
- [ ] Test de la création des écritures de compensation

- [ ] Test des contrôles de cohérence
- [ ] Test de la suppression d'une écriture rétrospective

#### 3.2 Tests d'intégration

- [ ] Test complet sur Section témoin (ULM par exemple)
- [ ] Vérification des soldes avant/après
- [ ] Génération du bilan/compte de résultat
- [ ] Comparaison 2024/2025
- [ ] Vérification des écritures de compensation via requêtes SQL

#### 3.3 Tests SQL de validation

```sql
-- Vérifier la stabilité des soldes au 01/01/2025
SELECT
    section,
    compte,
    SUM(CASE WHEN date <= '2025-01-01' THEN debit - credit ELSE 0 END) as solde_01_01
FROM ecritures
WHERE section IN ('Avion', 'ULM', 'Services')
GROUP BY section, compte;

-- Vérifier les écritures de compensation
SELECT
    e1.id as id_principale,
    e1.date,
    e1.compte as compte_principale,
    e2.id as id_compensation,
    e2.compte as compte_compensation,
    e2.libelle
FROM ecritures e1
LEFT JOIN ecritures e2 ON e2.numero_piece = e1.id
WHERE e1.date >= '2024-01-01' AND e1.date < '2025-01-01'
AND e2.libelle LIKE 'Ajustement rétrospectif%'
ORDER BY e1.date, e1.id;
```

### Phase 4 : Exécution (1 jour)

#### 4.1 Préparation

- [x] **Sauvegarde complète de la base de données**
- [x] Activer le mode RAN : `$config['ran_mode_enabled'] = true`
- [x] Vérifier les soldes de référence au 01/01/2025

#### 4.2 Ordre de saisie recommandé

1. **Section témoin (ULM)** : Petits montants, facile à vérifier
   - Saisir quelques écritures
   - Vérifier les écritures de compensation créées
   - Vérifier les soldes au 01/01/2025 (doivent être inchangés)

2. **Section Avion** : Plus de données
   - Cotisations par lots
   - Écritures synthétiques par compte (600, 700)
   - Vérification intermédiaire

3. **Services Généraux**
   - Écritures synthétiques uniquement
   - Vérification finale

#### 4.3 Validation après chaque section

- [ ] Vérifier les soldes au 01/01/2025 (doivent être identiques)
- [ ] Lister les écritures de compensation créées
- [ ] Exporter les opérations du compte 102 en 2024

#### 4.4 Validation finale globale

- [ ] Bilan consolidé toutes sections
- [ ] Compte de résultat comparatif 2024/2025
- [ ] Vérification des totaux
- [ ] **Désactiver le mode RAN** : `$config['ran_mode_enabled'] = false`

### Phase 5 : Documentation (1 jour)

- [ ] Mise à jour de la documentation comptable
- [ ] Export des listings des opérations compte 102 en 2024
- [ ] Note explicative pour l'expert-comptable
- [ ] Documentation de la procédure de rollback

---

## 8. Points de Vigilance

### 8.1 Risques identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Erreur de calcul des compensations | Élevé | Faible | Tests unitaires + Contrôles automatiques |
| Incohérence des soldes | Critique | Faible | Contrôles automatiques + Rollback |
| Perte de traçabilité | Moyen | Faible | Référencement via numéro pièce + Requêtes SQL |
| Erreur de manipulation | Élevé | Moyen | Interface distincte (fond rouge) + Avertissement |
| Modification/suppression incorrecte | Élevé | Moyen | Option : Interdire modification/suppression |

### 8.2 Mesures de sécurité

1. **Backup complet** avant toute opération
2. **Mode RAN activable/désactivable** : `ran_mode_enabled` dans config
3. **Test sur copie** de la base de données avant mise en production
4. **Validation des soldes** après chaque opération
5. **Procédure de rollback** documentée et testée

### 8.3 Communication

- **Utilisateurs** : Expliquer le mode RAN et son utilisation (fond rouge = attention)
- **Expert-comptable** : Expliquer la méthode de compensation rétrospective
- **Documentation** : Archiver les exports des comptes 102

---

## 9. Réversibilité et Rollback

### 9.1 Rollback ciblé : Suppression des écritures RAN

Pour annuler la ventilation rétrospective sans restaurer un backup complet :

**Étape 1 : Supprimer les écritures de compensation 2024**
```sql
-- Supprimer toutes les écritures d'ajustement rétrospectif affectant le compte 102
DELETE FROM ecritures
WHERE date >= '2024-01-01'
AND date < '2025-01-01'
AND compte = '102'
AND libelle LIKE 'Ajustement rétrospectif%';
```

**Étape 2 : Supprimer les écritures principales 2024**
```sql
-- Identifier et supprimer les écritures principales (celles qui ont des compensations)
DELETE FROM ecritures
WHERE date >= '2024-01-01'
AND date < '2025-01-01'
AND id IN (
    SELECT DISTINCT numero_piece
    FROM ecritures
    WHERE libelle LIKE 'Ajustement rétrospectif%'
    AND date >= '2024-01-01'
    AND date < '2025-01-01'
);
```

**Étape 3 : Vérifier les soldes**
```sql
-- Vérifier que les soldes au 01/01/2025 sont revenus à leur état initial
SELECT section, compte, SUM(CASE WHEN date <= '2025-01-01' THEN debit - credit ELSE 0 END) as solde
FROM ecritures
WHERE section IN ('Avion', 'ULM', 'Services')
GROUP BY section, compte;
```

**IMPORTANT :** Exclure les écritures d'initialisation (au 31/12/2024 ou 01/01/2025) qui ont le compte 102 comme contrepartie. Ces écritures d'ouverture ne doivent PAS être supprimées.

### 9.2 Rollback complet : Restauration de sauvegarde

**Procédure de rollback brutale** (mais plus sûre) :

1. **Arrêter toutes les opérations** sur la base de données
2. **Restaurer la sauvegarde** effectuée avant la ventilation :
   ```bash
   mysql -u root -p gvv_db < backup_pre_ventilation_2024.sql
   ```
3. **Vérifier la restauration** :
   - Contrôler les soldes au 01/01/2025
   - Vérifier l'absence d'écritures 2024 pour les nouvelles sections
4. **Analyser les logs** pour comprendre le problème
5. **Corriger et recommencer** la ventilation si nécessaire

### 9.3 Recommandations

- **Préférer le rollback complet** (restauration de sauvegarde) pour la première exécution
- N'utiliser le rollback ciblé que pour des corrections ponctuelles
- Toujours tester la procédure de rollback sur une copie de la base avant l'exécution réelle
- Documenter toutes les opérations effectuées pour faciliter le diagnostic en cas de problème

---

## 10. Conclusion

### 10.1 Validité de la solution

L'approche par **écritures rétrospectives avec compensation automatique (Mode RAN)** est :

✅ **Comptablement correcte** : Écritures équilibrées, respect du principe de séparation des exercices
✅ **Techniquement sûre** : Transaction atomique, contrôles de cohérence, préservation des soldes
✅ **Pragmatique** : Évite la ressaisie complète de 2024, utilise le formulaire existant
✅ **Traçable** : Référencement via numéro de pièce, requêtes SQL pour audit
✅ **Réversible** : Procédures de rollback ciblé ou complet documentées
✅ **Simple à implémenter** : Pas de table additionnelle, pas de rapport PDF complexe

### 10.2 Bénéfices attendus

- **Comparabilité** : Bilans et comptes de résultat 2024/2025 à structure constante
- **Cohérence** : Soldes bancaires préservés au 01/01/2025, comptes clients équilibrés
- **Conformité** : Respect des principes comptables de retraitement rétrospectif
- **Efficacité** : Une écriture synthétique par compte au lieu de ressaisir toutes les opérations
- **Flexibilité** : Mode RAN activable/désactivable selon les besoins

### 10.3 Recommandations finales

1. **Tester d'abord** sur une copie de la base de données
2. **Commencer par une section témoin** (ULM) avec peu de données
3. **Valider les soldes** après chaque opération
4. **Documenter soigneusement** les écritures passées pour l'expert-comptable
5. **Désactiver le mode RAN** après utilisation (`ran_mode_enabled = false`)
6. **Conserver les sauvegardes** pour pouvoir revenir en arrière si nécessaire

---

## Annexes

### A. Références comptables

- Plan Comptable Général - Compte 102 : Report à nouveau
- Règlement ANC 2014-03 : Changement de méthodes comptables
- Norme IAS 8 : Méthodes comptables, changements d'estimations et erreurs

### B. Requêtes SQL utiles

```sql
-- Vérifier les soldes au 01/01/2025 par section
SELECT section, compte,
       SUM(CASE WHEN date <= '2025-01-01' THEN debit - credit ELSE 0 END) as solde_01_01
FROM ecritures
WHERE section IN ('Avion', 'ULM', 'Services', 'Planeur')
GROUP BY section, compte
ORDER BY section, compte;

-- Lister toutes les écritures rétrospectives passées en 2024
SELECT e.*, e2.libelle as libelle_compensation, e2.compte as compte_compensation
FROM ecritures e
LEFT JOIN ecritures e2 ON e2.numero_piece = e.id
WHERE e.date >= '2024-01-01' AND e.date < '2025-01-01'
AND e2.libelle LIKE 'Ajustement rétrospectif%'
ORDER BY e.date, e.id;

-- Vérifier les opérations sur le compte 102 en 2024
SELECT date, compte, debit, credit, libelle, numero_piece
FROM ecritures
WHERE compte = '102'
AND date >= '2024-01-01'
AND date < '2025-01-01'
ORDER BY date, id;

-- Identifier les comptes avec initialisation 102
SELECT DISTINCT e1.compte, e1.section
FROM ecritures e1
WHERE e1.date = '2025-01-01'
AND EXISTS (
    SELECT 1 FROM ecritures e2
    WHERE e2.id_piece = e1.id_piece
    AND e2.compte = '102'
)
ORDER BY e1.section, e1.compte;

-- Vérifier l'équilibre des écritures de compensation
SELECT numero_piece,
       COUNT(*) as nb_lignes,
       SUM(debit) as total_debit,
       SUM(credit) as total_credit
FROM ecritures
WHERE libelle LIKE 'Ajustement rétrospectif%'
AND date >= '2024-01-01'
AND date < '2025-01-01'
GROUP BY numero_piece
HAVING ABS(SUM(debit) - SUM(credit)) > 0.01;  -- Trouver les déséquilibres
```

### C. Contact et support

- **Développeur** : Équipe GVV
- **Expert-comptable** : [À compléter]
- **Documentation** : `/doc/design_notes/ventilation_retrospective_multi_sections_2024.md`

---

**Document créé le :** 2025-12-09
**Dernière mise à jour :** 2025-12-09
**Version :** 2.0
**Statut :** Proposition technique révisée - Mode RAN avec compensation à la date d'écriture
