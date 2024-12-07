<html>

<head>
	<meta http-equiv="Content-Language" content="en" />
	<meta name="GENERATOR" content="PHPEclipse 1.0" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Script d'installation de GVV</title>
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
* todo:
* cd workspace/
* vérifier que apache à le droit d'écrire dans images
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

	/*
 * Vérifie si un fichier est modifiable
 */
	function check_writable($filename, $comment = "", $fatal = FALSE) {
		chmod($filename, 0666);
		if (is_writable($filename)) {
			echo "$filename est modifiable.<br>";
		} else {
			echo "<br>$filename n'est pas modifiable.<br>$comment";
			if ($fatal) exit();
		}
	}

	/**
	 * Vérifie l'existence de plusieurs fichiers
	 */
	function check_all_files() {
		echo "Vérification de l'arborescence depuis " . getcwd() . "<br/>";
		checkfile("../application", "Réimportez l'arborescence.<br>", "dossier");
		checkfile("../system", "Réimportez l'arborescence.<br>", "dossier");
		checkfile("../install", "Réimportez l'arborescence.<br>", "dossier");

		// 	echo "Existence des polices à la racine du serveur: ";
		// 	$dir_list = split('/', getcwd());
		// 	array_pop($dir_list);
		// 	array_pop($dir_list);
		// 	$fonts_dir = join('/', $dir_list) . '/fonts'; 
		//     checkfile($fonts_dir, "recopiez celui de application/third_party/pChart<br>", "dossier");

		$fatal = FALSE;
		check_writable("../application/config/club.php", "Vous ne pourrez pas modifier la configuration du club<br>"
			. "Changez les droits sur le serveur<br>", $fatal);
		check_writable("../application/config/facturation.php", "Vous ne pourrez pas changer la configuration de la facturation<br>"
			. "Changez les droits sur le serveur<br>", $fatal);
		check_writable("../assets/images", "Vous ne pourrez pas charger d'images ni générer de diagrammes<br>"
			. "Changez les droits sur le serveur<br>", $fatal);
		check_writable("../uploads", "Vous ne pourrez pas changer le logo<br>"
			. "Changez les droits sur le serveur<br>", $fatal);
		check_writable("../uploads/restore", "Vous ne pourrez pas restaurer les sauvegardes<br>"
			. "Changez les droits sur le serveur<br>", $fatal);
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

	echo "<H1>Installation de GVV</H1><br>";

	echo "server user_id=" . get_current_user() . "<br/>";
	echo 'PHP is running as: ' . exec('whoami') . "<br/>";
	echo "current directory=" . getcwd() . "<br/>";


	echo "<H2>Verification des fichiers sur le serveur</H2>";

	/*
 * Verification de l'import de l'arborescence de GVV
*
*/
	echo "<H3>Verification de l'arborescence</H3>";
	check_all_files();

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

	/**
	 * 
	 * Affiche un message en cas d'erreur de base de données fatale
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
		$msg .= "modifiez le fichier $configbase ou configurez votre base de données MYSQL</br>";
		$msg .= "<br/>";
		$msg .= "Exemple sous Linux:<br/>";
		$msg .= "mysql -u root -pmot_de_passe" . "<br/>";
		$msg .= "show databases;" . "<br/>";

		$msg .= "create database $base;" . "<br/>";
		$msg .= "use $base;" . "<br/>";
		$msg .= "create user $nom@localhost identified by '$password';" . "<br/>";
		$msg .= "grant all on $base.* to $nom@localhost;" . "<br/>";
		$msg .= "SHOW GRANTS FOR $nom@localhost;" . "<br/>";

		die($msg);
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
		// unset($lst[2]);
		array_pop($lst);
		array_pop($lst);
		$script = join("/", $lst);
		$url = $protocol . '://' . $host . $script;
		return $url;
	}

	// Verification connexion BDD
	$db = mysqli_connect($serveur, $nom, $password, $base) or fatal_db();

	if (!mysqli_set_charset($db, "utf8")) {
		printf("Erreur lors du chargement du jeu de caractères utf8 : %s\n</br>", mysql_error());
	} else {
		printf("Jeu de caractères courant : %s\n</br>", "utf8");
	}


	//Verification structure BDD
	$sql = "SHOW TABLES FROM $base";
	$req = mysqli_query($db, $sql) or die('erreur sql !<br>' . $sql . '<br>' . mysqli_error($db));

	echo "La connection à la base de données est correcte.<br>";

	echo '<br>Detection des Tables:</i><br>';
	while ($data = mysqli_fetch_row($req)) {
		echo '</i><br>';
		echo $data[0];
	}

	function mysql_import($filename, $db) {

		$file_content = file($filename);
		$query = "";
		foreach ($file_content as $sql_line) {
			if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
				$query .= $sql_line;
				if (substr(rtrim($query), -1) == ';') {
					// echo $query . '<br/>';
					$result = mysqli_query($db, $query)
						or die(mysqli_error($db));
					$query = "";
				}
			}
		}
	}

	if (mysqli_num_rows($req) < 22) {
		echo '<br>Structure inexistante';
		echo '<br>Création des tables de la base de données<br>';

		mysql_import("./gvv_structure.sql", $db);
		echo '<br>Initialisation des valeurs par défaut';
		mysql_import("./gvv_defaut.sql", $db);
		echo '<br>La base de données à été créée';
	} else {
		echo "<br><br>Les tables de la base existent déjà, aucune action effectuée.<br>";
	}

	//Verification utilisateurs
	$requete1 = ("SELECT * FROM users");
	$resultat = mysqli_query($db, $requete1);


	echo '<br><br>Detection des Utilisateurs:</i><br><br>';

	while ($data = $resultat->fetch_assoc()) {
		// on affiche les informations de l'enregistrement en cours
		echo '  ' . $data['username'] . ' </i><br>';
	}


	if (mysqli_num_rows($resultat) < 1) {
		echo '<br>Aucun utilisateurs detectés</i>';
		echo '<br>Création d utilisateurs: </i><br>';
		mysql_import("./initial_users.sql", $db);
		echo "<br>Après installation, vous pouvez vous connecter en utilisant
   	     testadmin/password ou testuser/password<br>";
	} else {
		echo "<br>Les utilisateurs existent déjà, rien à faire.<br/>";
	}

	//Verification sécurité
	if (mysqli_num_rows($resultat) > 2) {
	}

	// Import éventuel de base de test
	// http://localhost/gvv2/install/?db=dusk_tests.sql
	if (array_key_exists('db', $_GET)) {
		echo "<br>Import de la base " . $_GET['db'] . "<br/>";
		mysql_import("./" . $_GET['db'], $db);
	}

	mysqli_close($db);

	echo "le mot de passe de testuser est password.<br/>";
	echo "le mot de passe de testadmin est password.<br/>";
	echo "N'oubliez pas de les changer<br/>";

	echo '<br/>';
	$url = site_url();
	echo 'vous vouvez maintenant utiliser GVV: <a href="' . $url . '">' . $url . '</a>';

	echo "<br><br>Fin de la procédure d'installation<br>";
	?>