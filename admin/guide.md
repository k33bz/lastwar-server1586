Comprehensive Technical Guide: Building a Secure JWT-Based Email Magic Link Admin System for www.example.com

Introduction

The need for secure, scalable, and intuitive user authentication flows is paramount for modern web applications—especially in the context of administrative panels that allow multiple independently authorized user groups (alliances in this case) to manage sensitive data. For www.example.com, the goal is to create a passwordless JWT-based admin login interface that leverages email-based magic links, providing each alliance (represented by one or more email addresses) fine-grained access to only their data as found in a JSON file (/data/alliances.json). Additionally, the global admin must have the ability to manage users dynamically.

This guide presents the conceptual and practical roadmap for such a system, including full PHP code broken into clearly described files, explanations of JWT and magic-link authentication, the data and permission model, approaches for concurrency and JSON I/O, robust security practices, token revocation, and administrative user CRUD. Each component is analyzed and substantiated with relevant web resources.

System Architecture Overview

The authentication and authorization system designed herein is stateless session (JWT-based), passwordless (using only verified email addresses and signed magic links), and provides both global administration and multi-alliance user management.

High-Level Workflow

Login Request: User enters an email address. If recognized, the system sends a one-time magic link to that email.

Magic Link Click: User follows the link, which contains an expiring, single-use JWT session token.

JWT Validation & Role Resolution: On accessing the login callback via the link, the token is verified and permissions are loaded.

Session Established: A JWT is set in an HTTP-only cookie, and the user is directed to the admin interface, where they may view or edit only the alliances they are authorized for.

Admin CRUD: The global admin can add, edit, or remove user-email associations and their roles via a protected administrative view and API endpoints.

Alliance Data Edit: Alliance users can edit their alliance chunk in the JSON.

Logout: Destroys the current token/cookie.

Revocation/Expiry: Tokens expire automatically, and can also be manually revoked via blacklist (e.g., after user removal or compromise).

Project Structure and PHP File Layout

Suggested file/folder structure:

/admin/
  ├── config.php
  ├── jwt.php
  ├── mailer.php
  ├── login.php
  ├── send_magic_link.php
  ├── callback.php
  ├── dashboard.php
  ├── allies_api.php
  ├── admin_api.php
  ├── users.json            # for user/role/email mapping
  ├── token_blacklist.json  # for revoked JWT ids
/data/alliances.json        # stored alliance data
/vendor/                    # Composer deps (PHPMailer, firebase/php-jwt, etc.)
.env

Each file’s role and code are explained below. The report will progress from configuration and library setup, through authentication and magic-link mechanics, permission models, data handling, concurrency, admin tools, to security and cleanup.

1. JWT Basics and Implementation in PHP

JWT (JSON Web Token) is a compact, URL-safe token used for secure information exchange in stateless authentication systems. Each JWT token contains a header, payload (claims), and a signature:

{
  "alg": "HS256",
  "typ": "JWT"
}
.
{
  "sub": "uvvu@example.com",  // subject, the user's email
  "aud": "alliance",     // audience: "alliance" or "admin"
  "alliances": ["UVVU"], // alliance slugs user can edit
  "exp": 1719999999,     // expiry
  "iat": 1719990000,     // issued at
  "jti": "randomuuid"    // unique jwt id for blacklist
}
.
[signature]

The signature ensures integrity and authenticity using a secret key, which should be stored server-side (for example, using .env and loaded via phplib like vlucas/phpdotenv).

PHP implementation: We use [firebase/php-jwt][16†source] for encoding and decoding tokens, as it is the de facto standard in the PHP ecosystem. Installation:

composer require firebase/php-jwt

Sample usage:

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$payload = [
    'sub' => $user_email,
    'exp' => time() + 15 * 60,
    'aud' => $role, // 'admin' or 'alliance'
    'alliances' => $allianceSlugs,
    'iat' => time(),
    'jti' => bin2hex(random_bytes(8)), // unique id
];
$jwt = JWT::encode($payload, $secret_key, 'HS256');

// Decoding (verification):
try {
    $token = JWT::decode($jwt, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    // Handle invalid/expired/malformed JWT
}

Importance of Expiry and jti claims:Set a short expiry on session tokens (e.g., 15 min) for security, and use a unique jti claim for revocation (blacklist), as discussed below.

2. Magic Link Email Authentication in PHP

Principle

"Magic links" provide a passwordless experience: users log in by clicking a unique, expiring link sent to their email. This requires strong link signing, short validity, single-use semantics (enforced via JWT jti and blacklist), and protection against replay attacks.

Security Best Practices for Magic Links

Links must include expiring, unique JWT—never guessable session ids.

Ensure links cannot be brute-forced: tokens should be securely random (at least 128 bits).

Mark used or revoked tokens as invalid (via blacklist file/database or a revocation table).

The magic link endpoint must verify the incoming token, issue a new session JWT, and invalidate the magic link's original token.

If a redirect URL is permitted in link, it must be validated against whitelist to prevent phishing.

Email Sending with PHP

We'll use PHPMailer (or alternatives like Symfony Mailer or the native mail() function, but PHPMailer is the most popular and reliable), installed via Composer:

composer require phpmailer/phpmailer

Basic usage for SMTP:

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $smtp_host;
$mail->SMTPAuth = true;
$mail->Username = $smtp_user;
$mail->Password = $smtp_pass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
// ...set other options, sender and recipient...

$mail->setFrom('noreply@example.com', 'Last War 1586');
$mail->addAddress($email);

$mail->Subject = 'Your Login Link';
$mail->Body = "Click here to log in:\n$magic_link_url";

$mail->send();

For improved deliverability and DKIM/DMARC compliance, configure SPF/DKIM in your DNS.

References:

PHPMailer documentation and install guides.

3. Configuration and Secret Management

config.php — centralized, loads keys from .env, manages secret keys.

<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('SECRET_KEY', $_ENV['SECRET_KEY']);        // JWT signing
define('SMTP_HOST', $_ENV['SMTP_HOST']);
define('SMTP_USER', $_ENV['SMTP_USER']);
define('SMTP_PASS', $_ENV['SMTP_PASS']);
define('SMTP_FROM', $_ENV['SMTP_FROM']);          // sending address
define('ADMIN_EMAIL', 'admin@example.com');
define('USERS_FILE', __DIR__.'/users.json');
define('BLACKLIST_FILE', __DIR__.'/token_blacklist.json');
define('ALLIANCES_FILE', $_SERVER['DOCUMENT_ROOT'].'/data/alliances.json');
?>

.env example:

SECRET_KEY=KEX....your-long-secret...........
SMTP_HOST=smtp.example.com
SMTP_USER=mailer@example.com
SMTP_PASS=app-password-string
SMTP_FROM=noreply@example.com

Best Practice:Never hardcode secrets in code. Use .env/environment vars.

4. User and Permission Model (Data Model Design)

users.json — stores the mapping of email addresses to role and permissions.

Example:

{
  "users": [
    {
      "email": "uvvu@example.com",
      "alliances": ["uvvu"],
      "role": "alliance"
    },
    {
      "email": "orce@example.com",
      "alliances": ["orce", "uvvu"],
      "role": "alliance"
    },
    {
      "email": "admin@example.com",
      "alliances": ["*"],    // * for global admin/all
      "role": "admin"
    }
  ]
}

Structure explanation:

"email": user identity

"alliances": array of slugs the user can edit. ["*"] grants superuser/admin rights.

"role": "alliance" users can only edit their alliances, "admin" can manage users.

Managing Multiple Alliances per User:Just include all alliance slugs in arrays in "alliances" (e.g., for users managing several).

5. Sending and Validating Magic Links

5.1 Requesting the Link - /admin/login.php

A simple HTML form for email input:

<!-- /admin/login.php -->
<form method="post" action="send_magic_link.php">
    <label for="email">Alliance Email:</label>
    <input type="email" name="email" required autocomplete="email">
    <button type="submit">Send Magic Link</button>
</form>

5.2 Backend for Sending the Magic Link - /admin/send_magic_link.php

<?php
require_once 'config.php';
require_once 'jwt.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $email = strtolower(trim($_POST['email']));
    $users = json_decode(file_get_contents(USERS_FILE), true)['users'];

    // Find this email
    $user = null;
    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $user = $u; break;
        }
    }
    if (!$user) {
        die('Unknown email address (or not authorized).');
    }

    // Generate short-lived magic token (e.g., 10 min), with random jti
    $payload = [
        'sub' => $email,
        'aud' => $user['role'],
        'alliances' => $user['alliances'],
        'exp' => time() + 10*60,
        'iat' => time(),
        'jti' => bin2hex(random_bytes(16)),
        'magic' => true                  // distinguish from session token
    ];
    $magic_token = JWT::encode($payload, SECRET_KEY, 'HS256');
    $link = "https://www.example.com/admin/callback.php?token=$magic_token";

    // Optionally: store jti in a temporary used-link file/array, and add after use to blacklist

    // Send email
    $subject = "Your Magic Login Link for Last War 1586";
    $body = <<<EOT
Hi,

Click to access your admin dashboard (expires in 10 minutes):

$link

If you did not request this, ignore this email.

Best regards,
The Last War 1586 Admins
EOT;
    send_email($email, $subject, $body);

    echo "Login link sent. Check your email.";
} else {
    die('Invalid request.');
}
?>

Note: The email sending is performed by mailer.php (see next section).

5.3 Email Sending Helper - /admin/mailer.php

<?php
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(SMTP_FROM, 'Last War 1586');
        $mail->addAddress($to);

        $mail->isHTML(false); // send plain text
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer error: ".$mail->ErrorInfo);
        throw new Exception("Could not send mail");
    }
}
?>

References: PHPMailer is widely used and supports robust error handling and SMTP settings.

5.4 Magic Link Callback - /admin/callback.php

When the user clicks the emailed link, they're sent (GET) to /admin/callback.php?token=...

<?php
require_once 'config.php';
require_once 'jwt.php';

// Optional: Implement CSRF check or make sure referer is correct

if (!isset($_GET['token'])) {
    die('Missing token.');
}

$magic_token = $_GET['token'];
try {
    $decoded = JWT::decode($magic_token, new Firebase\JWT\Key(SECRET_KEY, 'HS256'));
    // Check not on blacklist
    $blacklist = file_exists(BLACKLIST_FILE) ? json_decode(file_get_contents(BLACKLIST_FILE), true)['jti'] : [];
    if (in_array($decoded->jti, $blacklist)) {
        die("Token is revoked (already used).");
    }
    // If not a "magic" token, block (prevents reuse of JWT session tokens)
    if (empty($decoded->magic) || !$decoded->magic) {
        die("Invalid magic token (wrong purpose).");
    }
    // Single-use: add this jti to blacklist
    $blacklist[] = $decoded->jti;
    file_put_contents(BLACKLIST_FILE, json_encode(['jti'=>$blacklist], JSON_PRETTY_PRINT));

    // Issue a new "session" JWT with longer expiry (30-120 min), no 'magic' flag
    $session_payload = [
        'sub' => $decoded->sub,
        'aud' => $decoded->aud,
        'alliances' => $decoded->alliances,
        'exp' => time() + 60*60,
        'iat' => time(),
        'jti' => bin2hex(random_bytes(16))
    ];
    $session_token = JWT::encode($session_payload, SECRET_KEY, 'HS256');
    setcookie('jwt', $session_token, [
      'expires' => time()+60*60,
      'httponly' => true,
      'secure' => true,
      'path' => '/admin/'
    ]);
    // Log in!
    header('Location: dashboard.php');
    exit;
} catch (\Firebase\JWT\ExpiredException $e) {
    die('This link has expired.');
} catch (Exception $e) {
    die('Invalid login link.');
}
?>

Single-use enforcement via blacklist:Each used magic token’s jti is stored in a blacklist (here, token_blacklist.json). Any future attempt to use a token with the same jti is denied. (If you want to avoid unbounded growth, periodically delete outdated entries via a cron job.)

6. Session Validation and Dashboard Access

All admin pages (except email-link endpoints) should require a valid, non-expired JWT in cookie. Use a reusable validation loader.

jwt.php (helper, reuse in all protected endpoints):

<?php
require_once 'config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function require_jwt_session() {
    if (!isset($_COOKIE['jwt'])) {
        header('Location: login.php');
        exit;
    }
    try {
        $token = JWT::decode($_COOKIE['jwt'], new Key(SECRET_KEY, 'HS256'));
        // Check blacklist
        $blacklist = file_exists(BLACKLIST_FILE) ? json_decode(file_get_contents(BLACKLIST_FILE), true)['jti'] : [];
        if (in_array($token->jti, $blacklist)) {
            setcookie('jwt', '', time()-3600, "/admin/");
            header('Location: login.php?revoked=1');
            exit;
        }
        // Optionally: check aud/role for endpoint
        return $token;
    } catch (Exception $e) {
        setcookie('jwt', '', time()-3600, "/admin/");
        header('Location: login.php?expired=1');
        exit;
    }
}
?>

dashboard.php (example main admin view):

<?php
require_once 'jwt.php';
$user_token = require_jwt_session();

// Show different content based on $user_token->aud ("admin" vs. "alliance")
?>
<html><body>
<h1>Welcome, <?=htmlspecialchars($user_token->sub)?></h1>
<?php
if ($user_token->aud === "admin") {
  echo "<a href='admin_api.php'>User management</a> | ";
}
// List and edit alliances according to $user_token->alliances
foreach ($user_token->alliances as $slug) {
  echo "<a href='allies_api.php?action=view&alliance=$slug'>Edit alliance: $slug</a><br>";
}
?>
<form method="post" action="logout.php"><button>Log out</button></form>
</body></html>

7. Reading and Writing JSON Files in PHP (with Concurrency)

alliances.json (example data, structure may vary):

{
  "alliances": [
    {
      "slug": "uvvu",
      "name": "UVVU Alliance",
      "description": "...",
      "members": ["A", "B", "C"]
    },
    {
      "slug": "orce",
      "name": "Orce Alliance",
      "description": "...",
      "members": ["D", "E"]
    }
  ]
}

PHP Read/Write with File Locking

PHP’s file_get_contents() and file_put_contents() suffice for small files, but concurrent writes must be protected.Use flock() to obtain a lock before modifying files:

function read_json_file($path) {
    $handle = fopen($path, 'r');
    if (flock($handle, LOCK_SH)) {
        $data = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        return json_decode($data, true);
    }
    fclose($handle);
    throw new Exception("Could not lock $path for reading.");
}

function write_json_file($path, $data) {
    $handle = fopen($path, 'c+');
    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    } else {
        fclose($handle);
        throw new Exception("Could not lock $path for writing.");
    }
}

These should be used for both /data/alliances.json and /admin/users.json at all write (and preferably read) points.

allies_api.php (API endpoint for alliance data edit)

<?php
require_once 'jwt.php';
$user_token = require_jwt_session();

$alliances = read_json_file(ALLIANCES_FILE)['alliances'];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action']==='view') {
    $slug = $_GET['alliance'];
    if (!in_array($slug, $user_token->alliances) && $user_token->aud !== 'admin') {
        http_response_code(403);
        die("Not permitted.");
    }
    $entry = null;
    foreach ($alliances as $a) {
      if ($a['slug'] === $slug) {
        $entry = $a;
        break;
      }
    }
    if (!$entry) die("Alliance not found.");
    // show edit form with existing data
    // ...
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit alliance (ensure alliance in user's permitted list)
    $slug = $_POST['slug'];
    if (!in_array($slug, $user_token->alliances) && $user_token->aud !== 'admin') {
        http_response_code(403);
        die("Not permitted.");
    }
    // Read alliances, modify, write back (with lock)
    $alliancesdata = read_json_file(ALLIANCES_FILE);
    foreach ($alliancesdata['alliances'] as &$a) {
        if ($a['slug'] == $slug) {
            $a['description'] = $_POST['description'];
            // ...other fields
        }
    }
    write_json_file(ALLIANCES_FILE, $alliancesdata);
    header('Location: dashboard.php');
    exit;
}
?>

8. Global Admin Panel: User CRUD (admin_api.php)

This page (visible only to admin@example.com and those with "role":"admin") permits managing all users.

<?php
require_once 'jwt.php';
$user_token = require_jwt_session();
if ($user_token->aud !== 'admin') {
    http_response_code(403); die("Admin only.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usersdata = read_json_file(USERS_FILE);
    // Support actions: add, edit, delete
    if ($_POST['action'] === 'add') {
        $usersdata['users'][] = [
            'email' => strtolower(trim($_POST['email'])),
            'alliances' => array_map('trim', explode(',', $_POST['alliances'])), // e.g. "uvvu,orce"
            'role' => $_POST['role']
        ];
    } elseif ($_POST['action'] === 'delete') {
        $usersdata['users'] = array_filter($usersdata['users'], function($u){
            return $u['email'] !== $_POST['email'];
        });
        // Optionally, immediately revoke all tokens for this user (e.g., add all their known jti's to the blacklist)
    }
    write_json_file(USERS_FILE, $usersdata);
}

$usersdata = read_json_file(USERS_FILE);
// Display management UI, show all users, forms for add/edit/delete
// ...
?>

Note: All user meta-edits should be protected by JWT role check. When a user is deleted, all their magic links and session tokens should also be blacklisted (see revocation).

9. Token Expiration, Refresh, and Revocation

Best practices for JWT expiration and refresh:

Short-lived session tokens reduce window of compromise if a token is stolen.

Refresh: For very long sessions (not generally advised for admin), implement a refresh token flow.

Blacklisting: Maintain a persistent jti blacklist. On logout, user deletion, password change, or admin action, immediately add all relevant tokens’ jti values (if known) to the blacklist. For magic links, the token is blacklisted at use.

Sample code for adding jti to blacklist:

function blacklist_token($jti) {
    $file = BLACKLIST_FILE;
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['jti'=>[]];
    if (!in_array($jti, $data['jti'])) {
        $data['jti'][] = $jti;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}

Automated Blacklist Cleanup: Over time, expired/revoked jtis should be deleted from the blacklist (e.g., nightly cron job) to avoid unbounded file growth.

10. Security Best Practices

JWT Security

Do not include sensitive data in token payloads. Claims are only base64url-encoded and can be read by anyone with the token.

Always use strong, random, and sufficiently long secret keys, not hardcoded.

Use short expiry. Expired tokens are always rejected.

Use HTTPS on all endpoints at all times to prevent token theft via MITM.

Magic Link Security

Tokens must be single-use, enforced using the jti blacklist. Never allow silent token reuse.

The magic link should be clearly marked as valid for only a short time ("expires in X min").

Do not allow arbitrary redirect URLs via query params unless they are validated against a whitelist. This blocks phishing attacks (open redirect vulnerabilities).

Every place you accept untrusted input (email, URL param) should be validated and sanitized.

Email Security

Use SMTP, with SPF/DKIM configured in DNS to maximize email deliverability and avoid fraudulent emails.

Never include sensitive data in emails.

Admin/User Data Integrity

All modifications to the JSON files (alliances.json, users.json, token_blacklist.json) must be atomic, with exclusive locks for writes (see above).

All admin actions must use JWT session enforcement to block anonymous access.

Cron/Cleanup

Manage the blacklist (and possibly magic link tables, logs) with an automated cron script, purging expired links and tokens.

Sample cron.php:

<?php
$file = BLACKLIST_FILE;
if (!file_exists($file)) exit;
$data = json_decode(file_get_contents($file), true);
$new_jti = [];
foreach ($data['jti'] as $jt) {
    // Optionally: store [jti, expiry], and keep only those not expired
    // For now, just keep last N entries or those < X days old
}
file_put_contents($file, json_encode(['jti' => $new_jti], JSON_PRETTY_PRINT));
?>

Schedule via crontab for regular cleanup.

11. Environment and Deployment Notes

Use Composer to manage dependencies (firebase/php-jwt, phpmailer/phpmailer, vlucas/phpdotenv).

Store user, alliance, and revocation data in the admin/ folder with proper server permissions.

Only expose endpoints through HTTPS. Use HSTS to enforce this browser-side.

Restrict direct access to admin files by role, and possibly IP range for extra security.

12. Multi-Alliance User Association Model

Supporting users belonging to multiple alliances requires only an array of slugs in their alliances field in users.json—an arrangement that is natural for the data model and all alliance-based permission checks.

All authorization checks (e.g., in allies_api.php and UI code), should validate the intersection of $user_token->alliances and the alliance being edited.

Conclusion

With this guide, you can deploy a secure, stateless admin system that provides:

Strong, scalable email/magic-link authentication without passwords

Single-use, short-lived tokens preventing replay and phishing attacks

Clear separation of alliance and administrative roles

Flexible multi-alliance user support

Safe, concurrent JSON data handling

Robust token revocation and expiry, with manageable cron-based cleanup

Strong security best practices for JWT, email, and user data

A globally authorized admin with full CRUD over users, using only easily auditable JSON flatfiles

All code provided can be extended or refactored for more complex integrations (e.g., database storage, job queues, richer role management) if the project's scope expands, without architectural dead-ends. Recommended references are woven throughout for deeper study and maintenance as the stack and threat landscape evolves.

References (22)

PHP-JWT - Firebase Open Source. https://firebaseopensource.com/projects/firebase/php-jwt

Authentication with JWT (JSON Web Token) in PHP. https://www.netopsiyon.com/en/authentication-with-jwt-json-web-token-in-php

JWT in Practice – Part 2: Refresh Tokens, Expiration, and Best .... https://dev.to/gabrielle_eduarda_776996b/jwt-in-practice-part-2-refresh-tokens-expiration-and-best-practices-20p2

Understanding JWT Expiration Time claim (exp) - MojoAuth. https://mojoauth.com/blog/understanding-jwt-expiration-time-claim-exp/

How to Manage JWT Expiration and Revoke JWTs | FusionAuth. https://fusionauth.io/articles/tokens/revoking-jwts

Revoke Access Using a JWT Blacklist | SuperTokens. https://supertokens.com/blog/revoking-access-with-a-jwt-blacklist

Vfix Technology - Laravel Passwordless Login with Magic Link | Step-by .... https://www.vfixtechnology.com/passwordless-login-in-laravel-using-magic-link-step-by-step-guide

Set Up Seamless Magic Link Authentication with SendGrid and Laravel. https://www.twilio.com/en-us/blog/developers/community/seamless-magic-link-authentication-sendgrid-laravel

Dfns - The Magic Link Vulnerability. https://www.dfns.co/article/the-magic-link-vulnerability

PHPMailer: Tutorial with Code Snippets [2025] - Mailtrap. https://mailtrap.io/blog/phpmailer/

GitHub - PHPMailer/PHPMailer: The classic email sending library for PHP. https://github.com/PHPMailer/PHPMailer

How to Use PHPMailer to Send Emails: A Complete Setup and .... https://unione.io/en/blog/php-mailer-example

PHP: How to read and write to a JSON file - Sling Academy. https://www.slingacademy.com/article/php-read-write-json-file/

PHP: flock - Manual. https://www.php.net/manual/en/function.flock.php

file locking in php - Stack Overflow. https://stackoverflow.com/questions/5449395/file-locking-in-php

In Laravel, Is there a way to delete old revoked/expired passport tokens. https://stackoverflow.com/questions/54549982/in-laravel-is-there-a-way-to-delete-old-revoked-expired-passport-tokens

Reduce expired oauth_tokens before 2.4.6 upgrade. https://experienceleague.adobe.com/en/docs/experience-cloud-kcs/kbarticles/ka-26848

How to setup cronjobs for a Laravel project - DEV Community. https://dev.to/dreywandowski/how-to-setup-cronjobs-for-a-laravel-project-2li2

PHP Authorization with JWT (JSON Web Tokens) — SitePoint. https://www.sitepoint.com/php-authorization-jwt-json-web-tokens/

Fast and simple dealing with Php Multi Tenancy - GitHub. https://github.com/tafhyseni/php-multi-tenancy

How to Implement Multi-Tenancy in PHP Applications - Datatas. https://datatas.com/how-to-implement-multi-tenancy-in-php-applications/

How to Build a Multi-Tenant SaaS Platform Using PHP. https://www.goglides.dev/bella_swan_/how-to-build-a-multi-tenant-saas-platform-using-php-38ng