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
        $this->database->backup2($type);
    }

    /**
     * Sauvegarde des fichiers média
     */
    public function backup_media() {
        $this->load->helper('file');
        
        // Path to uploads directory (excluding restore subdirectory)
        $uploads_path = './uploads';
        
        // Check if uploads directory exists
        if (!is_dir($uploads_path)) {
            show_error('Le répertoire uploads n\'existe pas');
            return;
        }
        
        // Check if there are any files to backup (excluding restore directory)
        $files = glob($uploads_path . '/*');
        $has_content = false;
        foreach ($files as $file) {
            if (basename($file) !== 'restore') {
                $has_content = true;
                break;
            }
        }
        
        if (!$has_content) {
            show_error('Aucun fichier média à sauvegarder (le répertoire uploads est vide)');
            return;
        }
        
        // Get club name and create filename following same convention as database
        $nom_club = $this->config->item('nom_club');
        if (!$nom_club) {
            $nom_club = 'gvv_club'; // fallback
        }
        $clubid = 'gvv_' . strtolower(str_replace(' ', '_', $nom_club)) . '_media_';
        $dt = date("Y_m_d");
        
        // Properly handle accented characters by transliterating them to ASCII
        $safe_clubid = $this->transliterate_to_ascii($clubid);
        $filename = $safe_clubid . "$dt.tar.gz";

        $backupdir = getcwd() . "/backups/";
                
        // Ensure backups directory exists
        if (!is_dir($backupdir)) {
            mkdir($backupdir, 0755, true);
        }
        $filepath = $backupdir . '/' . $filename;
        
        // Create tar.gz archive excluding the restore subdirectory
        $command = "cd " . escapeshellarg($uploads_path) . " && tar --exclude='restore' -czf " . escapeshellarg($filepath) . " .";

        gvv_info("Backup media command: " . $command);
        exec($command, $output, $return_code);
        gvv_info("Backup media return code: " . $return_code . ", Output: " . implode("\n", $output));

        // if ($return_code == 0 && file_exists($full_backup_path)) {
        if ($return_code == 0) {
            // Load the download helper and send the file to browser
            $this->load->helper('download');
            $data = file_get_contents($filepath);
            force_download($filename, $data);
            
            // Clean up the temporary backup file after download
            unlink($filepath);
        } else {
            show_error('Erreur lors de la création de la sauvegarde des médias. Code de retour: ' . $return_code . 
                      '<br>Commande: ' . htmlspecialchars($command) . 
                      '<br>Sortie: ' . implode('<br>', $output));
        }
    }

    /**
     * Affiche la page de restauration
     *
     * A completer. Peut-être vaudrait-il mieux ne pas supporter
     * cela. C'est sûrement une faille de sécurité potentielle ???
     */
    public function restore() {
        $dir = getcwd() . '/backups/';
        $files = glob($dir . '*.{zip,sql.gz}', GLOB_BRACE); // get all file names
        $backups = array();
        foreach ($files as $file) {
            $name = basename($file);
            $url = base_url() . 'backups/' . $name;
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
     * Affiche la page de sauvegarde unifiée
     */
    public function backup_form() {
        load_last_view('admin/backup_form', array());
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

            // Les fichiers standards de backup sont des zip même sous Linux
            $this->unzip->extract($filename, $upload_path);

            // $sqlfile = str_replace('.zip', '.sql', $orig_name);
            $sqlfiles = glob($upload_path . '*.sql');
            $sqlfile = $sqlfiles[0];
            $sql = file_get_contents($sqlfile);

            // remove the uncompressed file
            unlink($sqlfile);
            // remove the zip file
            $this->unlink_zip($filename);

            // disable foreign key checks before restoring
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

            if ($erase_db) {
                $this->database->drop_all();
            }
            $this->database->sql($sql);
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

            load_last_view('admin/restore_success', $data);
        }
    }

    /**
     * Restaure les fichiers média
     */
    public function do_restore_media() {
        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path, 0755, true)) {
                die("Cannot create " . $upload_path);
            }
        }

        // delete all files in the uploads/restore directory
        $files = glob($upload_path . '*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'tar|gz|tgz'; // Allow tar, gz, and tgz files
        $config['max_size'] = '500000'; // 500MB for media files
        $config['file_ext_tolower'] = FALSE; // Don't force lowercase extension checking
        $config['remove_spaces'] = FALSE; // Don't remove spaces from filenames

        $this->load->library('upload', $config);

        $merge_media = $this->input->post('merge_media');

        if (! $this->upload->do_upload()) {
            $error = array(
                'error' => $this->upload->display_errors(),
                'merge_media' => 1
            );
            load_last_view('admin/restore_form', $error);
        } else {
            $data = $this->upload->data();
            $filename = $config['upload_path'] . $data['file_name'];
            
            // Custom validation: ensure we only accept archive files
            $file_ext = strtolower(pathinfo($data['orig_name'], PATHINFO_EXTENSION));
            $orig_name_lower = strtolower($data['orig_name']);
            
            // Check if it's a valid archive file
            $is_valid_archive = in_array($file_ext, ['tar', 'gz', 'tgz']) || 
                               strpos($orig_name_lower, '.tar.gz') !== false ||
                               strpos($orig_name_lower, '.tar') !== false;
            
            if (!$is_valid_archive) {
                // Remove uploaded file and show error
                unlink($filename);
                $error = array(
                    'error' => 'Seuls les fichiers d\'archive (.tar, .gz, .tgz, .tar.gz) sont autorisés pour la restauration des médias.',
                    'merge_media' => 1
                );
                load_last_view('admin/restore_form', $error);
                return;
            }
            
            // Handle different archive formats
            $file_ext = strtolower(pathinfo($data['orig_name'], PATHINFO_EXTENSION));
            $orig_name_lower = strtolower($data['orig_name']);
            
            if (in_array($file_ext, ['tar', 'gz', 'tgz']) || strpos($orig_name_lower, '.tar.gz') !== false) {
                // Extract tar.gz archive to uploads directory
                $uploads_path = './uploads/';
                
                if (!$merge_media) {
                    // If not merging, backup existing uploads first
                    $backup_existing = './uploads_backup_' . date('Y_m_d_H_i_s');
                    if (is_dir($uploads_path)) {
                        // Create backup of existing directory
                        $command_backup = "cp -r " . escapeshellarg(rtrim($uploads_path, '/')) . " " . escapeshellarg($backup_existing);
                        exec($command_backup, $backup_output, $backup_return);
                        
                        if ($backup_return === 0) {
                            // Remove existing content except restore directory
                            $existing_files = glob($uploads_path . '*');
                            foreach ($existing_files as $existing_file) {
                                if (basename($existing_file) !== 'restore') {
                                    if (is_dir($existing_file)) {
                                        $this->remove_directory($existing_file);
                                    } else {
                                        unlink($existing_file);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Ensure uploads directory exists
                if (!is_dir($uploads_path)) {
                    mkdir($uploads_path, 0755, true);
                }
                
                // Get absolute paths
                $abs_uploads_path = realpath($uploads_path);
                $abs_filename = realpath($filename);
                
                if (!$abs_uploads_path || !$abs_filename) {
                    show_error('Erreur lors de la résolution des chemins pour la restauration');
                    return;
                }
                
                // Extract archive with multiple fallback methods
                $success = false;
                $extraction_method = '';
                
                // Method 1: Try with full path to tar
                if (!$success) {
                    $tar_path = '/usr/bin/tar';
                    if (file_exists($tar_path)) {
                        if ($file_ext === 'gz' && strpos($orig_name_lower, '.tar.gz') !== false) {
                            $options = "-xzf";
                        } else if ($file_ext === 'tgz') {
                            $options = "-xzf";
                        } else {
                            $options = "-xf";
                        }
                        $command = "cd " . escapeshellarg($abs_uploads_path) . " && " 
                        . $tar_path . " --overwrite --no-same-owner --no-same-permissions $options " . escapeshellarg($abs_filename) . " 2>&1";

                        gvv_info("Method 1 - Full path tar command: " . $command);
                        exec($command, $output, $return_code);
                        gvv_info("Method 1 - Return code: " . $return_code . ", Output: " . implode("\n", $output));
                        
                        if ($return_code === 0) {
                            $success = true;
                            $extraction_method = 'full_path_tar';
                        }
                    }
                }
                
                // Method 2: Try without cd, using absolute paths
                if (!$success) {
                    if ($file_ext === 'gz' && strpos($orig_name_lower, '.tar.gz') !== false) {
                        $command = "tar --no-same-owner --no-same-permissions -xzf " . escapeshellarg($abs_filename) . " -C " . escapeshellarg($abs_uploads_path) . " 2>&1";
                    } else if ($file_ext === 'tgz') {
                        $command = "tar --no-same-owner --no-same-permissions -xzf " . escapeshellarg($abs_filename) . " -C " . escapeshellarg($abs_uploads_path) . " 2>&1";
                    } else {
                        $command = "tar --no-same-owner --no-same-permissions -xf " . escapeshellarg($abs_filename) . " -C " . escapeshellarg($abs_uploads_path) . " 2>&1";
                    }
                    
                    gvv_info("Method 2 - Direct extraction command: " . $command);
                    exec($command, $output2, $return_code2);
                    gvv_info("Method 2 - Return code: " . $return_code2 . ", Output: " . implode("\n", $output2));
                    
                    if ($return_code2 === 0) {
                        $success = true;
                        $extraction_method = 'direct_tar';
                        $return_code = $return_code2;
                        $output = $output2;
                    }
                }
                
                // Method 3: Try PharData (PHP built-in)
                if (!$success && (strpos($orig_name_lower, '.tar.gz') !== false || $file_ext === 'tgz')) {
                    gvv_info("Method 3 - Trying PharData extraction");
                    try {
                        $phar = new PharData($abs_filename);
                        $phar->extractTo($abs_uploads_path, null, true);
                        $success = true;
                        $extraction_method = 'phardata';
                        $return_code = 0;
                        gvv_info("Method 3 - PharData extraction successful");
                    } catch (Exception $e) {
                        gvv_error("Method 3 - PharData extraction failed: " . $e->getMessage());
                    }
                }
                
                gvv_info("Extraction result - Success: " . ($success ? 'YES' : 'NO') . ", Method: " . $extraction_method);
                
                // Clean up
                // unlink($filename);
                
                if ($success) {
                    $data['file_name'] = $data['orig_name'];
                    $data['restore_type'] = 'media';
                    $data['extraction_method'] = $extraction_method;
                    gvv_info("Media restoration successful using method: " . $extraction_method);
                    load_last_view('admin/restore_success', $data);
                } else {
                    $error_message = 'Erreur lors de la restauration des médias. ';
                    if (isset($return_code)) {
                        $error_message .= 'Code de retour: ' . $return_code . '. ';
                    }
                    if (!empty($output)) {
                        $error_message .= 'Détails: ' . implode("\n", $output);
                    }
                    gvv_error("Media restoration failed: " . $error_message);
                    show_error($error_message);
                }
            } else {
                show_error('Format de fichier non supporté pour la restauration des médias. Formats acceptés: .tar.gz, .tgz, .tar');
            }
        }
    }

    /**
     * Helper method to remove a directory recursively
     */
    private function remove_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->remove_directory($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
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
     * Analyse la structure de la base de données
     */
    function metadata() {
        $this->load->library('gvvmetadata');
        $this->gvvmetadata->dump();
    }

    /**
     * Transliterate accented characters to ASCII equivalents
     * @param string $text
     * @return string
     */
    private function transliterate_to_ascii($text) {
        // Define character mappings
        $transliterations = array(
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y', 'Ÿ' => 'Y',
            'Ç' => 'C', 'Ñ' => 'N'
        );
        
        // Apply transliterations
        $result = strtr($text, $transliterations);
        
        // Remove any remaining non-ASCII characters and replace with underscore
        $result = preg_replace('/[^\x20-\x7E]/', '_', $result);
        
        // Clean up quotes, control characters, and multiple underscores
        $result = preg_replace('/[\'\"\x00-\x1F\x7F-\x9F]+/', '_', $result);
        $result = preg_replace('/_+/', '_', $result);
        $result = trim($result, '_');
        
        return $result;
    }


}