<?php

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Process filtering and searching dynamically.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // 1. Handle Exact Matches (and Relationships)
        $filterable = property_exists($this, 'filterable') ? $this->filterable : [];

        foreach ($filterable as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $this->applyFilter($query, $field, $filters[$field]);
            }
        }

        // 2. Handle Generic Search
        $searchable = property_exists($this, 'searchable') ? $this->searchable : [];
        $search = $filters['search'] ?? null;

        if ($search && ! empty($searchable)) {
            $query->where(function (Builder $q) use ($search, $searchable) {
                foreach ($searchable as $field) {
                    $this->applySearch($q, $field, $search);
                }
            });
        }

        return $query;
    }

    /**
     * Apply a "Where" filter, supporting dot-notation for relationships.
     */
    protected function applyFilter(Builder $query, string $field, mixed $value): void
    {
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $column = array_pop($parts);
            $relation = implode('.', $parts); // supports 'owner.user' → nested whereHas

            $query->whereHas($relation, fn ($q) => $q->where($column, $value));
        } else {
            $query->where($query->getModel()->getTable().'.'.$field, $value);
        }
    }

    /**
     * Apply a "Like" search, supporting dot-notation for relationships.
     */
    protected function applySearch(Builder $query, string $field, string $search): void
    {
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $column = array_pop($parts);
            $relation = implode('.', $parts);

            $query->orWhereHas($relation, fn ($q) => $q->where($column, 'like', "%{$search}%"));
        } else {
            $query->orWhere($query->getModel()->getTable().'.'.$field, 'like', "%{$search}%");
        }
    }
}
