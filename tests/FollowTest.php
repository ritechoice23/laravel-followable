<?php

use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->team = Team::create(['name' => 'Team Alpha']);
    $this->organization = Organization::create(['name' => 'Acme Corp']);
});

test('user can follow a team', function () {
    $result = $this->user->follow($this->team);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->team))->toBeTrue()
        ->and(Follow::count())->toBe(1);
});

test('user can follow an organization', function () {
    $result = $this->user->follow($this->organization);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->organization))->toBeTrue();
});

test('user can follow another user', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $result = $this->user->follow($user2);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($user2))->toBeTrue();
});

test('team can follow another team', function () {
    $team2 = Team::create(['name' => 'Team Beta']);

    $result = $this->team->follow($team2);

    expect($result)->toBeTrue()
        ->and($this->team->isFollowing($team2))->toBeTrue();
});

test('follow is idempotent', function () {
    $this->user->follow($this->team);
    $result = $this->user->follow($this->team);

    expect($result)->toBeFalse()
        ->and(Follow::count())->toBe(1);
});

test('user can unfollow a model', function () {
    $this->user->follow($this->team);

    $result = $this->user->unfollow($this->team);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->team))->toBeFalse()
        ->and(Follow::count())->toBe(0);
});

test('unfollow returns false when not following', function () {
    $result = $this->user->unfollow($this->team);

    expect($result)->toBeFalse();
});

test('user can toggle follow', function () {
    $result1 = $this->user->toggleFollow($this->team);
    expect($result1)->toBeTrue()
        ->and($this->user->isFollowing($this->team))->toBeTrue();

    $result2 = $this->user->toggleFollow($this->team);
    expect($result2)->toBeFalse()
        ->and($this->user->isFollowing($this->team))->toBeFalse();
});

test('follow with metadata', function () {
    $this->user->follow($this->team, ['source' => 'web', 'campaign' => 'summer']);

    $follow = Follow::first();
    expect($follow->metadata)->toBe(['source' => 'web', 'campaign' => 'summer']);
});

test('can set and get metadata on follow', function () {
    $this->user->follow($this->team);

    $follow = Follow::first();
    $metadata = $follow->metadata ?? [];
    $metadata['key'] = 'value';
    $follow->metadata = $metadata;
    $follow->save();

    expect($follow->fresh()->metadata['key'])->toBe('value');
});

test('can remove metadata from follow', function () {
    $this->user->follow($this->team, ['key' => 'value']);

    $follow = Follow::first();
    $metadata = $follow->metadata;
    unset($metadata['key']);
    $follow->metadata = $metadata;
    $follow->save();

    expect($follow->fresh()->metadata['key'] ?? null)->toBeNull();
});

test('followingCount returns correct count', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->user->follow($this->team);
    $this->user->follow($this->organization);
    $this->user->follow($user2);

    expect($this->user->followingCount())->toBe(3);
});

test('followersCount returns correct count', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $this->user->follow($this->team);
    $user2->follow($this->team);
    $user3->follow($this->team);

    expect($this->team->followersCount())->toBe(3);
});

test('isFollowedBy returns correct result', function () {
    $this->user->follow($this->team);

    expect($this->team->isFollowedBy($this->user))->toBeTrue();

    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    expect($this->team->isFollowedBy($user2))->toBeFalse();
});
