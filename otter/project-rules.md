# Project Rules for AI Agents - Optimized Version

## 🚨 CRITICAL RULES - ALWAYS CHECK FIRST
| Rule | Action Required | Why Critical |
|------|----------------|--------------|
| **WAIT** in prompt | **STOP** - Get explicit authorization before acting | Prevents unauthorized actions |
| **Git Operations: Git Bash MANDATORY** | **ALWAYS** use Git Bash terminal for Git operations | Ensures reliable git integration and path handling |
| **Server Management: PowerShell PREFERRED** | **USE** PowerShell for server management and testing on Windows | Better Windows process management and diagnostics |
| **No Remote Push** | **NEVER** push without explicit user permission | Security requirement |
| **AJAX Pattern** | **ALWAYS** use `isset($_POST['action'])` detection | Prevents JSON/HTML errors |
| **Working Directory** | **ALWAYS** operate from `otter/` root | Ensures correct path resolution |

---

## 🎯 CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant
- **No Backwards Compatibility:** Clean implementation, no legacy support needed
- **AI Agent Autonomy:** Handle all tasks without user intervention unless authorization required
- **Context-Based Terminal Usage:** Use appropriate terminal for specific tasks

---

## 🖥️ TERMINAL USAGE GUIDELINES

### **Git Operations: Git Bash MANDATORY**
- **Path:** `C:\Program Files\Git\bin\bash.exe`
- **Why Required:** Provides reliable git integration and Unix-style path handling
- **Commands:** `git add`, `git commit`, `git push`, `git status`, `git log`, `git branch`
- **Known Issues:** Git operations in PowerShell can have path handling and integration problems

### **Server Management & Testing: PowerShell PREFERRED (Windows)**
- **Why Preferred:** Better Windows process management, native HTTP testing, and diagnostic tools
- **Commands:** `php -S localhost:8000`, `Invoke-WebRequest`, `netstat`, `tasklist`, `taskkill`
- **Environment:** Windows machine with PowerShell 5.1+ or PowerShell Core
- **Known Issues:** Path separators may need adjustment for PHP commands

### **Development Tasks: Context Dependent**
- **File Operations:** Either terminal works well
- **PHP Execution:** Either terminal works well
- **Path Handling:** Choose based on path style needed (Unix vs Windows)

---

## ⚡ QUICK REFERENCE COMMANDS

### Git Operations (Git Bash MANDATORY)
```bash
# View operations
git log --oneline -10
git diff
git branch -a
git status

# Basic operations
git add .
git commit -m "message"
git checkout -b feature-name
git checkout main

# Remote operations (REQUIRES USER PERMISSION)
git push origin main  # NEVER without permission
```

### Server Management (PowerShell PREFERRED)
```powershell
# Start server (PowerShell)
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log

# Check server status (PowerShell)
netstat -an | findstr :8000
tasklist | findstr php

# Stop server (PowerShell)
taskkill /F /IM php.exe

# HTTP testing (PowerShell)
Invoke-WebRequest http://localhost:8000/health_check.php
```

### Git Bash Server Management (Alternative)
```bash
# Start server (Git Bash)
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log &

# Check server status (Git Bash)
ps aux | grep php

# Stop server (Git Bash)
pkill -f "php -S localhost:8000"

# HTTP testing (Git Bash)
curl -I http://localhost:8000/health_check.php
```

### AJAX Handler Pattern (PHP)
```php
<?php
ob_start();
header('Content-Type: application/json');

if (isset($_POST['action'])) {
    try {
        ob_clean();
        $response = ['success' => true, 'data' => 'result'];
        echo json_encode($response);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
ob_end_flush();
?>
```

---

## 🔧 AI AGENT RESPONSIBILITIES

### ✅ DO THESE AUTOMATICALLY
- **Use appropriate terminal for specific tasks**
- Handle ALL terminal operations without asking user
- Manage development environment proactively
- Test functionality after every significant change
- Update changelog before major commits
- Suggest automation tools when recurring issues detected
- Escalate repeated errors to user with systemic solutions

### ❌ NEVER DO THESE WITHOUT PERMISSION
- Push to remote repositories
- Execute destructive operations without confirmation
- Introduce security/privacy features unless requested
- Ask user to perform tasks you can do yourself
- **Use Git Bash for server management when PowerShell is available**

### 🚨 ESCALATION TRIGGERS
- Same error occurs more than once → Propose systemic solution
- Destructive operation detected → Request explicit confirmation
- No linter/formatter detected → Suggest adding one
- Documentation changes → Update all related references
- **Wrong terminal for task → Switch to appropriate terminal**

---

## 🖥️ DEVELOPMENT ENVIRONMENT

### Terminal Configuration
- **Git Operations:** Git Bash (`C:\Program Files\Git\bin\bash.exe`) - **MANDATORY**
- **Server Management:** PowerShell (Windows) - **PREFERRED**
- **Development Tasks:** Context dependent - choose appropriate terminal
- **How to Open:** 
  - VS Code/Cursor: Terminal dropdown → Select appropriate terminal
  - Command Palette: "Terminal: Select Default Profile" → Choose terminal
  - Direct: Run appropriate terminal executable

### Server Configuration
- **Type:** PHP built-in development server
- **Port:** 8000 (http://localhost:8000)
- **Directory:** Always start from `otter/` root
- **Health Check:** `http://localhost:8000/health_check.php`

### File Structure
- **Root:** `otter/` directory
- **Changelog:** `clients-enterprise/changelog.md`
- **Config:** `config/` directory
- **Tests:** `tests/` directory
- **Assets:** `css/`, `js/`, `lib/` directories

### Path Implementation
- **Universal Relative Paths:** No environment detection needed
- **Simple URLs:** Direct relative paths (e.g., `assets/css/admin.css`)
- **Cross-Server Compatible:** Works on any server structure
- **PATH_INFO Handling:** Always clean PATH_INFO before redirects

---

## 🧪 TESTING PROTOCOL

### Pre-Testing Checklist
1. ✅ **Using appropriate terminal for task**
2. ✅ Server running: Check with appropriate command
3. ✅ PHP processes: Check with appropriate command
4. ✅ Working directory: Should show `otter/`
5. ✅ Error log: Check `php_errors.log`

### Testing Sequence
1. **Ensure appropriate terminal is active**
2. Start server with appropriate command
3. Wait 2-3 seconds for initialization
4. Use appropriate HTTP testing command
5. Verify response codes and content
6. Check PHP errors in terminal output
7. Review error log for details

### Error Recovery
- **Port conflicts:** Stop server with appropriate command or use different port
- **Server won't start:** Check PHP processes and port availability
- **Unexpected responses:** Check PHP syntax and error logs
- **Timeouts:** Verify server is actually running
- **Wrong terminal:** Switch to appropriate terminal for task

---

## 📝 CHANGELOG MANAGEMENT

### Commands
- **`changelog`:** Document all session changes
- **`changelog status`:** Document current application functionality

### Timestamp Generation
```bash
# Git Bash
date +"%Y-%m-%d %H:%M:%S"
```
```powershell
# PowerShell
Get-Date -Format "yyyy-MM-dd HH:mm:ss"
```

### Changelog Location
`clients-enterprise/changelog.md`

---

## 🔒 SAFETY PROCEDURES

### Pre-Operation Checks
- **Verify appropriate terminal is active**
- Verify working directory: Should show `otter/`
- Check repository status: `git status` (Git Bash)
- Confirm branch: `git branch -a` (Git Bash)
- Backup changes: `git stash` if needed (Git Bash)

### Operation Validation
- Review changes: `git diff` (Git Bash)
- Validate staged: `git diff --cached` (Git Bash)
- Test functionality after commits
- Confirm remote operations before pushing

### Emergency Procedures
- **Git hangs:** Use Git Bash, or try `git config --global --unset core.pager`
- **Server issues:** Check port conflicts and PHP processes with appropriate terminal
- **AJAX fails:** Verify detection pattern and JSON formatting
- **Paths break:** Confirm relative path implementation
- **Wrong terminal:** Switch to appropriate terminal for task

---

## 🎯 SUCCESS CRITERIA
- ✅ **Appropriate terminal used for specific tasks**
- ✅ No duplicate code between classes
- ✅ Universal relative paths work across all scenarios
- ✅ Simple, consistent relative paths generated
- ✅ Specific, actionable error messages
- ✅ Simpler, more maintainable code
- ✅ Multi-enterprise architecture supported
- ✅ WCAG compliance maintained
- ✅ Clean implementation without legacy requirements

---

## 📋 COMMAND REFERENCE

### Git Bash Commands (Git Operations MANDATORY)
- Use `ls -la` to list files
- Use `pwd` to print working directory
- Use `git` commands for all version control operations
- Use `date +"%Y-%m-%d %H:%M:%S"` for timestamps

### PowerShell Commands (Server Management PREFERRED)
- Use `dir` or `ls` to list files
- Use `pwd` to print working directory
- Use `netstat -an | findstr :8000` to check port usage
- Use `tasklist | findstr php` to check PHP processes
- Use `taskkill /F /IM php.exe` to stop server
- Use `Invoke-WebRequest` for HTTP testing
- Use `Get-Date -Format "yyyy-MM-dd HH:mm:ss"` for timestamps

### Git Commands (Git Bash MANDATORY)
```bash
git log --oneline -10
git diff
git branch -a
git status
git add .
git commit -m "message"
git push origin main  # NEVER without permission
git stash
git checkout -b feature-name
git checkout main
```

---

## 🚀 IMPLEMENTATION NOTES

### Critical Success Factors
1. **Use Git Bash for all Git operations**
2. **Use PowerShell for server management on Windows**
3. **Choose appropriate terminal for specific tasks**
4. **Never push to remote without explicit user permission**
5. **Follow AJAX detection patterns to prevent JSON errors**
6. **Execute all operations from otter/ directory**
7. **Document all changes in changelog**
8. **Test functionality after every significant change**

### Quality Assurance
- **Verify appropriate terminal is active before operations**
- Test all git operations before implementing
- Verify server functionality after changes
- Check for PHP syntax errors
- Validate AJAX responses
- Confirm WCAG compliance
- Maintain MVP focus throughout development

---

## 📚 DOCUMENTATION STANDARDS
- **Target Audience:** AI agents unless directed otherwise
- **Optimization:** Structure for AI agent comprehension and action
- **Clarity:** Use clear, actionable language
- **Completeness:** Provide sufficient context for autonomous operation
- **Terminal Focus:** Document appropriate terminal for each task type

---

## ⚠️ KNOWN ISSUES & BEST PRACTICES

### Git Bash Issues
- **Path Handling:** Git operations work best with Unix-style paths
- **Integration:** Native git integration prevents command conflicts
- **Process Management:** Limited Windows process management capabilities

### PowerShell Issues
- **Path Separators:** May need forward slashes for PHP commands
- **Git Integration:** Can have issues with git command integration
- **Process Management:** Excellent Windows process management

### Best Practices
- **Git Operations:** Always use Git Bash for reliability
- **Server Management:** Use PowerShell on Windows for better process control
- **Development Tasks:** Choose terminal based on specific task requirements
- **Documentation:** Always specify which terminal for which task
- **Testing:** Use PowerShell for Windows-specific diagnostics

---

*These optimized rules provide comprehensive guidance for AI agents working with this PHP project, emphasizing context-based terminal usage, automation-friendly procedures, safety measures, and MVP development principles.*