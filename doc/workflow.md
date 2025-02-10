# Development workflow

Ce fichier pour documenter les étapes de développement.

## Génération de la migration

Définir la nouvelle table dan phpmyadmin puis d'exporter le schema pour générere la migration.

Attention aux champs auto-incrémenté, ne pas oublier de le spécifier dans phpmyadmin. 

Créer la migration dans application/migrations

Naming conventions for CodeIgniter 2.x Migration Classes:

* File Names: Snake_case with numeric prefixes (no specific timestamp format was enforced).
* Class Names: CamelCase prefixed with Migration_.
Methods: Classes must define up() and down() methods.

Update application/config/migration.php

## Génération du modèle

dans application/models. Attention select_page doit retourner également la colonne de la clé primaire même si elle n'est jamais affichée.


## Génération des fichiers de traduction

language/french/attachments_lang.php
  
## Génération du contrôleur

dans application/controllers

### Création d'un test unitaire du modèle 

http://gvv.net/index.php/attachments/test

Tests are assertion based

    $this->load->library('unit_test');
    $this->unit->run($this->model->method(), 'expected_result', 'test_name');
    echo $this->unit->report();

and test execution

http://gvv.net/index.php/attachments/test

Pour une raison inconnue on ne peux pas appeler les fonctions de test depuis le modèle. Donc on retourne un tableau de résultat de contrôle d'assertions.

Si le modèle a besoin d'informations provenant d'autres pages c'est dans le select_page que la jointure est implémentée.

```
       $where = "volsa.vapilid = membres.mlogin and volsa.vamacid = machinesa.macimmat";

        $select = 'vaid, vadate, vapilid, vamacid, vacdeb, vacfin, vaduree, vaatt, vaobs, vainst as instructeur, valieudeco';
        $select .= ', concat(mprenom," ", mnom) as pilote, vacategorie, vadc, maprive as prive';
        $select .= ", facture, mdaten, (mdaten > \"$date25\") as m25ans, payeur, essence, reappro";
        $from = 'volsa, membres, machinesa';

        // echo "select $select from $from where $where and $selection" . br(); exit;

        $result = $this->db->select($select, FALSE)->from($from)->where($where)->where($selection)->
        // ->limit($nb, $debut)
        order_by("vadate $order, vacdeb $order")->get()->result_array();
```

## Génération des vues

### Génération des vues de la liste

select_page() dans le modèle retourne les valeurs brutes depuis la base de données. C'est aussi cette fonction qui stocke les valeurs dans le cache.

Dans la vue bs_tableView.php c'est la fonction $this->gvvmetadata->table("vue_attachments", $attrs, ""); qui génère la table HTML à aficher.

Les champs sont affichés par la fonction array_field de la classe Metadata.

### Métadonnées

Les metadonnées doivent être renseignées pour un affichage correcte des vues.

Par exemple:

La page des vols avion affiche des pilotes et des machines (vapilid et vamacid).




## Génération d'un test end-to-end

Dans le projet dusk_gvv
