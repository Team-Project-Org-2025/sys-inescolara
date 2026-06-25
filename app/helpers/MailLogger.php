<?php

namespace SysInescolara\helpers;

class MailLogger
{
    private static string $logDir = '';

    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        $logDir = self::getLogDir();

        $filename = 'email-' . date('Y-m-d-H-i-s') . '-' . bin2hex(random_bytes(4)) . '.html';
        $filepath = $logDir . DIRECTORY_SEPARATOR . $filename;

        $logContent = "To: {$toName} <{$to}>\n";
        $logContent .= "Subject: {$subject}\n";
        $logContent .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= str_repeat('-', 60) . "\n\n";
        $logContent .= $htmlBody;

        $written = file_put_contents($filepath, $logContent, LOCK_EX);

        $summary = "[" . date('Y-m-d H:i:s') . "] TO: {$to} | SUBJECT: {$subject} | FILE: {$filename}\n";
        file_put_contents($logDir . DIRECTORY_SEPARATOR . 'emails.log', $summary, FILE_APPEND | LOCK_EX);

        error_log("MailLogger: Email logged to {$filename} (to: {$to}, subject: {$subject})");

        return $written !== false;
    }

    public static function getLogDir(): string
    {
        if (self::$logDir === '') {
            self::$logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'emails';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0775, true);
            }
        }
        return self::$logDir;
    }
}
