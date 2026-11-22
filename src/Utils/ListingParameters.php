<?php

declare(strict_types=1);

namespace HindBiswas\ModelUtils\Utils;

use Closure;
use HindBiswas\ModelUtils\Traits\Filterable;
use HindBiswas\ModelUtils\Traits\IsSequential;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * Class ListingParameters
 *
 * Encapsulates, sanitizes, and applies query parameters (search query, pagination limit,
 * sort column and direction, and 'ft_' prefixed filter criteria) to Eloquent query builders.
 */
class ListingParameters
{
    /**
     * Create a new ListingParameters instance.
     *
     * @param  string|null  $search  The search query keyword.
     * @param  int  $perPage  The number of records per page.
     * @param  string|null  $sortBy  The column or sorter alias to sort by.
     * @param  string|null  $order  The sort direction ('asc' or 'desc').
     * @param  array<string, mixed>  $filters  Key-value pairs of filter criteria (without 'ft_' prefix).
     * @param  array<string, Closure(Builder<*>, string): void>  $sorters  Custom sorter closures keyed by column or alias.
     */
    public function __construct(
        public ?string $search,
        public int $perPage,
        public ?string $sortBy,
        public ?string $order,
        public array $filters = [],
        public array $sorters = []
    ) {
        $this->filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Determine if a filter or search parameter exists and is not empty.
     *
     * @param  string  $key  The parameter key ('search' or a filter key).
     */
    public function has(string $key): bool
    {
        if ($key === 'search') {
            return ! empty($this->search);
        }

        return array_key_exists($key, $this->filters);
    }

    /**
     * Retrieve a filter or search parameter value by key.
     *
     * @param  string  $key  The parameter key ('search' or a filter key).
     * @return mixed The parameter value or null if not present.
     */
    public function get(string $key): mixed
    {
        if ($key === 'search') {
            return $this->search;
        }

        return $this->filters[$key] ?? null;
    }

    /**
     * Set or overwrite a filter or search parameter value.
     *
     * @param  string  $key  The parameter key ('search' or a filter key).
     * @param  mixed  $value  The value to set.
     */
    public function set(string $key, mixed $value): void
    {
        if ($key === 'search') {
            $this->search = is_string($value) ? $value : (string) $value;
        } else {
            $this->filters[$key] = $value;
        }
    }

    /**
     * Get an associative array of filters combined with search suitable for Filterable::scopeFilter.
     *
     * @return array<string, mixed>
     */
    public function forScopeFilter(): array
    {
        $scopeFilters = $this->filters;
        if (! empty($this->search)) {
            $scopeFilters['search'] = $this->search;
        }

        return $scopeFilters;
    }

    /**
     * Automatically apply search, filters, sorting, and pagination constraints to an Eloquent query builder.
     *
     * Handles:
     * - SoftDeletes withTrashed when a 'trashed' filter is present.
     * - HindBiswas\ModelUtils\Traits\Filterable (or scopeFilter) integration.
     * - Fallback basic search across $model->searchable properties if Filterable is not used.
     * - Custom sorters or standard column sorting with asc/desc direction.
     * - Sequential sorting fallback (IsSequential / scopeSequenced) or latest created_at.
     * - Deterministic secondary sort by the model's primary key.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  The Eloquent query builder.
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        /** @var TModel $model */
        $model = $query->getModel();

        $query->when(
            $this->has('trashed') && in_array(SoftDeletes::class, class_uses_recursive($model), true),
            fn (Builder $q) => $q->withTrashed() // @phpstan-ignore method.notFound
        );

        // Check if model uses the Filterable & IsSequential trait
        $usesFilterable = in_array(Filterable::class, class_uses_recursive($model), true)
            || method_exists($model, 'scopeFilter');
        $usesSequential = in_array(IsSequential::class, class_uses_recursive($model), true)
            || method_exists($model, 'scopeSequenced');

        if ($usesFilterable) {
            // Combine search and filters into one array for the Filterable scope
            $allParameters = array_merge($this->filters, [
                'search' => $this->search,
            ]);

            $query->filter($allParameters); // @phpstan-ignore method.notFound
        } else {
            // Fallback basic search if the trait isn't present
            if (! empty($this->search) && property_exists($model, 'searchable') && is_array($model->searchable)) {
                $query->where(function (Builder $q) use ($model) {
                    foreach ($model->searchable as $index => $field) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $q->{$method}($field, 'like', "%{$this->search}%");
                    }
                });
            }
        }

        // Apply Sorting
        $direction = $this->order ?? 'asc';

        if (! empty($this->sortBy)) {
            if (isset($this->sorters[$this->sortBy])) {
                ($this->sorters[$this->sortBy])($query, $direction);
            } else {
                $query->orderBy($model->qualifyColumn($this->sortBy), $direction);
            }
        } elseif ($usesSequential) {
            $query->sequenced(); // @phpstan-ignore method.notFound
        } elseif ($model->usesTimestamps() && $model->getCreatedAtColumn()) {
            $query->latest($model->qualifyColumn($model->getCreatedAtColumn()));
        }

        if ($this->sortBy !== $model->getKeyName()) {
            $query->orderBy($model->getQualifiedKeyName());
        }

        return $query;
    }

    /**
     * Get the filters array with the original 'ft_' prefix restored.
     *
     * @return array<string, mixed>
     */
    public function prefixedFilters(): array
    {
        $prefixedFilters = [];
        foreach ($this->filters as $key => $value) {
            $prefixedFilters["ft_{$key}"] = $value;
        }

        return $prefixedFilters;
    }

    /**
     * Convert the listing parameters to an associative array.
     *
     * @return array{
     *     search: string|null,
     *     per_page: int,
     *     sort_by: string|null,
     *     order: string|null,
     *     filters: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'order' => $this->order,
            'filters' => $this->filters,
        ];
    }

    /**
     * Parse an incoming HTTP request into a sanitized ListingParameters instance.
     *
     * Extracts:
     * - `search`: trimmed non-empty string or null.
     * - `per_page`: clamped between configured min/max limits (defaults: 10, 30, 100).
     * - `sort_by`: verified against allowed sort columns/closures or set to null.
     * - `order`: strictly normalized to 'asc', 'desc', or null.
     * - `ft_*`: extracted into filter keys (prefix stripped) and filtered by whitelist if provided.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  array<array-key, string|Closure(Builder<*>, string): void>  $allowedSortColumns  Indexed array of allowed column names or key-value pairs of column to custom Closure.
     * @param  array<string>|null  $allowedFilters  Optional whitelist of allowed filter keys (without 'ft_' prefix).
     */
    public static function parseRequest(
        Request $request,
        array $allowedSortColumns = [],
        ?array $allowedFilters = null,
    ): self {
        // 1. Search
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;

        // 2. Pagination limits
        $minPerPage = (int) config('lmu.pagination.min', 10);
        $defaultPerPage = (int) config('lmu.pagination.default', 30);
        $maxPerPage = (int) config('lmu.pagination.max', 100);

        $perPage = (int) $request->input('per_page', $defaultPerPage);
        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        } elseif ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        // 3. Sorting Column
        $allowedKeys = [];
        $sorters = [];

        foreach ($allowedSortColumns as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $allowedKeys[] = $value;
            } elseif (is_string($key) && $value instanceof Closure) {
                $allowedKeys[] = $key;
                $sorters[$key] = $value;
            }
        }

        $sortBy = $request->input('sort_by');

        if ($sortBy && ! in_array($sortBy, $allowedKeys, true)) {
            $sortBy = null;
        }

        // 4. Order Direction
        $order = strtolower((string) $request->input('order', ''));
        $order = in_array($order, ['asc', 'desc'], true) ? $order : null;

        // 5. Extract only parameters starting with 'ft_'
        $filters = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'ft_') && $value !== null && $value !== '') {
                $filterKey = substr($key, 3); // Strip off 'ft_' (e.g., ft_is_active -> is_active)

                // Whitelist check if $allowedFilters is provided
                if ($allowedFilters === null || in_array($filterKey, $allowedFilters, true)) {
                    $filters[$filterKey] = $value;
                }
            }
        }

        return new self(
            search: $search,
            perPage: $perPage,
            sortBy: $sortBy,
            order: $order,
            filters: $filters,
            sorters: $sorters
        );
    }
}
