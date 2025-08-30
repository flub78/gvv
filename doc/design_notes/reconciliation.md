# Reconciliation

## Objects mis en oeuvre

### ReleveParser

Il a une fonction parse qui retourne les éléments identifiés dans le relevé de compte c'est un objet / structure ParserResult

### Reconciliator

Le constructeur prend un ParserResult en entré. Cet object fait le rapprochement avec les écritures GVV présentes dans la base de données. Il est ensuite possible de l'interroger pour connaître les résultats de la reconciliation. 
Il contient entre autre une collection de StatementOperation

Il a une méthode to_HTML pour afficher le Reconciliator sous forme de table

### StatementOperation

Résultat du rapprochement d'une opération. Il contient des informations sur l'opération (statement operation). 
Il contient potentiellement une collection de ReconciliationLine, une collection de ProposalLine ou une collection de MultiProposalCombination ainsi que d'autres informations, la date, le montant, la nature de l'opération.

L'attribut `proposals` contient une liste d'objets ProposalLine créés à partir du hash d'écritures retourné par le modèle.

L'attribut `multiple_proposals` contient une liste d'objets MultiProposalCombination, chacun encapsulant les données brutes d'une combinaison d'écritures dont la somme correspond au montant de l'opération bancaire.

Il a une méthode to_HTML pour afficher l'opération.

### ReconciliationLine

Objet qui correspond au rapprochement d'un montant. Si le StatementOperation est rapproché sur son montant global il ne contient qu'une seule ReconciliationLine. En cas de rapprochements multiple, l'opération du relevé est associée à plusieurs ReconciliationLine dont le montant global correspond au montant du Statement Opération.

Il a une méthode to_HTML pour afficher l'opération.

### ProposalLine

Objet qui contient les informations au sujet des propositions de rapprochements. Il peut être créé à partir d'un hash d'écriture où la clé est l'ID de l'écriture et la valeur est son image/description. Il a une méthode to_HTML

Exemple de structure de données :
```
Array
(
    [31238] => 20/02/2025 423,17 € Echéance Prêt 02/2025 223555146266
    [31282] => 20/02/2025 29,11 € Intérêt Prêt 02/2025 223555146266
)
```

### MultiProposalCombination

C'est une combinaison d'écritures dont le montant additionné correspond à la somme d'un élément de relevé (StatementOperation)

Le StatementOperation peut contenir un ou plusieurs MultiProposalCombination. Chaque MultiProposalCombination contient directement les données brutes des écritures sous forme d'array dans l'attribut `combination_data`.

Il existe une méthode to_HTML pour cet objet.

Exemple de structure de données d'entrée :
```
Array
(
    [0] => Array
        (
            [montant] => 200.00
            [image] => 05/04/2025 200,00 € LARTISIEN ulm Xavier - Virement - 559588019957 OpenFlyers : 63595
            [ecriture] => 35284
        )
    [1] => Array
        (
            [montant] => 476.50
            [image] => 07/04/2025 476,50 € Remb part d'assurance RC CTL Rem Chq 0039097
            [ecriture] => 31268
        )
)
```



