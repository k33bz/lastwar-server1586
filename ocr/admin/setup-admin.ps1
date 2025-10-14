# Target directory
$basePath = "C:\path\to\project\admin"

# Create folders
New-Item -ItemType Directory -Force -Path $basePath
New-Item -ItemType Directory -Force -Path "$basePath\..\data"
New-Item -ItemType Directory -Force -Path "$basePath\vendor"

# Create files with sample content
$files = @{
    "config.php" = "<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
define('SECRET_KEY', $_ENV['SECRET_KEY']);
define('SMTP_HOST', $_ENV['SMTP_HOST']);
define('SMTP_USER', $_ENV['SMTP_USER']);
define('SMTP_PASS', $_ENV['SMTP_PASS']);
define('SMTP_FROM', $_ENV['SMTP_FROM']);
define('ADMIN_EMAIL', 'admin@example.com');
define('USERS_FILE', __DIR__.'/users.json');
define('BLACKLIST_FILE', __DIR__.'/token_blacklist.json');
define('ALLIANCES_FILE', $_SERVER['DOCUMENT_ROOT'].'/data/alliances.json');
?>"
    "jwt.php" = "<?php
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
        $blacklist = file_exists(BLACKLIST_FILE) ? json_decode(file_get_contents(BLACKLIST_FILE), true)['jti'] : [];
        if (in_array($token->jti, $blacklist)) {
            setcookie('jwt', '', time()-3600, '/admin/');
            header('Location: login.php?revoked=1');
            exit;
        }
        return $token;
    } catch (Exception $e) {
        setcookie('jwt', '', time()-3600, '/admin/');
        header('Location: login.php?expired=1');
        exit;
    }
}
?>"
    "users.json" = "{`"users`":[{`"email`":`"admin@example.com`",`"alliances`":[`"*`"],`"role`":`"admin`"}]}"
    "token_blacklist.json" = "{`"jti`":[]}"
    ".env" = "SECRET_KEY=your-secret-key
SMTP_HOST=smtp.example.com
SMTP_USER=mailer@example.com
SMTP_PASS=your-smtp-password
SMTP_FROM=noreply@example.com"
    # Add more files as needed...
}

# Write files
foreach ($file in $files.Keys) {
    $path = Join-Path $basePath $file
    Set-Content -Path $path -Value $files[$file] -Force
}

# Create sample alliances.json
Set-Content -Path "$basePath\..\data\alliances.json" -Value '{
  "alliances": [
    {
      "slug": "uvvu",
      "name": "UVVU Alliance",
      "description": "Example description",
      "members": ["A", "B", "C"]
    }
  ]
}' -Force

Write-Host "✅ Admin system files created at $basePath"