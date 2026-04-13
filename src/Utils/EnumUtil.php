<?php

declare(strict_types=1);

namespace HindBiswas\ModelUtils\Utils;

use BackedEnum;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnitEnum;

class EnumUtil
{
    /**
     * Converts the enum to an array of values (for backed enums) or names (for pure enums).
     */
    public static function toArray(string $enumClass): array
    {
        self::validateEnumClass($enumClass);

        $isBacked = is_subclass_of($enumClass, BackedEnum::class);

        return array_map(
            fn (UnitEnum $case) => $isBacked ? $case->value : $case->name,
            $enumClass::cases()
        );
    }

    /**
     * Converts the enum to a comma-separated string of values.
     */
    public static function toCSV(string $enumClass): string
    {
        return implode(',', self::toArray($enumClass));
    }

    /**
     * Converts the enum to an associative array of identifier => label pairs.
     */
    public static function toAssocArray(string $enumClass): array
    {
        self::validateEnumClass($enumClass);
        $assoc = [];

        foreach ($enumClass::cases() as $case) {
            $assoc[self::getIdentifier($case)] = self::getLabel($case);
        }

        return $assoc;
    }

    /**
     * Converts the enum to an array of options with 'value' and 'label' keys.
     * Similar to Optionable trait but for pure enums, allowing custom label methods if defined.
     */
    public static function toOptions(string $enumClass): array
    {
        self::validateEnumClass($enumClass);

        return array_map(fn ($case) => [
            'value' => self::getIdentifier($case),
            'label' => self::getLabel($case),
        ], $enumClass::cases());
    }

    /**
     * Internal helper to resolve the value or name.
     */
    private static function getIdentifier(UnitEnum $case): string|int
    {
        return $case instanceof BackedEnum ? $case->value : $case->name;
    }

    /**
     * Internal helper to resolve the label.
     */
    private static function getLabel(UnitEnum $case): string
    {
        if (method_exists($case, 'label')) {
            /** @var mixed $case */
            return $case->label();
        }

        $identifier = $case instanceof BackedEnum ? (string) $case->value : $case->name;

        return Str::headline($identifier);
    }

    protected static function validateEnumClass(string $enumClass): void
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("Class '{$enumClass}' is not a valid enum.");
        }
    }
}
