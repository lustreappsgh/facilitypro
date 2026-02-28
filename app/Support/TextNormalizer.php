<?php

namespace App\Support;

class TextNormalizer
{
    private const MOJIBAKE_MAP = [
        // Common mojibake sequences for curly quotes/dashes/bullets.
        "\u{00E2}\u{20AC}\u{2122}" => "'",
        "\u{00E2}\u{20AC}\u{2018}" => "'",
        "\u{00E2}\u{20AC}\u{2019}" => "'",
        "\u{00E2}\u{20AC}\u{201C}" => '"',
        "\u{00E2}\u{20AC}\u{201D}" => '"',
        "\u{00E2}\u{20AC}\u{2013}" => "-",
        "\u{00E2}\u{20AC}\u{2014}" => "-",
        "\u{00E2}\u{20AC}\u{00A2}" => "-",
    ];

    public static function fixMojibake(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (str_contains($value, 'â') || str_contains($value, 'Ã')) {
            $converted = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return str_replace(array_keys(self::MOJIBAKE_MAP), array_values(self::MOJIBAKE_MAP), $value);
    }
}
