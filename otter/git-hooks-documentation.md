# Git Hooks Documentation

> **Note:** Git Bash is the only supported environment for all git hooks and development tasks. PowerShell and .ps1 scripts are legacy and provided for historical reference only.

## Overview

This document describes the git hooks implemented to prevent common PHP errors that have been recurring in the changelog. The hooks are designed to catch issues before they reach the repository and cause problems in production.

**Environment**: All hooks now use **Git Bash** as the standard environment, replacing the previous PowerShell/Windows approach.

## Implemented Hooks

### 1. Pre-commit Hook

**Purpose**: Validates staged PHP files before committing to prevent common errors.

**Checks Performed**:
- **PHP Closing Tags (`?>`)**: Prevents JSON contamination that causes "Unexpected token '<'" errors
- **Trailing Whitespace**: Prevents whitespace contamination in JSON responses
- **Session Management**: Warns about `session_start()` without proper status checks
- **PHP Syntax Errors**: Basic PHP syntax validation
- **AJAX Output Buffering**: Warns about JSON headers without output buffering

**Files**:
- `.git/hooks/pre-commit` (Bash version - **Primary**)
- `.git/hooks/pre-commit.ps1` (PowerShell version - **Legacy**)

### 2. Pre-push Hook

**Purpose**: Comprehensive validation before any potential remote operations.

**Checks Performed**:
- **Critical API Issues**: Blocks push if PHP closing tags found in API/reports files
- **PHP Syntax Errors**: Comprehensive syntax validation across all PHP files
- **Session Management**: Warns about session issues
- **File Structure**: Checks for critical project files
- **Git Configuration**: Validates git user settings

**Files**:
- `.git/hooks/pre-push` (Bash version - **Primary**)
- `.git/hooks/pre-push.ps1` (PowerShell version - **Legacy**)

## File Type Classification

### 1. Pure PHP Files (API/JSON Output)

**Purpose**: Files that output JSON data for AJAX requests.

**Characteristics**:
- Contain only PHP code
- Output JSON responses
- No HTML content
- Used for API endpoints

**Examples**:
- `lib/api/*.php`
- `reports/reports_api.php`
- `reports/reports_api_internal.php`

**PHP Closing Tag Rule**: **STRICTLY FORBIDDEN** - These files must never contain `?>` closing tags.

### 2. HTML Files with Embedded PHP

**Purpose**: Files that render HTML pages with embedded PHP logic.

**Characteristics**:
- Mix HTML and PHP content
- Render complete web pages
- Use PHP for data processing and logic
- Output HTML, not JSON

**Examples**:
- `reports/certificates-earned.php`
- `reports/enrollees.php`
- `reports/registrants.php`
- `dashboard.php`
- `login.php`

**PHP Closing Tag Rule**: **ALLOWED** - These files may contain `?>` closing tags where necessary for HTML rendering.

**Current Issue**: The pre-push hook incorrectly treats all files in `reports/` directory as API files. This needs to be updated to distinguish between file types.

## Error Prevention

### 1. PHP Closing Tags (`?>`)

**Problem**: PHP closing tags in files that output JSON cause HTML to be sent instead of JSON, resulting in "Unexpected token '<'" errors in browser console.

**Solution**: Hooks detect and block commits containing `?>` in **pure PHP files only**.

**Example Error** (Pure PHP file):
```php
<?php
// API code here
echo json_encode($data);
?>  // ← This causes the problem
```

**Fix** (Pure PHP file):
```php
<?php
// API code here
echo json_encode($data);
// Remove the closing tag entirely
```

**Example** (HTML with embedded PHP - **ALLOWED**):
```php
<?php
ob_start();
require __DIR__ . '/data.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Report</title>
</head>
<body>
    <?php foreach ($data as $item): ?>
        <div><?= htmlspecialchars($item) ?></div>
    <?php endforeach; ?>
</body>
</html>
<?php
echo ob_get_clean();
?>
```

### 2. Trailing Whitespace

**Problem**: Trailing whitespace can contaminate JSON responses and cause parsing errors.

**Solution**: Hooks detect and block commits with trailing whitespace.

### 3. Session Management

**Problem**: `session_start()` called multiple times causes "session already started" warnings.

**Solution**: Hooks warn about `session_start()` without proper status checks.

**Recommended Pattern**:
```php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
```

### 4. AJAX Output Buffering

**Problem**: JSON responses without output buffering can be contaminated by unexpected output.

**Solution**: Hooks warn about JSON headers without `ob_start()`.

**Recommended Pattern**:
```php
ob_start();
header('Content-Type: application/json');
// ... AJAX logic ...
ob_clean();
echo json_encode($result);
```

## Usage

### Environment Setup

**Primary Environment**: Git Bash (`C:\Program Files\Git\bin\bash.exe`)

**Terminal Configuration**:
- Use Git Bash for all development operations
- Ensure hooks are executable: `chmod +x .git/hooks/pre-commit .git/hooks/pre-push`
- Configure git pager: `git config --global core.pager cat`

### Automatic Execution

The hooks run automatically when you:
- **Commit**: `git commit` triggers pre-commit hook
- **Push**: `git push` triggers pre-push hook

### Manual Testing

You can test the hooks manually:

```bash
# Test pre-commit hook
.git/hooks/pre-commit

# Test pre-push hook
.git/hooks/pre-push
```

### Legacy PowerShell Testing (Windows)

> **Legacy:** The following commands are for historical reference only. Use Git Bash for all new development and testing.

```powershell
# Test PowerShell versions directly (legacy)
powershell.exe -ExecutionPolicy Bypass -File .git/hooks/pre-commit.ps1
powershell.exe -ExecutionPolicy Bypass -File .git/hooks/pre-push.ps1
```

## Error Messages

### Pre-commit Hook Messages

- **❌ ERROR**: Critical issues that block the commit
- **⚠️ WARNING**: Issues that should be addressed but don't block the commit

### Pre-push Hook Messages

- **❌ CRITICAL**: Issues that completely block pushing
- **❌ ERROR**: Issues that block pushing
- **⚠️ WARNING**: Issues that should be addressed

## Common Fixes

When hooks fail, here are the most common fixes:

1. **Remove PHP closing tags** from **pure PHP files** that output JSON
2. **Remove trailing whitespace** from files
3. **Add session status checks** before `session_start()`
4. **Add output buffering** for AJAX handlers
5. **Fix PHP syntax errors** identified by the hooks

## File Type Handling

### Current Limitations

**Issue**: The pre-push hook treats all files in `reports/` directory as API files, causing false positives for HTML files with embedded PHP.

**Impact**: HTML files with legitimate `?>` closing tags are being blocked from pushing.

**Workaround**: Use `git push --no-verify` when pushing HTML files with embedded PHP until the hook logic is updated.

### Planned Improvements

**Solution**: Update hook logic to distinguish between file types:

1. **Pure PHP files**: Check for `?>` closing tags (forbidden)
2. **HTML with embedded PHP**: Allow `?>` closing tags
3. **Detection method**: Check for HTML content (`<!DOCTYPE`, `<html>`, etc.)

**Implementation**:
```bash
# Example logic for future hook update
if grep -q "<!DOCTYPE\|<html" "$file"; then
    # HTML file with embedded PHP - allow closing tags
    echo "HTML file detected - closing tags allowed"
else
    # Pure PHP file - check for closing tags
    if grep -q "?>" "$file"; then
        echo "ERROR: PHP closing tag found in pure PHP file"
    fi
fi
```

## Configuration

### Disabling Hooks (Temporary)

To temporarily disable hooks for a single operation:

```bash
git commit --no-verify
git push --no-verify
```

### Permanently Disabling Hooks

Remove or rename the hook files:
```bash
mv .git/hooks/pre-commit .git/hooks/pre-commit.disabled
mv .git/hooks/pre-push .git/hooks/pre-push.disabled
```

## Maintenance

### Updating Hooks

To update the hooks:
1. Edit the appropriate hook file
2. Test manually to ensure it works
3. Commit the changes

### Adding New Checks

To add new validation checks:
1. Identify the recurring error pattern
2. Add the check to the Bash version (primary)
3. Test thoroughly
4. Document the new check

## Troubleshooting

### Hook Not Running

1. Check file permissions: `ls -la .git/hooks/`
2. Verify hook is executable: `chmod +x .git/hooks/pre-commit`
3. Check for syntax errors in hook files
4. Ensure you're using Git Bash environment

### False Positives

If hooks are blocking valid code:
1. Review the specific error message
2. Consider if the code pattern is actually problematic
3. Check if the file is HTML with embedded PHP (allowed) vs pure PHP (forbidden)
4. Modify the hook to be more specific if needed

### Performance Issues

If hooks are slow:
1. Check if PHP syntax validation is taking too long
2. Consider excluding large files or directories
3. Optimize regex patterns in the hooks

## Integration with Project Rules

These hooks align with the project's core principles:

- **Simple**: Focus on specific, documented problems
- **Reliable**: Prevent errors that break functionality
- **Accurate**: Target issues that cause actual problems
- **MVP Focus**: Address immediate needs without over-engineering
- **Git Bash Standard**: All operations use Git Bash for consistency

The hooks specifically enforce the AJAX implementation standards and prevent the recurring issues documented in the changelog.

## Migration from PowerShell to Git Bash

1. **Primary Environment**: Switched from PowerShell to Git Bash. All new hooks and scripts must use Git Bash.
2. **Legacy Support**: PowerShell versions (`.ps1`) remain for backward compatibility only. Do not use for new development.
3. **Performance**: Bash hooks are generally faster and more reliable than PowerShell equivalents.
4. **Remove Legacy Files**: Plan to remove PowerShell versions when no longer needed.

### Changes Made

1. **Primary Environment**: Switched from PowerShell to Git Bash
2. **Hook Files**: Updated `.git/hooks/pre-commit` and `.git/hooks/pre-push` to use Bash syntax
3. **Legacy Support**: PowerShell versions (`.ps1`) remain for backward compatibility
4. **Documentation**: Updated to reflect Git Bash as the standard environment

### Benefits

1. **Consistency**: All development operations use the same shell environment
2. **Unix Compatibility**: Bash syntax works across Windows, macOS, and Linux
3. **Git Integration**: Better integration with Git's native Unix-like commands
4. **Performance**: Bash hooks are generally faster than PowerShell equivalents

### Next Steps

1. **Update Hook Logic**: Modify pre-push hook to distinguish between file types
2. **Remove Legacy Files**: Eventually remove PowerShell versions when no longer needed
3. **Test Coverage**: Ensure all scenarios work correctly in Git Bash environment 