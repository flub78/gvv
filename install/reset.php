<html>

<head>
    <meta http-equiv="Content-Language" content="en" />
    <meta name="GENERATOR" content="PHPEclipse 1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Réinitialisation de GVV</title>
</head>

<body bgcolor="#FFFFFF" text="#000000" link="#FF9966" vlink="#FF9966" alink="#FFCC99">
    <?php

    /*
 * Installation de GVV
 *
 * Created on 31 oct. 2011
 *
 * GVV est géré sous Code Igniter, pour fonctionner il faut que la base de données
 * soit installée. Cette partie fonctionne donc sans CodeIgniter.
 *
 * Ce script efface toutes les tables dans la base de données.
 *
 */

    /**
     * Vérifie l'existence d'un fichier ou répèrtoire
     */
    function checkfile($filename, $comment = "", $type = "fichier") {
        if (!file_exists($filename)) {
            echo "<br>Le $type $filename n'éxiste pas.<br>$comment";
            exit();
        } else {
            echo "$type $filename trouvé.<br>";
        }
    }

    /**
     * Lit les paramètres de base de données depuis un fichier de configuration
     */
    function read_db_parameters($filename) {
        $res = array();
        $txt = file($filename);
        foreach ($txt as $line) {
            foreach (array('hostname', 'username', 'password', 'database') as $field) {
                if (preg_match("/.*($field)(.*)=\s.(.*)(';)/", $line, $matches)) {
                    $res[$field] = $matches[3];
                }
            }
        }

        if (isset($_GET['HOSTNAME'])) $res['hostname'] = $_GET['HOSTNAME'];
        if (isset($_GET['USERNAME'])) $res['username'] = $_GET['USERNAME'];
        if (isset($_GET['PASSWORD'])) $res['password'] = $_GET['PASSWORD'];
        if (isset($_GET['DATABASE'])) $res['database'] = $_GET['DATABASE'];

        return $res;
    }

    /**
     * Calcul l'URL du site
     */
    function site_url() {
        $protocol = strpos(strtolower($_SERVER['REQUEST_SCHEME']), 'https')
            === FALSE ? 'http' : 'https';

        $host     = $_SERVER['HTTP_HOST'];
        $script   = $_SERVER['SCRIPT_NAME'];
        $params   = $_SERVER['QUERY_STRING'];

        $currentUrl = $protocol . '://' . $host . $script . '?' . $params;

        $lst = preg_split("/\//", $script);
        array_pop($lst);
        array_pop($lst);
        $script = join("/", $lst);
        $url = $protocol . '://' . $host . $script;
        return $url;
    }

    echo "<H1>Réinitialisation de GVV</H1><br>";

    echo "Ce script efface toutes les tables afin de pouvoir relancer l'installation <br/>";

    echo "<H2>Verification de l'installation</H2>";

    /*
 * Verification de l'import de l'arborescence de GVV
*
*/
    echo "<H3>Verification de l'arborescence</H3>";

    checkfile("../application", "Réimportez l'arborescence.<br>", "dossier");
    checkfile("../system", "Réimportez l'arborescence.<br>", "dossier");
    checkfile("../install", "Réimportez l'arborescence.<br>", "dossier");
    checkfile("../assets", "Réimportez l'arborescence.<br>", "dossier");

    /*
 * Verification de la structure de la base de donn�es
*/
    echo "<H3>Verification de la base de données</H3>";

    $configbase = "../application/config/database.php";
    $db_params = read_db_parameters($configbase);
    $serveur = $db_params['hostname'];
    $nom = $db_params['username'];
    $password = $db_params['password'];
    $base = $db_params['database'];
    /*
$serveur = "localhost";
$nom = "gvv_user";
$password = "lfoyfgbj";
$base = "gvv2";
*/

    function fatal_db() {
        global $serveur, $nom, $password, $base, $configbase;

        $msg = "<br/>";
        $msg .= "Connexion à la base de données impossible.<br/>";
        $msg .= "vos paramètres:</br>";
        $msg .= "serveur=$serveur</br>";
        $msg .= "nom=$nom</br>";
        $msg .= "mot de passe=$password</br>";
        $msg .= "base=$base</br></br>";
        $msg .= "adapter le fichier $configbase ou modifiez votre base de données";
        die($msg);
    }

    // Verification connexion BDD
    $db = mysqli_connect($serveur, $nom, $password, $base) or fatal_db();

    //Verification structure BDD
    $sql = "SHOW TABLES FROM $base";
    $req = mysqli_query($db, $sql) or die('erreur sql !<br>' . $sql . '<br>' . mysqli_error($db));

    echo "La connection à la base de données est correcte.<br>";

    echo '<br>Detection des Tables:</i><br>';
    $sql = "SET FOREIGN_KEY_CHECKS=0;";
    mysqli_query($db, $sql) or die('erreur sql !<br>' . $sql . '<br>' . mysql_error());

    while ($data = mysqli_fetch_row($req)) {
        echo '</i><br>';
        $sql = "drop table " . $data[0] . ";";
        echo "$sql";
        mysqli_query($db, $sql) or die('erreur sql !<br>' . $sql . '<br>' . mysql_error());
    }
    $sql = "SET FOREIGN_KEY_CHECKS=1;";
    mysqli_query($db, $sql) or die('erreur sql !<br>' . $sql . '<br>' . mysql_error());

    // function mysql_import($filename) {

    //     $file_content = file($filename);
    //     $query = "";
    //     foreach ($file_content as $sql_line) {
    //         if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
    //             $query .= $sql_line;
    //             if (substr(rtrim($query), -1) == ';') {
    //                 //	echo $query;
    //                 $result = mysql_query($query) or die(mysql_error());
    //                 $query = "";
    //             }
    //         }
    //     }
    // }

    mysqli_close($db);
    echo '<br/><br/>';

    /*
 * Suppression des images
*/
    echo "<H3>Suppression des images générées</H3>";
    foreach (glob("../assets/images/*.png") as $filename) {
        try {
            if (chmod($filename, 755)) {
            } else {
                // echo "changement de droits impossible sur $filename</br>";
            }
            if (unlink($filename)) {
                echo "$filename supprimé</br>" . "\n";
            } else {
                echo "suppression de $filename impossible</br>" . "\n";
            }
        } catch (Exception $e) {
            echo "Erreur pendant la suppression de $filename. Essayez de le détruire vous-même.</br>" . "\n";
        }
    }
    $dir = opendir("../assets/images");
    while (false !== ($entry = readdir($dir))) {
        echo "Fichier dans le répèrtoire assets/images: $entry</br>";
    }
    closedir($dir);

    $url = site_url() . "/install";

    echo 'Installation: <a href="' . $url . '">' . $url . '</a>';
    ?>