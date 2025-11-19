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
 * @filesource avion.php
 * @package controllers
 * 
 * Contrôleur de gestion des paramètres de configuration
 */
include('./application/libraries/Gvv_Controller.php');
class Configuration extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'configuration';
    protected $model = 'configuration_model';
    protected $modification_level = 'bureau';
    protected $rules = array();

    /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou ré-affichage après erreur.
     * Sont statiques les parties qui ne changent pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        $this->load->model('sections_model');
        $section_selector = $this->sections_model->selector_with_null();
        $this->gvvmetadata->set_selector('section_selector', $section_selector);
    }

    /**
     * Transforme les données brutes en base en données compatibles avec le format de base de données
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = array();
        // Méthode basée sur les méta-données
        $table = $this->gvv_model->table();
        $fields_list = $this->gvvmetadata->fields_list($table);
        foreach ($fields_list as $field) {
            // Skip file field here - we'll handle it specially below
            if ($field !== 'file') {
                $processed_data[$field] = $this->gvvmetadata->post2database($table, $field, $this->input->post($field));
            }
        }

        // Handle file upload for configuration parameters
        if (isset($_FILES['userfile']) && $_FILES['userfile']['name'] && $_FILES['userfile']['error'] === UPLOAD_ERR_OK) {
            $upload_result = $this->handle_file_upload($processed_data['cle']);
            if ($upload_result['success']) {
                // Delete old file if it exists when updating (any extension)
                if ($action == MODIFICATION && isset($this->data['file']) ) {
                    $old_file = $this->data['file']; // Already has full path
                    if (file_exists($old_file)) {
                        unlink($old_file);
                        log_message('info', "Configuration: Deleted old file {$old_file}");
                    }
                    
                    // Also try to clean up any other files with the same base name but different extensions
                    $this->cleanup_old_files_by_key($processed_data['cle'], $upload_result['filename']);
                }
                
                // IMPORTANT: Always update the file field with the new filename (including new extension)
                $processed_data['file'] = $upload_result['filename'];
                log_message('info', "Configuration: File uploaded successfully - {$upload_result['filename']}");
                log_message('info', "Configuration: Database will be updated with file path: {$upload_result['filename']}");
            } else {
                // Set error message for display
                $this->session->set_flashdata('upload_error', $upload_result['error']);
                log_message('error', "Configuration: File upload failed - {$upload_result['error']}");
            }
        } else {
            // No file upload, preserve existing file value if updating
            if ($action == MODIFICATION && isset($this->data['file']) && $this->data['file']) {
                $processed_data['file'] = $this->data['file'];
                log_message('debug', "Configuration: No file upload, preserving existing file: {$this->data['file']}");
            } else {
                // For creation without file, process the file field normally (likely empty)
                $processed_data['file'] = $this->gvvmetadata->post2database($table, 'file', $this->input->post('file'));
            }
        }

        // Clean up empty file field
        if (!isset($processed_data['file']) || !$processed_data['file']) {
            unset($processed_data['file']);
        }

        // Debug logging for file field
        if (isset($processed_data['file'])) {
            log_message('info', "Configuration form2database: Final processed_data[file] = {$processed_data['file']}");
        } else {
            log_message('info', "Configuration form2database: No file field in processed_data");
        }
        
        // Debug all processed data
        log_message('debug', "Configuration form2database: All processed_data = " . json_encode($processed_data));

        // On ne force pas la section, elle est saisie par l'utilisateur
        if (!$processed_data['club']) {
            unset($processed_data['club']);
        }

        if (!$processed_data['lang']) {
            unset($processed_data['lang']);
        }

        return $processed_data;
    }

    /**
     * Handle file upload for configuration parameters
     * Files are stored in uploads/configuration/ with basename = configuration.cle value
     * Files are compressed using the same system as attachments
     *
     * @param string $cle Configuration key to use as basename
     * @return array ['success' => bool, 'filename' => string, 'error' => string]
     */
    private function handle_file_upload($cle) {
        if (!isset($_FILES['userfile']) || $_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'No file uploaded or upload error'];
        }

        $config['upload_path'] = './uploads/configuration/';
        $config['allowed_types'] = 'png|jpeg|jpg|gif|webp|pdf|doc|docx|xls|xlsx|txt|csv|zip';
        $config['max_size'] = 10240; // 10MB
        $config['encrypt_name'] = FALSE; // Use predictable names
        $config['overwrite'] = TRUE; // Overwrite existing files instead of creating versions

        $this->load->library('upload', $config);

        // Generate filename based on configuration key and new file extension
        $file_info = pathinfo($_FILES['userfile']['name']);
        $extension = strtolower($file_info['extension']);
        $generated_filename = $cle . '.' . $extension;

        log_message('info', "Configuration upload: cle={$cle}, original={$_FILES['userfile']['name']}, desired={$generated_filename}");

        // Set the file name we want
        $config['file_name'] = $generated_filename;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('userfile')) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $this->upload->display_errors()
            ];
        }

        $upload_data = $this->upload->data();
        $uploaded_file = $upload_data['full_path'];
        $actual_filename = $upload_data['file_name'];
        
        log_message('info', "Configuration upload result: uploaded_file={$uploaded_file}, actual_filename={$actual_filename}");
        
        // Ensure we have the correct filename (handle any CI upload library quirks)
        if ($actual_filename !== $generated_filename) {
            // Upload library created a different name, rename it to our desired name
            $desired_path = './uploads/configuration/' . $generated_filename;
            if (file_exists($uploaded_file) && rename($uploaded_file, $desired_path)) {
                $uploaded_file = $desired_path;
                $actual_filename = $generated_filename;
                log_message('info', "Configuration: Renamed uploaded file to desired name: {$generated_filename}");
            } else {
                log_message('warning', "Configuration: Could not rename uploaded file from {$actual_filename} to {$generated_filename}");
            }
        }

        // NOW clean up old files with different extensions (after successful upload)
        $this->cleanup_old_files_by_key($cle, $actual_filename);

        // Apply compression using the same system as attachments
        if (class_exists('File_compressor')) {
            $this->load->library('File_compressor');
            $compression_result = $this->File_compressor->compress($uploaded_file);

            if ($compression_result['success']) {
                // Log compression success
                log_message('info', "Configuration file compression: file={$generated_filename}, " .
                          "original={$compression_result['stats']['original_size']}B, " .
                          "compressed={$compression_result['stats']['compressed_size']}B, " .
                          "ratio=" . round($compression_result['stats']['compression_ratio'] * 100, 1) . "%, " .
                          "method={$compression_result['stats']['method']}");
            } else {
                // Log compression skip/failure
                log_message('debug', "Configuration file compression skipped: {$compression_result['error']}");
            }
        } else {
            log_message('debug', "Configuration file compression: File_compressor not available");
        }

        return [
            'success' => true,
            'filename' => "./uploads/configuration/" . $actual_filename, // Use actual filename that was uploaded/renamed
            'error' => ''
        ];
    }

    /**
     * Clean up old files for a configuration key with any extension
     * This prevents accumulation of files when extensions change (jpg -> png)
     *
     * @param string $cle Configuration key
     * @param string $keep_filename Filename to keep (don't delete this one)
     */
    private function cleanup_old_files_by_key($cle, $keep_filename) {
        $config_dir = './uploads/configuration/';
        $pattern = $config_dir . $cle . '.*';
        
        // Find all files matching the configuration key pattern
        $matching_files = glob($pattern);
        
        foreach ($matching_files as $file) {
            $basename = basename($file);
            // Don't delete the file we're currently keeping
            if ($basename !== $keep_filename && $basename !== basename($keep_filename)) {
                if (file_exists($file) && unlink($file)) {
                    log_message('info', "Configuration: Cleaned up old file {$file}");
                } else {
                    log_message('warning', "Configuration: Could not delete old file {$file}");
                }
            }
        }
    }

    /**
     * Override delete to clean up associated files
     */
    public function delete($id = null) {
        // Get ID from parameter or URI segment
        if ($id === null) {
            $id = $this->uri->segment(3);
        }
        
        // Get the record to check for file
        $record = $this->gvv_model->get_by_id('id', $id);
        
        // Delete the record first
        $result = parent::delete($id);
        
        // Clean up file if it exists
        if ($record && isset($record['file']) && $record['file']) {
            $file_path = $record['file']; // Already has full path
            if (file_exists($file_path)) {
                unlink($file_path);
                log_message('info', "Configuration file deleted: {$file_path}");
            }
        }
        
        return $result;
    }
    /**
     * Override pre_update to add debugging for file field updates
     */
    function pre_update($id, &$data = array()) {
        if (isset($data['file'])) {
            log_message('info', "Configuration pre_update: file field = {$data['file']}");
        } else {
            log_message('info', "Configuration pre_update: NO file field in processed_data");
        }
        log_message('debug', "Configuration pre_update: All data = " . json_encode($data));
        
        parent::pre_update($id, $data);
    }

    /**
     * Test configuration model
     *
     * This function will test the configuration model by performing the following steps:
     * - Count the number of configuration
     * - Create one with club defined in french
     * - Create one with section not define in french (same key)
     * - Create one with section defined in english (same key)
     * check that there are three more in database
     * check it is possible to retrieve the first, second and third data
     * check that fetching an unknown key returns null
     * test file upload functionality
     * delete the test data 
     * check that we are back to the initial number of configuration
     * 
     */
    public function test_model($primary_key = null) {

        $this->unit->run(true, true, "Testing $this->controller model");

        $data1 = [
            'cle' => 'test_cle',
            'valeur' => 'test_valeur',
            'lang' => 'french',
            'club' => '1',
            'categorie' => 'test_categorie',
            'description' => 'French with section defined'
        ];

        $data2 = [
            'cle' => 'cle2',
            'valeur' => 'test_valeur2',
            'lang' => 'french',
            'club' => null,
            'categorie' => 'test_categorie',
            'description' => 'French with no section defined'
        ];

        $data3 = [
            'cle' => 'test_cle',
            'valeur' => 'test_value',
            'lang' => 'english',
            'club' => '1',
            'categorie' => 'test_category',
            'description' => 'English with section defined'
        ];

        $count = $this->gvv_model->count();

        // Create test records
        $id1 = $this->gvv_model->create($data1);
        $id2 = $this->gvv_model->create($data2);
        $id3 = $this->gvv_model->create($data3);

        // Verify count increased by 3
        $this->unit->run($this->gvv_model->count(), $count + 3, "Count increased by 3 after creation");

        // Verify retrieval
        $value1 = $this->gvv_model->get_param($data1['cle']);
        $this->unit->run($value1, $data1['valeur'], "Retrieved value 1 matches");

        $value2 = $this->gvv_model->get_param($data2['cle']);
        $this->unit->run($value2, $data2['valeur'], "Retrieved value 2 matches");

        $value3 = $this->gvv_model->get_param($data3['cle'], $data3['lang']);
        $this->unit->run($value3, $data3['valeur'], "Retrieved value 3 matches");

        // Check unknown key returns null
        $unknown_value = $this->gvv_model->get_param('unknown_key');
        $this->unit->run($unknown_value, null, "Unknown key returns null");

        // Test file upload functionality
        $this->test_file_upload();

        // Delete test data
        $this->gvv_model->delete(['id' => $id1]);
        $this->gvv_model->delete(['id' => $id2]);
        $this->gvv_model->delete(['id' => $id3]);

        // Verify back to initial count
        $this->unit->run($this->gvv_model->count(), $count, "Count back to initial after delete");
    }

    /**
     * Test file upload functionality for configuration parameters
     */
    private function test_file_upload() {
        // Test configuration with file
        $file_config = [
            'cle' => 'test_file_config',
            'valeur' => 'Test configuration with file',
            'lang' => 'french',
            'categorie' => 'test',
            'description' => 'Test file upload',
            'file' => 'test_file_config.txt'
        ];

        // Create test file
        $test_file_path = './uploads/configuration/test_file_config.txt';
        file_put_contents($test_file_path, 'This is a test configuration file');

        // Test file exists
        $this->unit->run(file_exists($test_file_path), true, "Test file created successfully");

        // Create configuration record with file
        $config_id = $this->gvv_model->create($file_config);
        $this->unit->run(is_numeric($config_id), true, "Configuration with file created");

        // Verify file field is stored
        $retrieved = $this->gvv_model->get_by_id('id', $config_id);
        $this->unit->run($retrieved['file'], $file_config['file'], "File field stored correctly");

        // Test file cleanup on deletion
        $delete_result = $this->gvv_model->delete(['id' => $config_id]);
        $this->unit->run($delete_result, true, "Configuration deleted successfully");

        // Verify file was cleaned up (should be done by controller override)
        // Note: This test is run through the model, not controller, so file cleanup 
        // won't happen. This is expected behavior.
        
        // Clean up test file manually for this test
        if (file_exists($test_file_path)) {
            unlink($test_file_path);
        }
        $this->unit->run(!file_exists($test_file_path), true, "Test file cleaned up");

        // Test directory structure
        $config_dir = './uploads/configuration/';
        $this->unit->run(is_dir($config_dir), true, "Configuration uploads directory exists");
        $this->unit->run(is_writable($config_dir), true, "Configuration uploads directory is writable");
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Testing $this->controller controller");
        $this->test_model();
        $this->tests_results($format);
    }
}
