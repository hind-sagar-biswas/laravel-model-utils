<?php

use HindBiswas\ModelUtils\Traits\Filterable;
use HindBiswas\ModelUtils\Traits\IsSequential;
use HindBiswas\ModelUtils\Utils\ListingParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ListingTestArticle extends Model
{
    use Filterable;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'listing_test_articles';

    protected array $filterable = [
        'status',
        'category',
    ];

    protected array $searchable = [
        'title',
    ];
}

class ListingTestSequentialArticle extends Model
{
    use IsSequential;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'listing_test_sequential_articles';
}

class ListingTestFallbackArticle extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'listing_test_fallback_articles';

    public array $searchable = [
        'title',
        'content',
    ];
}

beforeEach(function () {
    Schema::create('listing_test_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('status')->default('active');
        $table->string('category')->default('general');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('listing_test_sequential_articles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('sequence');
    });

    Schema::create('listing_test_fallback_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('content');
    });
});

afterEach(function () {
    Schema::dropIfExists('listing_test_articles');
    Schema::dropIfExists('listing_test_sequential_articles');
    Schema::dropIfExists('listing_test_fallback_articles');
});

/*
|--------------------------------------------------------------------------
| Constructor & Basic Methods
|--------------------------------------------------------------------------
*/

it('initializes properties and filters out null and empty string filter values', function () {
    $params = new ListingParameters(
        search: 'keyword',
        perPage: 25,
        sortBy: 'title',
        order: 'desc',
        filters: [
            'status' => 'published',
            'empty_str' => '',
            'null_val' => null,
            'zero_num' => 0,
            'zero_str' => '0',
            'boolean_false' => false,
        ]
    );

    expect($params->search)->toBe('keyword')
        ->and($params->perPage)->toBe(25)
        ->and($params->sortBy)->toBe('title')
        ->and($params->order)->toBe('desc')
        ->and($params->filters)->toBe([
            'status' => 'published',
            'zero_num' => 0,
            'zero_str' => '0',
            'boolean_false' => false,
        ]);
});

it('checks key existence with has()', function () {
    $params = new ListingParameters(
        search: 'laravel',
        perPage: 15,
        sortBy: null,
        order: null,
        filters: [
            'status' => 'active',
            'zero' => 0,
        ]
    );

    expect($params->has('search'))->toBeTrue()
        ->and($params->has('status'))->toBeTrue()
        ->and($params->has('zero'))->toBeTrue()
        ->and($params->has('non_existent'))->toBeFalse();

    $paramsWithoutSearch = new ListingParameters(
        search: '',
        perPage: 15,
        sortBy: null,
        order: null,
        filters: []
    );

    expect($paramsWithoutSearch->has('search'))->toBeFalse();

    $paramsWithNullSearch = new ListingParameters(
        search: null,
        perPage: 15,
        sortBy: null,
        order: null,
        filters: []
    );

    expect($paramsWithNullSearch->has('search'))->toBeFalse();
});

it('retrieves parameter values with get()', function () {
    $params = new ListingParameters(
        search: 'term',
        perPage: 20,
        sortBy: 'name',
        order: 'asc',
        filters: ['role' => 'admin']
    );

    expect($params->get('search'))->toBe('term')
        ->and($params->get('role'))->toBe('admin')
        ->and($params->get('missing'))->toBeNull();
});

it('sets parameter values with set()', function () {
    $params = new ListingParameters(
        search: null,
        perPage: 20,
        sortBy: null,
        order: null,
        filters: []
    );

    $params->set('search', 'updated query');
    $params->set('status', 'draft');

    expect($params->search)->toBe('updated query')
        ->and($params->get('search'))->toBe('updated query')
        ->and($params->get('status'))->toBe('draft')
        ->and($params->filters['status'])->toBe('draft');
});

it('prepares filter array for Filterable scope using forScopeFilter()', function () {
    $paramsWithSearch = new ListingParameters(
        search: 'phpunit',
        perPage: 10,
        sortBy: null,
        order: null,
        filters: ['status' => 'active']
    );

    expect($paramsWithSearch->forScopeFilter())->toBe([
        'status' => 'active',
        'search' => 'phpunit',
    ]);

    $paramsWithoutSearch = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null,
        filters: ['status' => 'active']
    );

    expect($paramsWithoutSearch->forScopeFilter())->toBe([
        'status' => 'active',
    ]);
});

it('restores ft_ prefix with prefixedFilters()', function () {
    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null,
        filters: [
            'status' => 'active',
            'author_id' => 5,
        ]
    );

    expect($params->prefixedFilters())->toBe([
        'ft_status' => 'active',
        'ft_author_id' => 5,
    ]);
});

it('converts instance to array representation using toArray()', function () {
    $params = new ListingParameters(
        search: 'keyword',
        perPage: 50,
        sortBy: 'created_at',
        order: 'desc',
        filters: ['category' => 'news']
    );

    expect($params->toArray())->toBe([
        'search' => 'keyword',
        'per_page' => 50,
        'sort_by' => 'created_at',
        'order' => 'desc',
        'filters' => ['category' => 'news'],
    ]);
});

/*
|--------------------------------------------------------------------------
| parseRequest()
|--------------------------------------------------------------------------
*/

it('parses valid search parameter and trims whitespace', function () {
    $request = Request::create('/test', 'GET', [
        'search' => '  hello world  ',
    ]);

    $params = ListingParameters::parseRequest($request);

    expect($params->search)->toBe('hello world');
});

it('nullifies search when empty or not filled', function () {
    $requestEmpty = Request::create('/test', 'GET', ['search' => '   ']);
    $requestMissing = Request::create('/test', 'GET', []);

    expect(ListingParameters::parseRequest($requestEmpty)->search)->toBeNull()
        ->and(ListingParameters::parseRequest($requestMissing)->search)->toBeNull();
});

it('handles pagination limits with defaults and clamps', function () {
    config()->set('app.pagination.min', 10);
    config()->set('app.pagination.default', 30);
    config()->set('app.pagination.max', 100);

    // Default when omitted
    $defaultReq = Request::create('/test', 'GET', []);
    expect(ListingParameters::parseRequest($defaultReq)->perPage)->toBe(30);

    // Within bounds
    $normalReq = Request::create('/test', 'GET', ['per_page' => 50]);
    expect(ListingParameters::parseRequest($normalReq)->perPage)->toBe(50);

    // Clamped below min
    $tooLowReq = Request::create('/test', 'GET', ['per_page' => 5]);
    expect(ListingParameters::parseRequest($tooLowReq)->perPage)->toBe(10);

    // Clamped above max
    $tooHighReq = Request::create('/test', 'GET', ['per_page' => 200]);
    expect(ListingParameters::parseRequest($tooHighReq)->perPage)->toBe(100);
});

it('whitelists allowed sort columns and filters out disallowed ones', function () {
    $allowed = ['title', 'created_at'];

    $validReq = Request::create('/test', 'GET', ['sort_by' => 'title']);
    expect(ListingParameters::parseRequest($validReq, $allowed)->sortBy)->toBe('title');

    $invalidReq = Request::create('/test', 'GET', ['sort_by' => 'password']);
    expect(ListingParameters::parseRequest($invalidReq, $allowed)->sortBy)->toBeNull();
});

it('supports custom Closure sorters in allowedSortColumns', function () {
    $customSorter = fn (Builder $q, string $direction) => $q->orderBy('custom_field', $direction);

    $allowed = [
        'title',
        'custom' => $customSorter,
    ];

    $request = Request::create('/test', 'GET', ['sort_by' => 'custom']);
    $params = ListingParameters::parseRequest($request, $allowed);

    expect($params->sortBy)->toBe('custom')
        ->and($params->sorters)->toHaveKey('custom')
        ->and($params->sorters['custom'])->toBe($customSorter);
});

it('normalizes order direction to asc or desc and discards invalid values', function () {
    expect(ListingParameters::parseRequest(Request::create('/test', 'GET', ['order' => 'asc']))->order)->toBe('asc')
        ->and(ListingParameters::parseRequest(Request::create('/test', 'GET', ['order' => 'DESC']))->order)->toBe('desc')
        ->and(ListingParameters::parseRequest(Request::create('/test', 'GET', ['order' => 'invalid']))->order)->toBeNull()
        ->and(ListingParameters::parseRequest(Request::create('/test', 'GET', []))->order)->toBeNull();
});

it('extracts only ft_ prefixed filters and strips the prefix', function () {
    $request = Request::create('/test', 'GET', [
        'page' => 2,
        'unrelated_param' => 'ignored',
        'ft_status' => 'published',
        'ft_category' => 'tech',
        'ft_empty' => '',
    ]);

    $params = ListingParameters::parseRequest($request);

    expect($params->filters)->toBe([
        'status' => 'published',
        'category' => 'tech',
    ]);
});

it('whitelists ft_ filters when allowedFilters array is provided', function () {
    $request = Request::create('/test', 'GET', [
        'ft_status' => 'published',
        'ft_category' => 'tech',
        'ft_secret' => 'forbidden',
    ]);

    $params = ListingParameters::parseRequest($request, [], ['status', 'category']);

    expect($params->filters)->toBe([
        'status' => 'published',
        'category' => 'tech',
    ])
        ->and($params->filters)->not->toHaveKey('secret');
});

/*
|--------------------------------------------------------------------------
| apply() Query Building
|--------------------------------------------------------------------------
*/

it('applies withTrashed when trashed filter is set on soft-deletable model', function () {
    $active = ListingTestArticle::create(['title' => 'Article 1', 'status' => 'active']);
    $deleted = ListingTestArticle::create(['title' => 'Article 2', 'status' => 'active']);
    $deleted->delete();

    // Without trashed filter: only active returned
    $withoutTrashed = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null,
        filters: []
    );
    $resultsWithout = $withoutTrashed->apply(ListingTestArticle::query())->pluck('id')->all();
    expect($resultsWithout)->toBe([$active->id]);

    // With trashed filter: both active and soft-deleted returned
    $withTrashed = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null,
        filters: ['trashed' => true]
    );
    $resultsWith = $withTrashed->apply(ListingTestArticle::query())->pluck('id')->all();
    expect($resultsWith)->toContain($active->id, $deleted->id);
});

it('applies Filterable filters and search correctly', function () {
    ListingTestArticle::create(['title' => 'Laravel Deep Dive', 'status' => 'published', 'category' => 'backend']);
    ListingTestArticle::create(['title' => 'Vue 3 Overview', 'status' => 'published', 'category' => 'frontend']);
    ListingTestArticle::create(['title' => 'Laravel Tips', 'status' => 'draft', 'category' => 'backend']);

    $params = new ListingParameters(
        search: 'Laravel',
        perPage: 10,
        sortBy: null,
        order: null,
        filters: ['status' => 'published']
    );

    $titles = $params->apply(ListingTestArticle::query())->pluck('title')->all();

    expect($titles)->toBe(['Laravel Deep Dive']);
});

it('uses fallback basic search when model lacks Filterable but defines searchable property', function () {
    ListingTestFallbackArticle::create(['title' => 'Guide to PHP', 'content' => 'All about PHP']);
    ListingTestFallbackArticle::create(['title' => 'Guide to Rust', 'content' => 'All about Rust']);
    ListingTestFallbackArticle::create(['title' => 'Intro', 'content' => 'Contains PHP reference inside content']);

    $params = new ListingParameters(
        search: 'PHP',
        perPage: 10,
        sortBy: null,
        order: null,
        filters: []
    );

    $titles = $params->apply(ListingTestFallbackArticle::query())->pluck('title')->all();

    expect($titles)->toContain('Guide to PHP', 'Intro')
        ->and($titles)->not->toContain('Guide to Rust');
});

it('sorts by specified sortBy column and direction', function () {
    ListingTestArticle::create(['title' => 'Zebra', 'created_at' => now()->subDays(2)]);
    ListingTestArticle::create(['title' => 'Alpha', 'created_at' => now()->subDay()]);

    $paramsAsc = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: 'title',
        order: 'asc'
    );
    $titlesAsc = $paramsAsc->apply(ListingTestArticle::query())->pluck('title')->all();
    expect($titlesAsc)->toBe(['Alpha', 'Zebra']);

    $paramsDesc = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: 'title',
        order: 'desc'
    );
    $titlesDesc = $paramsDesc->apply(ListingTestArticle::query())->pluck('title')->all();
    expect($titlesDesc)->toBe(['Zebra', 'Alpha']);
});

it('sorts using custom sorter closure when provided', function () {
    ListingTestArticle::create(['title' => 'Article B', 'category' => 'Z']);
    ListingTestArticle::create(['title' => 'Article A', 'category' => 'A']);

    $customSorter = fn (Builder $q, string $dir) => $q->orderBy('category', $dir);

    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: 'by_category',
        order: 'asc',
        sorters: ['by_category' => $customSorter]
    );

    $titles = $params->apply(ListingTestArticle::query())->pluck('title')->all();

    expect($titles)->toBe(['Article A', 'Article B']);
});

it('falls back to sequenced scope when sortBy is null and model uses IsSequential', function () {
    ListingTestSequentialArticle::create(['name' => 'Second', 'sequence' => 2]);
    ListingTestSequentialArticle::create(['name' => 'First', 'sequence' => 1]);
    ListingTestSequentialArticle::create(['name' => 'Third', 'sequence' => 3]);

    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null
    );

    $names = $params->apply(ListingTestSequentialArticle::query())->pluck('name')->all();

    expect($names)->toBe(['First', 'Second', 'Third']);
});

it('falls back to latest created_at when sortBy is null and model has timestamps', function () {
    $first = ListingTestArticle::create(['title' => 'First', 'created_at' => now()->subHour()]);
    $second = ListingTestArticle::create(['title' => 'Second', 'created_at' => now()]);

    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: null,
        order: null
    );

    $titles = $params->apply(ListingTestArticle::query())->pluck('title')->all();

    expect($titles)->toBe(['Second', 'First']);
});

it('adds deterministic secondary order by primary key', function () {
    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: 'title',
        order: 'asc'
    );

    $builder = $params->apply(ListingTestArticle::query());
    $orders = $builder->getQuery()->orders ?? [];

    expect($orders)->toBeArray()
        ->and($orders[0]['column'])->toBe('listing_test_articles.title')
        ->and($orders[1]['column'])->toBe('listing_test_articles.id');
});

it('does not duplicate primary key sort when sortBy is primary key', function () {
    $params = new ListingParameters(
        search: null,
        perPage: 10,
        sortBy: 'id',
        order: 'desc'
    );

    $builder = $params->apply(ListingTestArticle::query());
    $orders = $builder->getQuery()->orders ?? [];

    expect(count($orders))->toBe(1)
        ->and($orders[0]['column'])->toBe('listing_test_articles.id')
        ->and($orders[0]['direction'])->toBe('desc');
});
