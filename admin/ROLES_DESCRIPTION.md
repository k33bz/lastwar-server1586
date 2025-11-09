# Server 1586 Website Admin Roles

## Role Hierarchy

The Server 1586 website uses a hierarchical role-based access control system:

```
Admin (Highest)
   ↓
President
   ↓
R5 (Alliance Leader)
   ↓
R4 (Alliance Officer)
   ↓
R3 (Member)
```

---

## 🔴 **ADMIN** (Super Administrator)

**Description:** Full administrative access to all website features. The highest level of access with complete control over the system.

**Who Gets This Role:** Server administrators and trusted technical managers only.

### Administrative Features (Admin Only)

**User Management:**
- ✅ Create and manage all user accounts
- ✅ Assign any role to users (Admin, President, R5, R4, R3)
- ✅ Manage user alliances and permissions
- ✅ View audit logs and user activity

**Alliance Management:**
- ✅ Full alliance editor with complete CRUD access
- ✅ Power editor for bulk alliance updates
- ✅ Alliance tag manager (categorization system)
- ✅ Discord channel configuration for all alliances

**Discord Configuration:**
- ✅ Configure Discord bot settings and webhooks
- ✅ Manage Discord server integration
- ✅ Configure global Discord channels
- ✅ Approve/reject global message templates
- ✅ Delete any user's scheduled or recurring messages

**Season 2 Configuration:**
- ✅ Configure Season 2 start date and settings
- ✅ Update event templates and calendar
- ✅ Modify alliance duel schedules
- ✅ Configure faction war times and server timezone

**Security & Maintenance:**
- ✅ Access backup and restore system
- ✅ Create manual backups
- ✅ Restore from backups
- ✅ View complete audit logs
- ✅ Access system diagnostics
- ✅ Modify protected configuration files

### Shared Features (Admin + Other Roles)

**Council Rotation:** (Admin + President)
- ✅ Regenerate council rotation schedule

**Discord Messaging:** (Admin + President + R5 + R4)
- ✅ Send instant Discord messages
- ✅ Schedule Discord messages
- ✅ Create recurring Discord messages
- ✅ Manage message templates
- ✅ Auto-delete configuration (1h, 6h, 12h, 24h, 48h)

**Season 2 Events:** (Admin + President + R5 + R4)
- ✅ View Season 2 event calendar
- ✅ Send event announcements
- ✅ Access event templates

---

## 🟡 **PRESIDENT** (Trusted Leadership)

**Description:** Elevated access for alliance leaders and trusted server representatives. Bridges the gap between R5 and Admin, providing important management capabilities without full administrative control.

**Who Gets This Role:** Top alliance leaders, server council representatives, and trusted coordinators.

### President-Exclusive Features

**Council Rotation Management:**
- ✅ **Regenerate rotation schedule** (shared with Admin)
- ✅ View current rotation status and statistics
- ✅ Recalculate future weeks when rankings change
- ✅ View fairness distribution
- ✅ Access rotation history

### Shared Features (President + R5 + R4)

**Discord Messaging:**
- ✅ Send instant Discord announcements
- ✅ Schedule future Discord messages
- ✅ Create recurring messages (daily, weekly, monthly)
- ✅ Auto-delete messages (1h, 6h, 12h, 24h, 48h)
- ✅ Create and manage message templates
- ✅ Submit templates for global approval
- ✅ Filter templates by season and event type

**Season 2 Event Management:**
- ✅ View complete event calendar (all 49 days)
- ✅ Send one-click event announcements
- ✅ Access event templates with variable replacement
- ✅ View current week/day status
- ✅ Filter events by week and importance

**Alliance Data:**
- ✅ View alliance information and statistics
- ✅ Access coordination tools
- ✅ View alliance rankings and power

### Restrictions (President Cannot)

- ❌ Create or delete user accounts
- ❌ Assign roles to other users
- ❌ Configure Discord bot settings
- ❌ Modify Season 2 configuration (start date, templates)
- ❌ Access backup/restore system
- ❌ Delete other users' messages
- ❌ Modify alliance data directly
- ❌ Access power editor or bulk tools

---

## 🟢 **R5** (Alliance Leader)

**Description:** Alliance leadership role with access to communication and coordination tools. Same permissions as R4 but typically represents the highest-ranking member of an alliance.

**Who Gets This Role:** Alliance leaders (R5 rank in-game).

### Features (Same as R4 + President)

**Discord Messaging:**
- ✅ Send instant Discord announcements
- ✅ Schedule future Discord messages
- ✅ Create recurring messages
- ✅ Auto-delete messages
- ✅ Create and manage message templates
- ✅ Access their alliance's Discord channels

**Season 2 Event Management:**
- ✅ View event calendar
- ✅ Send event announcements
- ✅ Access event templates

**Alliance Data:**
- ✅ View alliance information
- ✅ Access coordination tools

### Restrictions (R5 Cannot)

- ❌ Regenerate council rotation schedule (President/Admin only)
- ❌ Configure Discord or Season 2 settings (Admin only)
- ❌ Create or manage user accounts (Admin only)
- ❌ Access backup/restore (Admin only)
- ❌ Delete other users' messages (Admin only)
- ❌ Modify alliance data (Admin only)

---

## 🔵 **R4** (Alliance Officer)

**Description:** Alliance officer role with communication and coordination access. Same level of access as R5 for most features.

**Who Gets This Role:** Alliance officers (R4 rank in-game).

### Features (Same as R5)

**Discord Messaging:**
- ✅ Send instant Discord announcements
- ✅ Schedule future Discord messages
- ✅ Create recurring messages
- ✅ Auto-delete messages (1h, 6h, 12h, 24h, 48h)
- ✅ Create and manage message templates
- ✅ Submit templates for global approval
- ✅ Filter templates by season and event type
- ✅ Access their alliance's Discord channels only

**Season 2 Event Management:**
- ✅ View Season 2 event calendar
- ✅ Send one-click event announcements to alliance channels
- ✅ Access event templates
- ✅ View current week/day and days elapsed
- ✅ Filter events by week (1-7) and importance

**Alliance Data:**
- ✅ View their alliance's information
- ✅ View server rankings
- ✅ Access coordination tools

### Message Management

**Can Edit/Delete:**
- ✅ Their own instant messages
- ✅ Their own scheduled messages
- ✅ Their own recurring messages
- ✅ Their own templates

**Cannot Edit/Delete:**
- ❌ Other users' messages (even in same alliance)
- ❌ Admin-created messages
- ❌ Global templates

### Restrictions (R4 Cannot)

- ❌ Regenerate council rotation schedule (President/Admin only)
- ❌ Configure Discord bot settings (Admin only)
- ❌ Configure Season 2 settings like start date (Admin only)
- ❌ Create or manage user accounts (Admin only)
- ❌ Access backup/restore system (Admin only)
- ❌ Modify alliance data or power rankings (Admin only)
- ❌ Access other alliances' Discord channels
- ❌ Delete other users' messages
- ❌ Approve global templates (Admin only)

---

## 🔷 **R3** (Alliance Member)

**Description:** Basic member access. Limited to viewing public information.

**Who Gets This Role:** Regular alliance members.

### Features

- ✅ View public server information
- ✅ Access their user profile
- ✅ Limited dashboard access

### Restrictions (R3 Cannot)

- ❌ Send Discord messages
- ❌ Create templates
- ❌ Manage Season 2 events
- ❌ Access most admin features

---

## Permission Matrix

| Feature | Admin | President | R5 | R4 | R3 |
|---------|-------|-----------|----|----|-----|
| **Council Rotation Regeneration** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **User Management** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Alliance Power Editor** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Discord Bot Configuration** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Season 2 Configuration** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Backup/Restore** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Send Discord Messages** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Schedule Discord Messages** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Create Message Templates** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **View Season 2 Calendar** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Send Event Announcements** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Auto-Delete Messages** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Delete Others' Messages** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Approve Global Templates** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **View Audit Logs** | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Key Differences Summary

### President vs R5/R4

**President Has:**
- ✅ Council rotation regeneration access
- ✅ Designed for server-wide coordination
- ✅ Trusted leadership role

**R5/R4 Has:**
- Same Discord and Season 2 access as President
- Alliance-level coordination only
- Cannot regenerate council rotation

### R5 vs R4

**Functionally Identical:** R5 and R4 have the same website permissions. The distinction is organizational (R5 = leader, R4 = officer) but both can access the same features.

### President vs Admin

**Admin Has:**
- ✅ Full system configuration access
- ✅ User and role management
- ✅ Backup/restore system
- ✅ Discord bot configuration
- ✅ Season 2 configuration
- ✅ Can delete anyone's messages
- ✅ Power editor and bulk tools

**President Cannot:**
- ❌ Configure system settings
- ❌ Manage users or roles
- ❌ Access backups
- ❌ Modify templates or configurations

---

## Security & Audit

**All roles have:**
- 🔒 CSRF protection on state-changing actions
- 📝 Audit logging of all actions
- 🔐 JWT-based authentication
- 👁️ Activity monitoring

**Audit Events Logged:**
- Council rotation regenerations
- Discord message sending (instant, scheduled, recurring)
- Template creation and deletion
- Season 2 event announcements
- User logins and actions
- Configuration changes (Admin only)
- Backup operations (Admin only)

---

## Role Assignment

**How to Get a Role:**
1. Contact a Server Admin
2. Provide your alliance and in-game rank
3. Admin will create your account with appropriate role
4. You'll receive login credentials for https://www.lastwar1586.online

**Role Changes:**
- Only Admins can assign or change roles
- Role changes are logged in audit system
- President role requires Admin approval

---

## Use Cases

**Admin:**
- Server owner or technical administrator
- Needs full system configuration access
- Manages users, alliances, and system settings

**President:**
- Top alliance leader or council representative
- Coordinates server-wide events and rotation
- Trusted with council schedule management
- Does not need full admin access

**R5:**
- Alliance leader
- Sends alliance Discord announcements
- Coordinates alliance events
- Manages alliance communications

**R4:**
- Alliance officer
- Assists R5 with communications
- Coordinates alliance activities
- Same website access as R5

**R3:**
- Alliance member
- Views public information
- Limited access

---

**Website:** https://www.lastwar1586.online
**Version:** 3.5.0
**Last Updated:** November 2025
