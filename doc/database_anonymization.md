# Database Anonymization Tool

This document describes the `bin/anonymize.py` script for anonymizing GVV **SQL database backups**.

## Purpose

The anonymization tool allows you to create safe, anonymized copies of GVV **SQL database backups** for:
- Development and testing environments
- Training and demonstrations
- Sharing with developers without exposing personal data
- Bug reproduction in non-production environments

⚠️ **IMPORTANT**: This script is designed for **SQL database dumps**, not for media archives (attachments, images, PDFs).

## Supported File Types

### ✅ Supported (SQL Database Backups)
- **`.sql`** - Plain SQL dump files
- **`.sql.gz`** - Compressed SQL dump files (gzip)
- **`.zip`** - ZIP archives containing SQL dump files

### ❌ Not Supported (Media/Attachment Archives)
- **`.tar.gz`** - TAR archives (typically media/attachments)
- **`.tar`** - TAR archives  
- **`.tar.bz2`** - Compressed TAR archives
- **Media files** - Images, PDFs, documents

## File Type Detection

The script automatically detects the file type and will refuse to process media archives:

```bash
# SQL database backup (✅ Supported)
python bin/anonymize.py database_backup.sql.gz
✅ Processing SQL database backup...

# Media archive (❌ Not supported)  
python bin/anonymize.py media_attachments.tar.gz
❌ ERROR: This appears to be a media/attachments archive, not a SQL database backup.
```

## Features

- **Complete anonymization** of personal data (names, emails, phones, addresses)
- **Referential integrity preservation** - same person gets same fake name everywhere
- **Database structure preservation** - all tables, indexes, constraints maintained
- **Realistic French data** - uses authentic French names, addresses, phone patterns
- **Multiple formats supported** - .sql, .sql.gz, .zip files
- **Consistent results** - same input always produces same anonymized output

## Installation and Setup

### Dependencies

The script requires Python 3.6+ and one optional dependency for better encoding detection:

```bash
# Install the optional dependency for better encoding support
pip install chardet

# Or install from requirements file
pip install -r requirements-anonymize.txt
```

### Character Encoding Support

The script automatically detects and handles various character encodings commonly found in SQL dumps:
- **UTF-8** (preferred)
- **Latin-1** (ISO-8859-1) 
- **CP1252** (Windows-1252)
- **ISO-8859-x** variants

If encoding detection fails, the script falls back to safe alternatives and continues processing.

## Usage

```bash
# Basic usage - creates anonymous-backup.sql.gz
python bin/anonymize.py backup.sql.gz

# Specify output file
python bin/anonymize.py backup.sql.gz anonymous-backup.sql.gz

# Works with different formats
python bin/anonymize.py backup.sql
python bin/anonymize.py backup.zip
```

## What Gets Anonymized

### Personal Information
- **Names**: `mnom`, `mprenom` → Realistic French first/last names
- **Emails**: `memail` → firstname.lastname@example.com format
- **Phone numbers**: `mtelephone` → Valid French mobile numbers (06 XX XX XX XX)
- **Addresses**: `madresse` → French street addresses
- **Cities**: `mville` → French city names
- **Postal codes**: `mcodepostal` → Valid French postal codes
- **Trigrams**: `trigramme` → 3-letter codes derived from fake names
- **Birth dates**: `mdaten` → Similar age but different date

### Tables Processed
- `membres` - Member personal information
- `auth_users` - User authentication data
- `vols_planeur` - Glider flights (pilot references)
- `vols_avion` - Aircraft flights (pilot references)
- `vols_decouverte` - Discovery flights (passenger info)
- `achats` - Purchases (pilot references)
- `ecritures` - Accounting entries (user references)
- `comptes` - Accounts (names in descriptions)
- `historique` - History logs (user references)

## What's Preserved

### Database Structure
- All table schemas and column definitions
- Primary keys and foreign key relationships
- Indexes and unique constraints
- Auto-increment sequences
- Engine types and character sets

### Non-Personal Data
- All dates (except birth dates, which are shifted)
- All financial amounts and quantities
- All technical data (aircraft registrations, etc.)
- All status codes and categories
- All business logic relationships

### Data Relationships
- Member-to-flight relationships
- Pilot-to-instructor relationships
- User-to-transaction relationships
- Account hierarchies

## Example Anonymization

**Original data:**
```sql
INSERT INTO `membres` VALUES 
('jdupont','Dupont','Jean','jean.dupont@email.com','01 23 45 67 89','12 rue de la Paix','Paris','75001','JDU','1985-03-15');
```

**Anonymized data:**
```sql
INSERT INTO `membres` VALUES 
('clemaire','Noel','Manon','christophe.lemaire@example.com','06 62 35 84 20','6 Rue Saint-Antoine','Boulogne-Billancourt','32660','D0B','1984-12-01');
```

## Security Considerations

### ✅ Safe for Development
- All personal data completely replaced
- Email addresses use safe example.com domain
- Phone numbers are fictional but valid format
- Addresses are generic French locations

### ⚠️ Important Notes
- **Login credentials are preserved** - users can still log in with original passwords
- **Business relationships maintained** - same person always gets same fake identity
- **Not cryptographically secure** - uses deterministic hashing for consistency
- **For development only** - never use anonymized data in production

## Technical Implementation

### Consistency Mechanism
The tool uses MD5 hashing of original values to ensure:
- Same original name always produces same fake name
- Same original email always produces same fake email
- Cross-table references remain valid

### French Data Sources
- 40 authentic French male first names
- 40 authentic French female first names  
- 80+ French family names
- 40+ French cities
- 28+ French street name patterns
- Valid postal code generation

### Processing Flow
1. Extract compressed backup if needed
2. Parse SQL dump line by line
3. Identify INSERT statements for personal data tables
4. Parse and anonymize individual data values
5. Reconstruct SQL with anonymized data
6. Compress output in original format

## Troubleshooting

### Encoding Issues

If you encounter encoding errors like `'utf-8' codec can't decode byte`:

1. **Install chardet for better detection:**
   ```bash
   pip install chardet
   ```

2. **Manual encoding conversion:**
   ```bash
   # Convert Latin-1 to UTF-8
   iconv -f latin1 -t utf-8 backup.sql > backup_utf8.sql
   python bin/anonymize.py backup_utf8.sql
   ```

3. **Check the error message** - the script provides specific guidance for encoding issues

### Large Files

For very large SQL dumps:
- The script processes files line by line (memory efficient)
- Progress is reported every 10,000 lines
- Failed anonymization attempts fall back to original data

### Compressed Files

- **ZIP files**: Script extracts all .sql files found
- **GZIP files**: Direct decompression with encoding detection
- **Mixed formats**: Input and output can be different formats

## Limitations

- Only processes standard SQL dump format
- Requires explicit table/column configuration for new tables
- Does not anonymize data in stored procedures or functions
- Birth dates preserve approximate age rather than exact anonymization

## Development Usage

```bash
# Create development database
python bin/anonymize.py production_backup.sql.gz dev_backup.sql.gz
zcat dev_backup.sql.gz | mysql -u user -p database_name

# All original logins work with original passwords
# Personal data is completely fake but relationships preserved
```