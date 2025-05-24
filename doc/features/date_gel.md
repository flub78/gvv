# Date de gel

Il est important que la comptabilité ne soit plus modifiée une fois les comptes publiés.

Gvv est particulièrement flexible avant la clôture, par exemple la modification de la durée d'un vol ou le changement de pilote entraîne la refacturation et la propagation des changements. Une fois qu'un vol a été modifié, il n'y a pas de différences dans l'état du système avec ce qu'il aurait été si le vol avait été saisie initialement avec sa valeur finale. C'est très pratique pendant l'exploitation, on saisie les données et on les corrige en cas d'erreur pour qu'elles reflètent la réalité.

Cependant il faut que les modifications qui ont des conséquences sur la compta soient interdites une fois les comptes publiés.

C'était fait initialement à l'aide d'une date de gel dans un fichier de configuration. A l'origine, cette date devaient être mise à jour manuellement par le trésorier après la clôture puis cela a été automatisé.

Néanmoins cette approche ne convient plus avec la gestion des sections, chaque section devant pouvoir faire sa clôture sans attendre les autres et le fait qu'une section a clôturé ne devrait pas empêcher les autres de continuer à passer des écritures.  

Il faut maintenant avoir une date de gel par section et le plus simple est de gérer cela en base de données.