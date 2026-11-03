# PRD : Configuration unifiée de GVV

*Statut : validé — 2026-11-03*

## 1. Contexte

Les paramètres de GVV sont aujourd'hui dispersés entre plusieurs écrans et plusieurs supports, sans vue d'ensemble :

| Écran actuel | Accès | Contenu |
| :--- | :--- | :--- |
| Admin club > Configuration club | club-admin | Identité du club (nom, adresse, e-mail, site, logo, thème, FFVV…) |
| Admin club > Configuration (liste clé/valeur) | club-admin | Paramètres divers par clé : vols de découverte, relances, comptes de cotisation, aérodrome par défaut, cartes de membre… avec variantes par langue et par section |
| Paiements en ligne > Configuration | administrateur | Paramètres HelloAsso par section |
| Cartes de membre > Configuration | club-admin | Mise en page des cartes |
| Formulaires > Configuration | club-admin | Réglages des formulaires |
| Newsletter > Configuration | dev_users | Messagerie IMAP/SMTP, relecteurs, IA |
| *aucun écran* | édition manuelle des fichiers sur le serveur | Modules actifs (`gestion_vd`, `gestion_reservations`, `veille`…), copie des e-mails, SMTP, Google, SMS, ordonnanceurs, `dev_users`, base de données, URL… |

Seul l'**assistant d'installation** (`install/`) propose un parcours guidé : étapes numérotées, icône et titre par thème, valeurs actuelles préremplies, étapes optionnelles qu'on peut passer, saisie conservée quand on navigue entre les étapes, vérification des prérequis et indication claire de ce qui est fait. Mais il ne sert qu'à l'installation initiale. Une fois GVV installé, changer un module actif ou un paramètre SMTP oblige à éditer un fichier sur le serveur.

Ce PRD décrit une page de configuration unique, organisée en onglets, accessible depuis l'application. Il reprend les qualités de l'assistant d'installation, et les onglets affichés dépendent du profil de l'utilisateur.

## 2. Objectifs

* Un seul point d'entrée pour tous les paramètres de l'application.
* Rendre modifiables depuis l'interface les paramètres qui exigent aujourd'hui une intervention sur le serveur, en dehors des paramètres d'infrastructure réservés aux développeurs.
* Ne présenter à chacun que ce qu'il a le droit de voir et de modifier.
* Rendre le résultat de chaque modification évident : enregistré, refusé et pourquoi, ou pris en compte seulement au prochain chargement.
* L'administrateur n'a pas besoin de savoir si un paramètre est stocké dans un fichier ou en base de données.

## 3. Périmètre

### 3.1 Inclus

* Une page « Configuration » à onglets, accessible depuis le menu d'administration.
* La répartition des paramètres existants en onglets thématiques (§6).
* La visibilité et la modification des onglets selon le profil (§5).
* Les paramètres stockés dans les fichiers de configuration du club et dans la table de configuration.
* Le choix des textes des pages publiques « Règles de confidentialité » et « Conditions d'utilisation » (voir la demande associée).
* Des actions de test là où elles existent déjà ailleurs : envoi d'un e-mail de test, test IMAP, test de l'IA, test SMS.
* L'accès, depuis l'onglet concerné, aux listes de référence de la todo liste (types de vol, de qualification, de document, d'alerte, de maintenance, d'écriture comptable, tarifs et produits).
* La disparition des entrées de menu remplacées. Les anciennes adresses mènent au bon onglet.

### 3.2 Hors périmètre

* Le remplacement de l'assistant d'installation, qui reste l'outil de première installation.
* La modification depuis l'interface des paramètres dont une erreur empêcherait GVV de démarrer : connexion à la base de données, URL de base, clés de chiffrement. Ils restent affichés en lecture seule pour les dev_users (§6).
* La refonte des écrans de gestion des listes de référence : on y accède depuis la configuration, mais ils ne changent pas.
* La gestion des rôles et des autorisations, qui reste dans ses écrans actuels.
* Les tâches planifiées du serveur (cron, rclone) : elles sont hors de l'application.

## 4. Parties prenantes

* Membres du conseil d'administration (rôle `ca`).
* Administrateurs du club (rôle `club-admin`).
* Développeurs et mainteneurs (utilisateurs listés dans `dev_users`).
* Clubs utilisateurs de GVV (5-6 associations), qui doivent pouvoir adapter GVV sans intervention technique.

## 5. Profils et visibilité

| Profil | Voit | Modifie |
| :--- | :--- | :--- |
| Membre du CA | les onglets marqués CA dans §6, dont certains en lecture seule | uniquement les onglets marqués « CA (modification) » |
| club-admin | les onglets CA + ceux réservés au club-admin | tous les paramètres de ces onglets |
| dev_user | tous les onglets, y compris « Système » | tous les paramètres modifiables, et voit en lecture seule les paramètres d'infrastructure |

* **EX-PRO-01** : un onglet non autorisé pour le profil n'apparaît pas. Il n'est pas affiché grisé.
* **EX-PRO-02** : un accès direct par adresse à un onglet non autorisé affiche un message de refus explicite, jamais une page vide.
* **EX-PRO-03** : les profils se cumulent. Un utilisateur à la fois CA et dev_user voit l'union des onglets.
* **EX-PRO-04** : un utilisateur sans aucun de ces profils ne voit pas l'entrée de menu « Configuration ».

## 6. Organisation des onglets (proposition)

| Onglet | Profil minimal | Contenu |
| :--- | :--- | :--- |
| **Club** | CA (lecture), club-admin (modification) | Identité : sigle, nom, code, adresse, téléphone, e-mail, site, logo, contact RGPD |
| **Apparence** | club-admin | Titre de l'application, couleur de bannière, thème, palette |
| **Modules** | CA (lecture), club-admin (modification) | Activation des fonctionnalités : vols de découverte, réservations, formations, documents, paiements en ligne, ULM, tickets, pompes, rapprochements, newsletter de veille, auto-planchiste… |
| **Communication** | CA (modification) | Message du jour, copie systématique des e-mails, expéditeur et signature des e-mails |
| **Vols de découverte** | CA (modification) | Contacts par type d'appareil, expéditeur, signature, textes des courriels |
| **Finances** | CA (lecture), club-admin (modification) | Comptes de cotisation, seuils de relance, facturation (payeur non pilote, partage), contrôle de solde à la réservation |
| **Paiements en ligne** | club-admin | Paramètres HelloAsso par section (ouverts au club-admin, aujourd'hui réservés à l'administrateur technique) |
| **Documents et cartes** | club-admin | Cartes de membre, formulaires, accès aux types de documents |
| **Pages publiques** | club-admin | Textes des règles de confidentialité et des conditions d'utilisation, par langue |
| **Référentiels** | club-admin | Accès aux listes : types de vol, qualifications, alertes, maintenance, écritures, tarifs et produits, terrains, aérodrome par défaut |
| **Messagerie** | club-admin | Serveur SMTP, e-mail et téléphone de test, envoi de test |
| **Système** | dev_user | Newsletter (IMAP, IA, relecteurs), SMS, ordonnanceurs, mode de test, intégration Google, `dev_users`, et en lecture seule : base de données, URL, version, migration |

* **EX-PRO-05** : un onglet en lecture seule affiche les valeurs sans champ modifiable ni bouton d'enregistrement, et indique qu'il est en lecture seule. Les secrets n'y apparaissent jamais (EX-SEC-01).
* **EX-PRO-06** : le CA ne peut pas modifier l'identité du club.

## 6 bis. Pages publiques : règles de confidentialité et conditions d'utilisation

Ces deux pages sont accessibles sans connexion, et la page de connexion contient un lien vers chacune. La page de connexion pouvant servir de « page d'accueil » déclarée auprès de services tiers (Google), elle doit afficher le lien vers les règles de confidentialité.

Contenu attendu et origine de chaque information :

| Information affichée | Paramètre | Existant ? |
| :--- | :--- | :--- |
| Nom de l'association (responsable du traitement) | nom du club | oui |
| Adresse postale | adresse, code postal, ville du club | oui |
| E-mail de contact | e-mail du club | oui |
| Téléphone | téléphone du club | oui |
| Site du club | site web du club | oui |
| Contact RGPD (nom ou fonction et e-mail de la personne à qui adresser les demandes) | **contact RGPD**, onglet Club | **non, à créer**. Facultatif : s'il est vide, la page renvoie vers « le président » à l'e-mail du club |
| Mention des paiements en ligne (HelloAsso) | module paiements en ligne activé | oui |
| Texte des règles de confidentialité, par langue | **texte de confidentialité**, onglet Pages publiques | **non, à créer**. Facultatif : s'il est vide, un texte générique fourni par GVV est utilisé |
| Texte des conditions d'utilisation, par langue | **texte des conditions**, onglet Pages publiques | **non, à créer**. Même règle |
| Date de dernière mise à jour | date de dernière modification du texte | déduite, pas de saisie |

* **EX-PUB-01** : le texte générique fourni par GVV insère automatiquement les informations du club listées ci-dessus. Un club peut publier des pages conformes sans rien saisir d'autre que l'identité du club.
* **EX-PUB-02** : un texte propre au club peut utiliser les mêmes informations du club. Elles restent ainsi à jour quand l'identité du club change.
* **EX-PUB-03** : les mentions propres à un club (prestataires externes comme un stockage de sauvegarde, organismes destinataires, durées de conservation) se font dans le texte propre au club. Le texte générique ne mentionne que ce que GVV sait (modules activés).
* **EX-PUB-04** : l'onglet Pages publiques permet de prévisualiser chaque page et donne son adresse publique, prête à copier.
* **EX-PUB-05** : la page publique des newsletters (liste des numéros publiés et leurs PDF), accessible sans connexion quand la newsletter de veille est activée, fait partie des pages publiques. Son adresse est affichée dans cet onglet.

## 7. Exigences fonctionnelles

### 7.1 Navigation (inspirée de l'assistant d'installation)

* **EX-NAV-01** : chaque onglet a une icône et un titre, et chaque champ un libellé et une aide courte. Ces libellés et aides sont les mêmes que dans l'assistant d'installation quand le paramètre y figure.
* **EX-NAV-02** : chaque onglet affiche son état : *complet*, *incomplet* (un paramètre obligatoire manque) ou *optionnel non configuré*.
* **EX-NAV-03** : les champs sont préremplis avec les valeurs en vigueur. Quand un paramètre n'a jamais été défini, c'est la valeur par défaut de GVV qui s'affiche, signalée comme telle.
* **EX-NAV-04** : changer d'onglet alors que des modifications ne sont pas enregistrées demande une confirmation. La saisie n'est jamais perdue en silence.
* **EX-NAV-05** : l'onglet actif est conservé dans l'adresse de la page, ce qui permet de faire un lien direct vers un onglet.

### 7.2 Enregistrement et retour à l'utilisateur

* **EX-ENR-01** : chaque onglet s'enregistre indépendamment des autres.
* **EX-ENR-02** : après un enregistrement, un message indique explicitement le succès et liste les paramètres modifiés.
* **EX-ENR-03** : une valeur invalide est refusée avec un message placé à côté du champ concerné. Les autres saisies de l'onglet sont conservées.
* **EX-ENR-04** : si un paramètre ne peut pas être enregistré, par exemple parce que son fichier n'est pas modifiable sur le serveur, l'utilisateur est prévenu de ce qui n'a pas été enregistré et de la marche à suivre, comme le fait l'étape « Répertoires & droits » de l'installation.
* **EX-ENR-05** : un paramètre dont la modification n'est prise en compte qu'après une action supplémentaire (reconnexion, rechargement) l'indique.
* **EX-ENR-06** : l'activation d'un module dont des paramètres obligatoires manquent est possible, mais un avertissement désigne l'onglet à compléter.

### 7.3 Paramètres sensibles

* **EX-SEC-01** : les mots de passe, clés d'API et secrets ne sont jamais réaffichés en clair. Le champ indique seulement si une valeur est définie.
* **EX-SEC-02** : laisser vide un champ secret conserve la valeur existante. Effacer un secret demande une action explicite.
* **EX-SEC-03** : les paramètres sensibles ne sont visibles que dans les onglets réservés aux dev_users ou aux club-admin, selon §6.

### 7.4 Variantes par section et par langue

* **EX-VAR-01** : un paramètre qui peut varier par section (paiements en ligne, paramètres de la table de configuration propres à une section) affiche la section concernée et permet de passer d'une section à l'autre.
* **EX-VAR-02** : un paramètre qui peut varier par langue (textes, pages publiques) permet de saisir une valeur par langue disponible (français, anglais, néerlandais), avec repli sur le français.
* **EX-VAR-03** : l'utilisateur voit clairement si la valeur affichée est propre à la section ou à la langue, ou s'il s'agit de la valeur commune.

### 7.5 Actions de test

* **EX-TST-01** : les onglets Messagerie et Système proposent les tests existants (e-mail, IMAP, IA, SMS). Le résultat s'affiche dans la page, en succès ou en échec, avec le message d'erreur.
* **EX-TST-02** : un test utilise les valeurs enregistrées, et le dit.

### 7.6 Traçabilité

* **EX-HIS-01** : chaque modification est tracée : qui, quand, quel onglet, quel paramètre, ancienne et nouvelle valeur. Pour un secret, seul le fait qu'il a été modifié est tracé, jamais sa valeur.
* **EX-HIS-02** : chaque onglet donne accès à l'historique des modifications de ses paramètres, visible des profils qui peuvent modifier cet onglet.
* **EX-HIS-03** : depuis l'historique, un profil autorisé peut revenir à la valeur précédente d'un paramètre. Ce retour est lui-même tracé et suit les mêmes règles de validation qu'une saisie (EX-ENR-03). Un secret ne peut pas être restauré par ce moyen.

### 7.7 Transition

* **EX-TRA-01** : la page Configuration est accessible par une tuile dans la section « Administration du club » du tableau de bord, et par une entrée « Configuration » du menu d'administration, qui remplace les entrées existantes. Tuile et entrée de menu sont visibles de tout utilisateur ayant au moins un onglet autorisé (EX-PRO-04), y compris un dev_user qui ne serait pas membre du CA.
* **EX-TRA-02** : les anciennes adresses des écrans remplacés mènent à l'onglet correspondant.
* **EX-TRA-03** : un club qui met à jour GVV retrouve toutes ses valeurs existantes, sans ressaisie.
* **EX-TRA-04** : la livraison se fait en plusieurs étapes. Pendant la transition, les écrans existants restent disponibles pour les onglets pas encore livrés. Ordre de livraison :
  1. Pages publiques (confidentialité, conditions d'utilisation, newsletters), avec le paramètre contact RGPD de l'onglet Club ;
  2. Club, Modules, Messagerie, socle commun (profils, lecture seule, traçabilité) ;
  3. Les autres onglets.
* **EX-TRA-05** : la tâche « Unifier la configuration globale » de la todo liste passe dans la liste des tâches réalisées une fois la dernière étape livrée.

## 8. Exigences non fonctionnelles

* Interface Bootstrap 5, utilisable sur tablette.
* Libellés et messages traduits en français, anglais et néerlandais.
* Aucune modification de configuration ne doit pouvoir rendre GVV inaccessible à l'utilisateur qui la fait. Par exemple, désactiver un module ne doit pas masquer la page de configuration elle-même.
* La page reste rapide à afficher : moins de 2 secondes sur le serveur de production.

## 9. User stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Membre du CA | modifier le message du jour et les contacts des vols de découverte | tenir l'information à jour sans solliciter l'administrateur |
| club-admin | activer le module réservations depuis l'interface | ne pas avoir à éditer un fichier sur le serveur |
| club-admin | voir d'un coup d'œil quels onglets sont incomplets | savoir ce qui reste à configurer après une installation ou une mise à jour |
| club-admin | adapter les règles de confidentialité de mon club | publier une page conforme au RGPD et aux exigences de Google |
| dev_user | tester l'envoi d'e-mails et la connexion IMAP depuis la page | diagnostiquer une panne sans accès au serveur |
| dev_user | consulter la version, l'état de migration et la configuration de la base | diagnostiquer une installation |

## 10. Décisions

| Question | Décision |
| :--- | :--- |
| Le CA peut-il modifier l'identité du club ? | Non, lecture seule (EX-PRO-06) |
| Le CA voit-il certains onglets en lecture seule ? | Oui : Club, Modules, Finances (§6, EX-PRO-05) |
| Paiements en ligne ouverts au club-admin ? | Oui (§6) |
| Trace des modifications et retour arrière ? | Oui (§7.6) |
| Livraison en plusieurs étapes ? | Oui, en commençant par les pages publiques (EX-TRA-04) |
| Paramètres d'infrastructure | Lecture seule pour les dev_users (§3.2) |
