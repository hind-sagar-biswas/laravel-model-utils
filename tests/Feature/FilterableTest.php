<?php

use HindBiswas\ModelUtils\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FilterableOrganization extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'filterable_organizations';
}

class FilterableAuthor extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'filterable_authors';

    public function organization()
    {
        return $this->belongsTo(FilterableOrganization::class, 'organization_id');
    }
}

class FilterableArticle extends Model
{
    use Filterable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'filterable_articles';

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

    public function author()
    {
        return $this->belongsTo(FilterableAuthor::class, 'author_id');
    }
}

class FilterableArticleNoConfig extends Model
{
    use Filterable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'filterable_articles';
}

beforeEach(function () {
    Schema::create('filterable_organizations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('filterable_authors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('organization_id');
    });

    Schema::create('filterable_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('status');
        $table->foreignId('author_id');
    });

    $orgA = FilterableOrganization::create(['name' => 'Acme Labs']);
    $orgB = FilterableOrganization::create(['name' => 'Beta Group']);

    $authorA = FilterableAuthor::create([
        'name' => 'Alice',
        'organization_id' => $orgA->id,
    ]);

    $authorB = FilterableAuthor::create([
        'name' => 'Bob',
        'organization_id' => $orgB->id,
    ]);

    FilterableArticle::create([
        'title' => 'Laravel Guide',
        'status' => 'published',
        'author_id' => $authorA->id,
    ]);

    FilterableArticle::create([
        'title' => 'Draft Notes',
        'status' => 'draft',
        'author_id' => $authorA->id,
    ]);

    FilterableArticle::create([
        'title' => 'Testing Handbook',
        'status' => 'published',
        'author_id' => $authorB->id,
    ]);

    FilterableArticle::create([
        'title' => 'Release Plan',
        'status' => 'archived',
        'author_id' => $authorB->id,
    ]);
});

afterEach(function () {
    Schema::dropIfExists('filterable_articles');
    Schema::dropIfExists('filterable_authors');
    Schema::dropIfExists('filterable_organizations');
});

it('filters by exact local fields', function () {
    $titles = FilterableArticle::query()
        ->filter(['status' => 'published'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Laravel Guide', 'Testing Handbook']);
});

it('filters by related model fields using dot notation', function () {
    $titles = FilterableArticle::query()
        ->filter(['author.name' => 'Alice'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Laravel Guide', 'Draft Notes']);
});

it('filters by nested relationship fields using dot notation', function () {
    $titles = FilterableArticle::query()
        ->filter(['author.organization.name' => 'Beta Group'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Testing Handbook', 'Release Plan']);
});

it('ignores empty filter values', function () {
    $count = FilterableArticle::query()
        ->filter(['status' => ''])
        ->count();

    expect($count)->toBe(4);
});

it('searches across local and relationship fields', function () {
    $titleMatch = FilterableArticle::query()
        ->filter(['search' => 'Guide'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    $authorMatch = FilterableArticle::query()
        ->filter(['search' => 'Alice'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    $nestedMatch = FilterableArticle::query()
        ->filter(['search' => 'Acme'])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titleMatch)->toBe(['Laravel Guide']);
    expect($authorMatch)->toBe(['Laravel Guide', 'Draft Notes']);
    expect($nestedMatch)->toBe(['Laravel Guide', 'Draft Notes']);
});

it('combines exact filters with grouped search constraints', function () {
    $titles = FilterableArticle::query()
        ->filter([
            'status' => 'published',
            'search' => 'Alice',
        ])
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Laravel Guide']);
});

it('returns an unchanged query when filterable and searchable are not configured', function () {
    $count = FilterableArticleNoConfig::query()
        ->filter([
            'status' => 'published',
            'search' => 'Alice',
            'author.name' => 'Alice',
        ])
        ->count();

    expect($count)->toBe(4);
});
