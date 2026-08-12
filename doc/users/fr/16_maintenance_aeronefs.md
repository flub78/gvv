# Gestion de la Maintenance des Aéronefs

Le module Maintenance suit l'état de navigabilité de la flotte : équipements embarqués, programmes d'entretien, dossiers ouverts par aéronef ou équipement, opérations réalisées et bulletins de service constructeur. Il s'adresse à trois profils : le mécano (gestion complète), le responsable de section / trésorier (consultation), et tout pilote (consultation de l'état de navigabilité uniquement).

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Équipements maintenables](#équipements-maintenables)
3. [Programmes d'entretien](#programmes-dentretien)
4. [Dossiers d'entretien](#dossiers-dentretien)
5. [Opérations de maintenance](#opérations-de-maintenance)
6. [Bulletins de service](#bulletins-de-service)
7. [Synthèse de navigabilité](#synthèse-de-navigabilité)
8. [Droits d'accès](#droits-daccès)

## Vue d'ensemble

Le tableau de bord Maintenance (menu **Planeurs/Remorqueurs > Maintenance**, réservé aux mécanos et administrateurs) donne accès à l'ensemble des fonctions du module.

![Tableau de bord Maintenance](../screenshots/maintenance_aeronefs/dashboard_maintenance.png)

- **Équipements** : matériel embarqué suivi indépendamment de l'aéronef (parachute, extincteur, radio, etc.)
- **Programmes d'entretien** : contenu structuré (sections/tâches) déposé en Markdown, avec règle de butée (calendaire et/ou horaire)
- **Dossiers d'entretien** : association d'un aéronef ou d'un équipement à un programme, avec son cycle de vie
- **Opérations de maintenance** : intervention datée qui fait progresser un dossier, en saisie directe ou par dépôt d'un compte rendu papier
- **Bulletins de service** : documents constructeur rattachés à un aéronef, avec statut de traitement
- **Synthèse navigabilité** : état global de la flotte, accessible à tout pilote en lecture seule

---

## Équipements maintenables

Un équipement est rattaché à un seul aéronef à la fois. Il peut être créé, modifié, transféré vers un autre aéronef, ou désactivé (l'historique reste consultable).

![Liste des équipements](../screenshots/maintenance_aeronefs/equipements_liste.png)

- **Nouvel équipement** : nom, aéronef de rattachement, description
- **Transférer** : change l'aéronef de rattachement ; les dossiers et opérations déjà enregistrés restent liés à l'équipement (son identifiant ne change pas) et donc consultables sans perte d'historique
- **Désactiver / Réactiver** : masque l'équipement des listes actives sans supprimer son historique

---

## Programmes d'entretien

Un programme définit le contenu à vérifier, structuré en sections puis en tâches, et une règle de butée (échéance calendaire, horaire, ou les deux).

![Détail d'un programme](../screenshots/maintenance_aeronefs/programme_detail.png)

Le contenu est déposé en Markdown, avec un format volontairement simple :

```markdown
# Visite 100 heures cellule

## Moteur

### Vidange moteur
Vidange de l'huile moteur et remplacement du filtre.

### Controle des bougies
Depose, nettoyage et controle de l'ecartement des electrodes.

## Cellule

### Controle visuel du fuselage
Inspection des surfaces et jonctions, recherche de fissures.
```

- **Titre du programme** : titre de niveau 1 (`#`)
- **Sections** : titres de niveau 2 (`##`), pour regrouper les tâches (moteur, cellule, équipements de sécurité, etc.)
- **Tâches** : titres de niveau 3 (`###`), chaque point de contrôle élémentaire ; le texte sous le titre devient sa description

Un fichier invalide (aucune section, section sans tâche, tâche sans titre) est rejeté avant tout enregistrement — rien n'est archivé tant que le fichier n'est pas valide. Chaque dépôt crée une nouvelle version, conservée dans l'historique documentaire ; les dossiers déjà ouverts continuent de référencer le programme et sa structure la plus récente.

---

## Dossiers d'entretien

Un dossier associe un aéronef ou un équipement à un programme d'entretien et suit son cycle de vie : **Ouvert → Suspendu/Clôturé/Abandonné**.

![Détail d'un dossier](../screenshots/maintenance_aeronefs/dossier_view.png)

- **Ouvrir un dossier** : depuis le tableau de bord Maintenance, choisir l'entité (aéronef ou équipement) puis le programme à appliquer
- **Suspendre** : interrompt temporairement le suivi (aéronef immobilisé, par exemple), réactivable
- **Clôturer** / **Abandonner** : mettent fin au suivi ; le dossier reste consultable dans l'historique de l'entité
- L'historique des dossiers d'une entité (y compris clôturés) reste accessible même après un transfert d'équipement vers un autre aéronef

---

## Opérations de maintenance

Une opération enregistre une intervention datée sur un dossier ouvert, selon deux modes possibles sur le même écran :

![Formulaire d'opération](../screenshots/maintenance_aeronefs/operation_form.png)

- **Saisie directe** : chaque tâche du programme est cochée Fait / Non fait / Non applicable, avec un commentaire optionnel par tâche
- **Compte rendu papier** : dépôt d'un scan ou d'une photo du compte rendu signé par l'atelier (PDF ou image), consultable ensuite depuis l'historique du dossier
- **Relevé horamètre** / **Nouvelle échéance** : selon la règle de butée du programme, ces champs recalculent automatiquement le potentiel restant du dossier après enregistrement

Les deux modes ne s'excluent pas : une opération peut à la fois cocher des tâches et joindre un compte rendu.

---

## Bulletins de service

Les bulletins constructeur sont rattachés à un aéronef et suivis avec un statut applicatif (à traiter / traité / non applicable), indépendant du système documentaire générique utilisé pour le reste du dossier pilote.

![Liste des bulletins](../screenshots/maintenance_aeronefs/bulletins_liste.png)

- Sélectionner l'aéronef pour voir ses bulletins déposés
- **Déposer un bulletin** : fichier + description
- Le statut se change directement depuis la liste, sans repasser par un formulaire

---

## Synthèse de navigabilité

La synthèse donne l'état global de chaque aéronef (À jour / Échéance proche / Dépassé), calculé comme le pire état parmi ses dossiers ouverts et ceux de ses équipements.

![Vue flotte](../screenshots/maintenance_aeronefs/synthese_flotte.png)

En cliquant sur un aéronef, le détail affiche chaque entité (aéronef + ses équipements) avec, pour chaque dossier ouvert, l'échéance courante et/ou le potentiel restant en heures.

![Détail d'un aéronef](../screenshots/maintenance_aeronefs/synthese_aeronef.png)

Un export PDF est disponible pour chaque aéronef, utile pour un contrôle sans connexion.

---

## Droits d'accès

| Profil | Synthèse navigabilité | Historique (dossiers/opérations/bulletins/programmes) | Écriture |
|---|---|---|---|
| Mécano / administrateur | Lecture | Lecture | Complète (mécano borné à sa section) |
| Responsable de section, trésorier | Lecture | Lecture | Aucune |
| Pilote (membre standard) | Lecture | — | Aucune |

L'accès en écriture (création, modification, transfert, changement de statut) est toujours réservé aux mécanos et administrateurs. Toute tentative d'accès non autorisé affiche un message d'erreur explicite — jamais un échec silencieux.
