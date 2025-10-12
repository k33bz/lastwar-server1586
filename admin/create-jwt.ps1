param($path)
Set-Content -Path "$path\jwt.php" -Value @"
<?php
require_once 'config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
function require_jwt_session() {
    if (!isset(\$_COOKIE['jwt'])) {
        header('Location: login.php');
        exit;
    }
    try {
        \$token = JWT::decode(\$_COOKIE['jwt'], new Key(SECRET_KEY, 'HS256'));
        \$blacklist = file_exists(BLACKLIST_FILE) ? json_decode(file_get_contents(BLACKLIST_FILE), true)['jti'] : [];
        if (in_array(\$token->jti, \$blacklist)) {
            setcookie('jwt', '', time()-3600, '/admin/');
            header('Location: login.php?revoked=1');
            exit;
        }
        return \$token;
    } catch (Exception \$e) {
        setcookie('jwt', '', time()-3600, '/admin/');
        header('Location: login.php?expired=1');
        exit;
    }
}
?>
"@