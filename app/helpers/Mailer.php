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

        if ($smtpUser === 'resend') {
            $sent = self::sendViaResendApi($to, $toName, $subject, $htmlBody);
            if ($sent) return true;
            error_log("Resend API fallback a MailLogger");
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
            error_log("Mailer error: " . $e->getMessage() . " — fallback a MailLogger");
            return MailLogger::send($to, $toName, $subject, $htmlBody);
        }
    }

    private static function sendViaResendApi(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        $apiKey = getenv('SMTP_PASS');
        $from = getenv('SMTP_FROM') ?: 'noreply@inecolara.gob.ve';
        $fromName = getenv('SMTP_FROM_NAME') ?: 'SYSINECOLARA';

        $payload = json_encode([
            'from' => "{$fromName} <{$from}>",
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlBody,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'User-Agent: sysinescolara/1.0',
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            return true;
        }

        error_log("Resend API error (HTTP {$httpCode}): " . ($response ?: $error));
        return false;
    }
}
