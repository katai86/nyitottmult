<?php
/**
 * Nyitott Múlt – SMTP Levelező Szolgáltatás
 * Natív TLS socket kommunikáció (RFC 5321)
 */

declare(strict_types=1);

namespace NyitottMult;

class MailService
{
    /**
     * E-mail küldése beállított SMTP szerveren keresztül
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $toName = null): bool
    {
        if (!defined('SMTP_HOST') || empty(SMTP_HOST) || !defined('SMTP_USER') || empty(SMTP_USER)) {
            error_log('[MailService] SMTP is not configured in config.php');
            return false;
        }

        $host = SMTP_HOST;
        $port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $user = SMTP_USER;
        $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $from = defined('SMTP_FROM') ? SMTP_FROM : $user;
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Nyitott Múlt';

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 15);
            if (!$socket) {
                error_log("[MailService] Failed to connect to {$host}:{$port} - {$errstr} ({$errno})");
                return false;
            }

            self::readResponse($socket);

            // EHLO
            $hostname = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'nyitottmult.kataiattila.hu';
            self::sendCommand($socket, "EHLO " . $hostname);

            // STARTTLS port 587 esetén
            if ($port === 587) {
                self::sendCommand($socket, "STARTTLS");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log("[MailService] STARTTLS crypto handshake failed");
                    fclose($socket);
                    return false;
                }
                self::sendCommand($socket, "EHLO " . $hostname);
            }

            // AUTH LOGIN
            self::sendCommand($socket, "AUTH LOGIN");
            self::sendCommand($socket, base64_encode($user));
            self::sendCommand($socket, base64_encode($pass));

            // MAIL FROM
            self::sendCommand($socket, "MAIL FROM:<{$from}>");

            // RCPT TO
            self::sendCommand($socket, "RCPT TO:<{$to}>");

            // DATA
            self::sendCommand($socket, "DATA");

            $headers = [
                "MIME-Version: 1.0",
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>",
                "To: " . ($toName ? "=?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>" : "<{$to}>"),
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "Date: " . date('r'),
                "Content-Type: text/html; charset=UTF-8",
                "Content-Transfer-Encoding: base64"
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
            self::sendCommand($socket, $message);

            // QUIT
            self::sendCommand($socket, "QUIT");
            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            error_log("[MailService] Send error: " . $e->getMessage());
            return false;
        }
    }

    private static function sendCommand($socket, string $cmd): string
    {
        fwrite($socket, $cmd . "\r\n");
        return self::readResponse($socket);
    }

    private static function readResponse($socket): string
    {
        $response = "";
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        return $response;
    }
}
