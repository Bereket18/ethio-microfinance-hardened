# 🔒 ETHIO MICROFINANCE - COMPLETE SECURITY ASSESSMENT & COMPETITION PLAN

**Assessment Date:** September 26, 2026  
**Security Analyst:** Senior Cybersecurity Team  
**Competition Status:** READY FOR ATTACK/DEFENSE

---

## 📋 EXECUTIVE SUMMARY

This is a **PHP-based microfinance web application** that has already undergone significant security hardening (see CHANGELOG.md). However, for competition purposes, we need to:

1. **Verify all fixes are actually effective** (attackers will test them)
2. **Find any remaining vulnerabilities** the original audit missed
3. **Prepare offensive methodologies** to attack other teams
4. **Set up robust logging and monitoring** for defense
5. **Create competition playbooks** for attack and defense phases

### 🎯 Competition Context

- **Format:** Attack/Defense CTF-style
- **Network:** Shared classroom LAN, teams share IP addresses
- **Your Role:** Both attacker (against other teams) and defender (protect your system)
- **Deployment:** Docker-based (XAMPP stack with nginx, PHP-FPM, MariaDB)
- **Goal:** Maximum exploitation of opponents + maximum defense of your own system

---

## 🏗️ PROJECT ARCHITECTURE

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Frontend** | HTML5, Bootstrap 5.3.2, Vanilla JS | Modern |
| **Backend** | PHP | 8.3 (FPM) |
| **Database** | MariaDB | 11 |
| **Web Server** | Nginx | 1.27-alpine |
| **Container Runtime** | Docker Compose | v2+ |
| **Session Management** | PHP Sessions | Native |
| **Authentication** | Custom PHP | bcrypt passwords |

### Application Structure

```
ethio_microfinance/
├── config/              # Database & session configuration
│   ├── database.php     # DB connection with environment variables
│   └── session.php      # Session security settings
├── includes/            # Shared security functions
│   ├── auth.php         # Authentication & authorization
│   ├── functions.php    # Business logic helpers
│   ├── validation.php   # CSRF token validation
│   └── security_log.php # Logging & rate limiting
├── modules/             # Application features
│   ├── users/           # Login, register, profile
│   ├── loans/           # Apply, approve, repay, dashboard
│   └── accounts/        # Deposit, withdraw, create
├── admin/               # Admin panel
│   ├── dashboard.php    # Admin overview
│   └── manage_users.php # User management
├── docker/              # Container configurations
│   ├── nginx/           # Reverse proxy config
│   ├── php/             # PHP hardening
│   └── mysql/           # DB privilege restrictions
└── uploads/             # User file uploads (images)
```

### Network Architecture

```
INTERNET/LAN
      ↓
[nginx:8080] ← ONLY EXPOSED PORT
      ↓
 (internal network)
      ↓
[PHP-FPM:9000] ← FastCGI only
      ↓
 (internal network)
      ↓
[MariaDB:3306] ← Database only
```

**Key Security Features:**
- Only nginx port (8080) is published to LAN
- Database is on internal-only Docker network
- PHP-FPM runs as non-root user (uid 1000)
- Read-only root filesystem on containers
- Resource limits on all services

---

## 🎯 ATTACK SURFACE ANALYSIS

### External Attack Surface (Accessible from Competition LAN)

| Component | Port | Protocol | Risk Level | Notes |
|-----------|------|----------|------------|-------|
| Nginx Web Server | 8080 | HTTP | **HIGH** | Main attack vector |
| TLS (if enabled) | 8443 | HTTPS | **HIGH** | Not yet implemented |
| Database | 3306 | MySQL | **NONE** | Should NOT be reachable |
| PHP-FPM | 9000 | FastCGI | **NONE** | Internal only |

### Application Attack Surface

| Feature | Endpoint | Authentication | Risk Level | Attack Vectors |
|---------|----------|----------------|------------|----------------|
| User Login | `/modules/users/login.php` | None (public) | **CRITICAL** | Brute force, SQLi, credential stuffing |
| User Registration | `/modules/users/register.php` | None (public) | **HIGH** | Privilege escalation, SQLi, mass account creation |
| Admin Login | `/modules/users/admin_login.php` | None (public) | **CRITICAL** | Brute force, default credentials |
| User Profile | `/modules/users/profile.php` | Required | **HIGH** | IDOR, file upload, XSS |
| Loan Application | `/modules/loans/apply.php` | Required | **MEDIUM** | Business logic, SQLi |
| Loan Approval | `/modules/loans/approve.php` | Admin only | **CRITICAL** | Privilege escalation, CSRF |
| Loan Repayment | `/modules/loans/repay.php` | Required | **HIGH** | IDOR, logic flaws |
| Account Deposit | `/modules/accounts/deposit.php` | Required | **MEDIUM** | Race conditions, logic flaws |
| Account Withdraw | `/modules/accounts/withdraw.php` | Required | **HIGH** | Logic flaws, integer overflow |
| Admin Dashboard | `/admin/dashboard.php` | Admin only | **CRITICAL** | Backdoors, XSS, CSRF |
| User Management | `/admin/manage_users.php` | Admin only | **CRITICAL** | Mass privilege escalation |

### Data Flow Risk Points

1. **User Input → Database**
   - All POST parameters
   - GET query strings
   - File uploads
   - Cookie values

2. **Database → User Output**
   - User data display
   - Transaction history
   - Error messages
   - Admin panels

3. **Session Management**
   - Session fixation
   - Session hijacking
   - Concurrent sessions
   - Session timeout

---

## 🔍 KNOWN FIXES (Already Applied - MUST VERIFY)

According to CHANGELOG.md, these vulnerabilities were supposedly fixed:

### ✅ Authentication Fixes
- ❌ `?bypass=true` admin backdoor (removed from auth.php)
- ❌ `?force=true` admin backdoor in loans/approve.php (removed)
- ✅ Plaintext passwords replaced with bcrypt
- ✅ Session regeneration on login
- ✅ Rate limiting (5 failed attempts / 5 minutes)

### ✅ Authorization Fixes
- ✅ IDOR in profile.php (ownership checks added)
- ✅ IDOR in loans/repay.php (ownership checks added)
- ✅ Privilege escalation in register.php (role hardcoded server-side)

### ✅ Injection Fixes
- ✅ All SQL queries converted to prepared statements
- ✅ eval() removed from interest calculation
- ✅ system() command execution removed
- ✅ unserialize() removed

### ✅ File Upload Fixes
- ✅ File type validation via getimagesize()
- ✅ Random filename generation
- ✅ 2MB size limit
- ✅ CSRF token required

### ✅ Infrastructure Fixes
- ✅ Database credentials in environment variables
- ✅ Non-root container users
- ✅ Read-only root filesystem
- ✅ Network segmentation (internal-only DB)
- ✅ Nginx rate limiting (10 req/s general, 2 req/s login)
- ✅ Resource limits on all containers

### ⚠️ CRITICAL: We MUST verify these fixes actually work!
**Attackers will test every single one of these. If any fix is incomplete or bypassable, you WILL be exploited.**

---

## 🚨 REMAINING VULNERABILITIES TO INVESTIGATE

Even with the extensive hardening, these areas need deep investigation:

### 1. Session Security

**Potential Issues:**
- Are sessions properly invalidated on logout?
- Can sessions be hijacked via XSS?
- Is there a maximum session lifetime?
- Can multiple devices use the same session?
- Session fixation still possible?

**Test Commands:**
```bash
# Check session cookie security
curl -I http://target:8080/modules/users/login.php

# Look for session fixation
curl -c cookies.txt http://target:8080/modules/users/login.php
# Check if PHPSESSID in cookies.txt is accepted before login
```

### 2. CSRF Token Implementation

**Potential Issues:**
- Is the CSRF token actually validated on ALL state-changing operations?
- Can tokens be reused?
- Are tokens properly random?
- Token generation timing attacks?

**Files to Audit:**
- `includes/validation.php` - token generation/validation
- All forms in modules/ - token presence

### 3. Race Conditions

**High-Risk Areas:**
- Withdraw operation (check balance → update balance)
- Loan approval (check status → update status)
- Account creation (check exists → insert)

**Test Approach:**
```bash
# Send multiple simultaneous withdrawal requests
for i in {1..50}; do
  curl -X POST http://target:8080/modules/accounts/withdraw.php \
    -d "amount=1000&csrf_token=XXX" \
    --cookie "PHPSESSID=YYY" &
done
```

### 4. Business Logic Flaws

**Questions:**
- Can you apply for negative loan amounts?
- Can you repay negative amounts (adding money)?
- Can you deposit then immediately withdraw in a loop?
- Interest calculation edge cases (negative rates, huge amounts)?
- Can you delete your own admin account?

### 5. Information Disclosure

**Check For:**
- phpinfo() pages
- .git directory accessible?
- Error messages revealing paths/database structure
- Timing attacks on login (different response times for valid vs invalid users)
- Username enumeration via registration
- Comments in source code with credentials/TODOs

**Test Commands:**
```bash
curl http://target:8080/.git/config
curl http://target:8080/phpinfo.php
curl http://target:8080/config/database.php
curl http://target:8080/docker-compose.yml
```

### 6. Rate Limiting Bypass

**Test Approaches:**
- Send requests from multiple IPs (if possible)
- Rotate User-Agent headers
- Use different usernames (is it per-IP or per-username?)
- Distributed attack from multiple competition machines

### 7. File Upload Residual Risks

**Even with validation, check:**
- Double extension bypass: `shell.php.jpg`
- MIME type confusion
- SVG with embedded JavaScript
- Image files with EXIF command injection
- Path traversal in uploaded filename handling
- Uploaded files accessible directly via web?

### 8. Docker/Infrastructure

**Verify:**
- Is port 3306 actually unreachable from LAN?
- Can you escape the Docker container?
- Environment variable leakage via /proc
- Mounted volumes with sensitive data

### 9. Dependency Vulnerabilities

**External Resources:**
- Bootstrap 5.3.2 CDN
- Any outdated PHP extensions?
- MariaDB version vulnerabilities
- Nginx version vulnerabilities

### 10. Password Reset (Not Implemented = Potential Addition by Other Teams)

**Watch For:**
- Teams might add password reset functionality with vulnerabilities
- Token prediction
- Account takeover via email parameter tampering

---

## 🛠️ TOOLS YOU NEED TO INSTALL

### STEP 1: Verify Docker Installation

```bash
# Check Docker
docker --version
docker compose version

# Expected output:
# Docker version 24.x or higher
# Docker Compose version v2.x or higher
```

**If not installed:**
- Windows: Download Docker Desktop from https://www.docker.com/products/docker-desktop
- Linux: `sudo apt install docker.io docker-compose-plugin`

### STEP 2: Install Security Testing Tools

#### Core Tools (ESSENTIAL)

```bash
# 1. Nmap - Network scanner
sudo apt install nmap     # Linux
# Windows: Download from https://nmap.org/download.html

# Verify
nmap --version

# 2. Burp Suite Community Edition
# Download from: https://portswigger.net/burp/communitydownload
# This is your PRIMARY web attack tool

# 3. cURL - HTTP client
# Already installed on most systems
curl --version

# 4. Git
git --version
```

#### Web Application Testing

```bash
# 5. OWASP ZAP (Alternative to Burp)
# Download from: https://www.zaproxy.org/download/
# Useful for automated scanning

# 6. Nikto - Web server scanner
sudo apt install nikto
# Windows: Download from https://github.com/sullo/nikto

# 7. sqlmap - Automated SQL injection testing
sudo apt install sqlmap
# Or: git clone https://github.com/sqlmapproject/sqlmap.git

# 8. ffuf - Web fuzzer
go install github.com/ffuf/ffuf@latest
# Or download binary from https://github.com/ffuf/ffuf/releases
```

#### Password & Brute Force Tools

```bash
# 9. hydra - Login brute force
sudo apt install hydra

# 10. John the Ripper - Password cracker
sudo apt install john

# 11. hashcat - Advanced password cracking
sudo apt install hashcat
```

#### Traffic Analysis

```bash
# 12. Wireshark - Network protocol analyzer
sudo apt install wireshark
# Windows: Download from https://www.wireshark.org/

# 13. tcpdump - Command-line packet capture
sudo apt install tcpdump
```

#### Static Code Analysis

```bash
# 14. Semgrep - Static analysis for security
pip3 install semgrep

# 15. PHPCS (PHP Code Sniffer) - For PHP security checks
composer global require "squizlabs/php_codesniffer=*"
```

#### Container Security

```bash
# 16. Trivy - Container vulnerability scanner
# Install: https://aquasecurity.github.io/trivy/latest/getting-started/installation/

# 17. Docker Bench Security
git clone https://github.com/docker/docker-bench-security.git
```

#### Secret Scanning

```bash
# 18. Gitleaks - Find secrets in git repos
brew install gitleaks  # Mac
# Or: https://github.com/gitleaks/gitleaks/releases

# 19. TruffleHog - Secret scanner
pip3 install truffleHog
```

#### Useful Utilities

```bash
# 20. jq - JSON processor (for API testing)
sudo apt install jq

# 21. netcat - Network utility
sudo apt install netcat

# 22. gobuster - Directory/file brute forcer
go install github.com/OJ/gobuster/v3@latest
```

---

## 📝 TOOL INSTALLATION VERIFICATION CHECKLIST

Run this script to verify all tools:

```bash
#!/bin/bash
echo "=== SECURITY TOOLS VERIFICATION ==="
echo ""

tools=("docker" "nmap" "curl" "git" "sqlmap" "hydra" "wireshark" "tcpdump")

for tool in "${tools[@]}"; do
    if command -v $tool &> /dev/null; then
        echo "✅ $tool: $(command -v $tool)"
    else
        echo "❌ $tool: NOT FOUND"
    fi
done
```

---

## 🎯 NEXT STEPS

### Immediate Actions (Do This First):

1. **Run your Docker environment**
   ```bash
   cd /path/to/ethio_microfinance_hardened/ethio_microfinance
   cp .env.example .env
   # Edit .env and set strong passwords
   docker compose up -d --build
   docker compose exec app php migrate_hash_passwords.php
   ```

2. **Verify system is running**
   ```bash
   docker compose ps
   # All services should show "healthy"
   ```

3. **Test basic login**
   - Visit http://localhost:8080/modules/users/login.php
   - Try: `admin / admin123`
   - Should successfully log in

4. **Baseline testing** (Document BEFORE competition)
   - What endpoints exist?
   - What are normal response times?
   - What do error messages look like?

### Before Competition Day:

5. **Run complete security audit** (we'll do this together)
6. **Set up logging and monitoring**
7. **Create attack scripts** for common vulnerabilities
8. **Test defenses** against known attack patterns
9. **Prepare incident response procedures**
10. **Document everything** for quick reference during competition

---

## 📊 COMPETITION STRATEGY

### Phase 1: Pre-Competition (NOW)
- Understand every line of your code
- Test every fix claimed in CHANGELOG.md
- Find vulnerabilities OTHER teams will have (if they didn't fix them)
- Prepare exploit scripts

### Phase 2: Competition Start (First 30 Minutes)
- Get your system online and verify health
- Start logging
- Perform quick reconnaissance on other teams
- Deploy any last-minute hardening

### Phase 3: Attack Phase
- Target low-hanging fruit first (default credentials, known bypasses)
- Escalate to complex attacks
- Document every finding for points

### Phase 4: Defense Phase
- Monitor logs constantly
- Respond to attacks in real-time
- Patch newly-discovered vulnerabilities
- Counter-attack if allowed

---

## ❓ QUESTIONS FOR YOU

Before we proceed deeper, I need to know:

1. **Have you successfully run the Docker environment yet?**
   - Can you access http://localhost:8080 right now?

2. **What operating system are you using?**
   - Windows, Linux, or Mac?

3. **Which tools from the list above do you already have installed?**

4. **When is your competition scheduled?**
   - How much time do we have to prepare?

5. **Competition rules:**
   - Are you allowed to take down other teams' systems completely?
   - Or is it "find and exploit but keep services running"?
   - What counts as points (successful exploit, flags captured, service uptime)?

**Please answer these questions, and then we'll move to the next phase: hands-on security testing and vulnerability discovery.**

Your immediate next command should be:
```bash
cd C:\Users\berek\Downloads\ethio_microfinance_hardened\ethio_microfinance
docker compose up -d --build
```

Let me know when you've done this and I'll guide you through the complete verification and attack preparation process step by step! 🎯
