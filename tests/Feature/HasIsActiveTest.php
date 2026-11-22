<?php

use HindBiswas\ModelUtils\Traits\HasIsActive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActiveTestItem extends Model
{
    use HasIsActive;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'active_test_items';
}

class ActiveTestCustomCastItem extends Model
{
    use HasIsActive;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'active_test_items';

    protected $casts = [
        'metadata' => 'array',
        'priority' => 'integer',
    ];
}

beforeEach(function () {
    Schema::create('active_test_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->text('metadata')->nullable();
        $table->integer('priority')->default(0);
    });
});

afterEach(function () {
    Schema::dropIfExists('active_test_items');
});

it('automatically registers the boolean cast for is_active', function () {
    $itemTrue = ActiveTestItem::create(['name' => 'Item 1', 'is_active' => 1]);
    $itemFalse = ActiveTestItem::create(['name' => 'Item 2', 'is_active' => 0]);
    $itemStringTrue = ActiveTestItem::create(['name' => 'Item 3', 'is_active' => '1']);
    $itemStringFalse = ActiveTestItem::create(['name' => 'Item 4', 'is_active' => '0']);

    expect($itemTrue->fresh()->is_active)->toBeTrue()
        ->and($itemFalse->fresh()->is_active)->toBeFalse()
        ->and($itemStringTrue->fresh()->is_active)->toBeTrue()
        ->and($itemStringFalse->fresh()->is_active)->toBeFalse();
});

it('preserves existing model casts when initializing HasIsActive', function () {
    $item = ActiveTestCustomCastItem::create([
        'name' => 'Configured Item',
        'is_active' => 1,
        'metadata' => ['key' => 'value'],
        'priority' => '42',
    ]);

    $fresh = $item->fresh();

    expect($fresh->is_active)->toBeTrue()
        ->and($fresh->metadata)->toBe(['key' => 'value'])
        ->and($fresh->priority)->toBe(42)
        ->and($fresh->getCasts())->toHaveKey('is_active', 'boolean')
        ->and($fresh->getCasts())->toHaveKey('metadata', 'array')
        ->and($fresh->getCasts())->toHaveKey('priority', 'integer');
});

it('scopes query to only active records using scopeActive', function () {
    ActiveTestItem::create(['name' => 'Active A', 'is_active' => true]);
    ActiveTestItem::create(['name' => 'Active B', 'is_active' => true]);
    ActiveTestItem::create(['name' => 'Inactive C', 'is_active' => false]);
    ActiveTestItem::create(['name' => 'Inactive D', 'is_active' => false]);

    $activeNames = ActiveTestItem::query()->active()->pluck('name')->all();

    expect($activeNames)->toBe(['Active A', 'Active B']);
});

it('scopes query to only inactive records using scopeInactive', function () {
    ActiveTestItem::create(['name' => 'Active A', 'is_active' => true]);
    ActiveTestItem::create(['name' => 'Active B', 'is_active' => true]);
    ActiveTestItem::create(['name' => 'Inactive C', 'is_active' => false]);
    ActiveTestItem::create(['name' => 'Inactive D', 'is_active' => false]);

    $inactiveNames = ActiveTestItem::query()->inactive()->pluck('name')->all();

    expect($inactiveNames)->toBe(['Inactive C', 'Inactive D']);
});

it('toggles active status from active to inactive and persists to database', function () {
    $item = ActiveTestItem::create(['name' => 'Toggle Item', 'is_active' => true]);

    expect($item->is_active)->toBeTrue();

    $result = $item->toggleActive();

    expect($result)->toBeTrue()
        ->and($item->is_active)->toBeFalse()
        ->and($item->fresh()->is_active)->toBeFalse();
});

it('toggles active status from inactive to active and persists to database', function () {
    $item = ActiveTestItem::create(['name' => 'Toggle Item', 'is_active' => false]);

    expect($item->is_active)->toBeFalse();

    $result = $item->toggleActive();

    expect($result)->toBeTrue()
        ->and($item->is_active)->toBeTrue()
        ->and($item->fresh()->is_active)->toBeTrue();
});

it('toggles active status when initially false or null', function () {
    $item = new ActiveTestItem(['name' => 'New Item']);
    $item->is_active = false;
    $item->save();

    $item->toggleActive();

    expect($item->fresh()->is_active)->toBeTrue();
});

it('can combine active and inactive scopes with other query constraints', function () {
    ActiveTestItem::create(['name' => 'Tech Active', 'is_active' => true, 'priority' => 10]);
    ActiveTestItem::create(['name' => 'News Active', 'is_active' => true, 'priority' => 5]);
    ActiveTestItem::create(['name' => 'Tech Inactive', 'is_active' => false, 'priority' => 10]);

    $techActive = ActiveTestItem::query()
        ->active()
        ->where('priority', 10)
        ->pluck('name')
        ->all();

    expect($techActive)->toBe(['Tech Active']);

    $techInactive = ActiveTestItem::query()
        ->inactive()
        ->where('priority', 10)
        ->pluck('name')
        ->all();

    expect($techInactive)->toBe(['Tech Inactive']);
});
