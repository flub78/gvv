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
        // Get encryption parameters from POST
        $encrypt = $this->input->post('encrypt_backup');
        $passphrase = $this->input->post('passphrase');

        // If type is passed via POST (from form), use it
        $post_type = $this->input->post('type');
        if ($post_type !== false && $post_type !== null) {
            $type = $post_type;
        }

        $this->database->backup2($type, $encrypt, $passphrase);
    }

    /**
     * Sauvegarde des fichiers média
     */
    public function backup_media() {
        $this->load->helper('file');
        $this->load->helper('crypto');
        
        // Get encryption parameters from POST
        $encrypt = $this->input->post('encrypt_backup');
        $passphrase = $this->input->post('passphrase');
        
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
        
        // Create tar.gz archive excluding restore subdirectory and backup copies
        $command = "cd " . escapeshellarg($uploads_path) . " && tar --exclude='restore' --exclude='attachments_backup' --exclude='*.tmp' --exclude='*.bak' -czf " . escapeshellarg($filepath) . " .";

        gvv_info("Backup media command: " . $command);
        exec($command, $output, $return_code);
        gvv_info("Backup media return code: " . $return_code . ", Output: " . implode("\n", $output));

        if ($return_code == 0) {
            // If encryption is requested, encrypt the file
            if ($encrypt) {
                $encrypted_filename = get_encrypted_filename($filename);
                $encrypted_filepath = $backupdir . '/' . $encrypted_filename;
                
                gvv_info("Encrypting backup: $filepath to $encrypted_filepath");
                
                if (encrypt_file($filepath, $encrypted_filepath, $passphrase)) {
                    // Remove unencrypted file
                    unlink($filepath);
                    
                    // Update filepath and filename to encrypted version
                    $filepath = $encrypted_filepath;
                    $filename = $encrypted_filename;
                    gvv_info("Backup encrypted successfully");
                } else {
                    show_error('Erreur lors du chiffrement de la sauvegarde');
                    return;
                }
            }
            
            // Memory-efficient streaming download instead of loading entire file into memory
            $this->stream_file_download($filepath, $filename);
            
            // Clean up the temporary backup file after download
            unlink($filepath);
        } else {
            show_error('Erreur lors de la création de la sauvegarde des médias. Code de retour: ' . $return_code . 
                      '<br>Commande: ' . htmlspecialchars($command) . 
                      '<br>Sortie: ' . implode('<br>', $output));
        }
    }

    /**
     * Memory-efficient file streaming for large downloads
     * Streams file in chunks instead of loading entire file into memory
     */
    private function stream_file_download($filepath, $filename) {
        if (!file_exists($filepath)) {
            show_error('Le fichier de sauvegarde n\'existe pas');
            return;
        }

        $filesize = filesize($filepath);
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Disable output buffering and clean any existing buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Open file for reading
        $file = fopen($filepath, 'rb');
        if ($file === false) {
            show_error('Impossible d\'ouvrir le fichier de sauvegarde');
            return;
        }
        
        // Stream file in 8MB chunks to avoid memory issues
        $chunk_size = 8 * 1024 * 1024; // 8MB chunks
        while (!feof($file)) {
            $chunk = fread($file, $chunk_size);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            
            // Flush output to ensure chunks are sent immediately
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
        
        fclose($file);
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
        $this->load->helper('crypto');
        
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

        // Get passphrase from POST
        $passphrase = $this->input->post('passphrase');

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = '*'; // Allow all types, we'll validate manually
        $config['max_size'] = '1500';
        $config['file_ext_tolower'] = FALSE; // Don't force lowercase for .enc.zip detection

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

            // Manual validation: ensure we only accept database backup files
            $orig_name_lower = strtolower($data['orig_name']);
            // Accept .zip, .enc.zip but NOT .tar.gz (media backups)
            $is_valid_backup = preg_match('/\.(enc\.)?(zip|sql\.gz)$/i', $orig_name_lower) ||
                              (preg_match('/\.gz$/i', $orig_name_lower) && !preg_match('/\.tar\.gz$/i', $orig_name_lower));

            if (!$is_valid_backup) {
                // Remove uploaded file and show error
                $filename = $config['upload_path'] . $data['file_name'];
                unlink($filename);
                $error = array(
                    'error' => 'Seuls les fichiers de sauvegarde (.zip, .gz, .enc.zip, .enc.gz) sont autorisés.',
                    'erase_db' => 1
                );
                load_last_view('admin/restore_form', $error);
                return;
            }

            $this->load->library('unzip');
            $filename = $config['upload_path'] . $data['file_name'];
            $orig_name = $config['upload_path'] . $data['orig_name'];

            // Check if file is encrypted
            if (is_encrypted_backup($data['file_name'])) {
                gvv_info("do_restore: Encrypted backup detected: " . $data['file_name']);
                
                // Decrypt the file
                $decrypted_filename = $upload_path . get_decrypted_filename($data['file_name']);
                
                if (decrypt_file($filename, $decrypted_filename, $passphrase)) {
                    gvv_info("do_restore: Successfully decrypted to $decrypted_filename");
                    
                    // Remove encrypted file and use decrypted one
                    unlink($filename);
                    $filename = $decrypted_filename;
                    $orig_name = $upload_path . get_decrypted_filename($data['orig_name']);
                } else {
                    $error = array(
                        'error' => 'Erreur lors du déchiffrement de la sauvegarde. Vérifiez la passphrase.',
                        'erase_db' => 1
                    );
                    load_last_view('admin/restore_form', $error);
                    return;
                }
            }

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
        $this->load->helper('crypto');

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
        $config['allowed_types'] = '*'; // Allow all file types initially, we'll validate manually
        $config['max_size'] = '0'; // No size limit initially, we'll check server limits
        $config['file_ext_tolower'] = FALSE; // Don't force lowercase extension checking
        $config['remove_spaces'] = FALSE; // Don't remove spaces from filenames
        $config['overwrite'] = TRUE; // Allow overwriting files

        $this->load->library('upload', $config);

        $merge_media = $this->input->post('merge_media');
        $passphrase = $this->input->post('passphrase');

        // Debug: Log what we receive
        gvv_info("Media restore - POST data: " . print_r($_POST, true));
        gvv_info("Media restore - FILES data: " . print_r($_FILES, true));
        
        // Debug: Log server upload limits
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $memory_limit = ini_get('memory_limit');
        $max_execution = ini_get('max_execution_time');
        gvv_info("Server limits - upload_max_filesize: $upload_max, post_max_size: $post_max, memory_limit: $memory_limit, max_execution_time: $max_execution");

        // Check if both POST and FILES are empty - this indicates the form submission was rejected due to size limits
        if (empty($_POST) && empty($_FILES)) {
            $uploads_path = realpath('./uploads');
            $error = array(
                'error' => 'Le fichier sélectionné dépasse probablement la taille maximum autorisée par le serveur (' . $upload_max . ').<br>' .
                          'Limites actuelles du serveur:<br>' .
                          '- Taille maximum par fichier: ' . $upload_max . '<br>' .
                          '- Taille maximum des données POST: ' . $post_max . '<br><br>' .
                          '<strong>Solutions alternatives:</strong><br>' .
                          '1. Contacter l\'administrateur pour augmenter les limites du serveur<br>' .
                          '2. Utiliser un fichier plus petit<br>' .
                          '3. <strong>Extraction manuelle via SSH:</strong><br>' .
                          '<code>cd ' . $uploads_path . '<br>' .
                          'tar -xzf /chemin/vers/votre/fichier.tar.gz</code><br>' .
                          'Puis ajuster les permissions si nécessaire.',
                'merge_media' => 1
            );
            load_last_view('admin/restore_form', $error);
            return;
        }

        // Check if file was actually uploaded
        if (empty($_FILES['userfile']['name']) || $_FILES['userfile']['error'] != UPLOAD_ERR_OK) {
            $upload_error = '';
            if (!empty($_FILES['userfile']['error'])) {
                switch($_FILES['userfile']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $uploads_path = realpath('./uploads');
                        $upload_error = 'Le fichier dépasse la taille maximum autorisée par le serveur (' . ini_get('upload_max_filesize') . ').<br>' .
                                       '<strong>Solution alternative:</strong> Extraction manuelle via SSH:<br>' .
                                       '<code>cd ' . $uploads_path . '<br>' .
                                       'tar -xzf /chemin/vers/votre/fichier.tar.gz</code>';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $uploads_path = realpath('./uploads');
                        $upload_error = 'Le fichier dépasse la taille maximum autorisée par le formulaire.<br>' .
                                       '<strong>Solution alternative:</strong> Extraction manuelle via SSH:<br>' .
                                       '<code>cd ' . $uploads_path . '<br>' .
                                       'tar -xzf /chemin/vers/votre/fichier.tar.gz</code>';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $upload_error = 'Le fichier n\'a été que partiellement téléchargé.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $upload_error = 'Aucun fichier n\'a été sélectionné.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $upload_error = 'Répertoire temporaire manquant sur le serveur.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $upload_error = 'Échec de l\'écriture du fichier sur le disque.';
                        break;
                    default:
                        $upload_error = 'Erreur inconnue lors du téléchargement.';
                }
            } else {
                $upload_error = 'Vous n\'avez pas sélectionné de fichier à envoyer.';
            }
            
            $error = array(
                'error' => $upload_error,
                'merge_media' => 1
            );
            load_last_view('admin/restore_form', $error);
            return;
        }

        if (! $this->upload->do_upload()) {
            $error = array(
                'error' => $this->upload->display_errors(),
                'merge_media' => 1
            );
            load_last_view('admin/restore_form', $error);
        } else {
            $data = $this->upload->data();
            $filename = $config['upload_path'] . $data['file_name'];

            // Check if file is encrypted and decrypt if necessary
            if (is_encrypted_backup($data['file_name'])) {
                gvv_info("do_restore_media: Encrypted backup detected: " . $data['file_name']);

                // Decrypt the file
                $decrypted_filename = $upload_path . get_decrypted_filename($data['file_name']);

                if (decrypt_file($filename, $decrypted_filename, $passphrase)) {
                    gvv_info("do_restore_media: Successfully decrypted to $decrypted_filename");

                    // Remove encrypted file and use decrypted one
                    unlink($filename);
                    $filename = $decrypted_filename;

                    // Update data with decrypted filename
                    $data['file_name'] = basename($decrypted_filename);
                    $data['orig_name'] = get_decrypted_filename($data['orig_name']);
                } else {
                    $error = array(
                        'error' => 'Erreur lors du déchiffrement de la sauvegarde des médias. Vérifiez la passphrase.',
                        'merge_media' => 1
                    );
                    load_last_view('admin/restore_form', $error);
                    return;
                }
            }

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
        // Check if user is authorized for development/test features
        // Authorized user: fpeignot only
        $data = array(
            'is_dev_authorized' => ($this->dx_auth->get_username() === 'fpeignot')
        );
        return load_last_view('admin/admin', $data, $this->unit_test);
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

    /**
     * Anonymize all data - calls all anonymization routines
     * Only callable by authorized user (fpeignot)
     *
     * @param bool $with_number If true, use numbered anonymization (fast), otherwise use natural data (default)
     * @return void
     */
    public function anonymize_all_data() {
        // Check if user is authorized (fpeignot only)
        if ($this->dx_auth->get_username() !== 'fpeignot') {
            show_error('Cette fonction est réservée aux administrateurs autorisés', 403, 'Accès refusé');
            return;
        }

        // Check if numbered mode is requested via GET parameter
        $with_number = $this->input->get('with_number') == '1';

        $results = array();
        $total_updated = 0;
        $all_errors = array();

        // Call backend/users anonymization
        log_message('info', 'Starting global anonymization process');

        // Anonymize membres (extracted from membre/anonymize_all)
        log_message('info', 'Anonymizing membres data');
        $membres_updated = $this->_anonymize_membres($with_number);
        $results['membres'] = array(
            'routine' => 'Members data anonymization',
            'updated' => $membres_updated,
            'total' => $membres_updated
        );
        $total_updated += $membres_updated;

        // Anonymize users emails
        $users_updated = $this->_anonymize_users();
        $results['users'] = array(
            'routine' => 'Users email anonymization',
            'updated' => $users_updated,
            'total' => $users_updated
        );
        $total_updated += $users_updated;

        // Anonymize vols_decouverte (extracted from vols_decouverte/anonymize_all)
        log_message('info', 'Anonymizing discovery flights data');
        $vd_updated = $this->_anonymize_vols_decouverte($with_number);
        $results['vols_decouverte'] = array(
            'routine' => 'Discovery flights anonymization',
            'updated' => $vd_updated,
            'total' => $vd_updated
        );
        $total_updated += $vd_updated;

        log_message('info', "Global anonymization completed: $total_updated records updated");

        // Prepare view data
        $data = array(
            'title' => 'Anonymisation globale des données',
            'results' => $results,
            'total_updated' => $total_updated,
            'errors' => $all_errors,
            'message' => "Anonymisation globale terminée: $total_updated enregistrements mis à jour"
        );

        // Load view to display results
        load_last_view('admin/anonymization_results', $data);
    }

    /**
     * Helper method to anonymize users emails
     * Synchronizes user emails with corresponding membre emails
     *
     * @return int Number of records updated
     */
    private function _anonymize_users() {
        $users = $this->db->get('users')->result_array();
        $count = 0;

        foreach ($users as $user) {
            // Find corresponding membre by username (users.username = membres.mlogin)
            $membre = $this->db->where('mlogin', $user['username'])->get('membres')->row_array();

            $new_email = '';
            if ($membre && !empty($membre['memail'])) {
                // Use membre email if available
                $new_email = $membre['memail'];
            } else {
                // Generate random email if no membre or no email
                $random_string = substr(md5(uniqid($user['username'], true)), 0, 10);
                $new_email = $user['username'] . '_' . $random_string . '@example.com';
                log_message('info', "Anonymization: Generated random email for user {$user['username']}: {$new_email}");
            }

            // Update user email
            $this->db->where('id', $user['id']);
            $this->db->update('users', array('email' => $new_email));
            $count++;
            log_message('debug', "Anonymization: Updated user {$user['username']} email to {$new_email}");
        }

        return $count;
    }

    /**
     * Helper method to anonymize membres data
     * Simplified version of membre/anonymize_all logic
     *
     * @param bool $with_number If true, use numbered data, otherwise use natural-looking data
     * @return int Number of records updated
     */
    private function _anonymize_membres($with_number = false) {
        $this->load->model('membres_model');
        $this->load->model('comptes_model');

        // Natural data lists for realistic anonymization (expanded to 300 surnames for maximum variety)
        $noms = array(
            'Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy',
            'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent',
            'Fournier', 'Morel', 'Girard', 'Andre', 'Lefevre', 'Mercier', 'Dupuis', 'Lambert', 'Bonnet', 'Francois',
            'Martinez', 'Legrand', 'Garnier', 'Faure', 'Rousseau', 'Blanc', 'Guerin', 'Muller', 'Henry', 'Roussel',
            'Nicolas', 'Perrin', 'Morin', 'Mathieu', 'Clement', 'Gauthier', 'Dumont', 'Lopez', 'Fontaine', 'Chevalier',
            'Robin', 'Masson', 'Sanchez', 'Gerard', 'Nguyen', 'Boyer', 'Denis', 'Lemaire', 'Duval', 'Joly',
            'Gautier', 'Roger', 'Roche', 'Roy', 'Noel', 'Meyer', 'Lucas', 'Meunier', 'Jean', 'Perez',
            'Marchand', 'Dufour', 'Blanchard', 'Marie', 'Barbier', 'Brun', 'Dumas', 'Brunet', 'Schmitt', 'Leroux',
            'Colin', 'Fernandez', 'Renard', 'Arnaud', 'Rolland', 'Caron', 'Giraud', 'Lacroix', 'Riviere', 'Benoit',
            'Leclerc', 'Payet', 'Olivier', 'Guillot', 'Bourgeois', 'Hubert', 'Berger', 'Carpentier', 'Vasseur', 'Louis',
            'Menard', 'Rey', 'Picard', 'Leclercq', 'Gaillard', 'Philippe', 'Le Gall', 'Paris', 'Girard', 'Barre',
            'Pierre', 'Renaud', 'Aubert', 'Schneider', 'Bertrand', 'Fabre', 'Vidal', 'Moulin', 'Delaunay', 'Breton',
            'Maillard', 'Lemoine', 'Remy', 'Marchal', 'Roussel', 'Dumont', 'Carre', 'Voisin', 'Pelletier', 'Cohen',
            'Lecomte', 'Fleury', 'Gros', 'Collet', 'Pages', 'Godard', 'Langlois', 'Gay', 'Charpentier', 'Boulanger',
            'Prevost', 'Perrot', 'Bailly', 'Lejeune', 'Etienne', 'Weber', 'Reynaud', 'Lefebvre', 'Baron', 'Roux',
            'Legrand', 'Rossi', 'Guillaume', 'Nguyen', 'Da Silva', 'Santos', 'Fernandes', 'Rodrigues', 'Pereira', 'Alves',
            'Ribeiro', 'Carvalho', 'Gomes', 'Martins', 'Ferreira', 'Costa', 'Oliveira', 'Souza', 'Lima', 'Silva',
            'Deschamps', 'Charrier', 'Marechal', 'Jacob', 'Leveque', 'Poirier', 'Boucher', 'Chevallier', 'Germain', 'Lebrun',
            'Levy', 'Besnard', 'Pasquier', 'Georges', 'Adam', 'Mallet', 'Guibert', 'Tanguy', 'Guyot', 'Marty',
            'Fischer', 'Toussaint', 'Rousseau', 'Bertin', 'Grondin', 'Monnier', 'Collin', 'Courtois', 'Maury', 'Klein',
            'Lefort', 'Launay', 'Jacquet', 'Coulon', 'Humbert', 'Tessier', 'Reynaud', 'Wagner', 'Dijoux', 'Hoarau',
            'Olivier', 'Aubry', 'Pruvost', 'Lacombe', 'Poulain', 'Bigot', 'Dupuis', 'Collet', 'Maillard', 'Salmon',
            'Bouvier', 'Bouchet', 'Lombard', 'Marques', 'Neveu', 'Gilbert', 'Leduc', 'Remy', 'Bonnet', 'Marin',
            'Germain', 'Lopes', 'Delorme', 'Texier', 'Leblanc', 'Carlier', 'Royer', 'Antoine', 'Barthelemy', 'Dos Santos',
            'Guillou', 'Berthier', 'Millet', 'Benard', 'Morvan', 'Charles', 'Lelievre', 'Mahe', 'Mounier', 'Vaillant',
            'Tessier', 'Alonso', 'Laroche', 'Guilbert', 'Picard', 'Leroux', 'Valentin', 'Lebreton', 'Bruneau', 'Cousin',
            'Guilloux', 'Masse', 'Boulay', 'Parent', 'Gregoire', 'Laine', 'Alexandre', 'Bernier', 'Lebeau', 'Cordier',
            'Hamon', 'Barriere', 'Raymond', 'Barbier', 'Bonneau', 'Leroy', 'Blondel', 'Buisson', 'Lejeune', 'Vallet',
            'Meunier', 'Letellier', 'Jacques', 'Martineau', 'Bonnin', 'Guillon', 'Guerin', 'Camus', 'Pichon', 'Reynaud',
            'Coste', 'Leclerc', 'Godard', 'Colas', 'Pons', 'Charron', 'Rocher', 'Boutin', 'Gay', 'Vallet'
        );
        $prenoms = array(
            'Jean', 'Marie', 'Pierre', 'Michel', 'Andre', 'Philippe', 'Alain', 'Jacques', 'Bernard', 'Christophe',
            'Claude', 'Patrick', 'Francois', 'Daniel', 'Marc', 'Paul', 'Nicolas', 'Laurent', 'Thierry', 'Christian',
            'Olivier', 'Sebastien', 'Eric', 'Pascal', 'Antoine', 'Vincent', 'Julien', 'David', 'Alexandre', 'Stephane',
            'Gerard', 'Frederic', 'Guillaume', 'Rene', 'Henri', 'Bruno', 'Denis', 'Didier', 'Yves', 'Serge',
            'Matthieu', 'Fabrice', 'Benoit', 'Charles', 'Jerome', 'Franck', 'Thomas', 'Dominique', 'Emmanuel', 'Gilles',
            'Ludovic', 'Maxime', 'Cedric', 'Benjamin', 'Lucas', 'Sylvain', 'Damien', 'Arnaud', 'Valentin', 'Hugo',
            'Louis', 'Arthur', 'Gabriel', 'Jules', 'Raphael', 'Felix', 'Oscar', 'Simon', 'Baptiste', 'Nathan',
            'Fabien', 'Xavier', 'Loic', 'Florian', 'Quentin', 'Clement', 'Alexis', 'Kevin', 'Jeremy', 'Jonathan',
            'Romain', 'Adrien', 'Mickael', 'Anthony', 'Cyril', 'Nicolas', 'Samuel', 'Mathieu', 'Vincent', 'Yannick',
            'Christophe', 'Laurent', 'Sebastien', 'Olivier', 'Julien', 'Cedric', 'Gregory', 'Francois', 'Pierre', 'Marc'
        );
        $rues = array(
            'de la République', 'Victor Hugo', 'de la Liberté', 'Jean Jaurès', 'du Général de Gaulle', 'de la Gare',
            'du 8 Mai 1945', 'des Écoles', 'de l\'Église', 'du Stade', 'de la Poste', 'des Tilleuls', 'du Moulin',
            'de la Mairie', 'du Commerce', 'de la Paix', 'Pasteur', 'Gambetta', 'Carnot', 'du 11 Novembre',
            'Nationale', 'de Verdun', 'de la Fontaine', 'des Lilas', 'du Maréchal Foch', 'de la Résistance', 'Foch',
            'des Roses', 'du Château', 'de Paris', 'de Lyon', 'Aristide Briand', 'Jules Ferry', 'Georges Clemenceau',
            'de Strasbourg', 'de Bretagne', 'du Pont', 'de la Plage', 'de la Mer', 'du Port', 'de la Vallée',
            'de la Montagne', 'du Général Leclerc', 'Maréchal Joffre', 'du Docteur Schweitzer', 'de la Forêt',
            'des Champs', 'des Prés', 'des Vignes', 'du Parc', 'des Jardins', 'de la Source', 'du Ruisseau',
            'Saint-Martin', 'Sainte-Anne', 'Saint-Jacques', 'Notre-Dame', 'de la Croix', 'du Calvaire', 'de l\'Abbaye',
            'des Artisans', 'des Commerçants', 'de l\'Industrie', 'du Travail', 'de la Fraternité', 'de l\'Égalité',
            'Lafayette', 'Mirabeau', 'Danton', 'Robespierre', 'Molière', 'Racine', 'Corneille', 'Voltaire', 'Rousseau',
            'Diderot', 'Montesquieu', 'Balzac', 'Zola', 'Flaubert', 'Maupassant', 'Baudelaire', 'Verlaine', 'Rimbaud',
            'des Acacias', 'des Érables', 'des Chênes', 'des Platanes', 'des Peupliers', 'des Saules', 'des Ormes',
            'du Marché', 'de la Halle', 'de la Place', 'du Centre', 'Principale', 'de la Fontaine', 'du Lavoir'
        );
        $villes = array(
            'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille',
            'Rennes', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon', 'Grenoble', 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne',
            'Saint-Denis', 'Le Mans', 'Aix-en-Provence', 'Clermont-Ferrand', 'Brest', 'Tours', 'Amiens', 'Limoges', 'Annecy',
            'Perpignan', 'Boulogne-Billancourt', 'Metz', 'Besançon', 'Orléans', 'Mulhouse', 'Rouen', 'Saint-Paul', 'Caen',
            'Argenteuil', 'Montreuil', 'Nancy', 'Roubaix', 'Tourcoing', 'Nanterre', 'Vitry-sur-Seine', 'Avignon', 'Créteil',
            'Poitiers', 'Dunkerque', 'Aubervilliers', 'Asnières-sur-Seine', 'Courbevoie', 'Versailles', 'Colombes', 'Aulnay-sous-Bois',
            'Saint-Pierre', 'Rueil-Malmaison', 'Pau', 'Champigny-sur-Marne', 'Antibes', 'La Rochelle', 'Cannes', 'Calais',
            'Béziers', 'Colmar', 'Bourges', 'Saint-Nazaire', 'Valence', 'Ajaccio', 'Issy-les-Moulineaux', 'Levallois-Perret',
            'Quimper', 'Troyes', 'Neuilly-sur-Seine', 'Antony', 'Sarcelles', 'Cergy', 'Niort', 'Chambéry', 'Lorient',
            'Saint-Quentin', 'Beauvais', 'Ivry-sur-Seine', 'Clichy', 'Cholet', 'Montauban', 'Laval', 'Pantin', 'Épinay-sur-Seine',
            'Maisons-Alfort', 'Châteauroux', 'Chelles', 'Évry', 'Sartrouville', 'Hyères', 'Fontenay-sous-Bois', 'Arles', 'La Seyne-sur-Mer',
            'Bayonne', 'Drancy', 'Sevran', 'Albi', 'Vincennes', 'Charleville-Mézières', 'Saint-Malo', 'Corbeil-Essonnes'
        );

        // Get all members with their trigramme
        $membres = $this->db->select('mlogin, trigramme')->from('membres')->get()->result_array();
        $count = 0;

        foreach ($membres as $membre) {
            $mlogin = $membre['mlogin'];
            $has_trigramme = !empty($membre['trigramme']);

            if ($with_number) {
                // Numbered mode (fast)
                $nom = 'Nom' . mt_rand(1000, 9999);
                $prenom = 'Prenom' . mt_rand(1000, 9999);

                $random_data = array(
                    'mnom' => $nom,
                    'mprenom' => $prenom,
                    'memail' => 'membre' . mt_rand(1000, 9999) . '@example.com',
                    'madresse' => mt_rand(1, 999) . ' Rue Test',
                    'ville' => 'Ville' . mt_rand(100, 999),
                    'cp' => mt_rand(10000, 99999),
                    'mtelf' => '0612345' . sprintf('%03d', mt_rand(0, 999)),
                    'mtelm' => '0698765' . sprintf('%03d', mt_rand(0, 999))
                );
            } else {
                // Natural mode (realistic)
                $nom = $noms[array_rand($noms)];
                $prenom = $prenoms[array_rand($prenoms)];
                $rue = $rues[array_rand($rues)];
                $ville = $villes[array_rand($villes)];

                $random_data = array(
                    'mnom' => $nom,
                    'mprenom' => $prenom,
                    'memail' => strtolower($prenom . '.' . $nom) . mt_rand(1, 99) . '@example.com',
                    'madresse' => mt_rand(1, 150) . ' rue ' . $rue,
                    'ville' => $ville,
                    'cp' => mt_rand(10000, 99999),
                    'mtelf' => '01' . sprintf('%02d', mt_rand(20, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)),
                    'mtelm' => '06' . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99))
                );
            }

            // Generate trigramme from anonymized name if member had one
            if ($has_trigramme) {
                // Generate trigramme: First letter of prenom + first two letters of nom (uppercase)
                $trigramme = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 2));
                $random_data['trigramme'] = $trigramme;
            }

            // Update membre using direct DB update
            $this->db->where('mlogin', $mlogin);
            $this->db->update('membres', $random_data);

            // Update compte if membre has one (pilote column links to membres.mlogin)
            $this->db->where('pilote', $mlogin);
            $this->db->update('comptes', array(
                'nom' => $random_data['mnom'] . ' ' . $random_data['mprenom']
            ));

            $count++;
        }

        return $count;
    }

    /**
     * Helper method to anonymize vols_decouverte data
     * Simplified version of vols_decouverte/anonymize_all logic
     *
     * @param bool $with_number If true, use numbered data, otherwise use natural-looking data
     * @return int Number of records updated
     */
    private function _anonymize_vols_decouverte($with_number = false) {
        $this->load->model('vols_decouverte_model');

        // Natural data lists for realistic anonymization (expanded to 300 surnames for maximum variety)
        $noms = array(
            'Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy',
            'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent',
            'Fournier', 'Morel', 'Girard', 'Andre', 'Lefevre', 'Mercier', 'Dupuis', 'Lambert', 'Bonnet', 'Francois',
            'Martinez', 'Legrand', 'Garnier', 'Faure', 'Rousseau', 'Blanc', 'Guerin', 'Muller', 'Henry', 'Roussel',
            'Nicolas', 'Perrin', 'Morin', 'Mathieu', 'Clement', 'Gauthier', 'Dumont', 'Lopez', 'Fontaine', 'Chevalier',
            'Robin', 'Masson', 'Sanchez', 'Gerard', 'Nguyen', 'Boyer', 'Denis', 'Lemaire', 'Duval', 'Joly',
            'Gautier', 'Roger', 'Roche', 'Roy', 'Noel', 'Meyer', 'Lucas', 'Meunier', 'Jean', 'Perez',
            'Marchand', 'Dufour', 'Blanchard', 'Marie', 'Barbier', 'Brun', 'Dumas', 'Brunet', 'Schmitt', 'Leroux',
            'Colin', 'Fernandez', 'Renard', 'Arnaud', 'Rolland', 'Caron', 'Giraud', 'Lacroix', 'Riviere', 'Benoit',
            'Leclerc', 'Payet', 'Olivier', 'Guillot', 'Bourgeois', 'Hubert', 'Berger', 'Carpentier', 'Vasseur', 'Louis',
            'Menard', 'Rey', 'Picard', 'Leclercq', 'Gaillard', 'Philippe', 'Le Gall', 'Paris', 'Girard', 'Barre',
            'Pierre', 'Renaud', 'Aubert', 'Schneider', 'Bertrand', 'Fabre', 'Vidal', 'Moulin', 'Delaunay', 'Breton',
            'Maillard', 'Lemoine', 'Remy', 'Marchal', 'Roussel', 'Dumont', 'Carre', 'Voisin', 'Pelletier', 'Cohen',
            'Lecomte', 'Fleury', 'Gros', 'Collet', 'Pages', 'Godard', 'Langlois', 'Gay', 'Charpentier', 'Boulanger',
            'Prevost', 'Perrot', 'Bailly', 'Lejeune', 'Etienne', 'Weber', 'Reynaud', 'Lefebvre', 'Baron', 'Roux',
            'Legrand', 'Rossi', 'Guillaume', 'Nguyen', 'Da Silva', 'Santos', 'Fernandes', 'Rodrigues', 'Pereira', 'Alves',
            'Ribeiro', 'Carvalho', 'Gomes', 'Martins', 'Ferreira', 'Costa', 'Oliveira', 'Souza', 'Lima', 'Silva',
            'Deschamps', 'Charrier', 'Marechal', 'Jacob', 'Leveque', 'Poirier', 'Boucher', 'Chevallier', 'Germain', 'Lebrun',
            'Levy', 'Besnard', 'Pasquier', 'Georges', 'Adam', 'Mallet', 'Guibert', 'Tanguy', 'Guyot', 'Marty',
            'Fischer', 'Toussaint', 'Rousseau', 'Bertin', 'Grondin', 'Monnier', 'Collin', 'Courtois', 'Maury', 'Klein',
            'Lefort', 'Launay', 'Jacquet', 'Coulon', 'Humbert', 'Tessier', 'Reynaud', 'Wagner', 'Dijoux', 'Hoarau',
            'Olivier', 'Aubry', 'Pruvost', 'Lacombe', 'Poulain', 'Bigot', 'Dupuis', 'Collet', 'Maillard', 'Salmon',
            'Bouvier', 'Bouchet', 'Lombard', 'Marques', 'Neveu', 'Gilbert', 'Leduc', 'Remy', 'Bonnet', 'Marin',
            'Germain', 'Lopes', 'Delorme', 'Texier', 'Leblanc', 'Carlier', 'Royer', 'Antoine', 'Barthelemy', 'Dos Santos',
            'Guillou', 'Berthier', 'Millet', 'Benard', 'Morvan', 'Charles', 'Lelievre', 'Mahe', 'Mounier', 'Vaillant',
            'Tessier', 'Alonso', 'Laroche', 'Guilbert', 'Picard', 'Leroux', 'Valentin', 'Lebreton', 'Bruneau', 'Cousin',
            'Guilloux', 'Masse', 'Boulay', 'Parent', 'Gregoire', 'Laine', 'Alexandre', 'Bernier', 'Lebeau', 'Cordier',
            'Hamon', 'Barriere', 'Raymond', 'Barbier', 'Bonneau', 'Leroy', 'Blondel', 'Buisson', 'Lejeune', 'Vallet',
            'Meunier', 'Letellier', 'Jacques', 'Martineau', 'Bonnin', 'Guillon', 'Guerin', 'Camus', 'Pichon', 'Reynaud',
            'Coste', 'Leclerc', 'Godard', 'Colas', 'Pons', 'Charron', 'Rocher', 'Boutin', 'Gay', 'Vallet'
        );
        $prenoms = array(
            'Jean', 'Marie', 'Pierre', 'Michel', 'Andre', 'Philippe', 'Alain', 'Jacques', 'Bernard', 'Christophe',
            'Claude', 'Patrick', 'Francois', 'Daniel', 'Marc', 'Paul', 'Nicolas', 'Laurent', 'Thierry', 'Christian',
            'Olivier', 'Sebastien', 'Eric', 'Pascal', 'Antoine', 'Vincent', 'Julien', 'David', 'Alexandre', 'Stephane',
            'Gerard', 'Frederic', 'Guillaume', 'Rene', 'Henri', 'Bruno', 'Denis', 'Didier', 'Yves', 'Serge',
            'Matthieu', 'Fabrice', 'Benoit', 'Charles', 'Jerome', 'Franck', 'Thomas', 'Dominique', 'Emmanuel', 'Gilles',
            'Ludovic', 'Maxime', 'Cedric', 'Benjamin', 'Lucas', 'Sylvain', 'Damien', 'Arnaud', 'Valentin', 'Hugo',
            'Louis', 'Arthur', 'Gabriel', 'Jules', 'Raphael', 'Felix', 'Oscar', 'Simon', 'Baptiste', 'Nathan',
            'Fabien', 'Xavier', 'Loic', 'Florian', 'Quentin', 'Clement', 'Alexis', 'Kevin', 'Jeremy', 'Jonathan',
            'Romain', 'Adrien', 'Mickael', 'Anthony', 'Cyril', 'Nicolas', 'Samuel', 'Mathieu', 'Vincent', 'Yannick',
            'Christophe', 'Laurent', 'Sebastien', 'Olivier', 'Julien', 'Cedric', 'Gregory', 'Francois', 'Pierre', 'Marc'
        );

        // Get all discovery flights
        $vols = $this->db->select('id')->from('vols_decouverte')->get()->result_array();
        $count = 0;

        foreach ($vols as $row) {
            $id = $row['id'];

            if ($with_number) {
                // Numbered mode (fast)
                $random_data = array(
                    'beneficiaire' => 'Nom' . mt_rand(1000, 9999),
                    'de_la_part' => 'DeLaPart' . mt_rand(1000, 9999),
                    'beneficiaire_email' => 'vol' . mt_rand(1000, 9999) . '@example.com',
                    'beneficiaire_tel' => '0612345' . sprintf('%03d', mt_rand(0, 999)),
                    'urgence' => 'Contact' . mt_rand(1000, 9999) . ' - 0698765' . sprintf('%03d', mt_rand(0, 999))
                );
            } else {
                // Natural mode (realistic)
                $nom = $noms[array_rand($noms)];
                $prenom = $prenoms[array_rand($prenoms)];
                $nom_donneur = $noms[array_rand($noms)];
                $prenom_donneur = $prenoms[array_rand($prenoms)];

                $random_data = array(
                    'beneficiaire' => $prenom . ' ' . $nom,
                    'de_la_part' => $prenom_donneur . ' ' . $nom_donneur,
                    'beneficiaire_email' => strtolower($prenom . '.' . $nom) . mt_rand(1, 99) . '@example.com',
                    'beneficiaire_tel' => '06' . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)),
                    'urgence' => $prenoms[array_rand($prenoms)] . ' ' . $noms[array_rand($noms)] . ' - 06' . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99)) . sprintf('%02d', mt_rand(10, 99))
                );
            }

            // Update vol de decouverte using direct DB update
            $this->db->where('id', $id);
            $this->db->update('vols_decouverte', $random_data);
            $count++;
        }

        return $count;
    }

    /**
     * Extract test data for Playwright tests
     * Extracts real pilot, aircraft, and account data from the database
     * to be used in end-to-end tests, avoiding hardcoded data issues
     *
     * Only available to authorized user (fpeignot)
     */
    public function extract_test_data() {
        // Security check: only for authorized user (fpeignot only)
        if ($this->dx_auth->get_username() !== 'fpeignot') {
            show_error('Cette fonction est réservée aux administrateurs autorisés', 403, 'Accès refusé');
            return;
        }

        // Initialize test data structure
        $test_data = array(
            'metadata' => array(
                'extracted_at' => date('Y-m-d H:i:s'),
                'database' => $this->db->database,
                'version' => '1.0'
            ),
            'pilots' => array(),
            'instructors' => array(
                'glider' => array(),
                'airplane' => array()
            ),
            'gliders' => array(
                'two_seater' => array(),
                'single_seater' => array()
            ),
            'tow_planes' => array(),
            'accounts' => array()
        );

        $results = array();

        // Extract regular pilots with accounts
        $query = $this->db->query("
            SELECT
                m.mlogin,
                CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                m.mprenom as first_name,
                m.mnom as last_name,
                m.actif,
                c.id as account_id,
                CONCAT('(411) ', m.mnom, ' ', m.mprenom) as account_label
            FROM membres m
            LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
            WHERE m.actif = 1
                AND m.ext = 0
                AND c.id IS NOT NULL
            ORDER BY m.mnom, m.mprenom
            LIMIT 10
        ");

        foreach ($query->result() as $row) {
            $test_data['pilots'][] = array(
                'login' => $row->mlogin,
                'full_name' => $row->full_name,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'account_id' => (int)$row->account_id,
                'account_label' => $row->account_label
            );
        }
        $results[] = array(
            'routine' => 'Pilotes avec comptes',
            'extracted' => count($test_data['pilots'])
        );

        // Extract glider instructors
        $query = $this->db->query("
            SELECT
                m.mlogin,
                CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                m.mprenom as first_name,
                m.mnom as last_name,
                m.inst_glider,
                c.id as account_id,
                CONCAT('(411) ', m.mnom, ' ', m.mprenom) as account_label
            FROM membres m
            LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
            WHERE m.actif = 1
                AND m.ext = 0
                AND m.inst_glider IS NOT NULL
                AND m.inst_glider != ''
                AND c.id IS NOT NULL
            ORDER BY m.mnom, m.mprenom
            LIMIT 5
        ");

        foreach ($query->result() as $row) {
            $test_data['instructors']['glider'][] = array(
                'login' => $row->mlogin,
                'full_name' => $row->full_name,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'qualification' => $row->inst_glider,
                'account_id' => (int)$row->account_id,
                'account_label' => $row->account_label
            );
        }
        $results[] = array(
            'routine' => 'Instructeurs planeur',
            'extracted' => count($test_data['instructors']['glider'])
        );

        // Extract airplane instructors (tow pilots)
        $query = $this->db->query("
            SELECT
                m.mlogin,
                CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                m.mprenom as first_name,
                m.mnom as last_name,
                m.inst_airplane
            FROM membres m
            WHERE m.actif = 1
                AND m.ext = 0
                AND m.inst_airplane IS NOT NULL
                AND m.inst_airplane != ''
            ORDER BY m.mnom, m.mprenom
            LIMIT 5
        ");

        foreach ($query->result() as $row) {
            $test_data['instructors']['airplane'][] = array(
                'login' => $row->mlogin,
                'full_name' => $row->full_name,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'qualification' => $row->inst_airplane
            );
        }
        $results[] = array(
            'routine' => 'Pilotes remorqueurs',
            'extracted' => count($test_data['instructors']['airplane'])
        );

        // Extract two-seater gliders
        $query = $this->db->query("
            SELECT
                mpimmat as registration,
                mpmodele as model,
                mpconstruc as manufacturer,
                mpbiplace as seats,
                mpautonome as autonomous
            FROM machinesp
            WHERE actif = 1
                AND mpbiplace = '1'
            ORDER BY mpimmat
            LIMIT 5
        ");

        foreach ($query->result() as $row) {
            $test_data['gliders']['two_seater'][] = array(
                'registration' => $row->registration,
                'model' => $row->model,
                'manufacturer' => $row->manufacturer,
                'seats' => 2,
                'autonomous' => (bool)$row->autonomous
            );
        }
        $results[] = array(
            'routine' => 'Planeurs biplaces',
            'extracted' => count($test_data['gliders']['two_seater'])
        );

        // Extract single-seater gliders
        $query = $this->db->query("
            SELECT
                mpimmat as registration,
                mpmodele as model,
                mpconstruc as manufacturer,
                mpbiplace as seats,
                mpautonome as autonomous
            FROM machinesp
            WHERE actif = 1
                AND mpbiplace = '0'
            ORDER BY mpimmat
            LIMIT 5
        ");

        foreach ($query->result() as $row) {
            $test_data['gliders']['single_seater'][] = array(
                'registration' => $row->registration,
                'model' => $row->model,
                'manufacturer' => $row->manufacturer,
                'seats' => 1,
                'autonomous' => (bool)$row->autonomous
            );
        }
        $results[] = array(
            'routine' => 'Planeurs monoplaces',
            'extracted' => count($test_data['gliders']['single_seater'])
        );

        // Extract tow planes
        $query = $this->db->query("
            SELECT
                macimmat as registration,
                macmodele as model,
                macconstruc as manufacturer,
                macrem as is_tow_plane
            FROM machinesa
            WHERE actif = 1
                AND macrem = 1
            ORDER BY macimmat
            LIMIT 5
        ");

        foreach ($query->result() as $row) {
            $test_data['tow_planes'][] = array(
                'registration' => $row->registration,
                'model' => $row->model,
                'manufacturer' => $row->manufacturer
            );
        }
        $results[] = array(
            'routine' => 'Avions remorqueurs',
            'extracted' => count($test_data['tow_planes'])
        );

        // Extract member accounts (for billing)
        $query = $this->db->query("
            SELECT
                c.id,
                c.nom as account_name,
                c.pilote as pilot_login,
                c.codec as account_code,
                CONCAT('(', c.codec, ') ', c.nom) as label
            FROM comptes c
            WHERE c.actif = 1
                AND c.codec LIKE '411%'
                AND c.pilote IS NOT NULL
                AND c.pilote != ''
            ORDER BY c.nom
            LIMIT 20
        ");

        foreach ($query->result() as $row) {
            $test_data['accounts'][] = array(
                'id' => (int)$row->id,
                'name' => $row->account_name,
                'pilot_login' => $row->pilot_login,
                'code' => $row->account_code,
                'label' => $row->label
            );
        }
        $results[] = array(
            'routine' => 'Comptes membres',
            'extracted' => count($test_data['accounts'])
        );

        // Create output directory if needed
        $output_dir = FCPATH . 'playwright/test-data';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0755, true);
        }

        // Write JSON file
        $output_file = $output_dir . '/fixtures.json';
        $json = json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $write_success = file_put_contents($output_file, $json) !== false;

        // Calculate total extracted
        $total_extracted = 0;
        foreach ($results as $result) {
            $total_extracted += $result['extracted'];
        }

        // Prepare view data
        $data = array(
            'title' => 'Extraction de données de test',
            'message' => $write_success ?
                'Les données de test ont été extraites avec succès !' :
                'Erreur lors de l\'écriture du fichier de sortie',
            'results' => $results,
            'total_extracted' => $total_extracted,
            'output_file' => $output_file,
            'file_size' => $write_success ? filesize($output_file) : 0,
            'errors' => $write_success ? array() : array('Impossible d\'écrire le fichier ' . $output_file)
        );

        // Load view
        load_last_view('admin/bs_extraction_results', $data);
    }

    /**
     * Generate encrypted test database
     * Creates anonymized backup, adds test users, and encrypts the result
     * Only callable by authorized user (fpeignot)
     */
    public function generate_test_database() {
        // Security check: only for authorized user (fpeignot only)
        if ($this->dx_auth->get_username() !== 'fpeignot') {
            show_error('Cette fonction est réservée aux administrateurs autorisés', 403, 'Accès refusé');
            return;
        }

        // Load database config
        require(APPPATH . 'config/database.php');
        $db_config = $db['default'];

        $results = array();
        $errors = array();
        $temp_files = array();

        try {
            // Step 1: Create database backup before anonymization
            log_message('info', 'Step 1: Creating pre-anonymization backup');
            $backup_file = sys_get_temp_dir() . '/gvv_pre_anon_' . time() . '.sql';
            $temp_files[] = $backup_file;

            $cmd = sprintf(
                'mysqldump --single-transaction --skip-lock-tables -h%s -u%s -p%s %s > %s 2>&1',
                escapeshellarg($db_config['hostname']),
                escapeshellarg($db_config['username']),
                escapeshellarg($db_config['password']),
                escapeshellarg($db_config['database']),
                escapeshellarg($backup_file)
            );

            exec($cmd, $output, $return_code);
            if ($return_code !== 0) {
                throw new Exception("Échec de la sauvegarde pré-anonymisation: " . implode("\n", $output));
            }
            $results[] = array('step' => 'Sauvegarde initiale', 'status' => 'OK', 'details' => filesize($backup_file) . ' bytes');

            // Step 2: Anonymize data using existing method
            log_message('info', 'Step 2: Anonymizing data');
            ob_start();
            $with_number = $this->input->post('with_number') == '1';
            
            $membres_updated = $this->_anonymize_membres($with_number);
            $users_updated = $this->_anonymize_users();
            $vd_updated = $this->_anonymize_vols_decouverte($with_number);
            
            ob_end_clean();
            
            $total_anonymized = $membres_updated + $users_updated + $vd_updated;
            $results[] = array('step' => 'Anonymisation', 'status' => 'OK', 'details' => "$total_anonymized enregistrements anonymisés");

            // Step 3: Add test users
            log_message('info', 'Step 3: Adding test users');
            $test_users_script = FCPATH . 'bin/create_test_users.sh';
            
            if (file_exists($test_users_script)) {
                $env_vars = sprintf(
                    'MYSQL_HOST=%s MYSQL_USER=%s MYSQL_PASSWORD=%s MYSQL_DATABASE=%s',
                    escapeshellarg($db_config['hostname']),
                    escapeshellarg($db_config['username']),
                    escapeshellarg($db_config['password']),
                    escapeshellarg($db_config['database'])
                );
                
                exec("$env_vars bash $test_users_script 2>&1", $output, $return_code);
                if ($return_code === 0) {
                    $results[] = array('step' => 'Utilisateurs de test', 'status' => 'OK', 'details' => '6 utilisateurs créés');
                } else {
                    $results[] = array('step' => 'Utilisateurs de test', 'status' => 'WARNING', 'details' => 'Script échoué: ' . implode("\n", $output));
                }
            } else {
                $results[] = array('step' => 'Utilisateurs de test', 'status' => 'SKIPPED', 'details' => 'Script non trouvé');
            }

            // Step 4: Update fixtures.json with test data
            log_message('info', 'Step 4: Updating fixtures.json');

            try {
                // Initialize test data structure
                $test_data = array(
                    'metadata' => array(
                        'extracted_at' => date('Y-m-d H:i:s'),
                        'database' => $this->db->database,
                        'version' => '1.0'
                    ),
                    'pilots' => array(),
                    'instructors' => array(
                        'glider' => array(),
                        'airplane' => array()
                    ),
                    'gliders' => array(
                        'two_seater' => array(),
                        'single_seater' => array()
                    ),
                    'tow_planes' => array(),
                    'accounts' => array()
                );

                // Extract regular pilots with accounts
                $query = $this->db->query("
                    SELECT
                        m.mlogin,
                        CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                        m.mprenom as first_name,
                        m.mnom as last_name,
                        m.actif,
                        c.id as account_id,
                        CONCAT('(411) ', m.mnom, ' ', m.mprenom) as account_label
                    FROM membres m
                    LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
                    WHERE m.actif = 1
                        AND m.ext = 0
                        AND c.id IS NOT NULL
                    ORDER BY m.mnom, m.mprenom
                    LIMIT 10
                ");
                foreach ($query->result() as $row) {
                    $test_data['pilots'][] = array(
                        'login' => $row->mlogin,
                        'full_name' => $row->full_name,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'account_id' => (int)$row->account_id,
                        'account_label' => $row->account_label
                    );
                }

                // Extract glider instructors
                $query = $this->db->query("
                    SELECT
                        m.mlogin,
                        CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                        m.mprenom as first_name,
                        m.mnom as last_name,
                        m.inst_glider,
                        c.id as account_id,
                        CONCAT('(411) ', m.mnom, ' ', m.mprenom) as account_label
                    FROM membres m
                    LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
                    WHERE m.actif = 1
                        AND m.ext = 0
                        AND m.inst_glider IS NOT NULL
                        AND m.inst_glider != ''
                        AND c.id IS NOT NULL
                    ORDER BY m.mnom, m.mprenom
                    LIMIT 5
                ");
                foreach ($query->result() as $row) {
                    $test_data['instructors']['glider'][] = array(
                        'login' => $row->mlogin,
                        'full_name' => $row->full_name,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'qualification' => $row->inst_glider,
                        'account_id' => (int)$row->account_id,
                        'account_label' => $row->account_label
                    );
                }

                // Extract airplane instructors (tow pilots)
                $query = $this->db->query("
                    SELECT
                        m.mlogin,
                        CONCAT(m.mnom, ' ', m.mprenom) as full_name,
                        m.mprenom as first_name,
                        m.mnom as last_name,
                        m.inst_airplane
                    FROM membres m
                    WHERE m.actif = 1
                        AND m.ext = 0
                        AND m.inst_airplane IS NOT NULL
                        AND m.inst_airplane != ''
                    ORDER BY m.mnom, m.mprenom
                    LIMIT 5
                ");
                foreach ($query->result() as $row) {
                    $test_data['instructors']['airplane'][] = array(
                        'login' => $row->mlogin,
                        'full_name' => $row->full_name,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'qualification' => $row->inst_airplane
                    );
                }

                // Extract two-seater gliders
                $query = $this->db->query("
                    SELECT
                        mpimmat as registration,
                        mpmodele as model,
                        mpconstruc as manufacturer,
                        mpbiplace as seats,
                        mpautonome as autonomous
                    FROM machinesp
                    WHERE actif = 1
                        AND mpbiplace = '1'
                    ORDER BY mpimmat
                    LIMIT 5
                ");
                foreach ($query->result() as $row) {
                    $test_data['gliders']['two_seater'][] = array(
                        'registration' => $row->registration,
                        'model' => $row->model,
                        'manufacturer' => $row->manufacturer,
                        'seats' => 2,
                        'autonomous' => (bool)$row->autonomous
                    );
                }

                // Extract single-seater gliders
                $query = $this->db->query("
                    SELECT
                        mpimmat as registration,
                        mpmodele as model,
                        mpconstruc as manufacturer,
                        mpbiplace as seats,
                        mpautonome as autonomous
                    FROM machinesp
                    WHERE actif = 1
                        AND mpbiplace = '0'
                    ORDER BY mpimmat
                    LIMIT 5
                ");
                foreach ($query->result() as $row) {
                    $test_data['gliders']['single_seater'][] = array(
                        'registration' => $row->registration,
                        'model' => $row->model,
                        'manufacturer' => $row->manufacturer,
                        'seats' => 1,
                        'autonomous' => (bool)$row->autonomous
                    );
                }

                // Extract tow planes
                $query = $this->db->query("
                    SELECT
                        macimmat as registration,
                        macmodele as model,
                        macconstruc as manufacturer,
                        macrem as is_tow_plane
                    FROM machinesa
                    WHERE actif = 1
                        AND macrem = 1
                    ORDER BY macimmat
                    LIMIT 5
                ");
                foreach ($query->result() as $row) {
                    $test_data['tow_planes'][] = array(
                        'registration' => $row->registration,
                        'model' => $row->model,
                        'manufacturer' => $row->manufacturer
                    );
                }

                // Extract member accounts (for billing)
                $query = $this->db->query("
                    SELECT
                        c.id,
                        c.nom as account_name,
                        c.pilote as pilot_login,
                        c.codec as account_code,
                        CONCAT('(', c.codec, ') ', c.nom) as label
                    FROM comptes c
                    WHERE c.actif = 1
                        AND c.codec LIKE '411%'
                        AND c.pilote IS NOT NULL
                        AND c.pilote != ''
                    ORDER BY c.nom
                    LIMIT 20
                ");
                foreach ($query->result() as $row) {
                    $test_data['accounts'][] = array(
                        'id' => (int)$row->id,
                        'name' => $row->account_name,
                        'pilot_login' => $row->pilot_login,
                        'code' => $row->account_code,
                        'label' => $row->label
                    );
                }

                // Create output directory if needed
                $output_dir = FCPATH . 'playwright/test-data';
                if (!is_dir($output_dir)) {
                    mkdir($output_dir, 0755, true);
                }

                // Write JSON file
                $output_file = $output_dir . '/fixtures.json';
                $json = json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $write_success = file_put_contents($output_file, $json) !== false;

                // Calculate total extracted
                $total_extracted = count($test_data['pilots']) +
                                 count($test_data['instructors']['glider']) +
                                 count($test_data['instructors']['airplane']) +
                                 count($test_data['gliders']['two_seater']) +
                                 count($test_data['gliders']['single_seater']) +
                                 count($test_data['tow_planes']) +
                                 count($test_data['accounts']);

                if ($write_success) {
                    $results[] = array('step' => 'Mise à jour fixtures.json', 'status' => 'OK', 'details' => "$total_extracted enregistrements");
                } else {
                    $results[] = array('step' => 'Mise à jour fixtures.json', 'status' => 'WARNING', 'details' => 'Échec écriture fichier');
                }
            } catch (Exception $e) {
                $results[] = array('step' => 'Mise à jour fixtures.json', 'status' => 'WARNING', 'details' => $e->getMessage());
            }

            // Step 5: Create anonymized dump and package it (following backup2 procedure exactly)
            log_message('info', 'Step 5: Creating anonymized dump');

            // Load crypto helper
            $this->load->helper('crypto');

            // Get passphrase from config
            $passphrase = $this->config->item('passphrase');
            if (empty($passphrase)) {
                throw new Exception("Passphrase non configurée dans application/config/program.php");
            }

            // Ensure install directory exists
            $install_dir = FCPATH . 'install';
            if (!is_dir($install_dir)) {
                mkdir($install_dir, 0755, true);
            }

            // Get migration version (same as backup2)
            $this->db->select_max('version');
            $query = $this->db->get('migrations');
            $row = $query->row();
            $migration = $row ? $row->version : 0;

            // Create filenames following backup2 naming convention
            $nom_club = $this->config->item('nom_club');
            $clubid = strtolower(str_replace(' ', '_', $nom_club));
            $dt = date("Ymd_His");
            $database = $db_config['database'];

            $base_name = $database . "_backup_" . $clubid . "_" . $dt . "_migration_" . $migration;
            $base_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $base_name);
            $sql_filename = $base_name . ".sql";
            $zip_filename = $base_name . ".zip";

            // Change to install directory (same as backup2 does with backups dir)
            $original_dir = getcwd();
            chdir($install_dir);

            // Create SQL dump in install directory
            $cmd = sprintf(
                'mysqldump --single-transaction --skip-lock-tables -h%s -u%s -p%s %s > %s 2>&1',
                escapeshellarg($db_config['hostname']),
                escapeshellarg($db_config['username']),
                escapeshellarg($db_config['password']),
                escapeshellarg($database),
                escapeshellarg($sql_filename)
            );

            exec($cmd, $output, $return_code);
            if ($return_code !== 0) {
                chdir($original_dir);
                throw new Exception("Échec du dump anonymisé: " . implode("\n", $output));
            }

            if (!file_exists($sql_filename)) {
                chdir($original_dir);
                throw new Exception("Le fichier SQL n'a pas été créé");
            }

            $results[] = array('step' => 'Dump anonymisé', 'status' => 'OK', 'details' => filesize($sql_filename) . ' bytes');

            // Step 6: Create ZIP archive (same as backup2)
            log_message('info', 'Step 6: Creating ZIP archive');

            $cmd = sprintf('zip %s %s 2>&1', escapeshellarg($zip_filename), escapeshellarg($sql_filename));
            exec($cmd, $output, $return_code);
            if ($return_code !== 0) {
                chdir($original_dir);
                throw new Exception("Échec de la création de l'archive ZIP: " . implode("\n", $output));
            }

            if (!file_exists($zip_filename)) {
                chdir($original_dir);
                throw new Exception("Le fichier ZIP n'a pas été créé");
            }

            // Delete SQL file (same as backup2)
            unlink($sql_filename);

            $results[] = array('step' => 'Archive ZIP', 'status' => 'OK', 'details' => filesize($zip_filename) . ' bytes');

            // Step 7: Encrypt the ZIP file (same as backup2)
            log_message('info', 'Step 7: Encrypting ZIP file');

            $encrypted_filename = get_encrypted_filename($zip_filename);

            if (!encrypt_file($zip_filename, $encrypted_filename, $passphrase)) {
                chdir($original_dir);
                throw new Exception("Échec du chiffrement OpenSSL");
            }

            // Delete unencrypted ZIP (same as backup2)
            unlink($zip_filename);

            // Rename to fixed name for test database
            $final_filename = 'base_de_test.enc.zip';
            if (file_exists($final_filename)) {
                unlink($final_filename);
            }
            rename($encrypted_filename, $final_filename);

            // Return to original directory
            chdir($original_dir);

            $results[] = array('step' => 'Chiffrement OpenSSL', 'status' => 'OK', 'details' => filesize($install_dir . '/' . $final_filename) . ' bytes');

            // Step 8: Restore original database
            log_message('info', 'Step 8: Restoring original database');
            $cmd = sprintf(
                'mysql -h%s -u%s -p%s %s < %s 2>&1',
                escapeshellarg($db_config['hostname']),
                escapeshellarg($db_config['username']),
                escapeshellarg($db_config['password']),
                escapeshellarg($db_config['database']),
                escapeshellarg($backup_file)
            );

            exec($cmd, $output, $return_code);
            if ($return_code !== 0) {
                throw new Exception("CRITIQUE: Échec de la restauration. Base en état anonymisé! Erreur: " . implode("\n", $output));
            }
            $results[] = array('step' => 'Restauration base', 'status' => 'OK', 'details' => 'Base restaurée à l\'état initial');

            $success_message = "Base de test générée avec succès dans install/base_de_test.enc.zip";

        } catch (Exception $e) {
            log_message('error', 'Test database generation failed: ' . $e->getMessage());
            $errors[] = $e->getMessage();
            $success_message = null;
        } finally {
            // Cleanup temp files
            foreach ($temp_files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        // Prepare view data
        $data = array(
            'title' => 'Génération de la base de test',
            'results' => $results,
            'errors' => $errors,
            'message' => $success_message,
            'show_form' => empty($success_message) && empty($errors)
        );

        load_last_view('admin/bs_test_database_generation', $data);
    }

}