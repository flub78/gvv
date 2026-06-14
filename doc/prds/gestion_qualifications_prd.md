# PRD — Gestion des Qualifications Pilotes

**Version :** 1.0  
**Date :** 2026-06-14  
**Statut :** Proposition  
**Produit :** GVV (Gestion Vol à Voile)

---

## 1. Résumé exécutif

Ce document décrit les exigences pour un module de gestion des qualifications pilotes dans GVV. Une qualification est une habilitation aéronautique ou associative associée à un pilote : licence de vol, certificat médical, qualification instructeur, autorisation d'emport passager, etc. Elle a une date d'obtention, peut avoir une date d'expiration, et peut être appuyée par une pièce justificative.

Le module couvre le cycle de vie complet : déclaration, validation, mise à jour, suivi des expirations, blocage conditionnel d'opérations, et tableaux de bord administratifs.

---

## 2. Contexte

### 2.1 Situation actuelle

GVV gère déjà des données de qualification mais de façon fragmentée :

- La table `events` stocke les attributs métier (type, date d'obtention, date d'expiration, numéro). C'est la source de vérité opérationnelle actuelle.
- La table `archived_documents` permet d'attacher des fichiers (copies de licences, rapports médicaux) mais reste disconnectée des données `events`.
- Le contrôleur `alarmes.php` calcule les alertes d'expiration médicale et d'instructeur à la volée.
- Le champ `membres.mniveaux` encode en bitmap certaines qualifications opérationnelles (instructeur, remorqueur…) — source d'incohérence documentée dans le PRD autorisation.

Cette fragmentation génère une double saisie, un risque de désynchronisation, et une UX dégradée.

### 2.2 Réglementation de référence

Dans le domaine du vol à voile en Europe, les qualifications gérées par un club incluent notamment :

| Qualification | Nature | Validité typique |
|---|---|---|
| BPP (Brevet Pilote Planeur) | Nationale FR — licence de base | Illimitée |
| LAPL(S) / SPL | Européenne EASA | Contrôle de compétence tous les 24 mois |
| Visite médicale LAPL | Réglementaire | 4 ans < 40 ans, 2 ans ≥ 40 ans |
| Certificat médical Classe 2 | Réglementaire | 5 ans < 40 ans, 2 ans ≥ 40 ans |
| Qualification instructeur FI(s) | Réglementaire | Renouvellement périodique |
| Qualification remorquage (FTT) | Opérationnelle | Contrôle périodique |
| Autorisation emport passager | Opérationnelle | Maintien de compétence |
| Contrôle de compétence (CCA) | Réglementaire | 24 mois |
| Licence ULM | Nationale FR | Assurance annuelle, médecin traitant |
| PPL / ATPL | Européenne | Selon règlement EASA Part-FCL |

La liste exacte des qualifications gérées est configurable par l'administrateur (cf. §4.6).

---

## 3. Acteurs

| Acteur | Description |
|---|---|
| **Pilote** | Membre qui possède des qualifications. Peut les déclarer, les consulter et les mettre à jour. |
| **Administrateur** | Membre du CA ou responsable de section. Vérifie, valide et supervise les qualifications de tous les pilotes. |

---

## 4. Exigences fonctionnelles

### 4.1 Déclaration d'une qualification

**EF-Q01** — Un pilote peut déclarer une qualification pour son propre compte.  
**EF-Q02** — Un administrateur peut déclarer une qualification pour n'importe quel pilote.  
**EF-Q03** — La déclaration saisit au minimum : type de qualification, date d'obtention, date d'expiration (si applicable), numéro ou référence (optionnel).  
**EF-Q04** — Une pièce justificative (PDF, image) peut être jointe à la déclaration.  
**EF-Q05** — Une qualification déclarée par un pilote lui-même est marquée "en attente de validation" jusqu'à approbation par un administrateur.  
**EF-Q06** — Une qualification déclarée directement par un administrateur est immédiatement validée.

### 4.2 Validation d'une qualification auto-déclarée

**EF-Q07** — Un administrateur voit la liste des qualifications en attente de validation.  
**EF-Q08** — Il peut approuver ou rejeter une qualification, avec un motif de rejet libre.  
**EF-Q09** — Le pilote est notifié du résultat (validation ou rejet) par bannière et optionnellement par email.  
**EF-Q10** — Une qualification rejetée reste visible par le pilote avec son motif ; il peut soumettre une nouvelle déclaration.

### 4.3 Mise à jour d'une qualification

**EF-Q11** — Un pilote peut mettre à jour sa propre qualification (nouvelle date d'expiration, nouveau fichier joint, nouveau numéro).  
**EF-Q12** — Toute mise à jour par un pilote repasse en statut "en attente de validation".  
**EF-Q13** — Un administrateur peut mettre à jour la qualification d'un pilote sans validation supplémentaire.  
**EF-Q14** — L'historique des versions d'une qualification est conservé (qui a modifié, quand, quelle valeur).

### 4.4 Consultation de l'état d'une qualification

**EF-Q15** — Un pilote voit sur son tableau de bord personnel la liste de ses qualifications avec leur statut : valide, expire bientôt, expirée, manquante, en attente.  
**EF-Q16** — Un pilote peut consulter le détail de chaque qualification (dates, numéro, pièce jointe, historique).  
**EF-Q17** — Un administrateur peut consulter les qualifications de n'importe quel pilote.  
**EF-Q18** — Un administrateur a accès à un tableau récapitulatif de toutes les qualifications de tous les pilotes actifs, filtrable par type et par statut.  
**EF-Q19** — Le tableau récapitulatif est exportable en CSV et en PDF.

### 4.5 Alarmes et notifications

**EF-Q20** — Le système calcule automatiquement le statut de chaque qualification : `valide`, `expire_bientot`, `expiree`, `manquante`, `en_attente`.  
**EF-Q21** — Un seuil d'alerte avant expiration est configurable par type de qualification (ex. 30 jours, 60 jours).  
**EF-Q22** — Un pilote reçoit une alarme visuelle (bannière / dashboard) lorsqu'une de ses qualifications expire bientôt ou est expirée.  
**EF-Q23** — Un administrateur reçoit une alarme agrégée sur les qualifications expirées ou en voie d'expiration pour l'ensemble des pilotes.  
**EF-Q24** — Des notifications email peuvent être envoyées automatiquement avant l'expiration, selon la configuration de chaque type.  
**EF-Q25** — Un administrateur peut désactiver unitairement une alarme pour un pilote donné (cas exceptionnel documenté).

### 4.6 Configuration des types de qualifications

**EF-Q26** — Un administrateur peut définir la liste des types de qualifications gérés par le système.  
**EF-Q27** — Pour chaque type, il configure : nom, description, caractère expirable (oui/non), délai d'alerte avant expiration, caractère obligatoire (oui/non), activité concernée (planeur / avion / ULM / global).  
**EF-Q28** — Un type peut être archivé (masqué) sans perdre les données historiques.  
**EF-Q29** — L'ordre d'affichage des types dans les tableaux est configurable.

### 4.7 Blocage conditionnel d'opérations

**EF-Q30** — Certaines opérations peuvent être conditionnées à la validité d'une ou plusieurs qualifications : réservation d'appareil, réservation sans instructeur, enregistrement de vol solo.  
**EF-Q31** — La liste des qualifications requises pour chaque opération est configurable par l'administrateur.  
**EF-Q32** — Si une qualification requise est expirée ou manquante, l'opération est bloquée et un message explicite indique quelle qualification est insuffisante.  
**EF-Q33** — Un administrateur peut lever manuellement un blocage pour un pilote donné, avec justification tracée.

---

## 5. Exigences non fonctionnelles

**ENF-Q01** — Les qualifications d'un pilote sont accessibles à la section qui les concerne (dimension section héritée du type de qualification ou renseignée à la déclaration).  
**ENF-Q02** — Un pilote ne peut pas voir ni modifier les qualifications d'un autre pilote.  
**ENF-Q03** — Les pièces justificatives sont stockées de façon sécurisée ; leur accès est limité au pilote concerné et aux administrateurs.  
**ENF-Q04** — Toute action (déclaration, validation, rejet, modification) est journalisée (qui, quand, action).  
**ENF-Q05** — Le module doit s'intégrer au système d'alarmes générique (`AlarmAggregator`) documenté dans `gestion_alarmes_design.md`.  
**ENF-Q06** — L'interface utilise les composants Bootstrap 5 et les conventions UI de GVV.  
**ENF-Q07** — Les libellés sont disponibles en français, anglais et néerlandais.

---

## 6. Cas limites et règles métier

- **Qualification sans expiration** : un BPP est illimité ; le champ date d'expiration est absent, le statut reste `valide` indéfiniment une fois validé.
- **Plusieurs occurrences du même type** : un pilote peut avoir un historique de renouvellements médicaux ; seule la version la plus récente est active, les précédentes sont conservées en historique.
- **Qualification manquante vs. non applicable** : un pilote avion n'a pas de BPP planeur — le caractère "manquant" ne s'applique que si le type est marqué obligatoire pour son activité.
- **Qualification en attente bloquante** : une qualification en attente de validation ne doit pas bloquer l'opérateur (elle ne compte pas comme valide, mais ne génère pas d'alarme critique immédiate — sauf si la date de validité initiale est dépassée).

---

## 7. Hors périmètre

- Synchronisation automatique avec des registres externes (DGAC, LBA, CAA, OpenFlyers).
- Signature électronique des qualifications.
- Gestion des qualifications des aéronefs (non humaines).
- Paiement en ligne des renouvellements de licences.

---

## 8. Dépendances

| Dépendance | Nature |
|---|---|
| PRD autorisation (`2025_authorization_refactoring_prd.md`) | Contrôle d'accès au module ; résolution du bitmap `mniveaux` (Phase 13) |
| PRD alarmes génériques (`gestion_alarmes_generiques_prd.md`) | Intégration des alarmes de qualification dans `AlarmAggregator` |
| PRD archivage documentaire (`archivage_documentaire_prd.md`) | Stockage des pièces justificatives |
| Design `analyse_de_la_gestion_des_droits_et_autorisations.md` | Architecture de référence events / documents / qualifications |
