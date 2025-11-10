<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;
use Ritechoice23\Followable\Tests\Models\Organization;

beforeEach(function () {
    Relation::morphMap([
        'user' => User::class,
        'team' => Team::class,
        'organization' => Organization::class,
    ]);
});

afterEach(function () {
    Relation::morphMap([], false);
});

test('followings works correctly with morphMap', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);

    $user->follow($team);
    $user->follow($org);

    expect($user->followingCount())->toBe(2);

    $followings = $user->followings()->get();

    expect($followings)->toHaveCount(2);
});

test('followers works correctly with morphMap', function () {
    $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user1->follow($team);
    $user2->follow($team);

    expect($team->followersCount())->toBe(2);

    $followers = $team->followers()->get();

    expect($followers)->toHaveCount(2);
});

test('followingsGrouped works correctly with morphMap', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);

    $user->follow($team);
    $user->follow($org);

    $grouped = $user->followingsGrouped();

    expect($grouped)->toHaveCount(2)
        ->and($grouped->has('team'))->toBeTrue()
        ->and($grouped->has('organization'))->toBeTrue()
        ->and($grouped->get('team'))->toHaveCount(1)
        ->and($grouped->get('organization'))->toHaveCount(1);
});

test('followersGrouped works correctly with morphMap', function () {
    $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user1->follow($team);
    $user2->follow($team);

    $grouped = $team->followersGrouped();

    expect($grouped)->toHaveCount(1)
        ->and($grouped->has('user'))->toBeTrue()
        ->and($grouped->get('user'))->toHaveCount(2);
});

test('isFollowing works with morphMap', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user->follow($team);

    expect($user->isFollowing($team))->toBeTrue();
});

test('followings with type filter works with morphMap', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);

    $user->follow($team);
    $user->follow($org);

    // Test with morph key
    $teamFollowings = $user->followings('team')->get();
    expect($teamFollowings)->toHaveCount(1)
        ->and($teamFollowings->first()->id)->toBe($team->id);

    // Test with full class name
    $orgFollowings = $user->followings(Organization::class)->get();
    expect($orgFollowings)->toHaveCount(1)
        ->and($orgFollowings->first()->id)->toBe($org->id);
});

test('followers with type filter works with morphMap', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);

    $user->follow($team);

    // Test with morph key
    $followers = $team->followers('user')->get();
    expect($followers)->toHaveCount(1)
        ->and($followers->first()->id)->toBe($user->id);

    // Test with full class name
    $followers2 = $team->followers(User::class)->get();
    expect($followers2)->toHaveCount(1)
        ->and($followers2->first()->id)->toBe($user->id);
});

test('mutualFollowings works with morphMap', function () {
    $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $team1 = Team::create(['name' => 'Team Alpha']);
    $team2 = Team::create(['name' => 'Team Beta']);

    $user1->follow($team1);
    $user1->follow($team2);

    $user2->follow($team1);
    $user2->follow($team2);

    $mutual = $user1->mutualFollowings($user2);

    expect($mutual)->toHaveCount(2);
});

test('followingsOfType works with morphMap keys', function () {
    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $team = Team::create(['name' => 'Team Alpha']);
    $org = Organization::create(['name' => 'Acme Corp']);

    $user->follow($team);
    $user->follow($org);

    // Test with morph key
    $result = $user->followingsOfType('team')->get();
    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($team->id);

    // Test with array of morph keys
    $result2 = $user->followingsOfType(['team', 'organization'])->get();
    expect($result2)->toHaveCount(2);
});
