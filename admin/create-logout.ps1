param($path)
Set-Content -Path "$path\logout.php" -Value @"
<?php
setcookie('jwt', '', time()-3600, '/admin/');
header('Location: login.php');
exit;
?>
"@