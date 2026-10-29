<?php

namespace App\Support;

use RuntimeException;
use Throwable;

class UserFacingError
{
    public const GENERIC = 'Something went wrong. Please try again.';

    public static function message(Throwable $e): string
    {
        if ($e instanceof RuntimeException) {
            $text = trim($e->getMessage());
            if (self::isSafe($text)) {
                return $text;
            }
        }

        return self::GENERIC;
    }

    public static function isSafe(string $text): bool
    {
        if ($text === '' || strlen($text) > 240) {
            return false;
        }

        return ! preg_match(
            '/SQLSTATE|stack trace|vendor\/|\\\\|\.php\b|SELECT |INSERT |UPDATE |DELETE |syntax error|PDOException|QueryException|Illuminate\\\\/i',
            $text
        );
    }
}
