<?php

use Illuminate\Database\Eloquent\Model;
use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

test('user can follow multiple different model types', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->follow($team);
    $user->follow($org);
    $user->follow($user2);

    expect($user->followingCount())->toBe(3)
        ->and(Follow::where('follower_id', $user->id)->count())->toBe(3);

    $follows = Follow::where('follower_id', $user->id)->get();
    $types = $follows->pluck('followable_type')->unique()->toArray();

    expect($types)->toHaveCount(3);
});

test('team can follow user and other teams', function () {
    $team = Team::create(['name' => 'Team Alpha']);
    $team2 = Team::create(['name' => 'Team Beta']);
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $team->follow($team2);
    $team->follow($user);

    expect($team->followingCount())->toBe(2)
        ->and($team->isFollowing($team2))->toBeTrue()
        ->and($team->isFollowing($user))->toBeTrue();
});

test('organization can be followed by different model types', function () {
    $org = Organization::create(['name' => 'Acme Corp']);
    $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user1->follow($org);
    $user2->follow($org);
    $team->follow($org);

    expect($org->followersCount())->toBe(3);

    $followers = Follow::where('followable_id', $org->id)
        ->where('followable_type', $org->getMorphClass())
        ->get();

    $followerTypes = $followers->pluck('follower_type')->unique()->toArray();
    expect($followerTypes)->toHaveCount(2);
});

test('polymorphic follow relationships are correctly stored', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user->follow($team);

    $follow = Follow::first();

    expect($follow->follower_type)->toBe($user->getMorphClass())
        ->and($follow->follower_id)->toBe($user->id)
        ->and($follow->followable_type)->toBe($team->getMorphClass())
        ->and($follow->followable_id)->toBe($team->id);
});

test('polymorphic relationships can be eager loaded', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);

    $user->follow($team);
    $user->follow($org);

    $follows = Follow::with(['follower', 'followable'])->get();

    expect($follows)->toHaveCount(2);

    foreach ($follows as $follow) {
        expect($follow->relationLoaded('follower'))->toBeTrue()
            ->and($follow->relationLoaded('followable'))->toBeTrue()
            ->and($follow->follower)->toBeInstanceOf(User::class)
            ->and($follow->followable)->toBeInstanceOf(Model::class);
    }
});

test('circular follows are possible between same model types', function () {
    $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user1->follow($user2);
    $user2->follow($user1);

    expect($user1->isFollowing($user2))->toBeTrue()
        ->and($user2->isFollowing($user1))->toBeTrue()
        ->and($user1->followersCount())->toBe(1)
        ->and($user2->followersCount())->toBe(1);
});

test('getMorphClass returns correct values', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    expect($user->getMorphClass())->toBeString()
        ->and($team->getMorphClass())->toBeString()
        ->and($user->getMorphClass())->not->toBe($team->getMorphClass());
});
