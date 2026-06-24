<?php

namespace SysInescolara\helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static ?PHPMailer $mail = null;

    private static function getInstance(): PHPMailer
    {
        if (self::$mail === null) {
            self::$mail = new PHPMailer(true);
            self::$mail->isSMTP();
            self::$mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            self::$mail->SMTPAuth = true;
            self::$mail->Username = getenv('SMTP_USER');
            self::$mail->Password = getenv('SMTP_PASS');
            self::$mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
            self::$mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            self::$mail->CharSet = 'UTF-8';

            $from = getenv('SMTP_FROM') ?: 'noreply@inecolara.gob.ve';
            $fromName = getenv('SMTP_FROM_NAME') ?: 'SYSINECOLARA';
            self::$mail->setFrom($from, $fromName);
        }
        return self::$mail;
    }

    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        $smtpUser = getenv('SMTP_USER');
        if (empty($smtpUser) || $smtpUser === 'tu-correo@gmail.com') {
            return MailLogger::send($to, $toName, $subject, $htmlBody);
        }

        try {
            $mail = self::getInstance();
            $mail->clearAddresses();
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }
}
