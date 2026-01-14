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
    /**
     * Admin page - DEPRECATED
     * This page has been removed. Development and test features are now available
     * in the "Développement & Tests" section of the welcome dashboard.
     * 
     * @deprecated Use welcome/index instead
     */
    public function page() {
        // Redirect to welcome dashboard instead
        redirect('welcome');
    }

    /**
     * Just display phpinfo
     */
    public function info() {
        echo phpinfo();
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
    public function extract_test_data($silent = false) {
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
        $test_data['pilots'] = $this->_unique_by_key($test_data['pilots'], 'login');
        $results[] = array(
            'routine' => 'Pilotes avec comptes',
            'extracted' => count($test_data['pilots'])
        );

        // Extract glider instructors
        $this->load->model('membres_model');
        $glider_selector = $this->membres_model->qualif_selector('mlogin', ITP | IVV);

        foreach ($glider_selector as $login => $label) {
            if (empty($login)) {
                continue;
            }

            $member = $this->db
                ->select('mlogin, mnom, mprenom, inst_glider')
                ->from('membres')
                ->where('mlogin', $login)
                ->where('actif', 1)
                ->where('ext', 0)
                ->get()
                ->row();

            if (!$member) {
                continue;
            }

            $account = $this->db
                ->select('id, nom, codec')
                ->from('comptes')
                ->where('pilote', $login)
                ->like('codec', '411', 'after')
                ->order_by('id', 'asc')
                ->limit(1)
                ->get()
                ->row();

            if (!$account) {
                continue; // skip if no 411 account
            }

            $test_data['instructors']['glider'][] = array(
                'login' => $member->mlogin,
                'full_name' => $member->mnom . ' ' . $member->mprenom,
                'first_name' => $member->mprenom,
                'last_name' => $member->mnom,
                'qualification' => $member->inst_glider,
                'account_id' => (int)$account->id,
                'account_label' => '(' . $account->codec . ') ' . $account->nom
            );
        }
        $test_data['instructors']['glider'] = $this->_unique_by_key($test_data['instructors']['glider'], 'login');
        $results[] = array(
            'routine' => 'Instructeurs planeur',
            'extracted' => count($test_data['instructors']['glider'])
        );

        // Extract airplane instructors (FI/FE) using same selector as vols_avion
        $air_selector = $this->membres_model->qualif_selector('mlogin', FI_AVION | FE_AVION);

        foreach ($air_selector as $login => $label) {
            if (empty($login)) {
                continue;
            }

            $member = $this->db
                ->select('mlogin, mnom, mprenom, inst_airplane')
                ->from('membres')
                ->where('mlogin', $login)
                ->where('actif', 1)
                ->where('ext', 0)
                ->get()
                ->row();

            if (!$member) {
                continue;
            }

            $account = $this->db
                ->select('id, nom, codec')
                ->from('comptes')
                ->where('pilote', $login)
                ->like('codec', '411', 'after')
                ->order_by('id', 'asc')
                ->limit(1)
                ->get()
                ->row();

            if (!$account) {
                continue; // skip if no 411 account
            }

            $test_data['instructors']['airplane'][] = array(
                'login' => $member->mlogin,
                'full_name' => $member->mnom . ' ' . $member->mprenom,
                'first_name' => $member->mprenom,
                'last_name' => $member->mnom,
                'qualification' => $member->inst_airplane,
                'account_id' => (int)$account->id,
                'account_label' => '(' . $account->codec . ') ' . $account->nom
            );
        }
        $test_data['instructors']['airplane'] = $this->_unique_by_key($test_data['instructors']['airplane'], 'login');
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
                AND mpbiplace = '2'
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
        $test_data['gliders']['two_seater'] = $this->_unique_by_key($test_data['gliders']['two_seater'], 'registration');
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
        $test_data['gliders']['single_seater'] = $this->_unique_by_key($test_data['gliders']['single_seater'], 'registration');
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
        $test_data['tow_planes'] = $this->_unique_by_key($test_data['tow_planes'], 'registration');
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
        $test_data['accounts'] = $this->_unique_by_key($test_data['accounts'], 'pilot_login');
        $results[] = array(
            'routine' => 'Comptes membres',
            'extracted' => count($test_data['accounts'])
        );

        // Add balance search test cases
        $test_data['balance_search_tests'] = array(
            array(
                'description' => 'Search for Adam using ADA',
                'search_term' => 'ADA',
                'expected_name' => 'Adam',
                'expected_account_code' => '411'
            ),
            array(
                'description' => 'Search for Alonso using ALO',
                'search_term' => 'ALO',
                'expected_name' => 'Alonso',
                'expected_account_code' => '411'
            ),
            array(
                'description' => 'Search for Barbier using BAR',
                'search_term' => 'BAR',
                'expected_name' => 'Barbier',
                'expected_account_code' => '411'
            )
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

        $summary = array(
            'write_success' => $write_success,
            'results' => $results,
            'total_extracted' => $total_extracted,
            'output_file' => $output_file,
            'file_size' => $write_success ? filesize($output_file) : 0,
            'errors' => $write_success ? array() : array('Impossible d\'écrire le fichier ' . $output_file)
        );

        if ($silent) {
            return $summary;
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
            'file_size' => $summary['file_size'],
            'errors' => $summary['errors']
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

            // Step 3: Add legacy test users (testuser, testadmin, etc.)
            log_message('info', 'Step 3: Adding legacy test users');
            $legacy_result = $this->_create_test_legacy_users();
            $legacy_created = $legacy_result['created'];

            if ($legacy_created > 0) {
                $results[] = array('step' => 'Utilisateurs legacy', 'status' => 'OK',
                    'details' => "$legacy_created utilisateurs créés (testuser, testadmin, testplanchiste, testca, testbureau, testtresorier)");
            } else if (!empty($legacy_result['errors'])) {
                $results[] = array('step' => 'Utilisateurs legacy', 'status' => 'WARNING',
                    'details' => 'Erreurs: ' . implode('; ', $legacy_result['errors']));
            } else {
                $results[] = array('step' => 'Utilisateurs legacy', 'status' => 'INFO',
                    'details' => 'Aucun utilisateur créé');
            }

            // Step 3b: Add Gaulois test users with full profiles
            log_message('info', 'Step 3b: Adding Gaulois test users');
            $gaulois_result = $this->_create_test_gaulois_users();
            $gaulois_created = $gaulois_result['created'];
            
            if ($gaulois_created > 0) {
                $results[] = array('step' => 'Utilisateurs Gaulois', 'status' => 'OK',
                    'details' => "$gaulois_created utilisateurs créés (asterix, obelix, abraracourcix, goudurix, panoramix)");
            } else if (!empty($gaulois_result['errors'])) {
                $results[] = array('step' => 'Utilisateurs Gaulois', 'status' => 'WARNING', 
                    'details' => 'Erreurs: ' . implode('; ', $gaulois_result['errors']));
            } else {
                $results[] = array('step' => 'Utilisateurs Gaulois', 'status' => 'INFO', 
                    'details' => 'Utilisateurs déjà existants (non recréés)');
            }

            // Step 4: Update fixtures.json with test data
            log_message('info', 'Step 4: Updating fixtures.json');

            try {
                $fixtures_result = $this->extract_test_data(true);

                if (!is_array($fixtures_result)) {
                    throw new Exception('Extraction fixtures.json: résultat inattendu');
                }

                if ($fixtures_result['write_success']) {
                    $results[] = array(
                        'step' => 'Mise à jour fixtures.json',
                        'status' => 'OK',
                        'details' => $fixtures_result['total_extracted'] . ' enregistrements'
                    );
                } else {
                    $results[] = array(
                        'step' => 'Mise à jour fixtures.json',
                        'status' => 'WARNING',
                        'details' => !empty($fixtures_result['errors']) ? implode('; ', $fixtures_result['errors']) : 'Échec écriture fichier'
                    );
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

            // Step 8: Optionally restore original database (skip for test environments)
            // Check if we should restore by looking at a POST parameter
            $keep_anonymized = $this->input->post('keep_anonymized') == '1';

            if (!$keep_anonymized) {
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
                $success_message = "Base de test générée avec succès dans install/base_de_test.enc.zip (base de test non installée)";
            } else {
                $results[] = array('step' => 'Restauration base', 'status' => 'SKIPPED', 'details' => 'Base gardée anonymisée pour les tests');
                $success_message = "Base de test générée avec succès dans install/base_de_test.enc.zip (base anonymisée active)";
            }

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

    /**
     * Generate initial database schema (install/gvv_init.sql)
     * Creates schema + minimal test data for fresh GVV installation
     */
    public function generate_initial_schema() {
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

        try {
            // Step 1: Generate schema (structure only, no data)
            log_message('info', 'Step 1: Generating database schema');

            $install_dir = FCPATH . 'install';
            $schema_file = $install_dir . '/gvv_init.sql';

            // Backup existing file if it exists
            if (file_exists($schema_file)) {
                $backup_file = $install_dir . '/gvv_init.sql.backup_' . date('Ymd_His');
                copy($schema_file, $backup_file);
                $results[] = array('step' => 'Sauvegarde ancien fichier', 'status' => 'OK', 'details' => basename($backup_file));

                // Delete the old file so we can create a new one with proper ownership
                unlink($schema_file);
            }

            // Generate schema using mysqldump --no-data (use absolute path)
            $cmd = sprintf(
                '/usr/bin/mysqldump --no-data --skip-triggers --skip-lock-tables -h%s -u%s -p%s %s',
                escapeshellarg($db_config['hostname']),
                escapeshellarg($db_config['username']),
                escapeshellarg($db_config['password']),
                escapeshellarg($db_config['database'])
            );

            exec($cmd . ' 2>&1', $output, $return_code);

            if ($return_code !== 0) {
                throw new Exception("Échec de la génération du schéma: " . implode("\n", $output));
            }

            // Write the captured output to the file
            file_put_contents($schema_file, implode("\n", $output));

            // Set proper permissions so we can append test data
            chmod($schema_file, 0666);

            if (!file_exists($schema_file) || filesize($schema_file) == 0) {
                throw new Exception("Le fichier de schéma n'a pas été créé ou est vide");
            }

            $results[] = array('step' => 'Génération du schéma', 'status' => 'OK', 'details' => filesize($schema_file) . ' bytes');

            // Step 2: Add minimal test data
            log_message('info', 'Step 2: Adding minimal test data');

            $test_data = $this->_generate_minimal_test_data();

            // Append test data to schema file
            file_put_contents($schema_file, "\n-- ========================================\n", FILE_APPEND);
            file_put_contents($schema_file, "-- Données de test minimales\n", FILE_APPEND);
            file_put_contents($schema_file, "-- ========================================\n\n", FILE_APPEND);
            file_put_contents($schema_file, $test_data, FILE_APPEND);

            $results[] = array('step' => 'Ajout données de test', 'status' => 'OK', 'details' => strlen($test_data) . ' bytes');

            $success_message = "Schéma initial généré avec succès dans install/gvv_init.sql";

        } catch (Exception $e) {
            log_message('error', 'Initial schema generation failed: ' . $e->getMessage());
            $errors[] = $e->getMessage();
            $success_message = null;
        }

        // Prepare view data
        $data = array(
            'title' => 'Génération du schéma initial',
            'results' => $results,
            'errors' => $errors,
            'message' => $success_message,
            'show_form' => empty($success_message) && empty($errors)
        );

        load_last_view('admin/bs_initial_schema_generation', $data);
    }

    /**
     * Return array with unique entries based on a key
     */
    private function _unique_by_key($items, $key) {
        $unique = array();
        $seen = array();

        foreach ($items as $item) {
            if (!is_array($item) || !array_key_exists($key, $item)) {
                continue;
            }

            $value = strtolower((string)$item[$key]);
            if (!isset($seen[$value])) {
                $seen[$value] = true;
                $unique[] = $item;
            }
        }

        return $unique;
    }

    /**
     * Generate minimal test data for fresh installation
     * Extracted from actual working gvv_init.sql file
     * @return string SQL INSERT statements
     */
    private function _generate_minimal_test_data() {
        // Get current migration version dynamically
        $this->db->select_max('version');
        $query = $this->db->get('migrations');
        $row = $query->row();
        $migration_version = $row ? $row->version : 0;

        // Hardcoded INSERT statements from working gvv_init.sql
        $sql = <<<SQL
-- Migration version
INSERT INTO `migrations` (`version`) VALUES ({$migration_version});

-- Membres de test (utilisateurs Gaulois)
INSERT INTO `membres` (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES
('abraracourcix', 'Le Gaulois', 'Abraracourcix', 'abraracourcix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 8192, 0, 0, 0, 1, '0', '', 0, 'abraracourcix', '', '0', '', '', '', 0),
('asterix', 'Le Gaulois', 'Asterix', 'asterix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 524288, 0, 0, 0, 1, '0', '', 0, 'asterix', '', '0', '', '', '', 0),
('goudurix', 'Le Gaulois', 'Goudurix', 'goudurix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 0, 0, 0, 0, 1, '0', '', 0, 'goudurix', '', '0', '', '', '', 0),
('panoramix', 'Le Gaulois', 'Panoramix', 'panoramix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 491520, 0, 0, 0, 1, '0', '', 0, 'panoramix', '', '0', '', '', '', 0);

-- Permissions
INSERT INTO `permissions` (`id`, `role_id`, `data`) VALUES
(1, 1, 'a:1:{s:3:"uri";a:22:{i:0;s:8:"/membre/";i:1;s:14:"/planeur/page/";i:2;s:12:"/avion/page/";i:3;s:12:"/vols_avion/";i:4;s:14:"/vols_planeur/";i:5;s:19:"/rapports/licences/";i:6;s:19:"/compta/mon_compte/";i:7;s:23:"/compta/journal_compte/";i:8;s:25:"/compta/filterValidation/";i:9;s:12:"/compta/pdf/";i:10;s:15:"/compta/export/";i:11;s:17:"/compta/new_year/";i:12;s:18:"/comptes/new_year/";i:13;s:17:"/achats/new_year/";i:14;s:14:"/tickets/page/";i:15;s:13:"/event/stats/";i:16;s:12:"/event/page/";i:17;s:17:"/event/formation/";i:18;s:11:"/event/fai/";i:19;s:11:"/presences/";i:20;s:10:"/licences/";i:21;s:9:"/welcome/";}}'),
(2, 7, 'a:1:{s:3:"uri";a:3:{i:0;s:12:"/vols_avion/";i:1;s:14:"/vols_planeur/";i:2;s:0:"";}}'),
(3, 9, 'a:1:{s:3:"uri";a:8:{i:0;s:10:"/factures/";i:1;s:8:"/compta/";i:2;s:9:"/comptes/";i:3;s:10:"/remorque/";i:4;s:16:"/plan_comptable/";i:5;s:11:"/categorie/";i:6;s:8:"/tarifs/";i:7;s:0:"";}}'),
(4, 8, 'a:1:{s:3:"uri";a:20:{i:0;s:8:"/membre/";i:1;s:9:"/planeur/";i:2;s:7:"/avion/";i:3;s:12:"/vols_avion/";i:4;s:14:"/vols_planeur/";i:5;s:10:"/factures/";i:6;s:8:"/compta/";i:7;s:8:"/compta/";i:8;s:8:"/compta/";i:9;s:9:"/comptes/";i:10;s:9:"/tickets/";i:11;s:7:"/event/";i:12;s:10:"/rapports/";i:13;s:10:"/licences/";i:14;s:8:"/achats/";i:15;s:10:"/terrains/";i:16;s:7:"/admin/";i:17;s:9:"/reports/";i:18;s:7:"/mails/";i:19;s:12:"/historique/";}}'),
(5, 3, 'a:1:{s:3:"uri";a:2:{i:0;s:23:"/compta/journal_compte/";i:1;s:13:"/compta/view/";}}'),
(6, 2, 'a:1:{s:3:"uri";a:32:{i:0;s:8:"/membre/";i:1;s:9:"/planeur/";i:2;s:7:"/avion/";i:3;s:17:"/vols_avion/page/";i:4;s:29:"/vols_avion/filterValidation/";i:5;s:16:"/vols_avion/pdf/";i:6;s:23:"/vols_avion/statistics/";i:7;s:21:"/vols_avion/new_year/";i:8;s:19:"/vols_planeur/page/";i:9;s:24:"/vols_planeur/statistic/";i:10;s:31:"/vols_planeur/filterValidation/";i:11;s:18:"/vols_planeur/pdf/";i:12;s:24:"/vols_planeur/pdf_month/";i:13;s:26:"/vols_planeur/pdf_machine/";i:14;s:25:"/vols_planeur/export_per/";i:15;s:21:"/vols_planeur/export/";i:16;s:23:"/vols_planeur/new_year/";i:17;s:19:"/factures/en_cours/";i:18;s:15:"/factures/page/";i:19;s:15:"/factures/view/";i:20;s:21:"/factures/ma_facture/";i:21;s:19:"/compta/mon_compte/";i:22;s:23:"/compta/journal_compte/";i:23;s:25:"/compta/filterValidation/";i:24;s:12:"/compta/pdf/";i:25;s:17:"/compta/new_year/";i:26;s:18:"/comptes/new_year/";i:27;s:14:"/tickets/page/";i:28;s:13:"/event/stats/";i:29;s:12:"/event/page/";i:30;s:17:"/event/formation/";i:31;s:11:"/event/fai/";}}');

-- Roles
INSERT INTO `roles` (`id`, `parent_id`, `name`) VALUES
(1, 0, 'membre'),
(2, 9, 'admin'),
(3, 8, 'bureau'),
(7, 1, 'planchiste'),
(8, 7, 'ca'),
(9, 3, 'tresorier');

-- Sections
INSERT INTO `sections` (`id`, `nom`, `description`) VALUES
(1, 'Planeur', 'Section planeur de l\'aéroclub d\'Abbeville');

-- Types de rôles
INSERT INTO `types_roles` (`id`, `nom`, `description`) VALUES
(1, 'user', 'Capacity to login and see user data'),
(2, 'auto_planchiste', 'Capacity to create, modify and delete the user own data'),
(5, 'planchiste', 'Authorization to create, modify and delete flight data'),
(6, 'ca', 'capacity to see all data for a section including global financial data'),
(7, 'bureau', 'capacity to see all data for a section including personnal financial data'),
(8, 'tresorier', 'Capacity to edit financial data for one section'),
(9, 'super-tresorier', 'Capacity to see an edit financial data for all sections'),
(10, 'club-admin', 'capacity to access all data and change everything');

-- Type de tickets
INSERT INTO `type_ticket` (`id`, `nom`) VALUES
(0, 'Remorqué'),
(1, 'treuillé');

-- Utilisateurs de test (login=username, password=username)
INSERT INTO `users` (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES
(15, 1, 'testuser', '\$1\$wu3.3t2.\$Wgk43dHPPi3PTv5atdpnz0', 'testuser@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2023-06-17 06:34:23', '2011-04-21 15:21:13', '2023-06-17 04:34:23'),
(16, 2, 'testadmin', '\$1\$uM1.f95.\$AnUHH1W/xLS9fxDbt8RPo0', 'frederic.peignot@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2025-02-19 16:21:28', '2011-04-21 15:21:40', '2025-02-19 15:21:28'),
(58, 7, 'testplanchiste', '\$1\$DT0.QJ1.\$yXqRz6gf/jWC4MzY2D05Y.', 'testplanchiste@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2023-06-17 06:30:44', '2012-01-25 21:00:23', '2023-06-17 04:30:44'),
(59, 8, 'testca', '\$1\$9h..cY3.\$NzkeKkCoSa2oxL7bQCq4v1', 'testca@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:00:58', '2014-12-23 20:38:30'),
(60, 3, 'testbureau', '\$1\$NC0.SN5.\$qwnSUxiPbyh6v2JrhA1fH1', 'testbureau@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2012-01-25 21:03:01', '2012-01-25 21:01:36', '2014-12-23 20:39:00'),
(61, 9, 'testtresorier', '\$1\$KiPMl0ho\$/E3NBaprpM5Xcv.z40zjK0', 'testresorier@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:02:36', '2012-01-25 20:02:36'),
(118, 1, 'asterix', '\$1\$178.XGif\$uv3FdWy4uSb4hURObhQaU1', 'asterix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:07', '2023-06-17 04:32:07'),
(119, 1, 'goudurix', '\$1\$TgWj4h2S\$O.t2stMILkVwqeV5xC/Ky.', 'goudurix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:11', '2023-06-17 04:32:11'),
(120, 1, 'panoramix', '\$1\$Ih02twmD\$BnsuIlxHH62qF41/puKs30', 'panoramix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:16', '2023-06-17 04:32:16'),
(121, 1, 'abraracourcix', '\$1\$B0U6TBCD\$Mcx76FTA.ulT.TO.sX2HZ1', 'abraracourcix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:20', '2023-06-17 04:32:20');

-- Profils utilisateurs
INSERT INTO `user_profile` (`id`, `user_id`, `country`, `website`) VALUES
(120, 118, NULL, NULL),
(121, 119, NULL, NULL),
(122, 120, NULL, NULL),
(123, 121, NULL, NULL);

-- Rôles par section
INSERT INTO `user_roles_per_section` (`id`, `user_id`, `types_roles_id`, `section_id`) VALUES
(1, 15, 1, 1),
(2, 16, 10, 1),
(3, 58, 5, 1),
(4, 59, 6, 1),
(5, 60, 7, 1),
(6, 61, 8, 1),
(7, 118, 1, 1),
(8, 119, 1, 1),
(9, 120, 1, 1),
(10, 121, 1, 1);

-- Plan comptable de base
INSERT INTO `planc` (`pcode`, `pdesc`) VALUES
('102', 'Fonds associatif (sans droit de reprise)'),
('110', 'Report à nouveau (solde créditeur)'),
('119', 'Report à nouveau (solde débiteur)'),
('120', 'Résultat de l\'exercice (excédent)'),
('129', 'Résultat de l\'exercice (déficit)'),
('164', 'Emprunts auprès des établissements de crédit'),
('215', 'Matériel'),
('218', 'Mobilier.'),
('281', 'Amortissement des immobilisations corporelles'),
('371', 'Marchandises'),
('401', 'Fournisseurs'),
('409', 'Fournisseurs débiteurs. Accomptes'),
('411', 'Clients'),
('441', 'Etat - Subventions'),
('46', 'Débiteurs divers et créditeur divers'),
('487', 'Produits constatés d\'avance'),
('512', 'Banque'),
('531', 'Caisse'),
('60', 'Achats'),
('601', 'Achats stockés - Matières premières et fournitures'),
('602', 'Achats stockés - Autres approvisionements'),
('604', 'Achats d\'études et prestations de services'),
('605', 'Achat autres.'),
('606', 'Achats non stockés de matières et fournitures'),
('607', 'Achats de marchandises'),
('61', 'Services extérieurs'),
('611', 'Sous-traitance générale'),
('612', 'Redevances de crédit-bail'),
('613', 'Locations'),
('615', 'Entretien et réparations'),
('616', 'Assurances'),
('62', 'Autres services extérieurs'),
('621', 'Personels extérieur à l\'association'),
('622', 'Rémunérations et Honoraires.'),
('623', 'Publicité, Publications, Relations publiques'),
('624', 'Transport de bien et transport collectif du person'),
('625', 'Déplacement, missions et reception'),
('626', 'Frais postaux et télécommunications'),
('628', 'Divers, cotisations'),
('629', 'Rabais, ristournes, remises sur services extérieur'),
('63', 'Impôts et Taxes'),
('631', 'Impots sur rémunération'),
('635', 'Autres impôts et Taxes.'),
('64', 'Charges de Personnel'),
('65', 'Autres Charges de gestion courante'),
('651', 'Redevance pour concessions, brevets'),
('654', 'Pertes sur créances irrécouvrables'),
('657', 'Subventions versées par l\'association'),
('66', 'Charges financières'),
('67', 'Chages Exceptionnelles'),
('674', 'Autres.'),
('678', 'Autres charges exceptionnelles'),
('68', 'Dotation aux Amortissements'),
('70', 'Ventes'),
('701', 'Ventes de produits finis'),
('706', 'Prestations de services'),
('707', 'Ventes de marchandises'),
('708', 'Produit des activités annexes'),
('74', 'Subventions d\'exploitation'),
('75', 'Autres produits de gestion courante'),
('753', 'Assurances licences FFVV.'),
('754', 'Retour des Fédérations (bourses).'),
('756', 'Cotisations'),
('76', 'Produits financiers'),
('774', 'Autres produits exceptionnels'),
('775', 'Produits des cessions d\'éléments d\'actif'),
('778', 'Autres produits exceptionnels'),
('78', 'Reprise sur amortissements'),
('781', 'Reprises sur amortissements et provisions');

-- Comptes de test
INSERT INTO `comptes` (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES
(292, 'Immobilisations', '', 'Immobilisations', '215', 1, 0.00, 0.00, 'testadmin', 1),
(293, 'Fonds associatifs', '', 'Fonds associatifs', '102', 1, 0.00, 0.00, 'testadmin', 1),
(294, 'Banque', '', 'Banque', '512', 1, 850.47, 152.63, 'testadmin', 1),
(295, 'Emprunt', '', 'Emprunt', '164', 1, 0.00, 0.00, 'testadmin', 1),
(296, 'Atelier de la Somme', '', 'Fournisseur', '401', 1, 350.00, 350.00, 'testadmin', 1),
(297, 'Frais de bureau', '', 'Frais de bureau', '606', 1, 25.50, 0.00, 'testadmin', 1),
(298, 'Essence plus huile', '', 'Essence plus huile', '606', 1, 125.50, 0.00, 'testadmin', 1),
(299, 'Entretien', '', 'Entretien', '615', 1, 350.00, 350.00, 'testadmin', 1),
(300, 'Assurances', '', 'Assurances', '616', 1, 0.00, 0.00, 'testadmin', 1),
(301, 'Heures de vol planeur', '', 'Heures de vol planeur', '706', 1, 0.00, 0.00, 'testadmin', 1),
(302, 'Heures de vol avion', '', 'Heures de vol avion', '706', 1, 0.00, 0.00, 'testadmin', 1),
(303, 'Heures de vol ULM', '', 'Heures de vol ULM', '706', 1, 0.00, 0.00, 'testadmin', 1),
(304, 'Subventions', '', 'Subventions', '74', 1, 0.00, 0.00, 'testadmin', 1),
(305, '(411) Test User', 'testuser', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(306, '(411) Test Admin', 'testadmin', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(307, '(411) Test CA', 'testca', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(308, '(411) Test Bureau', 'testbureau', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(309, 'Boutique', '', 'Boutique', '707', 1, 0.00, 0.00, 'testadmin', 1);

-- Planeurs de test
INSERT INTO `machinesp` (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES
('Alexander Schleicher', 'Ask21', 'F-CGAA', '', 0.00, '2', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('Centrair', 'Pégase', 'F-CGAB', 'EG', 0.00, '1', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('DG', 'DG800', 'F-CGAC', 'AC', 0.00, '1', 0, 0, 0, 1, 'gratuit', 'gratuit', 'gratuit', 180, 1, '', 0, 0, 0, '');

-- Avions remorqueurs de test
INSERT INTO `machinesa` (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES
('Robin', 'DR400', 'F-GUFB', 0.00, 4, 1, 0, 1, 1, '', 'gratuit', 'gratuit', 0, 0),
('Aeropol', 'Dynamic', 'F-JUFA', 0.00, 2, 1, 0, 1, 1, '', 'hdv-ULM', 'hdv-ULM', 0, 0);

-- Terrains
INSERT INTO `terrains` (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES
('LFAY', 'Amiens Glisy', 123.400, 0.000, ''),
('LFEG', 'Argenton Sur Creuse', 123.500, 0.000, ''),
('LFJR', 'Angers', 0.000, 0.000, ''),
('LFLV', 'Vichy', 121.400, 0.000, '253m'),
('LFNC', 'montdauphin saint crepin', 123.500, 123.050, 'alt 903 m'),
('LFOI', 'Abbeville', 123.500, 0.000, ''),
('LFON', 'Dreux', 123.500, 0.000, ''),
('LFQB', 'Troyes - Barberey', 123.725, 0.000, ''),
('LFQO', 'Lille - Marq en Bareuil', 0.000, 0.000, ''),
('LFRI', 'la Roche sur Yon', 0.000, 0.000, ''),
('LFYG', 'Cambrai', 999.999, 0.000, ''),
('LFYR', 'Romorantin', 119.070, 0.000, '');

-- Use new authorization for Gaulois test users only
INSERT INTO `use_new_authorization` (`username`, `created_at`, `notes`) VALUES
('asterix', NOW(), 'Gaulois test user - created by initial schema'),
('obelix', NOW(), 'Gaulois test user - created by initial schema'),
('abraracourcix', NOW(), 'Gaulois test user - created by initial schema'),
('goudurix', NOW(), 'Gaulois test user - created by initial schema'),
('panoramix', NOW(), 'Gaulois test user - created by initial schema');

SQL;

        return $sql;
    }

    /**
     * Create Gaulois test users with full profiles
     * Called after anonymization to add test data with clear names and contact info
     * @return array Result array with 'created' count
     */
    private function _create_test_gaulois_users() {
        $result = array('created' => 0, 'errors' => array());
        
        // Password hash for "password" (MD5 crypt)
        $password_hash = '$1$wu3.3t2.$Wgk43dHPPi3PTv5atdpnz0';
        
        // Role bits from program.php
        $REMORQUEUR = 8192;     // 2**13
        $IVV = 65536;            // 2**16 - Instructeur Vol à Voile (Glider instructor)
        $FI_AVION = 131072;      // 2**17 - Instructeur Vol Avion (Airplane instructor)
        $CA_BIT = 64;            // 2**6 - Conseil d'Administration
        $TRESORIER = 8;          // 2**3
        
        // Section IDs
        $planeur_section = 1;
        $ulm_section = 2;
        $avion_section = 3;
        $general_section = 4;
        
        // Get types_roles IDs from database (hardcode fallbacks)
        $types_roles = array(
            'user' => 1,
            'planchiste' => 5,
            'ca' => 6,
            'tresorier' => 8,
            'instructeur' => 11
        );
        
        // Query for actual role IDs
        $roles_query = $this->db->get('types_roles');
        foreach ($roles_query->result() as $role) {
            $types_roles[$role->nom] = $role->id;
        }
        
        // Test users definition
        $test_users = array(
            array(
                'username' => 'asterix',
                'nom' => 'Asterix',
                'prenom' => 'Le Gaulois',
                'email' => 'asterix@gmail.com',
                'adresse' => '12 rue de Babaorum',
                'cp' => 22000,
                'ville' => 'Village gaulois',
                'sections' => array($planeur_section, $general_section),
                'roles_bits' => 0,
                'is_admin' => 0
            ),
            array(
                'username' => 'obelix',
                'nom' => 'Obelix',
                'prenom' => 'Le Gaulois',
                'email' => 'obelix@gmail.com',
                'adresse' => '27 rue du Menhir',
                'cp' => 22000,
                'ville' => 'Village gaulois',
                'sections' => array($planeur_section, $ulm_section, $general_section),
                'roles_bits' => $REMORQUEUR,
                'is_admin' => 0
            ),
            array(
                'username' => 'abraracourcix',
                'nom' => 'Abraracourcix',
                'prenom' => 'Le Gaulois',
                'email' => 'abraracourcix@gmail.com',
                'adresse' => '3 rue du Menhir',
                'cp' => 22000,
                'ville' => 'Village gaulois',
                'sections' => array($planeur_section, $avion_section, $ulm_section, $general_section),
                'roles_bits' => $REMORQUEUR + $FI_AVION + $CA_BIT,
                'is_admin' => 0
            ),
            array(
                'username' => 'goudurix',
                'nom' => 'Goudurix',
                'prenom' => 'Le Gaulois',
                'email' => 'goudurix@gmail.com',
                'adresse' => '3 rue du Menhir',
                'cp' => 22000,
                'ville' => 'Village gaulois',
                'sections' => array($avion_section, $general_section),
                'roles_bits' => $TRESORIER,
                'is_admin' => 0
            ),
            array(
                'username' => 'panoramix',
                'nom' => 'Panoramix',
                'prenom' => 'Le Gaulois',
                'email' => 'panoramix@gmail.com',
                'adresse' => '1 rue du Menhir',
                'cp' => 22000,
                'ville' => 'Village gaulois',
                'sections' => array(),
                'roles_bits' => 0,
                'is_admin' => 1
            )
        );
        
        // Create each user
        foreach ($test_users as $user_data) {
            try {
                $username = $user_data['username'];

                // Check if user already exists and delete if so
                $existing = $this->db->where('username', $username)->get('users');
                if ($existing->num_rows() > 0) {
                    $user_id = $existing->row()->id;

                    // Delete in correct order to respect foreign key constraints
                    // 1. Delete from use_new_authorization
                    $this->db->where('username', $username)->delete('use_new_authorization');

                    // 2. Delete from user_roles_per_section (FK to users)
                    $this->db->where('user_id', $user_id)->delete('user_roles_per_section');

                    // 3. Delete from comptes (411 accounts)
                    $this->db->where('pilote', $username)->where('codec', 411)->delete('comptes');

                    // 4. Delete from membres
                    $this->db->where('username', $username)->delete('membres');

                    // 5. Delete from user_profile (FK to users)
                    $this->db->where('user_id', $user_id)->delete('user_profile');

                    // 6. Delete from users (last, as other tables reference it)
                    $this->db->where('id', $user_id)->delete('users');

                    log_message('info', "Deleted existing Gaulois user: $username");
                }

                // 1. Create user in users table
                $user_insert = array(
                    'role_id' => 1,
                    'username' => $username,
                    'password' => $password_hash,
                    'email' => $user_data['email'],
                    'banned' => 0,
                    'last_ip' => '127.0.0.1',
                    'last_login' => date('Y-m-d H:i:s'),
                    'created' => date('Y-m-d H:i:s')
                );
                $this->db->insert('users', $user_insert);
                $user_id = $this->db->insert_id();
                
                // 2. Create membre entry
                $membre_insert = array(
                    'mlogin' => $username,
                    'mnom' => $user_data['nom'],
                    'mprenom' => $user_data['prenom'],
                    'memail' => $user_data['email'],
                    'madresse' => $user_data['adresse'],
                    'cp' => $user_data['cp'],
                    'ville' => $user_data['ville'],
                    'pays' => 'France',
                    'msexe' => 'M',
                    'mniveaux' => $user_data['roles_bits'],
                    'macces' => 0,
                    'club' => 0,
                    'ext' => 0,
                    'actif' => 1,
                    'username' => $username,
                    'categorie' => '0'
                );
                $this->db->insert('membres', $membre_insert);
                
                // 3. Create 411 accounts for each section
                foreach ($user_data['sections'] as $section_id) {
                    $compte_insert = array(
                        'nom' => '(411) ' . $user_data['nom'] . ' ' . $user_data['prenom'],
                        'pilote' => $username,
                        'desc' => 'Compte client 411 ' . $user_data['nom'] . ' ' . $user_data['prenom'],
                        'codec' => 411,
                        'actif' => 1,
                        'debit' => 0.0,
                        'credit' => 0.0,
                        'club' => $section_id,
                        'saisie_par' => 'admin'
                    );
                    
                    // Check if account already exists for this section
                    $existing_compte = $this->db->where('pilote', $username)
                                               ->where('codec', 411)
                                               ->where('club', $section_id)
                                               ->get('comptes');
                    if ($existing_compte->num_rows() === 0) {
                        $this->db->insert('comptes', $compte_insert);
                    }
                }
                
                // 4. Create user roles per section
                foreach ($user_data['sections'] as $section_id) {
                    $section_roles = array();
                    
                    // Always add 'user' role
                    $section_roles[] = $types_roles['user'];
                    
                    // CA role applies to all sections
                    if ($user_data['roles_bits'] & $CA_BIT) {
                        $section_roles[] = $types_roles['ca'];
                    }
                    
                    // Treasurer role applies to all sections
                    if ($user_data['roles_bits'] & $TRESORIER) {
                        $section_roles[] = $types_roles['tresorier'];
                    }
                    
                    // Instructor roles for specific sections
                    if ($user_data['roles_bits'] & $FI_AVION && $section_id == $avion_section) {
                        $section_roles[] = $types_roles['instructeur'];
                    }
                    
                    // Remorqueur (tow pilot) for avion section
                    if ($user_data['roles_bits'] & $REMORQUEUR && $section_id == $avion_section) {
                        $section_roles[] = $types_roles['instructeur'];  // Use instructeur as tow pilot marker
                    }
                    
                    // Insert unique roles
                    $section_roles = array_unique($section_roles);
                    foreach ($section_roles as $types_role_id) {
                        $role_insert = array(
                            'user_id' => $user_id,
                            'types_roles_id' => $types_role_id,
                            'section_id' => $section_id,
                            'granted_at' => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('user_roles_per_section', $role_insert);
                    }
                }

                // 5. Add to new authorization system
                $auth_insert = array(
                    'username' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'notes' => 'Gaulois test user - created by generate_test_database'
                );
                $this->db->insert('use_new_authorization', $auth_insert);

                $result['created']++;
                
            } catch (Exception $e) {
                $result['errors'][] = "Erreur création {$user_data['username']}: " . $e->getMessage();
                log_message('error', "Failed to create Gaulois user {$user_data['username']}: " . $e->getMessage());
            }
        }
        
        return $result;
    }

    /**
     * Create legacy test users (testuser, testadmin, etc.) with known state
     * These users use the legacy DX_Auth authorization system
     * Called by generate_test_database to ensure consistent test environment
     * @return array Result array with 'created' count
     */
    private function _create_test_legacy_users() {
        $result = array('created' => 0, 'errors' => array());

        // Get default section ID (usually 1 = Planeur)
        $section_query = $this->db->select('id')->limit(1)->get('sections');
        if ($section_query->num_rows() === 0) {
            $result['errors'][] = 'No sections found in database';
            return $result;
        }
        $default_section = $section_query->row()->id;

        // Get types_roles IDs for new authorization system
        $types_roles = array(
            'user' => 1,
            'planchiste' => 5,
            'ca' => 6,
            'bureau' => 7,
            'tresorier' => 8,
            'club-admin' => 10
        );

        // Query for actual role IDs
        $roles_query = $this->db->get('types_roles');
        foreach ($roles_query->result() as $role) {
            $types_roles[$role->nom] = $role->id;
        }

        // Test users definition
        // IMPORTANT: These users use LEGACY authorization (DX_Auth) and should NOT be in use_new_authorization table
        $test_users = array(
            array(
                'username' => 'testuser',
                'password_hash' => '$1$wu3.3t2.$Wgk43dHPPi3PTv5atdpnz0',
                'email' => 'testuser@free.fr',
                'role_id' => 1,  // Legacy: membre
                'types_roles_id' => $types_roles['user']  // New: user
            ),
            array(
                'username' => 'testadmin',
                'password_hash' => '$1$uM1.f95.$AnUHH1W/xLS9fxDbt8RPo0',
                'email' => 'frederic.peignot@free.fr',
                'role_id' => 2,  // Legacy: admin
                'types_roles_id' => $types_roles['club-admin']  // New: club-admin
            ),
            array(
                'username' => 'testplanchiste',
                'password_hash' => '$1$DT0.QJ1.$yXqRz6gf/jWC4MzY2D05Y.',
                'email' => 'testplanchiste@free.fr',
                'role_id' => 7,  // Legacy: planchiste
                'types_roles_id' => $types_roles['planchiste']  // New: planchiste
            ),
            array(
                'username' => 'testca',
                'password_hash' => '$1$9h..cY3.$NzkeKkCoSa2oxL7bQCq4v1',
                'email' => 'testca@free.fr',
                'role_id' => 8,  // Legacy: ca
                'types_roles_id' => $types_roles['ca']  // New: ca
            ),
            array(
                'username' => 'testbureau',
                'password_hash' => '$1$NC0.SN5.$qwnSUxiPbyh6v2JrhA1fH1',
                'email' => 'testbureau@free.fr',
                'role_id' => 3,  // Legacy: bureau
                'types_roles_id' => $types_roles['bureau']  // New: bureau
            ),
            array(
                'username' => 'testtresorier',
                'password_hash' => '$1$8XMCm61f$CS0gO5YjH.xHm2ZyaZNQt/',
                'email' => 'testresorier@free.fr',
                'role_id' => 9,  // Legacy: tresorier
                'types_roles_id' => $types_roles['tresorier']  // New: tresorier
            )
        );

        // Create each user
        foreach ($test_users as $user_data) {
            try {
                $username = $user_data['username'];

                // Check if user already exists and delete if so
                $existing = $this->db->where('username', $username)->get('users');
                if ($existing->num_rows() > 0) {
                    $user_id = $existing->row()->id;

                    // Delete in correct order to respect foreign key constraints
                    // NOTE: Don't delete from use_new_authorization as these users should NOT be there

                    // 1. Delete from user_roles_per_section (FK to users)
                    $this->db->where('user_id', $user_id)->delete('user_roles_per_section');

                    // 2. Delete from comptes (411 accounts)
                    $this->db->where('pilote', $username)->where('codec', 411)->delete('comptes');

                    // 3. Delete from membres
                    $this->db->where('username', $username)->delete('membres');

                    // 4. Delete from user_profile (FK to users)
                    $this->db->where('user_id', $user_id)->delete('user_profile');

                    // 5. Delete from users (last, as other tables reference it)
                    $this->db->where('id', $user_id)->delete('users');

                    log_message('info', "Deleted existing legacy test user: $username");
                }

                // 1. Create user in users table with LEGACY role_id
                $user_insert = array(
                    'role_id' => $user_data['role_id'],  // IMPORTANT: Legacy role for DX_Auth
                    'username' => $username,
                    'password' => $user_data['password_hash'],  // All have password "password"
                    'email' => $user_data['email'],
                    'banned' => 0,
                    'last_ip' => '127.0.0.1',
                    'last_login' => date('Y-m-d H:i:s'),
                    'created' => date('Y-m-d H:i:s')
                );
                $this->db->insert('users', $user_insert);
                $user_id = $this->db->insert_id();

                // 2. Create membre entry (if not admin - admins might not be in membres)
                if ($user_data['role_id'] != 2) {  // Not admin
                    $membre_insert = array(
                        'mlogin' => $username,
                        'mnom' => ucfirst($username),
                        'mprenom' => 'Test',
                        'memail' => $user_data['email'],
                        'madresse' => '1 rue de Test',
                        'cp' => 75000,
                        'ville' => 'Paris',
                        'pays' => 'France',
                        'msexe' => 'M',
                        'mniveaux' => 0,
                        'macces' => 0,
                        'club' => 0,
                        'ext' => 0,
                        'actif' => 1,
                        'username' => $username,
                        'categorie' => '0'
                    );
                    $this->db->insert('membres', $membre_insert);
                }

                // 3. Create 411 account in default section (if not admin)
                if ($user_data['role_id'] != 2) {  // Not admin
                    $compte_insert = array(
                        'nom' => '(411) ' . ucfirst($username),
                        'pilote' => $username,
                        'desc' => 'Compte client 411 ' . ucfirst($username),
                        'codec' => 411,
                        'actif' => 1,
                        'debit' => 0.0,
                        'credit' => 0.0,
                        'club' => $default_section,
                        'saisie_par' => 'testadmin'
                    );
                    $this->db->insert('comptes', $compte_insert);
                }

                // 4. Create user roles per section for new authorization system
                // Even though these users use legacy auth, we create the roles
                // so they can be migrated to new system if needed
                $role_insert = array(
                    'user_id' => $user_id,
                    'types_roles_id' => $user_data['types_roles_id'],
                    'section_id' => $default_section,
                    'granted_at' => date('Y-m-d H:i:s')
                );
                $this->db->insert('user_roles_per_section', $role_insert);

                $result['created']++;
                log_message('info', "Created legacy test user: $username (role_id={$user_data['role_id']})");

            } catch (Exception $e) {
                $result['errors'][] = "Erreur création {$user_data['username']}: " . $e->getMessage();
                log_message('error', "Failed to create legacy test user {$user_data['username']}: " . $e->getMessage());
            }
        }

        return $result;
    }

}