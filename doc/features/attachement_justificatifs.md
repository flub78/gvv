# Implémentation d'attachements photos pour application PHP/CodeIgniter 3


## Base de données

```sql
CREATE TABLE attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attachable_type VARCHAR(50) NOT NULL,    -- Type d'entité (writing, flight, pilot, etc.)
    attachable_id INT NOT NULL,              -- ID de l'entité
    filename VARCHAR(255) NOT NULL,          -- Nom du fichier sur le serveur
    original_name VARCHAR(255),              -- Nom original du fichier
    mime_type VARCHAR(100),           
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Index composé pour optimiser les recherches
    INDEX idx_attachable (attachable_type, attachable_id)
);
```

## Structure de stockage des fichiers

```
uploads/
  attachments/
    YYYY/           # Année
      MM/           # Mois
        TYPE/     # Type d'entité (writings, flights, pilots)
          ID/     # ID de l'entité
```

## Controller

```php
class Attachments extends CI_Controller {
    
    // Liste des types d'entités autorisés et leurs configurations
    private $allowed_types = [
        'writing' => [
            'model' => 'Writing_model',
            'permission' => 'can_edit_writing'
        ],
        'flight' => [
            'model' => 'Flight_model',
            'permission' => 'can_edit_flight'
        ],
        'pilot' => [
            'model' => 'Pilot_model',
            'permission' => 'can_edit_pilot'
        ]
    ];
    
    public function upload($type, $id) {
        // Validation du type d'entité
        if (!array_key_exists($type, $this->allowed_types)) {
            return $this->output->set_status_header(400, 'Invalid entity type');
        }

        // Chargement du modèle approprié
        $config = $this->allowed_types[$type];
        $this->load->model($config['model']);
        
        // Vérification des droits d'accès
        if (!$this->auth->{$config['permission']}($id)) {
            return $this->output->set_status_header(403);
        }

        // Configuration du dossier de destination
        $upload_path = $this->_get_upload_path($type, $id);
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        // Configuration de l'upload
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf';
        $config['max_size'] = 10240; // 10MB max
        $config['encrypt_name'] = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            return $this->output->set_json([
                'success' => false,
                'error' => $error
            ]);
        }

        $upload_data = $this->upload->data();
        
        // Compression d'image si nécessaire
        if (in_array($upload_data['file_type'], ['image/jpeg', 'image/png'])) {
            $this->_compress_image($upload_data['full_path']);
        }

        // Sauvegarde en base de données
        $attachment_data = [
            'attachable_type' => $type,
            'attachable_id' => $id,
            'filename' => $upload_data['file_name'],
            'original_name' => $upload_data['orig_name'],
            'mime_type' => $upload_data['file_type'],
            'file_size' => $upload_data['file_size']
        ];

        $this->db->insert('attachments', $attachment_data);

        return $this->output->set_json([
            'success' => true,
            'data' => $attachment_data
        ]);
    }

    private function _get_upload_path($type, $id) {
        $date = date('Y/m/d');
        return FCPATH . "uploads/attachments/{$date}/{$type}/{$id}/";
    }

    // La méthode _compress_image reste inchangée
}
```

## Vue (HTML/JavaScript)

```html
<div class="form-group">
    <!-- Input file classique -->
    <input type="file" id="attachment" accept="image/*,application/pdf" 
           capture="environment" style="display: none;">
    
    <!-- Bouton personnalisé -->
    <button type="button" class="btn btn-primary" onclick="triggerFileInput()">
        <i class="fas fa-camera"></i> Prendre une photo
    </button>
</div>

<script>
function triggerFileInput() {
    document.getElementById('attachment').click();
}

document.getElementById('attachment').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    // Envoi du fichier au serveur
    fetch(`/attachments/upload/${writing_id}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Afficher un message de succès
            alert('Document attaché avec succès');
        } else {
            // Gérer l'erreur
            alert('Erreur lors de l\'upload: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'upload');
    });
});
</script>
```

## Modification du JavaScript

```javascript
function uploadAttachment(type, id) {
    const file = document.getElementById('attachment').files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    // URL modifiée pour inclure le type
    fetch(`/attachments/upload/${type}/${id}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Document attaché avec succès');
        } else {
            alert('Erreur lors de l\'upload: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'upload');
    });
}
```
## Model trait (optionnel)

```php
trait HasAttachments {
    public function attachments() {
        $type = strtolower(get_class($this));
        return $this->db
            ->where('attachable_type', $type)
            ->where('attachable_id', $this->id)
            ->get('attachments')
            ->result();
    }

    public function addAttachment($file) {
        // Logique d'ajout d'attachement
    }

    public function removeAttachment($attachment_id) {
        // Logique de suppression d'attachement
    }
}
```

## Utilisation dans les modèles

```php
class Writing_model extends CI_Model {
    use HasAttachments;
    // ... reste du modèle
}

class Flight_model extends CI_Model {
    use HasAttachments;
    // ... reste du modèle
}

class Pilot_model extends CI_Model {
    use HasAttachments;
    // ... reste du modèle
}
```

## Points clés de l'implémentation

### 1. Base de données polymorphe
- Utilisation du pattern polymorphe avec `attachable_type` et `attachable_id`
- Permet d'attacher des fichiers à n'importe quel type d'entité
- Index optimisé pour les recherches

### 2. Organisation des fichiers
- Structure hiérarchique incluant le type d'entité
- Maintient la séparation logique des fichiers
- Facilite la maintenance et la recherche

### 2. Sécurité
- Vérification des droits d'accès
- Validation des types de fichiers
- Chiffrement des noms de fichiers

### 3. Optimisation
- Compression automatique des images
- Limitation de la taille maximale
- Redimensionnement des images trop grandes

### 4. Interface mobile
- Utilisation de l'attribut `capture="environment"` pour la caméra
- Interface simple et adaptée au mobile
- Feedback utilisateur clair

## Guide d'utilisation
1. Cliquer sur le bouton "Prendre une photo"
2. Sur mobile, possibilité de choisir entre l'appareil photo ou la galerie
3. Après la capture, l'upload se fait automatiquement
4. Un message de confirmation s'affiche

## Points d'attention
- Créer le dossier `uploads/attachments` avec les bonnes permissions
- Configurer le serveur web pour limiter l'accès direct aux fichiers uploadés
- Ajouter les règles nécessaires dans le `.gitignore`
- Mettre en place une stratégie de backup pour les fichiers uploadés
