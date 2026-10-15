<?php

namespace App\Helpers;

/**
 * Readable companions for the admin-chosen theme colours.
 *
 * The theme settings let the platform owner pick any accent — a bright lime
 * (the old default) or a deep red (the LIBServices preset). Text sitting on
 * the accent, or accent-coloured text sitting on the dark brand colour, has to
 * stay readable either way, so the pages ask here instead of hard-coding ink.
 */
class ThemeContrast
{
    /** Text colour to put ON a background of $hex: near-black or white. */
    public static function ink(string $hex, string $dark = '#111827', string $light = '#ffffff'): string
    {
        // Whichever has more contrast, with a slight lean toward white because
        // white on a saturated button reads as the "designed" choice.
        $l = self::luminance($hex);
        $onLight = (1.05 / ($l + 0.05)) * 1.15;
        $onDark = ($l + 0.05) / (self::luminance($dark) + 0.05);

        return $onLight >= $onDark ? $light : $dark;
    }

    /**
     * $hex as text on a dark background (header, footer, hero). Deep colours
     * are lifted toward white until they read; bright ones are left alone.
     */
    public static function onDark(string $hex): string
    {
        $rgb = self::rgb($hex);
        if (! $rgb) {
            return $hex;
        }
        for ($mix = 0.0; $mix <= 0.7 && self::luminanceRgb($rgb, $mix) < 0.3; $mix += 0.05) {
            // keep lifting
        }
        $mix = min($mix, 0.7);

        return sprintf('#%02x%02x%02x', ...array_map(fn ($c) => (int) round($c + (255 - $c) * $mix), $rgb));
    }

    /** WCAG relative luminance, 0 (black) to 1 (white). */
    public static function luminance(string $hex): float
    {
        $rgb = self::rgb($hex);

        return $rgb ? self::luminanceRgb($rgb, 0) : 0.0;
    }

    private static function luminanceRgb(array $rgb, float $mixWhite): float
    {
        [$r, $g, $b] = array_map(function ($c) use ($mixWhite) {
            $c = ($c + (255 - $c) * $mixWhite) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function rgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return null;
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
