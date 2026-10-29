<?php

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasIsActive
{
    /**
     * Initialize the trait to automatically register the cast for is_active.
     */
    public function initializeHasIsActive(): void
    {
        /** @var Model $this */
        $this->casts = array_merge([
            'is_active' => 'boolean',
        ], $this->casts ?? []);
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive records.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Toggle the active status of the model instance and save it.
     */
    public function toggleActive(): bool
    {
        /** @var Model $this */
        $this->is_active = ! $this->is_active;

        return $this->save();
    }
}
