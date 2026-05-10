<?php

namespace App\Support;

use InvalidArgumentException;

final class LegalVaultPath
{
    public static function isSafeSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_\-\.\(\) ]+$/u', $segment);
    }

    /**
     * Normalisasi path relatif di dalam legal-vault (sama aturan dengan HRLegalController).
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }

        $parts = array_values(array_filter(explode('/', $path), fn ($p) => $p !== '' && $p !== '.'));
        foreach ($parts as $part) {
            if ($part === '..') {
                throw new InvalidArgumentException('Path tidak valid.');
            }
            if (! self::isSafeSegment($part)) {
                throw new InvalidArgumentException('Path tidak valid: karakter atau segmen tidak diizinkan.');
            }
        }

        if (count($parts) > 40) {
            throw new InvalidArgumentException('Path terlalu dalam (maks. 40 level).');
        }

        return implode('/', $parts);
    }
}
