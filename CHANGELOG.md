# Changelog

All notable changes to `laravel-model-utils` will be documented in this file.

## First Light IV - 2026-11-22

### What's Changed

#### New Features & Traits

* **`HasIsActive` Trait**: Added model trait with automatic boolean casting for `is_active`, `scopeActive()`, `scopeInactive()`, and `toggleActive()`.
* **`ListingParameters` Utility**: Added query parameter helper for parsing and applying search keywords, pagination limits, sort columns/directions (with custom Closure sorters), and `ft_` prefixed filters to Eloquent query builders.
* **`StrUtil` Utility**: Added string utility for cryptographically secure random string generation (`StrUtil::random()`) and unambiguous, date-formatted receipt numbers (`StrUtil::receipt()`).
* **Global Helper Functions**:
  * `parse_params()`: Shorthand to parse listing parameters directly from the current (or injected) request.
  * `receipt()`: Shorthand for generating structured, human-friendly receipt numbers.
  * `enum_array()`: Shorthand for `EnumUtil::toArray()` to convert enums to arrays.
  
* **Publishable Assets & Configuration**:
  * Added `config/lmu.php` publishable via `php artisan vendor:publish --tag="lmu-config"`.
  * Added TypeScript interfaces (`resources/types/listing.ts`) publishable via `php artisan vendor:publish --tag="lmu-types"` for typed Inertia.js / frontend integration.
  

#### Bug Fixes & Refactoring

* **`Filterable` Trait**: Fixed an issue where searching for `'0'` was treated as empty and ignored.
* **`Filterable` Trait**: Fixed column qualification in nested `whereHas` and `orWhereHas` queries.
* **Internal Visibility**: Refactored internal helper methods across `Filterable`, `HasSlug`, and `IsSequential` to `private`.
* **Documentation & Testing**: Comprehensive PHPDocs across all classes/traits and added test suites for all new features (128 passing tests, 877 assertions).

**Full Changelog**: https://github.com/hind-sagar-biswas/laravel-model-utils/compare/v1.1.2...v1.2.0

## First Light III - 2026-10-25

### What's Changed

* Added new Trait: `IsSequential`
* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/hind-sagar-biswas/laravel-model-utils/pull/1
* Update orchestra/testbench requirement from ^10.0.0||^9.0.0 to ^11.1.0 by @dependabot[bot] in https://github.com/hind-sagar-biswas/laravel-model-utils/pull/2
* Bump actions/checkout from 6 to 7 by @dependabot[bot] in https://github.com/hind-sagar-biswas/laravel-model-utils/pull/3

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/hind-sagar-biswas/laravel-model-utils/pull/1

**Full Changelog**: https://github.com/hind-sagar-biswas/laravel-model-utils/compare/v1.1.1...v1.1.2

## First Light I - 2026-04-14

### Added

- `Archivable` trait with archive and restore operations, default active-record scope, and archived scopes.
- Feature tests added for `Archivable`

**Full Changelog**: https://github.com/hind-sagar-biswas/laravel-model-utils/compare/v1.0.0...v1.1.0

## First Light - 2026-04-13

**Full Changelog**: https://github.com/hind-sagar-biswas/laravel-model-utils/commits/v1.0.0
