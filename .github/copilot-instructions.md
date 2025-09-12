# GVV Copilot Instructions

## Project Overview

**GVV (Gestion Vol à voile)** is a web application for managing gliding clubs, developed in PHP since 2011. It serves as an alternative to GiVAV and is used by 5-6 gliding associations. The application handles member management, aircraft fleet management, flight logging, billing, basic accounting, flight calendars, and email communications.

## High-Level Details

- **Type**: Web application for gliding club management
- **Languages**: PHP 7.4, MySQL 5.x, HTML, CSS, JavaScript
- **Framework**: CodeIgniter 2.x (legacy version)
- **Size**: ~50 controllers, extensive model layer, multi-language support (French, English, Dutch)
- **Target Runtime**: Apache/Nginx web server with PHP 7.4 and MySQL
- **Status**: Maintenance mode - stable but no major feature development planned

## Environment Setup

**CRITICAL**: Always source the environment setup before running any PHP commands:
```bash
source setenv.sh
```
This sets PHP 7.4 as the active version. The project requires PHP 7.4 specifically - newer PHP versions are not compatible.

## Build and Validation

### PHP Syntax Validation
```bash
# Always source environment first
source setenv.sh

# Validate individual PHP files
php -l application/controllers/welcome.php

# Validate entire directories (use this pattern for bulk validation)
find application/controllers -name "*.php" -exec php -l {} \;
```

### Code Quality Tools
The project includes configuration for code quality tools in `/build/`:

- **PHPCS Configuration**: `build/phpcs.xml` - Uses Arne Blankerts' coding standard
- **PHPMD Configuration**: `build/phpmd.xml` - Uses Sebastian Bergmann's ruleset

**Note**: phpcs and phpmd tools are not currently installed in the environment, so code quality checks cannot be run automatically.

### Static Analysis (Legacy)
The project contains two build files that are not currently functional:
- `build.xml` (Ant-based) - requires tools not installed
- `build-phing.xml` (Phing-based) - phing not available

### Manual Validation Steps
1. **PHP Syntax**: Always run `php -l` on modified files
2. **File Permissions**: Ensure web-writable directories have correct permissions:
   ```bash
   chmod +wx application/logs
   chmod +wx uploads
   chmod +wx assets/images
   ```

## Project Architecture and Layout

### Directory Structure
```
/
├── index.php                    # Main entry point (CodeIgniter bootstrap)
├── setenv.sh                   # Environment setup (PHP 7.4)
├── application/                # CodeIgniter application directory
│   ├── controllers/            # MVC Controllers (50+ files)
│   ├── models/                 # Database models
│   ├── views/                  # HTML templates
│   ├── libraries/              # Custom libraries
│   ├── helpers/                # Helper functions
│   ├── config/                 # Configuration files
│   │   ├── config.php          # Main config (base_url, etc.)
│   │   ├── database.example.php # Database config template
│   │   └── routes.php          # URL routing
│   ├── migrations/             # Database migrations
│   └── third_party/            # External libraries
├── system/                     # CodeIgniter core (do not modify)
├── assets/                     # CSS, JS, images
├── themes/                     # UI themes
├── uploads/                    # User uploads
└── doc/                        # Documentation
```

### Key Files
- **Entry Point**: `index.php` - Main application bootstrap
- **Configuration**: `application/config/config.php` - Base URL, Google account settings
- **Database**: `application/config/database.php` (copy from .example.php)
- **Routing**: `application/config/routes.php` - URL routing rules

### CodeIgniter 2.x Patterns
- **Controllers**: Located in `application/controllers/`, extend `CI_Controller`
- **Models**: Located in `application/models/`, extend `CI_Model`
- **Views**: Located in `application/views/`, loaded via `$this->load->view()`
- **Libraries**: Auto-loaded or manually loaded via `$this->load->library()`
- **Helpers**: Loaded via `$this->load->helper()`

### Database Configuration
Copy `application/config/database.example.php` to `database.php` and configure:
- hostname, username, password, database name
- Uses MySQLi driver
- Required for any database operations

### Development Guidelines

1. **Environment**: Always use `source setenv.sh` before PHP commands
2. **Syntax Check**: Run `php -l` on all modified PHP files
3. **CodeIgniter Conventions**: Follow CI 2.x MVC patterns
4. **File Permissions**: Web-writable directories need proper permissions
5. **Third-party Code**: Located in `application/third_party/` - handle with care
6. **Legacy Code**: This is a maintenance-mode project - avoid major architectural changes

### Common Paths for Changes
- **New Features**: Add controllers in `application/controllers/`
- **Database**: Add models in `application/models/`, migrations in `application/migrations/`
- **UI Changes**: Modify views in `application/views/` and assets in `assets/`
- **Configuration**: Update files in `application/config/`
- **Business Logic**: Add libraries in `application/libraries/`

### Testing Notes
- **phpunit** - unit tests and integration tests
- **Legacy test directories exist** but are not functional
- **Test users available**: testuser/testadmin with password "password"

### Dependencies
- **PHP Extensions**: MySQLi, GD (for graphics), standard PHP extensions
- **Web Server**: Apache or Nginx with mod_rewrite/URL rewriting
- **Database**: MySQL 5.x or compatible
- **No Composer**: Project predates Composer, uses manual dependency management

---

**Important**: Trust these instructions and only search for additional information if something is incomplete or incorrect. The project is in maintenance mode with well-established patterns - follow existing code conventions rather than modernizing approaches.
