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
 * @filesource File_manager.php
 * @package libraries
 * Librairie de gestion des fichiers pour attachments et procédures
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Librairie de gestion des fichiers
 * 
 * Fournit des fonctionnalités communes pour la gestion des fichiers
 * dans GVV (attachments, procédures, etc.)
 */
class File_manager {

    private $CI;
    private $base_upload_path;
    private $allowed_types;
    private $max_file_size;
    private $create_thumbs;

    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->CI =& get_instance();
        
        // Configuration par défaut
        $default_config = array(
            'base_upload_path' => './uploads/',
            'allowed_types' => 'pdf|doc|docx|txt|md|jpg|jpeg|png|gif',
            'max_file_size' => 10240, // 10MB en KB
            'create_thumbs' => false
        );
        
        // Fusionner avec la configuration fournie
        $this->init(array_merge($default_config, $config));
    }
    
    /**
     * Initialiser la configuration
     */
    public function init($config) {
        $this->base_upload_path = rtrim($config['base_upload_path'], '/') . '/';
        $this->allowed_types = $config['allowed_types'];
        $this->max_file_size = $config['max_file_size'];
        $this->create_thumbs = $config['create_thumbs'];
        
        // Charger les librairies nécessaires
        $this->CI->load->library('upload');
        $this->CI->load->helper('file');
        $this->CI->load->helper('directory');
    }

    /**
     * Upload un fichier vers un dossier spécifique
     * 
     * @param string $sub_directory Sous-dossier de destination
     * @param string $file_field Nom du champ fichier dans le formulaire
     * @param array $additional_config Configuration additionnelle upload
     * @return array Résultat de l'upload avec statut et informations
     */
    public function upload_file($sub_directory, $file_field = 'file', $additional_config = array()) {
        gvv_debug("procedure: File_manager->upload_file called. Sub-directory: {$sub_directory}, Field: {$file_field}");
        $upload_path = $this->base_upload_path . trim($sub_directory, '/') . '/';
        gvv_debug("procedure: Full upload path: {$upload_path}");
        
        // Créer le dossier s'il n'existe pas
        if (!$this->ensure_directory_exists($upload_path)) {
            gvv_debug("procedure: Failed to ensure directory exists: {$upload_path}");
            return array(
                'success' => false,
                'error' => 'Impossible de créer le dossier de destination'
            );
        }
        gvv_debug("procedure: Directory ensured: {$upload_path}");
        
        // Configuration de l'upload
        $upload_config = array(
            'upload_path' => $upload_path,
            'allowed_types' => $this->allowed_types,
            'max_size' => $this->max_file_size,
            'encrypt_name' => false, // Garder le nom original par défaut
            'remove_spaces' => true,
            'overwrite' => false
        );
        
        // Fusionner avec la configuration additionnelle
        $upload_config = array_merge($upload_config, $additional_config);
        gvv_debug("procedure: Final upload config: " . var_export($upload_config, true));
        
        $this->CI->upload->initialize($upload_config);
        
        if (!$this->CI->upload->do_upload($file_field)) {
            $error = $this->CI->upload->display_errors('', '');
            gvv_debug("procedure: CodeIgniter upload->do_upload failed. Error: " . $error);
            return array(
                'success' => false,
                'error' => $error
            );
        }
        
        $upload_data = $this->CI->upload->data();
        gvv_debug("procedure: Upload successful. Data: " . var_export($upload_data, true));
        
        // Créer une miniature si demandé et si c'est une image
        if ($this->create_thumbs && $this->is_image($upload_data['file_ext'])) {
            $this->create_thumbnail($upload_data);
        }
        
        return array(
            'success' => true,
            'data' => $upload_data,
            'relative_path' => $sub_directory . '/' . $upload_data['file_name'],
            'full_path' => $upload_data['full_path']
        );
    }
    
    /**
     * Supprimer un fichier
     * 
     * @param string $file_path Chemin relatif du fichier
     * @return bool Succès de la suppression
     */
    public function delete_file($file_path) {
        $full_path = $this->base_upload_path . ltrim($file_path, '/');
        
        if (!file_exists($full_path)) {
            return false;
        }
        
        // Supprimer la miniature si elle existe
        $thumb_path = $this->get_thumbnail_path($full_path);
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        
        return unlink($full_path);
    }
    
    /**
     * Lister les fichiers d'un dossier
     * 
     * @param string $sub_directory Sous-dossier à lister
     * @param string $file_pattern Pattern de fichiers (ex: "*.md")
     * @return array Liste des fichiers avec informations
     */
    public function list_files($sub_directory, $file_pattern = '*') {
        $directory_path = $this->base_upload_path . trim($sub_directory, '/') . '/';
        
        if (!is_dir($directory_path)) {
            return array();
        }
        
        $files = glob($directory_path . $file_pattern);
        $file_list = array();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_info = array(
                    'name' => basename($file),
                    'path' => str_replace($this->base_upload_path, '', $file),
                    'size' => filesize($file),
                    'size_human' => $this->human_filesize(filesize($file)),
                    'modified' => filemtime($file),
                    'modified_human' => date('d/m/Y H:i', filemtime($file)),
                    'extension' => pathinfo($file, PATHINFO_EXTENSION),
                    'is_image' => $this->is_image(pathinfo($file, PATHINFO_EXTENSION))
                );
                
                // Ajouter le chemin de la miniature si elle existe
                if ($file_info['is_image']) {
                    $thumb_path = $this->get_thumbnail_path($file);
                    if (file_exists($thumb_path)) {
                        $file_info['thumbnail'] = str_replace($this->base_upload_path, '', $thumb_path);
                    }
                }
                
                $file_list[] = $file_info;
            }
        }
        
        return $file_list;
    }
    
    /**
     * Créer un dossier s'il n'existe pas
     * 
     * @param string $directory_path Chemin du dossier
     * @return bool Succès de la création
     */
    public function ensure_directory_exists($directory_path) {
        if (!is_dir($directory_path)) {
            return mkdir($directory_path, 0755, true);
        }
        return true;
    }
    
    /**
     * Valider un fichier avant upload
     * 
     * @param array $file_info Informations du fichier ($_FILES)
     * @return array Résultat de la validation
     */
    public function validate_file($file_info) {
        $errors = array();
        
        // Vérifier la taille
        if ($file_info['size'] > ($this->max_file_size * 1024)) {
            $errors[] = 'Fichier trop volumineux (max: ' . $this->max_file_size . 'KB)';
        }
        
        // Vérifier le type
        $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $allowed_extensions = explode('|', $this->allowed_types);
        if (!in_array(strtolower($extension), $allowed_extensions)) {
            $errors[] = 'Type de fichier non autorisé. Types acceptés: ' . str_replace('|', ', ', $this->allowed_types);
        }
        
        // Vérifier l'erreur d'upload
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->get_upload_error_message($file_info['error']);
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Obtenir le contenu d'un fichier texte
     * 
     * @param string $file_path Chemin relatif du fichier
     * @return string|false Contenu du fichier ou false si erreur
     */
    public function get_file_content($file_path) {
        $full_path = $this->base_upload_path . ltrim($file_path, '/');
        
        if (!file_exists($full_path)) {
            return false;
        }
        
        return file_get_contents($full_path);
    }
    
    /**
     * Sauvegarder du contenu dans un fichier
     * 
     * @param string $file_path Chemin relatif du fichier
     * @param string $content Contenu à sauvegarder
     * @return bool Succès de la sauvegarde
     */
    public function save_file_content($file_path, $content) {
        $full_path = $this->base_upload_path . ltrim($file_path, '/');
        
        // Créer le dossier si nécessaire
        $directory = dirname($full_path);
        if (!$this->ensure_directory_exists($directory)) {
            return false;
        }
        
        return file_put_contents($full_path, $content) !== false;
    }
    
    /**
     * Vérifier si un fichier est une image
     * 
     * @param string $extension Extension du fichier
     * @return bool True si c'est une image
     */
    private function is_image($extension) {
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
        return in_array(strtolower($extension), $image_extensions);
    }
    
    /**
     * Créer une miniature d'image
     * 
     * @param array $upload_data Données de l'upload
     * @return bool Succès de la création
     */
    private function create_thumbnail($upload_data) {
        $this->CI->load->library('image_lib');
        
        $config = array(
            'source_image' => $upload_data['full_path'],
            'new_image' => dirname($upload_data['full_path']) . '/thumb_' . $upload_data['file_name'],
            'create_thumb' => false,
            'maintain_ratio' => true,
            'width' => 150,
            'height' => 150
        );
        
        $this->CI->image_lib->initialize($config);
        
        if (!$this->CI->image_lib->resize()) {
            gvv_debug('Erreur création miniature: ' . $this->CI->image_lib->display_errors());
            return false;
        }
        
        $this->CI->image_lib->clear();
        return true;
    }
    
    /**
     * Obtenir le chemin de la miniature
     * 
     * @param string $file_path Chemin du fichier original
     * @return string Chemin de la miniature
     */
    private function get_thumbnail_path($file_path) {
        $dir = dirname($file_path);
        $filename = basename($file_path);
        return $dir . '/thumb_' . $filename;
    }
    
    /**
     * Convertir une taille de fichier en format lisible
     * 
     * @param int $bytes Taille en bytes
     * @return string Taille formatée
     */
    private function human_filesize($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Obtenir le message d'erreur d'upload
     * 
     * @param int $error_code Code d'erreur PHP
     * @return string Message d'erreur
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le serveur';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le formulaire';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier n\'a été que partiellement téléchargé';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été téléchargé';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Impossible d\'écrire le fichier sur le disque';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload arrêté par une extension PHP';
            default:
                return 'Erreur inconnue lors de l\'upload';
        }
    }
    
    /**
     * Nettoyer un nom de fichier
     * 
     * @param string $filename Nom de fichier à nettoyer
     * @return string Nom de fichier nettoyé
     */
    public function sanitize_filename($filename) {
        // Remplacer les espaces par des underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Supprimer les caractères spéciaux
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Limiter la longueur
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 96 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }
}