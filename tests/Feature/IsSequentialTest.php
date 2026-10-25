<?php

use HindBiswas\ModelUtils\Traits\IsSequential;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SequentialItem extends Model
{
    use IsSequential;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'sequential_items';
}

class SequentialObservedItem extends Model
{
    use IsSequential;

    public static int $updatingCount = 0;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'sequential_items';

    protected static function booted(): void
    {
        static::updating(function () {
            static::$updatingCount++;
        });
    }
}

class SequentialTask extends Model
{
    use IsSequential;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'sequential_tasks';

    protected array $sequentialUniqueWith = ['project_id'];
}

class SequentialSoftItem extends Model
{
    use IsSequential, SoftDeletes;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'sequential_soft_items';
}

class SequentialUniqueItem extends Model
{
    use IsSequential;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'sequential_unique_items';
}

/** Names ordered by sequence. */
function seqNames($query): array
{
    return $query->orderBy('sequence')->orderBy('id')->pluck('name')->all();
}

/** [name => sequence], ordered by id. */
function seqMap($query): array
{
    return $query->orderBy('id')->pluck('sequence', 'name')->all();
}

/** Creates records one by one, returns them keyed by name. */
function makeSequential(string $class, array $names, array $extra = []): array
{
    $items = [];

    foreach ($names as $name) {
        $items[$name] = $class::create(['name' => $name] + $extra);
    }

    return $items;
}

beforeEach(function () {
    Schema::create('sequential_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('sequence');
    });

    Schema::create('sequential_tasks', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('project_id')->nullable();
        $table->string('name');
        $table->integer('sequence');
    });

    Schema::create('sequential_soft_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('sequence');
        $table->softDeletes();
    });

    Schema::create('sequential_unique_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('sequence');
        $table->unique('sequence');
    });

    SequentialObservedItem::$updatingCount = 0;
});

afterEach(function () {
    Schema::dropIfExists('sequential_unique_items');
    Schema::dropIfExists('sequential_soft_items');
    Schema::dropIfExists('sequential_tasks');
    Schema::dropIfExists('sequential_items');
});

/*
|--------------------------------------------------------------------------
| Auto-assignment on create
|--------------------------------------------------------------------------
*/

it('assigns sequence 1 to the first record', function () {
    $item = SequentialItem::create(['name' => 'A']);

    expect($item->sequence)->toBe(1);
});

it('increments the sequence for subsequent records', function () {
    makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

it('keeps an explicitly provided sequence and continues from it', function () {
    $explicit = SequentialItem::create(['name' => 'A', 'sequence' => 10]);
    $next = SequentialItem::create(['name' => 'B']);

    expect($explicit->sequence)->toBe(10);
    expect($next->sequence)->toBe(11);
});

it('treats an explicit zero as a real value, not as unset', function () {
    $item = SequentialItem::create(['name' => 'A', 'sequence' => 0]);

    expect($item->fresh()->sequence)->toBe(0);
});

it('auto-assigns when sequence is explicitly null', function () {
    $item = SequentialItem::create(['name' => 'A', 'sequence' => null]);

    expect($item->sequence)->toBe(1);
});

it('uses max plus one and does not fill gaps', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);
    $items['B']->delete();

    $d = SequentialItem::create(['name' => 'D']);

    expect($d->sequence)->toBe(4);
});

it('reuses the number when the highest record is hard deleted', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B']);
    $items['B']->delete();

    $c = SequentialItem::create(['name' => 'C']);

    expect($c->sequence)->toBe(2);
});

it('does not modify existing rows when creating', function () {
    makeSequential(SequentialItem::class, ['A', 'B']);
    SequentialItem::create(['name' => 'C']);

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

/*
|--------------------------------------------------------------------------
| Scoped sequences (sequentialUniqueWith)
|--------------------------------------------------------------------------
*/

it('numbers each scope group independently', function () {
    SequentialTask::create(['name' => 'p1-a', 'project_id' => 1]);
    SequentialTask::create(['name' => 'p1-b', 'project_id' => 1]);
    SequentialTask::create(['name' => 'p2-a', 'project_id' => 2]);
    SequentialTask::create(['name' => 'p1-c', 'project_id' => 1]);
    SequentialTask::create(['name' => 'p2-b', 'project_id' => 2]);

    expect(seqMap(SequentialTask::query()))->toEqual([
        'p1-a' => 1,
        'p1-b' => 2,
        'p2-a' => 1,
        'p1-c' => 3,
        'p2-b' => 2,
    ]);
});

it('groups null scope values together', function () {
    SequentialTask::create(['name' => 'n-a', 'project_id' => null]);
    SequentialTask::create(['name' => 'n-b', 'project_id' => null]);
    SequentialTask::create(['name' => 'p1-a', 'project_id' => 1]);

    expect(seqMap(SequentialTask::query()))->toEqual([
        'n-a' => 1,
        'n-b' => 2,
        'p1-a' => 1,
    ]);
});

it('ignores scope columns on models that do not declare any', function () {
    $a = SequentialItem::create(['name' => 'A']);
    $b = SequentialItem::create(['name' => 'B']);

    expect([$a->sequence, $b->sequence])->toBe([1, 2]);
});

/*
|--------------------------------------------------------------------------
| scopeSequenced
|--------------------------------------------------------------------------
*/

it('orders by sequence ascending with the sequenced scope', function () {
    SequentialItem::create(['name' => 'C', 'sequence' => 3]);
    SequentialItem::create(['name' => 'A', 'sequence' => 1]);
    SequentialItem::create(['name' => 'B', 'sequence' => 2]);

    $names = SequentialItem::query()->sequenced()->pluck('name')->all();

    expect($names)->toBe(['A', 'B', 'C']);
});

/*
|--------------------------------------------------------------------------
| moveUp / moveDown
|--------------------------------------------------------------------------
*/

it('moves a record up by swapping with its predecessor', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    expect($items['B']->moveUp())->toBeTrue();

    expect(seqNames(SequentialItem::query()))->toBe(['B', 'A', 'C']);
    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 2, 'B' => 1, 'C' => 3]);
});

it('moves a record down by swapping with its successor', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    expect($items['B']->moveDown())->toBeTrue();

    expect(seqNames(SequentialItem::query()))->toBe(['A', 'C', 'B']);
});

it('returns false and changes nothing when moving the first record up', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    expect($items['A']->moveUp())->toBeFalse();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

it('returns false and changes nothing when moving the last record down', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    expect($items['C']->moveDown())->toBeFalse();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

it('returns false for a single-record group', function () {
    $a = SequentialItem::create(['name' => 'A']);

    expect($a->moveUp())->toBeFalse();
    expect($a->moveDown())->toBeFalse();
});

it('updates the in-memory sequence of the moved model and leaves it clean', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    $items['C']->moveUp();

    expect($items['C']->sequence)->toBe(2);
    expect($items['C']->isDirty('sequence'))->toBeFalse();
});

it('only changes the two swapped rows', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C', 'D', 'E']);

    $items['D']->moveUp();

    expect(seqMap(SequentialItem::query()))->toEqual([
        'A' => 1, 'B' => 2, 'C' => 4, 'D' => 3, 'E' => 5,
    ]);
});

it('moves correctly when sequences have gaps', function () {
    SequentialItem::create(['name' => 'A', 'sequence' => 10]);
    $b = SequentialItem::create(['name' => 'B', 'sequence' => 20]);
    SequentialItem::create(['name' => 'C', 'sequence' => 30]);

    $b->moveUp();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 20, 'B' => 10, 'C' => 30]);
});

it('moveUp then moveDown restores the original order', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    $items['B']->moveUp();
    $items['B']->moveDown();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

it('moves using fresh database state, not stale in-memory sequences', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    // Reorder behind the model's back: A=1, C=2, B=3. $items['C'] still says 3.
    SequentialItem::query()->whereKey($items['B']->id)->update(['sequence' => 3]);
    SequentialItem::query()->whereKey($items['C']->id)->update(['sequence' => 2]);

    expect($items['C']->moveUp())->toBeTrue();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 2, 'B' => 3, 'C' => 1]);
    expect($items['C']->sequence)->toBe(1);
});

it('returns false when the model no longer exists in the database', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);
    $items['B']->delete();

    expect($items['B']->moveUp())->toBeFalse();
    expect($items['B']->moveDown())->toBeFalse();
    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'C' => 3]);
});

it('does not persist unrelated dirty attributes when moving', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B']);

    $items['B']->name = 'Changed';
    $items['B']->moveUp();

    expect(SequentialItem::find($items['B']->id)->name)->toBe('B');
    expect($items['B']->isDirty('name'))->toBeTrue();
});

it('does not fire model updating events when moving or swapping', function () {
    $items = makeSequential(SequentialObservedItem::class, ['A', 'B', 'C']);

    $items['B']->moveUp();
    $items['C']->moveDown();
    $items['A']->swapSequence($items['C']);

    expect(SequentialObservedItem::$updatingCount)->toBe(0);
});

it('keeps moves inside the scope group', function () {
    $p1 = makeSequential(SequentialTask::class, ['p1-a', 'p1-b'], ['project_id' => 1]);
    $p2 = makeSequential(SequentialTask::class, ['p2-a', 'p2-b'], ['project_id' => 2]);

    expect($p2['p2-a']->moveUp())->toBeFalse();
    expect($p1['p1-b']->moveDown())->toBeFalse();

    expect($p2['p2-b']->moveUp())->toBeTrue();

    expect(seqMap(SequentialTask::query()->where('project_id', 1)))->toEqual(['p1-a' => 1, 'p1-b' => 2]);
    expect(seqMap(SequentialTask::query()->where('project_id', 2)))->toEqual(['p2-a' => 2, 'p2-b' => 1]);
});

it('moves within the null scope group', function () {
    $n = makeSequential(SequentialTask::class, ['n-a', 'n-b'], ['project_id' => null]);
    makeSequential(SequentialTask::class, ['p1-a'], ['project_id' => 1]);

    expect($n['n-b']->moveUp())->toBeTrue();

    expect(seqNames(SequentialTask::query()->whereNull('project_id')))->toBe(['n-b', 'n-a']);
    expect(seqMap(SequentialTask::query()->where('project_id', 1)))->toEqual(['p1-a' => 1]);
});

/*
|--------------------------------------------------------------------------
| swapSequence
|--------------------------------------------------------------------------
*/

it('swaps sequences of two non-adjacent records', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C', 'D']);

    expect($items['A']->swapSequence($items['D']))->toBeTrue();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 4, 'B' => 2, 'C' => 3, 'D' => 1]);
});

it('updates the in-memory sequence of both models and leaves them clean', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    $items['A']->swapSequence($items['C']);

    expect($items['A']->sequence)->toBe(3);
    expect($items['C']->sequence)->toBe(1);
    expect($items['A']->isDirty('sequence'))->toBeFalse();
    expect($items['C']->isDirty('sequence'))->toBeFalse();
});

it('returns false when swapping a record with itself', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B']);

    expect($items['A']->swapSequence($items['A']))->toBeFalse();
    expect($items['A']->swapSequence(SequentialItem::find($items['A']->id)))->toBeFalse();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2]);
});

it('returns false when the target is a different model class', function () {
    $item = SequentialItem::create(['name' => 'A']);
    $task = SequentialTask::create(['name' => 'T', 'project_id' => 1]);

    expect($item->swapSequence($task))->toBeFalse();

    expect($item->fresh()->sequence)->toBe(1);
    expect($task->fresh()->sequence)->toBe(1);
});

it('returns false when the records are in different scope groups', function () {
    $a = SequentialTask::create(['name' => 'p1-a', 'project_id' => 1]);
    SequentialTask::create(['name' => 'p1-b', 'project_id' => 1]);
    $c = SequentialTask::create(['name' => 'p2-a', 'project_id' => 2]);

    expect($a->swapSequence($c))->toBeFalse();

    expect(seqMap(SequentialTask::query()))->toEqual(['p1-a' => 1, 'p1-b' => 2, 'p2-a' => 1]);
});

it('allows swapping within the null scope group', function () {
    $items = makeSequential(SequentialTask::class, ['n-a', 'n-b'], ['project_id' => null]);

    expect($items['n-a']->swapSequence($items['n-b']))->toBeTrue();

    expect(seqMap(SequentialTask::query()))->toEqual(['n-a' => 2, 'n-b' => 1]);
});

it('refuses to swap a null-scope record with a non-null-scope record', function () {
    $a = SequentialTask::create(['name' => 'n-a', 'project_id' => null]);
    $b = SequentialTask::create(['name' => 'p1-a', 'project_id' => 1]);

    expect($a->swapSequence($b))->toBeFalse();
    expect($b->swapSequence($a))->toBeFalse();
});

it('returns false when the target no longer exists', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);
    $items['C']->delete();

    expect($items['A']->swapSequence($items['C']))->toBeFalse();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2]);
});

it('returns false when the record itself no longer exists', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B']);
    $items['A']->delete();

    expect($items['A']->swapSequence($items['B']))->toBeFalse();
    expect($items['B']->fresh()->sequence)->toBe(2);
});

it('swaps using fresh database values, not stale in-memory ones', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    SequentialItem::query()->whereKey($items['A']->id)->update(['sequence' => 5]);
    SequentialItem::query()->whereKey($items['C']->id)->update(['sequence' => 7]);

    expect($items['A']->swapSequence($items['C']))->toBeTrue();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 7, 'B' => 2, 'C' => 5]);
    expect($items['A']->sequence)->toBe(7);
    expect($items['C']->sequence)->toBe(5);
});

it('does not persist unrelated dirty attributes when swapping', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B']);

    $items['A']->name = 'Changed';
    $items['A']->swapSequence($items['B']);

    expect(SequentialItem::find($items['A']->id)->name)->toBe('A');
});

it('is a no-op that still returns true when both records share the same sequence (current behavior)', function () {
    // Characterization test: documents that duplicates can't be separated via swap.
    $a = SequentialItem::create(['name' => 'A', 'sequence' => 1]);
    $b = SequentialItem::create(['name' => 'B', 'sequence' => 1]);

    expect($a->swapSequence($b))->toBeTrue();

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 1]);
});

/*
|--------------------------------------------------------------------------
| Unique index on sequence (swap must never violate it mid-way)
|--------------------------------------------------------------------------
*/

it('swaps under a unique sequence index without violating it', function () {
    $items = makeSequential(SequentialUniqueItem::class, ['A', 'B', 'C']);

    expect($items['A']->swapSequence($items['C']))->toBeTrue();

    expect(seqMap(SequentialUniqueItem::query()))->toEqual(['A' => 3, 'B' => 2, 'C' => 1]);
});

it('moves under a unique sequence index without violating it', function () {
    $items = makeSequential(SequentialUniqueItem::class, ['A', 'B', 'C']);

    expect($items['B']->moveUp())->toBeTrue();
    expect($items['B']->moveDown())->toBeTrue();
    expect($items['B']->moveDown())->toBeTrue();

    expect(seqNames(SequentialUniqueItem::query()))->toBe(['A', 'C', 'B']);
});

/*
|--------------------------------------------------------------------------
| Soft deletes
|--------------------------------------------------------------------------
*/

it('counts soft-deleted rows when assigning the next sequence', function () {
    $items = makeSequential(SequentialSoftItem::class, ['A', 'B']);
    $items['B']->delete();

    $c = SequentialSoftItem::create(['name' => 'C']);

    expect($c->sequence)->toBe(3);
});

it('treats soft-deleted rows as occupying slots when moving (current behavior)', function () {
    // Characterization test: moveDown on the last *visible* record swaps with
    // the trashed row after it. Change this test if you decide to skip trashed rows.
    $items = makeSequential(SequentialSoftItem::class, ['A', 'B']);
    $items['B']->delete();

    expect($items['A']->moveDown())->toBeTrue();

    expect(SequentialSoftItem::withTrashed()->orderBy('id')->pluck('sequence', 'name')->all())
        ->toEqual(['A' => 2, 'B' => 1]);
});

/*
|--------------------------------------------------------------------------
| Transactions
|--------------------------------------------------------------------------
*/

it('works inside an outer transaction and commits with it', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    DB::transaction(function () use ($items) {
        $items['C']->moveUp();
        $items['C']->moveUp();
    });

    expect(seqNames(SequentialItem::query()))->toBe(['C', 'A', 'B']);
});

it('rolls back database changes when the outer transaction fails', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    try {
        DB::transaction(function () use ($items) {
            $items['C']->moveUp();

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);

    // Known limitation: the in-memory model keeps the rolled-back value.
    expect($items['C']->sequence)->toBe(2);
    expect($items['C']->fresh()->sequence)->toBe(3);
});

it('rolls back swapSequence when the outer transaction fails', function () {
    $items = makeSequential(SequentialItem::class, ['A', 'B', 'C']);

    try {
        DB::transaction(function () use ($items) {
            $items['A']->swapSequence($items['C']);

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    expect(seqMap(SequentialItem::query()))->toEqual(['A' => 1, 'B' => 2, 'C' => 3]);
});

/*
|--------------------------------------------------------------------------
| Randomized invariant check
|--------------------------------------------------------------------------
*/

it('keeps sequences unique and matches a reference model across many random operations', function () {
    mt_srand(1234);

    $names = ['A', 'B', 'C', 'D', 'E', 'F'];
    $items = makeSequential(SequentialItem::class, $names);
    $order = $names; // reference model: position in array == rank

    for ($i = 0; $i < 200; $i++) {
        $name = $names[mt_rand(0, count($names) - 1)];
        $pos = array_search($name, $order, true);
        $op = mt_rand(0, 2);

        if ($op === 0) { // up
            $result = $items[$name]->moveUp();
            expect($result)->toBe($pos > 0);

            if ($pos > 0) {
                [$order[$pos - 1], $order[$pos]] = [$order[$pos], $order[$pos - 1]];
            }
        } elseif ($op === 1) { // down
            $result = $items[$name]->moveDown();
            expect($result)->toBe($pos < count($order) - 1);

            if ($pos < count($order) - 1) {
                [$order[$pos + 1], $order[$pos]] = [$order[$pos], $order[$pos + 1]];
            }
        } else { // swap with a different random record
            do {
                $other = $names[mt_rand(0, count($names) - 1)];
            } while ($other === $name);

            expect($items[$name]->swapSequence($items[$other]))->toBeTrue();

            $otherPos = array_search($other, $order, true);
            [$order[$pos], $order[$otherPos]] = [$order[$otherPos], $order[$pos]];
        }

        expect(seqNames(SequentialItem::query()))->toBe($order);
        expect(SequentialItem::query()->pluck('sequence')->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6]);
    }
});