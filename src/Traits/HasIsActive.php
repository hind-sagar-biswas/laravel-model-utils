<?php

declare(strict_types=1);

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasIsActive
 *
 * Automatically manages boolean casting, query scopes, and active toggling
 * for an `is_active` attribute on Eloquent models.
 *
 * @property bool $is_active
 *
 * @method static Builder<static>|static active() Scope a query to only include active records.
 * @method static Builder<static>|static inactive() Scope a query to only include inactive records.
 *
 * @mixin Model
 */
trait HasIsActive
{
    /**
     * Initialize the trait to automatically register the boolean cast for is_active.
     *
     * Preserves any existing casts defined on the model.
     */
    public function initializeHasIsActive(): void
    {
        /** @var Model $this */
        $this->casts = array_merge([
            'is_active' => 'boolean',
        ], $this->casts ?? []);
    }

    /**
     * Scope a query to only include active records (is_active = true).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  The query builder instance.
     * @return Builder<TModel>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive records (is_active = false).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  The query builder instance.
     * @return Builder<TModel>
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Toggle the active status of the model instance and persist it.
     *
     * @return bool True if saving was successful, false otherwise.
     */
    public function toggleActive(): bool
    {
        /** @var Model $this */
        $this->is_active = ! $this->is_active;

        return $this->save();
    }
}
