# Design - Comptes Multi-Sections au Tableau de Bord

**Date:** 3 janvier 2026  
**Contexte:** GVV #multi-section-views  
**Statut:** Design

---

## 1. Problématique

Actuellement, un pilote avec des comptes dans plusieurs sections ne voit qu'une seule carte "Ma facture" au dashboard. Cette carte ouvre toujours le compte 411 de la **section active** en session.

**Exemple:**
- Jean est pilote Planeur (section 1) et ULM (section 2)
- Au dashboard, il ne voit qu'une carte "Ma facture" 
- Même s'il a deux comptes 411 distincts (un en section 1, un en section 2)
- Pour consulter son compte ULM, il doit se déconnecter/reconnecter

---

## 2. Solution Proposée

**Afficher plusieurs cartes "Mon compte" au dashboard**, une par section où le pilote a un compte 411.

**Exemple après implémentation:**
```
┌─────────────────────────────────────────────┐
│ Mon espace personnel                        │
├─────────────────────────────────────────────┤
│ [Mon compte - Planeur]  [Mon compte - ULM] │
│ [Mon compte - Avion]    [Calendrier]       │
│ [Mes vols planeur]      [Mes infos]        │
│ ...                                         │
└─────────────────────────────────────────────┘
```

**Comportement attendu:**
- Chaque carte "Mon compte - [Section]" ouvre le compte 411 dans la **section concernée**
- Le paramètre `$section` est passé à `compta/mon_compte`
- Les écritures affichées sont filtrées par **ce paramètre** plutôt que par la section de session

---

## 3. Architecture Technique

### 3.1 Structure de Données Existante

```sql
-- Table comptes
CREATE TABLE comptes (
  id INT PRIMARY KEY,
  pilote VARCHAR(32),      -- Login du pilote
  codec VARCHAR(3),        -- "411" pour compte pilote
  nom VARCHAR(64),         -- Nom du compte
  club INT,                -- Section ID (1=Planeur, 2=ULM, 3=Avion, 4=Général)
  debit DECIMAL(15,2),
  credit DECIMAL(15,2),
  actif BOOLEAN,
  ...
);

-- Table sections
CREATE TABLE sections (
  id INT PRIMARY KEY,
  nom VARCHAR(64),         -- "Planeur", "ULM", "Avion", "Général"
  acronyme VARCHAR(10),    -- "PLN", "ULM", "AVN", "GEN"
  ...
);
```

### 3.2 Méthodes Existantes

**`membres_model->registered_in_sections($mlogin)`**
- Retourne un array des `section_id` où le membre a un compte 411
- ✓ Déjà existe et fonctionne

**`comptes_model->compte_pilote($pilote, $section)`**
- Retourne le compte 411 d'un pilote pour une section donnée
- Actuellement: filtre par section active en session
- ⚠️ Besoin d'amélioration: accepter une section en paramètre

---

## 4. Changements Requis

### 4.1 Modèle (`comptes_model.php`)

#### Nouvelle méthode: `get_pilote_comptes($pilote)`

```php
/**
 * Get all 411 accounts for a pilot across all sections
 * 
 * @param string $pilote Pilot login identifier
 * @return array Array of account records with section info
 * 
 * Example:
 * [
 *   ['id' => 1, 'nom' => 'Jean Dupont Planeur', 'club' => 1, 'solde' => 150.50, 'section_name' => 'Planeur'],
 *   ['id' => 2, 'nom' => 'Jean Dupont ULM', 'club' => 2, 'solde' => -50.00, 'section_name' => 'ULM']
 * ]
 */
public function get_pilote_comptes($pilote)
```

### 4.2 Contrôleur (`compta.php`)

#### Modification: `mon_compte($section_id = null)`

**Avant:**
```php
function mon_compte() {
    $mlogin = $this->dx_auth->get_username();
    $info_pilote = $this->membres_model->get_by_id('mlogin', $mlogin);
    if (isset($info_pilote['compte']) && ($info_pilote['compte'] !== "0")) {
        $this->journal_compte($info_pilote['compte']);
    } else {
        $this->compte_pilote($mlogin);
    }
}
```

**Après:**
```php
function mon_compte($section_id = null) {
    $this->push_return_url("mon compte");
    
    $mlogin = $this->dx_auth->get_username();
    $info_pilote = $this->membres_model->get_by_id('mlogin', $mlogin);
    
    // Si section_id fourni, l'utiliser; sinon utiliser la section de session
    $target_section = $section_id ? $this->sections_model->get_by_id('id', $section_id) 
                                   : $this->sections_model->section();
    
    if (isset($info_pilote['compte']) && ($info_pilote['compte'] !== "0")) {
        $this->journal_compte($info_pilote['compte'], $target_section);
    } else {
        $this->compte_pilote($mlogin, $target_section);
    }
}
```

**Modifications de `journal_data()` et `journal_compte()` :**
- Accepter un paramètre `$section` explicite
- Utiliser ce paramètre pour filtrer les écritures plutôt que `$this->section`
- Les écritures doivent correspondre au compte dans cette section spécifique

### 4.3 Vue (`bs_dashboard.php`)

#### Section "Mon espace personnel"

**Avant:**
```php
<div class="col-6 col-md-4 col-lg-3 col-xl-2">
    <div class="sub-card text-center">
        <i class="fas fa-file-invoice-dollar text-success"></i>
        <div class="card-title">Ma facture</div>
        <div class="card-text text-muted">Consulter</div>
        <a href="<?= controller_url('compta/mon_compte') ?>" class="btn btn-success btn-sm">Accéder</a>
    </div>
</div>
```

**Après:**
```php
<?php
// Get all sections where the user has a 411 account
$this->load->model('comptes_model');
$this->load->model('sections_model');
$user_comptes = $this->comptes_model->get_pilote_comptes($username);

if (!empty($user_comptes)) {
    foreach ($user_comptes as $compte) {
        $section = $this->sections_model->get_by_id('id', $compte['club']);
        $section_name = $section ? $section['nom'] : $compte['club'];
        ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-file-invoice-dollar text-success"></i>
                <div class="card-title"><?= translation('dashboard_my_account') ?> - <?= htmlspecialchars($section_name) ?></div>
                <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                <a href="<?= controller_url('compta/mon_compte/' . $compte['club']) ?>" class="btn btn-success btn-sm">Accéder</a>
            </div>
        </div>
        <?php
    }
} else {
    // Fallback pour utilisateurs sans compte
    ?>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="sub-card text-center">
            <i class="fas fa-file-invoice-dollar text-success"></i>
            <div class="card-title"><?= translation('dashboard_my_account') ?></div>
            <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
            <a href="<?= controller_url('compta/mon_compte') ?>" class="btn btn-success btn-sm">Accéder</a>
        </div>
    </div>
    <?php
}
?>
```

---

## 5. Flux d'Exécution

```
1. Pilote accède au dashboard (welcome/index)
   ↓
2. Dashboard charge la vue bs_dashboard.php
   ↓
3. Dans la section "Mon espace personnel"
   ├─ Récupère tous les comptes 411 de ce pilote
   ├─ Pour chaque compte:
   │  ├─ Récupère le nom de la section
   │  └─ Génère une carte "Mon compte - [Section]"
   └─ Chaque carte a un lien: compta/mon_compte/[section_id]
   ↓
4. Pilote clique sur "Mon compte - Avion"
   ├─ URL: /compta/mon_compte/3
   ├─ Paramètre section_id = 3
   ↓
5. Contrôleur compta.php::mon_compte(3)
   ├─ Récupère la section avec ID 3 (Avion)
   ├─ Appelle account_pilote($mlogin, $section_3)
   ├─ Les écritures sont filtrées pour cette section
   ↓
6. Affichage du compte 411 dans la section Avion
```

---

## 6. Considérations Importantes

### 6.1 Filtrage des Écritures

**Point critique:** Actuellement, `journal_data()` et apparentées utilisent `$this->section` (section de session) pour filtrer les écritures. 

Après modification, ils doivent utiliser le paramètre `$section` explicite passé en argument.

```php
// Avant - utilise la session
$this->db->where('comptes.club', $this->section['id']);

// Après - utilise le paramètre
$this->db->where('comptes.club', $section['id']);
```

### 6.2 Rétro-compatibilité

- Si `mon_compte()` est appelé sans paramètre → utiliser la section de session (comportement actuel)
- Si `mon_compte($section_id)` est appelé → utiliser cette section

Cela garantit que les appels existants continuent de fonctionner.

### 6.3 Sécurité

- ⚠️ Vérifier que le pilote a effectivement un compte dans la section demandée
- ⚠️ Empêcher que un pilote accède à un compte d'une section où il n'a pas de compte 411

```php
// Dans mon_compte($section_id)
if ($section_id) {
    // Vérifier que l'utilisateur a un compte dans cette section
    $has_account = $this->comptes_model->has_compte_in_section($mlogin, $section_id);
    if (!$has_account) {
        $this->session->set_flashdata('error', 'Accès non autorisé');
        redirect('welcome');
    }
}
```

---

## 7. Traductions Requises

Fichiers à mettre à jour:
- `application/language/french/`
- `application/language/english/`
- `application/language/dutch/`

Clés suggérées:
```php
$lang['dashboard_my_account'] = "Mon compte";
$lang['dashboard_consult'] = "Consulter";
```

---

## 8. Cas d'Usage Validant la Solution

### CU1: Pilote mono-section
- Jean est pilote Planeur uniquement
- Une seule carte "Mon compte - Planeur" s'affiche
- ✓ Comportement identique à l'actuel

### CU2: Pilote multi-section
- Marie est pilote Planeur et Avion
- Deux cartes s'affichent: "Mon compte - Planeur" et "Mon compte - Avion"
- En cliquant sur "Mon compte - Avion", elle voit son compte dans la section Avion
- Les écritures affichées sont uniquement celles de son compte 411 en section Avion
- ✓ Nouveau comportement souhaité

### CU3: Changement de section
- Pierre accède à son compte ULM via le dashboard
- Il bascule ensuite sa section active via le menu (set_section)
- L'URL reste `/compta/mon_compte/2` → le compte ULM continue d'être affiché
- La section de session peut être différente de la section du compte consulté
- ✓ Décontextualization de la section

---

## 9. Implémentation - Ordre des Étapes

1. **Phase 1:** Modèle - Ajouter `get_pilote_comptes()`
2. **Phase 2:** Contrôleur - Modifier `mon_compte()` et méthodes annexes
3. **Phase 3:** Vue - Générer dynamiquement les cartes multi-sections
4. **Phase 4:** Traductions - Ajouter les clés requis
5. **Phase 5:** Tests - Validations

---

## 10. Impact sur le Codebase

### Fichiers Modifiés
- `application/models/comptes_model.php` - Ajouter méthode
- `application/controllers/compta.php` - Modifier `mon_compte()` et annexes
- `application/views/bs_dashboard.php` - Logique des cartes
- `application/language/*/` - Traductions

### Fichiers Non Modifiés
- Modèles: `sections_model.php`, `membres_model.php` ✓ Suffisant
- Autres contrôleurs - Pas d'impact
- Configuration - Pas de changements

### Régression Potentielle
- ⚠️ Si `mon_compte()` est appelé sans paramètre mais un compte existe en session différente
  → Correction: fallback à la section de session
- ⚠️ Les bookmarks anciens pointeront vers `/compta/mon_compte` sans section
  → Correction: toujours fonctionner (utilise section de session)
