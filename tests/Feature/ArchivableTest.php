<?php

use HindBiswas\ModelUtils\Traits\Archivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ArchivableDocument extends Model
{
    use Archivable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'archivable_documents';
}

beforeEach(function () {
    Schema::create('archivable_documents', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->dateTime('archived_at')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('archivable_documents'));

it('archives a model and sets archived_at', function () {
    $doc = ArchivableDocument::create(['title' => 'Important Doc']);

    expect($doc->archived_at)->toBeNull();
    expect($doc->isArchived())->toBeFalse();

    $result = $doc->archive();

    expect($result)->toBeTrue();
    expect($doc->fresh()->archived_at)->not->toBeNull();
    expect($doc->fresh()->isArchived())->toBeTrue();
});

it('restores an archived model and clears archived_at', function () {
    $doc = ArchivableDocument::create(['title' => 'Important Doc']);
    $doc->archive();

    expect($doc->fresh()->isArchived())->toBeTrue();

    $result = $doc->fresh()->restoreArchive();

    expect($result)->toBeTrue();
    expect($doc->fresh()->archived_at)->toBeNull();
    expect($doc->fresh()->isArchived())->toBeFalse();
});

it('excludes archived models by default through global scope', function () {
    ArchivableDocument::create(['title' => 'Active Doc']);
    $archived = ArchivableDocument::create(['title' => 'Archived Doc']);
    $archived->archive();

    $count = ArchivableDocument::query()->count();

    expect($count)->toBe(1);
});

it('retrieves only archived models using scopeOnlyArchived', function () {
    ArchivableDocument::create(['title' => 'Active Doc']);
    $archived1 = ArchivableDocument::create(['title' => 'Archived Doc 1']);
    $archived2 = ArchivableDocument::create(['title' => 'Archived Doc 2']);

    $archived1->archive();
    $archived2->archive();

    $archivedDocs = ArchivableDocument::query()->onlyArchived()->pluck('title')->all();

    expect($archivedDocs)->toContain('Archived Doc 1', 'Archived Doc 2');
    expect($archivedDocs)->not->toContain('Active Doc');
});

it('retrieves all models including archived using scopeWithArchived', function () {
    $active = ArchivableDocument::create(['title' => 'Active Doc']);
    $archived = ArchivableDocument::create(['title' => 'Archived Doc']);

    $archived->archive();

    $allDocs = ArchivableDocument::query()->withArchived()->pluck('title')->all();

    expect($allDocs)->toContain('Active Doc', 'Archived Doc');
    expect(count($allDocs))->toBe(2);
});

it('returns false when archiving an already archived model', function () {
    $doc = ArchivableDocument::create(['title' => 'Doc']);
    $doc->archive();

    $result = $doc->archive();

    expect($result)->toBeFalse();
});

it('returns false when restoring a non-archived model', function () {
    $doc = ArchivableDocument::create(['title' => 'Doc']);

    $result = $doc->restoreArchive();

    expect($result)->toBeFalse();
});

it('casts archived_at as datetime', function () {
    $doc = ArchivableDocument::create(['title' => 'Doc']);
    $doc->archive();

    $fresh = $doc->fresh();

    expect($fresh->archived_at)->toBeInstanceOf(Carbon::class);
});
