<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Administration du site
 *
 * @filesource admin.php
 * @package controllers
 *
 */
class Admin extends CI_Controller {
    protected $controller = "admin";
    protected $unit_test = FALSE;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Check if user is logged in or not
        $this->dx_auth->check_login();

        $this->load->library('Database');
        $this->load->helper('file');
    }

    /**
     * Sauvegarde de la base de données
     *
     * @param
     *            string type structure | "" = sauvegarde complète
     */
    public function backup($type = "") {
        $this->database->backup($type);
    }

    /**
     * Restauration de la base de données
     *
     * A completer. Peut-etre vaudrait-il mieux ne pas supporter
     * cela. C'est surement une faille de sécurité potentielle ???
     */
    public function restore() {
        $dir = getcwd() . '/backups/';
        $files = glob($dir . '*.{zip,sql.gz}', GLOB_BRACE); // get all file names
        $backups = array();
        foreach ($files as $file) {
            $name = basename($file);
            $url = base_url() . '/backups/' . $name;
            $anchor = anchor($url, $name);
            $backups[] = $anchor;
        }

        $error = array(
            'error' => '',
            'erase_db' => 1
        );
        if (count($files)) {
            $error['backups'] = $backups;
        }
        load_last_view('admin/restore_form', $error);
    }

    /**
     * Sous Windows, je n'arrive pas à supprimer le fichier zip .
     * ..
     *
     * @param unknown_type $zipfile
     */
    private function unlink_zip($zipfile) {
        $sPath = $zipfile;
        $aFilePath = explode("/", $sPath);
        $i = 0;
        $sLastFolder = "";
        foreach ($aFilePath as $sFolder) {
            $i++;
            if (file_exists($sLastFolder . $sFolder) || is_dir($sLastFolder . $sFolder)) {
                // chmod ($sLastFolder . $sFolder, 0777);
                $iOldumask = umask(0); // important part #1
                // chmod($sLastFolder . $sFolder, 0777);
                umask($iOldumask); // important part #2
                $sLastFolder .= $sFolder . "/";
            }
        }
        // Todo check why the following line triggers an error
        // unlink($sPath);
    }

    /**
     * Restaure la base
     * TODO utiliser le helper database
     */
    public function do_restore() {
        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path)) {
                die("Cannot create " . $upload_path);
            }
        }

        // delete all files in the uploads/restore directory
        $files = glob($upload_path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'zip|gz';
        $config['max_size'] = '1500';

        $this->load->library('upload', $config);

        $erase_db = $this->input->post('erase_db');

        if (! $this->upload->do_upload()) {
            // On a pas réussi à recharger la sauvegarde
            $error = array(
                'error' => $this->upload->display_errors(),
                'erase_db' => 1
            );
            load_last_view('admin/restore_form', $error);
        } else {

            // on a rechargé la sauvegarde
            $data = $this->upload->data();

            $this->load->library('unzip');
            $filename = $config['upload_path'] . $data['file_name'];
            // echo $filename . br();
            $orig_name = $config['upload_path'] . $data['orig_name'];
            // echo $orig_name . br();

            // TODO: support pour les fichiers *.sql.tz
            $this->unzip->extract($filename, $upload_path);

            // $sqlfile = str_replace('.zip', '.sql', $orig_name);
            $sqlfiles = glob($upload_path . '*.sql');
            $sqlfile = $sqlfiles[0];
            $sql = file_get_contents($sqlfile);

            // remove the uncompressed file
            unlink($sqlfile);
            // remove the zip file
            $this->unlink_zip($filename);

            if ($erase_db) {
                $this->database->drop_all();
            }
            $this->database->sql($sql);

            load_last_view('admin/restore_success', $data);
        }
    }

    /**
     * Restauration de la base de données
     *
     * A completer. Peut-etre vaudrait-il mieux ne pas supporter
     * cela. C'est surement une faille de sécurité potentielle ???
     */
    public function page() {
        return load_last_view('admin/admin', array(), $this->unit_test);
    }

    /**
     * Just display phpinfo
     */
    public function info() {
        echo phpinfo();
    }

    /**
     * Test unitaire
     */
    function test() {
        $this->unit_test = TRUE;
        echo heading("Test controller " . $this->controller, 3);

        $this->load->library('unit_test');

        $res = $this->page();
        echo $this->unit->run(($res == ""), FALSE, $this->controller . "/page", "non vide");
        echo $this->unit->run(preg_match("/PHP Error/", $res), 0, "membre/page2", "pas d'erreurs PHP");

        echo anchor(controller_url("tests"), "Tests unitaires");
    }

    function spy() {
        echo "spy:" . br();
        foreach ($_POST as $key => $value) {
            echo "POST[$key] = $value" . br();
        }
        foreach ($_GET as $key => $value) {
            echo "GET[$key] = $value" . br();
        }
        foreach ($_ENV as $key => $value) {
            echo "ENV[$key] = $value" . br();
        }
        var_dump($_REQUEST);
        echo "bye" . br();
    }

    /**
     * Analyse la structure de la mbase de données
     */
    function metadata() {
        $this->load->library('gvvmetadata');
        $this->gvvmetadata->dump();
    }
}
