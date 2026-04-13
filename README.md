# Laravel Model Utils

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hindbiswas/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/hindbiswas/laravel-model-utils)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hindbiswas/laravel-model-utils/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hindbiswas/laravel-model-utils/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hindbiswas/laravel-model-utils/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hindbiswas/laravel-model-utils/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hindbiswas/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/hindbiswas/laravel-model-utils)

A lightweight Laravel package that adds practical Eloquent model traits and utilities.

## Installation

You can install the package via composer:

```bash
composer require hindbiswas/laravel-model-utils
```

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Included Features

- `BelongsToAuth`: auto-assign `user_id` when an authenticated user creates a model.
- `Optionable`: convert model records to `value`/`label` option arrays.
- `Filterable`: dynamic exact filters + relationship-aware search with dot notation.
- `HasSlug`: generate slugs from model attributes with optional uniqueness and update behavior.
- `EnumUtil`: convert PHP enums to arrays, CSV, associative maps, and options.

## Usage

### BelongsToAuth Trait

Use this trait when you want `user_id` to be automatically set during creation.

```php
<?php

namespace App\Models;

use HindBiswas\ModelUtils\Traits\BelongsToAuth;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use BelongsToAuth;

    protected $guarded = [];
}
```

Behavior:

- If `user_id` is empty and a user is authenticated, `user_id` is filled from `Auth::id()`.
- If `user_id` is already present, it is not overridden.

### Optionable Trait

Use this trait to return model data as dropdown/select options.

```php
<?php

namespace App\Models;

use HindBiswas\ModelUtils\Traits\Optionable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Optionable;

    protected $guarded = [];
}
```

```php
Category::options();
// [
//   ['value' => 1, 'label' => 'Books'],
//   ['value' => 2, 'label' => 'Games'],
// ]
```

Customize fields:

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

Use this trait for dynamic exact filtering and free-text searching, including related model fields.

```php
<?php

namespace App\Models;

use HindBiswas\ModelUtils\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Filterable;

    protected $guarded = [];

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

- Exact filters use `where`/`whereHas`.
- Search uses grouped `orWhere`/`orWhereHas` with `%search%` matching.
- Dot notation supports nested relations like `owner.user.name`.

### HasSlug Trait

Use this trait to auto-generate slugs from one or more attributes.

```php
<?php

namespace App\Models;

use HindBiswas\ModelUtils\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasSlug;

    protected $guarded = [];

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
- `slugSource()`: source attributes used to build slug (default: empty array).
- `slugUniqueScope()`: controls uniqueness.
  - `false`: no uniqueness checks.
  - `true`: unique across table.
  - `['tenant_id']`: unique within a scope.
- `maxSlugLength()`: maximum slug length (default: `255`).
- `updateSlugOnUpdate()`: regenerate slug when source fields change.

`getRouteKeyName()` automatically returns `slugField()`.

### EnumUtil Helper

`EnumUtil` provides static helpers for pure and backed enums.

```php
<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
```

```php
use HindBiswas\ModelUtils\Utils\EnumUtil;

EnumUtil::toArray(OrderStatus::class);
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

## Quality and Test Coverage

Run tests:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hind Biswas Krishna](https://github.com/hind-sagar-biswas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
