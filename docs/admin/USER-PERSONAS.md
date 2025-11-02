# User Personas and Role-Based Access Control

**Version:** 1.0.0
**Date:** October 16, 2025
**Last Updated:** October 16, 2025

## Table of Contents

1. [Overview](#overview)
2. [Role Hierarchy](#role-hierarchy)
3. [Persona Definitions](#persona-definitions)
4. [Permission Matrix](#permission-matrix)
5. [Use Cases](#use-cases)
6. [Testing Scenarios](#testing-scenarios)

---

## Overview

The Last War 1586 Admin System implements a role-based access control (RBAC) system with five distinct user personas. Each persona has specific permissions and capabilities designed for their operational needs.

### Core Concepts

- **Role**: Primary permission level (admin, r5, r4)
- **Alliance Access**: Which alliances a user can view/edit
- **Power Editor (APE)**: Special flag granting alliance power editing rights
- **Wildcard Access (*)**: Access to all alliances

---

## Role Hierarchy

```
┌─────────────────────────────────────────────────┐
│                    ADMIN                         │
│  • Full system access                           │
│  • User management                              │
│  • Security controls                            │
│  • All alliances (*)                            │
│  • Always has APE access                        │
└─────────────────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
┌───────▼────────┐         ┌────────▼──────┐
│  R5 (Leader)   │         │  R5 + APE     │
│  • Rule signing │         │  • Rule sign  │
│  • Alliance edit│         │  • Power edit │
│  • View only    │         │  • Bulk ops   │
└────────────────┘         └───────────────┘
        │                           │
        └─────────────┬─────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
┌───────▼────────┐         ┌────────▼──────┐
│  R4 (Officer)  │         │  R4 + APE     │
│  • Alliance edit│         │  • Power edit │
│  • No sign      │         │  • Bulk ops   │
│  • View/update  │         │  • No sign    │
└────────────────┘         └───────────────┘
```

---

## Persona Definitions

### Persona 1: Administrator (Admin)

**Identifier:** `admin`
**Role Code:** `admin`
**Alliance Access:** `["*"]` (all alliances)
**Power Editor:** Implicit (always true)

#### Purpose
System administrators with full control over the admin panel. Responsible for user management, security monitoring, system configuration, and emergency operations.

#### Key Responsibilities
- Create, edit, and delete user accounts
- Manage alliance data (including power values)
- Access security monitoring and audit logs
- Perform secret key rotations
- Generate test tokens for automation
- Backup and restore system data
- Monitor system health

#### Typical Users
- System administrators
- DevOps engineers
- Security officers
- Project maintainers

#### Access Rights
```php
✅ Dashboard
✅ User Management (full CRUD)
✅ Alliance Power Editor (APE)
✅ Alliance Edit (all alliances)
✅ Rule Signing (all alliances)
✅ Security Monitor
✅ Security Audit Logs
✅ Security Key Rotation
✅ Backup & Restore
✅ Test Token Generation
✅ Magic Link Generation
```

#### Token Example
```json
{
  "sub": "admin@example.com",
  "aud": "admin",
  "alliances": ["*"],
  "powereditor": true,
  "jti": "unique-jwt-id",
  "exp": 1234567890,
  "iat": 1234567890
}
```

---

### Persona 2: Alliance Leader with Power Editor (R5+APE)

**Identifier:** `r5+ape`
**Role Code:** `r5`
**Alliance Access:** Specific alliances or `["*"]`
**Power Editor:** `true`

#### Purpose
Senior alliance leaders entrusted with both governance (rule signing) and operational data management (power editing). These users have elevated privileges beyond standard R5 roles.

#### Key Responsibilities
- Edit alliance information for assigned alliances
- Sign server rules and amendments for their alliance
- Update alliance power values in bulk
- Manage alliance rosters and data
- Coordinate with other alliances

#### Typical Users
- Trusted alliance leaders
- Coalition coordinators
- Long-term veteran leaders
- Data managers

#### Access Rights
```php
✅ Dashboard
✅ Alliance Power Editor (APE) - assigned alliances
✅ Alliance Edit - assigned alliances
✅ Rule Signing - assigned alliances
❌ User Management
❌ Security Monitor
❌ System Administration
```

#### Token Example
```json
{
  "sub": "leader@alliance.com",
  "aud": "r5",
  "alliances": ["UvvU", "1984"],
  "powereditor": true,
  "jti": "unique-jwt-id",
  "exp": 1234567890,
  "iat": 1234567890
}
```

---

### Persona 3: Alliance Leader (R5)

**Identifier:** `r5`
**Role Code:** `r5`
**Alliance Access:** Specific alliances
**Power Editor:** `false`

#### Purpose
Standard alliance leaders with governance responsibilities. Can edit alliance information and sign server rules but cannot modify power values.

#### Key Responsibilities
- Edit alliance information (name, R5 name, contact info)
- Sign server rules and amendments
- Update alliance metadata
- Communicate with admins

#### Typical Users
- Alliance leaders (R5)
- Alliance representatives
- Rule signatories

#### Access Rights
```php
✅ Dashboard
✅ Alliance Edit - assigned alliances
✅ Rule Signing - assigned alliances
❌ Alliance Power Editor (APE)
❌ User Management
❌ Security Monitor
```

#### Token Example
```json
{
  "sub": "r5@alliance.com",
  "aud": "r5",
  "alliances": ["K44"],
  "powereditor": false,
  "jti": "unique-jwt-id",
  "exp": 1234567890,
  "iat": 1234567890
}
```

---

### Persona 4: Alliance Officer with Power Editor (R4+APE)

**Identifier:** `r4+ape`
**Role Code:** `r4`
**Alliance Access:** Specific alliances
**Power Editor:** `true`

#### Purpose
Trusted alliance officers granted special power editing privileges. Can manage alliance data including power values but cannot sign rules.

#### Key Responsibilities
- Edit alliance information
- Update alliance power values
- Manage alliance operational data
- Support R5 with data management

#### Typical Users
- Senior alliance officers (R4)
- Data coordinators
- Trusted lieutenants
- Power tracking managers

#### Access Rights
```php
✅ Dashboard
✅ Alliance Power Editor (APE) - assigned alliances
✅ Alliance Edit - assigned alliances
❌ Rule Signing
❌ User Management
❌ Security Monitor
```

#### Token Example
```json
{
  "sub": "officer@alliance.com",
  "aud": "r4",
  "alliances": ["MTOP"],
  "powereditor": true,
  "jti": "unique-jwt-id",
  "exp": 1234567890,
  "iat": 1234567890
}
```

---

### Persona 5: Alliance Officer (R4)

**Identifier:** `r4`
**Role Code:** `r4`
**Alliance Access:** Specific alliances
**Power Editor:** `false`

#### Purpose
Standard alliance officers with basic editing capabilities. Can update alliance information but cannot edit power values or sign rules.

#### Key Responsibilities
- Edit alliance information (limited fields)
- Update alliance metadata
- View alliance data
- Maintain current information

#### Typical Users
- Alliance officers (R4)
- Information managers
- Alliance coordinators

#### Access Rights
```php
✅ Dashboard
✅ Alliance Edit - assigned alliances (limited)
❌ Alliance Power Editor (APE)
❌ Rule Signing
❌ User Management
❌ Security Monitor
```

#### Token Example
```json
{
  "sub": "r4@alliance.com",
  "aud": "r4",
  "alliances": ["STR8"],
  "powereditor": false,
  "jti": "unique-jwt-id",
  "exp": 1234567890,
  "iat": 1234567890
}
```

---

## Permission Matrix

| Feature/Action | Admin | R5+APE | R5 | R4+APE | R4 |
|---|---|---|---|---|---|
| **Dashboard Access** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **View Alliance Data** | ✅ All | ✅ Assigned | ✅ Assigned | ✅ Assigned | ✅ Assigned |
| **Edit Alliance Info** | ✅ All | ✅ Assigned | ✅ Assigned | ✅ Assigned | ✅ Assigned |
| **Edit Alliance Power** | ✅ All | ✅ Assigned | ❌ | ✅ Assigned | ❌ |
| **Delete Alliances** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Sign Server Rules** | ✅ | ✅ Assigned | ✅ Assigned | ❌ | ❌ |
| **User Management** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Create Users** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Edit Users** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Delete Users** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Security Monitor** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **View Audit Logs** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Security Key Rotation** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Backup & Restore** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Generate Test Tokens** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Generate Magic Links** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Power Editor (APE) Access** | ✅ | ✅ | ❌ | ✅ | ❌ |

---

## Use Cases

### Use Case 1: Admin Managing System
**Persona:** Administrator (Admin)

**Scenario:** System admin needs to add a new R5 user and grant APE access.

**Steps:**
1. Login with admin credentials
2. Navigate to User Management
3. Click "Add New User"
4. Fill form:
   - Email: newleader@alliance.com
   - Role: R5
   - Alliances: UvvU
   - Power Editor: ✓ Enabled
5. Generate magic link
6. Send link to user

**Expected Outcome:** New R5+APE user created, can access APE and sign rules for UvvU.

---

### Use Case 2: R5 Signing Server Rules
**Persona:** Alliance Leader (R5)

**Scenario:** R5 needs to sign server rules for their alliance.

**Steps:**
1. Login with R5 credentials
2. Navigate to Rules page
3. Review current server rules
4. Click "Sign Rules for [Alliance]"
5. Confirm signature

**Expected Outcome:** Alliance signature recorded in system.

**Access Control:**
- ✅ Can sign for assigned alliances
- ❌ Cannot sign for other alliances
- ❌ Cannot access user management

---

### Use Case 3: R4+APE Updating Power Values
**Persona:** Alliance Officer with Power Editor (R4+APE)

**Scenario:** R4+APE needs to update alliance power after battle.

**Steps:**
1. Login with R4+APE credentials
2. Navigate to Alliance Power Editor
3. View assigned alliances only
4. Update power value for alliance
5. Save changes

**Expected Outcome:** Power value updated, changes logged.

**Access Control:**
- ✅ Can edit power for assigned alliances
- ❌ Cannot delete alliances
- ❌ Cannot sign rules
- ❌ Cannot access other alliances

---

### Use Case 4: R4 Editing Alliance Info
**Persona:** Alliance Officer (R4)

**Scenario:** R4 needs to update alliance contact information.

**Steps:**
1. Login with R4 credentials
2. Navigate to Alliance Edit
3. View assigned alliance
4. Edit contact info, description
5. Attempt to edit power value → ❌ Denied

**Expected Outcome:** Metadata updated, power field disabled/hidden.

**Access Control:**
- ✅ Can edit basic alliance info
- ❌ Cannot edit power values
- ❌ Cannot sign rules

---

## Testing Scenarios

### Authentication Tests

#### Test 1.1: Admin Login
```
GIVEN: Valid admin credentials
WHEN: User logs in
THEN:
  - Redirected to dashboard
  - Token has aud: "admin"
  - Token has alliances: ["*"]
  - Token has powereditor: true (implicit)
```

#### Test 1.2: R5+APE Login
```
GIVEN: Valid R5+APE credentials for ["UvvU"]
WHEN: User logs in
THEN:
  - Redirected to dashboard
  - Token has aud: "r5"
  - Token has alliances: ["UvvU"]
  - Token has powereditor: true
```

#### Test 1.3: R4 Login (No APE)
```
GIVEN: Valid R4 credentials for ["K44"]
WHEN: User logs in
THEN:
  - Redirected to dashboard
  - Token has aud: "r4"
  - Token has alliances: ["K44"]
  - Token has powereditor: false
```

---

### Authorization Tests

#### Test 2.1: Admin Access All Pages
```
GIVEN: Logged in as admin
WHEN: Accessing each protected page
THEN: All pages return HTTP 200
  - dashboard.php
  - user_management.php
  - alliances_power.php
  - alliance_edit.php
  - security_monitor.php
  - security_audit.php
  - security_keys.php
  - security_backups.php
  - generate_test_token.php
```

#### Test 2.2: R5 Restricted Access
```
GIVEN: Logged in as R5 (no APE)
WHEN: Accessing protected pages
THEN:
  - dashboard.php → 200 OK
  - alliance_edit.php → 200 OK (assigned alliances only)
  - alliances_power.php → 403 Forbidden
  - user_management.php → 403 Forbidden
  - security_monitor.php → 403 Forbidden
```

#### Test 2.3: R4+APE APE Access
```
GIVEN: Logged in as R4+APE for ["MTOP"]
WHEN: Accessing alliance power editor
THEN:
  - alliances_power.php → 200 OK
  - Can see MTOP alliance
  - Cannot see other alliances
  - Can edit MTOP power
  - Cannot delete alliances
```

#### Test 2.4: R4 No APE Access
```
GIVEN: Logged in as R4 (no APE)
WHEN: Accessing alliance power editor
THEN:
  - alliances_power.php → 403 Forbidden
  - Redirected or access denied message
```

---

### Functional Tests

#### Test 3.1: Alliance Access Filtering
```
GIVEN: R5 with alliances: ["UvvU", "1984"]
WHEN: Loading alliance_edit.php
THEN:
  - Alliance list shows only UvvU and 1984
  - Other alliances not visible
  - Cannot edit other alliances via API
```

#### Test 3.2: Rule Signing Permissions
```
GIVEN: R5 with alliance ["K44"]
WHEN: Attempting to sign rules
THEN:
  - Can sign rules for K44
  - Cannot sign rules for other alliances
  - Signature recorded with timestamp
  - Audit log entry created
```

#### Test 3.3: Power Editor Field Visibility
```
GIVEN: R4 without APE
WHEN: Viewing alliance edit page
THEN:
  - Power input field is disabled or hidden
  - Cannot submit power value changes
  - API rejects power update attempts
```

#### Test 3.4: Admin Wildcard Access
```
GIVEN: Admin with alliances: ["*"]
WHEN: Loading any alliance page
THEN:
  - Can see all alliances
  - Can edit any alliance
  - Can perform admin operations
  - No filtering applied
```

---

### Security Tests

#### Test 4.1: Token Tampering Detection
```
GIVEN: Valid R4 token
WHEN: Token is modified to claim admin role
THEN:
  - Token signature validation fails
  - User is logged out
  - Redirected to login with error
```

#### Test 4.2: Alliance Access Bypass Attempt
```
GIVEN: R5 with alliance ["K44"]
WHEN: Directly accessing API for different alliance
  POST /alliance_api.php?alliance=UvvU
THEN:
  - Request rejected (403 Forbidden)
  - Audit log entry created
  - No data modified
```

#### Test 4.3: APE Flag Validation
```
GIVEN: R4 without APE flag
WHEN: Attempting to access APE functionality
THEN:
  - Access denied at page level
  - API calls rejected
  - Proper error message displayed
```

#### Test 4.4: Session Expiration
```
GIVEN: Valid session token
WHEN: Token expires (30 minutes)
THEN:
  - User logged out automatically
  - Session warning shown at 25 minutes
  - Redirect to login on next request
```

---

### Integration Tests

#### Test 5.1: User Creation Flow (Admin)
```
GIVEN: Admin logged in
WHEN: Creating new R5+APE user
THEN:
  - User added to users.json
  - Magic link generated
  - Email sent (if configured)
  - User can login with magic link
  - User has correct permissions
```

#### Test 5.2: Alliance Update Flow (R5)
```
GIVEN: R5 logged in with ["UvvU"]
WHEN: Updating alliance information
THEN:
  - Alliance data updated in alliances.json
  - Audit log entry created
  - Changes visible immediately
  - Other users see updated data
```

#### Test 5.3: Power Update Flow (R4+APE)
```
GIVEN: R4+APE logged in with ["MTOP"]
WHEN: Updating MTOP power value
THEN:
  - Power value updated in alliances.json
  - Rank recalculated automatically
  - History entry created
  - Audit log entry created
  - Changes reflected in frontend
```

---

## Test User Credentials

### Test Users Setup

Create these test users for comprehensive testing:

```json
{
  "users": [
    {
      "email": "admin.test@localhost.com",
      "role": "admin",
      "alliances": ["*"],
      "powereditor": false
    },
    {
      "email": "r5ape.test@localhost.com",
      "role": "r5",
      "alliances": ["UvvU", "1984"],
      "powereditor": true
    },
    {
      "email": "r5.test@localhost.com",
      "role": "r5",
      "alliances": ["K44"],
      "powereditor": false
    },
    {
      "email": "r4ape.test@localhost.com",
      "role": "r4",
      "alliances": ["MTOP"],
      "powereditor": true
    },
    {
      "email": "r4.test@localhost.com",
      "role": "r4",
      "alliances": ["STR8"],
      "powereditor": false
    }
  ]
}
```

---

## Conclusion

This document defines the five user personas for the Last War 1586 Admin System. Each persona has specific capabilities designed for their operational needs, with clear access controls enforced at multiple layers (token validation, page access, API calls, UI visibility).

### Key Takeaways

1. **Admin** - Full system control
2. **R5+APE** - Leadership + power management
3. **R5** - Leadership only
4. **R4+APE** - Operations + power management
5. **R4** - Basic operations

### Next Steps

- Implement automated unit tests for each persona
- Create test runner script
- Generate test reports
- Integrate with CI/CD pipeline

---

**Document Version:** 1.0.0
**Last Reviewed:** October 16, 2025
**Maintained By:** k33bz
