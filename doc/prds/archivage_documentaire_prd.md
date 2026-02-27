# PRD — Archivage Documentaire

Date : 4 février 2026 — mis à jour le 22 février 2026 (suppression unique_per_entity)

## Contexte
L’association doit archiver des documents liés aux pilotes et, potentiellement, à d’autres usages (documents club, déclarations d’activité, renouvellements, etc.). Les documents peuvent avoir une date de validité, être remplacés par des versions plus récentes, nécessiter une validation administrative et faire l’objet d’alertes d’expiration.

Le but n’est pas de se substituer aux outils existants (GESASSO, suivi des exigences médicales par la FFPLUM, espace partagé), mais de fournir un mécanisme simple et intégré pour les cas où nous avons des obligations réglementaires. L’espace partagé assure le stockage, mais sans gestion des versions, des validations ou des alertes. Ce module comble ces lacunes uniquement pour les documents soumis à exigences.

Ce PRD s’appuie sur l’analyse existante : [doc/design_notes/reuse_pilot_documents_attachments.md](doc/design_notes/reuse_pilot_documents_attachments.md).

## Objectifs
- Archiver des documents liés aux pilotes, sections ou au club.
- Gérer documents avec ou sans date de validité, avec statut d'expiration.
- Conserver l'historique des versions lors d'un remplacement.
- Exposer une liste dédiée des documents expirés pour les administrateurs.
- Notifier avant l'expiration (email + notification à la connexion).
- Permettre aux administrateurs de désactiver les alertes document par document.
- Permettre la réutilisation du stockage pour un futur module d'acceptation de documents (PRD séparé).

## Non-objectifs
- Gestion complète d’un GED (indexation avancée, recherche OCR, signatures électroniques, etc.).
- Automatisation de la collecte de documents via des tiers externes.

## Portée
### Inclus
- Documents liés à un pilote (ex. visite médicale) et documents non associés à un pilote (ex. documents club/sections).
- Plusieurs documents du même type peuvent coexister pour un même pilote ou une même entité (ex. deux assurances simultanées).
- Edition in-place d'un document (libellé, description, fichier) sans création de nouvelle version.
- Création explicite d'une nouvelle version via un bouton dédié, avec conservation de l'historique.
- Indicateurs visuels d'expiration et notifications avant expiration.
- Désactivation des alertes par document (par les administrateurs).
- Gestion des types de documents avec règles associées (obligatoire, portée, expiration, unicité, etc.).

### Exclu
- Modification automatique des statuts de membres ou des droits à partir des documents.
- Archivage physique ou destruction légale des documents.

## Personae & rôles
- **Pilote** : peut consulter ses documents, en ajouter, supprimer ses propres documents.
- **Administrateur (membre du CA)** : accès à tous les documents, voit la liste des documents expirés, peut désactiver les alertes par document.
- **Association/Club** : documents non liés à un pilote (documents club/sections).

## Parcours clés
1. **Pilote ajoute un document** → choisit le type, saisit un libellé si nécessaire → document visible immédiatement dans sa liste.
2. **Document arrive à expiration** → marqueur d'expiration affiché → alertes email envoyées → visible dans la liste "expirés" des administrateurs.
3. **Administrateur désactive une alerte** → clic sur l'alerte du document → plus d'alertes pour ce document.
4. **Edition d'un document** → l'utilisateur modifie le libellé, la description ou remplace le fichier en place → aucune nouvelle version créée → le document reste le même dans la chaîne.
5. **Nouvelle version** → l'utilisateur clique "Nouvelle version" sur un document existant → un nouveau maillon est créé dans la chaîne → la nouvelle version devient active → les versions précédentes restent accessibles.

## Exigences fonctionnelles
1. **Ajout de documents par les administrateurs**
   - Les administrateurs peuvent ajouter des documents pour :
     - Un pilote spécifique (documents pilotes, ex. visite médicale, assurance, brevet).
     - Une section spécifique (documents section, ex. procédures, règlements locaux).
     - Le club entier (documents club, ex. statuts, documents légaux, procédures générales).
   - Cette capacité s'ajoute à celle des pilotes qui peuvent ajouter leurs propres documents.

2. **Association des documents**
   - Un document peut être associé à un pilote, à une section, ou au club (document "club").
3. **Dates de validité**
   - La date de validité est optionnelle.
   - Le système doit déterminer l'état "expiré" en fonction de la date de validité.
4. **Documents et types de documents**
   - Un type de document définit des règles (portée, obligatoire, expiration, unicité) ; il ne constitue pas un emplacement unique par pilote.
   - Plusieurs documents du même type peuvent coexister pour un même pilote ou une même entité (ex. licence planeur et licence ULM sont deux instances du type "licence").
   - Chaque document dispose d'un libellé permettant de l'identifier parmi d'autres documents du même type.

5. **Versionning**
   - **Edition in-place** : l'utilisateur peut modifier le libellé, la description ou remplacer le fichier du document existant sans créer de nouvelle version.
   - **Nouvelle version** : action explicite via un bouton dédié sur le document. Crée un nouveau maillon dans la chaîne (lien vers la version précédente), la nouvelle version devient active, les précédentes restent consultables.
   - Si l'utilisateur ajoute un document d'un type pour lequel un document du même type existe déjà pour la même entité, un avertissement lui propose de créer une nouvelle version plutôt qu'un document indépendant.
   - L'historique des versions est accessible depuis le document courant.
6. **Suppression des documents**
   - Un pilote peut supprimer ses propres documents.
   - Un administrateur peut supprimer tout document.
7. **Accès administrateur**
   - Les administrateurs ont accès à tous les documents.
   - Ils disposent d’un accès direct à la liste des documents "expirés".
8. **Affichage des expirations**
   - Les documents expirés doivent être affichés avec un marqueur visuel spécifique.
   - Les documents proches de l’expiration doivent être mis en évidence.
   - Tous les documents n’ont pas de date d’expiration. Pour ceux qui en ont, l’état doit être "actif", "expiration proche" ou "expiré".
   - Pour les documents pilotes, un type peut être marqué "obligatoire". Si obligatoire et aucun document valide ou actif, le statut "manquant" est affiché pour le pilote.
9. **Désactivation des alertes**
   - Les administrateurs peuvent désactiver les alertes document par document en cliquant sur l’alerte.
   - Un document avec alerte désactivée n’apparaît plus dans les notifications ni dans la liste des documents expirés.
- 
10. **Système de notifications et alertes**
   
   **10.1 Destinataires des notifications**
   
   Le système de notifications fonctionne sur trois niveaux :
   
   - **Notifications personnelles (pilote)** : Chaque pilote reçoit des alertes concernant ses propres documents (expiration proche, document manquant). Canal : email + bannière UI à la connexion.
   
   - **Notifications administratives (bureau/CA)** : Les membres du bureau et les administrateurs reçoivent des alertes groupées concernant tous les documents expirés ou manquants. Canal : email quotidien/hebdomadaire + bannière UI + liste dédiée.
   
   - **Notifications section (optionnel, phase 2)** : Si le système de rôles le permet, les responsables de section peuvent recevoir des alertes concernant les documents de leur section.
   
   **10.2 Mécanisme de souscription**
   
   Les notifications sont basées sur les rôles avec possibilité d'opt-out :
   
   - **Pilotes** : notifications automatiques pour leurs propres documents (peuvent désactiver dans leurs préférences)
   - **Bureau/Admin** : notifications automatiques pour documents expirés/manquants (peuvent désactiver par type de notification)
   - **Section** : opt-in pour recevoir les alertes de leur section
   
   **10.3 Canaux de notification**
   
   - **Email** (prioritaire, phase 1) : Infrastructure existante dans GVV, template dédié pour les alertes documentaires
   - **Bannière UI** (phase 1) : Affichage des alertes à la connexion dans l'interface
   - **SMS** (optionnel, phase 2) : Réservé aux alertes critiques (7 jours avant expiration), nécessite intégration fournisseur externe
   
   **10.4 Gestion des rappels et anti-spam**
   
   Pour éviter de spammer les utilisateurs, le système applique des règles de fréquence basées sur l'urgence :
   
   - **90 jours avant expiration** : 1 notification initiale
   - **30 jours avant** : rappel si pas de notification depuis 15 jours
   - **15 jours avant** : rappel si pas de notification depuis 7 jours
   - **7 jours avant** : rappel tous les 2 jours
   - **Après expiration** : rappel quotidien (jusqu'au dépôt d'un nouveau document valide)
   
   L'historique des notifications est conservé pour éviter les doublons et permettre la traçabilité.
   
   **10.5 Types de notifications**
   
   - **Document expire bientôt** : notification au pilote concerné selon le délai configuré dans le type de document
   - **Document expiré** : notification au pilote + ajout à la liste admin
   - **Document manquant** : pour les types obligatoires, notification si aucun document valide n'existe
   - **Résumé administratif** : email groupé hebdomadaire (lundi) avec la liste complète des documents nécessitant attention
   
   Le délai d'alerte est paramétrable par type de document (champ `alert_days_before` de la table `document_types`, valeur par défaut : 30 jours).
**10.6 Infrastructure technique**

Le système de notifications s'appuie sur :

- **Tâche CRON quotidienne** : détection des documents proches de l'expiration (excluant `alarm_disabled = 1`)
- **Table `notification_history`** : traçabilité des notifications envoyées pour éviter les doublons
- **Bibliothèque `Notification_manager`** : gestion centralisée de l'envoi et des règles de fréquence
- **Templates email** : réutilisation de l'infrastructure existante (`application/libraries/Email.php`)

11. **Types de fichiers supportés**
   - Le système doit accepter au minimum les mêmes types que l’archivage des factures : images et PDF.
12. **Réutilisation des mécanismes existants**
   - Le système doit réutiliser les bibliothèques et mécanismes existants pour le stockage et la compression des fichiers.
13. **Gestion des types de documents**
   - Les administrateurs doivent pouvoir définir des types de documents (ex. visite médicale, assurance, brevet) avec des règles associées (obligatoire ou non, portée pilote/section/club, date d’expiration ou non, unique par entité ou non, délai d’alerte).
   - Ces types définissent des règles applicables aux documents (obligatoire, portée, expiration, délai d'alerte), pas des emplacements uniques. Un pilote peut avoir plusieurs documents du même type simultanément.


## Exigences non fonctionnelles
- **Sécurité** : contrôle d'accès strict par rôle.
- **Traçabilité** : conserver l'historique des versions et des notifications envoyées.
- **Lisibilité** : rendu clair des statuts d'expiration (actif, proche, expiré, alerte désactivée).
- **Performance** : consultation rapide de la liste des documents expirés.
- **Fiabilité des notifications** : anti-spam intégré, traçabilité complète, gestion gracieuse des erreurs d'envoi.

## Contraintes & dépendances
- Le stockage doit être compatible avec les structures existantes sous uploads/documents/.
- La solution doit être compatible avec la table existante des attachements (ex. `attachments`) ou garantir une continuité fonctionnelle.
- Les durées de validité peuvent s’étendre sur plusieurs années, sans imposer un rangement ou un filtrage basé sur l’année.

## Mesures de succès
- 100% des documents pilotes importés via ce mécanisme.
- Réduction des documents expirés non détectés.
- Taux d'ouverture des notifications > 60%.
- Zéro plainte de spam sur les notifications.
- Délai moyen entre expiration et renouvellement < 7 jours.

## Questions ouvertes
- ~~Quel délai par défaut pour les alertes d'expiration ?~~ → **Résolu** : 30 jours (paramétrable par type de document via `alert_days_before`)
- ~~Qui peut s'abonner aux alertes ?~~ → **Résolu** : Pilotes (auto), Bureau/Admin (auto), Section (opt-in)
- Quelles catégories de documents doivent être disponibles dès la première version ? Visite médicale, assurance, brevet pour les pilotes.
- Faut-il distinguer des niveaux de confidentialité par type de document ? À évaluer.
- Faut-il supporter les SMS dès la phase 1, ou différer à la phase 2 ? → **Recommandation** : Phase 2, email suffit pour MVP
- Quel fournisseur SMS si implémentation phase 2 ? (Twilio, OVH, autre ?)
- Existe-t-il un rôle "chef de section" dans la table `membres` pour les notifications section ?
