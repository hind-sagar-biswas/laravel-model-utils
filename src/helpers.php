<?php

declare(strict_types=1);

use HindBiswas\ModelUtils\Utils\EnumUtil;
use HindBiswas\ModelUtils\Utils\ListingParameters;
use HindBiswas\ModelUtils\Utils\StrUtil;
use Illuminate\Http\Request;

if (! function_exists('parse_params')) {
    /**
     * Parse an HTTP request into a ListingParameters instance.
     *
     * If no request is provided, resolves the current request from the container.
     *
     * @param  Request|null  $request  The HTTP request, or null to resolve current request.
     * @param  array<array-key, string|\Closure(\Illuminate\Database\Eloquent\Builder<*>, string): void>  $allowedSortColumns  Allowed sort columns or Closure sorters.
     * @param  array<string>|null  $allowedFilters  Optional whitelist of filter keys (without 'ft_' prefix).
     */
    function parse_params(
        ?Request $request = null,
        array $allowedSortColumns = [],
        ?array $allowedFilters = null
    ): ListingParameters {
        /** @var Request $req */
        $req = $request ?? request();

        return ListingParameters::parseRequest($req, $allowedSortColumns, $allowedFilters);
    }
}

if (! function_exists('receipt')) {
    /**
     * Generate a structured receipt number with a prefix, formatted date segment, and random suffix.
     *
     * Resulting format: `{prefix}{date(midFormat)}{randomSuffix}`
     * Total length matches the specified `$length`.
     *
     * @param  string  $prefix  The prefix for the receipt number (e.g. 'REC-', 'INV').
     * @param  int  $length  The total length of the generated receipt number.
     * @param  string  $midFormat  The date format for the middle part (defaults to 'Y').
     */
    function receipt(string $prefix, int $length, string $midFormat = 'Y'): string
    {
        return StrUtil::receipt($prefix, $length, $midFormat);
    }
}

if (! function_exists('enum_array')) {
    /**
     * Convert an enum class to an array of values (for backed enums) or names (for pure enums).
     *
     * @param  string  $enumClass  The enum class-string.
     * @return array<int|string>
     */
    function enum_array(string $enumClass): array
    {
        return EnumUtil::toArray($enumClass);
    }
}
