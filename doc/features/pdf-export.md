# Generating PDF from HTML Tables in PHP 7.4 + CodeIgniter 2 (Without Composer)

## Best Library: **TCPDF**

### Why TCPDF?
- **Standalone**: Does not require Composer.
- **Actively Maintained**: Works well with PHP 7.4.
- **Supports HTML Tables**: Allows rendering tables using `writeHTML()`.

---

## How to Use TCPDF in CodeIgniter 2

### 1. Download TCPDF  
Get it from:  
- [Official Website](https://tcpdf.org/)  
- [GitHub Repository](https://github.com/tecnickcom/TCPDF)  

Extract and place it inside `application/libraries/`.

---

### 2. Include TCPDF in a Controller
```php
require_once(APPPATH . 'libraries/tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->AddPage();

$html = '<table border="1">
            <tr><td>Hello</td><td>World</td></tr>
         </table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('table.pdf', 'D');
