# Clients Enterprise

A multi-enterprise web application for managing client data and reports, supporting California State University (CSU), California Community Colleges (CCC), and Demo environments. Built with PHP, JavaScript, and MySQL, featuring universal relative paths for cross-server compatibility.

**Target Audience**: AI agents and developers working on this project  
**Documentation Standards**: Optimized for AI agent comprehension and action  
**Project Rules**: See `project-rules.md` for detailed development guidelines

## Quick Start (5 minutes)

### Requirements
- PHP 8.4.6+
- MySQL database
- Google Sheets API access

### Start Development Server
```bash
# Navigate to project root
cd otter/

# Start PHP server (PowerShell PREFERRED on Windows)
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log

# Alternative: Git Bash
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log &
```

> **Note:** PowerShell is preferred for server management on Windows machines. Git Bash is required only for Git operations. See project rules for detailed terminal usage guidelines.

### Access Application
- **Login**: http://localhost:8000/login.php
- **Health Check**: http://localhost:8000/health_check.php
- **Admin**: http://localhost:8000/admin/index.php

### Production Access
- **Current**: https://webaim.org/training/online/otter/
- **Access**: Organization passwords or admin credentials

## Terminal Usage Guidelines

### Context-Based Terminal Selection

**Git Operations: Git Bash MANDATORY**
- All git commands must use Git Bash
- Ensures reliable git integration and path handling
- Commands: `git add`, `git commit`, `git push`, `git status`, `git log`

**Server Management: PowerShell PREFERRED (Windows)**
- PHP server startup and management
- Process monitoring and diagnostics
- HTTP testing and health checks
- Commands: `php -S localhost:8000`, `Invoke-WebRequest`, `netstat`, `tasklist`

**Development Tasks: Context Dependent**
- File operations: Either terminal works well
- PHP execution: Either terminal works well
- Path handling: Choose based on path style needed

### Known Issues & Best Practices

**Git Bash Issues**
- Path handling works best with Unix-style paths
- Native git integration prevents command conflicts
- Limited Windows process management capabilities

**PowerShell Issues**
- Path separators may need adjustment for PHP commands
- Git integration can have issues with command conflicts
- Excellent Windows process management and diagnostics

**Best Practices**
- Git operations: Always use Git Bash for reliability
- Server management: Use PowerShell on Windows for better process control
- Development tasks: Choose terminal based on specific task requirements
- Documentation: Always specify which terminal for which task
- Testing: Use PowerShell for Windows-specific diagnostics

---

## Architecture Overview

### Core Design Principles
- **MVP Focus**: Simple, reliable, accurate, WCAG compliant
- **Universal Paths**: Works on any server structure without configuration
- **Multi-Enterprise**: Equal handling of CSU, CCC, Demo environments
- **AI Agent Optimized**: Structured for autonomous operation

### Key Components
```
otter/
├── login.php              # Unified login for all enterprises
├── dashboard.php          # Organization dashboard
├── health_check.php       # Server health monitoring
├── admin/                 # Admin interface
├── reports/               # Reporting system
├── settings/              # Configuration management
├── lib/                   # Core libraries
│   ├── unified_enterprise_config.php  # Main configuration
│   ├── unified_database.php           # Database management
│   ├── enterprise_data_service.php    # Data service
│   └── enterprise_cache_manager.php   # Cache management
├── config/                # Configuration files
│   ├── passwords.json     # Multi-enterprise passwords
│   ├── csu.config        # CSU configuration
│   ├── ccc.config        # CCC configuration
│   └── demo.config       # Demo configuration
├── cache/                 # Enterprise-specific cache
├── css/                   # Centralized stylesheets
└── tests/                 # Comprehensive test suite
```

### Enterprise Detection
The application automatically detects enterprise from:
1. **URL Parameter**: `?ent=csu` or `?ent=ccc`
2. **Session**: `$_SESSION['enterprise_code']`
3. **Organization Password**: 4-digit password lookup
4. **Default**: `csu` if no other method succeeds

## Development Guide

### Daily Development Tasks

**Start Development (PowerShell PREFERRED on Windows)**
```powershell
# Check if server is running
netstat -an | findstr :8000

# Start server if needed
php -S localhost:8000

# Run health check
Invoke-WebRequest http://localhost:8000/health_check.php
```

**Stop Development (PowerShell PREFERRED on Windows)**
```powershell
# Stop PHP server
taskkill /F /IM php.exe

# Check for remaining processes
tasklist | findstr php
```

**Alternative: Git Bash Commands**
```bash
# Check if server is running
ps aux | grep php

# Start server if needed
php -S localhost:8000 &

# Run health check
curl -I http://localhost:8000/health_check.php

# Stop server
pkill -f "php -S localhost:8000"
```

**Common Testing Commands**
```bash
# Run all tests (either terminal)
php run_tests.php

# Test specific enterprise (either terminal)
php run_tests.php csu

# Run integration tests (either terminal)
php tests/run_all_tests.php

# Diagnostic tools (PowerShell PREFERRED on Windows)
./tests/diagnose_server.ps1
```

### Code Standards
- **PHP**: PSR-12 coding standards
- **JavaScript**: ES6+ with consistent naming
- **CSS**: Modular CSS with clear structure
- **File Naming**: snake_case for consistency
- **Testing**: 100% test coverage across all enterprises

### API Architecture

#### **External vs Internal API Pattern**
The application uses a deliberate separation between external and internal APIs to prevent output buffering race conditions:

**External APIs** (`reports_api.php`):
- Called by JavaScript via AJAX/fetch requests
- Set `Content-Type: application/json` headers
- Use output buffering for clean JSON output
- Always output JSON and exit

**Internal APIs** (`reports_api_internal.php`):
- Called by PHP via `require_once` includes
- NO HTTP headers (prevents "headers already sent" errors)
- NO output buffering (prevents JSON corruption of HTML pages)
- Return data arrays instead of outputting JSON

**Why This Pattern Exists:**
- **Race Condition Prevention**: Including external API files in HTML pages would cause JSON output instead of HTML
- **Architectural Necessity**: Same data processing logic needed for both browser and PHP consumption
- **Documented Duplication**: Function duplication is intentional and documented

See `best-practices.md` for detailed API architecture guidelines.

### Error Handling Patterns
```php
// Input validation
if (!isValidEnterpriseCode($code)) {
    return json_encode(['error' => 'Invalid enterprise code format']);
}

// Specific error messages
if ($password === $currentPassword) {
    return json_encode(['error' => 'Password already in use. Available: [1235, 1237, 1239]']);
}
```

### AJAX Implementation Standards
- **Detection**: Use `isset($_POST['action'])` for AJAX detection
- **Output Buffering**: Always use output buffering
- **Content-Type**: Set proper JSON Content-Type header
- **Exception Handling**: Always handle exceptions
- **Clean Exit**: Always exit after JSON response

## Configuration System

### Enterprise Configurations
Each enterprise has its own configuration file:
- `config/csu.config` - California State University (23 organizations)
- `config/ccc.config` - California Community Colleges
- `config/demo.config` - Testing environment

### Authentication System
```json
{
  "admin_passwords": {
    "csu": "4000",
    "ccc": "5000",
    "enterprise_builder": "6000",
    "groups_builder": "7000"
  },
  "organizations": [
    {
      "name": "Bakersfield",
      "password": "8472",
      "enterprise": "csu",
      "is_admin": false
    }
  ]
}
```

### Universal Paths Implementation
- **No Environment Detection**: Paths work on any server structure
- **Simple Relative Paths**: Direct relative paths throughout application
- **Cross-Server Compatibility**: Works without configuration changes
- **Directory Traversal**: Subdirectories use `../` to access root assets

## Core Features

### Authentication & Authorization
- **Unified Login**: Single login page for all organizations
- **Enterprise Detection**: Automatic detection from multiple sources
- **Session Management**: Secure session handling with enterprise context
- **Access Control**: Admin vs organization access levels

### Organization Dashboard
- **Data Display**: Real-time organization data with filtering
- **Enrollment Tracking**: Comprehensive enrollment management
- **Certificate Management**: Certificate earning and validation
- **Auto-Refresh**: 3-hour TTL with automatic data refresh
- **Loading Overlay**: Professional loading states during data operations

### Admin Interface
- **Data Management**: Comprehensive data control and monitoring
- **Cache Control**: Manual cache clearing and refresh capabilities
- **Enterprise Configuration**: Multi-enterprise administration
- **System Monitoring**: Health checks and diagnostic tools

### Reports System
- **Date-Range Filtering**: Flexible date selection for reports
- **Organization Data**: Detailed organization-specific reporting
- **Export Capabilities**: Data export in multiple formats
- **Filter State Management**: Persistent filter states across sessions

## API Documentation

### Internal APIs

**Organizations API**
- `OrganizationsAPI::getOrgData($org)` - Get organization data
- `OrganizationsAPI::getAllCertificatesEarnedRowsAllRange($org)` - Get certificates

**Enterprise Data Service**
- Google Sheets integration
- Data processing and validation
- Cache management
- Error handling and logging

**Cache Management API**
- `EnterpriseCacheManager::getInstance()` - Cache manager instance
- `isCacheFresh($ttl)` - Cache freshness checking
- `clearAllCache()` - Manual cache clearing

### External Integrations
- **Google Sheets API**: Real-time data integration
- **Database Connections**: MySQL database management
- **File System Operations**: Cache and configuration file management

## Troubleshooting Guide

### Common Issues & Solutions

**Server Won't Start (PowerShell PREFERRED on Windows)**
```powershell
# Check if port is in use
netstat -an | findstr :8000

# Kill existing PHP processes
taskkill /F /IM php.exe

# Check for other processes
tasklist | findstr php
```

**Alternative: Git Bash Commands**
```bash
# Check if port is in use
ps aux | grep php

# Kill existing PHP processes
pkill -f "php -S localhost:8000"

# Check for other processes
ps aux | grep php
```

**Login Problems**
- Check password format (4-digit numeric)
- Verify enterprise configuration
- Check session management
- Review error logs

**Data Loading Issues**
- Verify cache status and TTL
- Check Google Sheets API access
- Review network connectivity
- Validate enterprise configuration

**Cache Problems**
- Manual cache clearing through admin interface
- Check cache directory permissions
- Verify cache file integrity
- Review cache TTL settings

### Debug Tools
- **Health Check**: `http://localhost:8000/health_check.php`
- **Diagnostic Scripts**: `./tests/diagnose_server.ps1`
- **Error Logging**: `php_errors.log` for detailed error information
- **Test Utilities**: Comprehensive test suite for validation

## Deployment & Operations

### Production Deployment
- **GitHub Actions Integration**: Automated deployment pipeline
- **SFTP Configuration**: Secure file transfer configuration
- **Environment Setup**: Production environment configuration
- **Monitoring and Logging**: Comprehensive monitoring tools

### Configuration Management
- **Environment-Specific Settings**: Flexible environment configuration
- **Database Configuration**: Secure database connection management
- **Cache Management**: Production cache configuration
- **Backup Procedures**: Automated backup and recovery

## Security Considerations

### Production Security Checklist
- [ ] Password validation and secure storage
- [ ] Session security and management
- [ ] Input sanitization and validation
- [ ] Access control mechanisms
- [ ] Error handling without information disclosure
- [ ] HTTPS enforcement
- [ ] Regular security updates

### Known Limitations
- **MVP Scope**: Focused on core functionality, advanced features planned for future
- **Performance**: Optimized for current usage patterns, scaling considerations documented
- **Browser Support**: Modern browsers only, no legacy support required

## Success Criteria

### Project Success Metrics
- [ ] No duplicate code between classes
- [ ] Universal relative paths work correctly across all scenarios
- [ ] URL generation produces simple, consistent relative paths
- [ ] Error messages are specific and actionable
- [ ] Code is simpler and more maintainable
- [ ] Multi-enterprise architecture is supported
- [ ] WCAG compliance is maintained
- [ ] Clean implementation without legacy compatibility requirements

## Support Information

- **Contact**: accessibledocs@webaim.org
- **Documentation**: Project rules and best practices
- **Issue Reporting**: GitHub issues and changelog
- **Community Resources**: Development guidelines and standards

---

**For detailed development guidelines and project rules, see `project-rules.md` and `best-practices.md`.**
