# Admin Panel Functionality Overview

## 🎯 **Complete Feature List**

The admin panel provides comprehensive management tools for the Last War 1586 server. All pages now use shared header/footer components for consistency.

## ⚔️ **Alliance Management**

### **Alliance Power Editor** (`alliances_power.php`)
- **Purpose**: Bulk edit alliance power values
- **Features**:
  - Real-time power editing in table format
  - Add/delete alliances
  - Bulk save operations
  - Power statistics and totals
  - Role-based permissions (admin/power editor)
- **Access**: Admin and Power Editor roles

### **Alliance Editor** (`alliance_edit.php`)
- **Purpose**: Comprehensive alliance data editing
- **Features**:
  - Edit alliance names, tags, and member info
  - R5 name management
  - UID (Game ID) editing
  - Rule signing capabilities
  - Version-controlled rule amendments
- **Access**: R4+ users (limited), R5 users (full access)

### **Alliance Members API** (`allies_api.php`)
- **Purpose**: Manage alliance member data
- **Features**:
  - Add/edit/remove alliance members
  - Member role management
  - Bulk member operations
- **Access**: Admin only

## 👥 **User Management**

### **User Management** (`admin_api.php`)
- **Purpose**: Complete user account management
- **Features**:
  - Create/edit/delete user accounts
  - Role assignment (admin, power editor, R5, R4, etc.)
  - Password management
  - User status control
- **Access**: Admin only

### **Magic Link Generation** (`generate_magic_link.php`)
- **Purpose**: Generate secure authentication links
- **Features**:
  - Create time-limited login links
  - Email integration
  - Secure token generation
  - Link expiration management
- **Access**: Admin only

### **Send Magic Links** (`send_magic_link.php`)
- **Purpose**: Email magic links to users
- **Features**:
  - Email delivery system
  - Template management
  - Delivery tracking
- **Access**: Admin only

## 🛡️ **Security & Authentication**

### **JWT Key Rotation** (`key_rotation_admin_panel.php`)
- **Purpose**: Manage JWT secret key rotation
- **Features**:
  - Manual key rotation
  - Emergency rotation
  - Rotation history
  - Grace period management
  - Security incident response
- **Access**: Admin only

### **Security Monitor** (`security_monitor.php`)
- **Purpose**: Real-time security monitoring
- **Features**:
  - Failed login attempts
  - Suspicious activity detection
  - IP blocking
  - Rate limiting
  - Security alerts
- **Access**: Admin only

### **Multi-Factor Authentication** (`mfa_system.php`)
- **Purpose**: MFA setup and management
- **Features**:
  - TOTP configuration
  - Backup codes
  - Device management
  - MFA enforcement policies
- **Access**: Admin only

## 📊 **System Administration**

### **Audit Log Viewer** (`audit_log_viewer.php`)
- **Purpose**: View and analyze system activity
- **Features**:
  - Real-time log viewing
  - Advanced filtering
  - Export capabilities
  - Search functionality
  - Activity timeline
- **Access**: Admin only

### **Backup & Restore** (`backup_restore.php`)
- **Purpose**: Data backup and recovery
- **Features**:
  - Automatic backup scheduling
  - Manual backup creation
  - Data restoration
  - Backup verification
  - Storage management
- **Access**: Admin only

### **System Dependencies** (`test_dependencies.php`)
- **Purpose**: System health checking
- **Features**:
  - Dependency verification
  - Configuration validation
  - Performance testing
  - Error diagnostics
- **Access**: Admin only

## 🔧 **Development & Testing**

### **Alliance API Testing** (`test_alliances_api.php`)
- **Purpose**: Test alliance API endpoints
- **Features**:
  - API endpoint testing
  - Response validation
  - Performance benchmarking
  - Error simulation
- **Access**: Admin only

### **Audit System Testing** (`test_audit_init.php`)
- **Purpose**: Test audit logging system
- **Features**:
  - Audit system validation
  - Log integrity testing
  - Performance testing
- **Access**: Admin only

### **Audit Log Repair** (`fix_audit_log.php`)
- **Purpose**: Repair corrupted audit logs
- **Features**:
  - Log corruption detection
  - Automatic repair
  - Data recovery
  - Integrity verification
- **Access**: Admin only

## 📧 **Communication & Integration**

### **OAuth Callback** (`callback.php`)
- **Purpose**: Handle OAuth authentication
- **Features**:
  - OAuth flow completion
  - Token exchange
  - User profile integration
  - Error handling
- **Access**: Public (OAuth flow)

### **Enhanced Callback** (`enhanced_callback_with_key_rotation.php`)
- **Purpose**: OAuth with key rotation support
- **Features**:
  - Enhanced security
  - Automatic key rotation
  - Session management
- **Access**: Public (OAuth flow)

## 🔄 **Automated Systems**

### **Cron Jobs**
- **Key Rotation** (`cron_key_rotation.php`): Automated JWT key rotation
- **Token Cleanup** (`cron_token_cleanup.php`): Remove expired tokens
- **Main Cron** (`cron.php`): Orchestrates all scheduled tasks

### **Background Services**
- **Token Rotation** (`token_rotation.php`): Real-time token management
- **Secret Key Rotation** (`secret_key_rotation.php`): Key lifecycle management

## 📋 **Configuration & Setup**

### **Configuration Files**
- **Main Config** (`config.php`): Core system configuration
- **Environment** (`.env`): Environment-specific settings
- **Composer** (`composer.json`): PHP dependencies

### **Data Files**
- **Users** (`users.json`): User account data
- **Audit Logs** (`audit_log.json`): System activity logs
- **Secret Keys** (`secret_keys.json`): JWT key storage
- **Token Blacklist** (`token_blacklist.json`): Revoked tokens

## 🎨 **User Interface**

### **Shared Components**
- **Header** (`includes/header.php`): Navigation and authentication
- **Footer** (`includes/footer.php`): System status and quick actions
- **Styling**: Consistent, responsive design across all pages

### **Navigation Structure**
```
Dashboard
├── Alliance Management
│   ├── Alliance Power Editor
│   ├── Alliance Editor
│   └── Alliance Members
├── User Management
│   ├── User Accounts
│   ├── Magic Links
│   └── Send Links
├── Security & Monitoring
│   ├── JWT Key Rotation
│   ├── Security Monitor
│   └── MFA Settings
├── System Administration
│   ├── Audit Logs
│   ├── Backup & Restore
│   └── System Check
└── Development Tools
    ├── API Testing
    ├── Audit Testing
    └── Log Repair
```

## 🔐 **Access Control**

### **Role Hierarchy**
1. **Admin**: Full system access
2. **Power Editor**: Alliance power editing + viewing
3. **R5**: Alliance management for their alliance
4. **R4**: Limited alliance editing
5. **Member**: Read-only access (if any)

### **Permission Matrix**
| Feature | Admin | Power Editor | R5 | R4 | Member |
|---------|-------|--------------|----|----|--------|
| Alliance Power Edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| Alliance Delete | ✅ | ❌ | ❌ | ❌ | ❌ |
| User Management | ✅ | ❌ | ❌ | ❌ | ❌ |
| Security Settings | ✅ | ❌ | ❌ | ❌ | ❌ |
| Alliance Edit | ✅ | ❌ | ✅ | 🔸 | ❌ |
| Rule Signing | ✅ | ❌ | ✅ | ❌ | ❌ |

🔸 = Limited access (cannot edit alliance name or R5 name)

## 📈 **System Statistics**

The dashboard displays real-time statistics:
- **Total Users**: Count from users.json
- **Total Alliances**: Count from alliances.json  
- **Security Events**: 24-hour audit log count
- **Last Backup**: Most recent backup timestamp

## 🚀 **Getting Started**

1. **Login**: Use the login page with admin credentials
2. **Dashboard**: Overview of all system functions
3. **Navigation**: Use the header menu to access features
4. **Quick Actions**: Footer provides rapid access to common tasks

## 📚 **Documentation**

- **Setup Guides**: Various setup and configuration documents
- **API Documentation**: Endpoint specifications and examples
- **Security Guides**: Security implementation and best practices
- **Deployment**: Production deployment instructions

This admin panel provides enterprise-level functionality for managing the Last War 1586 server with comprehensive security, user management, and alliance administration capabilities.