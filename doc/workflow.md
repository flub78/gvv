# Development workflow

Ce fichier pour documenter les étapes de développement, typiquement la création d'une nouvelle resource avec son contrôleur, son modèle et ses vues.

## Génération de la migration

Définir la nouvelle table dan phpmyadmin puis d'exporter le schema pour générere la migration.

Attention aux champs auto-incrémenté, ne pas oublier de le spécifier dans phpmyadmin. 

Créer la migration dans application/migrations

Naming conventions for CodeIgniter 2.x Migration Classes:

* File Names: Snake_case with numeric prefixes (no specific timestamp format was enforced).
* Class Names: CamelCase prefixed with Migration_.
Methods: Classes must define up() and down() methods.

Incrémenter application/config/migration.php

## Génération du modèle

Dans application/models. Attention select_page doit retourner également la colonne de la clé primaire même si elle n'est jamais affichée.


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

Les metadonnées doivent être renseignées pour un affichage correcte des vues. Elle sont gérées dans Gvvmetadata.php. La tenttive de les répartir ans plusieurs fichiers n'a jamais abouti.

Par exemple:

La page des vols avion affiche des pilotes et des machines (vapilid et vamacid).

On peut utiliser les logs, pour identifier les champs pour lesquels il faut définit les metadonnées:
```
DEBUG - 2025-02-10 14:28:09 --> GVV: input_field(user_roles_per_section, user_id, 58, ro) type=int, subtype=
DEBUG - 2025-02-10 14:28:09 --> GVV: input_field(user_roles_per_section, types_roles_id, 5, ro) type=int, subtype=
DEBUG - 2025-02-10 14:28:09 --> GVV: input_field(user_roles_per_section, section_id, 1, ro) type=int, subtype=

DEBUG - 2025-02-10 14:33:18 --> GVV: array_field (vue_user_roles_per_section, username), id=1, type=varchar, subtype=, value=testuser
DEBUG - 2025-02-10 14:33:18 --> GVV: array_field (vue_user_roles_per_section, email), id=1, type=varchar, subtype=, value=testuser@free.fr
DEBUG - 2025-02-10 14:33:18 --> GVV: array_field (vue_user_roles_per_section, section_name), id=1, type=varchar, subtype=, value=Planeur
DEBUG - 2025-02-10 14:33:18 --> GVV: array_field (vue_user_roles_per_section, role_type), id=1, type=varchar, subtype=, value=user
```

Définir les métadonnees:

```
   $this->field['volsa']['vapilid']['Subtype'] = 'selector';
   $this->field['volsa']['vapilid']['Selector'] = 'pilote_selector';
```




## Génération d'un test end-to-end

Dans le projet dusk_gvv
