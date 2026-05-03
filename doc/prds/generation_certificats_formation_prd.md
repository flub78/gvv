# PRD — Génération de certificats de formation

Date : 2 mai 2026
Statut : Proposition
Produit : GVV (Gestion Vol à Voile)

---

## 1. Résumé exécutif

Ce document décrit les exigences pour livrer une première fonctionnalité de génération de documents dans GVV, appliquée au cas d'usage "Certificats de formation".

La version V1 vise un flux simple, fiable et opérationnel :
- génération d'un PDF d'attestation de formation,
- archivage automatique dans le dossier documentaire du pilote,
- consultation selon les droits utilisateurs.

La V1 ne couvre pas la signature électronique.

---

## 2. Contexte

GVV dispose :
- d'un module formation fonctionnel (programmes, inscriptions, progressions, séances),
- d'un socle documentaire actif (types de documents et archivage),
- d'une note de design dédiée à la plateforme documentaire.

Le cas d'usage "certificat de formation" est retenu comme premier usage de la génération documentaire car il est à forte valeur métier, à complexité modérée, et faiblement couplé aux workflows de signature.

---

## 3. Objectifs

### 3.1 Objectifs métier

1. Produire rapidement un justificatif de formation officiel et homogène.
2. Réduire la charge administrative de production manuelle des attestations.
3. Garantir la traçabilité (qui, quand, pour qui, quel document).
4. Valider le socle technique de génération documentaire avant d'autres usages.

### 3.2 Objectifs utilisateur

1. Instructeur/RP : générer un certificat en quelques clics depuis le contexte formation.
2. Élève : accéder à son certificat depuis son espace documentaire.
3. CA/administration : retrouver facilement le document archivé et son historique.

---

## 4. Périmètre

### 4.1 Inclus (V1)

1. Génération d'une attestation de formation en PDF (mode generate_only).
2. Génération depuis une formation existante (inscription/progression).
3. Archivage automatique dans les documents du pilote.
4. Contrôle d'accès selon rôles.
5. Journalisation minimale de l'action de génération.
6. Messages explicites de succès/échec pour l'utilisateur.

### 4.2 Hors périmètre (V1)

1. Signature électronique simple ou double.
2. Workflows multi-parties (instructeur + élève).
3. Variantes avancées par discipline avec logique spécifique complexe.
4. Génération en lot de plusieurs certificats.
5. Distribution email automatique.

---

## 5. Utilisateurs et rôles

| Rôle | Besoin principal |
|------|------------------|
| Instructeur | Générer un certificat pour un élève formé |
| Responsable pédagogique (RP) | Valider et générer des certificats |
| Élève | Consulter et télécharger son certificat |
| CA/Admin | Superviser, consulter, supprimer si nécessaire |

---

## 6. User stories

1. En tant qu'instructeur, je veux générer une attestation depuis la fiche d'un élève pour éviter la création manuelle d'un document.
2. En tant que RP, je veux garantir que le certificat généré utilise un format standard du club.
3. En tant qu'élève, je veux retrouver mon certificat dans mes documents pour mes démarches administratives.
4. En tant qu'admin, je veux voir qui a généré le document et quand, pour assurer la traçabilité.

---

## 7. Exigences fonctionnelles

### 7.1 Déclenchement

1. Le système doit proposer l'action "Générer un certificat de formation" depuis une vue formation pertinente.
2. L'action ne doit être visible que pour les rôles autorisés.

### 7.2 Données d'entrée

Le système doit utiliser au minimum :
1. Identité élève (nom, prénom, identifiant),
2. Programme de formation,
3. Instructeur ou référent pédagogique,
4. Dates pertinentes (ouverture formation, date de génération),
5. Statut de formation.

### 7.3 Génération PDF

1. Le système doit produire un PDF conforme au modèle du club.
2. Le document généré doit inclure un identifiant unique de document.
3. Le nom du fichier doit suivre une convention stable (ex : certificat_formation_<pilote>_<date>.pdf).

### 7.4 Archivage

1. Le PDF généré doit être archivé automatiquement dans le dossier documentaire du pilote.
2. Le type documentaire doit être clairement identifié (ex : certificat_formation).
3. Le document doit être immédiatement visible dans l'interface documentaire selon les droits.

### 7.5 Permissions

1. Génération autorisée : instructeur, RP, CA.
2. Consultation autorisée : élève concerné, instructeurs autorisés, RP, CA.
3. Suppression : CA uniquement (selon politique documentaire du club).

### 7.6 Journalisation et messages

1. Le système doit enregistrer au minimum : utilisateur générateur, pilote cible, horodatage, résultat.
2. En cas d'échec, un message utilisateur explicite doit indiquer la cause.
3. Aucune action ne doit échouer silencieusement.

---

## 8. Exigences non fonctionnelles

1. Performance : génération en moins de 5 secondes dans le cas nominal.
2. Fiabilité : pas de document partiellement archivé (génération et archivage atomiques du point de vue utilisateur).
3. Sécurité : accès strictement contrôlé aux documents par rôle et périmètre utilisateur.
4. Auditabilité : historique exploitable pour diagnostic et contrôle.

---

## 9. Règles métier

1. Un certificat ne peut être généré que pour une formation existante.
2. Si des données essentielles sont manquantes (pilote, programme, instructeur), la génération doit être refusée avec message explicite.
3. La génération ne modifie pas l'état pédagogique de la formation.
4. Plusieurs générations sont possibles (régénération), chaque document restant traçable.

---

## 10. Pré-requis

### 10.1 Pré-requis fonctionnels

1. Module formation actif et données cohérentes.
2. Type documentaire "certificat de formation" défini.
3. Modèle PDF validé par le club.

### 10.2 Pré-requis techniques

1. Feature flags formation et documentaire actifs.
2. Mécanisme d'archivage documentaire opérationnel.
3. Droits utilisateurs configurés pour les rôles cible.

---

## 11. Critères d'acceptation

1. Un instructeur autorisé voit le bouton de génération sur une formation valide.
2. En cliquant, le PDF est généré sans erreur et archivé dans les documents du pilote.
3. L'élève concerné peut consulter/télécharger le certificat.
4. Un utilisateur non autorisé ne peut ni générer ni consulter le document.
5. En cas de données incomplètes, le système bloque avec message compréhensible.
6. Un log de génération est présent et exploitable.

---

## 12. Dépendances

1. Modèle de données formation (inscriptions/programmes/progression).
2. Module documentaire (types + archivage).
3. Composant de génération PDF.
4. Système d'autorisations (legacy et/ou nouveau modèle actif selon utilisateur).

---

## 13. Risques

1. Incohérences de données formation (ex : référent manquant) bloquant la génération.
2. Ambiguïté sur la source de vérité pour certains champs du certificat.
3. Règles d'accès non harmonisées entre anciens et nouveaux mécanismes d'auth.
4. Multiplication de variantes de certificats trop tôt dans le projet.

Mitigation : livrer un unique certificat V1 standard, avec mapping de données strict et validation forte en entrée.

---

## 14. Plan de livraison proposé

### Lot 1 (V1)

1. Génération d'un certificat standard en mode generate_only.
2. Archivage automatique dans le dossier pilote.
3. Contrôles d'accès et logs de base.
4. Tests unitaires/integration sur le flux nominal et les erreurs bloquantes.

### Lot 2 (post-V1)

1. Variantes par discipline.
2. Envoi email optionnel.
3. Signature électronique (simple puis double) si validée.

---

## 15. Questions ouvertes

1. Quel libellé officiel doit apparaître (Certificat, Attestation, Reconnaissance) ?
2. La génération est-elle autorisée uniquement pour les formations clôturées, ou aussi en cours ?
3. Faut-il conserver toutes les versions régénérées, ou marquer une version "courante" ?
4. Quel niveau de personnalisation par section est requis en V1 (logo, pied de page, signataire) ?
