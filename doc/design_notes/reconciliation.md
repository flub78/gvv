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
Il contient potentiellement une collection de ReconciliationLine, un ProposalLine ou une collection de MultiProposalCombination ainsi que d'autres informations, la date, le montant, la nature de l'opération.

Il a une méthode to_HTML pour afficher l'opération.

### ReconciliationLine

Objet qui correspond au rapprochement d'un montant. Si le StatementOperation est rapproché sur son montant global il ne contient qu'une seule ReconciliationLine. En cas de rapprochements multiple, l'opération du relevé est associée à plusieurs ReconciliationLine dont le montant global correspond au montant du Statement Opération.

Il a une méthode to_HTML pour afficher l'opération.

### ProposalLine

Objet qui contient les information au sujet des proposition de rapprochements. Il a une méthode to_HTML

### MultiProposalCombination

C'est une combinaison d'écritures dont le montant additionné correspond a la somme d'en élément de relevé (StatementOperation)

Le StatementOperation peut contenir un ou plusieurs MultiProposalCombination
Il existe une methode to_HTML pour cet object.



