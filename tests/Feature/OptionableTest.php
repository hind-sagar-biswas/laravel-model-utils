<?php

use HindBiswas\ModelUtils\Traits\Optionable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use Optionable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'categories';
}

class Product extends Model
{
    use Optionable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'products';

    protected static function optionValue(): string
    {
        return 'code';
    }

    protected static function optionLabel(): string
    {
        return 'title';
    }
}

beforeEach(function () {
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('title');
    });
});

afterEach(function () {
    Schema::dropIfExists('products');
    Schema::dropIfExists('categories');
});

it('returns id and name pairs by default', function () {
    Category::create(['name' => 'Alpha']);
    Category::create(['name' => 'Beta']);

    expect(Category::options())->toBe([
        ['value' => 1, 'label' => 'Alpha'],
        ['value' => 2, 'label' => 'Beta'],
    ]);

    expect(Category::query()->getOptions())->toBe(Category::options());
});

it('uses the overridden value and label columns', function () {
    Product::create(['code' => 'sku-1', 'title' => 'First product']);
    Product::create(['code' => 'sku-2', 'title' => 'Second product']);

    expect(Product::options())->toBe([
        ['value' => 'sku-1', 'label' => 'First product'],
        ['value' => 'sku-2', 'label' => 'Second product'],
    ]);
});
