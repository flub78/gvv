# GVV Project Overview

## Purpose
GVV (Gestion Vol à voile) is a web application for managing gliding clubs. It handles:
- Member management
- Aircraft fleet management
- Flight logging
- Billing
- Basic accounting
- Flight calendars
- Email communications

Currently used by 5-6 gliding associations since 2011.

## Tech Stack
- **Languages**: PHP 7.4, MySQL 5.x, HTML, CSS, JavaScript
- **Framework**: CodeIgniter 2.x (legacy version)
- **UI**: Bootstrap 5
- **Database**: MySQL with migrations managed via CodeIgniter
- **Testing**: PHPUnit
- **Size**: ~50 controllers, extensive model layer
- **Multi-language**: French (primary), English, Dutch

## Status
- Deployed for 12 years
- Stable, in maintenance mode
- Legacy migration ongoing (CodeIgniter Unit_test → PHPUnit)
- 32 controllers still have old CI Unit_test methods
