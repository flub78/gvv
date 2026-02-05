# PRD — Archivage Documentaire

Date : 4 février 2026

## Contexte
L’association doit archiver des documents liés aux pilotes et, potentiellement, à d’autres usages (documents club, déclarations d’activité, renouvellements, etc.). Les documents peuvent avoir une date de validité, être remplacés par des versions plus récentes, nécessiter une validation administrative et faire l’objet d’alertes d’expiration.

Le but n’est pas de se substituer aux outils existants (GESASSO, suivi des exigences médicales par la FFPLUM, espace partagé), mais de fournir un mécanisme simple et intégré pour les cas où nous avons des obligations réglementaires. L’espace partagé assure le stockage, mais sans gestion des versions, des validations ou des alertes. Ce module comble ces lacunes uniquement pour les documents soumis à exigences.

Ce PRD s’appuie sur l’analyse existante : [doc/design_notes/reuse_pilot_documents_attachments.md](doc/design_notes/reuse_pilot_documents_attachments.md).

## Objectifs
- Archiver des documents liés aux pilotes, sections ou au club.
- Gérer documents avec ou sans date de validité, avec statut d’expiration.
- Conserver l’historique des versions lors d’un remplacement.
- Mettre en place un workflow de validation par administrateur.
- Exposer des listes dédiées (documents à valider, expirés).
- Notifier avant l’expiration (email + notification à la connexion).
- Permettre la réutilisation du stockage pour un futur module d’acceptation de documents (PRD séparé).

## Non-objectifs
- Gestion complète d’un GED (indexation avancée, recherche OCR, signatures électroniques, etc.).
- Automatisation de la collecte de documents via des tiers externes.

## Portée
### Inclus
- Documents liés à un pilote (ex. visite médicale) et documents non associés à un pilote (ex. documents club/sections).
- Versionning (remplacement par versions plus récentes tout en conservant l’accès aux anciennes versions).
- Statuts de validation (en attente, validé).
- Indicateurs visuels d’expiration et notifications avant expiration.
- Gestion des types de documents avec règles associées (obligatoire, portée, expiration, etc.).

### Exclu
- Modification automatique des statuts de membres ou des droits à partir des documents.
- Archivage physique ou destruction légale des documents.

## Personae & rôles
- **Pilote** : peut consulter ses documents, en ajouter, supprimer ses documents tant qu’ils ne sont pas validés.
- **Administrateur (membre du CA)** : accès à tous les documents, valide les documents, voit les listes dédiées (à valider, expirés).
- **Association/Club** : documents non liés à un pilote (documents club/sections).

## Parcours clés
1. **Pilote ajoute un document** → statut “en attente de validation” → visible dans la liste “à valider” des administrateurs.
2. **Administrateur valide un document** → statut “validé” → document verrouillé (non supprimable par le pilote).
3. **Document arrive à expiration** → marqueur d’expiration affiché → alertes email envoyées aux abonnés.
4. **Nouvelle version d’un document** → la nouvelle version devient la version active → les versions antérieures restent accessibles.

## Exigences fonctionnelles
1. **Association des documents**
   - Un document peut être associé à un pilote, à une section, ou au club (document “club”).
2. **Dates de validité**
   - La date de validité est optionnelle.
   - Le système doit déterminer l’état “expiré” en fonction de la date de validité.
3. **Versionning**
   - Le système doit permettre d’ajouter une nouvelle version d’un document sans supprimer l’ancienne.
   - Les versions précédentes doivent rester consultables. Certains documents sont uniques et non versionés.
4. **Statuts de validation**
   - Les documents ont au minimum les statuts : “en attente”, “validé”.
   - Les documents ajoutés par un pilote sont “en attente” par défaut.
5. **Suppression des documents**
   - Un pilote peut supprimer un document tant qu’il est “en attente”.
   - Un pilote ne peut pas supprimer un document “validé”.
6. **Accès administrateur**
   - Les administrateurs ont accès à tous les documents.
   - Ils disposent d’un accès direct à la liste des documents “à valider”.
   - Ils disposent d’un accès direct à la liste des documents “expirés”.
7. **Affichage des expirations**
   - Les documents expirés doivent être affichés avec un marqueur visuel spécifique.
   - Les documents proches de l’expiration doivent être mis en évidence.
   - Tous les documents n’ont pas de date d’expiration. Pour ceux qui en ont, l’état doit être “actif”, “expiration proche” ou “expiré”.
   - Pour les documents pilotes, un type peut être marqué “obligatoire”. Si obligatoire et aucun document valide, le statut “manquant” est affiché pour le pilote.
   - Les administrateurs doivent pouvoir désactiver les alarmes par pilote (ex. pilote inactif). 
8. **Notifications**
   - Les utilisateurs peuvent s’abonner à des alertes par email avant l’expiration.
   - Le délai d’alerte est paramétrable (valeur par défaut à définir).
9. **Types de fichiers supportés**
   - Le système doit accepter au minimum les mêmes types que l’archivage des factures : images et PDF.
10. **Réutilisation des mécanismes existants**
   - Le système doit réutiliser les bibliothèques et mécanismes existants pour le stockage et la compression des fichiers.
11. **Gestion des types de documents**
   - Les administrateurs doivent pouvoir définir des types de documents (ex. visite médicale, assurance, brevet) avec des règles associées (obligatoire ou non, associé à un pilote ou au club, date d’expiration ou non, emplacement de stockage par défaut, stockage par année ou non, etc.).
   - Ces types facilitent la création de documents et assurent l’uniformité.


## Exigences non fonctionnelles
- **Sécurité** : contrôle d’accès strict par rôle.
- **Traçabilité** : conserver l’historique des versions et l’état de validation.
- **Lisibilité** : rendu clair des statuts (expiré, en attente, validé).
- **Performance** : consultation rapide des listes “à valider” et “expirés”.

## Contraintes & dépendances
- Le stockage doit être compatible avec les structures existantes sous uploads/documents/.
- La solution doit être compatible avec la table existante des attachements (ex. `attachments`) ou garantir une continuité fonctionnelle.
- Les durées de validité peuvent s’étendre sur plusieurs années, sans imposer un rangement ou un filtrage basé sur l’année.

## Mesures de succès
- 100% des documents pilotes importés via ce mécanisme.
- Réduction des documents expirés non détectés.
- Délai moyen de validation réduit (à mesurer).

## Questions ouvertes
- Quel délai par défaut pour les alertes d’expiration ? 15 jours et une semaine avant.
- Qui peut s’abonner aux alertes (pilotes uniquement, administrateurs, tous les membres) ? Dépend du type de document.
- Quelles catégories de documents doivent être disponibles dès la première version ? Visite médicale, assurance, brevet pour les pilotes.
- Faut-il distinguer des niveaux de confidentialité par type de document ? À évaluer.
