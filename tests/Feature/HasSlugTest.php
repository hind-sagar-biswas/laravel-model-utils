<?php

use HindBiswas\ModelUtils\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BasicSlugPost extends Model
{
    use HasSlug;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'slug_posts';

    protected function slugSource(): array
    {
        return ['title'];
    }
}

class UniqueSlugPost extends BasicSlugPost
{
    protected function slugUniqueScope(): array|bool
    {
        return true;
    }
}

class ScopedSlugPost extends BasicSlugPost
{
    protected function slugUniqueScope(): array|bool
    {
        return ['category_id'];
    }
}

class UpdatingSlugPost extends BasicSlugPost
{
    protected function updateSlugOnUpdate(): bool
    {
        return true;
    }
}

class CustomFieldSlugPost extends BasicSlugPost
{
    protected function slugField(): string
    {
        return 'permalink';
    }
}

class NoSourceSlugPost extends Model
{
    use HasSlug;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'slug_posts';
}

beforeEach(function () {
    Schema::create('slug_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('slug')->nullable();
        $table->string('permalink')->nullable();
        $table->unsignedBigInteger('category_id')->nullable();
        $table->string('description')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('slug_posts'));

it('generates slug on create from the configured source', function () {
    $post = BasicSlugPost::create(['title' => 'Hello World']);

    expect($post->slug)->toBe('hello-world');
});

it('does not override an existing slug on create', function () {
    $post = BasicSlugPost::create([
        'title' => 'Hello World',
        'slug' => 'already-set',
    ]);

    expect($post->slug)->toBe('already-set');
});

it('can skip slug generation when no slug source is configured', function () {
    $post = NoSourceSlugPost::create(['title' => 'No Source']);

    expect($post->slug)->toBeNull();
});

it('enforces unique slugs across the entire table when scope is true', function () {
    $first = UniqueSlugPost::create(['title' => 'Same Title']);
    $second = UniqueSlugPost::create(['title' => 'Same Title']);

    expect($first->slug)->toBe('same-title');
    expect($second->slug)->toBe('same-title-1');
});

it('enforces unique slugs only within the configured scope', function () {
    $first = ScopedSlugPost::create(['title' => 'Same Title', 'category_id' => 10]);
    $second = ScopedSlugPost::create(['title' => 'Same Title', 'category_id' => 10]);
    $third = ScopedSlugPost::create(['title' => 'Same Title', 'category_id' => 20]);

    expect($first->slug)->toBe('same-title');
    expect($second->slug)->toBe('same-title-1');
    expect($third->slug)->toBe('same-title');
});

it('updates slug on update only when slug source attributes change', function () {
    $post = UpdatingSlugPost::create([
        'title' => 'Old Title',
        'description' => 'v1',
    ]);

    $post->update(['description' => 'v2']);
    expect($post->fresh()->slug)->toBe('old-title');

    $post->update(['title' => 'New Title']);
    expect($post->fresh()->slug)->toBe('new-title');
});

it('supports custom slug fields and route key name', function () {
    $post = CustomFieldSlugPost::create(['title' => 'Custom Route Key']);

    expect($post->permalink)->toBe('custom-route-key');
    expect($post->getRouteKeyName())->toBe('permalink');
});
