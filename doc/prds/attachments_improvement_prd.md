# PRD: Attachments Feature Improvement

**Product:** GVV (Gestion Vol à Voile)
**Feature:** Enhanced Attachments Management
**Version:** 1.0
**Status:** Draft
**Created:** 2025-10-09
**Author:** Product Owner / Treasurer Requirements

---

## 1. Executive Summary

This PRD outlines improvements to the GVV attachments system to enable inline attachment creation during accounting line entry and automatic file compression for storage optimization. These enhancements will improve the treasurer's workflow and reduce storage requirements without sacrificing document quality.

---

## 2. Background and Context

### 2.1 Current State

The GVV application includes an attachments system that allows users to associate files (invoices, receipts, contracts, etc.) with various database records. The current implementation:

- **Database:** `attachments` table with fields:
  - `id` (BIGINT, primary key)
  - `referenced_table` (VARCHAR, e.g., 'ecritures')
  - `referenced_id` (VARCHAR, foreign key to referenced record)
  - `user_id` (VARCHAR)
  - `filename` (VARCHAR, original filename)
  - `description` (VARCHAR)
  - `file` (VARCHAR, path to uploaded file)
  - `club` (TINYINT, section/club reference)
  - `file_backup` (VARCHAR, backup path after migration 039)

- **Storage Structure:** `./uploads/attachments/YYYY/SECTION/random_filename`
  - Files organized by year and section (e.g., ULM, Avion, Planeur, Général)
  - Recent migration (039) reorganized files into section-based subdirectories

- **Current Workflow:**
  1. Create accounting line (écritures) in `compta` controller
  2. Save the accounting line to get an ID
  3. Edit the saved accounting line
  4. Click "Add Attachment" in the attachments section
  5. Upload files via separate `attachments/create` form

- **Key Files:**
  - Controller: `application/controllers/attachments.php`
  - Model: `application/models/attachments_model.php`
  - Views: `application/views/attachments/bs_formView.php`, `bs_tableView.php`
  - Integration: `application/controllers/compta.php` (lines 46-48, 82-86)
  - Helper: `application/helpers/MY_html_helper.php` (`attachment()` function)

### 2.2 Problems Identified

**P1: Workflow Inefficiency (Treasurer)**
- Cannot attach documents during initial accounting line creation
- Requires two-step process: create line → edit line → attach files
- Breaks natural workflow of data entry
- Results in forgotten attachments or delayed uploads

**P2: Storage Inefficiency (System Administrator)**
- All files stored at full size without compression
- Redundant storage of large files (invoices, scanned receipts)
- No automatic optimization of images
- No tracking of storage savings
- Historical attachments (~300MB currently) taking up unnecessary space

**P3: Technical Limitation**
- Attachment upload requires a `referenced_id` (foreign key)
- This ID only exists *after* the accounting line is saved
- No mechanism for "pending" attachments waiting for parent record creation

---

## 3. Goals and Objectives

### 3.1 Business Goals

1. **Improve Treasurer Productivity:** Reduce time spent managing accounting line attachments by 50%
2. **Reduce Storage Costs:** Achieve 30-50% reduction in attachment storage through compression
3. **Maintain Document Quality:** Ensure all compressed files remain printable and readable on screen
4. **Transparency:** Provide visibility into compression effectiveness through logging

### 3.2 User Goals

**Treasurer:**
- Attach invoice scans while entering accounting lines (single workflow)
- Retrieve attached documents quickly when reviewing past entries
- Confidence that documents are preserved and accessible

**System Administrator:**
- Monitor storage usage and compression effectiveness
- Ensure file compression happens automatically
- Maintain system performance
- Reclaim storage from historical attachments

---

## 4. Target Users and Personas

### Persona 1: Marie - Club Treasurer

**Background:**
- Age: 52, treasurer for 5 years
- Uses GVV weekly for accounting entry
- Not highly technical but comfortable with web forms
- Often enters 10-20 transactions per session with supporting documents

**Pain Points:**
- Must scan invoices, save accounting line, then go back to attach them
- Sometimes forgets to attach documents until audit time
- Finds two-step process frustrating and time-consuming

**Desired Outcome:**
- Upload invoice PDFs/images directly when creating accounting line
- See immediate confirmation that files are attached
- Quick access to previously attached documents

### Persona 2: Jean - System Administrator

**Background:**
- Age: 45, manages club IT infrastructure
- Monitors server disk usage
- Concerned about growing file storage requirements

**Pain Points:**
- Attachment folder growing rapidly (currently ~300MB)
- High-resolution scans consuming unnecessary space
- No automatic cleanup or optimization
- Historical attachments taking up space with no way to compress them

**Desired Outcome:**
- Automatic compression of uploaded files
- Logs showing compression ratios
- Ability to tune compression settings if needed
- Ability to batch-compress existing attachments to reclaim storage

---

## 5. Functional Requirements

### 5.1 FR1: Inline Attachment During Creation (Priority: HIGH)

**Description:** Enable users to upload attachment files during accounting line creation, before the record is saved and receives an ID.

**User Story:**
> As a treasurer, I want to attach invoice scans while I'm entering an accounting line, so that I can complete all data entry in one session without switching between forms.

**Acceptance Criteria:**
- AC1.1: Attachment upload control visible on accounting line creation form (`compta/create`)
- AC1.2: User can select one or multiple files for upload
- AC1.3: Files are uploaded immediately to temporary storage location
- AC1.4: Upon form submission (accounting line save):
  - If save succeeds: associate attachments with new accounting line ID and move to permanent storage
  - If save fails: retain temporary files for form resubmission
- AC1.5: User can remove uploaded files before final submission
- AC1.6: Existing edit workflow continues to work unchanged

---

### 5.2 FR2: Automatic File Compression (Priority: HIGH)

**Description:** Automatically compress uploaded files when compression provides meaningful storage savings, while maintaining document quality.

**User Stories:**
> As a system administrator, I want uploaded attachments to be compressed automatically, so that I can reduce server storage requirements without manual intervention.

> As an administrator, I want to compress already uploaded attachments to save place, so that I can recover storage space from historical files without losing data.

**Acceptance Criteria:**
- AC2.1: System analyzes file type and size upon upload
- AC2.2: Compression strategy based on file type:
  - **Images (JPEG, PNG, GIF, BMP, WebP):**
    - Resize to maximum dimensions (1600x1200 pixels) while maintaining aspect ratio
    - Convert to JPEG format with quality 85
    - Apply gzip compression to resulting file
    - Store as `filename.jpg.gz`
  - **All other files (PDF, DOCX, CSV, TXT, etc.):**
    - Compress using PHP `gzencode()` function (level 9)
    - No format conversion
    - Store as `filename.ext.gz` (e.g., `invoice.pdf.gz`)
- AC2.3: Original file preserved if compression ratio < 10% or file size < 100KB
- AC2.4: Compression ratio logged to `application/logs/` with format:
  ```
  INFO - Attachment compression: file=invoice.pdf, original=2.5MB, compressed=450KB, ratio=82%, method=gzip
  INFO - Attachment compression: file=scan.jpg, original=5.2MB (3000x2000), compressed=850KB (1600x1067), ratio=84%, method=gd+gzip
  ```
- AC2.5: Files decompressed and served transparently on download
- AC2.6: Image resolution suitable for printing (300 DPI at A4 = ~1600x1200 pixels)
- AC2.7: Smartphone photos automatically optimized (typically 3-8MB → 500KB-1MB)

---

### 5.3 FR3: Batch Compression of Existing Attachments (Priority: MEDIUM)

**Description:** Provide CLI script for administrators to compress previously uploaded attachments.

**User Story:**
> As an administrator, I want to compress already uploaded attachments to save place, so that I can recover storage space from historical files without losing data.

**Acceptance Criteria:**
- AC3.1: CLI script available: `scripts/batch_compress_attachments.php`
- AC3.2: Supports dry-run mode for testing without changes
- AC3.3: Supports filtering by year, section, file type, minimum size
- AC3.4: Shows progress bar and estimated time remaining
- AC3.5: Generates detailed report of compression results
- AC3.6: Supports resume on interruption
- AC3.7: Backs up original files before compression
- AC3.8: Rolls back on compression failure
- AC3.9: Logs all operations with compression statistics
- AC3.10 Treasurers can still view or download previous attachments

---

### 5.4 FR4: Transparent Decompression (Priority: as HIGH than file compression)

**Description:** Automatically decompress files when they are displayed or downloaded.

**User Story:**
> As a treasurer, I want to view or download attachments in their original usable format, without needing to know they were compressed for storage.

**Acceptance Criteria:**
- AC4.1: When user clicks attachment link, system detects if file is compressed (`.gz` extension)
- AC4.2: If compressed, decompress on-the-fly using PHP `gzdecode()` before serving
- AC4.3: Original filename restored (remove `.gz` extension)
  - Images: Serve as `filename.jpg` (converted format)
  - Other files: Serve with original extension (e.g., `invoice.pdf`)
- AC4.4: Content-Type header matches served file format
- AC4.5: No UI indication that file was compressed or resized
- AC4.6: Download/view performance acceptable (<2 second delay for files up to 20MB)
- AC4.7: Users unaware that smartphone photos were resized (transparent optimization)

---

## 6. Non-Functional Requirements

### 6.1 Performance
- File upload with compression completes within 3 seconds for files <10MB
  - Image compression (resize + gzip): 1-2 seconds
  - Document compression (gzip only): 0.5-1 second
- Compression operations use pure PHP (no external processes)
- Temporary file cleanup runs daily without impacting system performance
- Download decompression adds <1 second to file serving time (in-memory operation)
- Batch compression processes ~30-50 files per minute (depending on file types and sizes)

### 6.2 Storage
- Achieve overall storage reduction of 40-70% for typical treasurer workflow:
  - **Smartphone photos (3-8MB):** 80-90% reduction → 500KB-1MB
  - **Scanned images (1-5MB):** 60-80% reduction
  - **Text files (TXT, CSV):** 80-95% reduction
  - **Office documents (PDF, DOCX, XLSX):** 10-40% reduction
  - **Already compressed (ZIP, RAR):** Skip compression
- Temporary file storage capped at 500MB
- Automatic cleanup of abandoned temporary files after 24 hours

### 6.3 Reliability
- Compression failures fall back to storing original file
- Original file preserved until compression confirmed successful
- Atomic file operations (temp → permanent) to prevent data loss
- Rollback capability if compression corrupts file

### 6.4 Compatibility
- Works with PHP 7.4
- Compatible with existing CodeIgniter 2.x framework
- No breaking changes to database schema
- Backward compatible with existing uncompressed attachments
- Supports existing workflows without modification

### 6.5 Usability
- No change to user interface complexity
- Inline upload feels like single workflow
- No training required for existing users
- Clear error messages if upload/compression fails

---

## 7. Success Metrics

| Metric | Current | Target | How to Measure |
|--------|---------|--------|----------------|
| Avg time to create accounting line with attachment | ~3 min (create + edit + attach) | <1 min (single form) | User workflow timing |
| Storage usage for attachments | 100% (no compression) | 30-50% | `du -sh uploads/attachments/` |
| Average compression ratio | N/A | 50-70% (with image optimization) | Log analysis |
| Smartphone photo size | 3-8 MB | 500KB-1MB | Log analysis |
| Forgotten attachments | ~10% of entries | <2% | Audit of accounting lines |
| Treasurer satisfaction | Baseline survey | >80% satisfied | User survey |

---

## 8. Risks and Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Compression corrupts files | HIGH | LOW | Preserve original until verified; extensive testing |
| Temp file storage fills disk | MEDIUM | MEDIUM | Strict size limits and 24-hour cleanup |
| Session-based approach fails on restart | MEDIUM | LOW | Store temp file metadata in database |
| Image compression degrades quality | MEDIUM | MEDIUM | Configurable quality settings; A/B test with users |
| PDF compression breaks features | HIGH | MEDIUM | Detect PDF features before compression; skip if risky |
| Performance impact on upload | LOW | MEDIUM | Make compression asynchronous; progress indicator |
| User accidentally removes files | LOW | HIGH | Add confirmation dialog; allow undo |

---

## 9. Dependencies and Prerequisites

### 9.1 System Requirements

**PHP Extensions (Required):**
- `zlib` (for gzip compression/decompression) - Usually enabled by default in PHP 7.4
- `gd` (for image resizing and optimization) - Available on production server

**Check Commands:**
```bash
php7.4 -m | grep -E 'zlib|gd'
```

**Expected Output:**
```
gd
zlib
```

**No External Tools Required:**
- ✅ No Ghostscript needed
- ✅ No ImageMagick CLI needed
- ✅ No LibreOffice needed
- ✅ Pure PHP implementation using built-in extensions only

### 9.2 Configuration Prerequisites

- Ensure `uploads/attachments/temp/` is writable (0777 during development)
- PHP `upload_max_filesize` configured appropriately (currently 20MB)
- PHP `post_max_size` sufficient for multiple files
- `max_file_uploads` set to 20 or higher

---

## 10. Out of Scope

The following items are explicitly out of scope for this release:

1. Drag-and-drop file upload interface
2. Image preview/thumbnails before upload
3. OCR for scanned documents
4. Attachment versioning
5. Attachment sharing across multiple accounting lines
6. Cloud storage integration (S3/Google Drive)
7. Automatic deletion of old attachments
8. Scheduled/automated batch compression (cron job)
9. Compression analytics dashboard
10. Inline attachment upload for other controllers (beyond `compta`)

## 11. Future Enhancements

The following features could be added in future iterations:

### 11.1 Configurable Image Quality Settings

**Description:** Allow administrators to configure image compression parameters

**Potential Settings:**
- Maximum image dimensions (default: 1600x1200)
- JPEG quality (default: 85)
- Different profiles for receipts vs. photos
- Option to preserve original resolution for specific file types

**Effort Estimate:** 2-4 hours

**Priority:** LOW - Default settings should work for most use cases

---

## 12. Open Questions

1. **Q:** Should we provide a "disable compression" option per file type?
   **A:** TBD - Gather user feedback after initial rollout

2. **Q:** Should compression be synchronous or asynchronous?
   **A:** Start with synchronous; move to async if performance issues arise

3. **Q:** Should original file be permanently kept as backup?
   **A:** No, storage savings are primary goal; rely on database/file backups

4. **Q:** How to handle compression for other referenced tables (not just `ecritures`)?
   **A:** Inline attachment upload can be generalized to other controllers later

5. **Q:** Should batch compression run automatically on a schedule?
   **A:** Out of scope for initial release; administrator can run manually or set up cron job

6. **Q:** How long should batch compression backup files be retained?
   **A:** TBD - Recommend 7 days with configurable retention period

---

## 13. Related Documents

- **Implementation Plan:** `doc/plans/attachments_improvement_plan.md` (design, architecture, implementation details)
- **Current System Documentation:** `doc/plans/attachments_directory_reorganization.md` (migration 039)
- **Development Workflow:** `doc/development/workflow.md`
- **Project Context:** `CLAUDE.md`, `.github/copilot-instructions.md`

---

## 14. Approval and Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Product Owner | [TBD] | | |
| Treasurer (User Rep) | [TBD] | | |
| System Administrator | [TBD] | | |
| Developer | [TBD] | | |

---

**End of PRD**
