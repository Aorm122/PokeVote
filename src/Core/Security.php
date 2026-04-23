<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function hashWithSecret(string $value): string
    {
        $secret = Env::get('APP_SECRET', 'pokevote-change-me');
        return hash('sha256', $value . '|' . $secret);
    }

    public static function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        if ($normalized === '') {
            $normalized = 'category-' . random_int(1000, 9999);
        }

        return $normalized;
    }
}
