<?php

use HindBiswas\ModelUtils\Traits\BelongsToAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Factories\UserFactory;

class Post extends Model
{
    use BelongsToAuth;

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->foreignId('user_id')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('posts'));

it('automatically assigns user_id on create when authenticated', function () {
    $user = UserFactory::new()->create();
    Auth::setUser($user);

    $post = Post::create(['title' => 'Hello']);

    expect($post->user_id)->toBe($user->id);
});

it('does not override user_id if already set', function () {
    $user1 = UserFactory::new()->create();
    $user2 = UserFactory::new()->create();
    Auth::setUser($user1);

    $post = Post::create(['title' => 'Hello', 'user_id' => $user2->id]);

    expect($post->user_id)->toBe($user2->id);
});

it('does not set user_id when not authenticated', function () {
    Auth::logout();

    $post = Post::create(['title' => 'Hello']);

    expect($post->user_id)->toBeNull();
});
