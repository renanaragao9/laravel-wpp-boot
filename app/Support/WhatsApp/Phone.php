<?php

declare(strict_types=1);

namespace App\Support\WhatsApp;

final class Phone
{
    public static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', explode('@', $value)[0]) ?? '';
    }
}
