# Configuration File Support Implementation

**Date:** 2025-01-20  
**Feature:** Support for image/file configuration parameters  
**Based on:** member_photo_improvement_prd.md and attachments_improvement_prd.md  

## Overview

This implementation adds support for images and files as configuration parameters in GVV, following the same patterns used for member photos and attachments.

## Design Decisions

### 1. Storage Location
- **Directory:** `uploads/configuration/`
- **Filename Pattern:** `{configuration.cle}.{extension}`
- **Examples:** `vd.background_image.png`, `vd.logo.jpg`

### 2. File Handling Pattern
Following existing patterns:
- **Member Photos:** `uploads/photos/{random}_{mlogin}.png`
- **Attachments:** `uploads/attachments/YYYY/SECTION/{random}_{original_name}.ext`
- **Configuration:** `uploads/configuration/{cle}.{extension}` (simplified, predictable)

### 3. Compression Integration
- Uses existing `File_compressor` library
- Same compression strategy as attachments:
  - Images: Resize to 1600x1200, recompress
  - PDFs: Ghostscript /ebook optimization
  - Other files: Not compressed (future gzip option)

### 4. Database Schema
- Uses existing `configuration.file` VARCHAR(255) field
- Stores only filename, not full path
- Path construction handled in metadata layer

## Implementation Components

### 1. Controller Changes (`application/controllers/configuration.php`)

#### `form2database()` Enhancement
- Added file upload handling with `handle_file_upload()`
- Old file cleanup when replacing
- Error handling with flash messages

#### New `handle_file_upload()` Method
- Uploads to `uploads/configuration/`
- Uses configuration key as basename
- Applies compression automatically
- Returns success/error status

#### `delete()` Override
- Cleans up associated files when configuration deleted
- Logs file deletion

### 2. Metadata Changes (`application/libraries/Gvvmetadata.php`)

#### Field Definitions
```php
$this->field['configuration']['file']['Name'] = 'Fichier';
$this->field['configuration']['file']['Subtype'] = 'upload_image';
$this->field['vue_configuration']['file']['Subtype'] = 'upload_image';
```

### 3. MetaData.php Updates

#### Display Logic Enhancement
- Added configuration file path handling
- Pattern: `uploads/configuration/{filename}`
- Thumbnail rendering using `attachment()` helper

#### Upload Form Enhancement
- Updated `input_field()` for configuration context
- Correct file path construction for preview

### 4. Model Changes (`application/models/configuration_model.php`)

#### `select_page()` Update
- Added `file` field to SELECT query
- Enables file column display in table view

### 5. View Changes (`application/views/configuration/bs_tableView.php`)

#### Table Fields Update
- Added `file` to displayed fields array
- Shows thumbnail column in configuration list

## File Lifecycle

### Upload Process
1. User selects file via upload form
2. `handle_file_upload()` processes file:
   - Validates file type and size
   - Generates predictable filename
   - Applies compression if beneficial
   - Stores in `uploads/configuration/`
3. Database stores filename only
4. Old file deleted if replacing

### Display Process
1. Table view shows thumbnail via `attachment()` helper
2. Click opens full-size view in new tab
3. Metadata constructs full path: `uploads/configuration/{filename}`

### Deletion Process
1. Configuration record deletion triggers file cleanup
2. Physical file removed from filesystem
3. Operation logged

## Error Handling

### Upload Errors
- File size/type validation
- Compression failure fallback
- Flash message display

### Missing Files
- Graceful degradation for missing files
- No broken links or images

### Permissions
- Directory creation with proper permissions (777)
- File access validation

## Security Considerations

### File Type Restrictions
```php
'allowed_types' => 'png|jpeg|jpg|gif|webp|pdf|doc|docx|xls|xlsx|txt|csv|zip'
```

### File Size Limits
- Maximum: 10MB (configurable)
- Compression reduces storage needs

### Path Security
- No direct user control over file paths
- Predictable naming prevents conflicts
- Files served via attachment helper

## Testing Strategy

### Unit Tests
- File upload validation
- Compression integration
- Error handling

### Integration Tests
- Full upload/display/delete cycle
- File cleanup verification
- Metadata consistency

### Manual Testing
1. Upload various file types
2. Verify thumbnails in table view
3. Test file replacement
4. Verify cleanup on deletion

## Configuration Examples

### Image Background
```
cle: vd.background_image
valeur: Image de fond pour les bons
file: vd.background_image.png
```

### Logo File
```
cle: club.logo
valeur: Logo du club
file: club.logo.svg
```

## Future Enhancements

### Planned
- Multiple files per configuration key
- File versioning
- Bulk file management

### Possible
- Direct image editing
- Cloud storage integration
- File sharing between configurations

## Migration Notes

### Existing Configurations
- No migration needed (new feature)
- Existing configurations work unchanged
- File field optional

### Backward Compatibility
- Full compatibility maintained
- Optional file enhancement
- No breaking changes

## Monitoring

### Logs
- File uploads: success/failure
- Compression results: size reduction
- File deletions: cleanup confirmation

### Storage Usage
- Monitor `uploads/configuration/` size
- Compression effectiveness tracking
- Cleanup verification

## Dependencies

### Required Libraries
- `File_compressor` (existing)
- CodeIgniter Upload library
- GD extension (for images)

### No New Dependencies
- Reuses existing infrastructure
- No external tools required
- PHP-only implementation

---

This implementation provides a complete file support system for configuration parameters while maintaining consistency with existing GVV patterns and ensuring robust error handling and security.