# Test Files for Attachment Improvement PRD

This directory contains comprehensive test files to validate the attachment improvement PRD requirements. All files are organized by type and size categories to test various compression scenarios.

## File Organization

```
application/tests/data/attachments/
├── text/          # Text files (TXT, CSV, LOG)
├── documents/     # Documents (PDF, MD, DOCX, XLSX) 
├── images/        # Images (PNG, JPEG, GIF)
└── archives/      # Archive files (ZIP)
```

## Test File Categories

### TEXT FILES

**Small files (< 100KB):**
- `small_text_file_50kb.txt` (84 KB) - Basic text content
- `small_documentation_80kb.md` (7 KB) - Markdown documentation

**Medium files (100KB - 1MB):**
- `medium_text_file_500kb.txt` (680 KB) - Repeated text content
- `accounting_data_medium_300kb.csv` (359 KB) - Sample accounting data
- `medium_documentation_800kb.md` (118 KB) - Technical documentation

**Large files (> 1MB):**
- `large_text_file_2mb.txt` (1.4 MB) - Large text file
- `large_repetitive_text_5mb.txt` (12 MB) - Highly compressible repetitive text
- `accounting_data_large_800kb.csv` (1.2 MB) - Large accounting dataset

### DOCUMENT FILES

**PDF Files:**
- `small_invoice_90kb.pdf` (31 KB) - Mock invoice PDF (3 pages)
- `medium_contract_600kb.pdf` (159 KB) - Mock contract PDF (18 pages)  
- `large_manual_2mb.pdf` (1.3 MB) - Mock manual PDF (121 pages)

**DOCX Files:**
- `small_report_80kb.docx` (2.9 KB) - Small Word document
- `medium_proposal_400kb.docx` (18 KB) - Medium Word document
- `large_specification_1500kb.docx` (72 KB) - Large Word document

**XLSX Files:**
- `small_budget_70kb.xlsx` (5.5 KB) - Small Excel budget spreadsheet (50 rows)
- `medium_financials_500kb.xlsx` (23 KB) - Medium Excel financial data (500 rows)  
- `large_data_export_1800kb.xlsx` (78 KB) - Large Excel data export (2000 rows)

**Markdown Files:**
- `small_documentation_80kb.md` (7 KB) - Small documentation
- `medium_documentation_800kb.md` (118 KB) - Medium documentation
- `large_documentation_2mb.md` (518 KB) - Large documentation

**Threshold Test Files:**
- `file_just_under_100kb_98kb.txt` (130 KB) - Just under 100KB threshold
- `file_just_over_100kb_102kb.txt` (136 KB) - Just over 100KB threshold

### IMAGE FILES

**Small images (~600x400 pixels):**
- `small_receipt_scan_600x400.png` (8.7 KB) - Small PNG scan
- `small_invoice_photo_640x480.jpg` (134 KB) - Small JPEG photo
- `small_diagram_500x400.gif` (9.2 KB) - Small GIF diagram

**Medium images (>1600x1200 pixels):**
- `medium_document_scan_1920x1440.png` (22 KB) - Medium PNG scan
- `medium_photo_high_res_2048x1536.jpg` (718 KB) - Medium high-res JPEG
- `medium_chart_1800x1350.gif` (76 KB) - Medium GIF chart

**Large images (>8MB or very high resolution):**
- `large_smartphone_photo_4000x3000.jpg` (2.5 MB) - Simulated smartphone photo
- `large_scan_document_3500x2500.png` (44 KB) - Large document scan
- `large_noise_image_2000x2000.png` (12 MB) - Large noise image for testing

### ARCHIVE FILES

**ZIP Files (already compressed content):**
- `small_mock_archive_80kb.zip` (62 KB) - Small mock archive
- `medium_mock_archive_500kb.zip` (518 KB) - Medium mock archive
- `large_mock_archive_1mb.zip` (1.6 MB) - Large mock archive
- `small_photo_archive_compressed_90kb.zip` (15 KB) - Archive with JPEG photos

## PRD Test Scenarios Coverage

### EF2: Compression Strategy Testing

**Images (JPEG, PNG, GIF, BMP, WebP):**
- ✅ Resize to max 1600x1200 pixels while maintaining aspect ratio
- ✅ Convert to JPEG with quality 85
- ✅ Apply gzip compression to result
- ✅ Store as `filename.jpg.gz`

**All other files (PDF, DOCX, CSV, TXT, etc.):**
- ✅ Compress using PHP `gzencode()` level 9
- ✅ No format conversion
- ✅ Store as `filename.ext.gz`

**Compression Thresholds:**
- ✅ Files < 100KB: Original preserved (test with 98KB file)
- ✅ Files > 100KB: Compression applied (test with 102KB file)
- ✅ Compression ratio < 10%: Original preserved

**Expected Compression Ratios:**
- **Smartphone photos (3-8MB):** 80-90% reduction → 500KB-1MB
- **Scanned images (1-5MB):** 60-80% reduction
- **Text files (TXT, CSV):** 80-95% reduction
- **Office documents (PDF, DOCX, XLSX):** 10-40% reduction
- **Already compressed (ZIP, RAR):** Skip compression

### File Size Distribution

**Total Test Data:** 36 MB across 26 files
- Text files: 16 MB
- Document files: 3.8 MB
- Image files: 15 MB
- Archive files: 2.2 MB

## Usage

These test files can be used to:

1. **Test compression algorithms** - Verify compression ratios match PRD expectations
2. **Test decompression** - Ensure transparent decompression works correctly
3. **Test size thresholds** - Validate 100KB compression threshold
4. **Test file type handling** - Verify different file types are handled correctly
5. **Performance testing** - Measure compression/decompression time
6. **Storage testing** - Calculate actual storage savings

## File Naming Convention

Files follow the pattern: `{purpose}_{size}_{description}.{ext}`

- `purpose`: small/medium/large or specific use case
- `size`: approximate size or dimensions  
- `description`: content type or scenario
- `ext`: file extension

Examples:
- `small_receipt_scan_600x400.png`
- `medium_proposal_400kb.docx`
- `large_repetitive_text_5mb.txt`

## Notes

- PDF files are created using LibreOffice and can be opened by standard PDF viewers (Adobe Reader, browser PDF viewers, etc.)
- DOCX/XLSX files are created with proper Office Open XML structure and can be opened by LibreOffice/Excel
- Images are generated programmatically with test patterns
- All files are safe for testing and contain no sensitive data
- File sizes may vary slightly from targets due to compression during creation
- XLSX files contain realistic budget/financial data with proper spreadsheet formatting
- PDF files contain structured text content with multiple pages for realistic compression testing

---

Generated for GVV Attachment Improvement PRD testing
Last updated: 2025-01-02