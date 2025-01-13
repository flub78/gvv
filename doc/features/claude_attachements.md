# Ajout d'Attachements (Justificatifs) dans une Application PHP/CodeIgniter 3

Voici une approche simple et facile à implémenter pour permettre l'attachement de justificatifs, y compris la prise directe de photos depuis un téléphone.

## 1. Prise de photo sur mobile
- Utilisez l'élément HTML `<input type="file" accept="image/*" capture="camera">` pour permettre la prise de photo ou le téléchargement d'une image.
- Exemple de champ HTML :
  ```html
  <input type="file" name="justificatif" accept="image/*" capture="camera">
  ```

## 2. Organisation du stockage sur le serveur
- Stockez les fichiers de manière hiérarchique selon une structure logique : `/attachments/{année}/{mois}/{jour}/`.
- Exemple :
  - Une facture du 19 décembre 2024 sera stockée dans : `/attachments/2024/12/19/nom_du_fichier.jpg`.

## 3. Compression et redimensionnement des images
- Utilisez une bibliothèque comme **GD** ou **Imagick** pour compresser et redimensionner les images avant de les sauvegarder.
- Exemple avec GD en PHP :
  ```php
  function redimensionner_image($source, $destination, $largeur_max, $qualité = 75) {
      $image_info = getimagesize($source);
      $largeur_orig = $image_info[0];
      $hauteur_orig = $image_info[1];
      $ratio = $largeur_orig / $hauteur_orig;

      $largeur_nouvelle = $largeur_max;
      $hauteur_nouvelle = $largeur_max / $ratio;

      $image = imagecreatefromjpeg($source);
      $image_redimensionnée = imagecreatetruecolor($largeur_nouvelle, $hauteur_nouvelle);

      imagecopyresampled(
          $image_redimensionnée,
          $image,
          0, 0, 0, 0,
          $largeur_nouvelle, $hauteur_nouvelle,
          $largeur_orig, $hauteur_orig
      );

      imagejpeg($image_redimensionnée, $destination, $qualité);

      imagedestroy($image);
      imagedestroy($image_redimensionnée);
  }
  ```

## 4. Sauvegarde des fichiers
- Créez un contrôleur CodeIgniter pour gérer les téléchargements.
- Exemple :
  ```php
  public function upload_attachment() {
      $this->load->helper(['file', 'url']);

      if (!empty($_FILES['justificatif']['name'])) {
          $year = date('Y');
          $month = date('m');
          $day = date('d');
          $upload_path = FCPATH . "attachments/$year/$month/$day/";

          if (!is_dir($upload_path)) {
              mkdir($upload_path, 0755, true);
          }

          $temp_file = $_FILES['justificatif']['tmp_name'];
          $file_name = uniqid() . '.jpg';
          $destination = $upload_path . $file_name;

          // Redimensionner et sauvegarder
          redimensionner_image($temp_file, $destination, 1024);

          echo "Fichier téléchargé avec succès : " . base_url("attachments/$year/$month/$day/$file_name");
      } else {
          echo "Aucun fichier téléchargé.";
      }
  }
  ```

## 5. Retrouver les fichiers sans l'application
- La structure hiérarchique permet un accès simple à partir du système de fichiers :
  - Exemple : `/attachments/2024/12/19/nom_du_fichier.jpg`.

## 6. Sécurisation des fichiers
- Ajoutez un fichier `.htaccess` pour limiter l'accès public aux fichiers :
  ```apache
  <Files "*">
      Require all denied
  </Files>
  ```

Cette approche est facile à intégrer dans votre application existante avec un minimum de dépendances externes.



