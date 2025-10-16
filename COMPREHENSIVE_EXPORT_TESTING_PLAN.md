# Comprehensive Export Testing Plan for GVV Application

## Overview

This document outlines a comprehensive testing strategy for all CSV and PDF export functionality across the GVV application. Based on code analysis, I've identified **46 export endpoints** across **20 controllers** that need systematic testing.

## Export Endpoints Inventory

### 1. **Comptes Controller** (Financial Accounts)
- `comptes/export_resultat/csv` - Income statement CSV export
- `comptes/export_resultat/pdf` - Income statement PDF export (redirects to rapports)
- `comptes/csv_resultat_categories` - Results by categories CSV
- `comptes/export_bilan/csv` - Balance sheet CSV export
- `comptes/export_bilan/pdf` - Balance sheet PDF export (redirects to rapports)
- `comptes/dashboard/csv` - Financial dashboard CSV
- `comptes/dashboard/pdf` - Financial dashboard PDF
- `comptes/balance_csv/{codec?}/{codec2?}` - Account balance CSV
- `comptes/balance_pdf/{codec?}/{codec2?}` - Account balance PDF

### 2. **Compta Controller** (Accounting)
- `compta/export_journal` - Journal export (CSV/PDF based on POST button)
- `compta/export/{compte?}` - Account statement export (CSV/PDF)
- `compta/pdf/{compte?}` - Account statement PDF

### 3. **Rapports Controller** (Reports)
- `rapports/pdf_resultats` - Income statement PDF
- `rapports/pdf_resultats_par_categories` - Results by categories PDF
- `rapports/pdf_ffvv` - FFVV report PDF
- `rapports/licences` - Licenses PDF
- `rapports/bilan` - Balance sheet PDF

### 4. **Vols_planeur Controller** (Glider Flights)
- `vols_planeur/csv` - Glider flights CSV export
- `vols_planeur/pdf` - Glider flights PDF export
- `vols_planeur/export_per/{year}/{type}` - Periodic statistics
- `vols_planeur/pdf_machine/{year}` - Machine statistics PDF
- `vols_planeur/pdf_month/{year}` - Monthly statistics PDF

### 5. **Vols_avion Controller** (Airplane Flights)
- `vols_avion/csv` - Airplane flights CSV export
- `vols_avion/pdf` - Airplane flights PDF export
- `vols_avion/csv_month/{year}` - Monthly CSV
- `vols_avion/pdf_month/{year}` - Monthly PDF
- `vols_avion/csv_machine/{year}` - Machine CSV

### 6. **Vols_decouverte Controller** (Discovery Flights)
- `vols_decouverte/export/csv` - Discovery flights CSV
- `vols_decouverte/export/pdf` - Discovery flights PDF
- `vols_decouverte/generate_pdf/{data}` - Generate individual PDF

### 7. **Member/Personnel Controllers**
- `membre/export/pdf` - Members PDF export (redirects to rapports)
- `membre/export_certificats` - Certificates export

### 8. **Equipment Controllers**
- `avion/export/csv` - Aircraft list CSV
- `avion/export/pdf` - Aircraft list PDF
- `planeur/export/csv` - Glider list CSV
- `planeur/export/pdf` - Glider list PDF

### 9. **Administrative Controllers**
- `sections/export/csv` - Sections list CSV
- `sections/export/pdf` - Sections list PDF
- `plan_comptable/export/csv` - Chart of accounts CSV
- `plan_comptable/export/pdf` - Chart of accounts PDF

### 10. **Event Controllers**
- `event/csv/{type}` - Events CSV by type
- `event/pdf/{type}` - Events PDF by type
- `types_ticket/ventes_csv/{year}` - Ticket sales CSV
- `events_types/ventes_csv/{year}` - Event types sales CSV
- `achats/ventes_csv/{year}` - Purchases sales CSV

### 11. **Reports Controllers**
- `reports/export/csv/{request}` - Generic reports CSV
- `reports/export/pdf/{request}` - Generic reports PDF
- `tickets/export/csv/{pilote?}` - Tickets CSV export
- `tickets/export/pdf/{pilote?}` - Tickets PDF export

## Testing Strategy

### Phase 1: Infrastructure Tests

Create base test classes with common functionality:

```php
abstract class BaseExportTest extends TestCase 
{
    protected function validateCsvStructure($content, $expectedHeaders = [])
    protected function validatePdfContent($pdfPath, $expectedTerms = [])
    protected function checkExportAccess($url, $requiredRole = 'ca')
    protected function simulateExportRequest($controller, $method, $params = [])
}
```

### Phase 2: Controller-Specific Test Suites

#### 2.1 Financial Export Tests
**Class: `FinancialExportsTest`**
- Test all comptes controller exports
- Validate financial data consistency
- Check accounting format compliance
- Test different date ranges and filters

#### 2.2 Flight Data Export Tests  
**Class: `FlightDataExportsTest`**
- Test vols_planeur, vols_avion, vols_decouverte exports
- Validate flight log data completeness
- Check pilot information accuracy
- Test statistical aggregations

#### 2.3 Administrative Export Tests
**Class: `AdministrativeExportsTest`**
- Test membre, sections, plan_comptable exports
- Validate member data privacy compliance
- Check administrative data accuracy

#### 2.4 Equipment Export Tests
**Class: `EquipmentExportsTest`**
- Test avion, planeur exports
- Validate equipment specifications
- Check maintenance data inclusion

#### 2.5 Event Export Tests
**Class: `EventExportsTest`**
- Test event, types_ticket, events_types exports
- Validate event data completeness
- Check sales/ticket information

### Phase 3: Cross-Format Consistency Tests

**Class: `ExportConsistencyTest`**
- Compare CSV vs PDF content for same data
- Validate data integrity across formats
- Check formatting consistency

### Phase 4: Integration and Performance Tests

**Class: `ExportIntegrationTest`**
- Test export generation with real data
- Validate file generation performance
- Check memory usage for large exports

## Test Implementation Plan

### Step 1: Base Infrastructure (Week 1)

```php
// /application/tests/integration/exports/BaseExportTest.php
abstract class BaseExportTest extends TestCase
{
    protected $temp_files = [];
    
    protected function validateCsvStructure($content, $expectedHeaders = []) {
        // Validate CSV format, headers, data consistency
    }
    
    protected function validatePdfContent($pdfPath, $expectedTerms = []) {
        // Use pdftotext to extract and validate content
    }
    
    protected function checkExportAccess($url, $requiredRole = 'ca') {
        // Test role-based access control
    }
}
```

### Step 2: Critical Exports First (Week 2)

Priority order based on business importance:
1. **Financial exports** (comptes/*)
2. **Flight data exports** (vols_*/*)  
3. **Reports exports** (rapports/*)
4. **Administrative exports** (sections, plan_comptable)

### Step 3: Comprehensive Coverage (Week 3-4)

Complete test coverage for all 46 identified endpoints.

### Step 4: CI Integration (Week 4)

Integrate all export tests into the existing test suite structure.

## Test Configuration

### Required Dependencies
- `pdftotext` (poppler-utils)
- Temporary file handling
- Mock data generators
- Role-based authentication mocks

### Test Data Requirements
- Sample financial data (multiple years)
- Sample flight logs (various aircraft)
- Sample member data (anonymized)
- Sample equipment data

### Performance Benchmarks
- CSV generation: < 2 seconds for 1000 records
- PDF generation: < 5 seconds for standard reports  
- Memory usage: < 50MB for typical exports

## Test Execution Plan

### Automated Testing
```bash
# Run all export tests
./vendor/bin/phpunit application/tests/integration/exports/

# Run specific export category
./vendor/bin/phpunit application/tests/integration/exports/FinancialExportsTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html build/coverage-exports application/tests/integration/exports/
```

### Manual Testing Checklist
- [ ] Test each export with different user roles
- [ ] Verify file downloads work in browsers
- [ ] Check exports with empty data sets
- [ ] Validate exports with large data sets
- [ ] Test exports with special characters/Unicode
- [ ] Verify PDF formatting and layout
- [ ] Check CSV delimiter and encoding

## Success Criteria

### Functional Requirements
- ✅ All 46 export endpoints generate valid files
- ✅ CSV files contain proper headers and data
- ✅ PDF files are readable and well-formatted
- ✅ Data consistency between CSV and PDF formats
- ✅ Proper error handling for invalid requests

### Quality Requirements  
- ✅ 90%+ test coverage for export functionality
- ✅ All exports complete within performance benchmarks
- ✅ No memory leaks or excessive resource usage
- ✅ Proper access control enforcement
- ✅ Unicode/special character support

### Regression Protection
- ✅ Tests prevent future export bugs
- ✅ CI pipeline catches export failures
- ✅ Easy to add tests for new export features
- ✅ Clear test failure diagnostics

## Test File Structure

```
application/tests/integration/exports/
├── BaseExportTest.php
├── FinancialExportsTest.php
├── FlightDataExportsTest.php  
├── AdministrativeExportsTest.php
├── EquipmentExportsTest.php
├── EventExportsTest.php
├── ExportConsistencyTest.php
├── ExportIntegrationTest.php
├── data/
│   ├── sample_financial_data.json
│   ├── sample_flight_data.json
│   └── sample_member_data.json
└── helpers/
    ├── ExportTestHelper.php
    └── MockDataGenerator.php
```

## Implementation Priority Matrix

| Priority | Category | Endpoints | Business Impact | Implementation Effort |
|----------|----------|-----------|-----------------|----------------------|
| HIGH | Financial | 9 | Critical | Medium |
| HIGH | Flight Data | 8 | High | Medium |
| MEDIUM | Reports | 5 | High | Low |
| MEDIUM | Administrative | 6 | Medium | Low |
| LOW | Equipment | 4 | Low | Low |
| LOW | Events | 6 | Medium | Low |

## Maintenance and Evolution

### Regular Updates
- Monthly review of new export functionality
- Quarterly performance benchmark updates
- Annual test data refresh

### Extension Points
- Easy addition of new export endpoint tests
- Configurable test data sets
- Pluggable validation rules
- Custom assertion helpers

This comprehensive plan ensures complete coverage of GVV's export functionality while providing a solid foundation for future development and maintenance.