# Tests README

> **Note:** PowerShell is preferred for server management and diagnostics on Windows machines. Git Bash is required only for Git operations. See project rules for detailed terminal usage guidelines.

# Clients-Enterprise Testing System

## Overview

The Clients-Enterprise testing system provides enterprise-agnostic testing capabilities that validate functionality across all enterprises (CSU, CCC, demo) while maintaining MVP principles: simple, reliable, accurate, and WCAG compliant.

## Architecture

### Test Structure
```
tests/
├── test_base.php                    # Base test class with common functionality
├── run_enterprise_tests.php         # Enterprise-specific test runner
├── run_all_tests.php               # Master test runner for all enterprises
├── unit/                           # Unit tests
│   └── config_test.php            # Configuration validation tests
├── integration/                    # Integration tests
│   ├── login_test.php             # Login process tests
│   ├── data_service_test.php      # Data service tests
│   ├── direct_links_test.php      # Direct link functionality tests
│   ├── force_refresh_test.php     # Cache refresh tests
│   └── fix_chico_urls_test.php    # URL fix tests
└── enterprise/                     # Enterprise-specific tests
    └── csu_test.php               # CSU-specific validation tests
```

## Key Features

### ✅ Enterprise-Agnostic Testing
- All tests work with any enterprise (CSU, CCC, demo)
- No hardcoded enterprise references
- Automatic enterprise detection and configuration

### ✅ Comprehensive Coverage
- **Configuration Tests**: Validate enterprise config loading
- **API Tests**: Verify API endpoints and responses
- **Login Tests**: Test authentication and session management
- **Data Service Tests**: Validate data service functionality
- **Direct Links Tests**: Test URL generation and direct links

### ✅ MVP Compliance
- **Simple**: Easy to understand and maintain
- **Reliable**: Consistent test execution across environments
- **Accurate**: Validates actual functionality, not just syntax
- **WCAG Compliant**: Maintains accessibility standards

## Usage

### Quick Start

#### Test All Enterprises
```bash
php run_tests.php
# or
php run_tests.php all
```

#### Test Specific Enterprise
```bash
php run_tests.php csu
php run_tests.php ccc
php run_tests.php demo
```

#### Test Specific Enterprise Category
```bash
php tests/run_enterprise_tests.php csu
```

### Command Line Options

The test runner supports various command line options:

```bash
# Test all enterprises
php run_tests.php

# Test specific enterprise
php run_tests.php csu

# Test specific enterprise with test type (future enhancement)
php run_tests.php csu config
```

## Test Categories

### 1. Configuration Tests
- Enterprise configuration loading
- Organization data validation
- Admin organization detection
- URL configuration validation

### 2. API Tests
- API endpoint accessibility
- JSON response validation
- Error handling verification

### 3. Login Tests
- Password validation
- Session management
- Authentication flow

### 4. Data Service Tests
- Data service file existence
- Cache management validation

### 5. Direct Links Tests
- Direct link file existence
- URL generation validation

## Test Base Class

The `TestBase` class provides common functionality for all tests:

### Core Methods
- `setEnterprise($enterprise_code)` - Set enterprise to test
- `getEnterprise()` - Get current test enterprise
- `initEnterprise($enterprise_code)` - Initialize enterprise configuration
- `runTest($test_name, $test_function)` - Run test with error handling

### Assertion Methods
- `assertTrue($condition, $message)` - Assert condition is true
- `assertFalse($condition, $message)` - Assert condition is false
- `assertEquals($expected, $actual, $message)` - Assert values are equal
- `assertNotNull($value, $message)` - Assert value is not null
- `assertNotEmpty($value, $message)` - Assert value is not empty
- `assertValidUrl($url, $message)` - Assert URL is valid
- `assertGreaterThan($expected, $actual, $message)` - Assert value is greater than

## Enterprise-Specific Testing

### CSU Tests
```php
// CSU-specific validation
TestBase::runTest('CSU Enterprise Name', function() {
    $enterprise = EnterpriseConfig::getEnterprise();
    TestBase::assertEquals('CSU', $enterprise['name']);
});
```

### Adding New Enterprise Tests
1. Create enterprise-specific test file in `tests/enterprise/`
2. Use `TestBase::setEnterprise()` to set the enterprise
3. Add enterprise-specific validation logic
4. Include the test in the master test runner

## Test Results

### Success Criteria
- ✅ All tests pass for all enterprises
- ✅ No enterprise-specific failures
- ✅ Consistent behavior across environments
- ✅ WCAG compliance maintained

### Output Format
```
=== Testing Enterprise: CSU ===
Running Configuration Tests...
[PASS] Enterprise Config Loading:
[PASS] Organizations Loading:
[PASS] Admin Organization:
[PASS] URL Configuration:

=== Enterprise Test Summary ===
Enterprise: CSU
Total Tests: 9
Passed: 9
Failed: 0
Success Rate: 100%
✅ All tests passed for CSU!
```

## Best Practices

### 1. Enterprise-Agnostic Code
```php
// ✅ Good: Use TestBase for enterprise management
TestBase::initEnterprise('csu');

// ❌ Bad: Hardcode enterprise
EnterpriseConfig::init('csu');
```

### 2. Comprehensive Assertions
```php
// ✅ Good: Multiple assertions for thorough validation
TestBase::assertNotNull($enterprise, 'Enterprise should be loaded');
TestBase::assertNotEmpty($enterprise['name'], 'Enterprise name should not be empty');
TestBase::assertEquals('CSU', $enterprise['name'], 'Enterprise should be CSU');

// ❌ Bad: Single assertion may miss issues
TestBase::assertNotNull($enterprise);
```

### 3. Clear Test Names
```php
// ✅ Good: Descriptive test names
TestBase::runTest('Admin Organization Detection', function() {
    // Test logic
});

// ❌ Bad: Unclear test names
TestBase::runTest('Test 1', function() {
    // Test logic
});
```

## Troubleshooting

### Common Issues

#### 1. Session Warnings
**Problem**: `session_start(): Session cannot be started after headers have already been sent`
**Solution**: Use output buffering in session tests
```php
ob_start();
session_start();
// Test logic
        ob_clean();
```

#### 2. Enterprise Not Found
**Problem**: `Enterprise configuration file not found`
**Solution**: Ensure enterprise config files exist in `config/{enterprise}/enterprise.json`

#### 3. Test Failures
**Problem**: Tests failing for specific enterprise
**Solution**: 
1. Check enterprise configuration file
2. Verify required files exist
3. Validate enterprise-specific data

## Future Enhancements

### Planned Features
1. **Test Categories**: Support for running specific test categories
2. **Performance Tests**: Load testing and performance validation
3. **Accessibility Tests**: Automated WCAG compliance testing
4. **Coverage Reports**: Test coverage analysis
5. **Continuous Integration**: Automated test execution

### Extensibility
The testing system is designed to be easily extensible:
- Add new test categories in `run_enterprise_tests.php`
- Create enterprise-specific tests in `tests/enterprise/`
- Extend `TestBase` class with new assertion methods
- Add new enterprises by updating the enterprises array

## Maintenance

### Regular Tasks
1. **Update Tests**: When new features are added
2. **Validate Coverage**: Ensure all critical paths are tested
3. **Review Failures**: Investigate and fix test failures
4. **Update Documentation**: Keep this README current

### Adding New Tests
1. Create test file in appropriate directory
2. Use `TestBase` class for enterprise-agnostic testing
3. Add test to relevant test runner
4. Update documentation
5. Run full test suite to verify

## Success Metrics

- **Test Coverage**: 100% of critical functionality tested
- **Enterprise Coverage**: All enterprises (CSU, CCC, demo) validated
- **Reliability**: Tests pass consistently across environments
- **Maintainability**: Easy to add new tests and enterprises
- **Performance**: Tests complete in reasonable time
- **Accessibility**: WCAG compliance maintained

This testing system ensures that the Clients-Enterprise application maintains high quality and reliability across all enterprises while following MVP principles.

# Password Change and Direct Link Test Suite

This test suite validates the refactored password change functionality and direct link updates in the clients-enterprise application.

## Overview

The test suite covers:
- **Backend Tests**: Database operations, enterprise.json regeneration, direct link generation
- **Frontend Tests**: JavaScript module functionality, AJAX requests, DOM manipulation
- **Integration Tests**: End-to-end password change workflow
- **Environment Tests**: Configuration validation, file permissions, database connectivity

## Test Files

### 1. `password_change_test.php`
**Backend PHP tests** that validate:
- Database password updates
- Enterprise.json regeneration
- Direct link generation with new passwords
- AJAX endpoint functionality
- Enterprise API endpoint
- Cache clearing functionality

**Usage:**
```bash
# Command line
php password_change_test.php

# Web browser
http://localhost:8000/clients-enterprise/tests/password_change_test.php?run_tests=1
```

### 2. `frontend_test.html`
**Frontend JavaScript tests** that validate:
- Module imports and functionality
- Direct link fetching from enterprise API
- Cache clearing operations
- AJAX request handling
- DOM manipulation for table updates
- Error handling scenarios

**Usage:**
```bash
# Open in web browser
http://localhost:8000/clients-enterprise/tests/frontend_test.html
```

### 3. `run_tests.php`
**Comprehensive test runner** that executes all tests and generates detailed reports.

**Usage:**
```bash
# Command line
php run_tests.php

# Web browser
http://localhost:8000/clients-enterprise/tests/run_tests.php?run_tests=1
```

## Running the Tests

### Prerequisites
1. PHP development server running on localhost:8000
2. Database connection configured
3. Test organization "TEST_ORG" exists in the database (optional)

### Quick Start
1. **Start the PHP server:**
   ```bash
   cd clients-refresh
   php -S localhost:8000 --router=router.php
   ```

2. **Run comprehensive tests:**
   ```bash
   cd clients-enterprise/tests
   php run_tests.php
   ```

3. **View frontend tests:**
   - Open `http://localhost:8000/clients-enterprise/tests/frontend_test.html`
   - Click "Run All Tests" to execute frontend validation

## Test Categories

### Backend Tests
- **Database Password Update**: Validates password changes in the database
- **Enterprise.json Regeneration**: Tests the regeneration of enterprise configuration files
- **Direct Link Generation**: Verifies direct links are generated with new passwords
- **AJAX Endpoint**: Tests the password change AJAX handler
- **Enterprise API**: Validates the enterprise API endpoint
- **Cache Clearing**: Tests cache invalidation functionality

### Frontend Tests
- **Module Import**: Validates ES6 module imports
- **Direct Link Fetching**: Tests fetching direct links from the API
- **Cache Clearing**: Tests frontend cache clearing operations
- **AJAX Request**: Simulates password change AJAX requests
- **Enterprise API**: Tests API endpoint accessibility
- **DOM Manipulation**: Validates table refresh functionality
- **Error Handling**: Tests various error scenarios

### Environment Tests
- **File Permissions**: Validates file accessibility and permissions
- **Environment Configuration**: Tests enterprise configuration loading
- **Database Connectivity**: Validates database connection and operations

## Expected Results

### Successful Test Run
```
✅ ALL TESTS PASSED! The refactored code is working correctly.
```

### Test Output Example
```
Starting Comprehensive Test Suite...
====================================

Testing File Permissions...
==========================
[PASS] unified_database.php
[PASS] direct_link.php
[PASS] enterprise_config.php
[PASS] enterprise_api.php
[PASS] dashboard-link-utils.js
[PASS] index.php
[PASS] Config Directory

Testing Environment Configuration...
====================================
[PASS] Enterprise Config
[PASS] Environment Detection

Testing Database Connectivity...
==============================
[PASS] Connection

Running Backend Tests...
=======================
[PASS] Database Password Update
[PASS] Enterprise.json Regeneration
[PASS] Direct Link Generation
[PASS] AJAX Endpoint
[PASS] Enterprise API Endpoint
[PASS] Cache Clearing

Running Frontend Tests...
========================
[PASS] Test File Exists
[PASS] Test File Content
[PASS] Module Imports

SUMMARY
=======
Total Tests: 15
Passed: 15
Failed: 0
Success Rate: 100.0%

✅ ALL TESTS PASSED! The refactored code is working correctly.
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify database configuration in `lib/unified_database.php`
   - Check database server is running
   - Ensure database credentials are correct

2. **File Permission Errors**
   - Check file permissions on critical files
   - Ensure config directory is writable
   - Verify PHP has read/write access

3. **Module Import Errors**
   - Check browser console for CORS issues
   - Verify ES6 modules are supported
   - Ensure file paths are correct

4. **AJAX Request Failures**
   - Check network connectivity
   - Verify server is running on correct port
   - Check browser console for error details

### Debug Mode
Enable detailed error reporting by adding to test files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Test Data

The tests use a test organization named "TEST_ORG" with:
- Original password: "1234"
- Test password: "5678"

If this organization doesn't exist, some tests may fail. You can:
1. Create the test organization manually
2. Modify the test to use an existing organization
3. Skip organization-specific tests

## Customization

### Adding New Tests
1. Create test method in `PasswordChangeTest` class
2. Add test call to `runAllTests()` method
3. Update test documentation

### Modifying Test Data
Edit the test constants in `password_change_test.php`:
```php
private $testOrgName = 'YOUR_TEST_ORG';
private $originalPassword = 'original';
private $newPassword = 'new';
```

## Report Generation

Test reports are automatically saved to:
```
test_report_YYYY-MM-DD_HH-MM-SS.txt
```

Reports include:
- Test execution timestamp
- Duration
- Detailed results by category
- Success/failure summary
- Error details for failed tests

## Integration with CI/CD

The test suite can be integrated into continuous integration pipelines:

```bash
# Example CI script
cd clients-enterprise/tests
php run_tests.php > test_output.txt 2>&1
if grep -q "ALL TESTS PASSED" test_output.txt; then
    echo "Tests passed successfully"
    exit 0
else
    echo "Tests failed"
    exit 1
fi
```

## Support

For issues with the test suite:
1. Check the troubleshooting section above
2. Review test output for specific error messages
3. Verify all prerequisites are met
4. Check file permissions and database connectivity 

# Diagnostic Tools

This directory contains PowerShell scripts for server management and diagnostics. (PREFERRED on Windows)

For server management and diagnostics on Windows, use PowerShell commands:

```powershell
# Start PHP server with error logging
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log

# Generate a timestamp
Get-Date -Format "yyyy-MM-dd HH:mm:ss"

# Run diagnostic script
./diagnose_server.ps1
```

For Git operations, use Git Bash commands:

```bash
# Git operations (Git Bash MANDATORY)
git add .
git commit -m "message"
git push origin main

# Generate a timestamp (Git Bash)
date +"%Y-%m-%d %H:%M:%S"
```

## Tools

### `start_server.ps1`