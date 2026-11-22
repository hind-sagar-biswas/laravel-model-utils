<?php

use HindBiswas\ModelUtils\Utils\ListingParameters;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

enum HelperTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum HelperTestRole
{
    case Admin;
    case User;
}

it('creates ListingParameters instance using parse_params helper with current request', function () {
    $request = Request::create('/items', 'GET', [
        'search' => 'laptop',
        'per_page' => 15,
        'ft_status' => 'active',
    ]);

    app()->instance('request', $request);

    $params = parse_params();

    expect($params)->toBeInstanceOf(ListingParameters::class)
        ->and($params->search)->toBe('laptop')
        ->and($params->perPage)->toBe(15)
        ->and($params->get('status'))->toBe('active');
});

it('creates ListingParameters instance using parse_params helper with explicit request and options', function () {
    $request = Request::create('/items', 'GET', [
        'search' => 'phone',
        'sort_by' => 'price',
        'order' => 'desc',
        'ft_category' => 'electronics',
        'ft_hidden' => 'secret',
    ]);

    $params = parse_params(
        request: $request,
        allowedSortColumns: ['price', 'created_at'],
        allowedFilters: ['category']
    );

    expect($params)->toBeInstanceOf(ListingParameters::class)
        ->and($params->search)->toBe('phone')
        ->and($params->sortBy)->toBe('price')
        ->and($params->order)->toBe('desc')
        ->and($params->get('category'))->toBe('electronics')
        ->and($params->has('hidden'))->toBeFalse();
});

it('generates a receipt using receipt helper', function () {
    Carbon::setTestNow('2026-11-22');

    $code = receipt('INV-', 18);

    expect(strlen($code))->toBe(18)
        ->and(str_starts_with($code, 'INV-2026'))->toBeTrue();

    Carbon::setTestNow();
});

it('generates a receipt using receipt helper with custom midFormat', function () {
    Carbon::setTestNow('2026-11-22 15:00:00');

    $code = receipt('ORD/', 16, 'ym');

    // 'ORD/' (4) + '2611' (4) + 8 random = 16
    expect(strlen($code))->toBe(16)
        ->and(str_starts_with($code, 'ORD/2611'))->toBeTrue();

    Carbon::setTestNow();
});

it('converts enums to arrays using enum_array helper', function () {
    expect(enum_array(HelperTestStatus::class))->toBe(['active', 'inactive'])
        ->and(enum_array(HelperTestRole::class))->toBe(['Admin', 'User']);
});
