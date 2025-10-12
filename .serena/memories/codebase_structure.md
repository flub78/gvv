# GVV Codebase Structure

## Directory Layout
```
/
├── index.php                    # Main entry point
├── setenv.sh                    # MUST run before PHP commands
├── application/
│   ├── controllers/             # Request handling (~50 controllers)
│   ├── models/                  # Database operations (data + metadata)
│   ├── views/                   # HTML templates
│   ├── libraries/               # Business logic (Gvvmetadata.php is central)
│   ├── helpers/                 # Utility functions
│   ├── config/                  # Configuration files
│   │   ├── config.php           # Base URL, Google settings
│   │   ├── database.php         # Copy from database.example.php
│   │   ├── migration.php        # Update version after creating migration
│   │   └── routes.php           # URL routing
│   ├── migrations/              # Database migrations (numbered files)
│   ├── tests/                   # PHPUnit tests
│   │   ├── unit/                # Helpers, models, libraries, i18n
│   │   ├── integration/         # Real database operations, metadata
│   │   ├── enhanced/            # CI framework helpers/libraries
│   │   ├── controllers/         # JSON/HTML/CSV output parsing
│   │   └── mysql/               # Real database CRUD operations
│   ├── language/                # Multi-language support
│   │   ├── french/              # Primary language
│   │   ├── english/
│   │   └── dutch/
│   └── third_party/             # External libraries (TCPDF, phpqrcode, etc.)
├── system/                      # CodeIgniter core (DO NOT MODIFY)
├── assets/                      # CSS, JS, images
├── themes/                      # UI themes
├── uploads/                     # User-uploaded files (needs +wx permissions)
├── doc/                         # Documentation
│   ├── design_notes/            # Feature design documents
│   └── development/             # Development guides
├── scripts/                     # CLI scripts (batch operations, maintenance)
└── build/                       # Test coverage reports
```

## Key Files
- `application/libraries/Gvvmetadata.php` - Central metadata system
- `application/config/attachments.php` - Attachments configuration
- `application/controllers/compta.php` - Accounting controller
- `application/controllers/attachments.php` - Attachments controller
- `application/models/attachments_model.php` - Attachments data model

## Test Organization
- **~128 tests** across all suites
- Unit tests: No database dependencies
- Integration tests: Real database operations
- Coverage target: >70% overall

## Third-Party Libraries
Located in `application/third_party/`:
- TCPDF: PDF generation
- phpqrcode: QR code generation
- Google API: Google integration
- CIUnit: Legacy testing framework (being phased out)
