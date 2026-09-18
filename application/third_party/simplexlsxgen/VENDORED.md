# SimpleXLSXGen — vendored copy

- Source: https://github.com/shuchkin/simplexlsxgen
- Version: 1.5.17
- Vendored commit: 162a4a9b929611d69dbd6f1a7f42af483a87b537
- License: MIT (see `LICENSE`)
- Vendored on: 2026-09-18 (manual copy of `src/SimpleXLSXGen.php`, no build step)

Write-only XLSX generator, single file, no Composer/PSR-4 required, no
dependency beyond the `zlib` PHP extension (used via `gzcompress()`; no
`ext-zip` needed). Chosen for GVV's manual/no-Composer dependency management
(see `AI_INSTRUCTIONS.md`), same vendoring style as `tfpdf.php`/`fpdf.php`.

Used by `application/libraries/MetaData.php::xlsx_table()`.

To update: replace `SimpleXLSXGen.php` with a newer `src/SimpleXLSXGen.php`
from the upstream repository and update the version/commit above.
