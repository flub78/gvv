# Vols avion

Les vérifications pour les vols avions ne sont pas les mêmes que pour les vols planeur.

* on ne test pas si le pilote est déjà en vol.
* On ne test pas si l'avion est déjà en vol
* on ne test pas si l'horamètre couvre déja la plage.

Les vérifications ci-dessus sont absolues, ce sont toujoures des cas d'erreur.
Par contre in ne faut pas imposer que les vols soient rentrés dans l'ordre et donc pas vérifier que la valeur d'horamètre de coincide pas avec la valeur précédante.