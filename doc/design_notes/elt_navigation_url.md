# URL de navigation vers un élément.
    
Un mécanisme courant dans GVV est la possibilité de naviguer depuis un élément affiché dans une table vers l'élément lui même.
Il est possible par exemple de naviguer d'un vol vers le planeur, d'une écriture vers un compte, .etc.

Cette facilité de navigation est assez agréable pour l'utilisateur, elle lui évite de repasser par les menus.


## Comment ça marche.

Si on prend par exemple la liste des vols avions, à partir d'un vol on peut naviguer vers le pilote et vers l'avion.

La table est généré depuis la vue vols_avion/bs_tabeleView.php par :
```
$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'mode' => ($has_modification_rights || $auto_planchiste) ? "rw" : "ro",
    'class' => $classes
);
...
echo $this->gvvmetadata->table("vue_vols_avion", $attrs, "");
```

La méthode table de la classe Metadata va utiliser les liste "fields" passée en paramètre ou les champs par défaut de la table. Dans notre cas on voit que les champs incluent vapilid et vamacid qui sont les champs de la base de données pour identifier le pilote et l'avion.

Pour ces champ, la génération du lien est automatique, une fois le sous-type défini à "key" et l'action à "avion/edit", le lien est généré automatiquement.
```
$this->field['vue_vols_avion']['vamacid']['Subtype'] = 'key';
$this->field['vue_vols_avion']['vamacid']['Action'] = 'avion/edit';
```

Pour les attachements c'est un peu plus compliqué, le champ referenced_table identifie la ressource (le contrôleur) et le champ referenced_id identifie l'élément. 

On veut générer quelque chose qui ressemble à :

    <a href="https://gvv.planeur-abbeville.fr/index.php/avion/edit/F-JUFA">F-JUFA</a>

Le mieux est probablement de faire le traitement dans le modèle.
