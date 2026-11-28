# Laravel Model Utils

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hindbiswas/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/hindbiswas/laravel-model-utils)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hind-sagar-biswas/laravel-model-utils/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hind-sagar-biswas/laravel-model-utils/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hind-sagar-biswas/laravel-model-utils/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hind-sagar-biswas/laravel-model-utils/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hindbiswas/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/hindbiswas/laravel-model-utils)

A lightweight Laravel package that adds practical Eloquent model traits and utilities.

## Installation

```bash
composer require hindbiswas/laravel-model-utils
```

### Publishing Configuration (Optional)

You can publish the package configuration file with:

```bash
php artisan vendor:publish --tag="lmu-config"
```

This publishes `config/lmu.php`:

```php
return [
    'pagination' => [
        'min' => (int) env('LMU_PAGINATION_MIN', 10),
        'max' => (int) env('LMU_PAGINATION_MAX', 100),
        'default' => (int) env('LMU_PAGINATION_DEFAULT', 30),
    ],
];
```

### Publishing TypeScript Types (Optional)

For Inertia.js, Vue, or React frontend, you can publish TypeScript interfaces for `ListingParameters`:

```bash
php artisan vendor:publish --tag="lmu-types"
```

This will publish `listing.ts` into `resources/js/types/listing.ts`.

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Included Features

- `BelongsToAuth`: auto-assign `user_id` when an authenticated user creates a model.
- `Optionable`: convert model records to `value`/`label` option arrays.
- `Filterable`: dynamic exact filters plus relationship-aware search with dot notation.
- `HasSlug`: generate slugs from model attributes with optional uniqueness and update behavior.
- `Archivable`: archive/restore records with query scopes for active and archived models.
- `EnumUtil`: convert PHP enums to arrays, CSV, associative maps, and options.
- `IsSequential`: auto-assign sequential number to models within a scope.
- `HasIsActive`: automatic boolean casting, active/inactive query scopes, and state toggling.
- `ListingParameters`: parse request query parameters (search, pagination, filters, sorting) and apply them to Eloquent queries.
- `StrUtil`: generate secure random strings and structured receipt/order reference numbers.
- `Global Helpers`: convenient global functions (`parse_params()`, `receipt()`, `enum_array()`).

## Usage

### BelongsToAuth Trait

```php
use HindBiswas\ModelUtils\Traits\BelongsToAuth;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use BelongsToAuth;
}
```

Behavior:

- If `user_id` is empty and a user is authenticated, `user_id` is filled from `Auth::id()`.
- If `user_id` is already present, it is not overridden.

### IsSequential

For this trait to work, must add a column (preferably `unsigned`) to the table of the model with the name: `sequence`.

```php
Schema::create('subjects', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(SchoolClass::class)->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unsignedInteger('sequence')->default(1);
    $table->string('code', 20)->nullable();
    $table->timestamps();

    $table->unique(['school_class_id', 'sequence']);
});
```

Here, for the example, the sequence is scoped to the `school_class_id`, and thus provide that via property.

```php
use HindBiswas\ModelUtils\Traits\IsSequential;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use IsSequential;

    protected array $sequentialUniqueWith = ['school_class_id'];    // only if the sequence is unique in respect to one or more columns
}
```

This comes with `moveUp()`, `moveDown()`, `swap()` methods, as well as `->sequenced()` scope for ordering.

### Optionable Trait

```php
use HindBiswas\ModelUtils\Traits\Optionable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Optionable;
}
```

```php
Category::options();
// [
//   ['value' => 1, 'label' => 'Books'],
//   ['value' => 2, 'label' => 'Games'],
// ]
```

You can override the columns used for options:

```php
protected static function optionValue(): string
{
    return 'code';
}

protected static function optionLabel(): string
{
    return 'title';
}
```

### Filterable Trait

```php
use HindBiswas\ModelUtils\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Filterable;

    protected array $filterable = [
        'status',
        'author.name',
        'author.organization.name',
    ];

    protected array $searchable = [
        'title',
        'author.name',
        'author.organization.name',
    ];
}
```

```php
$articles = Article::query()->filter([
    'status' => 'published',
    'author.name' => 'Alice',
    'search' => 'Laravel',
])->get();
```

Notes:

- Exact filters use `where` and `whereHas`.
- Search uses grouped `orWhere` and `orWhereHas` with `%search%` matching.
- Dot notation supports nested relations like `owner.user.name`.

### HasSlug Trait

```php
use HindBiswas\ModelUtils\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasSlug;

    protected function slugSource(): array
    {
        return ['title'];
    }

    protected function slugUniqueScope(): array|bool
    {
        return true;
    }

    protected function updateSlugOnUpdate(): bool
    {
        return true;
    }
}
```

Available hooks:

- `slugField()`: slug column name (default: `slug`).
- `slugSource()`: source attributes used to build slug.
- `slugUniqueScope()`: controls uniqueness.
  - `false`: no uniqueness checks.
  - `true`: unique across table.
  - `['tenant_id']`: unique within a scope.
- `maxSlugLength()`: maximum slug length (default: `255`).
- `updateSlugOnUpdate()`: regenerate slug when source fields change.

`getRouteKeyName()` returns `slugField()`.

### HasIsActive Trait

For this trait to work, add a `boolean` column named `is_active` to the table:

```php
Schema::table('authors', function (Blueprint $table) {
    $table->boolean('is_active')->default(true);
});
```

Add the trait to your model:

```php
use HindBiswas\ModelUtils\Traits\HasIsActive;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasIsActive;
}
```

Behavior & Features:

- **Automatic Casting**: Automatically registers `'is_active' => 'boolean'` in the model's attribute casts upon initialization, preserving any custom casts already defined.
- **Query Scopes**:
  ```php
  // Retrieve only active records (where is_active = true)
  Author::query()->active()->get();

  // Retrieve only inactive records (where is_active = false)
  Author::query()->inactive()->get();
  ```
- **Toggle Active**:
  ```php
  $author = Author::first();

  // Inverts is_active boolean value and persists to database
  $author->toggleActive();
  ```


### Archivable Trait

```php
use HindBiswas\ModelUtils\Traits\Archivable;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use Archivable;
}
```

Your table should have an `archived_at` nullable datetime column.

```php
Schema::table('documents', function (Blueprint $table) {
    $table->dateTime('archived_at')->nullable();
});
```

```php
$document->archive();
$document->restoreArchive();
$document->isArchived();

Document::query()->onlyArchived()->get();
Document::query()->withArchived()->get();
```

Behavior:

- Active records are returned by default because a global scope excludes archived rows.
- `onlyArchived()` fetches only archived records.
- `withArchived()` fetches both active and archived records.
- `archived_at` is cast to `datetime` automatically.

### EnumUtil Helper

```php
use HindBiswas\ModelUtils\Utils\EnumUtil;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

EnumUtil::toArray(OrderStatus::class);
// ['draft', 'published']

// Or use the global helper shortcut:
enum_array(OrderStatus::class);
// ['draft', 'published']

EnumUtil::toCSV(OrderStatus::class);
// 'draft,published'

EnumUtil::toAssocArray(OrderStatus::class);
// ['draft' => 'Draft', 'published' => 'Published']

EnumUtil::toOptions(OrderStatus::class);
// [
//   ['value' => 'draft', 'label' => 'Draft'],
//   ['value' => 'published', 'label' => 'Published'],
// ]
```

If enum cases implement a `label()` method, `EnumUtil` uses it. Otherwise, labels are generated using `Str::headline(...)`.

### StrUtil Helper

Provides string generation utilities for cryptographically secure random strings and structured, human-friendly receipt and reference numbers.

```php
use HindBiswas\ModelUtils\Utils\StrUtil;

// 1. Generate a secure random string (default: 16 alphanumeric characters)
StrUtil::random(); // e.g., 'aB3dE7gH1jK9mN5p'
StrUtil::random(32); // 32 characters

// Custom character sets
StrUtil::random(10, '0123456789'); // Numeric only, e.g. '8392019485'
StrUtil::random(8, 'abcdef0123456789'); // Hexadecimal lowercase

// 2. Generate a structured receipt number
// Format: {prefix}{date(midFormat)}{randomSuffix}
// Total length matches the specified length parameter.
// Uses an unambiguous uppercase alphanumeric character set (excludes 0, 1, I, O to prevent confusion).
StrUtil::receipt('REC-', 16); // e.g., 'REC-2026X9K7M4P2' (midFormat defaults to 'Y')
StrUtil::receipt('INV/', 20, 'ymd'); // e.g., 'INV/261122H3K9N2P5W8'

// Global helper shortcut
receipt('REC-', 16);
```

### ListingParameters Utility

`ListingParameters` parses, validates, and applies HTTP listing query parameters (search query, pagination limits, sorting column/direction, and `ft_` prefixed filters) directly to Eloquent query builders.

#### Controller Usage

You can use the `parse_params()` helper (which auto-resolves the current request if omitted) or `ListingParameters::parseRequest()` directly:

```php
use App\Models\Article;
use Illuminate\Http\Request;

public function index(Request $request)
{
    // Using the global helper function:
    $params = parse_params(
        allowedSortColumns: [
            'title',
            'created_at',
            'custom_ranking' => fn ($query, $direction) => $query->orderBy('views', $direction),
        ],
        allowedFilters: ['status', 'category_id'] // optional whitelist (without 'ft_' prefix)
    );

    // Or explicitly passing $request:
    // $params = parse_params($request, $allowedSortColumns, $allowedFilters);

    $articles = $params->apply(Article::query())
        ->paginate($params->perPage);

    return view('articles.index', compact('articles', 'params'));
}
```

#### Query String Conventions

- **Search**: `?search=Laravel` &mdash; trimmed string keyword.
- **Pagination**: `?per_page=25` &mdash; clamped between configured min and max bounds (defaults: min 10, default 30, max 100).
- **Sorting**: `?sort_by=title&order=desc` &mdash; validated against `allowedSortColumns`, and `order` is strictly `'asc'` or `'desc'`.
- **Filters**: `?ft_status=published&ft_category_id=5` &mdash; only inputs prefixed with `ft_` are extracted as filters (stripping the `ft_` prefix, dropping empty strings and nulls).

#### What `apply()` Handles

When `$params->apply($query)` is called:
1. **SoftDeletes**: Automatically applies `withTrashed()` if a `trashed` filter is present and the model uses Laravel's `SoftDeletes` trait.
2. **Filterable Integration**: If the model uses the `Filterable` trait (or defines `scopeFilter`), passes all filters and search term via `$query->filter(...)`.
3. **Fallback Search**: If the model lacks `Filterable` but defines a `$searchable` property array (e.g. `public array $searchable = ['title', 'content'];`), groups `orWhere` `like %search%` clauses across those fields.
4. **Sorting**:
   - Executes custom closure sorters if registered in `allowedSortColumns`.
   - Otherwise applies standard column sorting with asc/desc direction.
   - If `sort_by` is not specified, falls back to `IsSequential` (`sequenced()`) if present, or latest `created_at` timestamp.
   - Applies deterministic secondary ordering by the model's primary key.

#### Helper Methods

```php
// Check and inspect parameters
$params->has('status');      // bool
$params->get('status');      // mixed
$params->set('status', 'draft');

// Array transformations
$params->toArray();          // Associative array with search, per_page, sort_by, order, filters
$params->prefixedFilters();  // ['ft_status' => 'published'] (restores 'ft_' prefix)
$params->forScopeFilter();   // Filters array combined with 'search' keyword
```

#### Pagination Configuration

Pagination limits can be customized by publishing `config/lmu.php`:

```bash
php artisan vendor:publish --tag="lmu-config"
```

In `config/lmu.php`:

```php
return [
    'pagination' => [
        'min' => (int) env('LMU_PAGINATION_MIN', 10),
        'max' => (int) env('LMU_PAGINATION_MAX', 100),
        'default' => (int) env('LMU_PAGINATION_DEFAULT', 30),
    ],
];
```

You can also adjust these directly in your `.env` file without publishing:

```env
LMU_PAGINATION_MIN=10
LMU_PAGINATION_MAX=100
LMU_PAGINATION_DEFAULT=30
```

#### TypeScript Types for Frontend (Inertia.js / Vue / React)

When passing `params` to your frontend via Inertia.js (e.g., `$params->toArray()`), you can publish the matching TypeScript interfaces:

```bash
php artisan vendor:publish --tag="lmu-types"
```

This publishes `listing.ts` to `resources/js/types/listing.ts`:

```typescript
export type SortOrder = 'asc' | 'desc';
export type FilterValue =
    | string
    | number
    | boolean
    | null
    | Array<string | number>;

/** Mirrors ListingParameters::toArray() */
export interface ListingProps<F extends string = string> {
    search: string | null;
    per_page: number;
    sort_by: string | null;
    order: SortOrder | null;
    filters: Partial<Record<F, FilterValue>> | [];
}
```

Example usage in an Inertia Vue component (`Index.vue`):

```vue
<script setup lang="ts">
import type { ListingProps } from '@/types/listing';

type ArticleFilters = 'status' | 'category_id';

defineProps<{
    articles: {
        data: Array<{ id: number; title: string }>;
        // ... pagination links & meta
    };
    params: ListingProps<ArticleFilters>;
}>();
</script>
```

### Global Helpers

The package registers global helper functions automatically:

| Helper | Description |
|---|---|
| `parse_params(?$request, $allowedSortColumns, $allowedFilters)` | Parses current (or passed) request into a `ListingParameters` instance. |
| `receipt($prefix, $length, $midFormat = 'Y')` | Generates a structured reference/receipt number. |
| `enum_array($enumClass)` | Converts backed enums to values or pure enums to names (`EnumUtil::toArray()` shortcut). |

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for details.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hind Biswas Krishna](https://github.com/hind-sagar-biswas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
