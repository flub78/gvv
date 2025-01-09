# Smartphone Image Upload Processing in Legacy PHP

This guide explains how to handle smartphone photo uploads in a legacy PHP application (PHP 7.4, CodeIgniter 2.x) without requiring Composer or additional libraries.

## Configuration Requirements

The solution is designed around typical smartphone photo characteristics:
- Maximum upload size: 10MB (accommodates most smartphone photos which are 2-8MB)
- Maximum resolution: 2400x3600 pixels
- Supported formats: JPEG, PNG, GIF
- JPEG quality: 85% (balancing quality and file size)
- Uses PHP's built-in GD library

## Implementation

### 1. Configuration Constants

```php
<?php
// Configuration constants
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);  // 10MB max upload size
define('MAX_WIDTH', 2400);
define('MAX_HEIGHT', 3600);
define('JPEG_QUALITY', 85);
define('UPLOAD_PATH', './uploads/');

// CodeIgniter upload configuration
$config['upload_path'] = UPLOAD_PATH;
$config['allowed_types'] = 'gif|jpg|jpeg|png';
$config['max_size'] = MAX_UPLOAD_SIZE / 1024; // Convert to KB for CI
$config['file_ext_tolower'] = TRUE;
$config['remove_spaces'] = TRUE;
```

### 2. Image Processing Function 

```php
function process_image($source_path, $target_path) {
    // Get image info
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        return false;
    }

    // Create image resource based on type
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    // Calculate new dimensions while maintaining aspect ratio
    $width = $image_info[0];
    $height = $image_info[1];
    
    if ($width > MAX_WIDTH || $height > MAX_HEIGHT) {
        $ratio = min(MAX_WIDTH / $width, MAX_HEIGHT / $height);
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG images
        if ($image_info[2] === IMAGETYPE_PNG) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }
        
        imagecopyresampled(
            $new_image, $image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $width, $height
        );
        
        $image = $new_image;
    }

    // Save the processed image
    $path_info = pathinfo($target_path);
    switch (strtolower($path_info['extension'])) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($image, $target_path, JPEG_QUALITY);
            break;
        case 'png':
            imagepng($image, $target_path, 9); // Maximum PNG compression
            break;
        case 'gif':
            imagegif($image, $target_path);
            break;
    }

    imagedestroy($image);
    return $target_path;
}
```

### 3. CodeIgniter Controller Implementation

```php
class Upload extends CI_Controller {
    public function do_upload() {
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('userfile')) {
            $error = $this->upload->display_errors();
            // Handle error
        } else {
            $upload_data = $this->upload->data();
            $source_path = $upload_data['full_path'];
            $target_path = UPLOAD_PATH . 'processed_' . $upload_data['file_name'];
            
            if (process_image($source_path, $target_path)) {
                unlink($source_path); // Remove original
                // Handle success
            } else {
                // Handle processing error
            }
        }
    }
}
```

## Key Features

1. **Resolution Management**
   - Automatically resizes images exceeding maximum dimensions
   - Maintains aspect ratio during resizing
   - Suitable resolution for web use while preserving quality

2. **Optimization Features**
   - JPEG compression at 85% quality
   - Maximum PNG compression (level 9)
   - Transparency preservation for PNG images
   - Automatic lowercase file extensions
   - Removal of spaces from filenames

3. **Error Handling**
   - Validates image type and dimensions
   - Proper resource cleanup to prevent memory leaks
   - Error reporting through CodeIgniter's upload library

## Implementation Steps

1. Add the configuration constants to your application/config directory
2. Include the processing function in a helper or library file
3. Create the upload controller with the provided example code
4. Ensure the upload directory exists and has proper write permissions

## Notes

- The solution works with PHP's built-in GD library
- No additional Composer packages required
- Compatible with default smartphone camera settings
- Provides good balance between image quality and server storage
