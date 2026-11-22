<?php

use HindBiswas\ModelUtils\Utils\StrUtil;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| StrUtil::random()
|--------------------------------------------------------------------------
*/

it('generates a random string of the specified length', function () {
    expect(strlen(StrUtil::random(16)))->toBe(16)
        ->and(strlen(StrUtil::random(32)))->toBe(32)
        ->and(strlen(StrUtil::random(1)))->toBe(1);
});

it('uses default alphanumeric characters for random string', function () {
    $random = StrUtil::random(100);

    expect($random)->toMatch('/^[0-9a-zA-Z]{100}$/');
});

it('generates random strings from custom character sets', function () {
    $digitsOnly = StrUtil::random(50, '0123456789');
    expect($digitsOnly)->toMatch('/^[0-9]{50}$/');

    $hex = StrUtil::random(40, 'abcdef0123456789');
    expect($hex)->toMatch('/^[a-f0-9]{40}$/');

    $binary = StrUtil::random(30, '01');
    expect($binary)->toMatch('/^[01]{30}$/');
});

it('returns an empty string when requested length is zero or negative', function () {
    expect(StrUtil::random(0))->toBe('')
        ->and(StrUtil::random(-5))->toBe('');
});

it('throws InvalidArgumentException when character set is empty', function () {
    StrUtil::random(10, '');
})->throws(InvalidArgumentException::class, 'Character set cannot be empty.');

it('generates distinct random strings across multiple calls', function () {
    $first = StrUtil::random(20);
    $second = StrUtil::random(20);

    expect($first)->not->toBe($second);
});

/*
|--------------------------------------------------------------------------
| StrUtil::receipt()
|--------------------------------------------------------------------------
*/

it('generates a receipt of the exact requested length with default year format', function () {
    Carbon::setTestNow('2026-05-15 12:00:00');

    $receipt = StrUtil::receipt('REC-', 16);

    expect(strlen($receipt))->toBe(16)
        ->and(str_starts_with($receipt, 'REC-2026'))->toBeTrue();

    Carbon::setTestNow();
});

it('generates a receipt with a custom mid date format', function () {
    Carbon::setTestNow('2026-11-22 14:30:00');

    $receipt = StrUtil::receipt('INV/', 20, 'ymd');

    // Prefix: 'INV/' (4), date '261122' (6), random (10) -> total 20
    expect(strlen($receipt))->toBe(20)
        ->and(str_starts_with($receipt, 'INV/261122'))->toBeTrue();

    Carbon::setTestNow();
});

it('uses an unambiguous character set for the random suffix in receipt', function () {
    Carbon::setTestNow('2026-01-01');

    $receipt = StrUtil::receipt('ORD-', 50);
    $suffix = substr($receipt, strlen('ORD-2026'));

    // Character set is 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789' (excludes 0, 1, I, O)
    expect($suffix)->toMatch('/^[A-HJKL-NP-Z2-9]+$/')
        ->and($suffix)->not->toContain('0')
        ->and($suffix)->not->toContain('1')
        ->and($suffix)->not->toContain('I')
        ->and($suffix)->not->toContain('O');

    Carbon::setTestNow();
});

it('throws InvalidArgumentException when length is less than or equal to prefix and date length', function () {
    Carbon::setTestNow('2026-01-01');

    // 'REC-' (4) + '2026' (4) = 8
    // Length 8 or less should throw
    StrUtil::receipt('REC-', 8);
})->throws(InvalidArgumentException::class, 'Length must be greater than the length of the prefix and midFormat.');

it('throws InvalidArgumentException when length is much shorter than prefix', function () {
    StrUtil::receipt('LONG-PREFIX-', 5);
})->throws(InvalidArgumentException::class, 'Length must be greater than the length of the prefix and midFormat.');

it('generates distinct receipt numbers across multiple calls', function () {
    $receiptA = StrUtil::receipt('TX-', 20);
    $receiptB = StrUtil::receipt('TX-', 20);

    expect($receiptA)->not->toBe($receiptB);
});
