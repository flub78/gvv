# Data-Driven Development in GVV

## Overview

GVV implements a comprehensive metadata-driven architecture that centralizes data model definitions and automatically generates UI components, form inputs, validation rules, and display formatting. This approach ensures consistency across the application and reduces code duplication.

## Architecture

### Core Classes

1. **MetaData.php** - Abstract base class providing core metadata functionality
2. **GVVMetadata.php** - Concrete implementation extending MetaData with GVV-specific definitions
3. **MailMetadata.php** - Specialized metadata for email management (inherits from GVVMetadata)

### Metadata Sources

The system combines two sources of metadata:

1. **Database Schema** - Automatically extracted using `SHOW FULL FIELDS FROM table`
2. **Application Metadata** - Programmatically defined in PHP classes

```php
// Example: Database schema extraction
$sql = "show full fields from $table";
$res = $this->CI->db->query($sql);
foreach ($res->result_array() as $row) {
    $field = $row['Field'];
    $this->field[$table][$field] = $row; // Stores Type, Null, Key, Default, Extra
}
```

## Data Types and Subtypes

### Base Types (from MySQL schema)
- `varchar` - Variable length strings
- `int` - Integers  
- `tinyint` - Small integers
- `decimal` - Decimal numbers
- `date` - Date values
- `datetime` - Date and time values
- `time` - Time values

### Subtypes (application-defined semantics)

#### String Subtypes
- `email` - Email addresses with validation and mailto links
- `password` - Hidden input, encrypted storage
- `ipaddress` - IP address format validation
- `url` - URL validation
- `key` - Foreign key reference with clickable links
- `selector` - Dropdown selection from related table
- `upload_image` - Image upload with preview
- `image` - Display uploaded images

#### Numeric Subtypes
- `currency` - Monetary values with Euro formatting
- `enumerate` - Integer codes with string labels
- `boolean` - True/false checkboxes
- `minute` - Time duration in minutes (HH:MM format)
- `time` - Decimal time converted to HH:MM
- `centieme` - Hundredths precision

#### Special Subtypes
- `checkbox` - Interactive checkboxes in tables
- `activity_date` - Dates with business rule validation
- `loader` - File upload interface

## Metadata Definition Examples

### Field Properties
```php
// Basic field naming and typing
$this->field['comptes']['codec']['Name'] = 'Code';
$this->field['comptes']['codec']['Subtype'] = 'selector';
$this->field['comptes']['codec']['Selector'] = 'codec_selector';

// Currency formatting
$this->field['vue_comptes']['debit']['Subtype'] = 'currency';

// Boolean checkboxes
$this->field['machinesp']['mpautonome']['Subtype'] = 'boolean';

// Date handling with defaults
$this->field['ecritures']['date_op']['Default'] = 'today';
$this->field['ecritures']['date_op']['Subtype'] = 'activity_date';
```

### Enumeration Values
```php
$this->field['membres']['categorie']['Subtype'] = 'enumerate';
$this->field['membres']['categorie']['Enumerate'] = $this->CI->config->item('categories_pilote');
```

### Field Attributes and Validation
```php
// Read-only fields
$this->field['comptes']['debit']['Attrs'] = array('readonly' => "readonly");

// CSS classes and JavaScript events
$this->field['ecritures']['description']['Attrs'] = array('class' => "description");

// Input validation hints
$this->field['membres']['mtelf']['Title'] = 'chiffres, espaces ou tirets';
```

## Form Generation

### Automatic Input Field Generation

The `input_field()` method generates appropriate HTML inputs based on metadata:

```php
function input_field($table, $field, $value = '', $mode = "ro", $attrs = array()) {
    $subtype = $this->field_subtype($table, $field);
    $type = $this->field_type($table, $field);
    
    if ($subtype == 'boolean') {
        return form_checkbox(array(
            'name' => $field,
            'value' => 1,
            'checked' => (0 != $value)
        ));
    } elseif ($subtype == 'selector') {
        $selector = $this->selector($this->field[$table][$field]['Selector']);
        return dropdown_field($field, $value, $selector, "id=\"$field\"");
    }
    // ... additional type handling
}
```

### Form Layout Generation

Multiple form layout options:

1. **Table Layout** - `form()` method
2. **Flexbox Layout** - `form_flexbox()` method  
3. **Generator** - `form_generator()` for rapid prototyping

## Table Display Generation

### Automatic Table Rendering

The `table()` method generates complete HTML tables with:

- Column headers from field names
- Formatted cell values based on subtypes
- Action buttons (edit, delete, etc.)
- Pagination controls
- CSS classes for styling

```php
// Example usage
$attrs = array(
    'controller' => 'membres',
    'actions' => array('edit', 'delete'),
    'fields' => array('nom', 'prenom', 'email', 'actif')
);
echo $this->gvvmetadata->table('vue_membres', $attrs);
```

### Value Formatting

The `array_field()` method handles display formatting:

```php
if ('currency' == $subtype) {
    return euro($value); // Format as currency
} elseif ('boolean' == $subtype) {
    return ($value) ? img(theme() . "/images/tick.png") : '';
} elseif ('key' == $subtype) {
    $url = controller_url($action . "/$value");
    return anchor($url, $label); // Clickable links
}
```

## Validation Rules Generation

### Automatic Rule Generation

The `rules()` method generates CodeIgniter validation rules:

```php
function rules($table, $field, $action) {
    $type = $this->field_type($table, $field);
    $subtype = $this->field_subtype($table, $field);
    $may_be_null = $this->field_attr($table, $field, 'Null');
    
    if ('email' == $subtype) {
        return "trim|required|valid_email";
    } elseif ('varchar' == $type) {
        return "trim|required|max_length[$size]|encode_php_tags|xss_clean";
    }
    // ... additional validation rules
}
```

## Data Transformation

### Database Value Conversion

The `post2database()` method converts form values to database format:

```php
function post2database($table, $field, $value = '') {
    $type = $this->field_type($table, $field);
    $subtype = $this->field_subtype($table, $field);
    
    if ('date' == $type) {
        return date_ht2db($value); // Convert DD/MM/YYYY to YYYY-MM-DD
    } elseif ("time" == $subtype) {
        return str_replace(":", ".", $value); // Convert HH:MM to decimal
    }
}
```

## Export Capabilities

The system supports multiple export formats:

1. **CSV Export** - `csv_table()` method
2. **PDF Export** - `pdf_table()` method
3. **Data Normalization** - `normalise()` for AJAX responses

## Usage Patterns

### Controller Integration

Controllers use metadata for:

1. **Form Generation**
```php
$fields = array('nom' => '', 'email' => '', 'actif' => 1);
$form_html = $this->gvvmetadata->form('membres', $fields);
```

2. **Validation Setup**
```php
$this->gvvmetadata->set_rules('membres', array_keys($fields), array(), CREATION);
```

3. **Table Display**
```php
$this->gvvmetadata->store_table('vue_membres', $query_result);
$table_html = $this->gvvmetadata->table('vue_membres', $display_attrs);
```

### View Integration

Views receive pre-formatted HTML that requires minimal additional processing:

```php
// In controller
$data['member_table'] = $this->gvvmetadata->table('vue_membres', $attrs);

// In view
echo $member_table; // Complete formatted table
```

## Benefits

1. **Consistency** - All forms and tables use the same formatting rules
2. **Maintainability** - Changes to field definitions propagate automatically
3. **Rapid Development** - New entities require minimal boilerplate code
4. **Type Safety** - Centralized validation and formatting rules
5. **Internationalization** - Field names support translation keys
6. **Database Evolution** - Schema changes are automatically reflected

## Extension Points

### Adding New Subtypes

1. Define subtype in metadata:
```php
$this->field['table']['field']['Subtype'] = 'new_subtype';
```

2. Add handling in `input_field()`, `array_field()`, and `rules()` methods

3. Implement formatting and validation logic

### Custom Selectors

```php
// Define selector data source
$this->set_selector('custom_selector', $options_array);

// Use in field definition
$this->field['table']['field']['Selector'] = 'custom_selector';
```

This metadata-driven approach is fundamental to GVV's architecture and enables rapid development while maintaining consistency across the application.