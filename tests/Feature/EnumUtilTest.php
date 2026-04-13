<?php

use HindBiswas\ModelUtils\Utils\EnumUtil;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

enum ApprovalState
{
    case PendingReview;
    case InProgress;
}

enum TicketPriority: string
{
    case Low = 'low';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low Priority',
            self::High => 'High Priority',
        };
    }
}

it('converts backed enums to arrays csv assoc arrays and options', function () {
    expect(EnumUtil::toArray(OrderStatus::class))->toBe(['draft', 'published']);
    expect(EnumUtil::toCSV(OrderStatus::class))->toBe('draft,published');
    expect(EnumUtil::toAssocArray(OrderStatus::class))->toBe([
        'draft' => 'Draft',
        'published' => 'Published',
    ]);
    expect(EnumUtil::toOptions(OrderStatus::class))->toBe([
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'published', 'label' => 'Published'],
    ]);
});

it('converts pure enums using their names and fallback labels', function () {
    expect(EnumUtil::toArray(ApprovalState::class))->toBe(['PendingReview', 'InProgress']);
    expect(EnumUtil::toCSV(ApprovalState::class))->toBe('PendingReview,InProgress');
    expect(EnumUtil::toAssocArray(ApprovalState::class))->toBe([
        'PendingReview' => 'Pending Review',
        'InProgress' => 'In Progress',
    ]);
    expect(EnumUtil::toOptions(ApprovalState::class))->toBe([
        ['value' => 'PendingReview', 'label' => 'Pending Review'],
        ['value' => 'InProgress', 'label' => 'In Progress'],
    ]);
});

it('uses a custom label method when provided', function () {
    expect(EnumUtil::toAssocArray(TicketPriority::class))->toBe([
        'low' => 'Low Priority',
        'high' => 'High Priority',
    ]);

    expect(EnumUtil::toOptions(TicketPriority::class))->toBe([
        ['value' => 'low', 'label' => 'Low Priority'],
        ['value' => 'high', 'label' => 'High Priority'],
    ]);
});

it('rejects non-enum classes', function () {
    EnumUtil::toArray(stdClass::class);
})->throws(InvalidArgumentException::class, "Class 'stdClass' is not a valid enum.");
