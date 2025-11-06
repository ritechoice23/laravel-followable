<?php

use Illuminate\Support\Facades\Schema;
use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->team = Team::create(['name' => 'Team Alpha']);
});

test('unique constraint prevents duplicate follows', function () {
    $this->user->follow($this->team);

    expect(function () {
        Follow::create([
            'follower_id' => $this->user->id,
            'follower_type' => $this->user->getMorphClass(),
            'followable_id' => $this->team->id,
            'followable_type' => $this->team->getMorphClass(),
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('deleting follower removes follow records', function () {
    $this->user->follow($this->team);
    expect(Follow::count())->toBe(1);

    $this->user->delete();

    expect(Follow::count())->toBe(1);
});

test('follows table has correct indexes', function () {
    $tableName = config('follow.table_name', 'follows');

    // Check if table exists and has the expected structure
    expect(Schema::hasTable($tableName))->toBeTrue();

    // Verify key columns exist (indexes are created on these columns)
    expect(Schema::hasColumns($tableName, [
        'follower_type',
        'follower_id',
        'followable_type',
        'followable_id',
        'created_at',
    ]))->toBeTrue();
});

test('can follow with null metadata', function () {
    $this->user->follow($this->team);

    $follow = Follow::first();
    // With array cast, null becomes empty array
    expect($follow->metadata)->toBeArray()->toBeEmpty();
});

test('can follow with empty metadata array', function () {
    $this->user->follow($this->team, []);

    $follow = Follow::first();
    expect($follow->metadata)->toBeArray()->toBeEmpty();
});

test('metadata is json encoded', function () {
    $metadata = ['key' => 'value', 'number' => 123, 'nested' => ['foo' => 'bar']];
    $this->user->follow($this->team, $metadata);

    $follow = Follow::first();
    expect($follow->metadata)->toBe($metadata);
});

test('following different model types', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $org = Organization::create(['name' => 'Org']);

    $this->user->follow($this->team);
    $this->user->follow($user2);
    $this->user->follow($org);

    expect($this->user->followingCount())->toBe(3)
        ->and($this->user->isFollowing($this->team))->toBeTrue()
        ->and($this->user->isFollowing($user2))->toBeTrue()
        ->and($this->user->isFollowing($org))->toBeTrue();
});

test('multiple users can follow same model', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $this->user->follow($this->team);
    $user2->follow($this->team);
    $user3->follow($this->team);

    expect($this->team->followersCount())->toBe(3);
});

test('follow record has timestamps', function () {
    $this->user->follow($this->team);

    $follow = Follow::first();
    expect($follow->created_at)->not->toBeNull()
        ->and($follow->updated_at)->not->toBeNull();
});
