# PRD — Moteur de procédures guidées

Date : 24 mars 2026

## Objectif

Doter GVV d'un moteur générique pour conduire des démarches administratives multi-étapes : inscription, renouvellement d'adhésion, demande de qualification, homologation, etc. Une procédure est un parcours défini par configuration, que suit un utilisateur (membre ou externe) jusqu'à validation complète par le club.

---

## Périmètre

**Inclus :**
- Définition de procédures par les administrateurs, sans programmation
- Exécution guidée d'une procédure par un utilisateur
- Machine à états avec état courant persisté entre sessions
- Branchement conditionnel selon les données collectées
- Collecte de données saisies par l'utilisateur
- Dépôt et validation de documents
- Interruption et reprise d'une procédure en cours
- Suivi administrateur des procédures en cours

**Hors périmètre :**
- Archivage long terme des documents fournis (→ plateforme documentaire)
- Signature électronique avec valeur légale (→ module acceptation)
- Workflow d'approbation hiérarchique multi-niveaux
- Intégrations applicatives spécifiques à chaque procédure (création de compte, facturation)

---

## Utilisateurs

| Rôle | Description |
|------|-------------|
| Candidat externe | Personne non encore membre, accède sans authentification GVV |
| Membre | Utilisateur GVV authentifié |
| Administrateur | Responsable du suivi et de la validation des dossiers |

---

## Besoins — Candidat / Membre

- Démarrer une procédure depuis un lien public ou depuis son espace personnel
- Naviguer pas à pas (avancer, revenir corriger une étape précédente)
- Savoir à tout moment où il en est dans la procédure et ce qu'il reste à faire
- Saisir des données demandées (texte libre, choix dans une liste, date, case à cocher…)
- Lire des documents PDF présentés dans le parcours
- Accepter des conditions ou règlements (case à cocher obligatoire)
- Uploader des documents demandés (photo, certificat médical, justificatif…)
- Interrompre la procédure et la reprendre ultérieurement sans perdre sa progression
- Être informé du résultat de la validation de ses documents (accepté, refusé + motif)

---

## Besoins — Administrateur

- Voir la liste de toutes les exécutions de procédures avec leur état courant
- Filtrer par procédure, état, date
- Consulter le dossier complet d'une exécution : données saisies, documents uploadés
- Valider ou rejeter un document fourni, avec un commentaire obligatoire en cas de rejet
- Suivre les procédures bloquées en attente de validation
- Archiver ou supprimer une exécution terminée ou abandonnée

---

## Machine à états

Chaque exécution d'une procédure est une instance dont l'état avance selon le cycle suivant :

```
[initialized]
      ↓
  [step:N]  ←──────────────────────────────┐
      ↓                                     │
[waiting_upload]  ──→  [waiting_validation] │
                               ↓            │
                          [rejected] ───────┘  (retour à waiting_upload)
                               ↓
                          [completed]
```

| État | Signification pour l'utilisateur |
|------|----------------------------------|
| `initialized` | Procédure créée, première étape non encore affichée |
| `step:N` | En cours de remplissage, étape N du parcours |
| `waiting_upload` | Toutes les saisies effectuées, documents à déposer |
| `waiting_validation` | Documents déposés, attente de validation par le club |
| `rejected` | Un ou plusieurs documents refusés, correction demandée |
| `completed` | Procédure entièrement validée |

Les états `step:N` sont dynamiques : leur nombre et leur contenu dépendent de la définition de la procédure et des branchements empruntés. L'état courant est persisté entre sessions.

---

## Branchements conditionnels

Une procédure peut adapter son parcours selon les données collectées aux étapes précédentes. Les branchements peuvent porter sur :

- Le contenu des pages affichées (informations différentes selon le profil)
- La liste des documents à fournir
- Les étapes suivantes à parcourir

**Exemple :** une procédure d'inscription présente des pages d'information différentes selon que le candidat choisit la section planeur, avion ou ULM, et demande des documents spécifiques à chaque discipline.

---

## Définition d'une procédure

Une procédure est définie par un fichier Markdown enrichi de métabalises. Ce fichier est versionné avec le code source. L'administrateur peut créer ou modifier une procédure sans intervention technique.

Les éléments configurables via le fichier Markdown :

| Élément | Description |
|---------|-------------|
| Pages de contenu | Texte informatif, instructions, HTML de base |
| Visualisation PDF | Affichage d'un document PDF dans le parcours |
| Cases d'acceptation | Acceptation obligatoire d'une condition ou d'un règlement |
| Saisie de données | Champs texte, date, sélection, case à cocher, fichier |
| Rupture de page | Séparation en étapes navigables (précédent / suivant) |
| Branchement | Choix conditionnel orientant vers un sous-parcours |
| Dépôt de document | Upload simple (stockage) ou avec validation administrateur |

---

## Accès et reprise

**Candidat externe** (sans compte GVV) :
- Démarre la procédure depuis une URL publique
- Reçoit un token unique et un code PIN de 4 chiffres
- Peut reprendre la procédure en saisissant son email + code PIN
- L'email permet une notification en cas de validation ou rejet

**Membre authentifié** :
- Démarre et reprend la procédure depuis son espace personnel
- L'état d'avancement est associé à son compte

---

## Contraintes

- Une même procédure peut avoir plusieurs définitions simultanées (ex : inscription planeur, inscription ULM) — chaque définition est une procédure distincte
- Une exécution en cours n'est pas affectée si la définition de la procédure évolue
- Les données saisies et les documents sont conservés pendant toute la durée d'exécution
- Les exécutions abandonnées (inactives depuis N jours) sont supprimées automatiquement
- Les fichiers uploadés sont protégés contre l'accès direct depuis le web
