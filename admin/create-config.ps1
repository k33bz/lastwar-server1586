param($path)
Set-Content -Path "$path\config.php" -Value @"
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use Dotenv\Dotenv;
\$dotenv = Dotenv::createImmutable(__DIR__);
\$dotenv->load();
define('SECRET_KEY', \$_ENV['SECRET_KEY']);
define('SMTP_HOST', \$_ENV['SMTP_HOST']);
define('SMTP_USER', \$_ENV['SMTP_USER']);
define('SMTP_PASS', \$_ENV['SMTP_PASS']);
define('SMTP_FROM', \$_ENV['SMTP_FROM']);
define('ADMIN_EMAIL', 'admin@example.com');
define('USERS_FILE', __DIR__.'/users.json');
define('BLACKLIST_FILE', __DIR__.'/token_blacklist.json');
define('ALLIANCES_FILE', \$_SERVER['DOCUMENT_ROOT'].'/data/alliances.json');
?>
"@