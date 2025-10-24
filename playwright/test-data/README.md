# Test Data Fixtures for Playwright

## Overview

This directory contains JSON fixtures extracted from the GVV database for use in Playwright end-to-end tests. The fixtures are dynamically generated from real database content to avoid hardcoded test data that becomes invalid after database anonymization.

## Files

- **fixtures.json** - Main test data file containing pilots, instructors, aircraft, and accounts
- **README.md** - This file

## Generating Test Data

### Via Admin Interface (Recommended)

1. Navigate to the admin page: `/admin/page`
2. In the "Outils de développement" section (development mode only)
3. Click "Extraire" on the "Extraire données test" card
4. The system will extract test data and save it to `playwright/test-data/fixtures.json`

### Workflow for Test Environment Preparation

The recommended workflow for preparing a test environment is:

1. **Anonymize database**: Click "Anonymiser données" to anonymize all personal data
2. **Extract test data**: Click "Extraire données test" to generate fixtures from anonymized data
3. **Run Playwright tests**: The tests will use the real anonymized data from fixtures.json

This ensures that Playwright tests always have valid test data that matches the current database state.

## Test Data Structure

The `fixtures.json` file contains the following data types:

### Metadata
```json
{
  "metadata": {
    "extracted_at": "2025-01-15 14:30:00",
    "database": "gvv2",
    "version": "1.0"
  }
}
```

### Pilots (10 records)
Active pilots with member accounts (411xxx codes):
```json
{
  "pilots": [
    {
      "login": "pilot_login",
      "full_name": "Prénom Nom",
      "first_name": "Prénom",
      "last_name": "Nom",
      "account_id": 123,
      "account_label": "(411) Prénom Nom"
    }
  ]
}
```

### Glider Instructors (5 records)
Instructors with `inst_glider` qualification:
```json
{
  "instructors": {
    "glider": [
      {
        "login": "instructor_login",
        "full_name": "Prénom Nom",
        "first_name": "Prénom",
        "last_name": "Nom",
        "qualification": "FI",
        "account_id": 124,
        "account_label": "(411) Prénom Nom"
      }
    ]
  }
}
```

### Airplane Instructors / Tow Pilots (5 records)
Pilots with `inst_airplane` qualification:
```json
{
  "instructors": {
    "airplane": [
      {
        "login": "tow_pilot_login",
        "full_name": "Prénom Nom",
        "first_name": "Prénom",
        "last_name": "Nom",
        "qualification": "FI(A)"
      }
    ]
  }
}
```

### Two-Seater Gliders (5 records)
```json
{
  "gliders": {
    "two_seater": [
      {
        "registration": "F-XXXX",
        "model": "ASK 21",
        "manufacturer": "Schleicher",
        "seats": 2,
        "autonomous": false
      }
    ]
  }
}
```

### Single-Seater Gliders (5 records)
```json
{
  "gliders": {
    "single_seater": [
      {
        "registration": "F-YYYY",
        "model": "Discus",
        "manufacturer": "Schempp-Hirth",
        "seats": 1,
        "autonomous": true
      }
    ]
  }
}
```

### Tow Planes (5 records)
```json
{
  "tow_planes": [
    {
      "registration": "F-ZZZZ",
      "model": "Robin DR400",
      "manufacturer": "Robin"
    }
  ]
}
```

### Member Accounts (20 records)
```json
{
  "accounts": [
    {
      "id": 125,
      "name": "Prénom Nom",
      "pilot_login": "pilot_login",
      "code": "41101",
      "label": "(41101) Prénom Nom"
    }
  ]
}
```

## Using Fixtures in Playwright Tests

### JavaScript Example

```javascript
import { test, expect } from '@playwright/test';
import fixtures from './test-data/fixtures.json';

test('flight registration with real data', async ({ page }) => {
  // Use first pilot
  const pilot = fixtures.pilots[0];

  // Use first glider instructor
  const instructor = fixtures.instructors.glider[0];

  // Use first two-seater glider
  const glider = fixtures.gliders.two_seater[0];

  // Use first tow pilot
  const towPilot = fixtures.instructors.airplane[0];

  // Use first tow plane
  const towPlane = fixtures.tow_planes[0];

  // Register flight
  await page.fill('[name="pilot"]', pilot.full_name);
  await page.fill('[name="glider"]', glider.registration);
  // ... etc
});
```

## Database Tables Referenced

- **membres** - Member/pilot information (mlogin, mprenom, mnom, inst_glider, inst_airplane)
- **comptes** - Member accounts for billing (id, nom, pilote, codec)
- **machinesp** - Glider aircraft (mpimmat, mpmodele, mpconstruc, mpbiplace, mpautonome)
- **machinesa** - Airplane aircraft (macimmat, macmodele, macconstruc, macrem)

## Security

⚠️ **Important**: This extraction service is only available in development mode (`ENVIRONMENT === 'development'`). It cannot be accessed in production environments.

## Troubleshooting

### No data extracted

If extraction returns 0 records for a category:
- Check that the database has active (`actif = 1`) records
- For pilots: Ensure they have associated accounts with codec LIKE '411%'
- For instructors: Check `inst_glider` and `inst_airplane` fields are not NULL/empty
- For aircraft: Verify `mpbiplace` values ('0' or '1') and `macrem` flag (1 for tow planes)

### Output directory not writable

Ensure `playwright/test-data/` directory has write permissions:
```bash
chmod 755 playwright/test-data
```

## Implementation Details

- **Controller**: `application/controllers/admin.php::extract_test_data()`
- **View**: `application/views/admin/bs_extraction_results.php`
- **Output Format**: JSON with UTF-8 encoding, pretty-printed
- **File Path**: `FCPATH . 'playwright/test-data/fixtures.json'`

## Related Features

- **Database Anonymization**: `admin/anonymize_all_data` - Anonymizes all personal data
- **Playwright Tests**: `playwright/tests/migrated/glider-flights.spec.js` - Uses these fixtures
