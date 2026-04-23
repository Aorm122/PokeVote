<?php

declare(strict_types=1);

namespace App\Core;

final class Http
{
    public static function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function badRequest(string $message): void
    {
        self::json(['success' => false, 'error' => $message], 400);
    }

    public static function tooManyRequests(string $message): void
    {
        self::json(['success' => false, 'error' => $message], 429);
    }

    public static function serverError(string $message = 'Unexpected server error'): void
    {
        self::json(['success' => false, 'error' => $message], 500);
    }

    public static function ipAddress(): string
    {
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if (is_string($forwardedFor) && $forwardedFor !== '') {
            $parts = explode(',', $forwardedFor);
            $candidate = trim($parts[0]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($remoteAddress) && $remoteAddress !== '' ? $remoteAddress : '0.0.0.0';
    }

    public static function userAgent(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return is_string($userAgent) ? $userAgent : '';
    }
}
