# Rapprochements bancaires

Il est possible de charger des fichiers CSV de relevés de compte afin d'effectuer les rapprochements bancaires.

Cela permet de
* comparer les opérations figurant sur vos relevés bancaires avec celles qui sont réellement enregistrées dans votre comptabilité
* générer automatiquement certaines information manquantes
* pré-remplir ou aider à la saisie des autres.
* repérer plus facilement les raisons des écarts.


## Banques supportées

Les relevés de compte en CSV ne sont pas standardisés, chaque banque peut avoir son propre format. Pour l'instant GVV support l'import des relevés émis par la Société Générale. En cas de changement de format ou de fichier émis par une autre banque il faudra adapter le programme.

## Rapprochement des opérations

Le rapprochement consiste à associer les lignes du relevé avec les opérations de GVV. Une fois les opérations associées, il est facile d'afficher les opérations qu'on ne retrouve pas soit dans le relevé, soit dans GVV.

GVV, ne peut pas rapprocher automatiquement, il peut néanmoins faciliter le rapprochement en suggérant des opérations à associer.

Question: est-ce qu'on considère qu'on ne peut rapprocher que des opérations de même date et de même montant ?

GVV identifie les type d'opérations suivante dans le relevé de banque:
* Paiement par CB
* Remise d’espèces
* Débit d'un chèque émis
* Commission et frais bancaires
* Prélèvement automatique
* Virement émis
* Prélèvement de remboursement d'emprunt

* Encaissement CB
* Remise de chèque
* Régularisation, annulation de frais bancaires
* Virement reçu

* il faudra ajouter retrait de liquide, mais je n'ai pas d'exemple dans les 6 derniers mois

### Identification des comptes

Pour pouvoir générer ou identifier des écritures il faut trouver quels sont les comptes concernés dans GVV à partir des information du relevé.

Attention, dans certains cas ça peut-être ambiguë, par exemple dans le cas d'un virement reçu, le champ DE: ne permet pas forcément d'identifier le compte client. Dans une famille une personne peut approvisionner plusieurs comptes. 

On ne peut pas non plus se baser sur le motif qui peux être absent.
