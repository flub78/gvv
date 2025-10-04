# GVV - Product Requirements Document
*Gestion Vol à voile (Gliding Club Management System)*

---

## 1. Overview

### 1.1 Product Objective
GVV (Gestion Vol à voile) is a comprehensive web-based management system designed specifically for gliding clubs and aviation associations. The primary objective is to provide a complete solution for managing all aspects of a gliding club's operations, from member management to flight logging, billing, and basic accounting.

### 1.2 Problem Being Solved
Gliding clubs face complex operational challenges:
- **Member Management Complexity**: Tracking pilots with different qualifications, licenses, and roles
- **Fleet Management**: Managing diverse aircraft types (gliders, tow planes, motor aircraft)
- **Flight Operations**: Recording manual and automatic flight logs with billing integration
- **Financial Management**: Complex billing rules unique to each club, accounting, and ticket systems
- **Regulatory Compliance**: Managing licenses, medical certificates, and training records
- **Communication**: Coordinating club activities and communicating with members

### 1.3 Target Users

#### Primary Users
- **Club Members**: Pilots accessing personal information, flight logs, and calendars
- **Flight Instructors (Planchistes)**: Recording flights, managing training records
- **Club Administrators (CA)**: Managing club operations, member communications
- **Treasurers (Trésoriers)**: Financial management, billing, accounting
- **System Administrators**: Technical management, backups, configuration

#### Secondary Users
- **Mechanics**: Aircraft maintenance tracking
- **Ground Crew**: Daily operations support
- **External Organizations**: FFVP integration, GESASSO export

---

## 2. Current Features

### 2.1 Member Management
- **Member Registry**: Complete member database with personal information
- **License Management**: Tracking pilot licenses, medical certificates, qualifications
- **Role-Based Access**: Hierarchical permission system (member → planchiste → ca → trésorier → admin)
- **Multi-Section Support**: Managing different club sections (gliders, motor aircraft, ULM)
- **User Authentication**: Secure login with password recovery
- **Profile Management**: Self-service profile updates

### 2.2 Aircraft Fleet Management
- **Aircraft Database**: Complete fleet registry (gliders, tow planes, motor aircraft)
- **Maintenance Tracking**: Aircraft status and maintenance records
- **Hour Tracking**: Flight time accumulation per aircraft
- **Private vs Club Aircraft**: Ownership categorization
- **Aircraft Configuration**: Performance data, pricing rules

### 2.3 Flight Operations
- **Manual Flight Entry**: Traditional logbook entry interface
- **Automatic Flight Import**: Integration with electronic flight logging systems
- **Flight Categories**: Standard, training (VI), test flights (VE), competition
- **Launch Methods**: Winch, tow plane, self-launch, external
- **Dual Control Support**: Training flight management
- **Flight Validation**: Data integrity checks and validation rules

### 2.4 Billing and Financial Management
- **Complex Billing Engine**: Customizable per-club billing rules
- **Product Catalog**: Configurable pricing for flights, services, memberships
- **Ticket System**: Pre-paid flight tickets with automatic deduction
- **Account Management**: Individual pilot accounts with credit/debit tracking
- **Multi-tariff Support**: Different pricing based on pilot category, aircraft type
- **Billing Modules**: Club-specific billing implementations (DAC, ACES, Vichy, etc.)

### 2.5 Accounting System
- **Double-Entry Accounting**: Basic accounting without VAT or payroll
- **Chart of Accounts**: Configurable account structure
- **Financial Reports**: Profit & loss statements, balance sheets
- **Bank Reconciliation**: Banking integration for account management
- **Purchase Tracking**: Expense management and tracking

### 2.6 Calendar and Scheduling
- **Google Calendar Integration**: Shared club calendar for flight intentions
- **Presence Tracking**: Member availability and attendance
- **Event Management**: Club events and activities scheduling
- **Weather Integration**: Flight planning support

### 2.7 Communication System
- **Email Management**: Bulk email to members with filtering
- **Email Address Management**: Contact list maintenance
- **Notifications**: Automated alerts and reminders
- **Multi-language Support**: French, English, Dutch translations

### 2.8 Reporting and Statistics
- **Flight Statistics**: Monthly, yearly, and historical reports
- **Pilot Progress**: Training progression tracking
- **Fleet Utilization**: Aircraft usage analytics
- **Financial Reports**: Revenue and cost analysis
- **Age Demographics**: Pilot age distribution analysis

### 2.9 Administration Tools
- **Database Backup/Restore**: Data protection and recovery
- **Migration System**: Database schema updates
- **Configuration Management**: Club-specific settings
- **User Role Management**: Permission assignment
- **System Monitoring**: Health checks and diagnostics

### 2.10 Integration Capabilities
- **FFVP Integration**: French federation connectivity
- **GESASSO Export**: Accounting system integration
- **Google Services**: Calendar and authentication
- **External Flight Logs**: Import from various sources
- **API Support**: Basic API for external integrations

---

## 3. Technical Architecture

### 3.1 Technology Stack
- **Backend Framework**: CodeIgniter 2.x (PHP framework)
- **Programming Language**: PHP 7.4
- **Database**: MySQL 5.x with MySQLi driver
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Web Server**: Apache/Nginx with mod_rewrite
- **Version Control**: Git (migrated from SVN)

### 3.2 Key Dependencies
- **PHP Extensions**: MySQLi, GD (graphics), standard extensions
- **JavaScript Libraries**: 
  - FullCalendar (calendar interface)
  - Bootstrap 5 (responsive UI)
  - jQuery (DOM manipulation)
- **Third-party Libraries**: 
  - Google Calendar API
  - pChart (graphical statistics)
  - Various utility libraries

### 3.3 External Integrations
- **Google Calendar**: Event management and synchronization
- **FFVP Systems**: License validation and federation integration
- **GESASSO**: Accounting software export
- **FlightLog Services**: Automatic flight data import
- **Email Services**: SMTP integration for communications

### 3.4 Database Architecture
- **Migration System**: CodeIgniter migrations for schema updates
- **Metadata-Driven**: Dynamic form generation based on database metadata
- **Referential Integrity**: Foreign key relationships maintained
- **Audit Trail**: User action logging and data change tracking

---

## 4. Functional Specifications

### 4.1 User Authentication and Authorization

#### User Stories
- **As a club member**, I can log in securely to access my personal information
- **As a member**, I can reset my password if forgotten
- **As an administrator**, I can assign roles and permissions to users
- **As a user**, I can change my password and personal information

#### Acceptance Criteria
- Secure login with username/password
- Password recovery via email
- Role-based access control with hierarchical permissions
- Session management with timeout
- Password strength validation

#### Edge Cases
- Account lockout after multiple failed attempts
- Handling of inactive/suspended members
- Role changes and permission propagation

### 4.2 Flight Logging

#### User Stories
- **As a planchiste**, I can record flights manually with all required details
- **As a planchiste**, I can import flights automatically from external systems
- **As a pilot**, I can view my personal flight history and statistics
- **As an administrator**, I can validate and correct flight data

#### Acceptance Criteria
- Complete flight data capture (date, time, aircraft, pilot, duration, etc.)
- Validation rules for data integrity
- Support for different flight types and launch methods
- Automatic billing generation upon flight creation
- Bulk import capabilities with error handling

#### Edge Cases
- Duplicate flight detection and prevention
- Handling of incomplete or invalid flight data
- Flight modifications after billing has been generated
- Cross-midnight flights and timezone handling

### 4.3 Billing and Financial Management

#### User Stories
- **As a treasurer**, I can configure billing rules specific to our club
- **As a treasurer**, I can generate bills automatically based on flight activity
- **As a member**, I can view my account balance and transaction history
- **As a treasurer**, I can manage pre-paid tickets and packages

#### Acceptance Criteria
- Flexible billing rule configuration
- Automatic charge calculation based on flight data
- Support for different pricing schemes (hourly, package, tickets)
- Account balance tracking with credit/debit management
- Integration with flight logging for automatic billing

#### Edge Cases
- Billing rule conflicts and priority handling
- Partial flight billing and prorating
- Refunds and billing corrections
- Complex shared billing scenarios

### 4.4 Member Management

#### User Stories
- **As a CA member**, I can maintain complete member records
- **As a CA member**, I can track pilot qualifications and certificates
- **As a member**, I can update my contact information
- **As an administrator**, I can activate/deactivate members

#### Acceptance Criteria
- Comprehensive member database with all required fields
- License and certificate expiration tracking
- Automated reminders for renewals
- Member status management (active, inactive, suspended)
- Integration with authentication system

#### Edge Cases
- Handling of expired medical certificates
- Member data privacy and GDPR compliance
- Duplicate member detection
- Data migration and member merging

### 4.5 Fleet Management

#### User Stories
- **As a CA member**, I can manage the aircraft fleet database
- **As a maintenance officer**, I can track aircraft hours and maintenance
- **As a planchiste**, I can see aircraft availability for flight logging
- **As a treasurer**, I can configure pricing for different aircraft

#### Acceptance Criteria
- Complete aircraft database with technical specifications
- Flight hour accumulation and tracking
- Maintenance schedule integration
- Aircraft availability status management
- Pricing configuration per aircraft type

#### Edge Cases
- Aircraft retirement and historical data preservation
- Handling of aircraft ownership changes
- Maintenance overrides and emergency operations
- Aircraft sharing between clubs

---

## 5. Constraints and Dependencies

### 5.1 Technical Limitations
- **Legacy Framework**: CodeIgniter 2.x limits modern PHP features, no composer
- **PHP Version Lock**: Requires PHP 7.4 specifically (not compatible with newer versions)
- **Single-Tenant Architecture**: Each club requires separate installation
- **Limited Mobile Optimization**: Responsive design but not native mobile app
- **Database Coupling**: Tight coupling to MySQL limits database portability

### 5.2 System Prerequisites
- **Server Requirements**: 
  - Linux/Windows server with Apache/Nginx
  - PHP 7.4 with MySQLi, GD extensions
  - MySQL 5.x or compatible database
  - Minimum 256MB RAM (recommended 512MB for backups)
- **Client Requirements**:
  - Modern web browser with JavaScript enabled
  - Internet connection for cloud integrations
  - PDF viewer for reports

### 5.3 Compatibility Requirements
- **Browser Support**: Chrome, Firefox, Safari, Edge (recent versions)
- **Mobile Compatibility**: Responsive design for tablets and phones
- **Integration Standards**: REST APIs for external system integration
- **Data Format Support**: CSV import/export, PDF generation
- **Email Standards**: SMTP compliance for communication features

### 5.4 Regulatory Dependencies
- **FFVP Integration**: Dependent on French Federation API availability
- **GDPR Compliance**: Must handle personal data according to EU regulations
- **Aviation Regulations**: Must support local aviation authority requirements
- **Accounting Standards**: Basic accounting compliance for non-profit organizations

---

## 6. Potential Improvements

### 6.1 Identified Friction Points

#### Technical Debt
- **Framework Modernization**: Upgrade from CodeIgniter 2.x to modern framework
- **PHP Version Compatibility**: Support for PHP 8.x and future versions
- **Test Coverage**: Insufficient automated testing (acknowledged ongoing issue)
- **API Standardization**: Lack of comprehensive REST API

#### User Experience Issues
- **Mobile Experience**: Limited mobile optimization for field operations
- **Billing Complexity**: Requires PHP programming for custom billing rules
- **Calendar Integration**: Complex Google Calendar setup process
- **Data Entry Efficiency**: Manual flight entry could be more streamlined

#### Operational Limitations
- **Multi-Tenant Support**: Each club requires separate installation
- **Real-time Collaboration**: Limited concurrent user support
- **Offline Capabilities**: No offline mode for field operations
- **Integration Complexity**: Limited third-party system integrations

### 6.2 Optimization Suggestions

#### Short-term Improvements (6-12 months)
1. **Enhanced Mobile Interface**: Improve responsive design for better mobile experience
2. **Simplified Billing Configuration**: Create GUI-based billing rule configuration
3. **Automated Testing**: Increase test coverage to enable confident development
4. **API Documentation**: Comprehensive API documentation for integrations
5. **Performance Optimization**: Database query optimization and caching

#### Medium-term Enhancements (1-2 years)
1. **Framework Migration**: Gradual migration to modern PHP framework (Laravel, Symfony)
2. **Real-time Features**: WebSocket integration for live updates
3. **Advanced Reporting**: Business intelligence dashboards and analytics
4. **Mobile App**: Native mobile application for field operations
5. **Cloud Services**: SaaS option for smaller clubs

#### Long-term Vision (2-5 years)
1. **Multi-Tenant Architecture**: Single installation supporting multiple clubs
2. **Microservices Architecture**: Modular, scalable system design
3. **AI Integration**: Predictive analytics for maintenance, weather, operations
4. **IoT Integration**: Direct aircraft telemetry and automatic logging
5. **Federation Platform**: Inter-club data sharing and competitions

### 6.3 Possible Roadmap

#### Phase 1: Stabilization and Modernization
- Framework upgrade planning
- Comprehensive testing implementation
- Security audit and improvements
- Documentation enhancement

#### Phase 2: User Experience Enhancement
- Mobile-first responsive redesign
- Simplified configuration interfaces
- Performance optimization
- Enhanced accessibility

#### Phase 3: Feature Expansion
- Advanced analytics and reporting
- Enhanced integrations
- Workflow automation
- Real-time collaboration features

#### Phase 4: Platform Evolution
- Multi-tenant architecture
- Cloud deployment options
- API ecosystem development
- Advanced federation features

---

## 7. Success Metrics

### 7.1 Adoption Metrics
- Number of active club installations
- User engagement and retention rates
- Feature utilization across different user roles
- Training time for new club administrators

### 7.2 Performance Metrics
- System response times and availability
- Database performance and query optimization
- Mobile experience ratings
- Integration success rates

### 7.3 Business Value Metrics
- Club operational efficiency improvements
- Time savings in administrative tasks
- Billing accuracy and automation rates
- Member satisfaction scores

---

## 8. Risk Assessment

### 8.1 Technical Risks
- **Legacy Framework**: Increasing difficulty maintaining CodeIgniter 2.x
- **PHP Compatibility**: Future PHP version incompatibilities
- **Security Vulnerabilities**: Aging framework security concerns
- **Scalability Limits**: Single-tenant architecture scaling issues

### 8.2 Operational Risks
- **Developer Dependency**: Limited developer pool familiar with legacy stack
- **Integration Fragility**: External service dependencies (Google, FFVP)
- **Data Migration**: Complex upgrade paths for existing installations
- **Customization Complexity**: Club-specific billing module development

### 8.3 Mitigation Strategies
- **Gradual Modernization**: Incremental framework migration approach
- **Community Building**: Expand developer community and documentation
- **Standardization**: Reduce custom implementations through configuration
- **Backup Systems**: Robust backup and disaster recovery procedures

---

## Conclusion

GVV represents a mature, feature-rich solution for gliding club management that has evolved over 14 years to meet the specific needs of aviation clubs. While the current implementation serves its user base effectively, the identified technical debt and modernization opportunities present a clear path for future development. The system's strength lies in its comprehensive feature set and deep understanding of gliding club operations, while its main challenges stem from the aging technical foundation and complex customization requirements.

The proposed roadmap balances immediate stability needs with long-term platform evolution, ensuring continued service to existing users while positioning for future growth and modernization.