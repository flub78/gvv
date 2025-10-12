# Batch Attachment Compression Script

## Overview

This Python script compresses existing PDF and image attachments in the GVV system without requiring database access. It scans the filesystem in `uploads/attachments/` directory and processes files directly.

## Features

- **PDF Compression**: Uses Ghostscript with `/ebook` quality (150 DPI)
- **Image Compression**: Uses Pillow to resize and recompress in original format
- **Filesystem-based**: No database dependencies required
- **Filtering**: By year, section, file type, and minimum size
- **Progress tracking**: With resume capability
- **Dry-run mode**: Preview changes without modifying files

## Requirements

```bash
# Python dependencies
pip3 install Pillow

# System dependencies
sudo apt-get install ghostscript
```

## Usage

### Basic Examples

```bash
# Preview what would be compressed (dry run)
python3 scripts/batch_compress_attachments.py --dry-run --verbose

# Compress all PDFs and images with detailed output
python3 scripts/batch_compress_attachments.py --verbose

# Compress only large files (>1MB)
python3 scripts/batch_compress_attachments.py --min-size=1MB

# Compress only PDFs from 2024
python3 scripts/batch_compress_attachments.py --type=pdf --year=2024

# Test with a small number of files
python3 scripts/batch_compress_attachments.py --limit=10 --dry-run
```

### Advanced Examples

```bash
# Compress images from specific section
python3 scripts/batch_compress_attachments.py --type=image --section=Avion

# Resume interrupted compression
python3 scripts/batch_compress_attachments.py --resume

# Compress files larger than 500KB, limit to 20 files
python3 scripts/batch_compress_attachments.py --min-size=500KB --limit=20
```

## Configuration

The script uses these default compression settings:

- **Minimum file size**: 100KB (smaller files skipped)
- **Minimum compression ratio**: 10% (files with less savings kept as original)
- **Image max dimensions**: 1600x1200 pixels
- **Image quality**: 85% for JPEG/WebP
- **PDF quality**: `/ebook` (150 DPI)

## File Processing

### Supported Formats
- **PDFs**: `.pdf` files compressed with Ghostscript
- **Images**: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp` files resized and recompressed

### Processing Logic
1. **Images**: Resized to max 1600x1200, recompressed in original format (JPEG stays JPEG, PNG stays PNG)
2. **PDFs**: Optimized with Ghostscript `/ebook` quality, kept as PDF files
3. **Other files**: Skipped (no gzip compression in this version)

### File Organization
The script scans: `uploads/attachments/YEAR/SECTION/` directories

## Output

### Progress Display
```
Processing: [████████████████████████████████████████] 100% (15/15) | ETA: 2s | Saved: 45.2 MB (78%)
```

### Summary Report
```
=== Summary ===
Total attachments: 15
Processed: 12
Successfully compressed: 10
Skipped: 2
Errors: 0
---
Storage before: 58.3 MB
Storage after: 13.1 MB
Total saved: 45.2 MB (78%)
---
Elapsed time: 1m 30s
```

## Safety Features

- **Dry-run mode**: Test without making changes
- **Progress tracking**: Resume interrupted operations
- **Minimum compression ratio**: Only keep compressed files if significant savings
- **Error handling**: Continue processing even if individual files fail
- **Backup consideration**: Original files are replaced only after successful compression

## Troubleshooting

### Common Issues

1. **"Pillow not available"**: Install with `pip3 install Pillow`
2. **"Ghostscript not available"**: Install with `sudo apt-get install ghostscript`
3. **"No files found"**: Check that `uploads/attachments/` directory exists and contains files
4. **Permission errors**: Ensure write permissions on files and directories

### Verification

After compression, you can verify the results by:
1. Checking file sizes: `ls -lh uploads/attachments/2024/Section/`
2. Testing file opening: Open compressed PDFs and images
3. Reviewing logs: Check progress file in `application/logs/`

## Integration Notes

This script is designed to work with the GVV attachment system described in `doc/plans/attachments_improvement_plan.md`. It processes only PDFs and compressible images as specified in the implementation plan.