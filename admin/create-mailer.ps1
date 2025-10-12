param($path)
Set-Content -Path "$path\mailer.php" -Value @"
<?php
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email(\$to, \$subject, \$body) {
    \$mail = new PHPMailer(true);
    try {
        \$mail->isSMTP();
        \$mail->Host = SMTP_HOST;
        \$mail->SMTPAuth = true;
        \$mail->Username = SMTP_USER;
        \$mail->Password = SMTP_PASS;
        \$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        \$mail->Port = 587;

        \$mail->setFrom(SMTP_FROM, 'Last War 1586');
        \$mail->addAddress(\$to);

        \$mail->isHTML(false);
        \$mail->Subject = \$subject;
        \$mail->Body = \$body;

        \$mail->send();
    } catch (Exception \$e) {
        error_log("Mailer error: ".\$mail->ErrorInfo);
        throw new Exception("Could not send mail");
    }
}
?>
"@