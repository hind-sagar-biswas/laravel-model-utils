<?php

declare(strict_types=1);

namespace HindBiswas\ModelUtils\Utils;

use InvalidArgumentException;

/**
 * Class StrUtil
 *
 * String utilities for secure random string generation and structured receipt/order number generation.
 */
class StrUtil
{
    /**
     * Generate a cryptographically secure random string of the specified length using the given character set.
     *
     * @param  int  $length  The length of the random string to generate.
     * @param  string  $characters  The character set to use for generating the random string.
     * @return string The generated random string, or an empty string if length is 0 or negative.
     *
     * @throws InvalidArgumentException When the character set is empty.
     */
    public static function random(
        int $length = 16,
        string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        if ($length <= 0) {
            return '';
        }

        $maxIndex = strlen($characters) - 1;

        if ($maxIndex < 0) {
            throw new InvalidArgumentException('Character set cannot be empty.');
        }

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, $maxIndex)];
        }

        return $string;
    }

    /**
     * Generate a structured receipt number with a prefix, formatted date segment, and random suffix.
     *
     * Resulting format: `{prefix}{date(midFormat)}{randomString}`
     * Total string length matches the requested `$length`.
     * The random suffix is generated from an unambiguous uppercase alphanumeric character set (excluding 0, 1, I, O).
     *
     * @param  string  $prefix  The prefix for the receipt number (e.g. 'REC-', 'INV').
     * @param  int  $length  The total length of the generated receipt number.
     * @param  string  $midFormat  The date format for the middle part (defaults to 'Y').
     * @return string The generated receipt number.
     *
     * @throws InvalidArgumentException When total length is less than or equal to the combined length of the prefix and formatted date segment.
     */
    public static function receipt(string $prefix, int $length, string $midFormat = 'Y'): string
    {
        $first = $prefix.now()->format($midFormat);
        $remainingLength = $length - strlen($first);

        if ($remainingLength <= 0) {
            throw new InvalidArgumentException('Length must be greater than the length of the prefix and midFormat.');
        }

        $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return $first.self::random($remainingLength, $charset);
    }
}
