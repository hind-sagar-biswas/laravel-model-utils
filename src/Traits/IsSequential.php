<?php

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @method static void creating(\Closure|string $callback)
 * @method static Builder query()
 */
trait IsSequential
{
    public static function bootIsSequential(): void
    {
        static::creating(function (Model $model) {
            if (is_null($model->sequence)) {
                $max = static::scopedTo($model)->max('sequence');
                $model->sequence = ((int) $max) + 1;
            }
        });
    }

    public function scopeSequenced(Builder $query): Builder
    {
        return $query->orderBy('sequence', 'asc');
    }

    public function moveUp(): bool
    {
        return $this->moveBy(-1);
    }

    public function moveDown(): bool
    {
        return $this->moveBy(1);
    }

    /**
     * Swap sequence with another model in the same group.
     * Returns false if not swappable; throws (and rolls back) on write failure.
     */
    public function swapSequence(Model $target): bool
    {
        /** @var Model $this */
        if (! $target instanceof static || $this->is($target)) {
            return false;
        }

        return DB::transaction(function () use ($target) {
            /** @var Model $this */
            $keyName = $this->getKeyName();

            // Lock both rows in key order to avoid deadlocks
            $rows = static::sequenceBaseQuery()
                ->whereIn($keyName, [$this->getKey(), $target->getKey()])
                ->orderBy($keyName)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Model $m) => (string) $m->getKey());

            $a = $rows->get((string) $this->getKey());
            $b = $rows->get((string) $target->getKey());

            if (! $a || ! $b) {
                return false;
            }

            foreach ($this->sequenceScopeColumns() as $col) {
                if (! static::sameValue($a->getAttribute($col), $b->getAttribute($col))) {
                    return false;
                }
            }

            $this->exchange($a, $b);

            $this->syncSequenceFrom($a);
            $target->syncSequenceFrom($b);

            return true;
        });
    }

    protected function moveBy(int $direction): bool
    {
        return DB::transaction(function () use ($direction) {
            /** @var Model $this */
            $rows = static::scopedTo($this)
                ->orderBy($this->getKeyName())
                ->lockForUpdate()
                ->get()
                ->sortBy('sequence')
                ->values();

            $index = $rows->search(function (Model $m) {
                /** @var Model $this */
                return (string) $m->getKey() === (string) $this->getKey();
            });

            if ($index === false) {
                return false;
            }

            $self = $rows->get($index);
            $target = $rows->get($index + $direction);

            if (! $target) {
                return false;
            }

            $this->exchange($self, $target);
            $this->syncSequenceFrom($self);

            return true;
        });
    }

    /** Swap via a temp value so a unique (scope, sequence) index is never violated. */
    protected function exchange(Model $a, Model $b): void
    {
        $seqA = $a->sequence;
        $seqB = $b->sequence;

        if ($seqA == $seqB) {
            return;
        }

        $tmp = ((int) static::scopedTo($a)->max('sequence')) + 1;

        static::writeSequence($a, $tmp);
        static::writeSequence($b, $seqA);
        static::writeSequence($a, $seqB);
    }

    protected static function writeSequence(Model $model, int $value): void
    {
        // Query-builder update: no events, no dirty-attribute side effects,
        // and updated_at is NOT touched.
        $affected = static::sequenceBaseQuery()
            ->whereKey($model->getKey())
            ->update(['sequence' => $value]);

        if ($affected === 0) {
            throw new RuntimeException('Failed to update sequence for '.static::class.'#'.$model->getKey());
        }

        $model->setAttribute('sequence', $value);
        $model->syncOriginalAttribute('sequence');
    }

    protected function syncSequenceFrom(Model $fresh): void
    {
        /** @var Model $this */
        $this->setAttribute('sequence', $fresh->sequence);
        $this->syncOriginalAttribute('sequence');
    }

    protected function sequenceScopeColumns(): array
    {
        return property_exists($this, 'sequentialUniqueWith') ? (array) $this->sequentialUniqueWith : [];
    }

    protected static function sequenceBaseQuery(): Builder
    {
        $query = static::query();

        // Trashed rows still occupy a sequence slot
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    protected static function scopedTo(Model $model): Builder
    {
        $query = static::sequenceBaseQuery();

        foreach ($model->sequenceScopeColumns() as $col) {
            $query->where($col, $model->getAttribute($col));
        }

        return $query;
    }

    protected static function sameValue(mixed $x, mixed $y): bool
    {
        if (is_null($x) || is_null($y)) {
            return is_null($x) && is_null($y);
        }

        return (string) $x === (string) $y;
    }
}
