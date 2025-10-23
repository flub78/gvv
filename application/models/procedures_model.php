<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Procedures
 *
 *  CRUD pour la gestion des procédures avec support markdown
 *  et fichiers attachés. Étend Common_Model pour les fonctionnalités de base.
 */
class Procedures_model extends Common_Model {
    public $table = 'procedures';
    protected $primary_key = 'id';
    public $error = ''; // To store error messages

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('File_manager');
    }

    /**
     *	Retourne le tableau utilisé pour l'affichage par page
     *	@return objet La liste des procédures
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        // Build a join with sections to also retrieve the section name
        $this->db->select($this->table . '.id, ' . $this->table . '.name, ' . $this->table . '.title, ' . 
                         $this->table . '.description, ' . $this->table . '.status, ' . $this->table . '.version, ' .
                         $this->table . '.created_at, ' . $this->table . '.updated_at, ' . $this->table . '.created_by, ' .
                         'sections.nom as section_name, sections.acronyme as section_acronym');
        $this->db->from($this->table);
        $this->db->join('sections', $this->table . '.section_id = sections.id', 'left');
        
        if (!empty($selection)) {
            $this->db->where($selection);
        }
        
        // Filtrer par section si l'utilisateur a une section spécifique
        if ($this->section) {
            $this->db->where('(' . $this->table . '.section_id = ' . $this->section_id . ' OR ' . $this->table . '.section_id IS NULL)');
        }
        
        // Ordre par titre
        $this->db->order_by($this->table . '.title', 'ASC');
        
        if ($per_page > 0) {
            $this->db->limit($per_page, $premier);
        }

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        // Ajouter des informations sur les fichiers pour chaque procédure
        foreach ($select as $key => $procedure) {
            $select[$key]['has_markdown'] = $this->has_markdown_file($procedure['name']);
            $select[$key]['attachments_count'] = $this->count_attachments($procedure['name']);
            $select[$key]['markdown_file_path'] = $this->get_markdown_file_path($procedure['name']);
        }

        return $select;
    }

    /**
     * Créer une nouvelle procédure avec sa structure de fichiers
     * 
     * @param array $data Données de la procédure
     * @return int|false ID de la procédure créée ou false si erreur
     */
    public function create_procedure($data, $is_uploading_md = false) {
        // Valider le nom (unique, alphanumerique + underscore)
        if (!$this->validate_procedure_name($data['name'])) {
            $this->error = 'Le nom de la procédure est invalide ou déjà utilisé.';
            return false;
        }
        
        // Ajouter les timestamps et l'utilisateur
        $data['created_by'] = $this->dx_auth->get_username();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $data['created_by'];
        
        // Insérer en base
        $procedure_id = $this->create($data);
        
        if ($procedure_id) {
            // Créer la structure de dossiers
            if (!$this->create_procedure_directory($data['name'])) {
                $this->delete(array('id' => $procedure_id));
                $this->error = 'Impossible de créer le dossier de la procédure. Vérifiez les permissions du dossier uploads/procedures.';
                return false;
            }
            
            // Créer un fichier markdown initial uniquement si aucun fichier n'est uploadé
            if (!$is_uploading_md) {
                if (!$this->create_initial_markdown_file($data)) {
                    $this->delete_procedure_directory($data['name']);
                    $this->delete(array('id' => $procedure_id));
                    $this->error = 'Impossible de créer le fichier markdown initial.';
                    return false;
                }
            }
        } else {
            $this->error = 'Erreur de base de données lors de la création de la procédure.';
        }
        
        return $procedure_id;
    }
    
    /**
     * Mettre à jour une procédure
     *
     * @param int $id ID de la procédure
     * @param array $data Nouvelles données
     * @return bool Succès de la mise à jour
     */
    public function update_procedure($id, $data) {
        $data['updated_by'] = $this->dx_auth->get_username();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Call parent update method (which doesn't return a value)
        $this->update('id', $data, $id);

        // Check if update was successful by verifying the record was updated
        $updated_record = $this->get_by_id('id', $id);
        return !empty($updated_record);
    }
    
    /**
     * Supprimer une procédure et ses fichiers
     * 
     * @param int $id ID de la procédure
     * @return bool Succès de la suppression
     */
    public function delete_procedure($id) {
        $procedure = $this->get_by_id('id', $id);
        if (!$procedure) {
            $this->error = "Procédure non trouvée.";
            return false;
        }
        
        // Supprimer le dossier et tous les fichiers
        if (!$this->delete_procedure_directory($procedure['name'])) {
            $this->error = "Erreur lors de la suppression du dossier de la procédure. Vérifiez les permissions sur le dossier 'uploads/procedures/'.";
            return false;
        }
        
        // Supprimer l'enregistrement en base
        $this->delete(array('id' => $id));
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            // If affected_rows is 0, check if the record is actually gone.
            // It might have been deleted in a previous failed attempt.
            $check = $this->get_by_id('id', $id);
            if (empty($check)) {
                return true; // Already deleted, so we consider it a success.
            }

            $this->error = "Erreur de base de données: la procédure n'a pas pu être supprimée.";
            return false;
        }
    }
    
    /**
     * Obtenir le contenu markdown d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return string|false Contenu markdown ou false si erreur
     */
    public function get_markdown_content($procedure_name) {
        $file_path = "procedures/{$procedure_name}/procedure_{$procedure_name}.md";
        return $this->file_manager->get_file_content($file_path);
    }
    
    /**
     * Sauvegarder le contenu markdown d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @param string $content Contenu markdown
     * @return bool Succès de la sauvegarde
     */
    public function save_markdown_content($procedure_name, $content) {
        $file_path = "procedures/{$procedure_name}/procedure_{$procedure_name}.md";
        
        // Mettre à jour le timestamp en base
        $this->update_procedure_by_name($procedure_name, array(
            'markdown_file' => $file_path
        ));
        
        return $this->file_manager->save_file_content($file_path, $content);
    }
    
    /**
     * Lister les fichiers attachés d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return array Liste des fichiers
     */
    public function list_procedure_files($procedure_name) {
        return $this->file_manager->list_files("procedures/{$procedure_name}");
    }
    
    /**
     * Upload un fichier pour une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @param string $file_field Nom du champ fichier
     * @return array Résultat de l'upload
     */
    public function upload_procedure_file($procedure_name, $file_field = 'file') {
        $config = array(
            'allowed_types' => 'pdf|doc|docx|txt|md|jpg|jpeg|png|gif|svg',
            'max_file_size' => 20480, // 20MB
            'create_thumbs' => true
        );
        
        return $this->file_manager->upload_file("procedures/{$procedure_name}", $file_field, $config);
    }
    
    /**
     * Supprimer un fichier d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @param string $filename Nom du fichier
     * @return bool Succès de la suppression
     */
    public function delete_procedure_file($procedure_name, $filename) {
        return $this->file_manager->delete_file("procedures/{$procedure_name}/{$filename}");
    }
    
    /**
     * Valider le nom d'une procédure
     * 
     * @param string $name Nom à valider
     * @return bool True si valide
     */
    private function validate_procedure_name($name) {
        // Vérifier le format (lettres, chiffres, underscores uniquement)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            return false;
        }
        
        // Vérifier la longueur
        if (strlen($name) < 3 || strlen($name) > 128) {
            return false;
        }
        
        // Vérifier l'unicité
        $existing = $this->select_all(array('name' => $name));
        return empty($existing);
    }
    
    /**
     * Créer la structure de dossiers pour une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return bool Succès de la création
     */
    private function create_procedure_directory($procedure_name) {
        return $this->file_manager->ensure_directory_exists("./uploads/procedures/{$procedure_name}/");
    }
    
    /**
     * Supprimer le dossier d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return bool Succès de la suppression
     */
    private function delete_procedure_directory($procedure_name) {
        $directory_path = FCPATH . "uploads/procedures/" . $procedure_name;
        
        if (!is_dir($directory_path)) {
            return true; // Nothing to delete
        }

        // Use a more robust recursive delete
        return $this->delete_directory_recursive($directory_path);
    }
    
    /**
     * Supprimer un dossier récursivement
     * 
     * @param string $dir_path Chemin du dossier
     * @return bool Succès de la suppression
     */
    private function delete_directory_recursive($dir_path) {
        if (!is_dir($dir_path)) {
            return false;
        }
        $files = array_diff(scandir($dir_path), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir_path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if (!$this->delete_directory_recursive($path)) {
                    return false;
                }
            } else {
                if (!unlink($path)) {
                    return false;
                }
            }
        }
        return rmdir($dir_path);
    }
    
    /**
     * Créer le fichier markdown initial d'une procédure
     * 
     * @param array $procedure_data Données de la procédure
     * @return bool Succès de la création
     */
    private function create_initial_markdown_file($procedure_data) {
        $content = "# {$procedure_data['title']}\n\n";
        
        if (!empty($procedure_data['description'])) {
            $content .= "{$procedure_data['description']}\n\n";
        }
        
        $content .= "## Objectif\n\n";
        $content .= "*Décrire l'objectif de cette procédure*\n\n";
        $content .= "## Prérequis\n\n";
        $content .= "- [ ] Prérequis 1\n";
        $content .= "- [ ] Prérequis 2\n\n";
        $content .= "## Procédure\n\n";
        $content .= "### Étape 1\n\n";
        $content .= "*Décrire la première étape*\n\n";
        $content .= "### Étape 2\n\n";
        $content .= "*Décrire la deuxième étape*\n\n";
        $content .= "## Notes importantes\n\n";
        $content .= "> Ajouter ici les notes importantes ou les points d'attention\n\n";
        $content .= "---\n";
        $content .= "*Version {$procedure_data['version']} - Créée le " . date('d/m/Y') . "*\n";
        
        return $this->save_markdown_content($procedure_data['name'], $content);
    }
    
    /**
     * Vérifier si une procédure a un fichier markdown
     * 
     * @param string $procedure_name Nom de la procédure
     * @return bool True si le fichier existe
     */
    private function has_markdown_file($procedure_name) {
        $file_path = "./uploads/procedures/{$procedure_name}/procedure_{$procedure_name}.md";
        return file_exists($file_path);
    }
    
    /**
     * Compter les fichiers attachés d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return int Nombre de fichiers
     */
    private function count_attachments($procedure_name) {
        $files = $this->list_procedure_files($procedure_name);
        // Exclure le fichier markdown principal du compte
        $count = 0;
        foreach ($files as $file) {
            if ($file['name'] !== "procedure_{$procedure_name}.md") {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Obtenir le chemin du fichier markdown d'une procédure
     * 
     * @param string $procedure_name Nom de la procédure
     * @return string Chemin relatif du fichier
     */
    private function get_markdown_file_path($procedure_name) {
        return "procedures/{$procedure_name}/procedure_{$procedure_name}.md";
    }
    
    /**
     * Mettre à jour une procédure par son nom
     * 
     * @param string $name Nom de la procédure
     * @param array $data Données à mettre à jour
     * @return bool Succès de la mise à jour
     */
    private function update_procedure_by_name($name, $data) {
        $data['updated_by'] = $this->dx_auth->get_username();
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('name', $name);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Obtenir les procédures par statut
     * 
     * @param string $status Statut recherché
     * @return array Liste des procédures
     */
    public function get_procedures_by_status($status) {
        return $this->select_page(0, 0, array('status' => $status));
    }
    
    /**
     * Obtenir les procédures d'une section
     * 
     * @param int $section_id ID de la section (null pour globales)
     * @return array Liste des procédures
     */
    public function get_procedures_by_section($section_id) {
        if ($section_id === null) {
            return $this->select_page(0, 0, array('section_id' => null));
        }
        return $this->select_page(0, 0, array('section_id' => $section_id));
    }
}