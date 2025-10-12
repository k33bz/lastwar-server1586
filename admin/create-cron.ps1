param($path)
Set-Content -Path "$path\cron.php" -Value @"
<?php
\$file = BLACKLIST_FILE;
if (!file_exists(\$file)) exit;
\$data = json_decode(file_get_contents(\$file), true);
\$new_jti = [];
// Add logic to clean expired tokens if you store expiry
file_put_contents(\$file, json_encode(['jti' => \$new_jti], JSON_PRETTY_PRINT));
?>
"@