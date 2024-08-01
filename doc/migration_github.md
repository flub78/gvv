# Migration Github

Suite à la disparition des services de developpez.com, j'ai migré les sources du site vers Github.

Cela implique certaines adaptations. La procédure d'installation sous subversion installait tous les fichiers y compris les fichier de configuration. Il suffisait alors d'adapter les fichiers de configuration. Le script de mise à jour était sélectif et ne mettait pas à jour les fichiers de configuration.

Sous Github, la mise à jour des fichiers des fichiers est globale.

## Étapes de migration d'un déploiement sous subversion.

1. Sauvegardez les fichiers de configuration dans application/config
2. Faire une copie de gvv
3. git clone https://github.com/flub78/gvv.git
4. renommez le répertoire si besoin
5. ré-installez vos fichiers de configuration
6. Testez, cela devrait fonctionner.


