# Attachments Test Coverage Analysis

**Date:** 2025-10-10
**Purpose:** Analyze existing PHPUnit tests for attachments functionality and identify coverage gaps

---

## Executive Summary

**Current Status:** ⚠️ **LIMITED COVERAGE** - Only basic helper function tests exist. No comprehensive controller, model, or integration tests.

**Existing Tests:**
- ✅ Helper function tests (partial coverage in `MyHtmlHelperIntegrationTest.php`)
- ✅ Model unit tests (basic CRUD in `attachments_model::test()`)
- ✅ Test data files (comprehensive set in `application/tests/data/attachments/`)

**Missing Tests:**
- ❌ Controller integration tests (upload, edit, delete workflows)
- ❌ File system validation tests
- ❌ File type handling tests
- ❌ Browser viewing vs download tests
- ❌ File replacement on edit tests

---

## Test Coverage Analysis

### ✅ COVERED: Helper Function Tests

**File:** `application/tests/integration/MyHtmlHelperIntegrationTest.php` (lines 436-573)

**Coverage:**
- `attachment()` function with empty filename (lines 441-446)
- `attachment()` function returns link with URL (lines 453-465)
- `attachment()` function with PDF file (lines 471-481)
- `attachment()` function with CSV file (lines 488-498)
- `attachment()` function with various extensions (docx, pptx, md) (lines 523-537)
- `attachment()` function with AVIF image (lines 544-555)
- Valid HTML structure generation (lines 561-572)

**What's Covered:**
- ✅ Link generation for different file types
- ✅ Icon selection based on file type
- ✅ Image thumbnail generation for image files
- ✅ HTML structure validation
- ✅ Extension-based detection (e.g., AVIF)

**What's NOT Covered:**
- ❌ Actual file viewing in browser
- ❌ File download functionality
- ❌ Integration with actual controller/model

---

### ✅ COVERED: Model Unit Tests

**File:** `application/models/attachments_model.php` (lines 115-160)

**Coverage:**
- Database CRUD operations (insert, select, delete)
- Basic data integrity validation
- Count validation after operations

**What's Covered:**
- ✅ Insert attachment record
- ✅ Get attachment by ID
- ✅ Delete attachment record
- ✅ Database count validation

**What's NOT Covered:**
- ❌ File system operations (actual file upload/delete)
- ❌ Year-based filtering
- ❌ Section-based organization
- ❌ File path validation
- ❌ Referenced table validation

---

### ✅ AVAILABLE: Test Data Files

**Location:** `application/tests/data/attachments/`

**Comprehensive Test Files:**
- **Text files:** Small (84 KB), Medium (680 KB), Large (12 MB)
- **Documents:** PDF (31 KB - 1.3 MB), DOCX, XLSX
- **Images:** PNG, JPEG, GIF (various sizes from 8.7 KB to 12 MB)
- **Archives:** ZIP files (62 KB - 1.6 MB)
- **Threshold tests:** Files at 98 KB and 102 KB (for compression threshold testing)

**Intended Use:**
- Compression algorithm testing
- File type handling
- Size threshold validation
- Performance testing

**Current Status:** ⚠️ Files exist but are NOT actively used in PHPUnit tests

---

## Missing Test Coverage - YOUR REQUIREMENTS

### ❌ MISSING: Test Case 1 - Attach Files During Creation/Edit

**Requirement:**
> Files of different types can be attached to accounting lines either when the accounting line is created or edited.

**Current Code Support:**
- **Creation:** `attachments/create` requires `table` and `id` parameters (lines 75-80 of `attachments.php`)
- **Limitation:** ⚠️ Cannot attach during accounting line creation (needs existing ID)
- **Edit:** Works correctly - can attach to existing records

**Missing Tests:**
- ❌ Upload file during accounting line creation (inline attachment - **PRD requirement**)
- ❌ Upload file when editing existing accounting line
- ❌ Upload multiple file types (PDF, JPEG, PNG, CSV, etc.)
- ❌ Validate file type restrictions
- ❌ Validate file size limits (20MB max - line 134)
- ❌ Error handling for failed uploads
- ❌ Form validation after upload failure

**Test Locations Needed:**
- `application/tests/controllers/AttachmentsControllerTest.php` (doesn't exist)
- `application/tests/integration/AttachmentsWorkflowTest.php` (doesn't exist)

---

### ❌ MISSING: Test Case 2 - Files Stored in Correct Directory Structure

**Requirement:**
> Once the file have been uploaded they can be found in the server under uploads/attachments/{year}/{section}

**Current Code Support:**
- **Directory structure:** `./uploads/attachments/{year}/{section}/` (line 121 of `attachments.php`)
- **Year:** Extracted from current date (line 107)
- **Section:** Retrieved from club/section field (lines 110-119)
- **Filename:** Random 6-digit prefix + sanitized original name (line 130)

**Missing Tests:**
- ❌ Verify files stored in correct year directory
- ❌ Verify files stored in correct section subdirectory
- ❌ Verify directory creation with proper permissions (0777 - line 124)
- ❌ Verify filename format (random prefix + original name)
- ❌ Verify section name sanitization (spaces replaced with underscores - line 119)
- ❌ Test with missing/invalid section (should use 'Unknown' - line 115)
- ❌ Test directory permissions after creation

**Test Example:**
```php
public function testFileStoredInCorrectDirectory() {
    // Upload file for section "Planeur" in 2025
    $file_path = $this->uploadAttachment('test.pdf', 'ecritures', 123, 'Planeur', '2025');

    // Assert file exists in correct location
    $expected_pattern = './uploads/attachments/2025/Planeur/\d{6}_test\.pdf';
    $this->assertMatchesRegularExpression($expected_pattern, $file_path);
    $this->assertFileExists($file_path);
}
```

---

### ❌ MISSING: Test Case 3 - Browser Viewing vs Download

**Requirement:**
> Uploaded files can be displayed in WEB browser or downloaded according to their types

**Current Code Support:**
- **Helper function:** `attachment()` in `application/helpers/MY_html_helper.php`
- **Image detection:** Lines 616-650 (mime type and extension-based)
- **Image display:** `<img>` tag with thumbnail (lines 636-650)
- **Document download:** `<a>` link with target="_blank" (lines 621-635)

**Missing Tests:**
- ❌ Images (JPEG, PNG, GIF) should display inline with `<img>` tag
- ❌ PDFs should open in browser (Content-Type: application/pdf)
- ❌ Office documents (DOCX, XLSX) should trigger download
- ❌ Text files (CSV, TXT) behavior validation
- ❌ AVIF image support (extension-based detection - line 616)
- ❌ WebP image support
- ❌ MIME type detection accuracy
- ❌ Fallback when mime_content_type() fails

**Test Example:**
```php
public function testImageFilesDisplayInBrowser() {
    $image_types = ['jpg', 'png', 'gif', 'webp', 'avif'];

    foreach ($image_types as $ext) {
        $html = attachment(1, "test.$ext", "http://example.com/test.$ext");

        // Should contain <img> tag for inline display
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString("http://example.com/test.$ext", $html);
    }
}

public function testPdfFilesOpenInBrowser() {
    // PDF should have link with target="_blank" and PDF icon
    $html = attachment(1, "invoice.pdf", "http://example.com/invoice.pdf");

    $this->assertStringContainsString('<a href="http://example.com/invoice.pdf"', $html);
    $this->assertStringContainsString('target="_blank"', $html);
    $this->assertStringContainsString('fa-file-pdf', $html);
    $this->assertStringContainsString('text-danger', $html); // PDF icon is red
}

public function testOfficeDocumentsTriggerDownload() {
    $doc_types = ['docx', 'xlsx', 'pptx'];

    foreach ($doc_types as $ext) {
        $html = attachment(1, "document.$ext", "http://example.com/document.$ext");

        // Should have download link
        $this->assertStringContainsString('<a href=', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }
}
```

**PRD Update Requirements (CA1.7, CA1.8):**
- ✅ CA1.7: Images and PDFs viewable directly in browser
- ✅ CA1.8: Other file types (ZIP, DOCX, XLSX) downloadable

---

### ❌ MISSING: Test Case 4 - File Replacement on Edit

**Requirement:**
> When an attachment is edited and the uploaded file is replaced, the initial file is removed from the server

**Current Code Support:**
- **File deletion:** Lines 154-162 of `attachments.php`
- **Process:**
  1. Get initial_id from session (line 155)
  2. Fetch old file path (line 158)
  3. Check file exists (line 159)
  4. Delete old file with `unlink()` (line 160)

**Missing Tests:**
- ❌ Edit attachment and upload new file
- ❌ Verify old file is deleted from file system
- ❌ Verify new file is saved with new path
- ❌ Verify database record is updated with new path
- ❌ Test with missing old file (should not error)
- ❌ Test with non-existent initial_id
- ❌ Test permissions to delete file

**Test Example:**
```php
public function testEditAttachmentReplacesOldFile() {
    // Create initial attachment
    $initial_file = $this->uploadTestFile('old_invoice.pdf');
    $attachment_id = 123;

    // Verify initial file exists
    $this->assertFileExists($initial_file);

    // Edit attachment with new file
    $new_file = $this->editAttachment($attachment_id, 'new_invoice.pdf');

    // Assert old file is deleted
    $this->assertFileDoesNotExist($initial_file);

    // Assert new file exists
    $this->assertFileExists($new_file);

    // Assert database updated with new path
    $attachment = $this->attachments_model->get_by_id('id', $attachment_id);
    $this->assertEquals($new_file, $attachment['file']);
}

public function testEditAttachmentHandlesMissingOldFile() {
    // Create attachment record without actual file
    $attachment_id = $this->createAttachmentWithoutFile();

    // Edit with new file (old file doesn't exist)
    // Should not throw error
    $new_file = $this->editAttachment($attachment_id, 'new_file.pdf');

    $this->assertFileExists($new_file);
}
```

---

## Summary Table: Test Coverage Status

| Test Requirement | Current Coverage | Missing Tests | Priority |
|-----------------|------------------|---------------|----------|
| **1. Attach during creation/edit** | 🔴 None | Controller workflow tests | **HIGH** |
| **2. Files in {year}/{section}** | 🔴 None | File system validation | **HIGH** |
| **3. Browser view vs download** | 🟡 Partial (helper only) | Integration + actual file serving | **HIGH** |
| **4. File replacement on edit** | 🔴 None | Edit workflow + file deletion | **HIGH** |
| Compression (PRD Phase 2) | 🔴 None | Compression library tests | **MEDIUM** |
| Decompression (PRD Phase 3) | 🔴 None | Transparent decompression tests | **MEDIUM** |
| Inline upload (PRD Phase 1) | 🔴 None | Temp file + session handling | **HIGH** |

**Legend:**
- 🔴 None = 0% coverage
- 🟡 Partial = Some aspects covered, major gaps remain
- 🟢 Good = Most scenarios covered

---

## Recommended Test Files to Create

### 1. Controller Tests

**File:** `application/tests/controllers/AttachmentsControllerTest.php`

**Coverage:**
- Upload workflow (create, formValidation)
- Edit workflow (file replacement)
- Delete workflow (file cleanup)
- Error handling (upload failures)
- Directory creation and permissions
- Filename sanitization

### 2. Integration Tests

**File:** `application/tests/integration/AttachmentsWorkflowTest.php`

**Coverage:**
- End-to-end attachment creation
- File system validation
- Database + file system consistency
- Multiple file types
- Section-based organization
- Year-based filtering

### 3. Model Tests (Enhanced)

**File:** `application/tests/mysql/AttachmentsModelMySqlTest.php`

**Coverage:**
- select_page() with year filtering
- select_page() with section filtering
- get_available_years() functionality
- Join with sections table
- Referenced table link generation

### 4. File Serving Tests

**File:** `application/tests/integration/AttachmentFileServingTest.php`

**Coverage:**
- MIME type detection
- Image inline display
- PDF browser viewing
- Document downloads
- Content-Type headers
- File size validation

---

## Test Implementation Priority

### Phase 1: Critical Tests (Before implementing PRD)

1. ✅ **Verify current functionality works**
   - File upload during edit
   - File stored in {year}/{section}
   - File deletion on replacement
   - Database record creation

2. ✅ **Establish baseline coverage**
   - Controller workflow tests
   - File system validation tests
   - Model integration tests

### Phase 2: PRD Implementation Tests (During development)

3. ✅ **Inline attachment tests** (PRD Phase 1)
   - Temp file handling
   - Session-based storage
   - Form validation with attachments

4. ✅ **Compression tests** (PRD Phase 2)
   - Image compression (resize + JPEG)
   - Document compression (gzip)
   - Threshold validation
   - Compression ratio logging

5. ✅ **Decompression tests** (PRD Phase 3)
   - Transparent serving of compressed files
   - Browser viewing of images
   - Download of documents

### Phase 3: Comprehensive Coverage (After deployment)

6. ✅ **Edge cases and error handling**
   - Missing files
   - Invalid file types
   - Permission issues
   - Disk space issues

---

## Conclusion

**Current Status:** The GVV attachments feature has **minimal automated test coverage** for the core workflows you specified.

**Critical Gaps:**
1. ❌ No controller-level tests for upload/edit/delete workflows
2. ❌ No file system validation tests
3. ❌ No integration tests for end-to-end scenarios
4. ❌ No tests for file replacement on edit

**Next Steps:**
1. **Immediate:** Create `AttachmentsControllerTest.php` to test current functionality
2. **Short-term:** Create `AttachmentsWorkflowTest.php` for integration testing
3. **Medium-term:** Enhance tests as PRD features are implemented
4. **Long-term:** Achieve >75% code coverage for attachments module

**Test Data:** ✅ Excellent test data files already exist in `application/tests/data/attachments/` - ready to use!
