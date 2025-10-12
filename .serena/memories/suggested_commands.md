# Suggested Commands for GVV Development

## Critical: Environment Setup
**ALWAYS run before any PHP command:**
```bash
source setenv.sh
```
This sets PHP 7.4 as the active version. The project requires PHP 7.4 specifically.

## Testing Commands

### Fast Development
```bash
./run-tests.sh                      # Fast tests, no coverage (~100-150ms)
./run-tests.sh path/to/TestFile.php # Run specific test file
./run-tests.sh --filter testMethod  # Run specific test method
```

### Coverage Analysis
```bash
./run-coverage.sh                   # Tests with coverage (~20 seconds)
./run-all-tests.sh --coverage       # All test suites with coverage (~60 seconds)
./run-all-tests.sh                  # All test suites without coverage (~2 seconds)
firefox build/coverage/index.html   # View coverage report
```

## PHP Validation
```bash
php -l application/controllers/welcome.php                  # Validate single file
find application/controllers -name "*.php" -exec php -l {} \; # Validate directory
```

## Database
```bash
# Use credentials from application/config/database.php
mysql -u gvv_user -p gvv2
```

## Deployment Commands
```bash
# Backup before deployment
mysqldump -u gvv_user -p gvv2 > backup_pre_feature.sql
tar -czf backup_uploads.tar.gz uploads/

# File permissions for web-writable directories
chmod +wx application/logs
chmod +wx uploads
chmod +wx assets/images
```

## Gemini CLI (for large codebase analysis)
```bash
gemini -p "@src/ Analyze this directory"
gemini --all_files -p "Analyze the project structure"
```
