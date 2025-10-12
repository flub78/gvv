#!/usr/bin/env python3
"""
Batch Compression Script for Existing Attachments

Processes ONLY PDFs and compressible images (JPEG, PNG, GIF, WebP).
Other file types (DOCX, CSV, TXT, etc.) are skipped.

This script works by scanning the filesystem in uploads/attachments/ directory
and does not require database access.

Usage: python3 scripts/batch_compress_attachments.py [options]

Options:
  --dry-run         Preview changes without actually compressing
  --verbose         Show detailed progress
  --year=YYYY       Only compress attachments from specific year
  --section=NAME    Only compress attachments from specific section
  --type=TYPE       Only compress specific file type (pdf or image)
  --min-size=SIZE   Only compress files larger than SIZE (e.g., 100KB, 1MB)
  --limit=N         Limit to first N files (for testing)
  --resume          Resume from last interruption

Examples:
  python3 scripts/batch_compress_attachments.py --dry-run --verbose
  python3 scripts/batch_compress_attachments.py --year=2024 --min-size=100KB
  python3 scripts/batch_compress_attachments.py --type=pdf --limit=10
  python3 scripts/batch_compress_attachments.py --type=image --limit=5

Requirements:
  - Python 3.6+
  - Pillow (for image compression): pip3 install Pillow
  - Ghostscript (for PDF compression): apt-get install ghostscript
"""

import sys
import os
import argparse
import json
import time
import re
import subprocess
import glob
from pathlib import Path
from datetime import datetime

try:
    from PIL import Image
    PIL_AVAILABLE = True
except ImportError:
    PIL_AVAILABLE = False
    print("WARNING: Pillow not installed. Image compression will be skipped.")
    print("Install with: pip3 install Pillow")


class BatchAttachmentCompressor:
    """Batch compressor for PDF and image attachments (filesystem-based)."""

    def __init__(self, args):
        self.args = args
        self.stats = {
            'total': 0,
            'processed': 0,
            'compressed': 0,
            'skipped': 0,
            'errors': 0,
            'original_size': 0,
            'compressed_size': 0
        }
        self.start_time = time.time()
        self.progress_file = './application/logs/batch_compression_progress.json'
        self.compression_config = self.load_compression_config()
        self.uploads_dir = './uploads/attachments/'

    def load_compression_config(self):
        """Load compression configuration."""
        return {
            'min_size': 102400,  # 100KB
            'min_ratio': 0.10,   # 10% minimum savings
            'image_max_width': 1600,
            'image_max_height': 1200,
            'image_quality': 85,
            'pdf_quality': 'ebook'  # /ebook = 150 DPI
        }

    def scan_filesystem_for_attachments(self, exclude_files=None):
        """Scan filesystem for PDF and image files to compress."""
        if exclude_files is None:
            exclude_files = set()
        else:
            exclude_files = set(exclude_files)

        if not os.path.exists(self.uploads_dir):
            print(f"ERROR: Uploads directory not found: {self.uploads_dir}")
            return []

        # Build file pattern based on filters
        patterns = []
        
        # Determine which file types to include
        if self.args.type == 'pdf':
            extensions = ['*.pdf']
        elif self.args.type == 'image':
            extensions = ['*.jpg', '*.jpeg', '*.png', '*.gif', '*.webp']
        else:
            # Default: both PDFs and images
            extensions = ['*.pdf', '*.jpg', '*.jpeg', '*.png', '*.gif', '*.webp']

        # Build directory path based on year/section filters
        if self.args.year and self.args.section:
            base_pattern = os.path.join(self.uploads_dir, str(self.args.year), self.args.section)
        elif self.args.year:
            base_pattern = os.path.join(self.uploads_dir, str(self.args.year), "*")
        elif self.args.section:
            base_pattern = os.path.join(self.uploads_dir, "*", self.args.section)
        else:
            base_pattern = os.path.join(self.uploads_dir, "*", "*")

        # Collect all matching files
        files = []
        for ext in extensions:
            pattern = os.path.join(base_pattern, ext)
            matching_files = glob.glob(pattern, recursive=False)
            
            # Also check subdirectories
            pattern_recursive = os.path.join(base_pattern, "**", ext)
            matching_files.extend(glob.glob(pattern_recursive, recursive=True))
            
            files.extend(matching_files)

        # Remove duplicates and convert to list of dicts with file info
        unique_files = []
        seen_files = set()

        for file_path in files:
            # Skip if already processed
            if file_path in exclude_files:
                continue
                
            # Skip duplicates
            if file_path in seen_files:
                continue
            seen_files.add(file_path)

            # Skip if file doesn't exist or is not a regular file
            if not os.path.isfile(file_path):
                continue

            # Check minimum size filter
            if self.args.min_size:
                try:
                    file_size = os.path.getsize(file_path)
                    if file_size < self.args.min_size:
                        continue
                except OSError:
                    continue

            # Create file info dict (simulating database record structure)
            file_info = {
                'file_path': file_path,
                'file_name': os.path.basename(file_path),
                'size': os.path.getsize(file_path) if os.path.exists(file_path) else 0
            }
            
            unique_files.append(file_info)

        # Sort by file path for consistent processing order
        unique_files.sort(key=lambda x: x['file_path'])
        
        # Apply limit if specified
        if self.args.limit:
            unique_files = unique_files[:self.args.limit]

        return unique_files

    def compress_image(self, file_path):
        """Compress image file using Pillow."""
        if not PIL_AVAILABLE:
            return {'success': False, 'error': 'Pillow not available'}

        try:
            # Open image
            img = Image.open(file_path)
            original_size = os.path.getsize(file_path)

            # Get original dimensions
            width, height = img.size
            original_dimensions = f"{width}x{height}"

            # Calculate new dimensions
            max_width = self.compression_config['image_max_width']
            max_height = self.compression_config['image_max_height']

            if width > max_width or height > max_height:
                ratio = min(max_width / width, max_height / height)
                new_width = int(width * ratio)
                new_height = int(height * ratio)
                img = img.resize((new_width, new_height), Image.Resampling.LANCZOS)
            else:
                new_width, new_height = width, height

            new_dimensions = f"{new_width}x{new_height}"

            # Save to temporary file
            temp_path = file_path + '.tmp'
            extension = os.path.splitext(file_path)[1].lower()

            # Save in original format
            if extension in ['.jpg', '.jpeg']:
                img.convert('RGB').save(temp_path, 'JPEG', quality=self.compression_config['image_quality'], optimize=True)
                method = 'pillow/resize+jpeg'
            elif extension == '.png':
                img.save(temp_path, 'PNG', optimize=True)
                method = 'pillow/resize+png'
            elif extension == '.gif':
                img.save(temp_path, 'GIF', optimize=True)
                method = 'pillow/resize+gif'
            elif extension == '.webp':
                img.save(temp_path, 'WEBP', quality=self.compression_config['image_quality'])
                method = 'pillow/resize+webp'
            else:
                return {'success': False, 'error': 'Unsupported image format'}

            # Check compression ratio
            compressed_size = os.path.getsize(temp_path)
            ratio = 1 - (compressed_size / original_size)

            if ratio < self.compression_config['min_ratio']:
                os.remove(temp_path)
                return {'success': False, 'error': 'Compression ratio too low'}

            # Replace original
            os.replace(temp_path, file_path)

            return {
                'success': True,
                'compressed_path': file_path,
                'original_size': original_size,
                'compressed_size': compressed_size,
                'ratio': ratio,
                'method': method,
                'dimensions': f"{original_dimensions} → {new_dimensions}"
            }

        except Exception as e:
            return {'success': False, 'error': str(e)}

    def compress_pdf(self, file_path):
        """Compress PDF file using Ghostscript."""
        try:
            # Check if Ghostscript is available
            result = subprocess.run(['gs', '--version'], capture_output=True, text=True)
            if result.returncode != 0:
                return {'success': False, 'error': 'Ghostscript not available'}

            original_size = os.path.getsize(file_path)
            temp_path = file_path + '.tmp.pdf'
            quality = self.compression_config['pdf_quality']

            # Build Ghostscript command
            cmd = [
                'gs',
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                f'-dPDFSETTINGS=/{quality}',
                '-dNOPAUSE',
                '-dQUIET',
                '-dBATCH',
                f'-sOutputFile={temp_path}',
                file_path
            ]

            # Execute Ghostscript
            result = subprocess.run(cmd, capture_output=True, text=True)

            if result.returncode != 0 or not os.path.exists(temp_path):
                if os.path.exists(temp_path):
                    os.remove(temp_path)
                return {'success': False, 'error': f'Ghostscript failed: {result.stderr}'}

            # Check compression ratio
            compressed_size = os.path.getsize(temp_path)
            if compressed_size == 0:
                os.remove(temp_path)
                return {'success': False, 'error': 'Ghostscript produced empty file'}

            ratio = 1 - (compressed_size / original_size)

            if ratio < self.compression_config['min_ratio']:
                os.remove(temp_path)
                return {'success': False, 'error': 'Compression ratio too low'}

            # Replace original
            os.replace(temp_path, file_path)

            return {
                'success': True,
                'compressed_path': file_path,
                'original_size': original_size,
                'compressed_size': compressed_size,
                'ratio': ratio,
                'method': f'ghostscript/{quality}'
            }

        except Exception as e:
            return {'success': False, 'error': str(e)}

    def process_attachment(self, file_info, current_number):
        """Process a single attachment."""
        file_path = file_info['file_path']
        file_name = file_info['file_name']

        # Check if file exists
        if not os.path.exists(file_path):
            self.stats['errors'] += 1
            print(f"ERROR: File not found: {file_path}")
            return

        # Verify file type
        extension = os.path.splitext(file_path)[1].lower()
        valid_extensions = ['.pdf', '.jpg', '.jpeg', '.png', '.gif', '.webp']

        if extension not in valid_extensions:
            self.stats['skipped'] += 1
            if self.args.verbose:
                print(f"SKIP: Unsupported type: {file_name}")
            return

        # Check if already compressed (skip compressed archives)
        if extension in ['.gz', '.zip', '.rar', '.7z']:
            self.stats['skipped'] += 1
            if self.args.verbose:
                print(f"SKIP: Already compressed: {file_name}")
            return

        original_size = os.path.getsize(file_path)

        # Check minimum size
        if original_size < self.compression_config['min_size']:
            self.stats['skipped'] += 1
            if self.args.verbose:
                print(f"SKIP: Too small: {file_name} ({self.format_bytes(original_size)})")
            return

        # Show progress
        if not self.args.verbose:
            self.show_progress(current_number)
        else:
            print(f"Processing: {file_name} ({self.format_bytes(original_size)})")

        self.stats['processed'] += 1

        # Skip compression in dry-run mode
        if self.args.dry_run:
            self.stats['compressed'] += 1
            self.stats['compressed_size'] += original_size
            return

        # Attempt compression
        if extension in ['.jpg', '.jpeg', '.png', '.gif', '.webp']:
            result = self.compress_image(file_path)
        elif extension == '.pdf':
            result = self.compress_pdf(file_path)
        else:
            result = {'success': False, 'error': 'Unsupported file type'}

        if result['success']:
            self.stats['compressed_size'] += result['compressed_size']
            self.stats['compressed'] += 1

            if self.args.verbose:
                ratio_pct = round(result['ratio'] * 100, 1)
                dims = result.get('dimensions', '')
                if dims:
                    print(f"  SUCCESS: {self.format_bytes(result['original_size'])} → {self.format_bytes(result['compressed_size'])} ({ratio_pct}% saved, {dims})")
                else:
                    print(f"  SUCCESS: {self.format_bytes(result['original_size'])} → {self.format_bytes(result['compressed_size'])} ({ratio_pct}% saved)")
        else:
            self.stats['skipped'] += 1
            if self.args.verbose:
                print(f"  SKIPPED: {result['error']}")

    def show_progress(self, current):
        """Show progress bar."""
        percent = round((current / self.stats['total']) * 100)
        bar_width = 40
        filled = round((percent / 100) * bar_width)
        empty = bar_width - filled

        bar = '[' + '█' * filled + '░' * empty + ']'

        # Calculate ETA
        elapsed = time.time() - self.start_time
        if elapsed > 0 and current > 0:
            rate = current / elapsed
            remaining = (self.stats['total'] - current) / rate
            eta = self.format_time(remaining)
        else:
            eta = 'calculating...'

        # Calculate storage saved
        saved = self.stats['original_size'] - self.stats['compressed_size']
        saved_percent = round((saved / self.stats['original_size']) * 100) if self.stats['original_size'] > 0 else 0

        print(f"\rProcessing: {bar} {percent}% ({current}/{self.stats['total']}) | ETA: {eta} | Saved: {self.format_bytes(saved)} ({saved_percent}%)", end='', flush=True)

        if current == self.stats['total']:
            print()  # Newline at end

    def save_progress(self, processed_files):
        """Save progress to file."""
        progress = {
            'timestamp': int(time.time()),
            'processed_files': processed_files,
            'stats': self.stats
        }

        # Ensure logs directory exists
        os.makedirs(os.path.dirname(self.progress_file), exist_ok=True)

        with open(self.progress_file, 'w') as f:
            json.dump(progress, f)

    def load_progress(self):
        """Load progress from file."""
        if not os.path.exists(self.progress_file):
            return []

        try:
            with open(self.progress_file, 'r') as f:
                progress = json.load(f)
            return progress.get('processed_files', [])
        except:
            return []

    def run(self):
        """Run batch compression."""
        print("=== Batch Attachment Compression (PDFs and Images Only) ===")
        print(f"Mode: {'DRY RUN' if self.args.dry_run else 'LIVE'}")
        print(f"Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"Scanning directory: {os.path.abspath(self.uploads_dir)}")

        # Show filters
        filters = []
        if self.args.year:
            filters.append(f"year={self.args.year}")
        if self.args.section:
            filters.append(f"section={self.args.section}")
        if self.args.type:
            filters.append(f"type={self.args.type}")
        if self.args.min_size:
            filters.append(f"min_size={self.format_bytes(self.args.min_size)}")
        if self.args.limit:
            filters.append(f"limit={self.args.limit}")

        if filters:
            print(f"Filters: {', '.join(filters)}")
        print()

        # Load progress if resuming
        processed_files = []
        if self.args.resume:
            processed_files = self.load_progress()
            if processed_files:
                print(f"Resuming from previous run ({len(processed_files)} files already processed)\n")

        # Get attachments to compress
        print("Scanning filesystem for PDFs and images...")
        file_list = self.scan_filesystem_for_attachments(processed_files if processed_files else None)
        self.stats['total'] = len(file_list)

        if self.stats['total'] == 0:
            print("No PDFs or images found matching criteria.")
            return True

        # Calculate total size
        for file_info in file_list:
            if os.path.exists(file_info['file_path']):
                self.stats['original_size'] += os.path.getsize(file_info['file_path'])

        print(f"Found {self.stats['total']} PDFs/images to process ({self.format_bytes(self.stats['original_size'])} total)\n")

        # Process each attachment
        for index, file_info in enumerate(file_list, 1):
            self.process_attachment(file_info, index)
            processed_files.append(file_info['file_path'])

            # Save progress
            if not self.args.dry_run:
                self.save_progress(processed_files)

        if not self.args.verbose and self.stats['total'] > 0:
            print()  # Newline after progress bar

        # Print summary
        self.print_summary()

        # Delete progress file on successful completion
        if not self.args.dry_run and os.path.exists(self.progress_file):
            os.remove(self.progress_file)

        return self.stats['errors'] == 0

    def print_summary(self):
        """Print summary statistics."""
        print("\n=== Summary ===")
        print(f"Total attachments: {self.stats['total']}")
        print(f"Processed: {self.stats['processed']}")
        print(f"Successfully compressed: {self.stats['compressed']}")
        print(f"Skipped: {self.stats['skipped']}")
        print(f"Errors: {self.stats['errors']}")
        print("---")
        print(f"Storage before: {self.format_bytes(self.stats['original_size'])}")
        print(f"Storage after: {self.format_bytes(self.stats['compressed_size'])}")

        saved = self.stats['original_size'] - self.stats['compressed_size']
        saved_percent = round((saved / self.stats['original_size']) * 100) if self.stats['original_size'] > 0 else 0

        print(f"Total saved: {self.format_bytes(saved)} ({saved_percent}%)")

        elapsed = time.time() - self.start_time
        print("---")
        print(f"Elapsed time: {self.format_time(elapsed)}")

        if self.args.dry_run:
            print("\n** This was a DRY RUN - no changes were made **")

    @staticmethod
    def format_bytes(bytes_val):
        """Format bytes as human-readable string."""
        if bytes_val < 1024:
            return f"{bytes_val} B"
        elif bytes_val < 1024 * 1024:
            return f"{bytes_val / 1024:.1f} KB"
        elif bytes_val < 1024 * 1024 * 1024:
            return f"{bytes_val / (1024 * 1024):.1f} MB"
        else:
            return f"{bytes_val / (1024 * 1024 * 1024):.2f} GB"

    @staticmethod
    def format_time(seconds):
        """Format seconds as human-readable string."""
        if seconds < 60:
            return f"{round(seconds)}s"
        elif seconds < 3600:
            return f"{round(seconds / 60)}m {round(seconds % 60)}s"
        else:
            hours = int(seconds / 3600)
            minutes = int((seconds % 3600) / 60)
            return f"{hours}h {minutes}m"

    @staticmethod
    def parse_size(size_str):
        """Parse size string (e.g., '100KB', '1MB') to bytes."""
        size_str = size_str.upper().strip()
        
        # Order matters - check longer units first
        multipliers = [
            ('GB', 1024 * 1024 * 1024),
            ('MB', 1024 * 1024),
            ('KB', 1024),
            ('B', 1)
        ]

        for unit, multiplier in multipliers:
            if size_str.endswith(unit):
                value_str = size_str[:-len(unit)].strip()
                if value_str:
                    value = float(value_str)
                    return int(value * multiplier)

        # If no unit found, assume bytes
        try:
            return int(float(size_str))
        except ValueError:
            raise ValueError(f"Invalid size format: {size_str}")


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description='Batch compress PDF and image attachments',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )

    parser.add_argument('--dry-run', action='store_true',
                        help='Preview changes without actually compressing')
    parser.add_argument('--verbose', action='store_true',
                        help='Show detailed progress')
    parser.add_argument('--year', type=int,
                        help='Only compress attachments from specific year')
    parser.add_argument('--section', type=str,
                        help='Only compress attachments from specific section')
    parser.add_argument('--type', type=str, choices=['pdf', 'image'],
                        help='Only compress specific file type')
    parser.add_argument('--min-size', type=str,
                        help='Only compress files larger than SIZE (e.g., 100KB, 1MB)')
    parser.add_argument('--limit', type=int,
                        help='Limit to first N files (for testing)')
    parser.add_argument('--resume', action='store_true',
                        help='Resume from last interruption')

    args = parser.parse_args()

    # Parse min_size if provided
    if args.min_size:
        try:
            args.min_size = BatchAttachmentCompressor.parse_size(args.min_size)
        except:
            print(f"ERROR: Invalid size format: {args.min_size}")
            sys.exit(1)

    # Run compressor
    compressor = BatchAttachmentCompressor(args)
    success = compressor.run()

    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
